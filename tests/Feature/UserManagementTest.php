<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UserManagementTest extends TestCase
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

    public function test_guest_and_student_cannot_access_user_management(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));

        $this->actingAs($this->student)->get(route('users.index'))->assertForbidden();
        $this->actingAs($this->student)->get(route('users.create'))->assertForbidden();
    }

    public function test_role_hierarchy_is_enforced(): void
    {
        $this->assertTrue($this->superadmin->canManageUsers());
        $this->assertTrue($this->admin->canManageUsers());
        $this->assertTrue($this->teacher->canManageUsers());
        $this->assertFalse($this->student->canManageUsers());

        $this->assertTrue($this->superadmin->canManageUser($this->admin));
        $this->assertTrue($this->superadmin->canManageUser($this->teacher));
        $this->assertFalse($this->admin->canManageUser($this->superadmin));
        $this->assertFalse($this->admin->canManageUser($this->admin));
        $this->assertTrue($this->admin->canManageUser($this->teacher));
        $this->assertTrue($this->admin->canManageUser($this->student));
        $this->assertFalse($this->teacher->canManageUser($this->teacher));
        $this->assertFalse($this->teacher->canManageUser($this->admin));
        $this->assertTrue($this->teacher->canManageUser($this->student));
    }

    public function test_superadmin_sees_all_users(): void
    {
        $response = $this->actingAs($this->superadmin)->get(route('users.index'));

        $response->assertOk()
            ->assertSee('Kelola Pengguna')
            ->assertSee('Superadmin BESTCO')
            ->assertSee('Administrator BESTCO')
            ->assertSee('Guru Piket 1')
            ->assertSee('Siswa Piket X 1');

        $users = $response->viewData('users');
        $this->assertEqualsCanonicalizing(
            ['superadmin', 'admin', 'piket_guru', 'piket_siswa'],
            $users->pluck('role_type')->map(fn (UserRole $role) => $role->value)->unique()->values()->all()
        );
    }

    public function test_admin_only_sees_teacher_and_student_accounts(): void
    {
        $response = $this->actingAs($this->admin)->get(route('users.index'));

        $response->assertOk()
            ->assertSee('Guru Piket 1')
            ->assertSee('Siswa Piket X 1')
            ->assertDontSee('Superadmin BESTCO');

        $users = $response->viewData('users');
        $this->assertTrue($users->every(
            fn (User $user) => in_array($user->role_type->value, ['piket_guru', 'piket_siswa'], true)
        ));
    }

    public function test_teacher_only_sees_student_accounts(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('users.index'));

        $response->assertOk()
            ->assertSee('Siswa Piket X 1')
            ->assertDontSee('Guru Piket 2')
            ->assertDontSee('Administrator BESTCO');

        $users = $response->viewData('users');
        $this->assertTrue($users->every(
            fn (User $user) => $user->role_type === UserRole::PIKET_SISWA
        ));
    }

    public function test_create_form_only_offers_roles_within_actor_scope(): void
    {
        $this->actingAs($this->superadmin)->get(route('users.create'))
            ->assertOk()
            ->assertDontSee('value="superadmin"', false)
            ->assertSee('value="admin"', false)
            ->assertSee('value="piket_guru"', false)
            ->assertSee('value="piket_siswa"', false);

        $this->actingAs($this->admin)->get(route('users.create'))
            ->assertOk()
            ->assertSee('value="piket_guru"', false)
            ->assertSee('value="piket_siswa"', false)
            ->assertDontSee('value="superadmin"', false);

        $this->actingAs($this->teacher)->get(route('users.create'))
            ->assertOk()
            ->assertSee('value="piket_siswa"', false)
            ->assertDontSee('value="piket_guru"', false);
    }

    public function test_admin_can_create_teacher_with_hashed_password_and_pin(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), $this->staffPayload([
            'name' => 'Guru Baru',
            'username' => 'guru_baru',
            'nip_nisn' => 'GURU900',
            'pin_code' => '654321',
        ]));

        $response->assertRedirect(route('users.index'))->assertSessionHasNoErrors();

        $user = User::where('username', 'guru_baru')->firstOrFail();
        $this->assertSame(UserRole::PIKET_GURU, $user->role_type);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertTrue(Hash::check('654321', $user->pin_code));
    }

    public function test_superadmin_can_create_student_with_profile(): void
    {
        $class = SchoolClass::firstOrFail();

        $response = $this->actingAs($this->superadmin)->post(route('users.store'), $this->studentPayload([
            'name' => 'Siswa Baru',
            'username' => 'siswa_baru',
            'nip_nisn' => 'NISN999',
            'school_class_id' => (string) $class->id,
            'nis' => 'X999',
            'phone' => '081234567890',
        ]));

        $response->assertRedirect(route('users.index'))->assertSessionHasNoErrors();

        $user = User::where('username', 'siswa_baru')->firstOrFail();
        $this->assertSame(UserRole::PIKET_SISWA, $user->role_type);
        $this->assertNull($user->pin_code);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $user->id,
            'school_class_id' => $class->id,
            'nis' => 'X999',
            'phone' => '081234567890',
        ]);
    }

    public function test_admin_cannot_create_role_outside_scope(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), $this->staffPayload([
            'role_type' => 'superadmin',
        ]));

        $response->assertSessionHasErrors('role_type');
        $this->assertDatabaseMissing('users', ['username' => 'guru_baru']);
    }

    public function test_superadmin_cannot_create_another_superadmin(): void
    {
        $response = $this->actingAs($this->superadmin)->post(route('users.store'), $this->staffPayload([
            'name' => 'Superadmin Kedua',
            'username' => 'superadmin_kedua',
            'nip_nisn' => 'SA002',
            'role_type' => UserRole::SUPERADMIN->value,
        ]));

        $response->assertSessionHasErrors('role_type');
        $this->assertDatabaseMissing('users', ['username' => 'superadmin_kedua']);
        $this->assertSame(1, User::where('role_type', UserRole::SUPERADMIN)->count());
    }

    public function test_superadmin_cannot_promote_another_account_to_superadmin(): void
    {
        $response = $this->actingAs($this->superadmin)->put(route('users.update', $this->admin), [
            'name' => $this->admin->name,
            'username' => $this->admin->username,
            'email' => $this->admin->email,
            'nip_nisn' => $this->admin->nip_nisn,
            'role_type' => UserRole::SUPERADMIN->value,
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('role_type');
        $this->assertSame(UserRole::ADMIN, $this->admin->fresh()->role_type);
        $this->assertSame(1, User::where('role_type', UserRole::SUPERADMIN)->count());
    }

    public function test_superadmin_edit_form_keeps_own_superadmin_role(): void
    {
        $this->actingAs($this->superadmin)->get(route('users.edit', $this->superadmin))
            ->assertOk()
            ->assertSee('value="superadmin"', false)
            ->assertDontSee('value="admin"', false);
    }

    public function test_duplicate_username_and_nis_are_rejected(): void
    {
        $duplicateUsername = $this->actingAs($this->admin)->post(route('users.store'), $this->staffPayload([
            'username' => 'admin',
        ]));
        $duplicateUsername->assertSessionHasErrors('username');

        $duplicateNis = $this->actingAs($this->superadmin)->post(route('users.store'), $this->studentPayload([
            'username' => 'siswa_lain',
            'nis' => $this->student->studentProfile->nis,
        ]));
        $duplicateNis->assertSessionHasErrors('nis');
    }

    public function test_staff_requires_six_digit_pin_and_confirmed_password(): void
    {
        $withoutPin = $this->actingAs($this->admin)->post(route('users.store'), $this->staffPayload([
            'pin_code' => null,
        ]));
        $withoutPin->assertSessionHasErrors('pin_code');

        $mismatchedPassword = $this->actingAs($this->admin)->post(route('users.store'), $this->staffPayload([
            'password_confirmation' => 'berbeda123',
        ]));
        $mismatchedPassword->assertSessionHasErrors('password');
    }

    public function test_admin_cannot_edit_or_update_accounts_outside_scope(): void
    {
        $this->actingAs($this->admin)->get(route('users.edit', $this->superadmin))->assertForbidden();
        $this->actingAs($this->admin)->get(route('users.edit', $this->admin))->assertForbidden();
        $this->actingAs($this->admin)->put(route('users.update', $this->superadmin), [])->assertForbidden();
        $this->actingAs($this->teacher)->get(route('users.edit', $this->teacher))->assertForbidden();
        $this->actingAs($this->teacher)->get(route('users.edit', $this->admin))->assertForbidden();
    }

    public function test_teacher_can_update_student_profile(): void
    {
        $newClass = SchoolClass::whereKeyNot($this->student->studentProfile->school_class_id)->first()
            ?? SchoolClass::firstOrFail();

        $response = $this->actingAs($this->teacher)->put(route('users.update', $this->student), [
            'name' => 'Siswa Piket Diperbarui',
            'username' => $this->student->username,
            'email' => $this->student->email,
            'nip_nisn' => $this->student->nip_nisn,
            'role_type' => UserRole::PIKET_SISWA->value,
            'is_active' => '1',
            'school_class_id' => (string) $newClass->id,
            'nis' => 'X777',
            'phone' => '081200000000',
        ]);

        $response->assertRedirect(route('users.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $this->student->id,
            'name' => 'Siswa Piket Diperbarui',
        ]);
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $this->student->id,
            'school_class_id' => $newClass->id,
            'nis' => 'X777',
            'phone' => '081200000000',
        ]);
    }

    public function test_updating_without_password_keeps_old_password(): void
    {
        $oldPasswordHash = $this->teacher->password;

        $response = $this->actingAs($this->superadmin)->put(route('users.update', $this->teacher), [
            'name' => 'Guru Piket Satu',
            'username' => $this->teacher->username,
            'email' => $this->teacher->email,
            'nip_nisn' => $this->teacher->nip_nisn,
            'role_type' => UserRole::PIKET_GURU->value,
            'is_active' => '1',
            'pin_code' => '',
        ]);

        $response->assertRedirect(route('users.index'))->assertSessionHasNoErrors();

        $this->teacher->refresh();
        $this->assertSame('Guru Piket Satu', $this->teacher->name);
        $this->assertSame($oldPasswordHash, $this->teacher->password);
    }

    public function test_updating_with_new_password_hashes_it(): void
    {
        $response = $this->actingAs($this->superadmin)->put(route('users.update', $this->teacher), [
            'name' => $this->teacher->name,
            'username' => $this->teacher->username,
            'email' => $this->teacher->email,
            'nip_nisn' => $this->teacher->nip_nisn,
            'role_type' => UserRole::PIKET_GURU->value,
            'is_active' => '1',
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ]);

        $response->assertRedirect(route('users.index'))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('passwordbaru', $this->teacher->fresh()->password));
    }

    public function test_self_role_change_and_self_deactivation_are_blocked(): void
    {
        $roleChange = $this->actingAs($this->superadmin)->put(route('users.update', $this->superadmin), [
            'name' => $this->superadmin->name,
            'username' => $this->superadmin->username,
            'email' => $this->superadmin->email,
            'nip_nisn' => $this->superadmin->nip_nisn,
            'role_type' => UserRole::ADMIN->value,
            'is_active' => '1',
        ]);
        $roleChange->assertSessionHasErrors('role_type');
        $this->assertSame(UserRole::SUPERADMIN, $this->superadmin->fresh()->role_type);

        $deactivation = $this->actingAs($this->superadmin)->patch(route('users.status', $this->superadmin));
        $deactivation->assertRedirect()->assertSessionHas('error');
        $this->assertTrue($this->superadmin->fresh()->is_active);
    }

    public function test_teacher_can_deactivate_student(): void
    {
        $response = $this->actingAs($this->teacher)->patch(route('users.status', $this->student));

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertFalse($this->student->fresh()->is_active);
    }

    public function test_last_active_superadmin_cannot_be_deactivated_or_demoted(): void
    {
        $inactiveSuperadmin = User::create([
            'name' => 'Superadmin Cadangan',
            'username' => 'superadmin_cadangan',
            'email' => null,
            'nip_nisn' => 'SA900',
            'password' => 'rahasia123',
            'pin_code' => null,
            'role_type' => UserRole::SUPERADMIN,
            'is_active' => false,
        ]);

        $deactivation = $this->actingAs($inactiveSuperadmin)->patch(route('users.status', $this->superadmin));
        $deactivation->assertRedirect()->assertSessionHas('error');
        $this->assertTrue($this->superadmin->fresh()->is_active);

        $demotion = $this->actingAs($inactiveSuperadmin)->put(route('users.update', $this->superadmin), [
            'name' => $this->superadmin->name,
            'username' => $this->superadmin->username,
            'email' => $this->superadmin->email,
            'nip_nisn' => $this->superadmin->nip_nisn,
            'role_type' => UserRole::ADMIN->value,
            'is_active' => '1',
        ]);
        $demotion->assertSessionHasErrors('role_type');
        $this->assertSame(UserRole::SUPERADMIN, $this->superadmin->fresh()->role_type);
    }

    /**
     * @return array<string, mixed>
     */
    private function staffPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Guru Baru',
            'username' => 'guru_baru',
            'email' => 'guru.baru@bestco.test',
            'nip_nisn' => 'GURU900',
            'role_type' => UserRole::PIKET_GURU->value,
            'is_active' => '1',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'pin_code' => '654321',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function studentPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Siswa Baru',
            'username' => 'siswa_baru',
            'email' => null,
            'nip_nisn' => 'NISN999',
            'role_type' => UserRole::PIKET_SISWA->value,
            'is_active' => '1',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'school_class_id' => (string) SchoolClass::firstOrFail()->id,
            'nis' => 'X999',
            'phone' => '081234567890',
        ], $overrides);
    }
}
