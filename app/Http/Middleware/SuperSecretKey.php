<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperSecretKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
     public function handle(Request $request, Closure $next): Response
    {
        $provided = (string) $request->header('X-SUPER-SECRET-KEY', '');
        $expected = (string) config('services.super_secret.key', '');

        if ($provided === '') {
            return response()->json([
                'message' => 'Brakuje klucza w nagłówku',
            ], 401);
        }

        if ($expected === '') {
            return response()->json([
                'message' => 'Brak konfiguracji klucza',
            ], 500);
        }

        if (!hash_equals($expected, $provided)) {
            return response()->json([
                'message' => 'Niepoprawny klucz',
            ], 403);
        }

        return $next($request);
    }
}
