{{ $__env->yieldContent('content') }}

@php
    $statusCode = $statusCode ?? 403;
    $title = $title ?? 'Akses Ditolak';
    $message = $message ?? 'Anda tidak memiliki izin untuk mengakses halaman ini.';
    $illustration = $illustration ?? 'error-403';
    $primaryColor = $primaryColor ?? 'amber-600';
    $accentGradient = $accentGradient ?? 'from-amber-500 to-amber-800';
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
