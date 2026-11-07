<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureActiveShift;
use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\EnsureRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsureAccountIsActive::class,
        ]);

        $middleware->alias([
            'shift.active' => EnsureActiveShift::class,
            'role' => EnsureRole::class,
            'feature' => EnsureFeatureEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Halaman tidak ditemukan.'], 404);
            }

            return response(view('errors.404', [
                'exception' => $e,
                'statusCode' => 404,
                'title' => 'Halaman Tidak Ditemukan',
                'message' => 'Halaman yang Anda cari tidak ada atau telah dipindahkan.',
                'illustration' => 'error-404',
                'primaryColor' => 'brand-600',
                'accentGradient' => 'from-brand-500 to-brand-700',
            ]), 404);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }

            return response(view('errors.403', [
                'exception' => $e,
                'statusCode' => 403,
                'title' => 'Akses Ditolak',
                'message' => 'Anda tidak memiliki izin untuk mengakses halaman ini.',
                'illustration' => 'error-403',
                'primaryColor' => 'amber-600',
                'accentGradient' => 'from-amber-500 to-amber-800',
            ]), 403);
        });

        $exceptions->render(function (UnauthorizedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Autentikasi diperlukan.'], 401);
            }

            return response(view('errors.401', [
                'exception' => $e,
                'statusCode' => 401,
                'title' => 'Autentikasi Diperlukan',
                'message' => 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.',
                'illustration' => 'error-401',
                'primaryColor' => 'blue-600',
                'accentGradient' => 'from-blue-500 to-blue-700',
                'actionLabel' => 'Masuk Sekarang',
                'actionUrl' => route('login'),
            ]), 401);
        });

        $exceptions->render(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi kedaluwarsa.'], 419);
            }

            return response(view('errors.419', [
                'exception' => $e,
                'statusCode' => 419,
                'title' => 'Sesi Kedaluwarsa',
                'message' => 'Sesi Anda telah kadaluarsa. Silakan muat ulang halaman dan coba lagi.',
                'illustration' => 'error-419',
                'primaryColor' => 'orange-600',
                'accentGradient' => 'from-orange-500 to-orange-700',
                'actionLabel' => 'Muat Ulang Halaman',
                'actionUrl' => '/',
            ]), 419);
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Terlalu banyak permintaan.'], 429);
            }

            return response(view('errors.429', [
                'exception' => $e,
                'statusCode' => 429,
                'title' => 'Terlalu Banyak Permintaan',
                'message' => 'Anda telah melakukan terlalu banyak permintaan. Silakan tunggu sebentar sebelum mencoba lagi.',
                'illustration' => 'error-429',
                'primaryColor' => 'rose-600',
                'accentGradient' => 'from-rose-500 to-rose-700',
            ]), 429);
        });

        $exceptions->render(function (ServiceUnavailableHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Layanan tidak tersedia.'], 503);
            }

            return response(view('errors.503', [
                'exception' => $e,
                'statusCode' => 503,
                'title' => 'Layanan Tidak Tersedia',
                'message' => 'Aplikasi sedang dalam pemeliharaan. Silakan coba lagi dalam beberapa menit.',
                'illustration' => 'error-503',
                'primaryColor' => 'slate-600',
                'accentGradient' => 'from-slate-500 to-slate-800',
            ]), 503);
        });

        $exceptions->render(function (Throwable $e, $request) {
            // Biarkan exception internal Laravel ditangani secara default agar
            // redirect validasi, autentikasi, otorisasi, dan 404 bekerja normal.
            if ($e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof AuthorizationException
                || $e instanceof ModelNotFoundException
                || $e instanceof HttpResponseException) {
                return null;
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada server.',
                ], method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
            }

            return response(view('errors.500', [
                'exception' => $e,
                'statusCode' => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500,
                'title' => 'Terjadi Kesalahan',
                'message' => config('app.debug') ? $e->getMessage() : 'Sistem sedang mengalami gangguan. Silakan coba lagi nanti.',
                'illustration' => 'error-500',
                'primaryColor' => 'rose-600',
                'accentGradient' => 'from-rose-500 to-rose-800',
            ]), method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        });
    })->create();
