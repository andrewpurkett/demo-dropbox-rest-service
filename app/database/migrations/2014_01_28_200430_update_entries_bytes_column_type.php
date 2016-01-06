<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateEntriesBytesColumnType extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('entries', function($table)
        {
            $table->dropColumn('bytes');
        });
        Schema::table('entries', function($table)
        {
            $table->bigInteger('bytes')->unsigned()->after('size');
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
            $table->dropColumn('bytes');
        });
        Schema::table('entries', function($table)
        {
            $table->integer('bytes')->after('size');
        });
	}

}
