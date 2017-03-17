<?php
/**
 * This is the template for generating the migration of a specified table.
 *
 * @var $tableName string full table name
 * @var $className string class name
 * @var $namespace string namespace
 * @var $methods array methods definitions
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

<?php foreach ($methods as $definition): ?>
        $this-><?= $definition[0] ?>(<?= $definition[1] ?>);
<?php endforeach; ?>
    }

    public function safeDown()
    {
        echo "<?= $className ?> cannot be reverted.\n";
        return false;
    }
}
