<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_histories');
    }
};