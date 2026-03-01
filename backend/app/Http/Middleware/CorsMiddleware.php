<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        \Log::info('CorsMiddleware running', ['origin' => $request->header('Origin')]);
        $response = $next($request);
        
        $response->headers->set('Access-Control-Allow-Origin', 'https://tttn-2.onrender.com');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        if ($request->isMethod('OPTIONS')) {
            $response->setStatusCode(200);
        }

        \Log::info('CorsMiddleware done', ['headers' => $response->headers->all()]);

        return $response;
    }
}