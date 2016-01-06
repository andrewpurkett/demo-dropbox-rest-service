<?php

class EntriesTableSeeder extends Seeder {

	public function run()
	{
		// Uncomment the below to wipe the table clean before populating
		DB::table('entries')->truncate();

		$entries = array(
			[
				'id' => 1,
				'dropbox_id' => 1,
				'parent_id' => 0,
				'path' => '/',
				'rev' => null,
				'size' => '0 bytes',
				'bytes' => 0,
				'icon' => 'folder',
				'mime_type' => '',
				'root' => 'dropbox',
				'file_modified' => '',
				'client_modified' => '',
				'is_dir' => 1,
				'folder_hash' => 'ffffffffffffffffffffffffffffffff',
			],
		);

		// Uncomment the below to run the seeder
		DB::table('entries')->insert($entries);
	}

}
