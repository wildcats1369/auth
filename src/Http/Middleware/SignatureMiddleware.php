<?php
namespace wildcats1369\auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use wildcats1369\auth\Services\AuthService;
use Exception;
use Illuminate\Support\Facades\Log;

class SignatureMiddleware
{
    protected $jwtService;

    public function __construct(AuthService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('x-signature');

        if (! $token) {
            return response()->json(['error' => 'Invalid Request'], 401);
        }

        try {
            $signature = $this->jwtService->generateSignature($request);
            if ($signature == $request->header('x-signature')) {
                return $next($request);
            } else {
                return response()->json(['error' => 'Invalid Request'], 401);
            }
        } catch (Exception $e) {
            return response()->json(['error' => 'Invalid Request'], 401);
        }
    }
}
