<?php

declare(strict_types=1);

namespace AUS\SentryCronMonitor\Tests;

use Psr\Http\Message\ResponseInterface;
use Throwable;

final class ResponseStore
{
    /**
     * @param array<ResponseInterface|Throwable> $responses
     */
    public function __construct(public array $responses)
    {
    }
}
