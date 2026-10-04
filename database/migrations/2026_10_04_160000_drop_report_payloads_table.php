<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The documents live in gzip files since 2026_10_04_120000; this table was
     * kept as a fallback copy until the files had proved out.
     */
    public function up(): void
    {
        Schema::dropIfExists('report_payloads');
    }

    /**
     * Brings the table back empty. The documents stay in their files; the
     * earlier migration's down() copies them in if it is rolled back too.
     */
    public function down(): void
    {
        Schema::create('report_payloads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->timestamps();
        });
    }
};
