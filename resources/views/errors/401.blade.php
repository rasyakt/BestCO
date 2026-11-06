{{ $__env->yieldContent('content') }}

@php
    $statusCode = $statusCode ?? 401;
    $title = $title ?? 'Autentikasi Diperlukan';
    $message = $message ?? 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.';
    $illustration = $illustration ?? 'error-401';
    $primaryColor = $primaryColor ?? 'blue-600';
    $accentGradient = $accentGradient ?? 'from-blue-500 to-blue-700';
    $actionLabel = $actionLabel ?? 'Masuk Sekarang';
    $actionUrl = $actionUrl ?? route('login');
@endphp

@include('errors.error', [
    'title' => $title,
    'message' => $message,
    'illustration' => $illustration,
    'primaryColor' => $primaryColor,
    'accentGradient' => $accentGradient,
    'actionLabel' => $actionLabel,
    'actionUrl' => $actionUrl,
    'statusCode' => $statusCode,
])
