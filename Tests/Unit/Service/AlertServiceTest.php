<?php

namespace AUS\SentryCronMonitor\Tests\Unit\Service;

use Generator;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
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
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['sentry_cron_monitor'] = [
            'integrationIdMsTeams' => 'integrationIdMsTeams',
            'teamsChannelName' => 'teamsChannelName',
            'orgName' => 'orgName',
            'authToken' => 'authToken123',
        ];
        $extensionConfiguration = new ExtensionConfiguration();
        $requestFactory = new TestingRequestFactory($responses);
        $dsnService = new DsnService(Dsn::createFromString('https://12345@example.com/42'));
        $service = new AlertService($extensionConfiguration, $requestFactory, $dsnService, new NullLogger());

        $service->createIfNotExists($title);
        $this->assertEquals($expectedRequests, $requestFactory->requestStore->requests, 'The requests made are not as expected');
    }

    /**
     * @param list<ResponseInterface|Throwable> $responses
     */
    #[Test]
    #[DataProvider('provideSentryFailures')]
    public function createIfNotExistsNeverThrowsAndLogsWarningOnSentryProblems(array $responses, int $expectedRequestCount): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['sentry_cron_monitor'] = [
            'integrationIdMsTeams' => 'integrationIdMsTeams',
            'teamsChannelName' => 'teamsChannelName',
            'orgName' => 'orgName',
            'authToken' => 'authToken123',
        ];
        $requestFactory = new TestingRequestFactory($responses);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with($this->isString(), $this->isArray());
        $service = new AlertService(
            new ExtensionConfiguration(),
            $requestFactory,
            new DsnService(Dsn::createFromString('https://12345@example.com/42')),
            $logger,
        );

        $service->createIfNotExists('My Job Name');

        $this->assertCount($expectedRequestCount, $requestFactory->requestStore->requests);
    }

    public static function provideSentryFailures(): Generator
    {
        $request = new Request('GET', 'https://example.com/api/0/projects/orgName/42/rules/');
        yield 'GET returns 404' => [
            [new ClientException('Client error: 404 Not Found', $request, new Response(404))],
            1,
        ];
        yield 'GET returns 403' => [
            [new ClientException('Client error: 403 Forbidden', $request, new Response(403))],
            1,
        ];
        yield 'GET connection error' => [
            [new ConnectException('Could not resolve host', $request)],
            1,
        ];
        yield 'GET returns invalid json' => [
            [new Response(200, [], 'this is not json')],
            1,
        ];
        yield 'GET returns unexpected structure' => [
            [new JsonResponse([['no-filters' => true]])],
            1,
        ];
        yield 'POST fails' => [
            [
                new JsonResponse([]),
                new ClientException('Client error: 400 Bad Request', new Request('POST', 'https://example.com/api/0/projects/orgName/42/rules/'), new Response(400)),
            ],
            2,
        ];
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
