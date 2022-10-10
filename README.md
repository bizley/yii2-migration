![Yii 2 Migration](yii2-migration.png)

# Yii 2 Migration

![Latest Stable Version](https://img.shields.io/packagist/v/bizley/migration.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/bizley/migration.svg)](https://packagist.org/packages/bizley/migration)
![License](https://img.shields.io/packagist/l/bizley/migration.svg)
[![Infection MSI](https://badge.stryker-mutator.io/github.com/bizley/yii2-migration/master)](https://infection.github.io)

## Migration Creator And Updater

In a perfect world you prepare migration files for your database first so you can run it when ready. 
But since this is not a perfect world, sometimes you start with a database already set - with this package 
you can easily **create a migration file** based on your DB schema with **one console command**.

Furthermore, when your DB is updated later on you can **generate a migration file updating the schema** to 
the current state. The package is comparing it with the migration history:

1. History of applied migrations is scanned to gather all modifications made to the table.
2. Virtual table schema is prepared and compared with the current table schema.
3. Differences are generated as an update migration.
4. In case of the migration history not keeping information about the table, a creating migration is generated.

## Installation

Run console command

```
composer require --dev bizley/migration
```

Or add the package to your `composer.json` file:

```json
{
    "require-dev": {
        "bizley/migration": "^4.0"
    }
}
```

then run `composer update`. 

## Configuration

Add the following in your configuration file (preferably console configuration file):

```php
[
    'controllerMap' => [
        'migration' => [
            'class' => 'bizley\migration\controllers\MigrationController',
        ],
    ],
]
```

Additional options are available as following (with their default values):

```php
[
    'controllerMap' => [
        'migration' => [
            'class' => 'bizley\migration\controllers\MigrationController',
            'migrationPath' => '@app/migrations', // Directory storing the migration classes
            'migrationNamespace' => null, // Full migration namespace
            'useTablePrefix' => true, // Whether the table names generated should consider the $tablePrefix setting of the DB connection
            'onlyShow' => false, // Whether to only display changes instead of generating update migration
            'fixHistory' => false, // Whether to add generated migration to migration history
            'skipMigrations' => [], // List of migrations from the history table that should be skipped during the update process
            'excludeTables' => [], // List of database tables that should be skipped for actions with "*"
            'fileMode' => null, // Permission to be set for newly generated migration files
            'fileOwnership' => null, // User and/or group ownership to be set for newly generated migration files
            'leeway' => 0, // Leeway in seconds to apply to a starting timestamp when generating migration
        ],
    ],
]
```

## Usage

The following console command are available (assuming you named the controller `migration` like in the example above):

- List all the tables in the database:

  ```
  php yii migration
  ```
    
  or

  ```
  php yii migration/list
  ```

- Generate migration to create DB table `table_name`:

  ```
  php yii migration/create "table_name"
  ```

- Generate migration to update DB table `table_name`:

  ```
  php yii migration/update "table_name"
  ```

  To generate migrations for all the tables in the database at once (except the excluded ones) use asterisk (*):

  ```
  php yii migration/create "*"
  php yii migration/update "*"
  ```

  You can generate multiple migrations for many tables at once by separating the names with comma:

  ```
  php yii migration/create "table_name1,table_name2,table_name3"
  ```

  You can provide an asterisk as a part of table name to use all tables matching the pattern:

  ```
  php yii migration/update "prefix_*"
  ```

  Creating multiple table migrations at once forces the proper migration order based on the presence of the foreign keys. 
  When tables are cross-referenced the additional foreign keys migration is generated at the end of default generation.

- Extract SQL statements of migration `migration_name` (UP) **New in 4.4.0**:

  ```
  php yii migration/sql "migration_name"
  ```

- Extract SQL statements of migration `migration_name` (DOWN) **New in 4.4.0**:

  ```
  php yii migration/sql "migration_name" "down"
  ```

## Command line parameters

| command              | alias | description                                                                                                            |
|----------------------|:-----:|------------------------------------------------------------------------------------------------------------------------|
| `migrationPath`      | `mp`  | Directory (one or more) storing the migration classes.                                                                 |
| `migrationNamespace` | `mn`  | Namespace (one or more) in case of generating a namespaced migration.                                                  |
| `useTablePrefix`     | `tp`  | Whether the generated table names should consider the `tablePrefix` setting of the DB connection.                      |
| `migrationTable`     | `mt`  | Name of the table for keeping the applied migration information.                                                       |
| `onlyShow`           | `os`  | Whether to only display changes instead of generating an update migration.                                             |
| `generalSchema`      | `gs`  | Whether to use the general column schema instead of the database specific one (see [1] below).                         |
| `fixHistory`         | `fh`  | Whether to add a migration history entry when the migration is generated.                                              |
| `skipMigrations`     |       | List of migrations from the history table that should be skipped during the update process (see [2] below).            |
| `excludeTables`      |       | List of tables that should be skipped.                                                                                 |
| `experimental`       | `ex`  | Whether to run in the experimental mode (see [3] below).                                                               |
| `fileMode`           | `fm`  | Generated file mode to be changed using `chmod`.                                                                       |
| `fileOwnership`      | `fo`  | Generated file ownership to be changed using `chown`/`chgrp`.                                                          |
| `leeway`             | `lw`  | The leeway in seconds to apply to a starting timestamp when generating migration, so it can be saved with a later date |

[1] Remember that with different database types, general column schemas may be generated with different length.

> ### MySQL examples:  
> Column `varchar(255)`  
> generalSchema=false: `$this->string(255)`    
> generalSchema=true: `$this->string()`  

> Column `int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY`    
> generalSchema=false: `$this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY')`  
> generalSchema=true: `$this->primaryKey()`

> When column size is different from DBMS' default, it's kept:  
> Column `varchar(45)`  
> generalSchema=false: `$this->string(45)`    
> generalSchema=true: `$this->string(45)`

[2] Here you can place the migrations containing actions that cannot be covered by the extractor, i.e. when there is a migration 
setting the RBAC hierarchy with the authManager component. Such actions should be kept in a separated migration and placed on 
this list to prevent them from being run during the extraction process.

[3] This mode allows using a raw SQL column definition for the migration updater (i.e. `['column' => 'varchar(255)']` instead 
of `['column' => $this->string()]`). Since the generating process in this mode depends on the individual DBMS syntax 
the results might not be correct. All help improving this mode is more than welcome.

## Important information about RENAMING tables or columns

When you rename a table or a column, remember to generate appropriate migration manually, otherwise this extension will 
not generate an updating migration (in case of the table) or will generate a migration with the command to drop the original column 
and add a renamed one (in case of the column). This is happening because yii2-migration can only compare two states of 
the table without the knowledge of how one state turned into another. While the very result of the migration renaming 
the column and the one dropping it and adding another is the same in terms of structure, the latter **makes you lose data**.

Once you add a renaming migration to the history, it's being tracked by the extension.

## Migrating from v2 or v3 to v4

See [Migrating to version 4.0](migrating_to_v4.md) section.

## Notes

This extension should work with all database types supported in Yii 2 core:

- CUBRID (9.3.x and higher)
- MS SQL Server (2008 and above)
- MySQL (4.1.x, 5.x, 8.x)
- Oracle
- PostgreSQL (9.x and above)
- SQLite (2/3)

Only history of the migrations extending `yii\db\Migration` class can be properly scanned, and only changes applied with
default `yii\db\Migration` methods can be recognised (except for `execute()`, `addCommentOnTable()`, and 
`dropCommentFromTable()` methods). Changes made to the table's data (like `insert()`, `upsert()`, `delete()`, `truncate()`, 
etc.) are not tracked.

Updating migrations process requires for the methods `createTable()`, `addColumn()`, and `alterColumn()` to provide changes 
in columns definition in the form of an instance of `yii\db\ColumnSchemaBuilder` (like `$this->string()` instead of `'varchar(255)'`).

The new 4.4.0 feature with extracting SQL statements from the existing migration supports all methods available in
`yii\db\Migration`.

## Tests

Tests for MySQL, PostgreSQL, and SQLite are provided. Database configuration is stored in `tests/config.php` (you can 
override it by creating `config.local.php` file there).  
Docker Compose file for setting up the databases is stored in `tests/docker`.

## Previous versions

These versions are not developed anymore but still available for all poor souls that are stuck with EOL PHP.
Some of the newest features may not be available there.

| version constraint | PHP requirements |                                    Yii requirements                                     |                                                            
|:------------------:|:----------------:|:---------------------------------------------------------------------------------------:|
|        ^3.6        |      >= 7.1      |                                       >= 2.0.15.1                                       |
|        ^2.9        |      < 7.1       | 2.0.13 to track non-unique indexes, 2.0.14 to handle `TINYINT` and `JSON` type columns. |
