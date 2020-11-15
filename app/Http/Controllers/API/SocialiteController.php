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
        // $user = Socialite::driver('facebook')->user();
        // return $user;
        $user = Socialite::driver('facebook')->stateless()->user();
        return $user->email;
        // // only allow people with @company.com to login
        // if(explode("@", $user->email)[1] !== 'company.com'){
        //     return redirect()->to('/');
        // }
        // // check if they're an existing user
        // $existingUser = User::where('email', $user->email)->first();
        // if($existingUser){
        //     // log them in
        //     auth()->login($existingUser, true);
        // } else {
        //     // create a new user
        //     $newUser                  = new User;
        //     $newUser->name            = $user->name;
        //     $newUser->email           = $user->email;
        //     $newUser->google_id       = $user->id;
        //     $newUser->avatar          = $user->avatar;
        //     $newUser->avatar_original = $user->avatar_original;
        //     $newUser->save();
        //     auth()->login($newUser, true);
        // }
        // return redirect()->to('/home');
    }
}
