<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payloads live apart from report metadata so that listings, status lookups
     * and the panel never drag megabytes of raw JSON along with them.
     */
    public function up(): void
    {
        Schema::create('report_payloads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_payloads');
    }
};
