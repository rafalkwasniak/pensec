<?php

use App\Support\ScanTime;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->timestamp('scanned_at')->nullable()->after('received_at');
        });

        // Reports already on file keep their scan date too, so the column is
        // not blank for everything collected before it existed. Documents are
        // tens of kilobytes, so decoding them all costs nothing worth saving.
        DB::table('reports')
            ->join('report_payloads', 'report_payloads.report_id', '=', 'reports.id')
            ->orderBy('reports.id')
            ->select('reports.id', 'report_payloads.payload')
            ->chunk(100, function ($rows): void {
                foreach ($rows as $row) {
                    $document = json_decode($row->payload, true);
                    $scannedAt = ScanTime::parse(is_array($document) ? ($document['scan_time'] ?? null) : null);

                    if ($scannedAt !== null) {
                        DB::table('reports')->where('id', $row->id)->update(['scanned_at' => $scannedAt]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropColumn('scanned_at');
        });
    }
};
