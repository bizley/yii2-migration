# Migrating to version 4.0

This library has been almost completely rewritten and some of the old options might not work anymore. Here is the list of
most important changes from end-user perspective. All class references should be prefixed with `\bizley\migration\`.

Option changes
--------------

### migrationPath

New alias is `mp`.

### migrationNamespace

New alias is `mn`.

### templateFile

Not available anymore as a controller's option.

To replace the current `migration.php` view file you can provide your own `Generator` class (with your version of 
`Generator::getCreateTableMigrationTemplate()`) through `controllers\MigrationController::$generatorClass`.

### templateFileUpdate

Not available anymore as a controller's option.

To replace the current `migration.php` view file you can provide your own `Updater` class (with your version of 
`Updater::getUpdateTableMigrationTemplate()`) through `controllers\MigrationController::$updaterClass`.

### useTablePrefix

New alias is `tp`.

### migrationTable

New alias is `mt`.

### showOnly

Renamed to `onlyShow` (new alias is `os`).

### generalSchema

New alias is `gs`.

### fixHistory

New alias is `fh`.

### tableOptionsInit

Not available anymore as a controller's option.

To modify the default table options you can provide your own `StructureRenderer` class (with your version
of `renderers\StructureRenderer::$createTableTemplate`) through `controllers\MigrationController::$structureRendererClass`.

### tableOptions

See `tableOptionsInit` above.

### templateFileForeignKey

Not available anymore as a controller's option.

To replace the current `migration.php` view file you can provide your own `Generator` class (with your version of 
`Generator::getCreateForeignKeysMigrationTemplate()`) through `controllers\MigrationController::$generatorClass`.

Action changes
--------------

### create

Can take now asterisk (`*`) as an argument (to generate migrations for all tables in database except excluded ones).
Also accept asterisks as a part of table name(s) (i.e. `ta*e`, to generate migrations for all tables matching the 
pattern, except excluded ones).

### create-all

Not available anymore. Use `create *` instead.

### update

Can take now asterisk (`*`) as an argument (to generate migrations for all tables in database except excluded ones).
Also accept asterisks as a part of table name(s) (i.e. `tab*le`, to generate migrations for all tables matching the 
pattern, except excluded ones).

### update-all

Not available anymore. Use `update *` instead.
