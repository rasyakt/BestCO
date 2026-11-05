<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PosShift;
use App\Models\User;
use App\Services\Feature\FeatureManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DynamicFeatureManagementTest extends TestCase
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

        $this->superadmin = User::where('role_type', UserRole::SUPERADMIN->value)->firstOrFail();
        $this->admin = User::where('role_type', UserRole::ADMIN->value)->firstOrFail();
        $this->teacher = User::where('role_type', UserRole::PIKET_GURU->value)->firstOrFail();
        $this->student = User::where('role_type', UserRole::PIKET_SISWA->value)->firstOrFail();
    }

    public function test_superadmin_can_access_feature_settings_page(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('settings.features'));

        $response->assertOk();
        $response->assertSee('Pengaturan Fitur &amp; Hak Akses', false);
        $response->assertSee('Dashboard Eksekutif');
        $response->assertSee('Kasir / Terminal POS');
    }

    public function test_non_superadmin_cannot_access_feature_settings(): void
    {
        $this->actingAs($this->admin)->get(route('settings.features'))->assertForbidden();
        $this->actingAs($this->teacher)->get(route('settings.features'))->assertForbidden();
        $this->actingAs($this->student)->get(route('settings.features'))->assertForbidden();
    }

    public function test_superadmin_can_disable_feature_globally(): void
    {
        // 1. By default, teacher can access promotions
        $this->actingAs($this->teacher)->get(route('promotions.index'))->assertOk();

        // 2. Superadmin disables promotions globally
        $payload = [
            'features' => [
                'promotions' => [
                    'is_active' => '0',
                    'roles' => ['admin', 'piket_guru'],
                ],
            ],
        ];

        $response = $this->actingAs($this->superadmin)->put(route('settings.features.update'), $payload);
        $response->assertRedirect(route('settings.features'));

        // 3. Now teacher visiting promotions gets 403 Forbidden
        $this->actingAs($this->teacher)->get(route('promotions.index'))->assertForbidden();

        // 4. Superadmin still retains emergency/master access
        $this->actingAs($this->superadmin)->get(route('promotions.index'))->assertOk();
    }

    public function test_superadmin_can_dynamically_grant_feature_to_student(): void
    {
        // 1. By default, student cannot access reports
        $this->actingAs($this->student)->get(route('reports.index'))->assertForbidden();

        // 2. Superadmin grants reports access to piket_siswa
        $manager = app(FeatureManager::class);
        $allFeatures = $manager->getFeatures();

        $payloadFeatures = [];
        foreach ($allFeatures as $key => $feat) {
            $roles = $feat['roles'];
            if ($key === 'reports') {
                $roles[] = 'piket_siswa';
            }
            $payloadFeatures[$key] = [
                'is_active' => '1',
                'roles' => $roles,
            ];
        }

        $this->actingAs($this->superadmin)->put(route('settings.features.update'), [
            'features' => $payloadFeatures,
        ])->assertRedirect(route('settings.features'));

        // 3. Now student can access reports!
        $this->actingAs($this->student)->get(route('reports.index'))->assertOk();
    }

    public function test_superadmin_can_dynamically_revoke_feature_from_teacher(): void
    {
        // 1. By default, teacher can access inventory
        $this->actingAs($this->teacher)->get(route('inventory.index'))->assertOk();

        // 2. Superadmin revokes inventory from piket_guru (leaving only admin)
        $manager = app(FeatureManager::class);
        $allFeatures = $manager->getFeatures();

        $payloadFeatures = [];
        foreach ($allFeatures as $key => $feat) {
            $roles = $feat['roles'];
            if ($key === 'inventory') {
                $roles = ['admin']; // Guru piket dihapus
            }
            $payloadFeatures[$key] = [
                'is_active' => '1',
                'roles' => $roles,
            ];
        }

        $this->actingAs($this->superadmin)->put(route('settings.features.update'), [
            'features' => $payloadFeatures,
        ])->assertRedirect(route('settings.features'));

        // 3. Now teacher cannot access inventory
        $this->actingAs($this->teacher)->get(route('inventory.index'))->assertForbidden();

        // Admin can still access
        $this->actingAs($this->admin)->get(route('inventory.index'))->assertOk();
    }

    public function test_superadmin_can_reset_settings_to_default(): void
    {
        // 1. Modify settings by disabling reports
        $this->actingAs($this->superadmin)->put(route('settings.features.update'), [
            'features' => [
                'reports' => [
                    'is_active' => '0',
                    'roles' => [],
                ],
            ],
        ]);

        $this->actingAs($this->teacher)->get(route('reports.index'))->assertForbidden();

        // 2. Reset to default
        $this->actingAs($this->superadmin)->post(route('settings.features.reset'))
            ->assertRedirect(route('settings.features'));

        // 3. Teacher can access reports again
        $this->actingAs($this->teacher)->get(route('reports.index'))->assertOk();
    }

    public function test_superadmin_can_enable_all_features(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('settings.features.enableAll'));
        $response->assertRedirect(route('settings.features'));

        $manager = app(FeatureManager::class);
        $features = $manager->getFeatures();

        foreach ($features as $feat) {
            $this->assertTrue($feat['is_active']);
        }
    }

    public function test_superadmin_can_control_manual_discount_and_coupon_permissions(): void
    {
        // Buka shift kasir
        $shift = PosShift::create([
            'shift_code' => 'SHF-TEST-DISC-01',
            'shift_date' => now()->toDateString(),
            'supervisor_teacher_id' => $this->teacher->id,
            'opened_by_user_id' => $this->teacher->id,
            'opened_at' => now(),
            'opening_cash_balance' => '100000.0000',
            'system_expected_cash' => '100000.0000',
            'status' => 'OPEN',
        ]);

        \App\Models\ShiftAttendance::create([
            'pos_shift_id' => $shift->id,
            'piket_schedule_id' => null,
            'slot_number' => 1,
            'scheduled_user_id' => $this->student->id,
            'scheduled_student_status' => 'PRESENT',
            'active_operator_user_id' => $this->student->id,
            'approved_by_teacher_id' => $this->teacher->id,
            'check_in_at' => now(),
        ]);

        $product = \App\Models\Product::where('inventory_type', 'RETAIL')->firstOrFail();

        // 1. Cabut hak diskon manual & kupon dari piket_siswa (hanya admin & piket_guru)
        $manager = app(FeatureManager::class);
        $allFeatures = $manager->getFeatures();

        $payloadFeatures = [];
        foreach ($allFeatures as $key => $feat) {
            $roles = $feat['roles'];
            if ($key === 'pos_manual_discount' || $key === 'pos_coupon') {
                $roles = ['admin', 'piket_guru']; // piket_siswa dicabut
            }
            $payloadFeatures[$key] = [
                'is_active' => '1',
                'roles' => $roles,
            ];
        }

        $this->actingAs($this->superadmin)->put(route('settings.features.update'), [
            'features' => $payloadFeatures,
        ])->assertRedirect(route('settings.features'));

        // 2. Siswa mencoba set diskon manual -> ditolak & direset ke 0
        $component = \Livewire\Livewire::actingAs($this->student)
            ->test(\App\Livewire\Pos\PosTerminal::class)
            ->call('addToCart', 'RETAIL', $product->id)
            ->set('manualDiscountTotal', '5000');

        $component->assertSet('manualDiscountTotal', '0');
        $component->assertSee('Anda tidak memiliki izin untuk memberikan diskon manual.');

        // 3. Siswa mencoba gunakan kupon -> ditolak
        $component->set('couponCode', 'MPLB10')
            ->call('applyCoupon');

        $component->assertSee('Anda tidak memiliki izin untuk menggunakan kupon promo.');

        // 4. Sebaliknya, guru piket tetap berhak menggunakan diskon manual
        $teacherComponent = \Livewire\Livewire::actingAs($this->teacher)
            ->test(\App\Livewire\Pos\PosTerminal::class)
            ->call('addToCart', 'RETAIL', $product->id)
            ->set('manualDiscountTotal', '5000');

        $teacherComponent->assertSet('manualDiscountTotal', 5000.0);
    }
}

