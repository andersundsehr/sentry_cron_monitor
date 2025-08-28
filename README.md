# Sentry Cron Monitor and Microsoft Teams Alerts Generator for TYPO3 Scheduler Tasks

For this to work you will need:
- Microsoft Teams Integration in Sentry: https://develop.sentry.dev/integrations/msteams/

1.) composer require andersundsehr/sentry_cron_monitor:dev-main

2.) Configure the extension:
  1.) Id of Integration of Microsoft Teams in Sentry: $integrationIdMsTeams
    - This you can get from the URL of the Microsoft Teams Integration in Sentry when you hover over "Configure"
  2.) Organization name in Sentry: $orgName
  3.) Name of Microsoft Teams channel in which the notification should go: $teamsChannelName
  4.) Authorization token for Sentry API: $authToken
    - This you can create in User Auth Tokens in Sentry
    - Make sure to give read and write permissions to alerts

3.) When a scheduler task runs, 
    - it will create a monitor and alert in Sentry for the given scheduler task if there is none yet
    - it will send a check in status to Sentry if the task was sucessful or not
      - if the task fails, it will trigger an alert in Sentry which will send a notification to the configured Microsoft Teams channel, example:
<img width="1227" height="322" alt="image" src="https://github.com/user-attachments/assets/91b7865e-e03a-4bac-89df-2db5f685fa97" />
