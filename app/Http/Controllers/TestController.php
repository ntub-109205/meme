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

     //    return back()->with('success','Image Upload successfully');
    	$a = [
    		'1' => 'ha'
    	];

    	return $a['2'];
    }
}
