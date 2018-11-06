<?php

namespace App\Http\Resources\Api\v1;

use App\Models\v1\ProductsMeta;
use App\Models\v1\UsersMeta;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Resources\Json\Resource;

class VendorResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        if ($this->getUserLevel($this->ID) == 0) {
            return [
                'vendor_id' => $this->ID,
                'vendor_name' => $this->getFullName($this->ID),
                'cleared_amount' => $this->getClearedAmount($this->ID),
                'completed_amount' => $this->getCompletedAmount($this->ID),
                'processing_amount' => $this->getProcessingAmount($this->ID)
            ];
        }

    }

    public function getClearedAmount($vendorID)
    {
        $post_ids = ProductsMeta::where('meta_key', '_dokan_vendor_id')->
        where('meta_value', $vendorID)->get();

        $query = "select 

sum(((IFNULL(O.meta_value, O2.meta_value)  * I1.meta_value) - (IFNULL(T.meta_value, T2.meta_value) * I1.meta_value))) as totalCompletedOrders, 
sum(IFNULL(O.meta_value, O2.meta_value)  * I1.meta_value ) as Sales,
sum(IFNULL(T.meta_value, T2.meta_value) * I1.meta_value) as Profit,
UU.ID as VendorID, 
UU.display_name as VendorName, 
A.post_status as Status

                  from wp_posts A
                  left join wp_users U on U.ID = (select meta_value from wp_postmeta P where post_id = A.ID and meta_key = '_customer_user' )
                  left join wp_users V on V.ID = (select meta_value from wp_postmeta P where post_id = A.ID and meta_key = '_dokan_vendor_id' )
                  left join wp_postmeta P on P.post_id = A.ID and P.meta_key='_order_total'
                  left join wp_woocommerce_order_items H on H.order_id = A.ID and H.order_item_type = 'line_item'
                  left join wp_woocommerce_order_itemmeta I1 on I1.order_item_id = H.order_item_id and I1.meta_key='_qty'
                  left join wp_woocommerce_order_itemmeta I2 on I2.order_item_id = H.order_item_id and I2.meta_key='_line_total'


                  left join wp_woocommerce_order_items Fee on Fee.order_id = A.ID and Fee.order_item_type = 'fee'
                  left join wp_woocommerce_order_itemmeta I3 on I3.order_item_id = Fee.order_item_id and I3.meta_key='_line_total'

                  left join wp_postmeta W on W.post_id = (select meta_value from wp_woocommerce_order_itemmeta where order_item_id = H.order_item_id and meta_key = '_variation_id' ) and W.meta_key = '_regular_price'
                  left join wp_postmeta S on S.post_id = (select meta_value from wp_woocommerce_order_itemmeta where order_item_id = H.order_item_id and meta_key = '_variation_id' ) and S.meta_key = '_sale_price'
                  left join wp_posts PP on PP.ID = (select meta_value from wp_woocommerce_order_itemmeta where order_item_id = H.order_item_id and meta_key = '_variation_id' ) 
                  left join wp_postmeta O on O.post_id = (select meta_value from wp_woocommerce_order_itemmeta where order_item_id = H.order_item_id and meta_key = '_variation_id' ) and O.meta_key = '_offer_price'
                  left join wp_postmeta T on T.post_id = (select meta_value from wp_woocommerce_order_itemmeta where order_item_id = H.order_item_id and meta_key = '_variation_id' ) and T.meta_key = '_offer_profit'
                  left join wp_postmeta M on M.post_id = (select meta_value from wp_woocommerce_order_itemmeta where order_item_id = H.order_item_id and meta_key = '_product_id') and M.meta_key= '_woo_vou_vendor_user'
                  left join wp_users UU on UU.ID = M.meta_value

                  left join wp_postmeta O2 on O2.post_id = (select meta_value from wp_woocommerce_order_itemmeta where order_item_id = H.order_item_id and meta_key = '_product_id' ) and O2.meta_key = '_main_offer_price'
                  left join wp_postmeta T2 on T2.post_id = (select meta_value from wp_woocommerce_order_itemmeta where order_item_id = H.order_item_id and meta_key = '_product_id' ) and T2.meta_key = '_main_offer_profit'

                  where A.post_type = 'shop_order'
                  and UU.display_name is not NULL
                  and A.post_status not in ('wc-cancelled', 'trash')
                  GROUP by UU.ID, A.post_status
                  order by UU.ID";

        $result = User::select(User::raw($query));
        return $result;
    }

    public function getCompletedAmount()
    {
        return 0;
    }

    public function getProcessingAmount()
    {
        return 0;
    }

    public function getFullName($id)
    {
        $first_name = UsersMeta::where('user_id', $id)
            ->where('meta_key', 'first_name')->first()->meta_value;

        $last_name = UsersMeta::where('user_id', $id)
            ->where('meta_key', 'last_name')->first()->meta_value;

        return $first_name . ' ' . $last_name;
    }

    public function getUserLevel($id)
    {
        return UsersMeta::where('user_id', $id)
            ->where('meta_key', 'wp_user_level')->first()->meta_value;
    }
}
