<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caterer_semester', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('caterer_id')->constrained()->cascadeOnDelete();

            // --- 合約細節 (屬於這學期的) ---
            // 供餐時段 ex: ["lunch", "morning_snack"]
            $table->jsonb('service_meals')->comment('本學期供應時段');

            $table->timestamps();

            // 防呆：同一學期，同一廠商只能簽一次約
            $table->unique(['semester_id', 'caterer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caterer_semester');
    }
};
