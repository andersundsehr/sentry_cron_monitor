<?php

namespace AUS\SentryCronMonitor\Xclass;

use Override;
use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Sentry\MonitorScheduleUnit;
use Sentry\SentrySdk;
use Throwable;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Execution;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

use function Sentry\captureCheckIn;
use function Sentry\captureException;

class Scheduler extends \TYPO3\CMS\Scheduler\Scheduler
{
    /**
     * @see https://docs.sentry.io/platforms/php/crons/
     * @throws Throwable
     */
    #[Override]
    public function executeTask(AbstractTask $task): bool
    {
        $execution = $task->getExecution();
        $cronCmd = null;
        if ($execution instanceof Execution) {
            $cronCmd = $execution->getCronCmd();
        }

        $monitorSchedule = null;
        if ($cronCmd) {
            $monitorSchedule = MonitorSchedule::crontab($cronCmd);
            $interval = 1440;
        } elseif ($execution instanceof Execution) {
            $interval = $execution->getInterval() / 60;
            if (is_float($interval)) {
                $interval = ceil($interval);
                trigger_error('Task interval can not divide to integer minutes', E_USER_WARNING);
                if ($interval < 5) {
                    $interval = 5; // minimum 5 minutes
                }
            }

            $monitorSchedule = MonitorSchedule::interval((int)$interval, MonitorScheduleUnit::minute());
        }

        $return = false;
        if ($monitorSchedule instanceof MonitorSchedule) {
            $monitorConfig = new MonitorConfig(
                $monitorSchedule,
            );

            $slug = $task->getTaskTitle() . ' (uid: ' . $task->getTaskUid() . ')';
            $checkInId = captureCheckIn(
                slug: $slug,
                status: CheckInStatus::inProgress(),
                monitorConfig: $monitorConfig,
            );

            $this->createAlert($slug, (int)$interval);

            $status = CheckInStatus::error();
            try {
                $return = parent::executeTask($task);
                $status = $return
                    ? CheckInStatus::ok()
                    : CheckInStatus::error();
            } catch (Throwable $throwable) {
                $status = CheckInStatus::error();
                captureException($throwable);
                throw $throwable;
            } finally {
                captureCheckIn(
                    slug: $slug,
                    status: $status,
                    checkInId: $checkInId,
                );
            }
        }

        return $return;
    }

    public function createAlert(string $slug, int $interval): void
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $integrationIdMsTeamsRaw = $extensionConfiguration->get('sentry_cron_monitor', 'integrationIdMsTeams');
        $integrationIdMsTeams = is_string($integrationIdMsTeamsRaw) ? str_replace("'", '', $integrationIdMsTeamsRaw) : '';

        $teamsChannelNameRaw = $extensionConfiguration->get('sentry_cron_monitor', 'teamsChannelName');
        $teamsChannelName = is_string($teamsChannelNameRaw) ? str_replace("'", '', $teamsChannelNameRaw) : '';

        $authTokenRaw = $extensionConfiguration->get('sentry_cron_monitor', 'authToken');
        $authToken = is_string($authTokenRaw) ? str_replace("'", '', $authTokenRaw) : '';

        $options = SentrySdk::getCurrentHub()->getClient()?->getOptions();
        if (!$options) {
            return;
        }

        $dsn = $options->getDsn();
        if (!$dsn) {
            return;
        }

        $host = $dsn->getHost();
        $scheme = $dsn->getScheme();
        $projectId = $dsn->getProjectId();
        $url = $scheme . '://' . $host . '/api/0/projects/sentry' . '/' . $projectId . '/rules/';
        $ch = curl_init($url);

        if ($this->alertExits($slug, $authToken, $url)) {
            return;
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $authToken,
            "Content-Type: application/json",
        ]);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            '{
                "name": "Monitor Alert for ' . $slug . '",
                "frequency": "' . $interval . '",
                "actionMatch": "any",
                "filterMatch": "all",
                "conditions": [
                  { "id": "sentry.rules.conditions.first_seen_event.FirstSeenEventCondition" },
                  { "id": "sentry.rules.conditions.regression_event.RegressionEventCondition" }
                ],
                "filters": [
                  {
                    "id": "sentry.rules.filters.tagged_event.TaggedEventFilter",
                    "key": "monitor.slug",
                    "match": "eq",
                    "value": "' . rtrim(strtolower((string)preg_replace('/[^A-Za-z0-9-]+/', '-', trim($slug))), '-') . '"
                  }
                ],
                "actions": [
                  {
                    "id": "sentry.integrations.msteams.notify_action.MsTeamsNotifyServiceAction",
                    "team": "' . $integrationIdMsTeams . '",
                    "channel": "' . $teamsChannelName . '"
                  }
                ]
            }'
        );

        curl_exec($ch);
        curl_close($ch);
    }

    private function alertExits(string $slug, string $authToken, string $ch): bool
    {
        $ch = curl_init($ch);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $authToken,
            "Content-Type: application/json",
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string)$response, true);
        if (is_array($data)) {
            foreach ($data as $rule) {
                if (is_array($rule) && isset($rule['filters']) && is_array($rule['filters'])) {
                    foreach ($rule['filters'] as $filter) {
                        if (is_array($filter) && $filter['value'] === $slug) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
