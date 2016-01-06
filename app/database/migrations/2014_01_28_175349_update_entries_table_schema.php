<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateEntriesTableSchema extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('entries', function($table)
        {
            $table->renameColumn('parentID', 'parent_id');
        });
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('entries', function($table)
        {
            $table->renameColumn('parent_id', 'parentID');
        });
	}

}
