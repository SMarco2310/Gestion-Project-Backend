<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\TacheController;
use App\Http\Controllers\CommentairesController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\TeamMemberController;
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

    // Organizations and Teams
    Route::apiResource('organizations', OrganizationController::class)->only(['index', 'store']);
    Route::apiResource('organizations', OrganizationController::class)->only(['show'])->middleware('org.role:proprietaire,admin,membre');
    Route::apiResource('organizations', OrganizationController::class)->only(['update', 'destroy'])->middleware('org.role:proprietaire,admin');

    // Teams
    Route::apiResource('organizations.teams', TeamController::class)->only(['index', 'show'])->middleware('org.role:proprietaire,admin,membre');
    Route::apiResource('organizations.teams', TeamController::class)->only(['store', 'update', 'destroy'])->middleware('org.role:proprietaire,admin');

    Route::apiResource('organizations.members', OrganizationMemberController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('organizations.teams.members', TeamMemberController::class)->only(['index', 'update', 'destroy']);

    // this handles all the methods for Projets endpoint
    Route::apiResource('projets',ProjetController::class);

    // this handles all the methods for Tags endpoint
    Route::apiResource('tags', TagController::class)->only(['index', 'store', 'destroy']);

    // this handles all the methods for Taches endpoint
    Route::apiResource('taches',TacheController::class);
    
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
