..  _task-execution:

==============
Task execution
==============

The extension monitors both automatic and manually triggered TYPO3 Scheduler
executions.

Automatic execution
===================

The server cron job starts all due Scheduler tasks:

..  code-block:: shell

    vendor/bin/typo3 scheduler:run

Command-line execution
======================

A developer or operator can run one configured Scheduler task by UID:

..  code-block:: shell

    vendor/bin/typo3 scheduler:execute --task=<task-uid>

``scheduler:run`` and ``scheduler:execute`` use the same Sentry monitor for the
same task.

Manual execution in the TYPO3 backend
=====================================

Starting a task manually in the TYPO3 Scheduler backend creates an additional
Sentry cron monitor instead of using the monitor for its scheduled executions.

..  important::

    The extension monitors a console command only when it runs as a configured
    TYPO3 Scheduler task. Running a Symfony command directly does not pass
    through this extension.

Check-in results
================

``in_progress``
    TYPO3 is about to run the task.

``ok``
    The task finished and returned ``true``.

``error``
    The task returned ``false`` or threw an exception.

Sentry uses these check-ins and the configured schedule to detect failed or
missed executions.

See :ref:`sentry-monitors` for the monitor name and schedule.
