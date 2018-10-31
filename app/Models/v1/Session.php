<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'wp_woocommerce_sessions';
    protected $primaryKey = 'session_id';
    public $timestamps = false;

    protected $fillable =
        [
            'session_key',
            'session_value',
            'session_expiry'
        ];
}
