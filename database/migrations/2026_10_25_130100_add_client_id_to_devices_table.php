<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which client owns a device. Nullable, so a device can sit unassigned, and
     * nullOnDelete so removing a client only unassigns their devices - the
     * reports those devices produced are kept (they are restrictOnDelete on the
     * device, untouched here).
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
