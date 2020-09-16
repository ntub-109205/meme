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
	Route::post('/store', 'API\TemplateController@store');
	Route::get('/show/{category_id}', 'API\TemplateController@show');
	Route::get('/saved/{template_id}', 'API\TemplateController@savedStatus');
	Route::post('/saved', 'API\TemplateController@saved');
	Route::get('/meme/{template_id}', 'API\TemplateController@meme');
});

Route::prefix('txt')->group(function () {
	Route::post('/templateStore', 'API\TxtController@templateStore');
	Route::post('/memeStore', 'API\TxtController@memeStore');
	Route::post('/test', 'API\TxtController@test');
});

/*
|------------------------------------
| image api
|------------------------------------
*/
Route::prefix('meme')->group(function () {
	Route::post('/store', 'API\ImageController@store');
	Route::get('/show/{category_id}', 'API\ImageController@show');
	Route::get('/saved/{meme_id}', 'API\ImageController@savedStatus');
	Route::post('/saved', 'API\ImageController@saved');
	Route::post('/thumb', 'API\ImageController@thumb');
});

/*
|------------------------------------
| profile api
|------------------------------------
*/
Route::prefix('profile')->group(function () {
	Route::get('/', 'API\ProfileController@user');
	Route::get('/show/saved', 'API\ProfileController@saved');
	Route::get('/show/myWork', 'API\ProfileController@myWork');
});
