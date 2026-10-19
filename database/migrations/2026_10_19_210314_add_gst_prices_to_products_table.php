<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->decimal('price_without_gst', 10, 2)->nullable()->after('price');
        $table->decimal('price_with_gst', 10, 2)->nullable()->after('price_without_gst');
        $table->enum('price_basis', ['without_gst', 'with_gst'])->default('without_gst')->after('price_with_gst');
    });

    // backfill existing products
    DB::table('products')->update(['price_without_gst' => DB::raw('price')]);
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn(['price_without_gst', 'price_with_gst', 'price_basis']);
    });
}
};
