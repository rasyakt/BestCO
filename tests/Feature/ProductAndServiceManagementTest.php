<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InventoryType;
use App\Enums\JournalType;
use App\Enums\ServiceGroup;
use App\Models\AccountingPeriod;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceBom;
use App\Models\StockLedgerEntry;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAndServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $teacher;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superadmin = User::where('role_type', 'superadmin')->firstOrFail();
        $this->admin = User::where('role_type', 'admin')->firstOrFail();
        $this->teacher = User::where('role_type', 'piket_guru')->firstOrFail();
        $this->student = User::where('role_type', 'piket_siswa')->firstOrFail();

        AccountingPeriod::firstOrCreate(
            ['period_name' => now()->format('F Y')],
            [
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
                'is_closed' => false,
            ]
        );
    }

    public function test_admin_can_view_product_and_service_tabs(): void
    {
        $responseProducts = $this->actingAs($this->admin)->get(route('inventory.index'));
        $responseProducts->assertStatus(200);
        $responseProducts->assertSee('Daftar stok');
        $responseProducts->assertSee('Tambah Produk');
        $responseProducts->assertSee('Tambah Layanan');

        $responseServices = $this->actingAs($this->admin)->get(route('inventory.index', ['type' => 'SERVICE']));
        $responseServices->assertStatus(200);
        $responseServices->assertSee('Katalog Layanan Jasa');
        $responseServices->assertSee('Fotocopy Hitam Putih A4');
    }

    public function test_admin_can_create_new_retail_product_with_hpp_and_selling_price(): void
    {
        $category = Category::where('category_scope', 'RETAIL')->firstOrFail();
        $unit = Unit::where('code', 'PCS')->firstOrFail();

        $payload = [
            'name' => 'Penghapus Joyko B40 Hitam',
            'sku' => 'ATK-PNG-B40',
            'barcode' => '8991234567890',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'inventory_type' => 'RETAIL',
            'average_cost' => 3000,
            'selling_price' => 5000,
            'min_stock_alert' => 10,
            'initial_stock' => 20,
        ];

        $response = $this->actingAs($this->admin)->post(route('inventory.products.store'), $payload);

        $response->assertRedirect(route('inventory.index', ['type' => 'RETAIL']));
        $response->assertSessionHas('success');

        $product = Product::where('sku', 'ATK-PNG-B40')->firstOrFail();
        $this->assertEquals('Penghapus Joyko B40 Hitam', $product->name);
        $this->assertEquals(InventoryType::RETAIL, $product->inventory_type);
        $this->assertEquals(3000, (float) $product->average_cost);
        $this->assertEquals(5000, (float) $product->selling_price);
        $this->assertEquals(20, (float) $product->current_stock);
        $this->assertEquals(10, (float) $product->min_stock_alert);

        // Verifikasi akun akuntansi persediaan dan HPP otomatis terpetakan
        $invCoa = ChartOfAccount::where('account_code', '1-1301')->firstOrFail();
        $cogsCoa = ChartOfAccount::where('account_code', '5-1101')->firstOrFail();
        $this->assertEquals($invCoa->id, $product->inventory_account_id);
        $this->assertEquals($cogsCoa->id, $product->cogs_account_id);

        // Verifikasi kartu stok awal
        $ledger = StockLedgerEntry::where('product_id', $product->id)->firstOrFail();
        $this->assertEquals('INITIAL_STOCK', $ledger->reference_type);
        $this->assertEquals(20, (float) $ledger->qty_change);
        $this->assertEquals(3000, (float) $ledger->unit_cost);
        $this->assertEquals(60000, (float) $ledger->total_value);

        // Verifikasi Jurnal Akuntansi Saldo Awal Seimbang
        $journal = JournalEntry::where('id', $ledger->reference_id)->firstOrFail();
        $this->assertEquals(60000, (float) $journal->total_debit);
        $this->assertEquals(60000, (float) $journal->total_credit);
        $this->assertTrue($journal->is_posted);
    }

    public function test_admin_can_create_new_raw_material_product(): void
    {
        $category = Category::where('category_scope', 'RAW_MATERIAL')->firstOrFail();
        $unit = Unit::where('code', 'LBR')->firstOrFail();

        $payload = [
            'name' => 'Kertas Buffalo Hijau [Lembar]',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'inventory_type' => 'RAW_MATERIAL',
            'average_cost' => 500,
            'selling_price' => 0,
            'min_stock_alert' => 20,
            'initial_stock' => 0,
        ];

        $response = $this->actingAs($this->admin)->post(route('inventory.products.store'), $payload);

        $response->assertRedirect(route('inventory.index', ['type' => 'RAW_MATERIAL']));
        $response->assertSessionHas('success');

        $product = Product::where('name', 'Kertas Buffalo Hijau [Lembar]')->firstOrFail();
        $this->assertEquals(InventoryType::RAW_MATERIAL, $product->inventory_type);
        $this->assertEquals(500, (float) $product->average_cost);
        $this->assertEquals(0, (float) $product->selling_price);

        // Verifikasi akun bahan baku jasa (1-1302 & 5-2101)
        $invCoa = ChartOfAccount::where('account_code', '1-1302')->firstOrFail();
        $cogsCoa = ChartOfAccount::where('account_code', '5-2101')->firstOrFail();
        $this->assertEquals($invCoa->id, $product->inventory_account_id);
        $this->assertEquals($cogsCoa->id, $product->cogs_account_id);
    }

    public function test_admin_can_create_new_service_with_bom(): void
    {
        $rawPaper = Product::where('sku', 'RAW-HVS-A4-LBR')->firstOrFail();

        $payload = [
            'name' => 'Jilid Mika Lakban A4',
            'code' => 'SRV-JLD-MIKA-A4',
            'service_group' => 'BINDING',
            'base_unit_name' => 'Buku',
            'base_price' => 7500,
            'boms' => [
                [
                    'raw_material_product_id' => $rawPaper->id,
                    'consumption_qty' => 2,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('inventory.services.store'), $payload);

        $response->assertRedirect(route('inventory.index', ['type' => 'SERVICE']));
        $response->assertSessionHas('success');

        $service = Service::with('boms')->where('code', 'SRV-JLD-MIKA-A4')->firstOrFail();
        $this->assertEquals('Jilid Mika Lakban A4', $service->name);
        $this->assertEquals(ServiceGroup::BINDING, $service->service_group);
        $this->assertEquals(7500, (float) $service->base_price);
        $this->assertEquals('Buku', $service->base_unit_name);

        // Verifikasi BoM terhubung
        $this->assertCount(1, $service->boms);
        $bom = $service->boms->first();
        $this->assertEquals($rawPaper->id, $bom->raw_material_product_id);
        $this->assertEquals(2, (float) $bom->consumption_qty);

        // Verifikasi akun pendapatan jasa laminating & jilid (4-2103)
        $revCoa = ChartOfAccount::where('account_code', '4-2103')->firstOrFail();
        $this->assertEquals($revCoa->id, $service->revenue_account_id);
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::firstOrFail();

        $payload = [
            'name' => $product->name . ' (Updated)',
            'selling_price' => 65000,
            'average_cost' => 48000,
            'min_stock_alert' => 8,
            'barcode' => '998877665544',
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->admin)->put(route('inventory.products.update', $product->id), $payload);

        $response->assertRedirect(route('inventory.index'));
        $response->assertSessionHas('success');

        $product->refresh();
        $this->assertStringContainsString('(Updated)', $product->name);
        $this->assertEquals(65000, (float) $product->selling_price);
        $this->assertEquals(48000, (float) $product->average_cost);
        $this->assertEquals(8, (float) $product->min_stock_alert);
    }

    public function test_admin_can_update_service(): void
    {
        $service = Service::firstOrFail();
        $rawPaperF4 = Product::where('sku', 'RAW-HVS-F4-LBR')->firstOrFail();

        $payload = [
            'name' => $service->name . ' (Tarif Baru)',
            'base_price' => 450,
            'base_unit_name' => 'Lembar',
            'is_active' => 1,
            'boms' => [
                [
                    'raw_material_product_id' => $rawPaperF4->id,
                    'consumption_qty' => 1,
                ]
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('inventory.services.update', $service->id), $payload);

        $response->assertRedirect(route('inventory.index', ['type' => 'SERVICE']));
        $response->assertSessionHas('success');

        $service->refresh();
        $this->assertStringContainsString('(Tarif Baru)', $service->name);
        $this->assertEquals(450, (float) $service->base_price);
        $this->assertEquals($rawPaperF4->id, $service->boms->first()->raw_material_product_id);
    }

    public function test_student_cannot_create_or_update_products_and_services(): void
    {
        $category = Category::firstOrFail();
        $unit = Unit::firstOrFail();

        $responsePostProduct = $this->actingAs($this->student)->post(route('inventory.products.store'), [
            'name' => 'Barang Ilegal',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'inventory_type' => 'RETAIL',
            'average_cost' => 1000,
            'selling_price' => 2000,
        ]);
        $responsePostProduct->assertStatus(403);

        $responsePostService = $this->actingAs($this->student)->post(route('inventory.services.store'), [
            'name' => 'Jasa Ilegal',
            'service_group' => 'FOTOCOPY',
            'base_unit_name' => 'Lembar',
            'base_price' => 500,
        ]);
        $responsePostService->assertStatus(403);
    }
}
