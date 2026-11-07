@php
    $statusCode = $statusCode ?? 500;
    $title = $title ?? 'Terjadi Kesalahan Server';
    $message = $message ?? 'Sistem sedang mengalami gangguan. Tim kami telah diberitahu tentang masalah ini.';
    $illustration = $illustration ?? 'error-500';
    $primaryColor = $primaryColor ?? 'rose-600';
    $accentGradient = $accentGradient ?? 'from-rose-500 to-rose-800';
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
