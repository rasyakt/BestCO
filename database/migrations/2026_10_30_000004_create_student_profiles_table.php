<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes');
            $table->string('nis', 25)->unique();
            $table->string('phone', 20)->nullable();
            $table->unsignedInteger('total_piket_completed')->default(0);
            $table->unsignedInteger('total_substitute_completed')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
