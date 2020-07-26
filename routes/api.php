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

Route::get('/login', 'Auth\LoginController@showLoginForm')->name('login');

/*
|------------------------------------
| login api
|------------------------------------
*/
Route::post('/login', 'API\UserController@login')->name('api_login');
// Route::post('register', 'API\UserController@register');
Route::group(['middleware' => 'auth:api'], function(){
	Route::post('/details', 'API\UserController@details')->name('api_details');
});


/*
|------------------------------------
| template api
|------------------------------------
*/
Route::prefix('template')->group(function () {
	Route::post('/store', 'API\TemplateController@store')->name('api_template_store');
	Route::post('/imageStore', 'API\TemplateController@imageStore');

	Route::post('/show', 'API\TemplateController@show')->name('api_template_show');
	Route::post('/test/store', 'API\TemplateController@testStore');
	Route::post('/savedStatus', 'API\TemplateController@savedStatus');
	Route::post('/saved', 'API\TemplateController@saved');
	Route::post('/meme', 'API\TemplateController@meme');
});

Route::prefix('txt')->group(function () {
	Route::post('/templateStore', 'API\TxtController@templateStore');
	Route::post('/memeStore', 'API\TxtController@memeStore');
});

/*
|------------------------------------
| image api
|------------------------------------
*/
Route::prefix('meme')->group(function () {
	Route::post('/store', 'API\ImageController@store')->name('api_meme_store');
	Route::post('/info', 'API\ImageController@info');
	Route::post('/savedStatus', 'API\ImageController@savedStatus');
	Route::post('/saved', 'API\ImageController@saved');
	Route::post('/thumb', 'API\ImageController@thumb');
});