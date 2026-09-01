..  _troubleshooting:

===============
Troubleshooting
===============

Start with the symptom that matches your result from :ref:`verify-setup`.

The task runs but no monitor appears
====================================

Check these conditions in order:

1. TYPO3 runs in an application context beginning with ``Production``.
2. The Sentry PHP SDK is initialized before the Scheduler runs.
3. The configured DSN is valid.
4. PHP can open the Sentry host through HTTPS and ``allow_url_fopen`` is enabled.
5. The Scheduler CLI process receives the same environment variables as the web
   process.

The exception ``Sentry is not initialized``
===========================================

The extension could not read a DSN from the current Sentry SDK hub. Initialize
the Sentry PHP SDK for CLI requests and verify that the Scheduler command loads
that initialization.

The monitor exists but the alert does not
==========================================

* Verify ``SENTRY_ORGANIZATION`` contains the organization slug, not its display
  name.
* Verify the DSN belongs to the expected Sentry project.
* Verify ``SENTRY_AUTH_TOKEN`` can list and create issue alert rules.
* Inspect the TYPO3 and Sentry logs for HTTP authentication or permission
  errors.

The alert exists but Teams receives nothing
===========================================

* Verify the Sentry Microsoft Teams integration is active.
* Compare the configured integration ID and channel name with Sentry.
* Confirm the first channel in the Microsoft Teams team is named ``General``.
* Confirm the event and alert rule use the ``Production`` environment.
* Trigger failure only with a controlled test task and inspect the alert history
  in Sentry.

The monitor schedule is inaccurate
===================================

* Verify ``SENTRY_CRON_TIMEZONE`` contains the intended IANA timezone.
* Compare cron expressions with the Scheduler task configuration.
* Remember that interval schedules are rounded up to whole minutes. An interval
  of 90 seconds becomes two minutes in Sentry.

Renaming a task creates another monitor
=======================================

The monitor identity contains the task title and UID. Renaming the task changes
that identity. Remove the obsolete monitor and alert rule manually in Sentry
after confirming that the new monitor works.

Too many missed-check-in alerts
===============================

Increase the monitor's failure tolerance in Sentry. Also confirm that the
Scheduler cron job runs frequently enough to start all due tasks.

Still unresolved
================

Collect the TYPO3 version, PHP version, application context, task schedule, and
relevant log message. Do not include DSNs or authentication tokens. Then open an
issue in the `GitHub issue tracker
<https://github.com/andersundsehr/sentry_cron_monitor/issues>`__.
