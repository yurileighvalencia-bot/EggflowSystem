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
        Schema::create('egg_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Small, Medium, Large, Cracked, etc.
            $table->string('code')->unique(); // SM, MD, LG, CR
            $table->text('description')->nullable();
            $table->integer('low_stock_threshold')->default(100); // Minimum quantity before alert
            $table->integer('restock_quantity')->default(1000); // Standard order quantity
            $table->decimal('default_price', 10, 2)->default(0); // Default selling price per unit
            $table->integer('sort_order')->default(0); // Display order
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('egg_categories');
    }
};
