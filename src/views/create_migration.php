<?php

declare(strict_types=1);

/**
 * This is the template for generating the migration of a specified table.
 *
 * @var $table \bizley\migration\table\TableStructure Table data
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
<?= $table->render() ?>
    }

    public function down()
    {
        $this->dropTable('<?= $table->renderName() ?>');
    }
}
