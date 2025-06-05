<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id('ComponentID');
            $table->string('name');
            $table->unsignedBigInteger('AreaID')->nullable();
            $table->text('desc')->nullable();
            $table->timestamps();

            $table->foreign('AreaID')->references('AreaID')->on('areas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};