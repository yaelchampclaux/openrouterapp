# Installation Step

prerequisite :

* Linux /WSL : docker an docker-compose
* Windows / Mac : Docker Desktop 

## 1 - Clone the project

`git clone yaelchampclaux/openrouterapplication`

This creates a openrouterapplication folder, edit it and then, go into it

`code openrouterapplication/`

`cd openrouterapplication`

## 2 - Get the remote code

Checkout to dev branch

`git checkout dev`

Pull remote dev branch

`git pull`

You should see the folder structure : docker/ mkdocs/ www/ and docker-compose.yml

## 3 - Create the database password file

At the root of ora folder, create the file db_root_password.txt, add a password and save. 

This password required by www-ora and pma-ora containers as you can see in docker-compose_.yml.

Then modify the /www/.env file, replace "your_password" in the following line with the password you wrote into db_root_password.txt :

`DATABASE_URL="mysql://root:your_password@db-ora:3306/ora?serverVersion=11.6.2-MariaDB-ubu2404"`

This .env file links symfony website with mariaDB database.

## 4 - Launch the environment

To launch the dev environment (from openrouterapplication folder), just :

`docker-compose up --build`

## 5 - Add Symfony's vendor to the website and create database structure

Access to www-ora container

`docker exec -it www-ora /bin/bash`

Access to openrouterapplication folder

`cd openrouterapplication`

Install vendors

`../composer.phar install`

Create database structure from code

`php bin/console doctrine:schema:update --force`

# Use the configured environment

`docker-compose up --build`

The environment is executed inside a WSL command window. 

Closing the window (or CTRL+C) where containers are executed shut down containers.

![Picture of OpenRouterApp architecture](../assets/images/Architecture.png "Schema of the 4 containers of OpenRouterApp")

# Access to website, documentation and PhpMyAdmin

* <a href="http://localhost:9310/">OpenRouterApplication</a> (http://localhost:9310/)
* <a href="http://localhost:9311/">PhpMyAdmin</a> (http://localhost:9311/)
* <a href="http://localhost:9312/">Documentation</a> (http://localhost:9312/)

# Access to code

Openrouterapplication code is into www folder

Openrouterapplication documentation code is into mkdocs folder

# Access to containers

__Containers have to be running  for the following command to work.__

Following command needs to be entered in a different command window than the one where containers are running.

### Access website container

`docker exec -it www-ora /bin/bash`

### Access documentation container

`docker exec -it doc-ora /bin/bash`

### Access phpMyAdmin container

`docker exec -it pma-ora /bin/bash`

### Access database container

`docker exec -it db-ora /bin/bash`

## Quit a container

`exit`