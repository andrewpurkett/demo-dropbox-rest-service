pretty-json
===========

Implements an Illuminate JsonableInterface

Include in your composer file, add to your require array:


	"require": {

		...
		"mocavo/pretty-json": "dev-master",
		...

	},

and then add an entry to your repository array, or create one if you don't have one:


	"repositories": [
	    {
	        "type": "vcs",
	        "url": "https://github.com/mocavo/pretty-json"
	    }
	],

This bypasses the need to add the package to packagist, etc.

Add to your app config aliases array:


	'aliases' => array(

		...
		'PrettyJSON' => 'Mocavo\PrettyJson',
		...

	),


To utilize the new pretty json output, when returning JSON, instead of using

	return Response::json($dataArray);

or

	return $dataArray;

You will use

	return new PrettyJSON($dataArray);
