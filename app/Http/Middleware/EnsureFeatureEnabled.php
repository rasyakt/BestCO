<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Services\Feature\FeatureManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureFeatureEnabled
{
    public function __construct(
        private readonly FeatureManager $featureManager
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Superadmin selalu memiliki akses administratif penuh
        if ($user->hasRole(UserRole::SUPERADMIN)) {
            return $next($request);
        }

        // Cek apakah fitur diaktifkan secara global di sistem
        if (! $this->featureManager->isGloballyEnabled($featureKey)) {
            abort(403, 'Akses ditolak: Fitur ini sedang dinonaktifkan oleh administrator sistem.');
        }

        // Cek apakah peran akun user ini diizinkan mengakses fitur
        if (! $this->featureManager->canAccess($featureKey, $user)) {
            abort(403, 'Akses ditolak: Peran Anda tidak memiliki izin untuk mengakses fitur ini.');
        }

        return $next($request);
    }
}

