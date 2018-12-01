<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class WooCommerceOrder extends Model
{
    protected $table = 'wp_woocommerce_order_items';
    protected $primaryKey = 'order_item_id';
    public $timestamps = false;
}
