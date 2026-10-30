<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 15)->unique()->comment('RIM, LBR, PCS, PACK, BOX, KG, GRM');
            $table->string('name', 50)->comment('Rim, Lembar, Pcs, Pack, Box, Kilogram, Gram');
            $table->boolean('allow_decimal')->default(false)->comment('TRUE untuk Gram/Kg, FALSE untuk Pcs/Lembar');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
