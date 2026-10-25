<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('utm_source', 100)->nullable()->after('message');
            $table->string('utm_medium', 100)->nullable()->after('utm_source');
            $table->string('utm_campaign', 100)->nullable()->after('utm_medium');
            $table->string('referrer', 255)->nullable()->after('utm_campaign');
            $table->string('landing_page', 255)->nullable()->after('referrer');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn(['utm_source', 'utm_medium', 'utm_campaign', 'referrer', 'landing_page']);
        });
    }
};
