..  _verify-setup:

================
Verify the setup
================

Run one harmless Scheduler task and confirm that Sentry receives its check-in.
This proves that TYPO3, Sentry, the alert API, and the extension configuration
work together.

Before you start
================

* Use an integration, staging, or production system whose TYPO3 application
  context starts with ``Production``.
* Choose a task that is safe to execute on demand.
* Complete the :ref:`configuration` first.

..  warning::

    Do not test with a business-critical import, export, or cleanup task unless
    you understand the effect of running it manually.

1. Find a task UID
==================

List the configured Scheduler tasks:

..  code-block:: shell

    vendor/bin/typo3 scheduler:list

Record the UID of a harmless task.

2. Run the task
===============

Replace ``<task-uid>`` with that UID:

..  code-block:: shell

    vendor/bin/typo3 scheduler:execute --task=<task-uid>

The command must run with the same ``Production`` context and environment
variables as the regular Scheduler cron job.

For a local Docker installation whose regular context is ``Development/docker``,
enable monitoring for this single command while retaining the Docker database
configuration:

..  code-block:: shell

    TYPO3_CONTEXT=Production/docker vendor/bin/typo3 scheduler:execute --task=<task-uid>

The context must start with ``Production``. Keep the ``/docker`` suffix locally;
``Production/Integration`` selects integration infrastructure such as its
database host and usually cannot run in a local Docker network.

Run the configured task through ``scheduler:execute``. Starting its Symfony
command directly bypasses the Scheduler integration and does not create a
Sentry monitor.

3. Check Sentry
===============

Open the Sentry project and verify these results:

..  list-table::
    :header-rows: 1

    *   - Location
        - Expected result
    *   - Crons
        - A monitor named ``<normalized task title>-uid-<task UID>`` exists.
    *   - Monitor details
        - The latest check-in has the ``ok`` status for a successful task.
    *   - Alerts
        - An issue alert named ``Monitor Alert for <normalized task
          title>-uid-<task UID>`` exists.

The first execution creates the monitor and issue alert. Later executions reuse
the matching alert rule.

4. Test a failure safely
========================

To verify Microsoft Teams delivery, use a dedicated test task that returns
``false`` or throws an exception. Run it only in a controlled environment. The
expected result is an ``error`` check-in followed by a Teams notification from
the generated Sentry alert rule.

The extension does not ship a failing test task. Remove or disable your test
task after verification.

If an expected result is missing, use :ref:`troubleshooting`.
