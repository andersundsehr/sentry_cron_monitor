<?php

namespace AUS\SentryCronMonitor\Service;

use Sentry\Dsn;
use Sentry\SentrySdk;

class DsnService
{
    public function __construct(private ?Dsn $dsn = null)
    {

    }

    public function provideUrl(string $orgName): string
    {
        $this->dsn ??= SentrySdk::getCurrentHub()->getClient()?->getOptions()?->getDsn() ??
            throw new \RuntimeException('Sentry is not initialized');

        return $this->dsn->getScheme() . '://' . $this->dsn->getHost() . '/api/0/projects/' . $orgName . '/' . $this->dsn->getProjectId() . '/rules/';
    }
}
