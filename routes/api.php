<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// 待改
Route::post('login', 'API\UserController@login')->name('api_login');

// Route::post('register', 'API\UserController@register');
Route::group(['middleware' => 'auth:api'], function(){
	Route::post('details', 'API\UserController@details')->name('api_details');;
});