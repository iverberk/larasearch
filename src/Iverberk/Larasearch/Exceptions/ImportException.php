<?php namespace Iverberk\Larasearch\Exceptions;

use Exception;

class ImportException extends Exception {

    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, $errorItems = [])
    {
        // make sure everything is assigned properly
        parent::__construct($message, $code);
    }

}
