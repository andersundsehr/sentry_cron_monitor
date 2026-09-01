<?php

declare(strict_types=1);

namespace AUS\SentryCronMonitor\Service;

use GuzzleHttp\Psr7\Header;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;

final readonly class MutedMonitorService
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
        private RequestFactory $requestFactory,
        private DsnService $dsnService,
    ) {
    }

    /**
     * @return list<array{id: string, slug: string, name: string}>
     */
    public function findMutedMonitors(): array
    {
        $organization = $this->getConfiguration('orgName');
        $authToken = $this->getConfiguration('authToken');
        $nextPageUrl = $this->dsnService->provideMonitorsUrl($organization);
        $visitedUrls = [];
        $mutedMonitors = [];

        while ($nextPageUrl !== null) {
            if (isset($visitedUrls[$nextPageUrl])) {
                throw new RuntimeException('Sentry returned a circular monitor pagination link.', 1754574518);
            }
            $visitedUrls[$nextPageUrl] = true;

            $response = $this->requestFactory->request($nextPageUrl, 'GET', [
                'headers' => $this->createHeaders($authToken),
            ]);
            $monitors = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($monitors) || !array_is_list($monitors)) {
                throw new RuntimeException('Sentry returned an invalid monitor list.', 1754574519);
            }

            foreach ($monitors as $monitor) {
                if (!is_array($monitor) || ($monitor['isMuted'] ?? $monitor['is_muted'] ?? false) !== true) {
                    continue;
                }

                $id = (string) ($monitor['id'] ?? '');
                if ($id === '') {
                    throw new RuntimeException('A muted Sentry monitor has no ID.', 1754574520);
                }

                $mutedMonitors[] = [
                    'id' => $id,
                    'slug' => (string) ($monitor['slug'] ?? ''),
                    'name' => (string) ($monitor['name'] ?? $monitor['slug'] ?? $id),
                ];
            }

            $nextPageUrl = $this->getNextPageUrl($response);
        }

        return $mutedMonitors;
    }

    public function deleteMonitor(string $monitorId): void
    {
        $organization = $this->getConfiguration('orgName');
        $authToken = $this->getConfiguration('authToken');

        $this->requestFactory->request(
            $this->dsnService->provideMonitorUrl($organization, $monitorId),
            'DELETE',
            ['headers' => $this->createHeaders($authToken)],
        );
    }

    private function getConfiguration(string $path): string
    {
        $value = (string) $this->extensionConfiguration->get('sentry_cron_monitor', $path);
        if ($value === '') {
            throw new RuntimeException('Missing extension configuration: ' . $path, 1754574521);
        }

        return $value;
    }

    /**
     * @return array{Authorization: string, Accept: string}
     */
    private function createHeaders(string $authToken): array
    {
        return [
            'Authorization' => 'Bearer ' . $authToken,
            'Accept' => 'application/json',
        ];
    }

    private function getNextPageUrl(ResponseInterface $response): ?string
    {
        foreach (Header::parse($response->getHeader('Link')) as $link) {
            if (($link['rel'] ?? null) !== 'next' || ($link['results'] ?? null) !== 'true') {
                continue;
            }

            $url = trim((string) ($link[0] ?? ''), '<>');
            if ($url !== '') {
                return $url;
            }
        }

        return null;
    }
}
