<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function test(Request $request)
    {
    	// $image = $request->file('meme_image');
     //    $filename = time().'.'.$image->extension();
     //    $location = public_path('images/meme/meme/');
     //    $image->move($location, $filename);
        
        // return $request;
        foreach ($request->all() as $key => $value) {
            echo($key);
            echo "   ";
        }
    }
}
