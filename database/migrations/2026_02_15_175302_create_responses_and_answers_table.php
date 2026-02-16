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
        // 3. 回覆單頭 (Snapshot 層)
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();

            // ⭐ 優化 4: 強化多租戶約束
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // ⭐ 宗師讚賞：這段 Snapshot 邏輯非常棒，請保留！
            $table->string('snapshot_role')->nullable();
            $table->string('snapshot_grade')->nullable()->index();
            $table->string('snapshot_class')->nullable();
            $table->string('snapshot_name')->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            // 防重複：同一個人對同一份問卷只能填一次
            $table->unique(['survey_id', 'user_id']);
        });

        // 4. 答案明細 (Data 層)
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            // 這裡用 foreignId 雖然安全，但為了大數據寫入效能，
            // 有時候大廠會拿掉 constraint，但我們學校系統量級還好，保留即可。
            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_question_id')->constrained()->cascadeOnDelete();
            $table->integer('rating_value')->nullable()->index();
            $table->text('text_value')->nullable();

            // ⭐ 優化 5: 支援多媒體回饋 (照片/影片)
            $table->json('media_urls')->nullable()->comment('照片網址陣列');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
        Schema::dropIfExists('survey_responses');
    }
};
