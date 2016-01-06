<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class RenameTreesTableToDropboxes extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trees', function($table)
        {
            $table->renameColumn('user_id', 'dropbox_id');
            $table->renameColumn('token', 'dropbox_token');
        });
        Schema::rename('trees', 'dropboxes');
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dropboxes', function($table)
        {
            $table->renameColumn('dropbox_id', 'user_id');
            $table->renameColumn('dropbox_token', 'token');
        });
        Schema::rename('dropboxes', 'trees');
    }

}
