<?php

declare(strict_types=1);

use AUS\SentryCronMonitor\Xclass\Scheduler as SchedulerXclass;
use TYPO3\CMS\Scheduler\Scheduler;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][Scheduler::class] = [
    'className' => SchedulerXclass::class,
];
