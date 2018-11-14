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
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $post = Products::where('ID', $this->ID)
            ->where('post_type', 'product')->first();
        $offerProductPrice = $this->getOfferProductPrice($post);
        $regularProductPrice = $this->getRegularProductPrice($post);
        $offer_percent = $this->getOfferPercent($offerProductPrice, $regularProductPrice);
        $total_sales = $this->getTotalSales($post);
        $deadline = $this->getDeadline($post);

        return [
            'id' => $this->ID,
            'title' => $this->post_title,
            'description' => $this->post_excerpt,
            'regular_price' => $regularProductPrice,
            'offer_price' => $offerProductPrice,
            'offer_percent' => $offer_percent,
            'total_sales' => $total_sales,
            'deadline' => $deadline,
            'image_url' => $this->findProductImageUrlByID($this->ID)
        ];
    }

    public function getOfferPercent($offerProductPrice, $regularProductPrice)
    {
        if ($offerProductPrice and $regularProductPrice)
            return ($offerProductPrice * 100) / $regularProductPrice;
        else
            return 0;
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

    public function getRegularProductPrice($post)
    {
        $meta = ProductsMeta::where('post_id', $post->ID)
            ->where('meta_key', '_regular_price')->first();
        if ($meta)
            return $meta->meta_value;
        else
            return 0;
    }

    public function getOfferProductPrice($post)
    {
        $meta = ProductsMeta::where('post_id', $post->ID)
            ->where('meta_key', '_main_offer_price')->first();
        if ($meta)
            return $meta->meta_value;
        else
            return 0;
    }

    public function getTotalSales($post)
    {
        $meta = ProductsMeta::where('post_id', $post->ID)
            ->where('meta_key', 'total_sales')->first();
        if ($meta)
            return $meta->meta_value;
        else
            return 0;
    }

    public function getDeadline($post)
    {
        $from = ProductsMeta::where('post_id', $post->ID)
            ->where('meta_key', '_sale_price_dates_from')->first()->meta_value;
        $to = ProductsMeta::where('post_id', $post->ID)
            ->where('meta_key', '_sale_price_dates_to')->first()->meta_value;

        if (is_numeric($to) and is_numeric($from)) {
            $diff = $to - $from;
            if ($diff) {
                $deadline = \DateTime::createFromFormat('YmdGis', $diff);
                if ($deadline)
                    return $deadline;//->format('Y-m-d G:i:s');
                else
                    return 0;
            }
        } else
            return 0;
    }
}
