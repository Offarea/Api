<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\v1\Products;
use App\Models\v1\ProductsMeta;
use App\Models\v1\UsersMeta;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use phpDocumentor\Reflection\Types\Array_;

class CartController extends Controller
{
    public $url = 'https://offarea.ir/offareaApi.php';

    public function get_cart_total(Request $request)
    {
        //Validation
        $validate = $this->validate($request, [
            'token' => 'required',
        ]);

        if ($validate) {
            $userID = $this->getUserIDByToken($request->token);
            if ($userID) {
                $cartContent = $this->getCartContentByUserID($userID);
                $cartArray = unserialize($cartContent->meta_value);

                $sum = 0;
                foreach ($cartArray as $num => $item) {
                    foreach ($item as $num2 => $internal_item) {
                        $sum = $sum + $internal_item['line_total'];
                    }
                }
                return \response([
                    'result' => $sum
                ]);
            } else {
                return \response([
                    'result' => 'false',
                    'message' => 'شما برای عملیات درخواست شده، دسترسی لازم را ندارید'
                ]);
            }
        } else {
            return \response([
                'result' => 'false',
                'message' => 'ارسال نشانه منحصر به فرد کاربر اجباری می باشد'
            ]);
        }

    }

    public function get_cart_count(Request $request)
    {
        //Validation
        $validate = $this->validate($request, [
            'token' => 'required',
        ]);

        if ($validate) {
            $userID = $this->getUserIDByToken($request->token);
            if ($userID) {
                $cartContent = $this->getCartContentByUserID($userID);
                $cartArray = unserialize($cartContent->meta_value);

                $sum = 0;
                foreach ($cartArray as $num => $item) {
                    foreach ($item as $num2 => $internal_item) {
                        $sum = $sum + $internal_item['quantity'];
                    }
                }
                return \response([
                    'result' => $sum
                ]);
            } else {
                return \response([
                    'result' => 'false',
                    'message' => 'شما برای عملیات درخواست شده، دسترسی لازم را ندارید'
                ]);
            }
        } else {
            return \response([
                'result' => 'false',
                'message' => 'ارسال نشانه منحصر به فرد کاربر اجباری می باشد'
            ]);
        }

    }

    public function get_cart_content(Request $request)
    {
        //Validation
        $validate = $this->validate($request, [
            'token' => 'required',
        ]);

        if ($validate) {
            $userID = $this->getUserIDByToken($request->token);
            if ($userID) {
                $cart = $this->getCartContentByUserID($userID);
                $cartArray = unserialize($cart->meta_value);

                $cartContent = null;
                $i = 0;
                foreach ($cartArray as $num => $item) {
                    foreach ($item as $num2 => $internal_item) {
                        $title = 'بدون عنوان';
                        if ($internal_item['variation_id'] != 0 and $internal_item['variation_id'] != null
                            and $internal_item['variation_id'] != '') {
                            $title = $this->getProductTitleByID($internal_item['variation_id']);
                        }
                        else
                        {
                            $internal_item['variation_id'] = 0;
                        }
                        $cartContent[$i] = array(
                            'productID' => $internal_item['product_id'],
                            'variationID' => $internal_item['variation_id'],
                            'productTitle' => $this->getProductTitleByID($internal_item['product_id']),
                            'variationTitle' => $title,
                            'quantity' => $internal_item['quantity'],
                            'amount' => $internal_item['line_total'] / $internal_item['quantity'],
                            'total' => $internal_item['line_total'],
                            'imageUrl' => $this->findProductImageUrlByID($internal_item['product_id'])
                        );
                        $i++;
                    }
                }
                return \response(
                    json_encode
                    (
                     ['message' => 'اطلاعات کارت با موفقیت از دیتابیس استخراج شد',
                    'content' => $cartArray,
                    'status' => 200]
                    ));
            } else {
                return \response([
                    'result' => 'false',
                    'message' => 'شما برای عملیات درخواست شده، دسترسی لازم را ندارید'
                ]);
            }
        } else {
            return \response([
                'result' => 'false',
                'message' => 'ارسال نشانه منحصر به فرد کاربر اجباری می باشد'
            ]);
        }

    }

    public function add_to_cart(Request $request)
    {
        $validate = $this->validate($request, [
            'token' => 'required',
            'productID' => 'required',
            'variationID' => 'required',
            'quantity' => 'required',
            'total' => 'required'
        ]);

        if ($validate) {
            $userID = $this->getUserIDByToken($request->token);
            if ($userID) {
                $key = Hash::make('7721');
                $cart = $this->getCartContentByUserID($userID);
                $cartArray = unserialize($cart->meta_value);
                $attribute = null;
                $metaValue = null;
                if ($request->variationID != 0) {
                    $attribute = $this->getAttributefromVariation($request->variationID, $userID);
                    $metaValue = $this->getMetaValuefromVariation($request->variationID, $userID);
                }
                $cartArray = array(
                    $key => array(
                        'key' => $key,
                        'product_id' => $request->productID,
                        'variation_id' => $request->variationID,
                        'variation' => array(
                            $attribute => $metaValue
                        ),
                        'quantity' => $request->quantity,
                        'data_hash' => $key,
                        'line_tax_data' => array(
                            'subtotal' => '',
                            'total' => ''
                        ),
                        'line_subtotal' => $request->total,
                        'line_subtotal_tax' => 0,
                        'line_total' => $request->total,
                        'line_tax' => 0
                    )
                );

                //array_push($cartArray, $newElement);
                $this->UpdateCart($userID, serialize($cartArray));
                return \response([
                    'result' => 'true',
                    'message' => 'به سبد خرید اضافه شد'
                ]);
            } else {
                return \response([
                    'result' => 'false',
                    'message' => 'شما برای عملیات درخواست شده، دسترسی لازم را ندارید، یا نشانه انحصاری کاربر معتبر نمی باشد'
                ]);
            }
        }
    }

    public function UpdateCart($userID, $newCart)
    {
        UsersMeta::
        where('user_id', $userID)
            ->where('meta_key', '_woocommerce_persistent_cart_1')
            ->update(['meta_value' => $newCart]);
    }

    public function getAttributefromVariation($variationID, $userID)
    {
        $meta = UsersMeta::where('user_id', $userID)
            ->where('meta_key', 'like', '%attribute%')->first();
        if ($meta)
            return $meta->meta_key;
        else
            return false;
    }

    public function getMetaValuefromVariation($variationID, $userID)
    {
        $meta = UsersMeta::where('user_id', $userID)
            ->where('meta_key', 'like', '%attribute%')->first();
        if ($meta)
            return $meta->meta_value;
        else
            return false;
    }

    public function getCartContentByUserID($userID)
    {
        $cartContent = UsersMeta::
        where('meta_key', '_woocommerce_persistent_cart_1')
            ->where('user_id', $userID)
            ->first();
        if ($cartContent) {
            return $cartContent;
        }
    }

    public function getUserIDByToken($tokenString)
    {
        $userMeta = UsersMeta::
        where('meta_key', 'api_token')
            ->where('meta_value', $tokenString)
            ->first();
        if ($userMeta) {
            return $userMeta->user_id;
        }

    }

    public function getProductTitleByID(int $id)
    {
        return Products::where('ID', $id)->first()->post_title;
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

