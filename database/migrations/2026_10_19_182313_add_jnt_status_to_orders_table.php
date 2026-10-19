<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('jnt_status')->nullable()->after('jnt_sorting_code');
            $table->timestamp('jnt_status_updated_at')->nullable()->after('jnt_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['jnt_status', 'jnt_status_updated_at']);
        });
    }
};