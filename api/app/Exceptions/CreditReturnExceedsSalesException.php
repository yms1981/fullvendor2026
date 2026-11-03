<?php

namespace App\Exceptions;

use RuntimeException;

class CreditReturnExceedsSalesException extends RuntimeException
{
    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function __construct(public array $items)
    {
        parent::__construct('return_exceeds_sales');
    }
}
