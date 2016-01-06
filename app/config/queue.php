<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Default Queue Driver
	|--------------------------------------------------------------------------
	|
	| The Laravel queue API supports a variety of back-ends via an unified
	| API, giving you convenient access to each back-end using the same
	| syntax for each one. Here you may set the default queue driver.
	|
	| Supported: "sync", "beanstalkd", "sqs", "iron"
	|
	*/

	'default' => 'iron',

	/*
	|--------------------------------------------------------------------------
	| Environment Queue Postfix
	|--------------------------------------------------------------------------
	|
	| Allows for use of iron.io queues in same project, useful for testing
	| You should use a local/queue configuration file with your own name
	| or a unique team name if sharing queues across a project team
	|
	*/

	'postfix' => 'demo',

	/*
	|--------------------------------------------------------------------------
	| Queue Connections
	|--------------------------------------------------------------------------
	|
	| Here you may configure the connection information for each server that
	| is used by your application. A default configuration has been added
	| for each back-end shipped with Laravel. You are free to add more.
	|
	*/

	'connections' => array(

		'iron' => array(
			'driver'  => 'iron',
			'project' => '000000000000000000000000',
			'token'   => '0000-0000000000000000000000',
			'queue'   => 'default',
			'encrypt' => false,
		),

	),

	/*
	|--------------------------------------------------------------------------
	| Failed Queue Jobs
	|--------------------------------------------------------------------------
	|
	| These options configure the behavior of failed queue job logging so you
	| can control which database and table are used to store the jobs that
	| have failed. You may change them to any database / table you wish.
	|
	*/

	'failed' => array(

		'database' => 'dropbox', 'table' => 'failed_jobs',

	),

	/*
	|--------------------------------------------------------------------------
	| Job Payload Encryption
	|--------------------------------------------------------------------------
	|
	| These options configure encryption for queue payloads. This may not be
	| desirable when you use queues to communicate with applications outside
	| of laravel or for performance reasons.
	|
	*/

	'crypt' => array(
		'enable_payload_encryption' => false,
	),

);
