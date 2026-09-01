<?php

declare(strict_types=1);

namespace AUS\SentryCronMonitor\Tests\Unit\Service;

use AUS\SentryCronMonitor\Service\DsnService;
use AUS\SentryCronMonitor\Service\MutedMonitorService;
use AUS\SentryCronMonitor\Tests\TestingRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentry\Dsn;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;

final class MutedMonitorServiceTest extends TestCase
{
    #[Test]
    public function findMutedMonitorsReadsAllPagesAndFiltersActiveMonitors(): void
    {
        $nextPageUrl = 'https://example.com/api/0/organizations/orgName/monitors/?project=42&cursor=next';
        $requestFactory = new TestingRequestFactory([
            new JsonResponse(
                [
                    ['id' => '1', 'slug' => 'active-monitor', 'name' => 'Active monitor', 'isMuted' => false],
                    ['id' => '2', 'slug' => 'muted-monitor', 'name' => 'Muted monitor', 'isMuted' => true],
                ],
                200,
                ['Link' => '<' . $nextPageUrl . '>; rel="next"; results="true"'],
            ),
            new JsonResponse([
                ['id' => 3, 'slug' => 'legacy-muted-monitor', 'is_muted' => true],
            ]),
        ]);
        $service = $this->createService($requestFactory);

        self::assertSame(
            [
                ['id' => '2', 'slug' => 'muted-monitor', 'name' => 'Muted monitor'],
                ['id' => '3', 'slug' => 'legacy-muted-monitor', 'name' => 'legacy-muted-monitor'],
            ],
            $service->findMutedMonitors(),
        );
        self::assertSame(
            [
                $this->expectedRequest(
                    'https://example.com/api/0/organizations/orgName/monitors/?project=42',
                    'GET',
                ),
                $this->expectedRequest($nextPageUrl, 'GET'),
            ],
            $requestFactory->requestStore->requests,
        );
    }

    #[Test]
    public function deleteMonitorUsesOrganizationAndMonitorId(): void
    {
        $requestFactory = new TestingRequestFactory([new JsonResponse(null, 202)]);
        $service = $this->createService($requestFactory);

        $service->deleteMonitor('monitor/id');

        self::assertSame(
            [
                $this->expectedRequest(
                    'https://example.com/api/0/organizations/orgName/monitors/monitor%2Fid/',
                    'DELETE',
                ),
            ],
            $requestFactory->requestStore->requests,
        );
    }

    private function createService(TestingRequestFactory $requestFactory): MutedMonitorService
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['sentry_cron_monitor'] = [
            'orgName' => 'orgName',
            'authToken' => 'authToken123',
        ];
        $extensionConfiguration = new ExtensionConfiguration();

        return new MutedMonitorService(
            $extensionConfiguration,
            $requestFactory,
            new DsnService(Dsn::createFromString('https://12345@example.com/42')),
        );
    }

    /**
     * @return array{uri: string, method: string, options: array<string, mixed>, context: null}
     */
    private function expectedRequest(string $uri, string $method): array
    {
        return [
            'uri' => $uri,
            'method' => $method,
            'options' => [
                'headers' => [
                    'Authorization' => 'Bearer authToken123',
                    'Accept' => 'application/json',
                ],
            ],
            'context' => null,
        ];
    }
}
