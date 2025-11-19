# Console

Symfony console is a php executable located in /home/you/openrouterapp/bin/ on www-ora container 

## About

`php bin/console about`

Gives information about Symfony and PHP.

## Clear cache

Should be used after each code modification, to empty cache, to guarantee that waht is viewed on browser matches the most recent code. 

`php bin/console cache:clear`

## Debug

### List all dotenv files with variables and values

`php bin/console debug:dotenv`

### Display current routes for an application 

`php bin/console debug:router`

### Debug API Platform resources

`php bin/console debug:api-resource`

### List messages you can dispatch using the message buses

`php bin/console debug:messenger`

### List schedules and their recurring messages

`php bin/console debug:scheduler`

### Display information about your security firewall(s)

`php bin/console debug:firewall`

### List classes/interfaces you can use for autowiring

`php bin/console debug:autowiring`

## Query OpenRouterApp database

`php bin/console doctrine:query:sql "SELECT * FROM your_table"`



