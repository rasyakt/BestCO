{{ $__env->yieldContent('content') }}

@php
    $statusCode = $statusCode ?? 404;
    $title = $title ?? 'Halaman Tidak Ditemukan';
    $message = $message ?? 'Halaman yang Anda cari tidak ada atau telah dipindahkan.';
    $illustration = $illustration ?? 'error-404';
    $primaryColor = $primaryColor ?? 'brand-600';
    $accentGradient = $accentGradient ?? 'from-brand-500 to-brand-700';
    $actionLabel = $actionLabel ?? null;
    $actionUrl = $actionUrl ?? null;
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
