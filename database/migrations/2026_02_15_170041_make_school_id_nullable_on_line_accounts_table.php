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
        Schema::table('line_accounts', function (Blueprint $table) {
            // Drop foreign key first (if exists)
            $table->dropForeign(['school_id']);

            // Make school_id nullable to support initial LINE login before profile completion
            $table->foreignId('school_id')
                ->nullable()
                ->change();

            // Re-add foreign key constraint
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

            $table->foreignId('school_id')
                ->nullable(false)
                ->change();

            $table->foreign('school_id')
                ->references('id')
                ->on('schools')
                ->onDelete('cascade');
        });
    }
};
