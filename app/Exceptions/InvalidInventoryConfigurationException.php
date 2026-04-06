<?php

namespace App\Exceptions;

use Exception;

class InvalidInventoryConfigurationException extends Exception
{
    /**
     * @param array<int, array<string, int|string>> $issues
     */
    public function __construct(
        public array $issues,
        string $message = 'Inventory configuration is invalid for one or more products.'
    ) {
        parent::__construct($message);
    }
}
