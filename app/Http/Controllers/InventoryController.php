<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Inventory\CreateProductAction;
use App\Actions\Inventory\CreateServiceAction;
use App\Actions\Inventory\RecordPurchaseRestockAction;
use App\Actions\Inventory\UpdateProductAction;
use App\Actions\Inventory\UpdateServiceAction;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Product;
use App\Models\Service;
use App\Models\StockLedgerEntry;
use App\Models\Unit;
use App\Rules\StrictCurrency;
use App\Rules\StrictNumeric;
use App\Rules\StrictQuantity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type', 'ALL');
        $search = $request->query('q', '');

        $query = Product::with(['unit', 'category'])->where('is_active', true);

        if ($type !== 'ALL' && $type !== 'SERVICE') {
            $query->where('inventory_type', $type);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $products = ($type !== 'SERVICE')
            ? $query->orderBy('inventory_type')->orderBy('name')->get()
            : collect();

        // Query daftar layanan jasa
        $servicesQuery = Service::with(['category', 'boms.rawMaterialProduct.unit', 'priceTiers'])
            ->where('is_active', true);

        if ($search !== '') {
            $servicesQuery->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $services = ($type === 'ALL' || $type === 'SERVICE')
            ? $servicesQuery->orderBy('service_group')->orderBy('name')->get()
            : collect();

        // Data master untuk modal Tambah & Edit Produk/Layanan
        $categories = Category::all();
        $units = Unit::all();
        $rawMaterials = Product::where('inventory_type', 'RAW_MATERIAL')
            ->where('is_active', true)
            ->with('unit')
            ->orderBy('name')
            ->get();

        // Akun pendanaan: Kas Besar Brankas (1-1102), Utang Usaha (2-1101), Modal Awal (3-1101)
        $fundingAccounts = ChartOfAccount::whereIn('account_code', ['1-1102', '2-1101', '3-1101'])->get();

        return view('inventory.index', compact(
            'products',
            'services',
            'type',
            'search',
            'fundingAccounts',
            'categories',
            'units',
            'rawMaterials'
        ));
    }

    /**
     * Daftarkan produk master baru (barang etalase atau bahan baku).
     */
    public function storeProduct(Request $request, CreateProductAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:64', 'unique:products,barcode'],
            'category_id' => ['required', 'exists:categories,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'inventory_type' => ['required', 'in:RETAIL,RAW_MATERIAL'],
            'average_cost' => ['required', new StrictCurrency(min: 0)],
            'selling_price' => [
                Rule::requiredIf($request->input('inventory_type') === 'RETAIL'),
                'nullable',
                new StrictCurrency(min: 0),
            ],
            'min_stock_alert' => ['nullable', new StrictNumeric(allowDecimal: true, min: 0)],
            'initial_stock' => ['nullable', new StrictNumeric(allowDecimal: true, min: 0)],
            'funding_account_id' => [
                'nullable',
                Rule::exists('chart_of_accounts', 'id')
                    ->whereIn('account_code', ['1-1102', '2-1101', '3-1101']),
            ],
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'average_cost.required' => 'HPP awal produk wajib diisi.',
            'selling_price.required' => 'Harga jual ritel wajib diisi untuk barang etalase.',
            'category_id.required' => 'Pilih kategori produk.',
            'unit_id.required' => 'Pilih satuan produk.',
            'funding_account_id.exists' => 'Akun sumber dana tidak valid. Gunakan Kas Besar, Utang Usaha, atau Modal Awal.',
        ]);

        $product = $action->execute(array_merge($validated, [
            'user_id' => (string) auth()->id(),
        ]));

        $typeLabel = $product->inventory_type->value === 'RETAIL' ? 'Barang etalase' : 'Bahan baku';
        return redirect()->route('inventory.index', ['type' => $product->inventory_type->value])->with(
            'success',
            "Produk baru '{$product->name}' ({$typeLabel}) berhasil ditambahkan! SKU: {$product->sku}"
        );
    }

    /**
     * Perbarui data master produk (harga jual, HPP, min alert, barcode, status aktif).
     */
    public function updateProduct(Request $request, int $id, UpdateProductAction $action): RedirectResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'unit_id' => ['sometimes', 'exists:units,id'],
            'selling_price' => ['required', new StrictCurrency(min: 0)],
            'average_cost' => ['required', new StrictCurrency(min: 0)],
            'min_stock_alert' => ['required', new StrictNumeric(allowDecimal: true, min: 0)],
            'barcode' => ['nullable', 'string', 'max:64', "unique:products,barcode,{$product->id}"],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $action->execute($product, $validated);

        return redirect()->route('inventory.index')->with(
            'success',
            "Data produk '{$product->name}' berhasil diperbarui!"
        );
    }

    /**
     * Daftarkan layanan jasa master baru (fotocopy, print, laminating, jilid, dll).
     */
    public function storeService(Request $request, CreateServiceAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:40', 'unique:services,code'],
            'service_group' => ['required', 'in:FOTOCOPY,PRINT,LAMINATING,BINDING,SCAN,OTHER'],
            'base_unit_name' => ['required', 'string', 'max:30'],
            'base_price' => ['required', new StrictCurrency(min: 0)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'boms' => ['nullable', 'array'],
            'boms.*.raw_material_product_id' => [
                'required_with:boms',
                'distinct',
                Rule::exists('products', 'id')
                    ->where('inventory_type', 'RAW_MATERIAL')
                    ->where('is_active', true),
            ],
            'boms.*.consumption_qty' => ['required_with:boms', new StrictQuantity(allowDecimal: true, min: 0.0001)],
            'price_tiers' => ['nullable', 'array'],
            'price_tiers.*.tier_name' => ['nullable', 'string', 'max:100'],
            'price_tiers.*.min_qty' => ['required_with:price_tiers', new StrictQuantity(allowDecimal: true, min: 1)],
            'price_tiers.*.discount_type' => ['nullable', 'in:PERCENTAGE,FIXED_DISCOUNT_PER_UNIT,SPECIAL_UNIT_PRICE'],
            'price_tiers.*.discount_value' => ['required_with:price_tiers', new StrictCurrency(min: 0)],
        ], [
            'name.required' => 'Nama layanan wajib diisi.',
            'base_price.required' => 'Harga jual / tarif layanan wajib diisi.',
            'service_group.required' => 'Pilih kelompok jasa.',
            'base_unit_name.required' => 'Satuan basis layanan wajib diisi.',
            'boms.*.raw_material_product_id.exists' => 'Bahan baku yang dipilih harus produk bahan baku yang aktif.',
        ]);

        $service = $action->execute($validated);

        return redirect()->route('inventory.index', ['type' => 'SERVICE'])->with(
            'success',
            "Layanan baru '{$service->name}' ({$service->service_group->label()}) berhasil ditambahkan! Tarif: Rp " . number_format((float) $service->base_price, 0, ',', '.')
        );
    }

    /**
     * Perbarui data master layanan (nama, tarif harga jual, satuan basis, status aktif).
     */
    public function updateService(Request $request, int $id, UpdateServiceAction $action): RedirectResponse
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'base_price' => ['required', new StrictCurrency(min: 0)],
            'base_unit_name' => ['required', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
            'boms' => ['nullable', 'array'],
            'boms.*.raw_material_product_id' => [
                'required_with:boms',
                'distinct',
                Rule::exists('products', 'id')
                    ->where('inventory_type', 'RAW_MATERIAL')
                    ->where('is_active', true),
            ],
            'boms.*.consumption_qty' => ['required_with:boms', new StrictQuantity(allowDecimal: true, min: 0.0001)],
        ], [
            'boms.*.raw_material_product_id.exists' => 'Bahan baku yang dipilih harus produk bahan baku yang aktif.',
        ]);

        $action->execute($service, $validated);

        return redirect()->route('inventory.index', ['type' => 'SERVICE'])->with(
            'success',
            "Data layanan '{$service->name}' berhasil diperbarui! Tarif baru: Rp " . number_format((float) $service->base_price, 0, ',', '.')
        );
    }

    public function restock(Request $request, RecordPurchaseRestockAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'qty_purchased' => ['required', new StrictQuantity(allowDecimal: true, min: 0.0001)],
            'purchase_unit_price' => ['required', new StrictCurrency(min: 0.0001)],
            'account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->whereIn('account_code', ['1-1102', '2-1101']),
            ],
        ], [
            'account_id.exists' => 'Akun pendanaan tidak valid. Gunakan Kas Besar atau Utang Usaha.',
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

        $movements = StockLedgerEntry::with(['createdBy', 'posShift'])
            ->where('product_id', $product->id)
            ->latest('id')
            ->paginate(25);

        return view('inventory.ledger', compact('product', 'movements'));
    }
}
