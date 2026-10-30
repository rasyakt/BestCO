<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\PosShift;
use App\Models\ShiftAttendance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActiveShift
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Superadmin & Admin memiliki akses bypass untuk inspeksi / audit
        if ($user->hasAnyRole(['superadmin', 'admin'])) {
            return $next($request);
        }

        // Cari shift yang sedang OPEN hari ini
        $activeShift = PosShift::where('status', ShiftStatus::OPEN->value)
            ->whereDate('shift_date', today())
            ->latest('opened_at')
            ->first();

        if (!$activeShift) {
            return redirect()->route('shift.index')->with(
                'error',
                'Shift kasir belum dibuka. Silakan lakukan proses Buka Shift bersama Guru Piket terlebih dahulu.'
            );
        }

        // Jika user adalah siswa piket, pastikan terdaftar sebagai operator aktif
        if ($user->role_type === UserRole::PIKET_SISWA) {
            $isAuthorizedOperator = ShiftAttendance::where('pos_shift_id', $activeShift->id)
                ->where('active_operator_user_id', $user->id)
                ->exists();

            if (!$isAuthorizedOperator) {
                return redirect()->route('shift.index')->with(
                    'error',
                    'Akses ditolak: Anda tidak terdaftar sebagai petugas piket operator aktif pada shift ini.'
                );
            }
        }

        return $next($request);
    }
}

