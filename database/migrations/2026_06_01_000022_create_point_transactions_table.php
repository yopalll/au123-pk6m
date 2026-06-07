<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->enum('type', ['earn', 'spend']);
            $table->integer('amount');
            $table->string('source'); // 'empty_return','purchase_discount','bonus'
            $table->integer('reference_id')->nullable();
            $table->string('reference_type')->nullable(); // polymorphic
            $table->string('description');
            $table->integer('saldo_after');
            $table->timestamp('created_at')->nullable();

            $table->foreign('id_user')->references('id_user')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
