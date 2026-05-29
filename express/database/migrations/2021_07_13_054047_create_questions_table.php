<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->integer('order');
            $table->integer('question_type_id');
            $table->longText('question');
            $table->json('setup')->nullable();
            $table->json('choices')->nullable();
            $table->json('rows')->nullable();
            $table->json('columns')->nullable();
            $table->json('items')->nullable();
            $table->integer('questionnaire_id');
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
        Schema::dropIfExists('questions');
    }
}
