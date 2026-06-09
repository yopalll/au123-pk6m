<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->string('email');
            $table->string('code');            // bcrypt hash of the 6-digit code
            $table->string('purpose', 32)->default('verify_email'); // verify_email | reset_password | login_2fa
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id_user')->on('users')->cascadeOnDelete();
            $table->index(['id_user', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};
