<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMailboxesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('mailboxes', function(Blueprint $table) {
			$table->bigInteger('id')->unsigned()->primary();
			$table->string('mailbox_id')->unique();
			$table->text('email_address');
			$table->string('service');
			$table->timestamp('last_synced_at');
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
		Schema::drop('mailboxes');
	}

}
