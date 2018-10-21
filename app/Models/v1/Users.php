<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'wp_users';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    public function Meta()
    {
        return $this->hasMany('App\Models\v1\UsersMeta', 'user_id');
    }

    protected $fillable =
        [
            'user_login',
            'user_pass',
            'user_nicename',
            'user_email',
            'user_registered',
            'user_status',
            'display_name'
        ];
}
