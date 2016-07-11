# yii2-migration

Migration creator.

## Installation

Add the package to your composer.json:

    {
        "require": {
            "bizley/migration": "*"
        }
    }

and run ```composer update``` or alternatively run ```composer require bizley/migration```

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

    php yii migration <table_name>

to create migration for DB table <table_name>.

You can create multiple migrations for many tables at once:

    php yii migration <table_name1>,<table_name2>,<table_name3>

## Parameters

--migrationPath __(default '@app/migrations')__ 
Directory storing the migration classes.

--templateFile __(default '@vendor/bizley/migration/src/views/migration.php')__ 
Template file for generating new migrations.

--useTablePrefix __(default true)__ 
Whether the table names generated should consider the `tablePrefix` setting of the DB connection.

--db __(default 'db')__ 
Connection|array|string the DB connection object or the application component ID of the DB connection to use when creating migrations. 
