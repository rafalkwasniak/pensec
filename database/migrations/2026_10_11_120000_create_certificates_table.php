<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            // Public, unguessable handle shown on the certificate and used in
            // its URL - that URL is also how the certificate is verified, so a
            // made-up id simply does not resolve.
            $table->string('public_id', 24)->unique();
            $table->string('holder_name');
            $table->unsignedTinyInteger('score');          // percent
            $table->unsignedSmallInteger('correct_count');
            $table->unsignedSmallInteger('total_count');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
