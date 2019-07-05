<?php

declare(strict_types=1);

/**
 * This is the template for generating the migration of postponed foreign keys.
 *
 * @var $fks \bizley\migration\table\ForeignKeyData[] Foreign keys data
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
<?php foreach ($fks as $fk): ?>
<?= $fk->render() ?>
<?php endforeach; ?>
    }

    public function down()
    {
        echo "<?= $className ?> cannot be reverted.\n";
        return false;
    }
}
