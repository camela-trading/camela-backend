<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_request_id')->nullable()->after('payment_method');
            $table->string('hitpay_reference')->nullable()->after('payment_request_id');
            $table->string('transaction_reference')->nullable()->after('hitpay_reference');
            $table->json('gateway_response')->nullable()->after('transaction_reference');
            $table->json('callback_response')->nullable()->after('gateway_response');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE orders
                MODIFY payment_status ENUM('UNPAID', 'PENDING', 'PAID', 'FAILED', 'CANCELLED', 'EXPIRED', 'REFUNDED')
                NOT NULL DEFAULT 'UNPAID'
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE orders
                MODIFY payment_status ENUM('UNPAID', 'PAID', 'FAILED', 'REFUNDED')
                NOT NULL DEFAULT 'UNPAID'
            ");
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_request_id',
                'hitpay_reference',
                'transaction_reference',
                'gateway_response',
                'callback_response',
            ]);
        });
    }
};
