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
use App\Http\Controllers\UserController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\VerificationController;
/**
 * Public endpoits
 */

// Authentication endpoints
Route::post('/signup',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);

// Email verification (public access via signed url)
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');

// Password reset endpoints (public)
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);


/**
 * Private endpoits
 */

Route::middleware('auth:sanctum')->group(function () {

    // Authenticated endpoints
    Route::get('/me', [UserController::class,'profile']);
    Route::post('/logout',[AuthController::class,'logout']);
    Route::put('/update-password',[AuthController::class,'update']);
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
    // Users
    Route::get('/users', [UserController::class, 'getUsers']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/profile', [UserController::class, 'update']);

    // Organizations and Teams
    Route::apiResource('organizations', OrganizationController::class)->only(['index', 'store']);
    Route::post('organizations/{organization}/logo', [OrganizationController::class, 'uploadLogo'])->middleware('organization.role:proprietaire,admin');
    Route::apiResource('organizations', OrganizationController::class)->only(['show'])->middleware('organization.role:proprietaire,admin,membre');
    Route::apiResource('organizations', OrganizationController::class)->only(['update', 'destroy'])->middleware('organization.role:proprietaire,admin');
    Route::put('organizations/{organization}/kanban-columns', [OrganizationController::class, 'updateKanbanColumns'])->middleware('organization.role:proprietaire,admin');

    // Teams
    Route::apiResource('organizations.teams', TeamController::class)->only(['index', 'show'])->middleware('organization.role:proprietaire,admin,membre');
    Route::apiResource('organizations.teams', TeamController::class)->only(['store', 'update', 'destroy'])->middleware('organization.role:proprietaire,admin');

    Route::apiResource('organizations.members', OrganizationMemberController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::apiResource('organizations.teams.members', TeamMemberController::class)->only(['index', 'update', 'destroy']);

    // this handles all the methods for Projets endpoint
    Route::apiResource('projets',ProjetController::class);

    // this handles all the methods for Tags endpoint
    Route::apiResource('tags', TagController::class)->only(['index', 'store', 'update', 'destroy']);

    // this handles all the methods for Taches endpoint
    Route::apiResource('taches',TacheController::class);
    Route::post('/taches/{id}/banner', [TacheController::class, 'uploadBanner']);
    
    // Upload profile picture
    Route::post('/users/profile-picture', [UserController::class, 'uploadProfilePicture']);

    // Notification endpoints
    Route::get('/notifications', [NotificationsController::class, 'index']);
    Route::get('/notifications/all', [NotificationsController::class, 'all']);
    Route::get('/notifications/unread-count', [NotificationsController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationsController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationsController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationsController::class, 'destroy']);

    // This handles all the methods for Commantaires endpoints
    Route::apiResource('commentaires',CommentairesController::class)->only(['index', 'store', 'update', 'destroy']);

    // Invitations
    Route::post('/invitations', [InvitationController::class, 'store']);
    Route::get('/invitations/{token}', [InvitationController::class, 'show']);
    Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);

});
