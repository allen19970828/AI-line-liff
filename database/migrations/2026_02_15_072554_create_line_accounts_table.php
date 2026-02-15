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
        Schema::create('line_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('綁定之系統使用者');

            $table->string('line_user_id')->unique()->comment('LINE User ID (Sub)');
            $table->string('display_name')->nullable()->comment('LINE 暱稱');
            $table->string('avatar_url')->nullable()->comment('LINE 大頭貼 URL');

            $table->text('access_token')->nullable()->comment('Channel Access Token');
            $table->text('refresh_token')->nullable()->comment('Channel Refresh Token');
            $table->timestamp('token_expires_at')->nullable()->comment('Token 過期時間');

            $table->jsonb('raw_response')->nullable()->comment('原始回應 JSON');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_accounts');
    }
};
