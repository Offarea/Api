<?php

namespace App\Http\Resources\Api\v1;

use App\Models\v1\Products;
use App\Models\v1\ProductsMeta;
use Illuminate\Http\Resources\Json\Resource;

class ProductSummary extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->ID,
            'title' => $this->post_title,
            'description'=>$this->post_excerpt,
            'image_url'=> $this->findProductImageUrlByID($this->ID)
        ];
    }

    public function findProductImageUrlByID($product_id)
    {
        $link_id = ProductsMeta::where('post_id', $product_id)
            ->where('meta_key', '_thumbnail_id')->first();
        $post_image_type = Products::where('ID', $link_id->meta_value)
            ->where('post_type', 'attachment')
            ->first();
        return $post_image_type->guid;
    }
}
