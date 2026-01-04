<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 1. Cho phép các yêu cầu từ Frontend (React/Vite) có thể truy cập Session/Cookie
        $middleware->statefulApi();

        // 2. Loại bỏ kiểm tra CSRF cho toàn bộ API để tránh lỗi 419 khi POST bài viết
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // 3. (Tùy chọn) Thêm Alias nếu sau này bạn dùng phân quyền hoặc Sanctum
        $middleware->alias([
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Cấu hình trả về lỗi dạng JSON nếu request từ API bị lỗi
    })->create();
