<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateEntriesTableForServicesApi extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('entries', function($table)
        {
            $table->dropColumn('size');
            $table->dropColumn('icon');
            $table->dropColumn('root');
            $table->dropColumn('folder_hash');
        });

        Schema::table('entries', function($table)
        {
            $table->renameColumn('path', 'original_path'); // TODO: this needs to be type text instead of string(varchar)... fix soon
            $table->renameColumn('file_modified', 'service_updated_at');
            $table->renameColumn('client_modified', 'client_updated_at');
        });

        Schema::table('entries', function($table)
        {
            $table->string('service_created_at')->after('mime_type')->nullable();
            $table->string('client_created_at')->after('service_updated_at')->nullable();
            $table->string('file_sha')->after('mime_type')->nullable();
            $table->string('etag')->after('file_sha')->nullable();
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
            $table->dropColumn('etag');
            $table->dropColumn('file_sha');
            $table->dropColumn('client_created_at');
            $table->dropColumn('service_created_at');
        });

        Schema::table('entries', function($table)
        {
            $table->renameColumn('original_path', 'path');
            $table->renameColumn('service_updated_at', 'file_modified');
            $table->renameColumn('client_updated_at', 'client_modified');
        });

        Schema::table('entries', function($table)
        {
            $table->string('size')->after('rev');
            $table->string('icon')->after('bytes');
            $table->string('root')->after('mime_type');
            $table->string('folder_hash')->after('is_dir');
        });
	}

}
