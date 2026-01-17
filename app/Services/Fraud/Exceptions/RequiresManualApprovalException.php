<?php

namespace App\Services\Fraud\Exceptions;

use Exception;

class RequiresManualApprovalException extends Exception
{
    public function __construct(
        public readonly string $ruleType,
        public readonly array $details = [],
        string $message = 'Manual approval required.'
    ) {
        parent::__construct($message);
    }
}
