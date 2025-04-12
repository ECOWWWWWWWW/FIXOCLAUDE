<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // ... other code ...
    
    /**
     * The application's route middleware.
     *
     * @var array<string, class-string|string>
     */
    // For newer Laravel versions (Laravel 9+)
    protected $routeMiddleware = [
        // ...
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
    ];

    // ... other code ...
}