<?php

declare(strict_types=1);

namespace bizley\migration\tests\migrations;

use yii\db\Migration;

class m180328_205900_drop_column_one_from_table_test_multiple extends Migration
{
    public function up(): void
    {
        $this->dropColumn('{{%test_multiple}}', 'one');
    }

    public function down(): void
    {
        $this->addColumn('{{%test_multiple}}', 'one', $this->integer());
    }
}
