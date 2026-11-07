@php
    $statusCode = $statusCode ?? 503;
    $title = $title ?? 'Layanan Tidak Tersedia';
    $message = $message ?? 'Aplikasi sedang dalam pemeliharaan. Silakan coba lagi dalam beberapa menit.';
    $illustration = $illustration ?? 'error-503';
    $primaryColor = $primaryColor ?? 'slate-600';
    $accentGradient = $accentGradient ?? 'from-slate-500 to-slate-800';
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
