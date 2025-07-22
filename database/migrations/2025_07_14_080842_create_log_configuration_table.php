<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_configuration', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('DepartmentID'); // Sesuaikan dengan primary key departments
            $table->unsignedBigInteger('AreaID');      // Sesuaikan dengan primary key areas
            $table->string('frequency_type'); // Misalnya 'hourly', 'twice_daily', 'daily'
            $table->json('schedule')->nullable(); // Jadwal waktu dalam array JSON
            $table->timestamps();

            $table->foreign('DepartmentID')->references('DepartmentID')->on('departments')->onDelete('cascade');
            $table->foreign('AreaID')->references('AreaID')->on('areas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_configuration');
    }
};