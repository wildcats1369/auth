<?php
namespace wildcats1369\auth\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use wildcats1369\auth\Services\AuthService;
use Illuminate\Routing\Controller;
use Log;

class AuthController extends Controller
{
    protected $jwtService;

    public function __construct(AuthService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $tokens = $this->jwtService->generateTokens($user);
        Log::info(json_encode($tokens));
        return response()->json(compact('tokens'));
    }

    public function get_token(Request $request)
    {
        $apiKey = $request->header('x-api-key');
        $user = User::where('public_key', $apiKey)->first();
        $response = $this->jwtService->generateTokens($user);
        return response()->json(compact('response'));

    }

    public function login_page()
    {
        return view('jwt::login');
    }

    public function refresh(Request $request)
    {
        try {
            // $claims = $this->jwtService->verifyToken($request->token);
            // $user = User::find($claims['sub']);
            $newToken = $this->jwtService->refreshToken(expiredToken: $request->token);
            return response()->json(compact('newToken'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token expired or invalid'], 401);
        }
    }

    public function logout()
    {
        // Invalidate the token (optional, depends on your implementation)
        return response()->json(['message' => 'Successfully logged out']);
    }
}
