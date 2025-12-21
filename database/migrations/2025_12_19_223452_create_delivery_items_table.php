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
        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('egg_category_id')->constrained()->cascadeOnDelete();
            $table->integer('qty_sent')->default(0);
            $table->integer('qty_received')->nullable(); // NULL = not yet confirmed
            $table->integer('qty_rejected')->default(0);
            $table->string('rejection_reason')->nullable(); // cracked, spoiled, wrong_size, other
            $table->timestamps();
            $table->softDeletes();

            $table->index(['delivery_id', 'batch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_items');
    }
};
