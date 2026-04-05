<?php

namespace App\Exceptions;

use Exception;

class InsufficientMaterialStockException extends Exception
{
    /**
     * @param array<int, array<string, int|string>> $shortages
     */
    public function __construct(
        public array $shortages,
        string $message = 'Insufficient material stock for this order.'
    ) {
        parent::__construct($message);
    }
}
