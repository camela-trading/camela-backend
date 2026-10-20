<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('nonmember_price_without_gst', 10, 2)->nullable()->after('price_basis');
            $table->decimal('nonmember_price_with_gst', 10, 2)->nullable()->after('nonmember_price_without_gst');
            $table->enum('nonmember_price_basis', ['without_gst', 'with_gst'])->default('without_gst')->after('nonmember_price_with_gst');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'nonmember_price_without_gst',
                'nonmember_price_with_gst',
                'nonmember_price_basis',
            ]);
        });
    }
};