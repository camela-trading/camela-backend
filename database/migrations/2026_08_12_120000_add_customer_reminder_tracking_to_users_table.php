<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('verification_reminder_sent_at')->nullable();
            $table->timestamp('cart_activity_at')->nullable();
            $table->timestamp('abandoned_cart_reminder_activity_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'verification_reminder_sent_at',
                'cart_activity_at',
                'abandoned_cart_reminder_activity_at',
            ]);
        });
    }
};
