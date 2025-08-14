<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/* Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum'); */

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/chat/guest', [ChatController::class, 'sendGuestMessage']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/user/stats', [AuthController::class, 'getUserStats']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);
    Route::delete('/user/account', [AuthController::class, 'deleteAccount']);

    // Chat routes
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::get('/conversations', [ChatController::class, 'getConversations']);
    Route::get('/conversations/{id}', [ChatController::class, 'getConversation']);
    Route::delete('/conversations/{id}', [ChatController::class, 'deleteConversation']);
});

// Admin-only routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [AdminController::class, 'getDashboardStats']);
    Route::get('/chat/stats', [AdminController::class, 'getChatStats']);
    
    // User management
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::get('/users/{userId}', [AdminController::class, 'getUserDetails']);
    Route::delete('/users/{userId}', [AdminController::class, 'deleteUser']);
    Route::put('/users/{userId}/premium', [AdminController::class, 'updateUserPremium']);
    
    // Conversation management
    Route::get('/users/{userId}/conversations', [AdminController::class, 'getUserConversations']);
    Route::get('/conversations/{conversationId}', [AdminController::class, 'getConversationDetails']);
    Route::delete('/conversations/{conversationId}', [AdminController::class, 'deleteConversation']);
    
    // Rating management
    Route::get('/ratings', [App\Http\Controllers\Api\RatingController::class, 'getAdminRatings']);
    Route::get('/ratings/stats', [App\Http\Controllers\Api\RatingController::class, 'getAdminRatingStats']);
});

// Public rating routes (accessible to both authenticated and guest users)
Route::post('/ratings', [App\Http\Controllers\Api\RatingController::class, 'submitRating']);
Route::get('/ratings/stats', [App\Http\Controllers\Api\RatingController::class, 'getRatingStats']);

// Authenticated user rating routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/my-ratings', [App\Http\Controllers\Api\RatingController::class, 'getUserRatings']);
});