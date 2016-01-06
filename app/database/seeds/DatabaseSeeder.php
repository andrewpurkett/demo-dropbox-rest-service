<?php

class DatabaseSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		Eloquent::unguard();

		// $this->call('UserTableSeeder');
		DB::statement('SET FOREIGN_KEY_CHECKS=0;');
		$this->call('DropboxesTableSeeder');
		// $this->call('EntriesTableSeeder');
		DB::statement('SET FOREIGN_KEY_CHECKS=1;');

		$this->call('MailboxesTableSeeder');
		$this->call('AttachmentsTableSeeder');
	}

}