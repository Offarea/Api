<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\Api\v1\ProductSummary;
use App\Models\v1\Products;
use App\Models\v1\ProductsMeta;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PostController extends Controller
{
    public function all_product_summary(Request $request)
    {
        $products = ProductSummary::collection(Products::all()
            ->where('post_type', 'product')
            ->where('post_status', 'publish'));

        $result = array();
        $counter = 0;

        foreach ($products as $product) {
            $post = Products::where('ID', $product->ID)
                ->where('post_type', 'product')->first();

            $offerProductPrice =
            $regularProductPrice = $this->getRegularProductPrice($post);
            $offer_percent = $this->getOfferPercent($offerProductPrice, $regularProductPrice);
            $total_sales = $this->getTotalSales($post);
            $deadline = $this->getDeadline($post);

            $result[$counter]['id'] = $product->ID;
            $result[$counter]['title'] = $product->post_title;
            $result[$counter]['description'] = $product->post_excerpt;
            $result[$counter]['regular_price'] = $regularProductPrice;
            $result[$counter]['offer_price'] = $this->getOfferProductPrice($post);
            $result[$counter]['offer_percent'] = $offer_percent;
            $result[$counter]['total_sales'] = $total_sales;
            $result[$counter]['deadline'] = $deadline;
            $result[$counter]['status'] = 'Active';
            $result[$counter]['category'] = array('category_id' => '', 'category_title' => '');
            $result[$counter]['city'] = '';
            $result[$counter]['location'] = array('langitude' => '', 'latitude' => '');
            $result[$counter]['image_url'] = $this->findProductImageUrlByID($product->ID);

            $counter++;
        }

        return json_encode(
            $result
        );

    }

    public function get_categories(Request $request)
    {

    }

    public function single(Request $request)
    {

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
