<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id('id_staff');
            $table->foreignId('id_salon')->constrained('salon', 'id_salon')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('profile_url')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_salon');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
