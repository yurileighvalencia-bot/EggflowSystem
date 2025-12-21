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
        Schema::create('wastage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('delivery_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('egg_category_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->enum('source', ['shop_spoilage', 'delivery_rejection', 'batch_expired', 'inventory_adjustment', 'other'])->default('other');
            $table->string('reason'); // Free-form description
            $table->foreignId('logged_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('logged_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'logged_at']);
            $table->index(['batch_id', 'source']);
            $table->index('delivery_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wastage_logs');
    }
};
