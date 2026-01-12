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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable()->unique();
            $table->text('address')->nullable();
            $table->decimal('credit_limit', 10, 2)->default(0);
            $table->decimal('current_balance', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone');
            $table->index('is_active');
        });

        // Modify sales table - change customer_id to reference customers instead of users
        Schema::table('sales', function (Blueprint $table) {
            // Drop the old foreign key
            $table->dropForeign(['customer_id']);
            
            // Re-add with reference to customers table
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('customers')
                  ->nullOnDelete();
        });

        // Modify reservations table - change customer_id to reference customers instead of users
        Schema::table('reservations', function (Blueprint $table) {
            // Drop the old foreign key
            $table->dropForeign(['customer_id']);
            
            // Re-add with reference to customers table
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('customers')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });

        // Revert reservations table
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });

        Schema::dropIfExists('customers');
    }
};
