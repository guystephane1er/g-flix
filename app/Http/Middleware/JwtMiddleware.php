<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            // Check if user is active
            if ($user && $user->status !== 'active') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User account is not active'
                ], 403);
            }
        } catch (Exception $e) {
            if ($e instanceof TokenInvalidException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token is invalid'
                ], 401);
            }
            
            if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token has expired'
                ], 401);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Authorization token not found'
            ], 401);
        }

        return $next($request);
    }
}