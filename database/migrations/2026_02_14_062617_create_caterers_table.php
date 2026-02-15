<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caterers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            // --- 核心資料 ---
            $table->string('name')->comment('廠商名稱');
            $table->string('supply_type')->index()->comment('供應類型');

            // --- 擴充資料 (合規必備：統編、電話、地址) ---
            $table->string('tax_id')->nullable()->comment('統一編號');
            $table->string('phone')->nullable()->comment('聯絡電話');
            $table->string('address')->nullable()->comment('廠商地址');
            $table->string('contact_person')->nullable()->comment('聯絡人');

            // --- 狀態控制 ---
            $table->boolean('is_active')->default(true)->comment('是否為合格供應商');
            $table->softDeletes();
            $table->timestamps();

            // 索引優化：同一學校不能有兩個名字一樣的廠商
            $table->unique(['school_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caterers');
    }
};
