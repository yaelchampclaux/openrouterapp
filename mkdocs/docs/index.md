# Documentation

This is the home page of the documentation for OpenRouterApp. 
openrouterapp is a symfony project.

## OpenRouterApp DEV Architecture

OpenRouterApp is a containerized Symfony application, using PHP 8.3 and MariaDB 11.6.2

It also has a Documentation (mkdocs) and a database admin Tool (Phpmyadmin).

![Picture of OpenRouterApp architecture](./assets/images/Architecture.png "Schema of the 4 containers of OpenRouterApp")

## Access to OpenRouterApp Website, PhpmyAdmin and Documentation




Le container PHP est nommé www-ora et est accessible via http://localhost:9210
Le container phpMyAdmin est nommé pma-ora et est accessible via http://localhost:9211
Le container documentation est nommé doc-ora et est accessible via http://localhost:9212
Le container MariaDB est nommé db-ora
