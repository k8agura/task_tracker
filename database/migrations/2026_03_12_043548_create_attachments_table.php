<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('original_name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('path');
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['task_id']);
            $table->index(['message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};