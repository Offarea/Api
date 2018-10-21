<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $table = 'wp_posts';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    public function Meta()
    {
        return $this->hasMany('App\Models\v1\ProductsMeta', 'post_id');
    }

    protected $fillable =
        [
            'post_date',
            'post_title',
            'post_content',
            'post_excerpt'
        ];
}
