<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;





// Public endpoits


Route::post('/signup',[AuthController::class,'register']);

Route::post('/login',[AuthController::class,'login']);


// Authenticated endpoints

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class,'profile']);
    Route::post('/logout',[AuthController::class,'logout']);
    // Route::put('/update',[AuthController::class,'update']);
});

/**
 * List of all the endpoints.
 */



// Route::get('')

// Route::
