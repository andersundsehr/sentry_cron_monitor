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
        $dsn = $this->getDsn();

        return $dsn->getScheme() . '://' . $dsn->getHost() . '/api/0/projects/' . $orgName . '/' . $dsn->getProjectId() . '/rules/';
    }

    public function provideSentry(): string
    {
        $dsn = $this->getDsn();

        return $dsn->getScheme() . '://' . $dsn->getHost();
    }

    public function provideMonitorsUrl(string $orgName): string
    {
        $dsn = $this->getDsn();

        return $this->provideSentry()
            . '/api/0/organizations/' . rawurlencode($orgName)
            . '/monitors/?project=' . rawurlencode($dsn->getProjectId());
    }

    public function provideMonitorUrl(string $orgName, string $monitorId): string
    {
        return $this->provideSentry()
            . '/api/0/organizations/' . rawurlencode($orgName)
            . '/monitors/' . rawurlencode($monitorId) . '/';
    }

    public function getDsn(): Dsn
    {
        return $this->dsn ??= SentrySdk::getCurrentHub()->getClient()?->getOptions()?->getDsn() ??
            throw new RuntimeException('Sentry is not initialized', 6020020999);
    }
}
