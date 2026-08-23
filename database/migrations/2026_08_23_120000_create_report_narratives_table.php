<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per report per variant. The narrative lives here rather than on
     * `reports` for the same reason the payload does: listings must not drag
     * pages of generated prose along with them.
     *
     * The unique index on (report_id, variant) is what makes queueing safe -
     * two clicks on the same button cannot produce two jobs writing two answers.
     */
    public function up(): void
    {
        Schema::create('report_narratives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('variant', 16);
            $table->string('status', 16)->index();

            // The model's answer, as the markdown it was asked to produce.
            $table->longText('content')->nullable();

            // What produced it, so a document can always be traced back to the
            // model that wrote it even after the config moves on.
            $table->string('model', 64)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('generated_at')->nullable();

            $table->timestamps();

            $table->unique(['report_id', 'variant']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_narratives');
    }
};
