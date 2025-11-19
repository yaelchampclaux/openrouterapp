openrouterapp# Project Initialisation

The initialisation of the project has been made in two steps:

1. Run the docker-compose file to launch development Environment

    `docker-compose -f docker-compose_openrouterapp.yml up --build`

2. Go into the php server container and initiate a symfony project (folder has to be empty)

    `docker exec -it www-eeg /bin/bash`

    `rmdir openrouterapp/public`

    `php ./composer.phar create-project symfony/skeleton openrouterapp`

# About database creation

After configuring database link with Symfony website into www/.env

`php bin/console doctrine:database:create`

# About Symfony module installed

Many symfony module can be installed to ease development. 

For debugging 

`../composer.phar require --dev symfony/profiler-pack`

For rendering

`../composer.phar require symfony/twig-bundle`

For ORM

`../composer.phar require symfony/orm-pack`

For creating entities

`../composer.phar require --dev symfony/maker-bundle`

For forms

`../composer.phar require form validator security-csrf`

For tests

`../composer.phar require --dev phpunit/phpunit`

For web assets

`../composer.phar require symfony/asset`

For admin, crud

`../composer.phar require easycorp/easyadmin-bundle`

For API platform

`../composer.phar require api`

For Querying CoreApi

`../composer.phar require symfony/http-client`

For Scheduled synchronization

`../composer.phar require messenger`

`../composer.phar require symfony/scheduler`

`../composer.phar require dragonmantank/cron-expression`

`../composer.phar require symfony/doctrine-messenger`

# Following command have been typed into www-ora container inside openrouterapp directory 