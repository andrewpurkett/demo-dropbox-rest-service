<?php

class DropboxesTableSeeder extends Seeder {

    private $sync_queue_name = 'dropbox.sync';

    public function __construct() {
        if (Config::has('queue.postfix')) {
            $this->sync_queue_name .= '.' . Config::get('queue.postfix');
        }
    }

	public function run()
	{
		DB::table('entries')->truncate();
		DB::table('dropboxes')->truncate();

		$dropboxes = array(
			[
				'id' => 1,
				'dropbox_authorized_id' => 123456789,
				'dropbox_token' => 'NopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNope'
			]
		);

		DB::table('dropboxes')->insert($dropboxes);

		Queue::push('DropboxCrawlerController@dequeue', ['id' => 1], $this->sync_queue_name);
	}

}
