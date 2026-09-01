<?php

namespace AUS\SentryCronMonitor\Service;

use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

final readonly class MonitorTitleService
{
    public function getTitle(AbstractTask $task): string
    {
        $taskTitle = $task instanceof ExecuteSchedulableCommandTask
            ? $this->getCommandIdentifier($task)
            : $task->getTaskTitle();

        return $taskTitle . ' (uid: ' . $task->getTaskUid() . ')';
    }

    private function getCommandIdentifier(ExecuteSchedulableCommandTask $task): string
    {
        if (method_exists($task, 'getCommandIdentifier')) {
            return $task->getCommandIdentifier();
        }

        return $task->getTaskType();
    }
}
