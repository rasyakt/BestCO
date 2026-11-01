<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Inventory\RecordBatchPurchaseInboundAction;
use App\Enums\JournalType;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Rules\StrictCurrency;
use App\Rules\StrictQuantity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class StockInboundController extends Controller
{
    /**
     * Tampilkan halaman formulir Barang Masuk (batch input) dan riwayat pengadaan.
     */
    public function index(): View
    {
        $products = Product::with(['unit', 'category'])
            ->where('is_active', true)
            ->orderBy('inventory_type')
            ->orderBy('name')
            ->get();

        // Akun pendanaan: Kas Besar Brankas (1-1102) atau Utang Usaha (2-1101)
        $fundingAccounts = ChartOfAccount::whereIn('account_code', ['1-1102', '2-1101'])->get();

        // Riwayat transaksi pengadaan barang masuk
        $recentPurchases = JournalEntry::with(['items.chartOfAccount', 'createdBy'])
            ->where('journal_type', JournalType::PURCHASE)
            ->latest('id')
            ->paginate(15);

        return view('inventory.inbound', compact('products', 'fundingAccounts', 'recentPurchases'));
    }

    /**
     * Simpan transaksi barang masuk multi-produk sekaligus.
     */
    public function store(Request $request, RecordBatchPurchaseInboundAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->whereIn('account_code', ['1-1102', '2-1101']),
            ],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'distinct',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'items.*.qty' => ['required', new StrictQuantity(allowDecimal: true, min: 0.0001)],
            'items.*.price' => ['required', new StrictCurrency(min: 0.0001)],
        ], [
            'account_id.required' => 'Pilih akun sumber dana (Kas Besar atau Utang Usaha).',
            'account_id.exists' => 'Akun pendanaan tidak valid. Gunakan Kas Besar atau Utang Usaha.',
            'items.required' => 'Minimal satu produk harus ditambahkan ke daftar barang masuk.',
            'items.min' => 'Minimal satu produk harus ditambahkan ke daftar barang masuk.',
            'items.*.product_id.required' => 'Pilih produk untuk setiap baris.',
            'items.*.product_id.distinct' => 'Produk yang sama tidak boleh muncul lebih dari satu baris.',
            'items.*.product_id.exists' => 'Produk yang dipilih tidak aktif atau tidak ditemukan.',
            'items.*.qty.required' => 'Kuantitas barang wajib diisi.',
            'items.*.price.required' => 'Harga beli satuan wajib diisi.',
        ]);

        $result = $action->execute(
            items: $validated['items'],
            accountId: (string) $validated['account_id'],
            createdByUserId: (string) auth()->id(),
            invoiceNumber: $validated['invoice_number'] ?? null,
            notes: $validated['notes'] ?? null,
        );

        $formattedTotal = number_format((float) $result['total_amount'], 0, ',', '.');
        $itemCount = $result['items_count'];

        return redirect()->route('inventory.inbound')->with(
            'success',
            "Barang Masuk berhasil disimpan! {$itemCount} produk telah ditambahkan dengan total Rp {$formattedTotal}."
        );
    }
}

