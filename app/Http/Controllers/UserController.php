<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $this->ensureCanManageUsers($actor);

        $roleValues = $this->manageableRoleValues($actor);
        $search = trim((string) $request->query('q', ''));
        $roleFilter = (string) $request->query('role', 'ALL');
        $statusFilter = (string) $request->query('status', 'ALL');

        $query = User::query()
            ->whereIn('role_type', $roleValues)
            ->with('studentProfile.schoolClass');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('nip_nisn', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter !== 'ALL' && in_array($roleFilter, $roleValues, true)) {
            $query->where('role_type', $roleFilter);
        }

        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query
            ->orderByRaw("CASE role_type
                WHEN 'superadmin' THEN 1
                WHEN 'admin' THEN 2
                WHEN 'piket_guru' THEN 3
                WHEN 'piket_siswa' THEN 4
                ELSE 5 END")
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $baseQuery = User::query()->whereIn('role_type', $roleValues);
        $counts = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
        ];

        return view('users.index', [
            'users' => $users,
            'roles' => $actor->role_type->manageableRoles(),
            'search' => $search,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter,
            'counts' => $counts,
        ]);
    }

    public function create(Request $request): View
    {
        $actor = $request->user();
        $this->ensureCanManageUsers($actor);

        return view('users.create', [
            'roles' => $this->assignableRoles($actor),
            'classes' => SchoolClass::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureCanManageUsers($actor);

        $validated = $request->validate(
            $this->rules($request, null),
            $this->messages(),
            $this->attributes(),
        );
        $role = UserRole::from($validated['role_type']);
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : true;

        DB::transaction(function () use ($validated, $role, $isActive): void {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => ($validated['email'] ?? null) ?: null,
                'nip_nisn' => ($validated['nip_nisn'] ?? null) ?: null,
                'password' => $validated['password'],
                'pin_code' => $role === UserRole::PIKET_SISWA ? null : ($validated['pin_code'] ?? null),
                'role_type' => $role,
                'is_active' => $isActive,
            ]);

            if ($role === UserRole::PIKET_SISWA) {
                StudentProfile::create([
                    'user_id' => $user->id,
                    'school_class_id' => $validated['school_class_id'],
                    'nis' => $validated['nis'],
                    'phone' => ($validated['phone'] ?? null) ?: null,
                ]);
            }
        });

        return redirect()
            ->route('users.index')
            ->with('success', "Akun {$validated['name']} berhasil ditambahkan.");
    }

    public function edit(Request $request, User $user): View
    {
        $actor = $request->user();
        $this->ensureCanManageTarget($actor, $user);

        return view('users.edit', [
            'user' => $user->loadMissing('studentProfile.schoolClass'),
            'roles' => $this->assignableRoles($actor, $user),
            'classes' => SchoolClass::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureCanManageTarget($actor, $user);

        $validated = $request->validate(
            $this->rules($request, $user),
            $this->messages(),
            $this->attributes(),
        );
        $newRole = UserRole::from($validated['role_type']);
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : $user->is_active;

        if ($user->is($actor)) {
            if ($newRole !== $actor->role_type) {
                throw ValidationException::withMessages([
                    'role_type' => 'Anda tidak dapat mengubah role akun sendiri.',
                ]);
            }

            if (! $isActive) {
                throw ValidationException::withMessages([
                    'is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.',
                ]);
            }
        }

        if (
            $user->hasRole(UserRole::SUPERADMIN)
            && ($newRole !== UserRole::SUPERADMIN || ! $isActive)
            && ! User::query()
                ->where('role_type', UserRole::SUPERADMIN)
                ->where('is_active', true)
                ->whereKeyNot($user->id)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'role_type' => 'Minimal harus ada satu akun superadmin yang aktif.',
            ]);
        }

        DB::transaction(function () use ($validated, $user, $newRole, $isActive): void {
            $user->name = $validated['name'];
            $user->username = $validated['username'];
            $user->email = ($validated['email'] ?? null) ?: null;
            $user->nip_nisn = ($validated['nip_nisn'] ?? null) ?: null;
            $user->role_type = $newRole;
            $user->is_active = $isActive;

            if (! empty($validated['password'])) {
                $user->password = $validated['password'];
            }

            if ($newRole === UserRole::PIKET_SISWA) {
                $user->pin_code = null;
            } elseif (! empty($validated['pin_code'])) {
                $user->pin_code = $validated['pin_code'];
            }

            $user->save();

            if ($newRole === UserRole::PIKET_SISWA) {
                StudentProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'school_class_id' => $validated['school_class_id'],
                        'nis' => $validated['nis'],
                        'phone' => ($validated['phone'] ?? null) ?: null,
                    ]
                );
            }
        });

        return redirect()
            ->route('users.index')
            ->with('success', "Data akun {$user->name} berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureCanManageTarget($actor, $user);

        if ($user->is($actor)) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        if (
            $user->hasRole(UserRole::SUPERADMIN)
            && $user->is_active
            && ! User::query()
                ->where('role_type', UserRole::SUPERADMIN)
                ->where('is_active', true)
                ->whereKeyNot($user->id)
                ->exists()
        ) {
            return back()->with('error', 'Minimal harus ada satu akun superadmin yang aktif.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with(
            'success',
            $user->is_active
                ? "Akun {$user->name} berhasil diaktifkan."
                : "Akun {$user->name} berhasil dinonaktifkan."
        );
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
            'email' => 'Format email tidak valid.',
            'unique' => ':attribute sudah digunakan.',
            'boolean' => 'Status akun tidak valid.',
            'min' => ':attribute minimal :min karakter.',
            'confirmed' => 'Konfirmasi password tidak cocok.',
            'pin_code.digits' => ':attribute harus terdiri dari 6 digit angka.',
            'exists' => ':attribute yang dipilih tidak ditemukan.',
            'role_type.in' => 'Role yang dipilih tidak sesuai kewenangan Anda.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.',
            'nip_nisn.regex' => 'NIP/NISN hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.',
            'phone.regex' => 'Nomor HP hanya boleh berisi angka, spasi, tanda hubung, tanda kurung, atau +.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function attributes(): array
    {
        return [
            'name' => 'Nama lengkap',
            'username' => 'Username',
            'email' => 'Email',
            'nip_nisn' => 'NIP/NISN',
            'role_type' => 'Role',
            'password' => 'Password',
            'pin_code' => 'PIN',
            'school_class_id' => 'Kelas',
            'nis' => 'NIS',
            'phone' => 'Nomor HP',
            'is_active' => 'Status akun',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Request $request, ?User $user): array
    {
        $actor = $request->user();
        $allowedRoles = array_map(
            static fn (UserRole $role): string => $role->value,
            $this->assignableRoles($actor, $user)
        );
        $studentProfile = $user?->studentProfile;
        $requestedRole = (string) $request->input('role_type');
        $isStudent = $requestedRole === UserRole::PIKET_SISWA->value;
        $requiresPin = in_array($requestedRole, [
            UserRole::SUPERADMIN->value,
            UserRole::ADMIN->value,
            UserRole::PIKET_GURU->value,
        ], true);
        $pinRequired = $requiresPin && ($user === null || $user->pin_code === null);

        return [
            'name' => ['required', 'string', 'max:150'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_.-]+$/',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'nip_nisn' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[A-Za-z0-9_.-]*$/',
                Rule::unique('users', 'nip_nisn')->ignore($user?->id),
            ],
            'role_type' => ['required', Rule::in($allowedRoles)],
            'is_active' => ['nullable', 'boolean'],
            'password' => $user
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed'],
            'pin_code' => ['nullable', 'digits:6', Rule::requiredIf($pinRequired)],
            'school_class_id' => [
                'nullable',
                'exists:school_classes,id',
                Rule::requiredIf($isStudent),
            ],
            'nis' => [
                'nullable',
                'string',
                'max:25',
                Rule::requiredIf($isStudent),
                Rule::unique('student_profiles', 'nis')->ignore($studentProfile?->id),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\s-]*$/'],
        ];
    }

    /**
     * Role yang boleh ditetapkan oleh aktor pada akun target.
     *
     * Akun sendiri selalu mempertahankan role-nya agar superadmin tidak
     * kehilangan akses, dan superadmin tidak dapat membuat superadmin baru.
     *
     * @return array<int, UserRole>
     */
    private function assignableRoles(User $actor, ?User $target = null): array
    {
        if ($target !== null && $target->is($actor)) {
            return [$actor->role_type];
        }

        return $actor->role_type->assignableRoles();
    }

    /**
     * @return array<int, string>
     */
    private function manageableRoleValues(User $actor): array
    {
        return array_map(
            static fn (UserRole $role): string => $role->value,
            $actor->role_type->manageableRoles()
        );
    }

    private function ensureCanManageUsers(User $actor): void
    {
        abort_unless($actor->canManageUsers(), 403, 'Anda tidak memiliki izin mengelola pengguna.');
    }

    private function ensureCanManageTarget(User $actor, User $target): void
    {
        abort_unless(
            $actor->canManageUser($target),
            403,
            'Anda tidak memiliki izin mengelola akun dengan role ini.'
        );
    }
}
