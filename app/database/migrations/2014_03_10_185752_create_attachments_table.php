<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAttachmentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('attachments', function(Blueprint $table) {
			$table->bigIncrements('id')->unsigned();
			$table->bigInteger('mailbox_id')->unsigned();
			$table->string('attachment_id');
			$table->text('original_path');
			$table->bigInteger('bytes')->unsigned();
			$table->string('mime_type');
			$table->string('file_sha')->nullable();
			$table->string('etag')->nullable();
			$table->string('service_created_at')->nullable();
			$table->string('service_updated_at')->nullable();
			$table->string('client_created_at')->nullable();
			$table->string('client_updated_at')->nullable();
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
		Schema::drop('attachments');
	}

}
