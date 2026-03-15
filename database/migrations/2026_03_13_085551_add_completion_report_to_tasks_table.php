<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->longText('completion_report')->nullable()->after('description');
            $table->timestamp('completed_at')->nullable()->after('completion_report');
            $table->unsignedBigInteger('completed_by')->nullable()->after('completed_at');

            $table->foreign('completed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['completed_by']);
            $table->dropColumn(['completion_report', 'completed_at', 'completed_by']);
        });
    }
};