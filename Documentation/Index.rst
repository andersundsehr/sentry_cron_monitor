..  _start:

===================
Sentry Cron Monitor
===================

**Detect failed TYPO3 Scheduler tasks before they go unnoticed.**

Sentry Cron Monitor reports every production Scheduler task to Sentry and
creates a Microsoft Teams alert for failures. It adds monitoring centrally, so
you do not have to modify each task.

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 3
    :class: pb-4
    :card-height: 100

    ..  card:: :ref:`Decide if it fits <introduction>`

        Understand the problem the extension solves, its audience, and its
        scope.

    ..  card:: :ref:`Install and configure <installation>`

        Meet the requirements, install the package, and configure its runtime
        values.

    ..  card:: :ref:`Verify the setup <verify-setup>`

        Run one harmless task and confirm its monitor, check-in, and alert in
        Sentry.

    ..  card:: :ref:`Clean up muted monitors <delete-muted-monitors-command>`

        Preview and delete muted cron monitors manually or with an automatic
        TYPO3 Scheduler task.

..  toctree::
    :hidden:
    :caption: Overview

    Introduction/Index
    Usage/Index

..  toctree::
    :hidden:
    :caption: Setup

    Requirements/Index
    Installation/Index
    Configuration/Index
    VerifySetup/Index

..  toctree::
    :hidden:
    :caption: Operations

    Commands/Index
    Troubleshooting/Index

..  toctree::
    :hidden:
    :caption: Technical reference

    Architecture/Index
    References/Index

Feedback and issues
===================

The documentation is maintained with the extension. Report unclear or outdated
content, problems, and feature requests in the `GitHub issue tracker
<https://github.com/andersundsehr/sentry_cron_monitor/issues>`__.
