<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEntriesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entries', function(Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->bigInteger('tree_id')->unsigned()->index();
            $table->integer('parentID')->unsigned();
            $table->string('path'); // TODO: this needs to be type text instead of string(varchar)... fix soon
            $table->string('rev');
            $table->string('size');
            $table->integer('bytes');
            $table->string('icon');
            $table->string('mime_type');
            $table->string('root');
            $table->string('file_modified');
            $table->string('client_modified');
            $table->integer('is_dir');
            $table->string('folder_hash');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('entries');
    }

}
