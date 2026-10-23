<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidStateTransitionException extends RuntimeException
{
    public function __construct(string $fromStatus, string $toStatus, ?string $reason = null)
    {
        $message = "Cannot transition policy request from state [{$fromStatus}] to [{$toStatus}]";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }
        parent::__construct($message);
    }
}
