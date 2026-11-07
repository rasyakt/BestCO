@php
    $statusCode = $statusCode ?? 419;
    $title = $title ??
    'Sesi Kedaluwarsa';
    $message = $message ?? 'Sesi Anda telah kadaluarsa. Silakan muat ulang halaman dan coba lagi.';
    $illustration = $illustration ?? 'error-419';
    $primaryColor = $primaryColor ?? 'orange-600';
    $accentGradient = $accentGradient ?? 'from-orange-500 to-orange-700';
    $actionLabel = $actionLabel ?? 'Muat Ulang Halaman';
    $actionUrl = $actionUrl ?? '/';
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
