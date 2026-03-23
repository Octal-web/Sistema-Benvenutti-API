<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user || !$user->isAdmin()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token is invalid'], 401);
        }

        return $next($request);
    }
}