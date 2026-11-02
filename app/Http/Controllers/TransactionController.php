<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Pos\VoidPosTransactionAction;
use App\Enums\UserRole;
use App\Models\PosTransaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('q', '');
        $status = $request->query('status', 'ALL');
        $date = $request->query('date', today()->format('Y-m-d'));

        $query = PosTransaction::with(['cashier', 'items.product', 'items.service', 'voidedByTeacher', 'posShift'])
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if ($status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($date !== '') {
            $query->whereDate('transaction_date', $date);
        }

        $transactions = $query->paginate(10)->withQueryString();
        $teachers = User::where('role_type', 'piket_guru')->where('is_active', true)->get();
        $storeInfo = StoreSettingController::getStoreInfo();

        return view('transactions.index', compact('transactions', 'search', 'status', 'date', 'teachers', 'storeInfo'));
    }

    public function void(Request $request, int $id, VoidPosTransactionAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'supervisor_teacher_id' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('role_type', UserRole::PIKET_GURU->value)
                    ->where('is_active', true),
            ],
            'supervisor_pin' => ['required', 'string'],
            'void_reason' => ['required', 'string', 'max:255'],
        ], [
            'supervisor_teacher_id.exists' => 'Guru supervisor yang dipilih tidak aktif atau tidak memiliki peran Piket Guru.',
        ]);

        $transaction = PosTransaction::with('items.boms')->findOrFail($id);
        $teacher = User::findOrFail($validated['supervisor_teacher_id']);

        if (! Hash::check($validated['supervisor_pin'], $teacher->pin_code)) {
            return back()->withErrors(['supervisor_pin' => 'PIN Approval Guru Supervisor salah.']);
        }

        try {
            $voided = $action->execute($transaction, $teacher, $validated['void_reason']);

            return redirect()->route('transactions.index')->with(
                'success',
                "Transaksi {$voided->invoice_number} berhasil di-VOID! Stok barang/bahan baku telah dikembalikan dan Jurnal Pembalik otomatis diposting."
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Gagal membatalkan transaksi: '.$e->getMessage()]);
        }
    }
}
