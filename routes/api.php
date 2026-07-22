<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Clients\ClientController;
use App\Http\Controllers\Api\V1\Restaurants\RestaurantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', RegisterController::class)
        ->name('auth.register');

    Route::post('auth/login', LoginController::class)
        ->name('auth.login');

    Route::post('auth/logout', LogoutController::class)
        ->middleware('auth:sanctum')
        ->name('auth.logout');

    Route::apiResource('restaurants', RestaurantController::class)
        ->middleware('auth:sanctum');

    Route::apiResource('restaurants.clients', ClientController::class)
        ->middleware('auth:sanctum');
});
