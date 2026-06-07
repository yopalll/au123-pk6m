<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empty_return_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_return');
            $table->string('photo_url');
            $table->timestamp('created_at')->nullable();

            $table->foreign('id_return')->references('id_return')->on('empty_returns')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empty_return_photos');
    }
};
