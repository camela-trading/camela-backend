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
       Schema::create('store_settings', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Store Information
            |--------------------------------------------------------------------------
            */

            $table->string('store_name');

            $table->string('tagline')->nullable();

            $table->string('support_email');

            $table->string('phone')->nullable();

            $table->text('address')->nullable();

            $table->string('logo')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Shipping
            |--------------------------------------------------------------------------
            */

            $table->decimal('standard_shipping',10,2)->default(5.99);

            $table->decimal('express_shipping',10,2)->default(12.99);

            $table->decimal('overnight_shipping',10,2)->default(24.99);

            $table->decimal('free_shipping_threshold',10,2)->default(75);

            /*
            |--------------------------------------------------------------------------
            | Tax
            |--------------------------------------------------------------------------
            */

            $table->decimal('tax_rate',5,2)->default(10);

            /*
            |--------------------------------------------------------------------------
            | Inventory
            |--------------------------------------------------------------------------
            */

            $table->integer('default_low_stock_threshold')->default(5);

            /*
            |--------------------------------------------------------------------------
            | Maintenance
            |--------------------------------------------------------------------------
            */

            $table->boolean('maintenance_mode')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Notifications
            |--------------------------------------------------------------------------
            */

            $table->boolean('notify_new_order')->default(true);

            $table->boolean('notify_low_stock')->default(true);

            $table->boolean('notify_new_customer')->default(true);

            $table->boolean('notify_order_delivered')->default(true);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};

