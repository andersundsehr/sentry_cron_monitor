..  _configuration:

=============
Configuration
=============

Configure the extension in :file:`config/system/additional.php`. This file is
not rewritten by TYPO3 backend configuration tools and can read values supplied
by each deployment environment.

1. Provide environment variables
================================

Define these variables in the deployment platform or secret manager:

..  code-block:: bash

    SENTRY_MS_TEAMS_INTEGRATION_ID=123456
    SENTRY_ORGANIZATION=my-organization
    SENTRY_MS_TEAMS_CHANNEL=Operations
    SENTRY_AUTH_TOKEN=replace-with-a-secret
    SENTRY_CRON_TIMEZONE=Europe/Berlin

Ensure that the variables are available in ``$_ENV`` before TYPO3 loads
:file:`config/system/additional.php`. The exact setup depends on the project and
hosting platform.

2. Map the variables to TYPO3
============================

Add this configuration:

..  code-block:: php
    :caption: config/system/additional.php

    <?php

    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['sentry_cron_monitor'] = [
        'integrationIdMsTeams' => (int) ($_ENV['SENTRY_MS_TEAMS_INTEGRATION_ID'] ?? 0),
        'orgName' => $_ENV['SENTRY_ORGANIZATION'] ?? '',
        'teamsChannelName' => $_ENV['SENTRY_MS_TEAMS_CHANNEL'] ?? '',
        'authToken' => $_ENV['SENTRY_AUTH_TOKEN'] ?? '',
        'timezone' => $_ENV['SENTRY_CRON_TIMEZONE'] ?? 'Europe/Berlin',
    ];

..  important::

    Do not commit ``SENTRY_AUTH_TOKEN`` or another secret to the repository.
    Store secrets in the mechanism provided by the deployment environment.

3. Clear the cache
==================

Apply the new system configuration:

..  code-block:: shell

    vendor/bin/typo3 cache:flush

Configuration reference
=======================

``integrationIdMsTeams``
    Environment variable: ``SENTRY_MS_TEAMS_INTEGRATION_ID``

    Numeric integration ID from the target URL of the
    :guilabel:`Configure` link in the Sentry Microsoft Teams integration.

``orgName``
    Environment variable: ``SENTRY_ORGANIZATION``

    Sentry organization slug, not its display name. The extension combines this
    slug with the project ID from the configured DSN to build the alert-rule API
    URL.

``teamsChannelName``
    Environment variable: ``SENTRY_MS_TEAMS_CHANNEL``

    Exact name of the Microsoft Teams channel that receives notifications.

``authToken``
    Environment variable: ``SENTRY_AUTH_TOKEN``

    Sentry user authentication token with ``alerts:read`` and ``alerts:write``
    permissions. The extension uses it to manage issue alert rules and to list
    and delete cron monitors.

..  _configuration-timezone:

``timezone``
    Environment variable: ``SENTRY_CRON_TIMEZONE``

    IANA timezone used for Sentry monitor schedules, for example
    ``Europe/Berlin``.

The extension requires no task-specific configuration. Continue with
:ref:`verify-setup` to confirm the integration.
