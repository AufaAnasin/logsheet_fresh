<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_data', function (Blueprint $table) {
            $table->id('LogID');
            $table->unsignedBigInteger('ComponentID')->nullable();
            $table->unsignedBigInteger('OperatorID')->nullable();
            $table->string('LogValue');
            $table->timestamp('LogTimestamp')->useCurrent();
            $table->text('Notes')->nullable();
            $table->timestamps();

            $table->foreign('ComponentID')->references('ComponentID')->on('components')->onDelete('cascade');
            $table->foreign('OperatorID')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_data');
    }
};