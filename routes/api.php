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
    Route::get('/products', 'PostController@all_product_summary');
    Route::get('/products/{product}', 'PostController@single');
    Route::post('/add', 'PostController@add_to_cart');
    Route::post('/get_cart_total', 'CartController@get_cart_total');

});
