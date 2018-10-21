<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class UsersMeta extends Model
{
    protected $table = 'wp_usermeta';
    protected $primaryKey = 'umeta_id';
    public $timestamps = false;

    protected $fillable =
        [
            'user_id',
            'meta_key',
            'meta_value'
        ];
}
