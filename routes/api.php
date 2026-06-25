<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\TacheController;
/**
* Public endpoits
*/

// Authenticated endpoints
Route::post('/signup',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);


/**
* Private endpoits
*/

Route::middleware('auth:sanctum')->group(function () {

    // Authenticated endpoints
    Route::get('/me', [AuthController::class,'profile']);
    Route::post('/logout',[AuthController::class,'logout']);
    Route::put('/update',[AuthController::class,'update']);

    // this handles all the methods for Projets endpoint
    Route::apiResource('projets',ProjetController::class);

    // this handles all the methods for Taches endpoint
    Route::apiResource('taches',TacheController::class);

});
