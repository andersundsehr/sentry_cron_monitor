..  _commands:

====================
Maintenance commands
====================

..  _delete-muted-monitors-command:

Delete muted cron monitors
==========================

Use ``sentry-cron-monitor:delete-muted`` to remove cron monitors that are muted
in Sentry. The command only processes monitors from the project identified by
the configured Sentry DSN.

The command considers a monitor muted when the Sentry API returns
``isMuted=true`` for the monitor itself. It does not delete active monitors or a
monitor with only one muted environment.

..  warning::

    Deleting a monitor also removes its check-in history from Sentry. The
    command runs only in a TYPO3 ``Production`` context.

Preview monitors
----------------

Run the command without options first:

..  code-block:: shell
    :caption: Preview muted monitors

    vendor/bin/typo3 sentry-cron-monitor:delete-muted

The command lists the ID, slug, and name of every matching monitor but deletes
nothing.

Delete the listed monitors
--------------------------

Review the preview, then add ``--force``:

..  code-block:: shell
    :caption: Delete muted monitors

    vendor/bin/typo3 sentry-cron-monitor:delete-muted --force

The command retrieves every cursor page from Sentry and sends one DELETE request
for each muted monitor. It exits immediately when one API request fails.

Run cleanup automatically
-------------------------

To run cleanup on a schedule:

1. Create a TYPO3 Scheduler task of type :guilabel:`Execute console commands`.
2. Select ``sentry-cron-monitor:delete-muted``.
3. Add the ``--force`` option.
4. Choose a conservative schedule, for example once per day.
5. Run the task manually once and check its Scheduler and Sentry results.

..  warning::

    Automatic cleanup permanently deletes every muted monitor in the configured
    project. Use the preview command after configuration changes and before
    enabling the scheduled task.
