<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function test()
    {
    	$a = ['1' => ' a b '];
    	$a['1'] = trim($a['1']);
    	dd($a['1']);
    }
}
