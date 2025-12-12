# Composer 

Composer is the tool to manage php package like Symfony and its vendors.

In this project the composer executable (composer.phar) is installed at the root of home's directory in www-eeg container (/home/you/composer.phar) 

## Update composer

`../composer.phar self-update`
 
## Update vendors

Vendors are external libraries and Symfony components installed via Composer. Stored in vendor/ directory.

`../composer.phar update`

Update only Symfony' packages

`../composer.phar update "symfony/*" --with-dependencies`

## Check the status of Symfony's recipe

Recipes are configuration files & scripts that help integrate Symfony packages it is managed by Symfony Flex. Stored in config/, templates/, src/, bin/console

`../composer.phar recipes`

If there are some update available, for instance "symfony/web-profiler-bundle (update available)"

You can manually review changes

`../composer recipes:diff symfony/web-profiler-bundle`

## Update recipe 

You can update a single recipe 

`../composer.phar recipes:update symfony/web-profiler-bundle`

Or update all

`../composer.phar recipes:update`

It will provide a list of outdated recipes, you can choose which one to update

If an update failed, or the recipe is broken, you can still reinstall it 

`../composer.phar recipes:install easycorp/easyadmin-bundle --force`

## Migration from Symfony 7.3.7 to 7.4 example

# Modify composer.json pour autorize 7.4
sed -i 's/"^7\.2"/"^7.4"/g' composer.json

# Update
../composer.phar update symfony/* --with-all-dependencies

# Update recipes
../composer.phar recipes:update

# Clear cache
bin/console cache:clear