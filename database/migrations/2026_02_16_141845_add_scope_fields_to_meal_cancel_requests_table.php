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
        Schema::table('meal_cancel_requests', function (Blueprint $table) {
            // [宗師指點] 申請範圍類型：null=個人, 'class_activity'=班級活動, 'group'=跨班團體
            $table->string('scope_type')
                ->nullable()
                ->after('leave_type')
                ->comment('申請範圍類型: null=個人, class_activity=班級活動, group=跨班團體');

            // [宗師指點] 關聯班級（僅班級活動停餐使用）
            $table->foreignId('classroom_id')
                ->nullable()
                ->after('scope_type')
                ->constrained()
                ->nullOnDelete()
                ->comment('關聯班級 (僅班級活動停餐)');

            // [宗師指點] 申請人（記錄哪位老師代為申請）
            $table->foreignId('requested_by')
                ->nullable()
                ->after('classroom_id')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('申請人 (代為申請的老師)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meal_cancel_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by');
            $table->dropConstrainedForeignId('classroom_id');
            $table->dropColumn('scope_type');
        });
    }
};
