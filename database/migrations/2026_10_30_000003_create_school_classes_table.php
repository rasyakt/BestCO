<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years');
            $table->enum('grade_level', ['X', 'XI', 'XII'])->comment('Tingkat kelas MPLB');
            $table->string('name', 50)->comment('Contoh: X MPLB 1, XI MPLB 2, XII MPLB 1');
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('grade_level', 'idx_classes_grade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};
