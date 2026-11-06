<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Inventory\ApproveStockOpnameAction;
use App\Actions\Inventory\CreateStockOpnameAction;
use App\Enums\StockOpnameStatus;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\StockOpname;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class StockOpnameController extends Controller
{
    /**
     * Daftar sesi stock opname beserta ringkasan selisih.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'ALL');

        if (! in_array($status, ['ALL', ...array_map(
            static fn (StockOpnameStatus $case): string => $case->value,
            StockOpnameStatus::cases()
        )], true)) {
            $status = 'ALL';
        }

        $totalSessions = StockOpname::count();
        $draftSessions = StockOpname::where('status', StockOpnameStatus::DRAFT)->count();
        $totalShortage = StockOpname::where('status', StockOpnameStatus::APPROVED)->sum('total_shortage_value');
        $totalOverage = StockOpname::where('status', StockOpnameStatus::APPROVED)->sum('total_overage_value');

        $query = StockOpname::with(['createdBy', 'approvedBy'])
            ->withCount('items')
            ->latest('opname_date')
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('opname_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($status !== 'ALL') {
            $query->where('status', $status);
        }

        $opnames = $query->paginate(10)->withQueryString();

        return view('inventory.opname.index', compact(
            'opnames',
            'search',
            'status',
            'totalSessions',
            'draftSessions',
            'totalShortage',
            'totalOverage'
        ));
    }

    /**
     * Formulir penghitungan fisik stok.
     */
    public function create(): View
    {
        $products = Product::with(['unit', 'category'])
            ->where('is_active', true)
            ->orderBy('inventory_type')
            ->orderBy('name')
            ->get();

        $productRows = $products->map(static fn (Product $product): array => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'type' => $product->inventory_type->value,
            'type_label' => $product->inventory_type->label(),
            'unit' => $product->unit?->code ?? '-',
            'stock' => (float) $product->current_stock,
            'cost' => (float) $product->average_cost,
            'category' => $product->category?->name ?? '-',
        ])->values();

        return view('inventory.opname.create', compact('productRows'));
    }

    /**
     * Simpan hasil hitung sebagai draf (belum mengubah stok).
     */
    public function store(Request $request, CreateStockOpnameAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'opname_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array'],
            'items.*.product_id' => [
                'required',
                'distinct',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'items.*.counted_qty' => ['nullable', 'regex:/^\d{1,9}(\.\d{1,4})?$/'],
        ], [
            'opname_date.required' => 'Tanggal opname wajib diisi.',
            'opname_date.date_format' => 'Format tanggal opname tidak valid.',
            'opname_date.before_or_equal' => 'Tanggal opname tidak boleh melebihi hari ini.',
            'items.required' => 'Minimal satu barang harus dihitung.',
            'items.*.product_id.required' => 'Produk pada baris hitung tidak valid.',
            'items.*.product_id.distinct' => 'Produk yang sama tidak boleh muncul lebih dari satu kali.',
            'items.*.product_id.exists' => 'Produk yang dipilih tidak aktif atau tidak ditemukan.',
            'items.*.counted_qty.regex' => 'Kuantitas fisik harus berupa angka 0 atau lebih dengan maksimal 4 desimal.',
        ]);

        $items = collect($validated['items'])
            ->map(static fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'counted_qty' => trim((string) ($item['counted_qty'] ?? '')),
            ])
            ->filter(static fn (array $item): bool => $item['counted_qty'] !== '')
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Isi minimal satu kuantitas fisik barang yang dihitung.',
            ]);
        }

        try {
            $opname = $action->execute(
                items: $items->all(),
                opnameDate: $validated['opname_date'],
                notes: $validated['notes'] ?? null,
                createdByUserId: (string) auth()->id(),
            );
        } catch (\DomainException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('inventory.opname.show', $opname)
            ->with('success', 'Draf stok opname ' . $opname->opname_number . ' berhasil disimpan. Stok belum berubah sampai disetujui.');
    }

    /**
     * Detail sesi opname, item hitung, dan status persetujuannya.
     */
    public function show(StockOpname $opname): View
    {
        $opname->load([
            'items' => fn ($query) => $query->with(['product.unit'])->orderBy('id'),
            'createdBy',
            'approvedBy',
            'journalEntry',
        ]);

        $items = $opname->items->sortBy(
            static fn ($item): string => $item->product?->name ?? ''
        )->values();

        return view('inventory.opname.show', compact('opname', 'items'));
    }

    /**
     * Setujui draf dan posting penyesuaian stok + jurnal.
     */
    public function approve(
        StockOpname $opname,
        ApproveStockOpnameAction $action,
    ): RedirectResponse {
        try {
            $approved = $action->execute($opname, (string) auth()->id());
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $journalNote = $approved->journal_entry_id
            ? ' Penyesuaian telah dijurnal otomatis.'
            : ' Tidak ada selisih nilai sehingga jurnal tidak diperlukan.';

        return redirect()
            ->route('inventory.opname.show', $approved)
            ->with('success', 'Stok opname ' . $approved->opname_number . ' disetujui. Stok telah disesuaikan.' . $journalNote);
    }

    /**
     * Hapus draf opname. Sesi yang sudah disetujui bersifat permanen.
     */
    public function destroy(StockOpname $opname): RedirectResponse
    {
        $user = auth()->user();

        if (! $opname->isDraft()) {
            return back()->with('error', 'Sesi opname yang sudah disetujui tidak dapat dihapus.');
        }

        $isAdmin = $user?->hasAnyRole([UserRole::SUPERADMIN, UserRole::ADMIN]) ?? false;

        if (! $isAdmin && (int) $opname->created_by_user_id !== (int) $user?->id) {
            abort(403, 'Anda hanya dapat menghapus draf opname milik sendiri.');
        }

        $number = $opname->opname_number;
        $opname->delete();

        return redirect()
            ->route('inventory.opname')
            ->with('success', 'Draf stok opname ' . $number . ' berhasil dihapus.');
    }
}
