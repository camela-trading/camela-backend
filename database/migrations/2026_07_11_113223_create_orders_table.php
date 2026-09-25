<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('order_number')->unique();

            $table->decimal('subtotal',12,2);

            $table->decimal('shipping_fee',12,2)
                ->default(0);

            $table->decimal('discount',12,2)
                ->default(0);

            $table->decimal('tax',12,2)
                ->default(0);

            $table->decimal('grand_total',12,2);

            $table->enum('payment_status',[
                'UNPAID',
                'PAID',
                'FAILED',
                'REFUNDED'
            ])->default('UNPAID');

            $table->enum('order_status',[
                'PENDING',
                'PROCESSING',
                'SHIPPED',
                'DELIVERED',
                'CANCELLED'
            ])->default('PENDING');

            $table->string('payment_method')
                ->nullable();

            $table->string('payment_reference')
                ->nullable();

            $table->timestamp('paid_at')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};