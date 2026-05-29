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
            $table->string('participant_id')->nullable();
            $table->foreignId('questionnaire_id')->nullable();
            $table->string('status', 10)->nullable();
            $table->json('agent')->nullable();
            $table->json('url_data')->nullable();
            $table->json('responses')->nullable();
            $table->json('history')->nullable();
            $table->json('config_data')->nullable();
            $table->timestamp('start_time_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('questionnaire_id');
            $table->index('participant_id');
            $table->index('status');
            $table->index(['questionnaire_id', 'participant_id']);
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
