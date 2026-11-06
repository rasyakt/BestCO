<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Halaman tidak ditemukan.'], 404);
            }

            return View::make('errors.404', [
                'exception' => $e,
                'statusCode' => 404,
                'title' => 'Halaman Tidak Ditemukan',
                'message' => 'Halaman yang Anda cari tidak ada atau telah dipindahkan.',
                'illustration' => 'error-404',
                'primaryColor' => 'brand-600',
                'accentGradient' => 'from-brand-500 to-brand-700',
            ])->status(404);
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada server.',
                ], $e->getStatusCode() ?: 500);
            }

            return View::make('errors.500', [
                'exception' => $e,
                'statusCode' => $e->getStatusCode() ?: 500,
                'title' => 'Terjadi Kesalahan',
                'message' => config('app.debug') ? $e->getMessage() : 'Sistem sedang mengalami gangguan. Silakan coba lagi nanti.',
                'illustration' => 'error-500',
                'primaryColor' => 'rose-600',
                'accentGradient' => 'from-rose-500 to-rose-800',
            ])->status($e->getStatusCode() ?: 500);
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e): Response
    {
        return parent::render($request, $e);
    }
}
