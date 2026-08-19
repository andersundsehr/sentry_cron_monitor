<?php

declare(strict_types=1);

namespace AUS\SentryCronMonitor\Tests;

use Exception;
use Override;
use Psr\Http\Message\ResponseInterface;

use function array_shift;

trait TestingRequestFactoryTrait
{
    public readonly RequestStore $requestStore;

    private readonly ResponseStore $responseStore;

    /**
     * @param array<ResponseInterface> $responses
     */
    public function __construct(array $responses)
    {
        $this->requestStore = new RequestStore();
        $this->responseStore = new ResponseStore($responses);
    }

    /**
     * @param array<string, string>|array<string, mixed> $options
     */
    public function request(string $uri, string $method = 'GET', array $options = [], ?string $context = null): ResponseInterface
    {
        $this->requestStore->requests[] = [
            'uri' => $uri,
            'method' => $method,
            'options' => $options,
            'context' => $context,
        ];
        return array_shift($this->responseStore->responses) ?? throw new Exception('to many requests made for this test.', 3414962574);
    }
}
