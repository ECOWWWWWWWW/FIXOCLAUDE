<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Http\Request;


Route::get('auth/{provider}', function ($provider) {
    return Socialite::driver($provider)->stateless()->redirect();
});

Route::get('auth/{provider}/callback', function ($provider) {
    $socialUser = Socialite::driver($provider)->stateless()->user();

    $user = User::updateOrCreate(
        ['email' => $socialUser->getEmail()],
        [
            'firstname' => $socialUser->getName(),
            'username' => $socialUser->getNickname() ?? explode('@', $socialUser->getEmail())[0],
            'password' => bcrypt(uniqid()), // Random password for security
        ]
    );

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'user' => $user,
        'access_token' => $token,
        'token_type' => 'Bearer'
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// In routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Admin Routes
    Route::prefix('admin')->group(function () {
        Route::middleware([\App\Http\Middleware\AdminMiddleware::class])->group(function () {
            // Admin user management
            Route::get('/users/pending', [AdminController::class, 'getPendingUsers']);
            Route::post('/users/{id}/approve', [AdminController::class, 'approveUser']);
            Route::post('/users/{id}/reject', [AdminController::class, 'rejectUser']);
            Route::post('/users/{id}/verification', [AdminController::class, 'requestVerification']);
            Route::get('/audit-logs', [AdminController::class, 'getAuditLogs']);
        });
    });
});

Route::middleware('auth:sanctum')->get('/notifications', function (Request $request) {
    return response()->json($request->user()->notifications);
});

// Admin middleware for role check
Route::middleware('auth:sanctum')->get('/admin-check', function (Request $request) {
    if ($request->user()->role !== 3) {
        return response()->json(['admin' => false], 403);
    }
    return response()->json(['admin' => true], 200);
});


// In routes/api.php
Route::middleware('auth:sanctum')->post('/user/update-firebase-token', function (Request $request) {
    $request->validate([
        'token' => 'required|string'
    ]);
    
    $user = $request->user();
    $user->firebase_token = $request->token;
    $user->save();
    
    return response()->json(['message' => 'Firebase token updated successfully']);
});