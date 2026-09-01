..  _installation:

============
Installation
============

Install the package
===================

Run this command in the TYPO3 project root:

..  code-block:: shell

    composer require andersundsehr/sentry_cron_monitor

Composer installs the extension and its Sentry SDK dependency. The extension
registers its Scheduler integration when TYPO3 loads the package.

Clear caches
============

Clear the TYPO3 caches after installing the package:

..  code-block:: shell

    vendor/bin/typo3 cache:flush

The extension has no database schema and adds no Scheduler task of its own.

Next step
=========

Add the project-specific values described in :ref:`configuration`.
