<?php

namespace App\Exceptions;

class ApiGenericException extends ApiException
{

    const NO_RECORDS = 204;
    const NO_CONTENT = 204;
    public function __construct($message, $status = 500, $errors = [])
    {
        parent::__construct($message, $status, $errors);
    }
}