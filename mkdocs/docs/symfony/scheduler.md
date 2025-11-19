Scheduler has been integrated to automatize iTop synchronization. 
Every day at midnight, iTopOrganisations, iTopInstallations and iTopSites are synchronized with iTop throught coreApi.

This is managed with the help of 3 entities :

* www/src/Scheduler/Message/SendDailySyncDataMessage.php
* www/src/Scheduler/Handler/SendDailySyncDataMessageHandler.php
* www/src/Scheduler/SyncItopTaskProvider.php

The transport configuration is in config/packages/messenger.yaml

Official doc (version 7.2) : [Symfony Scheduler](https://symfony.com/doc/current/scheduler.html)

# DEV

## Check scheduler

`php bin/console debug:scheduler`

You should see :

![Picture of debug:scheduler command result](../assets/images/dev_scheduler_debug.png "Schema of debug:scheduler command result")

## Check if Messages are being sent

Before the message is consumed, it should be inserted into messenger_messages table of eegtool database. 
If a consumer is running message are consumed instantly, so they can't be seen into the database.

Run this from www-eeg container to check counted messages:

`php bin/console messenger:stats`

## Check if the Message appear in the table 

from www-eeg container :

`php bin/console doctrine:query:sql "SELECT * FROM messenger_messages"`

or from phpMyAdmin, eegtool database, messenger_messages table.

## Launch a worker that consume messages

`php bin/console messenger:consume -v scheduler_default`

This command starts a worker that listens for messages in the scheduler_default transport (queue).

CTRL+C to stop

or from another command window opened on www-eeg container:

`php bin/console messenger:stop-workers`

## PROD !

### Check if messages exist:

`php bin/console doctrine:query:sql "SELECT * FROM messenger_messages"`

### Manually process a message:

`php bin/console messenger:consume scheduler_default --limit=1 -vv`

### Check worker logs:

`tail -f /var/log/messenger_worker.err.log`

