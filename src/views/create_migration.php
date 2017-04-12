<?php
/**
 * This is the template for generating the migration of a specified table.
 *
 * @var $tableName string full table name
 * @var $className string class name
 * @var $namespace string namespace
 * @var $columns array columns definitions
 * @var $primaryKey array primary key definition
 * @var $foreignKeys array foreign keys arrays
 * @var $uniqueIndexes array unique indexes arrays
 */

echo "<?php\n";
?>

<?php if ($namespace): ?>
namespace <?= $namespace ?>;
<?php echo "\n"; endif; ?>
use yii\db\Migration;

class <?= $className ?> extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('<?= $tableName ?>', [
<?php foreach ($columns as $name => $definition): ?>
            '<?= $name ?>' => $this<?= $definition ?>,
<?php endforeach; ?>
        ], $tableOptions);
<?php if (count($primaryKey) > 1): ?>

        $this->addPrimaryKey('primary_key', '<?= $tableName ?>', ['<?= implode('\',\'', $primaryKey) ?>']);
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

    public function safeDown()
    {
        echo "<?= $className ?> cannot be reverted.\n";
        return false;
    }
}
