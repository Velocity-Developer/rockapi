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
        $Authorization = $request->header('Authorization');

        if (!$Authorization) {
            return response()->json(['message' => 'Authorization is required'], 401);
        }

        if ($Authorization !== 'Bearer ' . env('API_PUBLIC_KEY')) {
            return response()->json(['message' => 'Unauthorized Authorization'], 401);
        }

        return $next($request);
    }
}
