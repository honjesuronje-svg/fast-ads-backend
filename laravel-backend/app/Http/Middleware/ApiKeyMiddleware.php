<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_API_KEY',
                    'message' => 'API key is required',
                ],
            ], 401);
        }

        try {
            $tenant = Tenant::where('api_key', $apiKey)
                ->where('status', 'active')
                ->first();

            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_API_KEY',
                        'message' => 'Invalid or inactive API key',
                    ],
                ], 401);
            }

            // Attach tenant to request for use in controllers
            $request->merge(['tenant' => $tenant]);
        } catch (\Exception $e) {
            // If database is not available, still return 401 for invalid key
            // This allows testing without database setup
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_API_KEY',
                    'message' => 'Invalid or inactive API key',
                ],
            ], 401);
        }

        return $next($request);
    }
}

