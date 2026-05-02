<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:5173',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:5173',
            'https://localhost:3000',
            'https://localhost:5173',
            'https://127.0.0.1:3000',
            'https://127.0.0.1:5173',
            'https://chama-frontend-ph8d.onrender.com',
        ];

        $origin = $request->header('Origin');
        $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : ($origin ?: '*');

        $credentials = $allowedOrigin === '*' ? 'false' : 'true';

        if ($request->isMethod('OPTIONS')) {
            return response()->json('OK', 200, [
                'Access-Control-Allow-Origin' => $allowedOrigin,
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept, Accept-Language, X-XSRF-TOKEN',
                'Access-Control-Allow-Credentials' => $credentials,
                'Access-Control-Max-Age' => '86400',
            ]);
        }

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            \Log::error('CorsMiddleware caught exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $response = response()->json([
                'error' => 'Server Error',
                'message' => $e->getMessage()
            ], 500, [
                'Access-Control-Allow-Origin' => $allowedOrigin,
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept, Accept-Language, X-XSRF-TOKEN',
                'Access-Control-Allow-Credentials' => $credentials,
                'Access-Control-Expose-Headers' => 'Content-Length, X-JSON-Response',
            ]);
        }

        if (method_exists($response, 'header')) {
            $response->header('Access-Control-Allow-Origin', $allowedOrigin);
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Accept-Language, X-XSRF-TOKEN');
            $response->header('Access-Control-Allow-Credentials', $credentials);
            $response->header('Access-Control-Expose-Headers', 'Content-Length, X-JSON-Response');
        }

        return $response;
    }
}
