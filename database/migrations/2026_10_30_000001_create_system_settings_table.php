<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('setting_group', 50)->comment('theme, accounting, pos, school');
            $table->string('setting_key', 100)->unique();
            $table->json('setting_value');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index('setting_group', 'idx_settings_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
