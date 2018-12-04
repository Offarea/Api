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
        if ($vendor_id) {
            $vendor = Users::where('ID', $vendor_id->meta_value)->first();

            $first_name = UsersMeta::where('user_id', $vendor->ID)
                ->where('meta_key', 'billing_first_name')->first();
            $last_name = UsersMeta::where('user_id', $vendor->ID)
                ->where('meta_key', 'billing_last_name')->first();

            if ($first_name and $last_name) {
                return $first_name->meta_value . ' ' . $last_name->meta_value;
            } else {
                $order_item_id = WooCommerceOrder::where('order_id', $id)
                    ->where('order_item_type', 'line_item')->first()->order_item_id;
                if ($order_item_id) {
                    $order_item_meta_product_id =
                        WooCommerceOrderMeta::where('order_item_id', $order_item_id)
                            ->where('meta_key', '_product_id')->first();
                    if ($order_item_meta_product_id) {
                        $post_author = Products::where('ID', $order_item_meta_product_id->meta_value)->first();
                        if ($post_author) {
                            return $this->getUserName($post_author->post_author);
                        }

                    }
                }

            }
        } else {
            $order_item_id = WooCommerceOrder::where('order_id', $id)
                ->where('order_item_type', 'line_item')->first()->order_item_id;
            if ($order_item_id) {
                $order_item_meta_product_id =
                    WooCommerceOrderMeta::where('order_item_id', $order_item_id)
                        ->where('meta_key', '_product_id')->first();
                if ($order_item_meta_product_id) {
                    $post_author = Products::where('ID', $order_item_meta_product_id->meta_value)->first();
                    if ($post_author) {
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

        if ($first_name and $last_name) {
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
            return 'در حال انجام';
        if ($status == 'wc-cleared')
            return 'تسویه شده';
        if ($status == 'wc-refunded')
            return 'مسترد شده';
        if ($status == 'wc-aborted')
            return 'باطل شده';
        if ($status == 'wc-canceled')
            return 'لغو شده';

    }

    public function get_order_details(Request $request)
    {
        $id = $request->order_id;
        $order = Products::where('ID', $id)->where('post_type', 'shop_order')->first();
        if($order)
        {
            $object = (object)[
                'order_no' => $id,
                'date' => $order->post_date,
                'status' => $this->get_status_eqaule($order->post_status),
                'amount' => $this->get_order_amount($id),
                'vendor' => $this->get_order_vendor_name($id),
                'payment_method' => $this->get_payment_method($id),
                'transaction_id' => $this->get_transaction_id($id),
                'paid_date' => $this->get_paid_date($id),
                'items' => $this->get_order_items($id),
            ];

            return json_encode($object);
        }
        else
        {
            return \response([
                'result' => 'false',
                'message' => 'شماره سفارش درخواستی نامعتبر می باشد'
            ]);
        }

    }

    public function get_order_items($id)
    {
        $order_item_ids = WooCommerceOrder::where('order_id', $id)->get();
        if($order_item_ids)
        {
            $i=0;
            $items = array();
            foreach ($order_item_ids as $order_item_id)
            {
                $type = $this->get_order_item_type($order_item_id);
                $items[$i]['type'] = $type;

                if($order_item_id->order_item_type == 'line_item')
                {
                    $item_meta_variation = WooCommerceOrderMeta::
                    where('order_item_id', $order_item_id->order_item_id)
                        ->where('meta_key', '_variation_id')
                        ->first()->meta_value;

                    $total_order = WooCommerceOrderMeta::
                    where('order_item_id', $order_item_id->order_item_id)
                        ->where('meta_key', '_line_subtotal')
                        ->first()->meta_value;

                    $total_paid = WooCommerceOrderMeta::
                    where('order_item_id', $order_item_id->order_item_id)
                        ->where('meta_key', '_line_total')
                        ->first()->meta_value;

                    $items[$i]['total_paid'] = $total_paid;
                    $items[$i]['total_order'] = $total_order;
                    $items[$i]['discount'] = $total_order - $total_paid;

                    $name = '';
                    // it is a simple product
                    if($item_meta_variation == 0 || $item_meta_variation == '0')
                    {
                        $product_id = WooCommerceOrderMeta::
                        where('order_item_id', $order_item_id->order_item_id)
                            ->where('meta_key', '_product_id')
                            ->first()->meta_value;

                        $name_meta = ProductsMeta::where('post_id', $product_id)
                            ->where('meta_key', '_sku')->first();

                        if($name_meta)
                        {
                            $name = $name_meta->meta_value;
                            $items[$i]['name'] = $name;
                        }
                        else
                        {
                            $name = 'فاقد نام';
                            $items[$i]['name'] = $name;
                        }


                    }
                    // it is a product variation
                    else if($item_meta_variation and $item_meta_variation != 0)
                    {
                        $product_id = $item_meta_variation;

                        $name_meta = ProductsMeta::where('post_id', $product_id)
                            ->where('meta_key', '_sku')->first();
                        if($name_meta)
                        {
                            $name = $name_meta->meta_value;
                        }
                        else
                        {
                            $name = 'فاقد نام';
                        }


                    }

                    $qty = WooCommerceOrderMeta::
                    where('order_item_id', $order_item_id->order_item_id)
                        ->where('meta_key', '_qty')
                        ->first()->meta_value;

                    $items[$i]['qty'] = $qty;

                }
                else if($order_item_id->order_item_type == 'fee')
                {
                    $total = WooCommerceOrderMeta::
                    where('order_item_id', $order_item_id->order_item_id)
                        ->where('meta_key', '_line_total')
                        ->first()->meta_value;

                    $items[$i]['total_paid'] = $total;
                    $items[$i]['total_order'] = $total;
                    $items[$i]['discount'] = '';
                    $items[$i]['name'] = 'مقدار پرداختی از کیف پول';
                    $items[$i]['qty'] = '';

                }
                else if($order_item_id->order_item_type == 'coupon')
                {
                    $discount = WooCommerceOrderMeta::
                    where('order_item_id', $order_item_id->order_item_id)
                        ->where('meta_key', 'discount_amount')
                        ->first()->meta_value;

                    $items[$i]['total_paid'] = '';
                    $items[$i]['total_order'] = '';
                    $items[$i]['discount'] = $discount;
                    $items[$i]['name'] = $order_item_id->order_item_name;
                    $items[$i]['qty'] = '';
                }

                $i++;
            }
        }
        return $items;
    }

    public function get_order_item_type($order_item_id)
    {
        if($order_item_id->order_item_type == 'line_item')
        {
            return 'پرداخت';
        }
        if($order_item_id->order_item_type == 'fee')
        {
            return 'کیف پول';
        }
        if($order_item_id->order_item_type == 'coupon')
        {
            return 'تخفیف';
        }
    }

    public function get_payment_method($id)
    {
        return ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_payment_method_title')->first()->meta_value;
    }

    public function get_paid_date($id)
    {
        return ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_paid_date')->first()->meta_value;
    }

    public function get_transaction_id($id)
    {
        return ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_transaction_id')->first()->meta_value;
    }
}
