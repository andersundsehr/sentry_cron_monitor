<?php

namespace AUS\SentryCronMonitor\Service;

use RuntimeException;
use Sentry\Dsn;
use Sentry\SentrySdk;

class DsnService
{
    public function __construct(private ?Dsn $dsn = null)
    {
    }

    public function provideUrl(string $orgName): string
    {
        $this->getDsn();

        return $this->dsn->getScheme() . '://' . $this->dsn->getHost() . '/api/0/projects/' . $orgName . '/' . $this->dsn->getProjectId() . '/rules/';
    }

    public function provideSentry(): string {
        $this->getDsn();
        return $this->dsn->getScheme() . '://' . $this->dsn->getHost();
    }

    /**
     * @return void
     */
    public function getDsn(): void
    {
        $this->dsn ??= SentrySdk::getCurrentHub()->getClient()?->getOptions()?->getDsn() ??
            throw new RuntimeException('Sentry is not initialized', 6020020999);
    }
}
