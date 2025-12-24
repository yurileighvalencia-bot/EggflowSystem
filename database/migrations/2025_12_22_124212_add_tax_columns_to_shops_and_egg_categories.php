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
        Schema::table('shops', function (Blueprint $table) {
            // Default tax rate stored as decimal (0.12 = 12%)
            // Using (5,4) precision for rates like 0.0525 (5.25%)
            $table->decimal('default_tax_rate', 5, 4)->default(0.0000)->after('address');
        });

        Schema::table('egg_categories', function (Blueprint $table) {
            // Philippine TRAIN Law: Agricultural products are VAT-exempt
            // Default true since most eggs are exempt
            $table->boolean('is_tax_exempt')->default(true)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('default_tax_rate');
        });

        Schema::table('egg_categories', function (Blueprint $table) {
            $table->dropColumn('is_tax_exempt');
        });
    }
};
