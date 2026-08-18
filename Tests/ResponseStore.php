<?php

declare(strict_types=1);

namespace AUS\SentryCronMonitor\Tests;

use Psr\Http\Message\ResponseInterface;

final class ResponseStore
{
    /**
     * @param array<ResponseInterface> $responses
     */
    public function __construct(public array $responses)
    {
    }
}
