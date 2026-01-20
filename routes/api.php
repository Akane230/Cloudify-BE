<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected auth routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/ping', function () { return response()->json(['ok' => true]); });
});

//user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user/profile-picture', [UserController::class, 'uploadProfilePicture']);
    Route::delete('/user/profile-picture', [UserController::class, 'deleteProfilePicture']);
    Route::middleware('auth:sanctum')->put('/user', [UserController::class, 'update']);

});

