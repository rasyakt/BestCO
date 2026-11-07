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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        $productQuery = $query->orderBy('inventory_type')->orderBy('name');

        // Daftar lengkap tetap dipakai modal Tambah Stok; tabel memakai versi terpaginasi.
        $products = ($type !== 'SERVICE')
            ? (clone $productQuery)->get()
            : collect();

        $productPage = ($type !== 'SERVICE')
            ? (clone $productQuery)->paginate(10)->withQueryString()
            : null;

        // Query daftar layanan jasa
        $servicesQuery = Service::with(['category', 'boms.rawMaterialProduct.unit', 'priceTiers'])
            ->where('is_active', true);

        if ($search !== '') {
            $servicesQuery->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $services = ($type === 'SERVICE')
            ? $servicesQuery->orderBy('service_group')->orderBy('name')->paginate(10)->withQueryString()
            : null;

        // Data master untuk modal Tambah & Edit Produk/Layanan serta Kelola Master
        $categories = Category::withCount(['products', 'services'])->orderBy('name')->get();
        $units = Unit::withCount(['products', 'unitConversionsAsSource', 'unitConversionsAsTarget'])->orderBy('code')->get();
        $rawMaterials = Product::where('inventory_type', 'RAW_MATERIAL')
            ->where('is_active', true)
            ->with('unit')
            ->orderBy('name')
            ->get();

        // Akun pendanaan: Kas di Tangan (1-1102), Kas di Bank (1-1103), Utang Usaha (2-1101), Modal Awal (3-1101)
        $fundingAccounts = ChartOfAccount::whereIn('account_code', ['1-1102', '1-1103', '2-1101', '3-1101'])->get();

        return view('inventory.index', compact(
            'products',
            'productPage',
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
            "Layanan baru '{$service->name}' ({$service->service_group->label()}) berhasil ditambahkan! Tarif: Rp ".number_format((float) $service->base_price, 0, ',', '.')
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
            "Data layanan '{$service->name}' berhasil diperbarui! Tarif baru: Rp ".number_format((float) $service->base_price, 0, ',', '.')
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
                Rule::exists('chart_of_accounts', 'id')->whereIn('account_code', ['1-1102', '1-1103', '2-1101']),
            ],
        ], [
            'account_id.exists' => 'Akun pendanaan tidak valid. Gunakan Kas di Tangan, Kas di Bank, atau Utang Usaha.',
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
            "Restock {$prod->name} sebanyak {$qty} {$prod->unit?->code} berhasil! HPP baru: Rp ".number_format((float) $result['new_average_cost'], 0, ',', '.')
        );
    }

    public function ledger(int $id): View
    {
        $product = Product::with('unit')->findOrFail($id);

        $movements = StockLedgerEntry::with(['createdBy', 'posShift'])
            ->where('product_id', $product->id)
            ->latest('id')
            ->paginate(10);

        return view('inventory.ledger', compact('product', 'movements'));
    }

    /**
     * Tambahkan kategori baru secara dinamis.
     */
    public function storeCategory(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category_scope' => ['required', 'in:RETAIL,RAW_MATERIAL,SERVICE'],
        ]);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug !== '' ? $baseSlug : 'kategori';
        $originalSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $category = Category::create([
            'name' => trim($validated['name']),
            'slug' => $slug,
            'category_scope' => $validated['category_scope'],
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Kategori '{$category->name}' berhasil ditambahkan.",
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'category_scope' => $category->category_scope,
                    'products_count' => 0,
                    'services_count' => 0,
                ],
            ], 201);
        }

        return redirect()->back()->with('success', "Kategori '{$category->name}' berhasil ditambahkan.");
    }

    /**
     * Hapus kategori jika belum pernah digunakan.
     */
    public function destroyCategory(int $id, Request $request): JsonResponse|RedirectResponse
    {
        $category = Category::withCount(['products', 'services'])->findOrFail($id);

        if ($category->products_count > 0 || $category->services_count > 0) {
            $message = "Kategori '{$category->name}' tidak dapat dihapus karena masih digunakan oleh produk atau layanan.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $name = $category->name;
        $category->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Kategori '{$name}' berhasil dihapus.",
            ]);
        }

        return redirect()->back()->with('success', "Kategori '{$name}' berhasil dihapus.");
    }

    /**
     * Tambahkan satuan unit baru secara dinamis.
     */
    public function storeUnit(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:15', 'unique:units,code'],
            'name' => ['required', 'string', 'max:50'],
            'allow_decimal' => ['nullable', 'boolean'],
        ]);

        $unit = Unit::create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'allow_decimal' => $request->boolean('allow_decimal'),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Satuan '{$unit->code} ({$unit->name})' berhasil ditambahkan.",
                'unit' => [
                    'id' => $unit->id,
                    'code' => $unit->code,
                    'name' => $unit->name,
                    'allow_decimal' => (bool) $unit->allow_decimal,
                    'products_count' => 0,
                ],
            ], 201);
        }

        return redirect()->back()->with('success', "Satuan '{$unit->code}' berhasil ditambahkan.");
    }

    /**
     * Hapus satuan jika belum pernah digunakan.
     */
    public function destroyUnit(int $id, Request $request): JsonResponse|RedirectResponse
    {
        $unit = Unit::withCount(['products', 'unitConversionsAsSource', 'unitConversionsAsTarget'])->findOrFail($id);

        $inUse = $unit->products_count > 0
            || $unit->unit_conversions_as_source_count > 0
            || $unit->unit_conversions_as_target_count > 0;

        if ($inUse) {
            $message = "Satuan '{$unit->code}' tidak dapat dihapus karena sedang digunakan dalam produk atau konversi satuan.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $code = $unit->code;
        $unit->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Satuan '{$code}' berhasil dihapus.",
            ]);
        }

        return redirect()->back()->with('success', "Satuan '{$code}' berhasil dihapus.");
    }
}
