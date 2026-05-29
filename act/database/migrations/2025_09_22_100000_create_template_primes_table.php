<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_primes', function (Blueprint $table) {
            $table->id();
			$table->foreignId('team_id');
            $table->foreignId('project_id');
            $table->foreignId('survey_id');
            $table->foreignId('user_id');
            $table->string('name', 64);
            $table->json('data');
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_primes');
    }
};