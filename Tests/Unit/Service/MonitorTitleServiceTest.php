<?php

namespace AUS\SentryCronMonitor\Tests\Unit\Service;

use AUS\SentryCronMonitor\Service\MonitorTitleService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

final class MonitorTitleServiceTest extends TestCase
{
    #[Test]
    public function usesCommandIdentifierForSchedulableCommandTask(): void
    {
        $task = new class ('aus-projectcenter:sync:absences-to-outlook') extends ExecuteSchedulableCommandTask {
            public function __construct(string $commandIdentifier)
            {
                $this->commandIdentifier = $commandIdentifier;
            }
        };
        $task->setTaskUid(26);

        self::assertSame(
            'aus-projectcenter-sync-absences-to-outlook-uid-26',
            (new MonitorTitleService())->getTitle($task),
        );
    }

    #[Test]
    public function usesTaskTitleForOtherSchedulerTasks(): void
    {
        $task = new class extends AbstractTask {
            public function __construct()
            {
            }

            public function execute(): bool
            {
                return true;
            }

            public function getTaskTitle(): string
            {
                return 'Custom task';
            }
        };
        $task->setTaskUid(12);

        self::assertSame('custom-task-uid-12', (new MonitorTitleService())->getTitle($task));
    }
}
