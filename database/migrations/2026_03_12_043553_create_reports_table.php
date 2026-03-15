<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('created_by');
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->json('filters')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};