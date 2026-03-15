<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id')->unique();
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};