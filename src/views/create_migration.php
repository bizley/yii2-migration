<?php
/**
 * This is the template for generating the migration of a specified table.
 *
 * @var $table \bizley\migration\table\TableStructure Table data
 * @var $tableName string full Table name
 * @var $className string Class name
 * @var $namespace string Migration namespace
 */

echo "<?php\n";
?>

<?php if ($namespace): ?>
namespace <?= $namespace ?>;
<?php echo "\n"; endif; ?>
use yii\db\Migration;

class <?= $className ?> extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('<?= $tableName ?>', [
<?php foreach ($table->columns as $column): ?>
            <?= $column->render(); ?>
<?php endforeach; ?>
        ], $tableOptions);
<?php if (array_key_exists('columnNames', $primaryKey) && count($primaryKey['columnNames']) > 1): ?>

        $this->addPrimaryKey('<?= $primaryKey['name'] !== null ? $primaryKey['name'] : 'primary_key' ?>', '<?= $tableName ?>', ['<?= implode('\',\'', $primaryKey['columnNames']) ?>']);
<?php endif; ?>
<?php if ($uniqueIndexes): ?>

<?php foreach ($uniqueIndexes as $index => $columns): ?>
        $this->createIndex('<?= $index ?>', '<?= $tableName ?>', <?= count($columns) === 1 ? '\'' . $columns[0] . '\'' : '[\'' . implode('\',\'', $columns) . '\']' ?>, true);
<?php endforeach; ?>
<?php endif; ?>
<?php if ($foreignKeys): ?>

<?php foreach ($foreignKeys as $key): ?>
        $this->addForeignKey(<?= $key ?>);
<?php endforeach; ?>
<?php endif; ?>
    }

    public function down()
    {
        $this->dropTable('<?= $tableName ?>');
    }
}
