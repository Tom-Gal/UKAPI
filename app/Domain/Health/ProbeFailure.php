<?php

namespace App\Domain\Health;

use RuntimeException;

final class ProbeFailure extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('Health probe failed.');
    }
}
