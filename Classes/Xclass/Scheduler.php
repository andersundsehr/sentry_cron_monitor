<?php

namespace AUS\SentryCronMonitor\Xclass;

use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Sentry\MonitorScheduleUnit;
use Sentry\SentrySdk;
use Throwable;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use function Sentry\captureCheckIn;
use function Sentry\captureException;

class Scheduler extends \TYPO3\CMS\Scheduler\Scheduler
{
    /**
     * @see https://docs.sentry.io/platforms/php/crons/
     * @throws Throwable
     */
    public function executeTask(AbstractTask $task): bool
    {
        $cronCmd = $task->getExecution()->getCronCmd();
        if ($cronCmd) {
            $monitorSchedule = MonitorSchedule::crontab($cronCmd);
            $interval = 1440;
        } else {
            $interval = $task->getExecution()->getInterval() / 60;
            if (is_float($interval)) {
                $interval = ceil($interval);
                trigger_error('Task interval can not divide to integer minutes', E_USER_WARNING);
                if($interval < 5) {
                    $interval = 5; // minimum 5 minutes
                }
            }
            $monitorSchedule = MonitorSchedule::interval($interval, MonitorScheduleUnit::minute());
        }

        $monitorConfig = new MonitorConfig(
            $monitorSchedule,
        );
        $slug = $task->getTaskTitle() . ' (uid: ' . $task->getTaskUid() . ')';
        $checkInId = captureCheckIn(
            slug: $slug,
            status: CheckInStatus::inProgress(),
            monitorConfig: $monitorConfig,
        );

        $this->createAlert($slug, $interval);

        try {
            $return = parent::executeTask($task);
            $status = $return
                ? CheckInStatus::ok()
                : CheckInStatus::error();
        } catch (Throwable $e) {
            $status = CheckInStatus::error();
            captureException($e);
            throw $e;
        } finally {
            captureCheckIn(
                slug: $slug,
                status: $status,
                checkInId: $checkInId,
            );
        }

        return $return;
    }

    public function createAlert($slug, $interval): void
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $integrationIdMsTeams = str_replace("'", '', $extensionConfiguration->get('sentry_cron_monitor', 'integrationIdMsTeams'));
        $teamsChannelName = str_replace("'", '', $extensionConfiguration->get('sentry_cron_monitor', 'teamsChannelName'));
        $authToken = str_replace("'", '', $extensionConfiguration->get('sentry_cron_monitor', 'authToken'));

        $options = SentrySdk::getCurrentHub()->getClient()?->getOptions();
        if (!$options) {
            return;
        }

        $dsn = $options->getDsn();
        $host = $dsn->getHost();
        $scheme = $dsn->getScheme();
        $projectId = $dsn->getProjectId();
        $url = $scheme . '://' . $host . '/api/0/projects/sentry' . '/' . $projectId . '/rules/';
        $ch = curl_init($url);

        if($this->alertExits($slug, $authToken, $url)) {
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
                    "value": "' . rtrim(strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', trim($slug))), '-') . '"
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

    private function alertExits($slug, $authToken, $ch)
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
        $data = json_decode($response, true);
        foreach ($data as $rule) {
            foreach ($rule['filters'] as $filter) {
                if ($filter['value'] === $slug) {
                    return true;
                }
            }
        }
        return false;
    }
}
