<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->namespace('Api\v1')->group(function ()
{
    Route::get('/users', 'UserController@index');
    Route::post('/login', 'UserController@login');
    Route::post('/register', 'UserController@register');
});

Route::prefix('v1/product')->namespace('Api\v1')->group(function ()
{
    Route::post('/all', 'PostController@all_product_summary');
    Route::post('/single/{product_id}', 'PostController@get_single');
    Route::post('/categories', 'PostController@get_categories');
    Route::post('/cities', 'PostController@get_cities');
    Route::post('/test', 'PostController@test');
});

Route::prefix('v1/cart')->namespace('Api\v1')->group(function ()
{
    Route::post('/add_to_cart', 'CartController@add_to_cart');
    Route::post('/get_cart_total', 'CartController@get_cart_total');
    Route::post('/get_cart_count', 'CartController@get_cart_count');
    Route::post('/get_cart_content', 'CartController@get_cart_content');
});

Route::prefix('v1/order')->namespace('Api\v1')->group(function ()
{

});

Route::prefix('v1/vendor')->namespace('Api\v1')->group(function ()
{
    Route::get('/all', 'VendorController@all');
});

Route::prefix('v1/session')->namespace('Api\v1')->group(function ()
{
    Route::post('/get_user_session', 'SessionController@get_user_session');
});

Route::prefix('v1/comment')->namespace('Api\v1')->group(function ()
{
    Route::post('/get_comments/{product_id}', 'CommentController@get_comments');
});