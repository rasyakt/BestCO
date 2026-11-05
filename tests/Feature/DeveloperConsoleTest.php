<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DeveloperConsoleTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $guru;
    private User $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superadmin = User::where('role_type', UserRole::SUPERADMIN->value)->firstOrFail();
        $this->admin = User::where('role_type', UserRole::ADMIN->value)->firstOrFail();
        $this->guru = User::where('role_type', UserRole::PIKET_GURU->value)->firstOrFail();
        $this->siswa = User::where('role_type', UserRole::PIKET_SISWA->value)->firstOrFail();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dev.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_developer_console(): void
    {
        $this->actingAs($this->admin)->get(route('dev.index'))->assertForbidden();
        $this->actingAs($this->guru)->get(route('dev.index'))->assertForbidden();
        $this->actingAs($this->siswa)->get(route('dev.index'))->assertForbidden();
    }

    public function test_superadmin_can_access_developer_console(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('dev.index'));

        $response->assertOk();
        $response->assertSee('Developer Console');
        $response->assertSee('Identitas & Logo', false);
        $response->assertSee('Statistik Sistem');
        $response->assertSee('Error Log');
    }

    public function test_superadmin_can_update_website_identity(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('custom_logo.png', 200, 200);

        $response = $this->actingAs($this->superadmin)->put(route('dev.identity.update'), [
            'app_name' => 'BESTCO DEV',
            'app_tagline' => 'Teaching Factory MPLB Enterprise',
            'footer_text' => 'Custom footer dev test',
            'app_logo' => $logo,
        ]);

        $response->assertRedirect(route('dev.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('system_settings', [
            'setting_key' => 'app_identity',
            'setting_group' => 'developer',
        ]);

        $setting = SystemSetting::where('setting_key', 'app_identity')->first();
        $this->assertSame('BESTCO DEV', $setting->setting_value['app_name']);
        $this->assertSame('Teaching Factory MPLB Enterprise', $setting->setting_value['app_tagline']);
        $this->assertNotNull($setting->setting_value['app_logo']);
        Storage::disk('public')->assertExists($setting->setting_value['app_logo']);
    }

    public function test_superadmin_can_reset_custom_logo(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('old_logo.png', 100, 100);
        $path = $file->storeAs('branding', 'old_logo.png', 'public');

        SystemSetting::create([
            'setting_key' => 'app_identity',
            'setting_group' => 'developer',
            'setting_value' => [
                'app_name' => 'BESTCO',
                'app_tagline' => 'Tagline',
                'app_logo' => $path,
                'footer_text' => 'Footer',
            ],
            'updated_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)->delete(route('dev.identity.resetLogo'));

        $response->assertRedirect(route('dev.index'));
        $response->assertSessionHas('success');

        Storage::disk('public')->assertMissing($path);

        $setting = SystemSetting::where('setting_key', 'app_identity')->first();
        $this->assertNull($setting->setting_value['app_logo']);
    }

    public function test_superadmin_can_clear_error_logs(): void
    {
        $logPath = storage_path('logs/laravel.log');
        file_put_contents($logPath, '[2026-11-05 12:00:00] local.ERROR: Test error for clear.');

        $response = $this->actingAs($this->superadmin)->delete(route('dev.logs.clear'));

        $response->assertRedirect(route('dev.index'));
        $response->assertSessionHas('success');

        $this->assertSame('', file_get_contents($logPath));
    }

    public function test_superadmin_can_download_error_logs(): void
    {
        $logPath = storage_path('logs/laravel.log');
        file_put_contents($logPath, '[2026-11-05 12:00:00] local.ERROR: Test error for download.');

        $response = $this->actingAs($this->superadmin)->get(route('dev.logs.download'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/plain; charset=UTF-8');
    }
}
