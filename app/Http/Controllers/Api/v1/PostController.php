<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\Api\v1\ProductSummary;
use App\Models\v1\Comments;
use App\Models\v1\Products;
use App\Models\v1\ProductsMeta;
use App\Models\v1\Users;
use App\Models\v1\UsersMeta;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

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
            $cities = $this->get_citiesByProductID($product->ID);
            $location = $this->get_locationByProductID($product->ID);
            $purchaseDeadlineDate = $this->getPurchaseDeadlineDate($product->ID);
            if ($purchaseDeadlineDate == 'Expired')
                $status = 'Deactive';
            else
                $status = 'Active';
            $result[$counter]['id'] = $product->ID;
            $result[$counter]['title'] = $product->post_title;
            $result[$counter]['description'] = $this->get_description($product->ID);
            $result[$counter]['regular_price'] = (float) $regularProductPrice;
            $result[$counter]['offer_price'] = (float) $offerProductPrice;
            $result[$counter]['offer_percent'] = $offer_percent;
            $result[$counter]['total_sales'] = (int)$total_sales;
            $result[$counter]['deadline_in_seconds'] = $deadline;
            $result[$counter]['purchase_expire_date'] = $purchaseDeadlineDate;
            $result[$counter]['barcode_expire_date'] = $this->getBarcodeExpireDate($product->ID);
            $result[$counter]['offer_status'] = $status;
            $result[$counter]['category'] = $categories;
            $result[$counter]['city'] = $cities;

            $result[$counter]['short_address'] = $this->getShortAddressByID($product->ID);
            $result[$counter]['work_hours'] = $this->getWorkHoursByID($product->ID);
            $result[$counter]['work_phone'] = $this->getWorkPhoneByID($product->ID);

            $result[$counter]['image_url'] = $this->findProductImageUrlByID($product->ID);


            $counter++;
        }

        // Get current page form url e.x. &page=1
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        // Create a new Laravel collection from the array data
        $itemCollection = collect($result);

        // Define how many items we want to be visible in each page
        $perPage = 10;

        // Slice the collection to get the items to display in current page
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();

        // Create our paginator and pass it to the view
        $paginatedItems = new LengthAwarePaginator($currentPageItems, count($itemCollection), $perPage);

        // set url path for generted links
        $paginatedItems->setPath($request->url());

        $allOffers = array(
            'allOffers' => $paginatedItems
        );

        $data = array
        (
            'status' => 1,
            'message' => 'تمامی محصولات با موفقیت واکشی شد',
            'result' => $allOffers


        );

        return json_encode(
            $data
        );

    }

    public function get_locationByProductID($product_id)
    {
        $longitude = ProductsMeta::where('post_id', $product_id)
            ->where('meta_key', '_longitude')->first();

        $latitude = ProductsMeta::where('post_id', $product_id)
            ->where('meta_key', '_latitude')->first();

        if ($latitude and $longitude) {
            $location = array('longitude' => $longitude, 'latitude' => $latitude);
            return $location;
        } else
            return
                [
                    'longitude' => 'N/A', 'latitude' => 'N/A'
                ];
    }

    public function getAddress($product_id)
    {
        return ProductsMeta::where('post_id', $product_id)
            ->where('meta_key', 'melocate')->first()->meta_value;
    }

    public function get_description($id)
    {
        $meta = ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_api_descr')->first();
        if ($meta)
        {
            return $meta->meta_value;
        }
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
        foreach ($cats as $cat) {
            $product_cats[$counter]['category_id'] = $cat->term_id;
            $product_cats[$counter]['category_title'] = $cat->name;
            $counter++;
        }
        return $product_cats;
    }

    public function get_citiesByProductID($product_id)
    {
        $cities = DB::select("SELECT A.term_id, A.name
                     FROM wp_terms A
                     LEFT JOIN wp_term_taxonomy B ON A.term_id = B.term_id
		             left join wp_term_relationships C on C.term_taxonomy_id = B.term_taxonomy_id
                     WHERE B.taxonomy = 'location'
		             and C.object_id = " . $product_id);

        $product_cities = array();
        $counter = 0;
        foreach ($cities as $city) {
            $product_cities[$counter]['city_id'] = $city->term_id;
            $product_cities[$counter]['city_title'] = $city->name;
            $counter++;
        }
        return $product_cities;
    }

    public function get_cities(Request $request)
    {
        $result = DB::select("SELECT wp_terms.term_id as city_id, wp_terms.name as city_title
                  FROM wp_terms 
                  LEFT JOIN wp_term_taxonomy ON wp_terms.term_id = wp_term_taxonomy.term_id
                  WHERE wp_term_taxonomy.taxonomy = 'location';");

        return json_encode(
            $result
        );
    }

    public function get_categories(Request $request)
    {
        $result = DB::select("SELECT wp_terms.term_id as category_id, wp_terms.name as category_title
                  FROM wp_terms 
                  LEFT JOIN wp_term_taxonomy ON wp_terms.term_id = wp_term_taxonomy.term_id
                  WHERE wp_term_taxonomy.taxonomy = 'product_cat';");

        return json_encode(
            $result
        );
    }

    public function get_single(Request $request)
    {
        $products = ProductSummary::collection(Products::all()
            ->where('post_type', 'product')
            ->where('post_status', 'publish'))
            ->where('ID', $request->product_id);

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
            $cities = $this->get_citiesByProductID($product->ID);
            $attributes = $this->getAttributesByProductID($product->ID);
            $comments = $this->getCommentByProductID($product->ID);
            $vendor = $this->get_vendor($product->ID);
            $purchaseDeadlineDate = $this->getPurchaseDeadlineDate($product->ID);
            if ($purchaseDeadlineDate == 'Expired')
                $status = 'Deactive';
            else
                $status = 'Active';

            $image_galley = $this->getImageGalleryByProductID($product->ID);
            $variations = $this->getVariationsByProductID($product->ID);

            $result[$counter]['id'] = $product->ID;
            $result[$counter]['title'] = $product->post_title;
            $result[$counter]['description'] = $this->get_description($product->ID);
            $result[$counter]['content'] = $this->get_PostContent($product->ID);
            $result[$counter]['offer_type'] = 'variable';
            $result[$counter]['regular_price'] = (float) $regularProductPrice;
            $result[$counter]['offer_price'] = (float) $offerProductPrice;
            $result[$counter]['offer_percent'] = $offer_percent;
            $result[$counter]['total_sales'] = (int)$total_sales;
            $result[$counter]['deadline'] = $deadline;
            $result[$counter]['purchase_expire_date'] = $purchaseDeadlineDate;
            $result[$counter]['barcode_expire_date'] = $this->getBarcodeExpireDate($product->ID);
            $result[$counter]['offer_status'] = $status;
            $result[$counter]['on_sales_count'] = 0;
            $result[$counter]['remain_count'] = 0;

            $result[$counter]['vendor'] = $vendor;


            $result[$counter]['category'] = $categories;
            $result[$counter]['attributes'] = $attributes;
            $result[$counter]['comments'] = $comments;
            $result[$counter]['city'] = $cities;
            $result[$counter]['address'] = $this->getAddress($product->ID);

            $result[$counter]['short_address'] = $this->getShortAddressByID($product->ID);
            $result[$counter]['work_hours'] = $this->getWorkHoursByID($product->ID);
            $result[$counter]['work_phone'] = $this->getWorkPhoneByID($product->ID);

            $result[$counter]['location'] = array('langitude' => $this->getLongitude($product->ID), 'latitude' => $this->getLatitude($product->ID));
            $result[$counter]['image_url'] = $this->findProductImageUrlByID($product->ID);
            $result[$counter]['image_gallery_urls'] = $image_galley;
            $result[$counter]['variations'] = $variations;

            $counter++;
        }

        $singleOffer = array(
            'singleOffer' => $result[0]
        );
        $data = array
        (
            'status' => 1,
            'message' => 'محصول مورد نظر با موفقیت واکشی شد',
            'result' => $singleOffer
        );

        return json_encode(
            $data
        );
    }

    public function getImageGalleryByProductID($product_id)
    {
        $id_string = ProductsMeta::where('post_id', $product_id)
            ->where('meta_key', '_product_image_gallery')->first()->meta_value;

        $ids = explode(",", $id_string);

        $urls = array();
        $i = 0;

        foreach ($ids as $id) {
            $address = ProductsMeta::where('post_id', $id)
                ->where('meta_key', '_wp_attached_file')->first()->meta_value;
            $urls[$i]['url'] = 'https://offarea.ir/wp-content/uploads/' . $address;
            $i++;
        }
        return $urls;
    }

    public function getVariationsByProductID($product_id)
    {
        $variations = DB::select("select * from wp_posts
                                  where  post_type = 'product_variation' 
                                  and post_parent =  " . $product_id);

        $product_variations = array();
        $i = 0;
        foreach ($variations as $variation) {
            $price = $this->getVariationPriceByID($variation->ID);
            $offer_price = $this->getVariationOfferPriceByID($variation->ID);

            $product_variations[$i]['id'] = $variation->ID;
            $product_variations[$i]['name'] = $this->getVariationNameByID($variation->ID);
            $product_variations[$i]['offer_price'] = $offer_price;
            $product_variations[$i]['offer_percent'] = $this->getOfferPercent($offer_price, $price);
            $product_variations[$i]['on_sales_count'] = 0;
            $product_variations[$i]['remain_count'] = 0;
            $i++;
        }

        return
            $product_variations;

    }

    public function getVariationNameByID($id)
    {
        return ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_sku')->first()->meta_value;
    }

    public function get_StockStatus($id)
    {
        $staus = ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_stock_status')->first();
        if($staus)
        {
            if($staus->meta_value == 'instock')
                return 'پیشنهاد موجود می باشد';
            else if($staus->meta_value == 'outofstock')
                return 'پیشنهاد به اتمام رسیده است';
        }
    }
    public function get_PostContent($id)
    {
        $meta = ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_api_content')->first();
        if($meta)
            return $meta->meta_value;
        else
            return 'محتوی ندارد';
    }
    public function getLongitude($id)
    {
        return ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_longitude')->first()->meta_value;
    }

    public function getLatitude($id)
    {
        return ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_latitude')->first()->meta_value;
    }

    public function getVariationPriceByID($id)
    {
        return ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_price')->first()->meta_value;
    }

    public function getShortAddressByID($id)
    {
        $meta = ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_short_address')->first();
        if ($meta)
            return $meta->meta_value;
        else
            return '';
    }

    public function getWorkHoursByID($id)
    {
        $meta = ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_work_hours')->first();
        if ($meta)
            return $meta->meta_value;
        else
            return '';
    }

    public function getWorkPhoneByID($id)
    {
        $meta = ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_work_phone')->first();
        if ($meta)
            return $meta->meta_value;
        else
            return '';
    }

    public function getVariationOfferPriceByID($id)
    {
        return ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_offer_price')->first()->meta_value;
    }

    public function getCommentByProductID($product_id)
    {
        $comments = Comments::all()->where('user_id', '<>', '0')
            ->where('comment_post_ID', $product_id);
        $result = array();
        $counter = 0;

        foreach ($comments as $comment) {
            $result[$counter]['id'] = $comment->comment_ID;
            $userFullName = $this->getUserFullName($comment->user_id);
            if (!$userFullName)
                $userFullName = $comment->comment_author;
            $result[$counter]['user'] = $userFullName;
            $result[$counter]['content'] = $comment->comment_content;
            $result[$counter]['comment_date'] = $comment->comment_date;
            $counter++;
        }

        return $result;
    }

    public function get_vendor($id)
    {
        $post_id = ProductsMeta::where('post_id', $id)
            ->where('meta_key', '_woo_vou_vendor_user')->first();
        if($post_id)
        {
            $user_id = $post_id->meta_value;
            $user = Users::where('ID', $user_id)->first();
            if($user)
            {
                $name = $this->getUserFullName($user_id);
                return array('id'=> $user_id, 'vendor_name'=> $name);
            }
        }
    }
    public function getUserFullName($user_id)
    {
        $first_name = UsersMeta::where('user_id', $user_id)
            ->where('meta_key', 'first_name')->first();

        $last_name = UsersMeta::where('user_id', $user_id)
            ->where('meta_key', 'last_name')->first();

        if ($first_name and $last_name) {
            return $first_name->meta_value . ' ' . $last_name->meta_value;
        }

    }

    public function getAttributesByProductID($product_id)
    {
        $post_attr = ProductsMeta::where('post_id', $product_id)
            ->where('meta_key', '_product_attributes')->first();

        $attrs = unserialize($post_attr->meta_value);
        $arr = array();
        $i = 0;

        foreach ($attrs as $attr) {
            if ($attr['is_variation'] != 1) {
                $arr[$i]['attr_name'] = $attr['name'];
                $arr[$i]['attr_value'] = $attr['value'];
                $i++;
            }
        }

        return $arr;
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
        $var = Products::where('post_parent', $post->ID)
            ->where('post_type', 'product_variation')->first();
        if ($var) {

            $meta = ProductsMeta::where('post_id', $var->ID)
                ->where('meta_key', '_regular_price')->first();
            if ($meta) {
                return $meta->meta_value;
            } else {
                $meta_price = ProductsMeta::where('post_id', $var->ID)
                    ->where('meta_key', '_price')->first();
                if ($meta_price)
                    return $meta_price->meta_value;
                else
                    return 0;
            }

        } else {
            $meta = ProductsMeta::where('post_id', $post->ID)
                ->where('meta_key', '_regular_price')->first();
            return $meta->meta_value;
        }
    }

    public function getOfferProductPrice($post)
    {
        $var = Products::where('post_parent', $post->ID)
            ->where('post_type', 'product_variation')->first();
        if ($var) {

            $meta = ProductsMeta::where('post_id', $var->ID)
                ->where('meta_key', '_offer_price')->first();
            if ($meta) {
                return $meta->meta_value;
            } else {
                return 0;
            }

        } else {
            $meta = ProductsMeta::where('post_id', $post->ID)
                ->where('meta_key', '_main_offer_price')->first();
            if ($meta) {
                return $meta->meta_value;
            } else {
                return 0;
            }

        }
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

        if ($from == '' || empty($from) || $from == null) {
            return 0;
        } else {
            $long = strtotime(Carbon::now());
            return $to - $long;
        }
    }

    public function getPurchaseDeadlineDate($product_id)
    {
        $to = ProductsMeta::where('post_id', $product_id)
            ->where('meta_key', '_sale_price_dates_to')->first()->meta_value;
        if ($to) {
            return date('Y-m-d H:i:s', (int)$to);
        } else {
            return 'Expired';
        }

    }

}
