<?php

namespace Mocavo;

class PrettyJson implements \Illuminate\Support\Contracts\JsonableInterface {

	public $content;

	public function __construct($data = array())
	{
		$this->content = $data;
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		if ($options != 0)
		{
			throw new BadMethodCallException("Options are not implemented, unknown method call argument");
		}

		return json_encode($this->content, JSON_PRETTY_PRINT);
	}

}