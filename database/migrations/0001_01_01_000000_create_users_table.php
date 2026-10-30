<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('username', 50)->unique();
            $table->string('email', 150)->nullable()->unique();
            $table->string('nip_nisn', 30)->nullable()->unique()->comment('NIP untuk Guru/Admin, NISN untuk Siswa');
            $table->string('password');
            $table->string('pin_code', 255)->nullable()->comment('Hashed 6-digit PIN untuk approval cepat Guru Piket');
            $table->enum('role_type', ['superadmin', 'admin', 'piket_guru', 'piket_siswa']);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
            $table->index(['role_type', 'is_active'], 'idx_users_role_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
