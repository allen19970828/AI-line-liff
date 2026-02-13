<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 宗師心法：此處只管建立「學校」本體，切勿在此處糾纏「用戶」的關聯。
     */
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            // [基礎架構]
            $table->id();

            // [核心識別]
            // 加上 unique() 保證唯一性，加上 index() 加速搜尋。
            // 學校代碼通常是查詢的關鍵（如教育部編號），務必建索引。
            $table->string('code')
                ->nullable()
                ->unique()
                ->index()
                ->comment('學校代碼 (如教育部編號)，唯一且建索引');

            $table->string('name')
                ->comment('學校全名');

            // [營運數據]
            // 稅籍編號 (統編)，字串為佳，因可能包含前導零或非數字格式
            $table->string('tax_id')
                ->nullable()
                ->comment('統一編號 (用於發票)');

            // [地理位置]
            // 拆分縣市與行政區，有利於後續做統計報表或下拉選單篩選
            $table->string('city')->nullable()->comment('縣市 (如：台中市)');
            $table->string('district')->nullable()->comment('行政區 (如：北屯區)');
            $table->string('address')->nullable()->comment('詳細地址');

            // [聯絡方式]
            $table->string('phone')->nullable()->comment('學校總機');
            $table->string('website')->nullable()->comment('官方網站 URL');

            // [狀態控制]
            // 預設為 false (未啟用)，需經過審核或手動啟用，此乃資安防禦之「最小權限原則」。
            $table->boolean('is_active')
                ->default(false)
                ->comment('啟用狀態 (true: 營運中, false: 停用/審核中)');

            // [時空標記]
            $table->timestamps();

            // [後悔藥]
            // 軟刪除，數據無價，不可輕易抹去。
            $table->softDeletes()->comment('刪除時間 (軟刪除)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 宗師指點：因為沒有在此處修改 users 表，所以回滾時極其乾淨。
        // 系統會自動處理掉建立在這個表上的外鍵約束。
        Schema::dropIfExists('schools');
    }
};
