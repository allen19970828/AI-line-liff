<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('line_accounts', function (Blueprint $table) {
            // [Step 1] 先新增欄位，暫時允許 NULL
            $table->foreignId('school_id')
                ->nullable()
                ->after('id')
                ->comment('所屬學校');
        });

        // [Step 2] 資料歸因：從關聯的 user.school_id 回填
        \DB::statement('
            UPDATE line_accounts
            SET school_id = (
                SELECT school_id
                FROM users
                WHERE users.id = line_accounts.user_id
            )
        ');

        // [Step 3] 設定為 NOT NULL + 外鍵約束
        Schema::table('line_accounts', function (Blueprint $table) {
            $table->foreignId('school_id')
                ->nullable(false)
                ->change();

            $table->foreign('school_id')
                ->references('id')
                ->on('schools')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('line_accounts', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
        });
    }
};
