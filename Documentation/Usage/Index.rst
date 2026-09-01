..  _usage:

============
How it works
============

The extension connects TYPO3 Scheduler tasks with Sentry Cron Monitoring. It
reports when a task starts, succeeds, or fails. The Scheduler remains
responsible for running the task.

For each monitored task execution, the extension:

1. Creates or updates the matching Sentry cron monitor.
2. Sends an ``in_progress`` check-in.
3. Creates the required alert rule when it does not exist.
4. Lets TYPO3 run the task.
5. Sends an ``ok`` or ``error`` check-in.

This monitoring runs only in a TYPO3 ``Production`` context and only when the
configured Sentry host is reachable. In every other case, TYPO3 still runs the
Scheduler task without Sentry monitoring.

Choose a topic
==============

..  card-grid::
    :columns: 1
    :columns-md: 3
    :gap: 3
    :card-height: 100

    ..  card:: :ref:`What happens during execution? <task-execution>`

        Follow automatic and manual task executions and their check-ins.

    ..  card:: :ref:`How are monitors created? <sentry-monitors>`

        Understand monitor identity, schedules, alerts, and cleanup.

..  toctree::
    :hidden:

    TaskExecution
    SentryMonitors
