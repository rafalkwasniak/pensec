<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The facts a narrative was written from, kept with it. The PDF renders its
     * tables from this rather than re-deriving them, so the prose and the
     * figures beside it always come from the same reading of the report - and a
     * download never has to read a document of hundreds of megabytes again.
     */
    public function up(): void
    {
        Schema::table('report_narratives', function (Blueprint $table): void {
            $table->longText('facts')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('report_narratives', function (Blueprint $table): void {
            $table->dropColumn('facts');
        });
    }
};
