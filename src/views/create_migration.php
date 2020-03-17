<?php

declare(strict_types=1);

/**
 * This is the template for generating the migration of a specified table.
 *
 * @var string $tableName Table name
 * @var string $body Migration content
 * @var string $className Migration class name
 * @var string $namespace Migration namespace
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
<?= $body ?>
    }

    public function down()
    {
        $this->dropTable('<?= $tableName ?>');
    }
}
