<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/phpinfo', function () {
    return view('phpinfo');
});

Route::get('/privacy', function () {
    return view('privacy');
});

Route::get('/test', 'TestController@test');

Route::get('/redirect', 'API\SocialiteController@redirectToProvider');
Route::get('/callback', 'API\SocialiteController@handleProviderCallback');

// Route::get('/google-redirect', 'API\SocialiteController@google_redirectToProvider');
// Route::get('/google-callback', 'API\SocialiteController@google_handleProviderCallback');