..  _introduction:

============
Introduction
============

Why this extension exists
=========================

Scheduler tasks often run without a person watching them. A failed import,
export, synchronization, or cleanup task can remain unnoticed until its missing
result causes another problem.

The primary goal of Sentry Cron Monitor is to make these failures visible in the
monitoring tools a team already uses. The extension connects TYPO3 Scheduler to
Sentry Cron Monitoring and routes failure alerts to Microsoft Teams.

Is this extension right for you?
===============================

Use this extension when all of the following statements apply:

* your project runs recurring tasks with TYPO3 Scheduler;
* your team uses Sentry for production monitoring;
* your team uses Microsoft Teams for operational notifications; and
* you want one central integration instead of adding Sentry code to every task.

Scope
=====

The extension monitors Scheduler tasks with cron or interval schedules. It does
not create Scheduler tasks, initialize the Sentry PHP SDK, or provide a test
task. Your project must provide those parts.

Continue with :ref:`usage` to understand the monitoring behavior.
