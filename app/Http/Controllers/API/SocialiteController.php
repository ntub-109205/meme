<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Socialite;

class SocialiteController extends Controller
{
    public function redirectToProvider()
	{
    	return Socialite::driver('facebook')->stateless()->redirect();
	}

	public function handleProviderCallback()
    {
        $user = Socialite::driver('facebook')->stateless()->user();
        return $user->email;
    }

    public function google_redirectToProvider()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function google_handleProviderCallback()
    {
        $user = Socialite::driver('google')->stateless()->user();
        return $user->email;
    }
}
