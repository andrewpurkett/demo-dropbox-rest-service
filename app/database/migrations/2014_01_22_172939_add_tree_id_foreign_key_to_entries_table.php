<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTreeIdForeignKeyToEntriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('entries', function(Blueprint $table)
        {
            $table->foreign('tree_id')
                  ->references('id')
                  ->on('trees')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
          });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('entries', function(Blueprint $table)
        {
        	$table->dropForeign('entries_tree_id_foreign');
        });
	}

}