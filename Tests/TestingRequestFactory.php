<?php

namespace AUS\SentryCronMonitor\Tests;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

final class TestingRequestFactory extends RequestFactory
{
    /**
     * @var list<array{uri: string, method: string, options: array<string, mixed>, context: string|null}>
     */
    public array $requests = [];

    /**
     * @param array<ResponseInterface> $responses
     */
    public function __construct(private array $responses)
    {
    }

    /**
     * @param array<string, string>|array $options
     */
    #[Override]
    public function request(string $uri, string $method = 'GET', array $options = [], ?string $context = null): ResponseInterface
    {
        $this->requests[] = [
            'uri' => $uri,
            'method' => $method,
            'options' => $options,
        ];
        return array_shift($this->responses) ?? throw new \Exception('to many requests made for this test.', 3414962574);
    }
}
