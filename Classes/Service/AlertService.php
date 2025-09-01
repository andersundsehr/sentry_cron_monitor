<?php

namespace AUS\SentryCronMonitor\Service;

use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;

#[Autoconfigure(public: true)]
final readonly class AlertService
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
        private RequestFactory $requestFactory,
        private DsnService $dsnService,
    ) {
    }

    public function createIfNotExists(string $title): void
    {
        $integrationIdMsTeams = $this->extensionConfiguration->get('sentry_cron_monitor', 'integrationIdMsTeams');
        $teamsChannelName = $this->extensionConfiguration->get('sentry_cron_monitor', 'teamsChannelName');
        $orgName = $this->extensionConfiguration->get('sentry_cron_monitor', 'orgName');
        $authToken = $this->extensionConfiguration->get('sentry_cron_monitor', 'authToken');

        $slug = rtrim(strtolower((string)preg_replace('/[^A-Za-z0-9]+/', '-', trim($title))), '-');

        if ($this->alertExits($orgName, $authToken, $slug)) {
            return;
        }

        $this->alertSentry($orgName, $authToken, $slug, $title, $integrationIdMsTeams, $teamsChannelName);
    }

    private function alertExits(string $orgName, string $authToken, string $slug): bool
    {
        $url = $this->dsnService->provideUrl($orgName);
        $response = $this->requestFactory->request($url, 'GET', [
            'headers' => [
                'Authorization' => 'Bearer ' . $authToken,
                'Content-Type' => 'application/json',
            ],
        ]);

        $jsonString = $response->getBody()->getContents();
        $data = json_decode($jsonString, true);

        if (!is_array($data)) {
            return false;
        }

        foreach ($data as $rule) {
            $filters = $rule['filters'] ?? null;
            if (!is_array($filters)) {
                throw new Exception('Unexpected data from Sentry', 3744386449);
            }

            foreach ($rule['filters'] as $filter) {
                if ($filter['value'] !== $slug) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    public function alertSentry(
        string $orgName,
        mixed $authToken,
        string $slug,
        string $title,
        mixed $integrationIdMsTeams,
        mixed $teamsChannelName
    ): void {
        $url = $this->dsnService->provideUrl($orgName);

        $this->requestFactory->request($url, 'POST', [
            'headers' => [
                'Authorization' => 'Bearer ' . $authToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                "name" => "Monitor Alert for " . $title,
                "frequency" => '5',
                "actionMatch" => "any",
                "filterMatch" => "all",
                "environment" => 'Production',
                "conditions" => [
                    ["id" => "sentry.rules.conditions.first_seen_event.FirstSeenEventCondition"],
                    ["id" => "sentry.rules.conditions.regression_event.RegressionEventCondition"],
                ],
                "filters" => [
                    [
                        "id" => "sentry.rules.filters.tagged_event.TaggedEventFilter",
                        "key" => "monitor.slug",
                        "match" => "eq",
                        "value" => $slug,
                    ],
                ],
                "actions" => [
                    [
                        "id" => "sentry.integrations.msteams.notify_action.MsTeamsNotifyServiceAction",
                        "team" => $integrationIdMsTeams,
                        "channel" => $teamsChannelName,
                    ],
                ],
            ],
        ]);
    }
}
