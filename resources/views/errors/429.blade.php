@php
    $statusCode = $statusCode ?? 429;
    $title = $title ?? 'Terlalu Banyak Permintaan';
    $message = $message ?? 'Anda telah melakukan terlalu banyak permintaan. Silakan tunggu sebentar sebelum mencoba lagi.';
    $illustration = $illustration ?? 'error-429';
    $primaryColor = $primaryColor ?? 'rose-600';
    $accentGradient = $accentGradient ?? 'from-rose-500 to-rose-700';
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
