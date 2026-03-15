<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('creator_id');
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('parent_task_id')->nullable();
            $table->timestamps();

            $table->foreign('status_id')->references('id')->on('task_statuses')->restrictOnDelete();
            $table->foreign('creator_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('parent_task_id')->references('id')->on('tasks')->nullOnDelete();

            $table->index(['status_id', 'due_date']);
            $table->index(['creator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};