# Sentry: Cron Monitor and Microsoft Teams Alerts Generator for TYPO3 Scheduler Tasks

For this to work you will need:
- Microsoft Teams Integration: https://develop.sentry.dev/integrations/msteams/

1.) composer require andersundsehr/sentry_cron_monitor:dev-master
2.) Configure the extension:
  - Id of Integration of Microsoft Teams in sentry: integrationIdMsTeams
    - This you can get from the url of the Microsoft Teams Integration in Sentry when you hover over Configure
  - Name of Microsoft Teams channel in which the notification should go: teamsChannelName
  - Authorization token for sentry api: authToken
    - This you can create in User Auth Tokens in Sentry
    - Make sure to give read and write permissions to alerts
