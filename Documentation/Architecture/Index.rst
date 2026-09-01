..  _architecture:

============
Architecture
============

The extension adds monitoring at the central TYPO3 Scheduler execution point.
This design covers every task without requiring changes to individual task
classes.

Component overview
==================

``ext_localconf.php``
    Registers :php:`AUS\SentryCronMonitor\Xclass\Scheduler` as an XCLASS for
    :php:`TYPO3\CMS\Scheduler\Scheduler`.

``Scheduler`` XCLASS
    Controls the execution flow, converts the schedule, sends Sentry check-ins,
    and delegates the original execution to TYPO3.

``DsnService``
    Reads the DSN from the initialized Sentry SDK. It derives the Sentry host
    and project alert-rule API URL.

``AlertService``
    Searches the project issue alert rules for the monitor slug and creates the
    Microsoft Teams rule when no match exists.

``MutedMonitorService``
    Lists all cron monitor pages for the DSN project, filters monitors with
    ``isMuted=true``, and deletes selected monitor IDs.

``DeleteMutedMonitorsCommand``
    Enforces the Production context, displays a cleanup preview, and delegates
    deletion to ``MutedMonitorService`` only when ``--force`` is present.

Execution diagram
=================

..  code-block:: text

    TYPO3 Scheduler command
             |
             v
    Scheduler XCLASS
             |
             +-- context is not Production ------> TYPO3 task execution
             |
             +-- Sentry host is unreachable -----> TYPO3 task execution
             |
             v
    Build monitor schedule and send in_progress check-in
             |
             v
    AlertService ----> Sentry issue alert API ----> Microsoft Teams rule
             |
             v
    TYPO3 task execution
             |
             +-- returns true --------------------> ok check-in
             |
             +-- returns false or throws ---------> error check-in

Design rationale
================

Central Scheduler interception
    A central integration minimizes setup and automatically includes new tasks.
    The tradeoff is a dependency on TYPO3 Scheduler internals.

Production-only monitoring
    Development executions do not create production monitors or notifications.
    Integration and staging systems that should send check-ins must use a TYPO3
    context beginning with ``Production``.

Reachability fallback
    A temporarily unreachable Sentry host must not prevent the business task
    from running. The extension therefore skips monitoring and delegates to the
    original Scheduler when the host check fails.

Automatic alert provisioning
    Keeping alert creation in the execution path removes a manual Sentry setup
    step. It also means that each monitored execution lists the project issue
    alert rules before running the task.

Generated alert rule
====================

The generated Sentry issue alert rule:

* uses the ``Production`` environment;
* matches the task's ``monitor.slug`` tag;
* reacts to first-seen and regression conditions; and
* sends the notification through the configured Microsoft Teams integration.

Architectural constraints
=========================

TYPO3 supports only one XCLASS per base class. Another extension that replaces
:php:`TYPO3\CMS\Scheduler\Scheduler` can conflict with this extension. Core
changes to the Scheduler class can also require an update after a TYPO3 upgrade.
