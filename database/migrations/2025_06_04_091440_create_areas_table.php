<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id('AreaID');
            $table->string('name');
            $table->unsignedBigInteger('DepartmentID')->nullable();
            $table->text('desc')->nullable();
            $table->timestamps();

            $table->foreign('DepartmentID')->references('DepartmentID')->on('departments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};