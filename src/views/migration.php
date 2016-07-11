<?php
/**
 * This is the template for generating the migration of a specified table.
 */

/* @var $tableName string full table name */
/* @var $className string class name */
/* @var $columns array columns definitions */
/* @var $foreignKeys array foreign keys arrays */

echo "<?php\n";
?>

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
<?php endforeach ?>
        ], $tableOptions);
        
<?php if ($foreignKeys): ?>
<?php foreach ($foreignKeys as $key): ?>
        $this->addForeignKey(<?= $key ?>);
<?php endforeach ?>
<?php endif ?>
    }
    
    public function safeDown()
    {
        echo "<?= $className ?> cannot be reverted.\n";
        return false;
    }
}
