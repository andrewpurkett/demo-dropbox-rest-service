<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class RenameEntriesTableTreeIdToDropboxId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('entries', function($table)
        {
    		$table->dropForeign('entries_tree_id_foreign');
    	});

        Schema::table('entries', function($table)
        {
            $table->renameColumn('tree_id', 'dropbox_id');
        });

        Schema::table('entries', function(Blueprint $table)
        {
            $table->foreign('dropbox_id')
                  ->references('id')
                  ->on('dropboxes')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });

        Schema::table('dropboxes', function($table)
        {
            $table->renameColumn('dropbox_id', 'dropbox_authorized_id');
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
    		$table->dropForeign('entries_dropbox_id_foreign');
        });

        Schema::table('entries', function($table)
        {
            $table->renameColumn('dropbox_id', 'tree_id');
        });

        Schema::table('entries', function(Blueprint $table)
        {
            $table->foreign('tree_id')
                  ->references('id')
                  ->on('dropboxes')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });

        Schema::table('dropboxes', function($table)
        {
            $table->renameColumn('dropbox_authorized_id', 'dropbox_id');
        });
	}

}
