<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('landing_page')->nullable()->after('image');
            $table->string('banner')->nullable()->after('landing_page');
            $table->json('images')->nullable()->after('banner');
            $table->unsignedInteger('sort_order')->default(0)->after('images');
            $table->string('seo_title')->nullable()->after('sort_order');
            $table->text('seo_description')->nullable()->after('seo_title');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['landing_page', 'banner', 'images', 'sort_order', 'seo_title', 'seo_description']);
        });
    }
};
