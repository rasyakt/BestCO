<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PromotionDiscountType;
use App\Enums\PromotionScope;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class PromotionController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAuthorized();

        $tab = $request->query('tab', 'all');
        $search = trim((string) $request->query('q', ''));
        $now = Carbon::now();

        $query = Promotion::with(['category', 'product', 'service', 'creator'])->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Tab filter
        match ($tab) {
            'active' => $query->where('is_active', true)
                ->where('start_datetime', '<=', $now)
                ->where('end_datetime', '>=', $now),
            'scheduled' => $query->where('is_active', true)
                ->where('start_datetime', '>', $now),
            'expired' => $query->where('is_active', true)
                ->where('end_datetime', '<', $now),
            'inactive' => $query->where('is_active', false),
            default => null,
        };

        $promotions = $query->paginate(12)->withQueryString();

        // Hitung statistik untuk badge tab (mutually exclusive)
        $counts = [
            'all' => Promotion::count(),
            'active' => Promotion::where('is_active', true)
                ->where('start_datetime', '<=', $now)
                ->where('end_datetime', '>=', $now)
                ->count(),
            'scheduled' => Promotion::where('is_active', true)
                ->where('start_datetime', '>', $now)
                ->count(),
            'expired' => Promotion::where('is_active', true)
                ->where('end_datetime', '<', $now)
                ->count(),
            'inactive' => Promotion::where('is_active', false)->count(),
        ];

        return view('promotions.index', compact('promotions', 'tab', 'search', 'counts'));
    }

    public function create(): View
    {
        $this->ensureAuthorized();

        $categories = Category::orderBy('name')->get();
        $products = Product::where('is_active', true)
            ->where('inventory_type', 'RETAIL')
            ->orderBy('name')
            ->get();
        $services = Service::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('promotions.create', compact('categories', 'products', 'services'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAuthorized();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30', 'alpha_dash', 'unique:promotions,code'],
            'description' => ['nullable', 'string', 'max:500'],
            'scope' => ['required', Rule::enum(PromotionScope::class)],
            'category_id' => ['nullable', 'required_if:scope,CATEGORY', 'exists:categories,id'],
            'product_id' => ['nullable', 'required_if:scope,PRODUCT', 'exists:products,id'],
            'service_id' => ['nullable', 'required_if:scope,SERVICE', 'exists:services,id'],
            'discount_type' => ['required', Rule::enum(PromotionDiscountType::class)],
            'discount_value' => ['required', 'numeric', 'gt:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0', 'required_if:scope,MIN_SPEND', 'gt:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validated['discount_type'] === PromotionDiscountType::PERCENTAGE->value && (float) $validated['discount_value'] > 100) {
            return back()->withInput()->withErrors(['discount_value' => 'Persentase diskon tidak boleh melebihi 100%.']);
        }

        Promotion::create([
            'name' => $validated['name'],
            'code' => ! empty($validated['code']) ? strtoupper((string) $validated['code']) : null,
            'description' => $validated['description'] ?? null,
            'scope' => $validated['scope'],
            'category_id' => $validated['scope'] === PromotionScope::CATEGORY->value ? $validated['category_id'] : null,
            'product_id' => $validated['scope'] === PromotionScope::PRODUCT->value ? $validated['product_id'] : null,
            'service_id' => $validated['scope'] === PromotionScope::SERVICE->value ? $validated['service_id'] : null,
            'discount_type' => $validated['discount_type'],
            'discount_value' => number_format((float) $validated['discount_value'], 4, '.', ''),
            'min_order_amount' => number_format((float) ($validated['min_order_amount'] ?? 0), 4, '.', ''),
            'max_discount_amount' => ! empty($validated['max_discount_amount'])
                ? number_format((float) $validated['max_discount_amount'], 4, '.', '')
                : null,
            'start_datetime' => Carbon::parse($validated['start_datetime']),
            'end_datetime' => Carbon::parse($validated['end_datetime']),
            'is_active' => $request->boolean('is_active', true),
            'created_by_user_id' => auth()->id(),
        ]);

        return redirect()->route('promotions.index')
            ->with('success', 'Promo berhasil dibuat dan dijadwalkan.');
    }

    public function edit(Promotion $promotion): View
    {
        $this->ensureAuthorized();

        $categories = Category::orderBy('name')->get();
        $products = Product::where('is_active', true)
            ->where('inventory_type', 'RETAIL')
            ->orderBy('name')
            ->get();
        $services = Service::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('promotions.edit', compact('promotion', 'categories', 'products', 'services'));
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $this->ensureAuthorized();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30', 'alpha_dash', Rule::unique('promotions', 'code')->ignore($promotion->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'scope' => ['required', Rule::enum(PromotionScope::class)],
            'category_id' => ['nullable', 'required_if:scope,CATEGORY', 'exists:categories,id'],
            'product_id' => ['nullable', 'required_if:scope,PRODUCT', 'exists:products,id'],
            'service_id' => ['nullable', 'required_if:scope,SERVICE', 'exists:services,id'],
            'discount_type' => ['required', Rule::enum(PromotionDiscountType::class)],
            'discount_value' => ['required', 'numeric', 'gt:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0', 'required_if:scope,MIN_SPEND', 'gt:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validated['discount_type'] === PromotionDiscountType::PERCENTAGE->value && (float) $validated['discount_value'] > 100) {
            return back()->withInput()->withErrors(['discount_value' => 'Persentase diskon tidak boleh melebihi 100%.']);
        }

        $promotion->update([
            'name' => $validated['name'],
            'code' => ! empty($validated['code']) ? strtoupper((string) $validated['code']) : null,
            'description' => $validated['description'] ?? null,
            'scope' => $validated['scope'],
            'category_id' => $validated['scope'] === PromotionScope::CATEGORY->value ? $validated['category_id'] : null,
            'product_id' => $validated['scope'] === PromotionScope::PRODUCT->value ? $validated['product_id'] : null,
            'service_id' => $validated['scope'] === PromotionScope::SERVICE->value ? $validated['service_id'] : null,
            'discount_type' => $validated['discount_type'],
            'discount_value' => number_format((float) $validated['discount_value'], 4, '.', ''),
            'min_order_amount' => number_format((float) ($validated['min_order_amount'] ?? 0), 4, '.', ''),
            'max_discount_amount' => ! empty($validated['max_discount_amount'])
                ? number_format((float) $validated['max_discount_amount'], 4, '.', '')
                : null,
            'start_datetime' => Carbon::parse($validated['start_datetime']),
            'end_datetime' => Carbon::parse($validated['end_datetime']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('promotions.index')
            ->with('success', "Promo '{$promotion->name}' berhasil diperbarui.");
    }

    public function toggle(Promotion $promotion): RedirectResponse
    {
        $this->ensureAuthorized();

        $promotion->update([
            'is_active' => ! $promotion->is_active,
        ]);

        $statusLabel = $promotion->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Promo '{$promotion->name}' berhasil {$statusLabel}.");
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $this->ensureAuthorized();

        // Hanya superadmin dan admin yang boleh menghapus promo
        abort_unless(
            auth()->user()?->hasAnyRole(['superadmin', 'admin']),
            403,
            'Hanya Administrator yang boleh menghapus data promo.'
        );

        $name = $promotion->name;
        $promotion->delete();

        return redirect()->route('promotions.index')
            ->with('success', "Promo '{$name}' telah dihapus.");
    }

    private function ensureAuthorized(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['superadmin', 'admin', 'piket_guru']),
            403,
            'Akses terbatas untuk Pengelola Promo & Diskon.'
        );
    }
}

