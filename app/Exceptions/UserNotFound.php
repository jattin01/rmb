<?php

namespace App\Exceptions;

class UserNotFound extends ApiException
{
    protected $status = 404;

    public function __construct($message, array $detail = [])
    {
        parent::__construct($message, $this->status, $detail);
    }
}
