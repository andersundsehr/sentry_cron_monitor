<?php

namespace AUS\SentryCronMonitor\Xclass;

use RuntimeException;
use AUS\SentryCronMonitor\Service\AlertService;
use Override;
use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Sentry\MonitorScheduleUnit;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Execution;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

use function Sentry\captureCheckIn;

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
        if (!$execution instanceof Execution) {
            throw new RuntimeException('Task ' . $task->getTaskUid() . ' execution is not an instance of TYPO3\CMS\Scheduler\Execution', 6967941122);
        }

        $monitorSchedule = $execution->getCronCmd()
            ? MonitorSchedule::crontab($execution->getCronCmd())
            : MonitorSchedule::interval((int) ceil($execution->getInterval() / 60), MonitorScheduleUnit::minute());

        $monitorConfig = new MonitorConfig($monitorSchedule);

        $title = $task->getTaskTitle() . ' (uid: ' . $task->getTaskUid() . ')';
        $checkInId = captureCheckIn(
            slug: $title,
            status: CheckInStatus::inProgress(),
            monitorConfig: $monitorConfig,
        );

        $alertService = GeneralUtility::makeInstance(AlertService::class);
        $alertService->createIfNotExists($title);

        $status = CheckInStatus::error();
        try {
            $return = parent::executeTask($task);
            $status = $return ? CheckInStatus::ok() : CheckInStatus::error();
        } finally {
            captureCheckIn(
                slug: $title,
                status: $status,
                checkInId: $checkInId,
            );
        }


        return $return;
    }
}
