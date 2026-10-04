<?php

use App\Support\JsonOutline;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Report documents move out of the database into gzip files on disk.
     *
     * A row has to be sent to MariaDB whole - capped by max_allowed_packet and
     * held in PHP memory while it goes - and scanner output now runs to hundreds
     * of megabytes. A file is written and read as a stream, and gzip keeps it at
     * about an eighth of the size.
     *
     * Nothing is deleted: report_payloads stays as it is, as a copy to fall back
     * on, and is dropped by a later migration once the files have proved out.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->string('payload_path')->nullable()->after('payload_sha256');
            $table->string('payload_format', 16)->default('report')->after('payload_path');
        });

        $disk = Storage::disk('local');

        DB::table('report_payloads')
            ->join('reports', 'reports.id', '=', 'report_payloads.report_id')
            ->select('report_payloads.id', 'reports.id as report_id', 'reports.report_uid', 'report_payloads.payload')
            ->lazyById(1, 'report_payloads.id', 'id')
            ->each(function (object $row) use ($disk): void {
                $path = 'reports/'.$row->report_uid.'.json.gz';

                $disk->put($path, gzencode($row->payload, 6));

                // Between 2026-10-01 and this migration the body was stored as
                // sent, envelope and all. Those rows are submissions already.
                $envelope = count(JsonOutline::members($row->payload, ['report_id', 'report'])) === 2;

                DB::table('reports')->where('id', $row->report_id)->update([
                    'payload_path' => $path,
                    'payload_format' => $envelope ? 'submission' : 'report',
                ]);
            });
    }

    /**
     * Reports stored after this migration exist only as files, so they are
     * copied back into report_payloads before the columns pointing at the files
     * disappear. They go back as sent, envelope included.
     */
    public function down(): void
    {
        $disk = Storage::disk('local');

        DB::table('reports')
            ->leftJoin('report_payloads', 'report_payloads.report_id', '=', 'reports.id')
            ->whereNull('report_payloads.id')
            ->whereNotNull('reports.payload_path')
            ->select('reports.id', 'reports.payload_path')
            ->lazyById(1, 'reports.id', 'id')
            ->each(function (object $row) use ($disk): void {
                DB::table('report_payloads')->insert([
                    'report_id' => $row->id,
                    'payload' => gzdecode($disk->get($row->payload_path)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('reports', function (Blueprint $table): void {
            $table->dropColumn(['payload_path', 'payload_format']);
        });
    }
};
