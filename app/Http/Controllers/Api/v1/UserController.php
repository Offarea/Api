<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\v1\Users;
use App\Models\v1\UsersMeta;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index()
    {
        $users = Users::all();
        return ($users->display_name);
    }

    public function login(Request $request)
    {
        //Validation
        $this->validate($request, [
            'username' => 'required',
            'password' => 'required'
        ]);

        $url = 'https://offarea.ir/offareaApi.php';

        // what post fields?
        $fields = array(
            'username' => $request->username,
            'password' => $request->password,
            'function' => 'login',
            'passcode' => 'f%eHjk!Ml{f,Q592u2yK'
        );

        // build the urlencoded data
        $postvars = http_build_query($fields);

        // open connection
        $ch = curl_init();

        // set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);

        // execute post
        $result = curl_exec($ch);

        // close connection
        curl_close($ch);

        if ($result) {
            $user = Users::where('user_login', $request->username)->first();
            $meta = UsersMeta::where('meta_key', 'api_token')->where('user_id', $user->ID)->first();
            $data = array(
                'status' => 1,
                'message' => 'با موفقیت وارد شدید',
                'result' => array(
                    'user' => array(
                        'name' => $user->display_name,
                        'apiToken' => $meta->meta_value,
                        'roles' => 1
                    )

                )
                );
            return \response([
                $data
            ]);
        }


    }

    public function register(Request $request)
    {
        $validData = $this->validate($request,
            [
                'user_login' => 'required|string|max:255|unique:wp_users',
                'billing_phone' => 'required',
                'user_email' => 'required|string|email|max:255|unique:wp_users',
                'user_pass' => 'required|string|min:4',
            ]);

        if (preg_match("/^09[0-9]{9}$/", $request->billing_phone)) {
            if ($this->isUnique($request->billing_phone)) {
                $newUser = Users::create([
                    'user_login' => $validData['user_login'],
                    'user_pass' => $this->createWpPassword($validData['user_pass']),
                    'user_nicename' => $validData['user_login'],
                    'user_email' => $validData['user_email'],
                    'user_registered' => date('Y-m-d H:i:s'),
                    'user_status' => '0',
                    'display_name' => $validData['user_login']
                ]);
                $newUserPhone = UsersMeta::create([
                    'user_id' => $newUser->ID,
                    'meta_key' => 'billing_phone',
                    'meta_value' => $validData['billing_phone']
                ]);
                $newUserApiToken = UsersMeta::create([
                    'user_id' => $newUser->ID,
                    'meta_key' => 'api_token',
                    'meta_value' => $this->getToken()
                ]);
                $newUser_register_via_app = UsersMeta::create([
                    'user_id' => $newUser->ID,
                    'meta_key' => 'registered_via_app',
                    'meta_value' => 'yes'
                ]);
                return \response([
                    'result' => 'true',
                    'message' => 'با موفقیت ثبت نام شدید',
                    'api_token' => $newUserApiToken->meta_value,
                    'status' => '200'
                ]);
            } else {
                return \response([
                    'result' => 'false',
                    'message' => 'شماره همراه درج شده تکراری است'
                ]);
            }

        } else
            return \response([
                'result' => 'false',
                'message' => 'شماره همراه درج شده نامعتبر است'
            ]);


        //return true;
    }

    public function isUnique(string $phone)
    {
        $meta = UsersMeta::where('meta_key', 'billing_phone')->where('meta_value', $phone)->first();
        if ($meta)
            return false;
        else
            return true;
    }

    public function createWpPassword(string $pass)
    {
        $url = 'https://offarea.ir/offareaApi.php';
        // Get cURL resource
        $curl = curl_init();
// Set some options - we are passing in a user agent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://offarea.ir/offareaApi.php',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array(
                'password' => $pass,
                'function' => 'createWpPassword',
                'passcode' => 'f%eHjk!Ml{f,Q592u2yK'
            )
        ));
// Send the request & save response to $resp
        $resp = curl_exec($curl);
// Close request to clear up some resources
        curl_close($curl);
        return $resp;
    }

    public function getToken()
    {
        $url = 'https://offarea.ir/offareaApi.php';
        $data = array('function' => 'getToken');
        // Get cURL resource
        $curl = curl_init();
// Set some options - we are passing in a user agent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://offarea.ir/offareaApi.php',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array(
                'function' => 'getToken',
                'passcode' => 'f%eHjk!Ml{f,Q592u2yK'
            )
        ));
// Send the request & save response to $resp
        $resp = curl_exec($curl);
// Close request to clear up some resources
        curl_close($curl);
        return $resp;
    }
}
