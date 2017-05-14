# yii2-migration

![Latest Stable Version](https://img.shields.io/packagist/v/bizley/migration.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/bizley/migration.svg)](https://packagist.org/packages/bizley/migration)
![License](https://img.shields.io/packagist/l/bizley/migration.svg)

## Migration creator and updater

Generates migration file based on the existing database table and previous migrations.

## Installation

Add the package to your composer.json:

    {
        "require": {
            "bizley/migration": "*"
        }
    }

and run `composer update` or alternatively run `composer require bizley/migration`

## Basic configuration

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

The following console command are available:

    php yii migration
    
or

    php yii migration/list

Lists all the tables in the database.

    php yii migration/create table_name

Generates migration to create DB table `table_name`.

    php yii migration/create-all

Generates migrations to create all DB tables.

    php yii migration/update table_name

Generates migration to update DB table `table_name`.

    php yii migration/update-all

Generates migrations to update all DB tables.

You can generate multiple migrations for many tables at once by separating the names with a comma:

    php yii migration/create table_name1,table_name2,table_name3

In case the file to be generated already exists user is asked if it should be overwritten or appended with number.

## Updating migration

Starting with yii2-migration v2.0 it is possible to generate updating migration for database table.

1. History of applied migrations is scanned to gather all modifications made to the table.
2. Virtual table schema is prepared and compared with current table schema.
3. Differences are generated as update migration.
4. In case of migration history not keeping information about the table creating migration is generated.

## Command line parameters

| --command            | -alias | description                                                             
|----------------------|:------:|-----------------------------------------------------------------------------------------------------------------------
| `db`                 |        | Application component's ID of the DB connection to use when generating migrations. _default:_ `'db'`
| `migrationPath`      | `p`    | Directory storing the migration classes. _default:_ `'@app/migrations'`
| `migrationNamespace` | `n`    | Namespace in case of generating namespaced migration. _default:_ `null`
| `templateFile`       | `F`    | Template file for generating create migrations. _default:_ `'@vendor/bizley/migration/src/views/create_migration.php'`
| `templateFileUpdate` | `U`    | Template file for generating update migrations. _default:_ `'@vendor/bizley/migration/src/views/update_migration.php'`
| `useTablePrefix`     | `P`    | Whether the table names generated should consider the `tablePrefix` setting of the DB connection. _default:_ `1`
| `migrationTable`     | `t`    | Name of the table for keeping applied migration information. _default:_ `'{{%migration}}'`
| `showOnly`           | `s`    | Whether to only display changes instead of generating update migration. _default:_ `0`
| `generalSchema`      | `g`    | Whether to use general column schema instead of database specific (1). _default:_ `0`
| `fixHistory`         | `h`    | Whether to add migration history entry when migration is generated. _default:_ `0`
| `skipMigrations`     |        | List of migrations from the history table that should be skipped during the update process (2). _default:_ `[]`

(1) Remember that with different database types general column schemas may be generated with different length.

> ### MySQL examples:  
> Column `varchar(45)`  
> generalSchema=0: `$this->string(45)`    
> generalSchema=1: `$this->string()`  
> Column `int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY`    
> generalSchema=0: `$this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY')`  
> generalSchema=1: `$this->primaryKey()`

(2) Here you can place migrations containing actions that can not be covered by extractor i.e.  when there is migration 
setting the RBAC hierarchy with authManager component. Such actions should be kept in separated migration and placed on 
this list to prevent them from being run during the extraction process.

## Renaming

When you rename table or column remember to generate appropriate migration manually otherwise this extension will 
not generate updating migration (in case of the table) or will generate migration with command to drop original column 
and add renamed one (in case of the column). This is happening because yii2-migration can only compare two states of 
the table without the knowledge of how one state turned into another. And while the very result of migration renaming 
the column and the one dropping it and adding another is the same in terms of structure, the latter makes you lose data.

Once you add renaming migration to the history it's being tracked by the extension.

## Notes

This extension is tested on MySQL database but should work with all database types supported in Yii 2 core:

- CUBRID (9.3.x and higher)
- MS SQL Server (2008 and above)
- MySQL (4.1.x and 5.x)
- Oracle
- PostgreSQL (9.x and above)
- SQLite (2/3)

Let me know if something is wrong with databases other than MySQL (and in case of MySQL let me know as well).

As far as I know Yii 2 does not keep information about table indexes (except unique ones) and foreign keys' 
ON UPDATE and ON DELETE actions so unfortunately this can not be tracked and applied to generated migrations - 
you have to add it on your own.

Only history of migrations extending `yii\db\Migration` class can be properly scanned and only changes applied with
default `yii\db\Migration` methods can be recognised (with the exception of `execute()`, `addCommentOnTable()` and 
`dropCommentFromTable()` methods). Changes made to table's data (like `insert()`, `delete()`, `truncate()`, etc.) 
are not tracked.
