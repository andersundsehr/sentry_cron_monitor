# aus_sentry_typo3_cron_monitor_generator
Generates sentry cron monitor with alerts for typo3 tasks

1.) composer require andersundsehr/aus_sentry_typo3_cron_monitor_generator:dev-main
2.) configure: 
  $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['aus_sentry_cronmonitor']['sentryProject'] 
  - Name of project in sentry
  $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['aus_sentry_cronmonitor']['sentryTeamsChannelId']
  - Id of Teams channel in sentry
  $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['aus_sentry_cronmonitor']['teamsChannelName']
  - Name of Teams channel in which the notification should go
WIP authToken, sentry url

For this to work you need:
- Microsoft Teams Integration: https://develop.sentry.dev/integrations/msteams/
- authToken
- 
