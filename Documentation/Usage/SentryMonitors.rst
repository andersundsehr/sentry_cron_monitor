..  _sentry-monitors:

===============
Sentry monitors
===============

The extension creates a Sentry cron monitor for each TYPO3 Scheduler task.
Existing and newly added tasks require no task-specific Sentry code.

Monitor identity
================

For Scheduler tasks that execute console commands, the extension combines and
normalizes the command identifier and task UID before sending a check-in to
Sentry:

..  code-block:: text

    <normalized-command-identifier>-uid-<task UID>

For example:

..  code-block:: text

    aus-projectcenter-sync-absences-to-outlook-uid-26

Failures for this monitor appear in Sentry as:

..  code-block:: text

    Cron failure: aus-projectcenter-sync-absences-to-outlook-uid-26

Other Scheduler task types use their normalized task title instead of a command
identifier. The UID distinguishes otherwise identical tasks. Renaming a task or
command changes the slug and can leave the previous monitor in Sentry.

Schedule mapping
================

Cron schedule
    The extension passes a Scheduler cron expression to Sentry unchanged.

Interval schedule
    The extension converts the interval from seconds to whole minutes and
    rounds up. For example, 90 seconds becomes a two-minute Sentry schedule.

The configured :ref:`configuration-timezone` tells Sentry how to interpret the
monitor schedule.

Alerts
======

Before each monitored run, the extension checks whether the matching Sentry
issue alert rule exists. It creates the rule when necessary. The rule sends
matching production failures to the configured Microsoft Teams channel.

Muted monitors
==============

Use the Production-only :ref:`delete-muted-monitors-command` to preview or
delete muted cron monitors in the configured Sentry project. You can also run
the cleanup automatically as a TYPO3 Scheduler console-command task.
