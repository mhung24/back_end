<?php

use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\CommentController;
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
use App\Http\Controllers\AuthorController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::prefix('users')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/login', [AuthController::class, 'adminLogin']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/tags', [TagController::class, 'index']);
Route::get('/articles/popular', [ArticleController::class, 'popular']);
Route::get('/articles', [ArticleController::class, 'index']);

Route::get('/authors/{id}', [AuthorController::class, 'getProfile']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile/update', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::put('/profile/password', [UserController::class, 'changePassword']);

    Route::get('/articles/mine', [ArticleController::class, 'mine']);

    Route::post('/authors/{id}/follow', [AuthorController::class, 'toggleFollow']);
    Route::post('/authors/{id}/report', [AuthorController::class, 'reportAuthor']);

    Route::prefix('moderator')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/articles/pending', [DashboardController::class, 'getPendingArticles']);
        Route::get('/report', [DashboardController::class, 'getReportData']);
        Route::get('/statistics', [DashboardController::class, 'getStatistics']);
        Route::get('/articles', [ArticleController::class, 'index']);
        Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);
        Route::get('/articles/{id}', [DashboardController::class, 'getArticleDetail']);
        Route::patch('/articles/{id}/status', [DashboardController::class, 'updateArticleStatus']);
        Route::get('/reports', [ReportController::class, 'index']);
        Route::patch('/reports/{id}/status', [ReportController::class, 'updateStatus']);
    });

    Route::post('/users/import', [UserController::class, 'importExcel']);
    Route::patch('/users/{id}/role', [UserController::class, 'toggleRole']);
    Route::patch('/users/{id}/reset-password', [UserController::class, 'resetPassword']);

    Route::apiResource('users', UserController::class);

    Route::get('/articles/{id}', [ArticleController::class, 'show'])->where('id', '[0-9a-fA-F-]{36}');

    Route::apiResource('articles', ArticleController::class)->except(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->except(['index']);
    Route::apiResource('tags', TagController::class)->except(['index']);

    Route::post('/reports', [ReportController::class, 'store']);
    Route::post('/upload', [MediaController::class, 'store']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::post('/articles/{id}/bookmark', [BookmarkController::class, 'toggle']);

    Route::post('/articles/{id}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
});

Route::get('/articles/{slug}', [ArticleController::class, 'showDetail']);
