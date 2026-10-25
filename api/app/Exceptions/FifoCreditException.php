<?php

namespace App\Exceptions;

use RuntimeException;

class FifoCreditException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorKey = 'fifo_credit_error',
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }
}
