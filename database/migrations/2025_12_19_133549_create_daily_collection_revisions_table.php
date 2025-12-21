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
        Schema::create('daily_collection_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->jsonb('old_values'); // Previous values as JSON
            $table->jsonb('new_values'); // New values as JSON
            $table->string('reason'); // Why the change was made
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index('daily_collection_id');
            $table->index('changed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_collection_revisions');
    }
};
