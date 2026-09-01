..  _references:

==========
References
==========

Glossary
========

Check-in
    A status message sent for one execution of a Sentry cron monitor. This
    extension uses ``in_progress``, ``ok``, and ``error``.

Cron monitor
    A Sentry object that records expected task schedules and executions. It can
    detect failures and missed executions.

DSN
    Data Source Name. It identifies the Sentry host and project used by the
    initialized Sentry SDK.

Issue alert rule
    A Sentry rule that matches events and executes an action. This extension
    creates a rule that sends matching monitor failures to Microsoft Teams.

Monitor slug
    Machine-readable monitor identifier used by the generated alert filter.

Scheduler task
    A TYPO3 task configured for one-time or recurring execution by the Scheduler
    system extension.

XCLASS
    TYPO3 mechanism that replaces an instantiated class with a subclass. This
    extension uses it to wrap central Scheduler execution.

Behavior reference
==================

..  list-table::
    :header-rows: 1

    *   - Condition
        - Behavior
    *   - Context is not ``Production``
        - Run the task without Sentry monitoring.
    *   - Sentry host is unreachable
        - Run the task without Sentry monitoring.
    *   - Task returns ``true``
        - Send an ``ok`` check-in.
    *   - Task returns ``false``
        - Send an ``error`` check-in.
    *   - Task throws an exception
        - Send an ``error`` check-in and preserve the exception.
    *   - Matching alert rule exists
        - Reuse it.
    *   - Matching alert rule does not exist
        - Create a Microsoft Teams issue alert rule.

External documentation
======================

* `TYPO3 Scheduler manual
  <https://docs.typo3.org/c/typo3/cms-scheduler/main/en-us/>`__
* `Manually executing a Scheduler task
  <https://docs.typo3.org/c/typo3/cms-scheduler/main/en-us/Administration/ManualExecution/Index.html>`__
* `TYPO3 XCLASS reference
  <https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Xclasses/Index.html>`__
* `TYPO3 system configuration
  <https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Configuration/Typo3ConfVars/Index.html>`__
* `Sentry PHP cron monitoring
  <https://docs.sentry.io/platforms/php/crons/>`__
* `Sentry API: Retrieve monitors for an organization
  <https://docs.sentry.io/api/crons/retrieve-monitors-for-an-organization/>`__
* `Sentry API: Delete a monitor
  <https://docs.sentry.io/api/crons/delete-a-monitor-or-monitor-environments/>`__
* `Sentry Microsoft Teams integration
  <https://develop.sentry.dev/integrations/msteams/>`__
* `How to document a TYPO3 extension
  <https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/Howto/WritingDocForExtension/Index.html>`__
