<?php

namespace AUS\SentryCronMonitor\Service;

use RuntimeException;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

final readonly class MonitorTitleService
{
    public function getTitle(AbstractTask $task): string
    {
        $taskTitle = $task instanceof ExecuteSchedulableCommandTask
            ? $this->getCommandIdentifier($task)
            : $task->getTaskTitle();

        return rtrim(
            strtolower((string) preg_replace(
                '/[^A-Za-z0-9]+/',
                '-',
                trim($taskTitle . ' (uid: ' . $task->getTaskUid() . ')'),
            )),
            '-',
        );
    }

    private function getCommandIdentifier(object $task): string
    {
        if (method_exists($task, 'getCommandIdentifier')) {
            $commandIdentifier = $task->getCommandIdentifier();
        } elseif (method_exists($task, 'getTaskType')) {
            $commandIdentifier = $task->getTaskType();
        } else {
            throw new RuntimeException('Could not determine the Scheduler command identifier.', 1756806956);
        }

        if (!is_string($commandIdentifier)) {
            throw new RuntimeException('The Scheduler command identifier is not a string.', 1756806957);
        }

        return $commandIdentifier;
    }
}
