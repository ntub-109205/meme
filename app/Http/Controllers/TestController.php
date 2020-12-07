<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Meme;
use App\User;
use Auth;

class TestController extends Controller
{
    public function test()
    {
    	$existingUser = User::where('email', 'kevin0507a@gmail.com')->first();
        // if ($existingUser) {
        //     auth('api')->login($existingUser, true);
        // }

        auth('web')->login($existingUser, true);
        dd(Auth::guard('api')->user()->id);
    }
}
