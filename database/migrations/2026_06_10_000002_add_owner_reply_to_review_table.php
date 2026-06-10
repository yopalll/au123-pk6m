<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('review', function (Blueprint $table) {
            $table->text('owner_reply')->nullable()->after('komentar');
            $table->timestamp('owner_reply_at')->nullable()->after('owner_reply');
        });
    }

    public function down(): void
    {
        Schema::table('review', function (Blueprint $table) {
            $table->dropColumn(['owner_reply', 'owner_reply_at']);
        });
    }
};
