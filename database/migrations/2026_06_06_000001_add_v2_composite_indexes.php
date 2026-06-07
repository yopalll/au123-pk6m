<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'is_featured'], 'idx_products_status_featured');
            $table->index(['id_product_category', 'status'], 'idx_products_cat_status');
            $table->index(['id_collection', 'status'], 'idx_products_col_status');
        });

        Schema::table('product_orders', function (Blueprint $table) {
            $table->index(['id_user', 'status'], 'idx_porders_user_status');
            $table->index(['id_user', 'created_at'], 'idx_porders_user_created');
        });

        Schema::table('forum_threads', function (Blueprint $table) {
            $table->index(['id_forum_category', 'status', 'is_pinned'], 'idx_threads_cat_status_pin');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->index('id_user', 'idx_carts_user');
        });

        Schema::table('empty_returns', function (Blueprint $table) {
            $table->index(['id_user', 'status'], 'idx_returns_user_status');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_status_featured');
            $table->dropIndex('idx_products_cat_status');
            $table->dropIndex('idx_products_col_status');
        });
        Schema::table('product_orders', function (Blueprint $table) {
            $table->dropIndex('idx_porders_user_status');
            $table->dropIndex('idx_porders_user_created');
        });
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropIndex('idx_threads_cat_status_pin');
        });
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('idx_carts_user');
        });
        Schema::table('empty_returns', function (Blueprint $table) {
            $table->dropIndex('idx_returns_user_status');
        });
    }
};
