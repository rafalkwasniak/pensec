<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('device_id')->constrained()->restrictOnDelete();
            $table->uuid('report_uid')->unique();
            $table->string('status', 16);
            $table->timestamp('received_at');
            $table->unsignedBigInteger('payload_bytes');
            $table->char('payload_sha256', 64);
            $table->string('source_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['device_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
