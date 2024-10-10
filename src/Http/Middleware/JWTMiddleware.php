<?php
namespace wildcats1369\auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use wildcats1369\auth\Services\AuthService;
use Exception;

class JWTMiddleware
{
    protected $jwtService;

    public function __construct(AuthService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');

        if (! $token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            $claims = $this->jwtService->verifyToken($token);
            $request->attributes->add(['claims' => $claims]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}
