<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\TacheController;
use App\Http\Controllers\CommentairesController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PasswordResetController;
/**
 * Public endpoits
 */

// Authentication endpoints
Route::post('/signup',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);

// Password reset endpoints (public)
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);


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
    Route::post('/taches/{tach}/banner', [TacheController::class, 'uploadBanner']);
    
    // Upload profile picture
    Route::post('/users/profile-picture', [AuthController::class, 'uploadProfilePicture']);

    // Notification endpoints
    Route::get('/notifications', [NotificationsController::class, 'index']);
    Route::get('/notifications/all', [NotificationsController::class, 'all']);
    Route::get('/notifications/unread-count', [NotificationsController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationsController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationsController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationsController::class, 'destroy']);

    // This handles all the methods for Commantaires endpoints
    Route::apiResource('commentaires',CommentairesController::class);


});
