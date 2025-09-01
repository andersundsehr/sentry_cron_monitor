<?php

namespace AUS\SentryCronMonitor\Tests\Unit\Service;

use Generator;
use Psr\Http\Message\ResponseInterface;
use AUS\SentryCronMonitor\Service\AlertService;
use AUS\SentryCronMonitor\Service\DsnService;
use AUS\SentryCronMonitor\Service\UrlService;
use AUS\SentryCronMonitor\Tests\TestingRequestFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentry\Dsn;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;

class AlertServiceTest extends TestCase
{
    /**
     * @param list<ResponseInterface> $responses
     * @param array<string, string>|array<string, mixed> $expectedRequests
     */
    #[Test]
    #[DataProvider('provideCreateAlertData')]
    public function createIfNotExists(string $title, array $responses, array $expectedRequests): void
    {
        $extensionConfiguration = new class extends ExtensionConfiguration {
            public function get(string $extension, string $path = ''): mixed
            {
                Assert::assertEquals($extension, 'sentry_cron_monitor');

                return match ($path) {
                    'integrationIdMsTeams' => 'integrationIdMsTeams',
                    'teamsChannelName' => 'teamsChannelName',
                    'orgName' => 'orgName',
                    'authToken' => 'authToken123',
                    default => null,
                };
            }
        };
        $requestFactory = new TestingRequestFactory($responses);
        $dsnService = new DsnService(Dsn::createFromString('https://12345@example.com/42'));
        $service = new AlertService($extensionConfiguration, $requestFactory, $dsnService);

        $service->createIfNotExists($title);
        $this->assertEquals($expectedRequests, $requestFactory->requests, 'The requests made are not as expected');
    }

    public static function provideCreateAlertData(): Generator
    {
        $alertMyJobName = [
            'filters' => [
                [
                    'value' => 'my-job-name',
                ],
            ],
        ];
        $alertNotMyJobName = [
            'filters' => [
                [
                    'value' => 'not-my-job-name',
                ],
            ],
        ];
        $alertDifferentJobName = [
            'filters' => [
                [
                    'value' => 'different-job-name',
                ],
            ],
        ];
        $createAlertRequest = [
            'frequency' => 5,
            'name' => 'Monitor Alert for My Job Name',
            'environment' => 'Production',
            'actionMatch' => 'any',
            'filterMatch' => 'all',
            'conditions' => [
                [
                    'id' => 'sentry.rules.conditions.first_seen_event.FirstSeenEventCondition',
                ],
                [
                    'id' => 'sentry.rules.conditions.regression_event.RegressionEventCondition',
                ],
            ],
            'filters' => [
                [
                    'id' => 'sentry.rules.filters.tagged_event.TaggedEventFilter',
                    'key' => 'monitor.slug',
                    'match' => 'eq',
                    'value' => 'my-job-name',
                ],
            ],
            'actions' => [
                [
                    'id' => 'sentry.integrations.msteams.notify_action.MsTeamsNotifyServiceAction',
                    'channel' => 'teamsChannelName',
                    'team' => 'integrationIdMsTeams',
                ],
            ],
        ];
        yield 'already exists' => [
            'title' => 'My Job Name',
            'responses' => [
                new JsonResponse([$alertMyJobName]), // does exist request
            ],
            'expectedRequests' => [
                [
                    'method' => 'GET',
                    'uri' => 'https://example.com/api/0/projects/orgName/42/rules/',
                    'options' => [
                        'headers' => [
                            'Authorization' => 'Bearer authToken123',
                            'Content-Type' => 'application/json',
                        ],
                    ],
                    'context' => null,
                ],
            ],
        ];
        yield 'nothing exists' => [
            'title' => 'My Job Name',
            'responses' => [
                new JsonResponse([]), // does exist request
                new JsonResponse([]), // create request
            ],
            'expectedRequests' => [
                [
                    'method' => 'GET',
                    'uri' => 'https://example.com/api/0/projects/orgName/42/rules/',
                    'options' => [
                        'headers' => [
                            'Authorization' => 'Bearer authToken123',
                            'Content-Type' => 'application/json',
                        ],
                    ],
                    'context' => null,
                ],
                [
                    'method' => 'POST',
                    'uri' => 'https://example.com/api/0/projects/orgName/42/rules/',
                    'options' => [
                        'headers' => [
                            'Authorization' => 'Bearer authToken123',
                            'Content-Type' => 'application/json',
                        ],
                        'json' => $createAlertRequest,
                    ],
                    'context' => null,
                ],
            ],
        ];
        yield 'wrong exists' => [
            'title' => 'My Job Name',
            'responses' => [
                new JsonResponse([$alertNotMyJobName]), // does exist request
                new JsonResponse([]), // create request
            ],
            'expectedRequests' => [
                [
                    'method' => 'GET',
                    'uri' => 'https://example.com/api/0/projects/orgName/42/rules/',
                    'options' => [
                        'headers' => [
                            'Authorization' => 'Bearer authToken123',
                            'Content-Type' => 'application/json',
                        ],
                    ],
                    'context' => null,
                ],
                [
                    'method' => 'POST',
                    'uri' => 'https://example.com/api/0/projects/orgName/42/rules/',
                    'options' => [
                        'headers' => [
                            'Authorization' => 'Bearer authToken123',
                            'Content-Type' => 'application/json',
                        ],
                        'json' => $createAlertRequest,
                    ],
                    'context' => null,
                ],
            ],
        ];
        yield 'different name' => [
            'title' => 'Different Job Name',
            'responses' => [
                new JsonResponse([$alertDifferentJobName]), // does exist request
            ],
            'expectedRequests' => [
                [
                    'method' => 'GET',
                    'uri' => 'https://example.com/api/0/projects/orgName/42/rules/',
                    'options' => [
                        'headers' => [
                            'Authorization' => 'Bearer authToken123',
                            'Content-Type' => 'application/json',
                        ],
                    ],
                    'context' => null,
                ],
            ],
        ];
    }
}
