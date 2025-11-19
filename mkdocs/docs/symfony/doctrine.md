# Doctrine usefull command

Doctrine is the Symfony's ORM.

# Create database

After configuring database link with Symfony website into www/.env

`php bin/console doctrine:database:create`

# Synchronize database schema and code

`php bin/console doctrine:schema:update --force`

# Create a new migration based on the current state of db schema

`php bin/console make:migration`

This command generates a new migration file based on the current state of your database schema and your entity mappings. It compares the current database schema with the mapping information in your entity classes and generates the necessary SQL statements to update the database schema to match the entity mappings.

Use this command when you have made changes to your entity classes and want to create a migration file to apply those changes to the database

# Create a new migration bis

`php bin/console doctrine:migrations:diff`

This command also generates a new migration file, but it does so by comparing the current state of your database schema with the current state of your entity mappings. It generates the necessary SQL statements to bring the database schema in line with the entity mappings.

This command is particularly useful when you have made changes to your entity classes and want to see the differences between the current database schema and the entity mappings before generating the migration file.

# Apply the migration

`php bin/console doctrine:migrations:migrate`

# Validate schema

`php bin/console doctrine:schema:validate`

You must have as response : 

Mapping

 [OK] The mapping files are correct.                                                                                    
                                                                                                                        
Database

 [OK] The database schema is in sync with the mapping files.     