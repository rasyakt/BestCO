<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_shift_id')->constrained('pos_shifts')->cascadeOnDelete();
            $table->foreignId('piket_schedule_id')->nullable()->constrained('piket_schedules')->nullOnDelete();
            $table->unsignedTinyInteger('slot_number')->comment('Slot 1, 2, atau 3');
            $table->foreignId('scheduled_user_id')->constrained('users')->comment('Siswa yang dijadwalkan awal');
            $table->enum('scheduled_student_status', ['PRESENT', 'SICK', 'PERMITTED', 'ABSENT', 'DISPENSATION'])->default('PRESENT');
            $table->string('absence_reason', 255)->nullable()->comment('Wajib diisi jika status != PRESENT');
            $table->boolean('is_substituted')->default(false);
            $table->foreignId('substituted_user_id')->nullable()->constrained('users')->comment('Siswa pengganti yang hadir aktual');
            $table->foreignId('active_operator_user_id')->constrained('users')->comment('scheduled_user_id jika hadir, atau substituted_user_id jika diganti');
            $table->timestamp('check_in_at')->useCurrent();
            $table->timestamp('check_out_at')->nullable();
            $table->foreignId('approved_by_teacher_id')->constrained('users');
            $table->timestamps();
            $table->unique(['pos_shift_id', 'slot_number'], 'uq_shift_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_attendances');
    }
};
