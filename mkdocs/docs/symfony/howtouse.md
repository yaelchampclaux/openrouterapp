# How to use 

You have to configure your dev environment once, then you'll just have to use the configured environment.

## Configuration of environment (to do once)

### 1 - Clone the project

`git clone https://gitpub.cdn-vas.net/eeg/eegtool.git`

This creates a eegtool folder, edit it and then, go into it

`code eegtool/`

`cd eegtool`

### 2 - Get the remote code

Checkout to dev branch

`git checkout dev`

Pull remote dev branch

`git pull`

You should see the folder structure : docker/ mkdocs/ www/ and docker-compose_eegtool.yml

### 3 - Create the database password file

At the root of eegtool folder, create the file db_root_password.txt, add a password and save. 

This password required by www-eeg and pma-eeg containers as you can see in docker-compose_eegtool.yml.

Then modify the /www/.env file, replace "your_password" in the following line with the password you wrote into db_root_password.txt :

`DATABASE_URL="mysql://root:your_password@db-eeg:3306/eegtool?serverVersion=11.6.2-MariaDB-ubu2404"`

This .env file links symfony website with mariaDB database.

### 4 - Launch the environment

To launch the dev environment (from eegtool folder), just :

`docker-compose -f docker-compose_eegtool.yml up --build`

this will start 4 containers. Please check [dev architecture](architecture.md)

### 5 - Add Symfony's vendor to the website and create database structure

Access to www-eeg container

`docker exec -it www-eeg /bin/bash`

Access to eegtool folder

`cd eegtool`

Install vendors

`../composer.phar install`

Create database structure from code

`php bin/console doctrine:schema:update --force`

### 6 - Access to PhpMyAdmin and add some data in the database

You should ask for an export of data only, obtained by going on phpmyadmin site http://localhost:8911/ of an already working dev environment,
click the eegtool schema then export, custom, unselect structure (just keep data), then Export button.

To import the file to your local database, go on your phpmyadmin site  http://localhost:8911/,
click the eegtool schema then import, Select the file .sql to import - Note .zip are accepted - then Import button.

troubleshooting : if database refuse access, ie. on phpmyadmin Access denied for user 'root'@'172.18.0.5' 

=> docker has problem to read the password... Please check [troubleshooting](troubleshooting.md)

## Use the configured environment

`docker-compose -f docker-compose_eegtool.yml up --build`

The environment is executed inside a WSL command window. 

Closing the window (or CTRL+C) where containers are executed shut down containers.

To access inside container, please be careful to have containers running.

## Access to website, documentation and PhpMyAdmin

* <a href="http://localhost:8910/">Website</a> (http://localhost:8910/)
* <a href="http://localhost:8911/">PhpMyAdmin</a> (http://localhost:8911/)
* <a href="http://localhost:8912/">Documentation</a> (http://localhost:8912/)

## Access to code

EEGTool code is into www folder

EEGTool documentation code is into mkdocs folder

## Access to containers

__Containers have to be running  for the following command to work.__

Following command needs to be entered in a different command window than the one where containers are running.

### Access website container

`docker exec -it www-eeg /bin/bash`

### Access documentation container

`docker exec -it doc-eeg /bin/bash`

### Access phpMyAdmin container

`docker exec -it pma-eeg /bin/bash`

### Access database container

`docker exec -it db-eeg /bin/bash`

## Quit a container

`exit`