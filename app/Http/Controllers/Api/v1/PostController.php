<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\Api\v1\ProductSummary;
use App\Models\v1\Products;
use App\Models\v1\ProductsMeta;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

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

            $offerProductPrice = $this->getOfferProductPrice($post);
            $regularProductPrice = $this->getRegularProductPrice($post);
            $offer_percent = $this->getOfferPercent($offerProductPrice, $regularProductPrice);
            $total_sales = $this->getTotalSales($post);
            $deadline = $this->getDeadline($post);
            $categories = $this->get_categoriesByProductID($product->ID);

            $result[$counter]['id'] = $product->ID;
            $result[$counter]['title'] = $product->post_title;
            $result[$counter]['description'] = $product->post_excerpt;
            $result[$counter]['regular_price'] = $regularProductPrice;
            $result[$counter]['offer_price'] = $offerProductPrice;
            $result[$counter]['offer_percent'] = $offer_percent;
            $result[$counter]['total_sales'] = $total_sales;
            $result[$counter]['deadline'] = $deadline;
            $result[$counter]['barcode_expire_date'] = $this->getBarcodeExpireDate($product->ID);
            $result[$counter]['status'] = 'Active';
            $result[$counter]['category'] = $categories;
            $result[$counter]['city'] = '';
            $result[$counter]['address'] = $this->getAddress($product->ID);
            $result[$counter]['location'] = array('langitude' => '', 'latitude' => '');
            $result[$counter]['image_url'] = $this->findProductImageUrlByID($product->ID);

            $counter++;
        }

        return json_encode(
            $result
        );

    }

    public function getAddress($product_id)
    {
        return ProductsMeta::where('post_id', $product_id)
            ->where('meta_key', 'melocate')->first()->meta_value;
    }

    public function getBarcodeExpireDate($product_id)
    {
        return ProductsMeta::where('post_id', $product_id)
            ->where('meta_key', '_woo_vou_exp_date')->first()->meta_value;
    }

    public function get_categoriesByProductID($product_id)
    {
        $cats = DB::select("SELECT A.term_id, A.name
                     FROM wp_terms A
                     LEFT JOIN wp_term_taxonomy B ON A.term_id = B.term_id
		             left join wp_term_relationships C on C.term_taxonomy_id = B.term_taxonomy_id
                     WHERE B.taxonomy = 'product_cat'
		             and C.object_id = " . $product_id);

        $product_cats = array();
        $counter = 0;
        foreach ($cats as $cat)
        {
            $product_cats[$counter]['category_id'] = $cat->term_id;
            $product_cats[$counter]['category_title'] = $cat->name;
            $counter++;
        }
        return $product_cats;
    }

    public function get_categories(Request $request)
    {
        $result = DB::select("SELECT wp_terms.term_id, wp_terms.name 
                  FROM wp_terms 
                  LEFT JOIN wp_term_taxonomy ON wp_terms.term_id = wp_term_taxonomy.term_id
                  WHERE wp_term_taxonomy.taxonomy = 'product_cat';");

        return json_encode(
            $result
        );
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
        {
            $variation = Products::where('post_parent', $post->ID)
                ->where('post_type', 'product_variation')->first();

            $var_meta = ProductsMeta::where('post_id', $variation->ID)
                ->where('meta_key', '_regular_price')->first();
            return $var_meta->meta_value;
        }
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
