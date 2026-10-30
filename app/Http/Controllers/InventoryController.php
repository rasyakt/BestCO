<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Inventory\RecordPurchaseRestockAction;
use App\Models\ChartOfAccount;
use App\Models\Product;
use App\Models\StockLedgerEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type', 'ALL');
        $search = $request->query('q', '');

        $query = Product::with(['unit', 'category'])->where('is_active', true);

        if ($type !== 'ALL') {
            $query->where('inventory_type', $type);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('inventory_type')->orderBy('name')->get();

        // Akun pendanaan pembelian: 1-1102 Kas Besar Brankas atau 2-1101 Utang Usaha
        $fundingAccounts = ChartOfAccount::whereIn('account_code', ['1-1102', '2-1101'])->get();

        return view('inventory.index', compact('products', 'type', 'search', 'fundingAccounts'));
    }

    public function restock(Request $request, RecordPurchaseRestockAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'qty_purchased' => ['required', 'numeric', 'min:0.0001'],
            'purchase_unit_price' => ['required', 'numeric', 'min:0'],
            'account_id' => ['required', 'exists:chart_of_accounts,id'],
        ]);

        $qty = number_format((float) $validated['qty_purchased'], 4, '.', '');
        $price = number_format((float) $validated['purchase_unit_price'], 4, '.', '');
        $total = bcmul($qty, $price, 4);

        $result = $action->execute(
            productId: (string) $validated['product_id'],
            qtyPurchased: $qty,
            purchaseUnitPrice: $price,
            totalInvoiceAmount: $total,
            accountId: (string) $validated['account_id'],
            createdByUserId: (string) auth()->id(),
        );

        $prod = $result['product'];

        return redirect()->route('inventory.index')->with(
            'success',
            "Restock {$prod->name} sebanyak {$qty} {$prod->unit?->code} berhasil! HPP baru: Rp " . number_format((float) $result['new_average_cost'], 0, ',', '.')
        );
    }

    public function ledger(int $id): View
    {
        $product = Product::with('unit')->findOrFail($id);

        $movements = StockLedgerEntry::with(['createdByUser', 'posShift', 'posTransaction'])
            ->where('product_id', $product->id)
            ->latest('id')
            ->paginate(25);

        return view('inventory.ledger', compact('product', 'movements'));
    }
}
