<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class Comments extends Model
{
    protected $table = 'wp_comments';
    protected $primaryKey = 'comment_ID';
    public $timestamps = false;
}
