<?php
namespace wildcats1369\auth\Http\Controllers;

use Filament\Http\Livewire\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Http\Request;
use wildcats1369\auth\Services\AuthService;
use Illuminate\Support\Facades\Log;

class Login extends BaseLogin
{
    protected $jwtService;

    public function __construct()
    {
        $this->jwtService = app()->make(AuthService::class);
        \Log::info('Construct method called');
    }

    public function authenticate() : ?LoginResponse
    {
        Log::info('authenticate');
        $credentials = $this->form->getState();

        if (! auth()->attempt($credentials)) {
            $this->addError('email', __('auth.failed'));
            return null;
        }

        $user = auth()->user();
        $token = $this->jwtService->generateTokens($user);
        Log::info($token);

        // You can store the token in the session or return it as part of the response
        session(['jwt_token' => $token]);

        return app(LoginResponse::class);
    }
}