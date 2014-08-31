<?php namespace Iverberk\Larasearch\Exceptions;

use Exception;

class ImportException extends Exception {

	// Redefine the exception so message isn't optional
	public function __construct($message, $code = 0, $errorItems = [])
	{
		// TODO: Handle the error items in a graceful way

		// make sure everything is assigned properly
		parent::__construct($message, $code);
	}

} 