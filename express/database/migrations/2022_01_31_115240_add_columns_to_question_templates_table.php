<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToQuestionTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_templates', function (Blueprint $table) {
            $table->longText('question')->after('name');
            $table->json('items1')->nullable()->after('items');
            $table->json('items2')->nullable()->after('items1');
            $table->json('values')->nullable()->after('items2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('question_templates', function (Blueprint $table) {
            //
        });
    }
}
