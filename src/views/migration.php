<?php

/**
 * This is the template for generating the migration.
 */

declare(strict_types=1);

/**
 * @var string $bodyUp Migration content for up()
 * @var string $bodyDown Migration content for down()
 * @var string $className Migration class name
 * @var string $namespace Migration namespace
 */

echo "<?php\n";
?>

<?php if ($namespace) : ?>
namespace <?= $namespace ?>;
<?php echo "\n"; endif; ?>
use yii\db\Migration;

class <?= $className ?> extends Migration
{
    public function safeUp()
    {
<?= $bodyUp ?>

    }

    public function safeDown()
    {
<?= $bodyDown ?>

    }
}
