<?php declare(strict_types=1);
/**
 * This is the template for generating the update migration of a specified table.
 *
 * @var $table \bizley\migration\table\TableStructure Table structure
 * @var $className string Class name
 * @var $namespace string Namespace
 * @var $plan \bizley\migration\table\TablePlan Changes definitions
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
<?= $plan->render($table) ?>
    }

    public function down()
    {
        echo "<?= $className ?> cannot be reverted.\n";
        return false;
    }
}
