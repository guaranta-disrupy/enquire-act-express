<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name',255);
            $table->integer('question_type_id');
            $table->json('setup')->nullable();
            $table->json('choices')->nullable();
            $table->json('rows')->nullable();
            $table->json('colums')->nullable();
            $table->json('items')->nullable();
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
        Schema::dropIfExists('question_templates');
    }
}
