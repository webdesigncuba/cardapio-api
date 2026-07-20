<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', RegisterController::class)
        ->name('auth.register');

    Route::post('auth/login', LoginController::class)
        ->name('auth.login');

    Route::post('auth/logout', LogoutController::class)
        ->middleware('auth:sanctum')
        ->name('auth.logout');
});
