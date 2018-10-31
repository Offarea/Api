<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\v1\Session;
use App\Models\v1\UsersMeta;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SessionController extends Controller
{
    public function get_user_session(Request $request)
    {
        $validate = $this->validate($request, [
            'token' => 'required'
        ]);
        if($validate)
        {
            $userID = $this->getUserIDByToken($request->token);
            if ($userID)
            {
                $session = $this->getSessionByUserID($userID);
                $sessionArray = unserialize($session);
            }
            return \response([
                'message' => 'اطلاعات کارت با موفقیت از دیتابیس استخراج شد',
                'content' => $sessionArray,
                'status' => 200
            ]);
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

    public function getSessionByUserID($userID)
    {
        $session = Session::
        where('session_key', $userID)->first();
        if ($session) {
            return $session->session_value;
        }
    }
}
