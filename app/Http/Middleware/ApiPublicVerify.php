<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiPublicVerify
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey) {
            return response()->json(['message' => 'API Key is required'], 401);
        }

        if ($apiKey !== 'pbDXmVtiprhpL0GFqAYY') {
            return response()->json(['message' => 'Unauthorized API Key'], 401);
        }

        return $next($request);
    }
}
