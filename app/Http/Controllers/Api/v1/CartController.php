<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\v1\UsersMeta;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CartController extends Controller
{
    public $url = 'https://offarea.ir/offareaApi.php';

    public function get_cart_total(Request $request)
    {
        //Validation
        $validate = $this->validate($request, [
            'token' => 'required',
        ]);

        if($validate)
        {
            $userID = $this->getUserIDByToken($request->token);
            if($userID)
            {
                $cartContent = $this->getCartContentByUserID($userID);
                $cartArray = unserialize($cartContent->meta_value);

                $sum = 0;
                $i = 0;
                foreach ($cartArray as $num => $item)
                {
                    foreach ($item as $num2 => $internal_item)
                    {
                        $sum  = $sum + $internal_item['line_total'];
                    }
                    $i++;
                }
                return \response([
                    'result' => $sum
                ]);
            }
            else
            {
                return \response([
                    'result' => 'false',
                    'message' => 'شما برای عملیات درخواست شده، دسترسی لازم را ندارید'
                ]);
            }
        }
        else
        {
            return \response([
                'result' => 'false',
                'message' => 'ارسال نشانه منحصر به فرد کاربر اجباری می باشد'
            ]);
        }

    }



    public function getCartContentByUserID($userID)
    {
        $cartContent = UsersMeta::
        where('meta_key','_woocommerce_persistent_cart_1')
            ->where('user_id', $userID)
            ->first();
        if($cartContent)
        {
            return $cartContent;
        }
    }

    public function getUserIDByToken($tokenString)
    {
        $userMeta = UsersMeta::
        where('meta_key','api_token')
            ->where('meta_value', $tokenString)
            ->first();
        if($userMeta)
        {
            return $userMeta->user_id;
        }

    }
}
