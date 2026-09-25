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
        Schema::create('products', function (Blueprint $table) {

            $table->id();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('title');

            $table->string('slug')->unique();

            $table->string('sku')->unique();

            $table->string('short_description')->nullable();

            $table->longText('description');

            $table->decimal('price',10,2);

            $table->decimal('compare_price',10,2)->nullable();

            $table->decimal('cost_price',10,2)->nullable();

            $table->integer('stock')->default(0);

            $table->integer('low_stock_alert')->default(5);

            $table->decimal('weight',8,2)->nullable();

            $table->enum('status',[

                'ACTIVE',

                'INACTIVE',
                
            ])->default('INACTIVE');

            $table->boolean('featured')->default(false);

            $table->string('seo_title')->nullable();

            $table->text('seo_description')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
