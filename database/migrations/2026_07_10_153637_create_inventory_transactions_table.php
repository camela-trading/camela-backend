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
        Schema::create('inventory_transactions', function (Blueprint $table) {

        $table->id();

        $table->foreignId('product_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->foreignId('user_id')
            ->nullable()
            ->constrained()
            ->nullOnDelete();

        $table->enum('type', [
            'STOCK_IN',
            'STOCK_OUT',
            'ADJUSTMENT',
            'SALE',
            'RETURN'
        ]);

        $table->integer('quantity');

        $table->integer('stock_before');

        $table->integer('stock_after');

        $table->text('remarks')->nullable();

        $table->timestamps();

        $table->index('product_id');
        $table->index('type');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
