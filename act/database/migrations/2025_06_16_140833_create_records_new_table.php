<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('records_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id');
            $table->json('responses')->nullable();
            $table->string('participant_id')->nullable();
            $table->json('info')->nullable();
            $table->enum('status', ['complete', 'test'])->default('complete');
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->index('survey_id');
            $table->index('participant_id');
            $table->index(['survey_id', 'participant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('records_new');
    }
};
