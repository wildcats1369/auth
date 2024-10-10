<?php
use wildcats1369\auth\Http\Controllers\AuthController;

Route::get('test', function () {
    dd(123);
});
Route::get('login', [AuthController::class, 'login_page']);
Route::post('login', [AuthController::class, 'login']);

Route::post('logout', [AuthController::class, 'logout']);

// If you want the user logged in before accessing the api
// Usually for user based content
Route::middleware('jwt.auth')->group(function () {
    Route::get('protected', function () {
        return response()->json(['message' => 'This is a protected route']);
    });
});

// No login needed but still have security
// Usually for admin that see all content or auth process
Route::middleware('sig.auth')->group(function () {
    Route::post('auth/get-token', [AuthController::class, 'get_token']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);
});