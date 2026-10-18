<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per finding derived from a report by ReportFacts. Kept off the
     * `reports` row (like the payload and the narratives) so a listing never
     * drags thousands of findings along, and so the panel can filter and search
     * them by level, source and host.
     *
     * A finding has no natural id in the facts array, so `fingerprint` is a hash
     * of (level, source, title, ip, where). The unique index on
     * (report_id, fingerprint) makes a re-parse idempotent: the same document
     * yields the same rows rather than duplicating them.
     */
    public function up(): void
    {
        Schema::create('report_findings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('level', 16)->index();
            $table->string('title');
            $table->string('ip', 45)->nullable()->index();
            $table->string('where')->nullable();
            $table->string('source')->index();
            $table->json('cves');
            $table->boolean('confirmed')->default(false);
            $table->text('note')->nullable();
            $table->char('fingerprint', 64);
            $table->timestamps();

            $table->unique(['report_id', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_findings');
    }
};
