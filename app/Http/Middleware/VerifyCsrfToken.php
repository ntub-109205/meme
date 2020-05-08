<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'http://localhost:8000/login',
        'http://localhost:8000/logout',
        'http://localhost:8000/meme/store',
        'http://localhost:8000/template/store',
        'http://localhost:8000/template/show'
    ];

    /*
    protected $except = [
        'http://140.131.115.99/login',
        'http://140.131.115.99/logout',
        'http://140.131.115.99/meme/store',
        'http://140.131.115.99/template/store',
        'http://140.131.115.99/template/show'
    ];
    */
}
