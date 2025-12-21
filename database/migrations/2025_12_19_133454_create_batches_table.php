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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('egg_category_id')->constrained()->cascadeOnDelete();
            $table->string('batch_code')->unique(); // Auto-generated: FARM-CAT-YYYYMMDD-XXX
            $table->date('collection_date');
            $table->date('expires_at')->nullable(); // Based on egg shelf life
            $table->integer('initial_quantity')->default(0); // Total collected in this batch
            $table->integer('current_quantity')->default(0); // Remaining after sales/wastage
            $table->enum('status', ['active', 'depleted', 'expired', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['farm_id', 'collection_date']);
            $table->index(['egg_category_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
