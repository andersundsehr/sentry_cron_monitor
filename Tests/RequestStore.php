<?php

declare(strict_types=1);

namespace AUS\SentryCronMonitor\Tests;

final class RequestStore
{
    /**
     * @var list<array{uri: string, method: string, options: array<string, mixed>, context: string|null}>
     */
    public array $requests = [];
}
