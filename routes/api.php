<?php

use App\Http\Controllers\Api\Moderator\ReportController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\Moderator\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Public Routes (Không cần đăng nhập)
Route::post('/login', [AuthController::class, 'login']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/tags', [TagController::class, 'index']);
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{article}', [ArticleController::class, 'show']);

// Private Routes (Yêu cầu Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile/update', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::put('/profile/password', [UserController::class, 'changePassword']);

    // Articles
    Route::get('/articles/mine', [ArticleController::class, 'mine']);

    // Moderator Group
    Route::prefix('moderator')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/articles/pending', [DashboardController::class, 'getPendingArticles']);
        Route::get('/report', [DashboardController::class, 'getReportData']);
        Route::get('/statistics', [DashboardController::class, 'getStatistics']);
        Route::get('/articles/{id}', [DashboardController::class, 'getArticleDetail']);
        Route::patch('/articles/{id}/status', [DashboardController::class, 'updateArticleStatus']);

        Route::get('/reports', [ReportController::class, 'index']);
        Route::patch('/reports/{id}/status', [ReportController::class, 'updateStatus']);
    });

    // User Management
    Route::post('/users/import', [UserController::class, 'importExcel']);
    Route::patch('/users/{id}/role', [UserController::class, 'toggleRole']);
    Route::patch('/users/{id}/reset-password', [UserController::class, 'resetPassword']);

    // API Resources (Tự động tạo store, show, update, destroy)
    Route::apiResource('users', UserController::class);
    Route::apiResource('articles', ArticleController::class)->except(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->except(['index']);
    Route::apiResource('tags', TagController::class)->except(['index']);

    // Others
    Route::post('/reports', [ReportController::class, 'store']);
    Route::post('/upload', [MediaController::class, 'store']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});
