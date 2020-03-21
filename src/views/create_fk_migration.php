<?php

declare(strict_types=1);

/**
 * This is the template for generating the migration of postponed foreign keys.
 *
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
    public function up()
    {
<?= $bodyUp ?>
    }

    public function down()
    {
<?= $bodyDown ?>
    }
}
