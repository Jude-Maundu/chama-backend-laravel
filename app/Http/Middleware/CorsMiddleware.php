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
        ];

        $origin = $request->header('Origin');
        $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : (count($allowedOrigins) > 0 ? $allowedOrigins[0] : '*');

        if ($request->isMethod('OPTIONS')) {
            return response()->json('OK', 200, [
                'Access-Control-Allow-Origin' => $origin ?: '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept, Accept-Language, X-XSRF-TOKEN',
                'Access-Control-Allow-Credentials' => 'true',
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
            ], 500);
        }

        if (method_exists($response, 'header')) {
            $response->header('Access-Control-Allow-Origin', $origin ?: '*');
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Accept-Language, X-XSRF-TOKEN');
            $response->header('Access-Control-Allow-Credentials', 'true');
            $response->header('Access-Control-Expose-Headers', 'Content-Length, X-JSON-Response');
        }

        return $response;
    }
}
