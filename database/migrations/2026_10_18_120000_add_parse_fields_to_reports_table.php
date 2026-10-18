<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parsing writes a small summary beside the report and a timestamp of when
     * it finished. The lifecycle itself rides the existing `status` column
     * (ReportStatus grew with the parsing values); these are only the result.
     *
     * The summary is the overview the panel shows without reading the document:
     * severity counts, totals, the plan, the personal-data flag, the scan
     * outcome and the coverage gaps. The individual findings live in their own
     * table so they can be filtered; this is the header above that list.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->timestamp('parsed_at')->nullable()->after('scanned_at');
            $table->string('parse_failure_reason')->nullable()->after('parsed_at');
            $table->json('summary')->nullable()->after('parse_failure_reason');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropColumn(['parsed_at', 'parse_failure_reason', 'summary']);
        });
    }
};
