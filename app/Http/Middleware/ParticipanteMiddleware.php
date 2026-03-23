<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ParticipanteMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user || !$user->isParticipante()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $participante = $user->participante;

            if (!$participante) {
                return response()->json(['error' => 'Participante not found'], 404);
            }

        } catch (JWTException $e) {
            return response()->json(['error' => 'Token is invalid'], 401);
        }

        return $next($request);
    }
}