<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('national_holidays', function (Blueprint $table) {
            $table->id();

            // [核心識別]
            $table->date('date')->unique()->comment('日期');

            // [關鍵區分]
            // 台灣特色：必須區分是「放假」還是「補班」
            // type: holiday (放假), makeup (補班/補課)
            $table->string('type')
                ->default('holiday')
                ->comment('類型: holiday=放假, makeup=補課(供餐)');

            // [描述]
            // ex: "元旦", "春節補假", "國慶日調整放假", "補行上班上課"
            $table->string('name')->comment('節日名稱/備註');

            // [方便查詢]
            // 建議加上年份，方便後台「匯入 114 年行事曆」時檢查用
            $table->integer('year')->index()->comment('民國年或西元年皆可，建議統一');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('national_holidays');
    }
};
