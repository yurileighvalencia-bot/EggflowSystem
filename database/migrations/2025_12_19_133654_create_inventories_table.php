<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('egg_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('available_stock')->default(0); // Stock available for sale
            $table->integer('reserved_stock')->default(0); // Stock reserved by customers
            $table->decimal('unit_price', 10, 2)->default(0); // Current selling price
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: one inventory record per shop/category/batch combination
            $table->unique(['shop_id', 'egg_category_id', 'batch_id'], 'inventory_unique');
            
            $table->index(['shop_id', 'egg_category_id']);
            $table->index('batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
