<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('store_settings', 'notify_order_delivered')) {
                $table->boolean('notify_order_delivered')->default(true)->after('notify_new_customer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            if (Schema::hasColumn('store_settings', 'notify_order_delivered')) {
                $table->dropColumn('notify_order_delivered');
            }
        });
    }
};
