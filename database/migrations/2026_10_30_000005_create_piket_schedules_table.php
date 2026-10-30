<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piket_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years');
            $table->date('schedule_date');
            $table->unsignedTinyInteger('day_of_week')->comment('1=Senin s.d. 7=Minggu');
            $table->unsignedTinyInteger('slot_number')->comment('1 (Perwakilan X), 2 (Perwakilan XI), 3 (Perwakilan XII)');
            $table->foreignId('school_class_id')->constrained('school_classes');
            $table->foreignId('scheduled_student_id')->constrained('users');
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->unique(['schedule_date', 'slot_number'], 'uq_schedule_date_slot');
            $table->index('schedule_date', 'idx_piket_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piket_schedules');
    }
};
