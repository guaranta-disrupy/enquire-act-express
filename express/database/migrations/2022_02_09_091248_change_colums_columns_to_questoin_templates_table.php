<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumsColumnsToQuestoinTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_templates', function (Blueprint $table) {
            $table->renameColumn('colums', 'columns');
            $table->renameColumn('items1', 'item1');
            $table->renameColumn('items2', 'item2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questoin_templates', function (Blueprint $table) {
            //
        });
    }
}
