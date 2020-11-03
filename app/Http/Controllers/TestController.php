<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Meme;

class TestController extends Controller
{
    public function test()
    {
    	$a = [
            "a" => "a",
            "b" => "b"
        ];
        Arr::forget($a, 'a');

        // $tags = Meme::find(1)->tags()->where('name', 'LIKE', '%h%')->get();
        $tags = Meme::find(1)->tags()->pluck('name');
        // dd($tags);
        dd(strlen('$2y$10$qlbiLMQn7bE.G5HSsh7Oyu3Kdj/Jqv0oZYMdEs82M1e/pPrb4XwKm'));
    }
}
