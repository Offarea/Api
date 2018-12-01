<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class WooCommerceOrderMeta extends Model
{
    protected $table = 'wp_woocommerce_order_itemmeta';
    protected $primaryKey = 'meta_id';
    public $timestamps = false;
}
