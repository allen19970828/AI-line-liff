<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meal_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_id')->constrained('school_staff')->cascadeOnDelete()->comment('回報對象 (老師/行政)');

            $table->date('occurred_at')->index()->comment('發生日期');
            $table->text('content')->comment('問題描述');
            $table->string('photo_path')->nullable()->comment('照片證據路徑');

            $table->string('status')->default('pending')->index()->comment('處理狀態'); // pending, processing, resolved
            $table->text('caterer_reply')->nullable()->comment('廠商回覆');

            $table->timestamp('resolved_at')->nullable()->comment('結案時間');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meal_complaints');
    }
};
