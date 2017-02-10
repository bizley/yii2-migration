# yii2-migration

![Latest Stable Version](https://img.shields.io/packagist/v/bizley/migration.svg) 
[![Total Downloads](https://img.shields.io/packagist/dt/bizley/migration.svg)](https://packagist.org/packages/bizley/migration) 
![License](https://img.shields.io/packagist/l/bizley/migration.svg) 

Migration creator. Generates migration file based on the existing database table.

## Installation

Add the package to your composer.json:

    {
        "require": {
            "bizley/migration": "*"
        }
    }

and run `composer update` or alternatively run `composer require bizley/migration`

## Configuration

Add the following in your configuration file (preferably console configuration file):

    'components' => [
        // ...
    ],
    'controllerMap' => [
        'migration' => [
            'class' => 'bizley\migration\controllers\MigrationController',
        ],
    ],

## Usage

Run console command 

    php yii migration table_name

to create migration for DB table `table_name`.

You can create multiple migrations for many tables at once by separating the names with a comma:

    php yii migration table_name1,table_name2,table_name3

In case the file to be generated already exists user is asked if it should be overwritten.

## Command line parameters

--migrationPath __(default '@app/migrations')__  
Directory storing the migration classes.

--migrationNamespace __(default null)__ **(new in 1.1)**  
Namespace in case of generating namespaced migration. With this option present `migrationPath` is ignored.

--defaultDecision __(default 'n')__ **(new in 1.1)**  
Default decision what to do in case the file to be generated already exists.  
Available options are:
- 'y' = asks before every existing file, overwrite is default option,
- 'n' = asks before every existing file, skip is default option,
- 'a' = doesn't ask, all files are overwritten,
- 's' = doesn't ask, no files are overwritten.

--templateFile __(default '@vendor/bizley/migration/src/views/migration.php')__  
Template file for generating new migrations.

--useTablePrefix __(default true)__  
Whether the table names generated should consider the `tablePrefix` setting of the DB connection.

--db __(default 'db')__  
Application component's ID of the DB connection to use when creating migrations. 
