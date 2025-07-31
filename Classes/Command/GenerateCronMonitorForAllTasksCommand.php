<?php

namespace AUS\AusJiraApi\Command;

use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function Sentry\init;
use function Sentry\captureCheckIn;

class GenerateCronMonitorForAllTasksCommand extends Command
{
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_scheduler_task');
        $qB = $connection->createQueryBuilder();
        $tasks = $qB
            ->select('*')
            ->from('tx_scheduler_task')
            ->where(
                $qB->expr()->eq('disable', $qB->createNamedParameter(0))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        init([
            'dsn' => 'https://c4a4e289838d4a4994bee2f2ea710060@sentry.andersundsehr.com/47',
            'send_default_pii' => true,
        ]);

        foreach ($tasks as $task) {
            echo $task['name'];
            $monitorSchedule = MonitorSchedule::crontab('0 0 * * *');
            $monitorConfig = new MonitorConfig(
                $monitorSchedule,
            );
            $slug = 'scheduler-task-' . $task['uid'];
            $checkInId = captureCheckIn(
                slug: $slug,
                status: CheckInStatus::inProgress(),
                monitorConfig: $monitorConfig,
            );

            // Short delay so Sentry can create cron monitor sucessfully
            sleep(1);

            $project = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['aus_sentry_cronmonitor']['sentryProject'];

            // create alert for monitor
            $ch = curl_init('https://sentry.andersundsehr.com/api/0/projects/sentry/' . $project . '/rules/');

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $authToken = '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';
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $authToken,
                "Content-Type: application/json",
            ]);

            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                '{
                "name": "Monitor Alert for ' . $slug . '",
                "frequency": 1440,
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
                    "value": "' . $slug . '"
                  }
                ],
                "actions": [
                  {
                    "id": "sentry.integrations.msteams.notify_action.MsTeamsNotifyServiceAction",
                    "team": 3,
                    "channel": "sentry"
                  }
                ]
            }'
            );

            curl_exec($ch);
            curl_close($ch);

            $status = empty($task['lastexecution_failure'])
                ? CheckInStatus::ok()
                : CheckInStatus::error();

            captureCheckIn(
                slug: $slug,
                status: $status,
                checkInId: $checkInId,
            );
        }

        return 0;
    }
}
