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
        Schema::create('delivery_discrepancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_item_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('qty_sent')->default(0);
            $table->integer('qty_received')->default(0);
            $table->integer('qty_rejected')->default(0);
            $table->integer('qty_missing'); // sent - (received + rejected)
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('investigated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resolution')->nullable(); // approved, rejected, partial_loss, other
            $table->text('notes')->nullable();
            $table->timestamp('reported_at');
            $table->timestamp('investigated_at')->nullable();
            $table->timestamps();

            $table->index('delivery_id');
            $table->index('reported_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_discrepancies');
    }
};
