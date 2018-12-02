<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\v1\Products;
use App\Models\v1\ProductsMeta;
use App\Models\v1\Users;
use App\Models\v1\UsersMeta;
use App\Models\v1\WooCommerceOrder;
use App\Models\v1\WooCommerceOrderMeta;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function get_customer_orders(Request $request)
    {
        $raw_orders = DB::select("select * from wp_posts where post_type = 'shop_order' 
                              and post_status in ('wc-completed', 'wc-processing', 'wc-cleared')
                              and post_author =   " . $request->user_id);

        $orders = array();
        $i = 0;

        foreach ($raw_orders as $raw_order) {
            $orders[$i]['order_no'] = $raw_order->ID;
            $orders[$i]['order_date'] = $raw_order->post_date;
            $orders[$i]['status'] = $this->get_status_eqaule($raw_order->post_status);
            $orders[$i]['amount'] = $this->get_order_amount($raw_order->ID);
            $orders[$i]['vendor'] = $this->get_order_vendor_name($raw_order->ID);

            $i++;
        }

        return json_encode(
            $orders
        );
    }

    public function get_order_amount($id)
    {
        return ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_order_total')->first()->meta_value;
    }

    public function get_order_vendor_name($id)
    {
        $vendor_id = ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_dokan_vendor_id')->first();
        if($vendor_id)
        {
            $vendor = Users::where('ID', $vendor_id->meta_value)->first();

            $first_name = UsersMeta::where('user_id', $vendor->ID)
                ->where('meta_key', 'billing_first_name')->first();
            $last_name = UsersMeta::where('user_id', $vendor->ID)
                ->where('meta_key', 'billing_last_name')->first();

            if($first_name and $last_name)
            {
                return $first_name->meta_value . ' ' . $last_name->meta_value;
            }
            else
            {
                $order_item_id = WooCommerceOrder::where('order_id', $id)
                    ->where('order_item_type', 'line_item')->first()->order_item_id;
                if($order_item_id)
                {
                    $order_item_meta_product_id =
                        WooCommerceOrderMeta::where('order_item_id', $order_item_id)
                            ->where('meta_key', '_product_id')->first();
                    if($order_item_meta_product_id)
                    {
                        $post_author = Products::where('ID', $order_item_meta_product_id->meta_value)->first();
                        if($post_author)
                        {
                            return $this->getUserName($post_author->post_author);
                        }

                    }
                }

            }
        }
        else
        {
            $order_item_id = WooCommerceOrder::where('order_id', $id)
            ->where('order_item_type', 'line_item')->first()->order_item_id;
                if($order_item_id)
                {
                    $order_item_meta_product_id =
                        WooCommerceOrderMeta::where('order_item_id', $order_item_id)
                            ->where('meta_key', '_product_id')->first();
                    if($order_item_meta_product_id)
                    {
                        $post_author = Products::where('ID', $order_item_meta_product_id->meta_value)->first();
                        if($post_author)
                        {
                            return $this->getUserName($post_author->post_author);
                        }

                    }
                }
        }


    }

    public function getUserName($id)
    {
        $first_name = UsersMeta::where('user_id', $id)
            ->where('meta_key', 'billing_first_name')->first();
        $last_name = UsersMeta::where('user_id', $id)
            ->where('meta_key', 'billing_last_name')->first();

        if($first_name and $last_name)
        {
            return $first_name->meta_value . ' ' . $last_name->meta_value;
        }
    }

    public function get_order_status()
    {
        return json_encode
        ([
            array(
                'number' => 1,
                'title' => 'wc-completed',
                'description' => 'تکمیل شده'),
            array(
                'number' => 2,
                'title' => 'wc-processing',
                'description' => 'در حال انچام'),
            array(
                'number' => 3,
                'title' => 'wc-cleared',
                'description' => 'تسویه شده'),
            array(
                'number' => 4,
                'title' => 'wc-refunded',
                'description' => 'مسترد شده'),
            array(
                'number' => 5,
                'title' => 'wc-aborted',
                'description' => 'باطل شده'),
            array(
                'number' => 6,
                'title' => 'wc-canceled',
                'description' => 'لغو شده'),
        ]);
    }

    public function get_status_eqaule($status)
    {

        if ($status == 'wc-completed')
            return 'تکمیل شده';
        if ($status == 'wc-processing')
            return 'در حال انچام';
        if ($status == 'wc-cleared')
            return 'تسویه شده';
        if ($status == 'wc-refunded')
            return 'مسترد شده';
        if ($status == 'wc-aborted')
            return 'باطل شده';
        if ($status == 'wc-canceled')
            return 'لغو شده';

    }
}
