<?php

namespace bizley\tests\mysql;

use bizley\tests\MockMigrationController;
use Yii;
use yii\console\Controller;

class MigrationControllerTest extends MysqlDbUpdaterTestCase
{
    protected function tearDown()
    {
        $this->dbDown('ALL');
        parent::tearDown();
    }

    /**
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    public function testCreateNonExisting()
    {
        $controller = new MockMigrationController('migration', Yii::$app);

        $this->assertEquals(Controller::EXIT_CODE_ERROR, $controller->runAction('create', ['non-existing-table']));

        $output = $controller->flushStdOutBuffer();

        $this->assertContains('> Generating create migration for table \'non-existing-table\' ...ERROR!', $output);
        $this->assertContains('Table \'non-existing-table\' does not exist!', $output);
    }

    /**
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    public function testUpdateNonExisting()
    {
        $controller = new MockMigrationController('migration', Yii::$app);

        $this->assertEquals(Controller::EXIT_CODE_ERROR, $controller->runAction('update', ['non-existing-table']));

        $output = $controller->flushStdOutBuffer();

        $this->assertContains('> Generating update migration for table \'non-existing-table\' ...ERROR!', $output);
        $this->assertContains('Table \'non-existing-table\' does not exist!', $output);
    }

    public function testCreateFileFail()
    {
        $this->dbUp('test_pk');

        $mock = $this->getMockBuilder('bizley\tests\MockMigrationController')
            ->setConstructorArgs(['migration', Yii::$app])->setMethods(['generateFile'])->getMock();
        $mock->method('generateFile')->willReturn(false);

        $this->assertEquals(Controller::EXIT_CODE_ERROR, $mock->runAction('create', ['test_pk']));

        $output = $mock->flushStdOutBuffer();

        $this->assertContains('> Generating create migration for table \'test_pk\' ...ERROR!', $output);
        $this->assertContains('Migration file for table \'test_pk\' can not be generated!', $output);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\db\Exception
     */
    public function testUpdateFileFail()
    {
        $this->dbUp('test_pk');
        Yii::$app->db->createCommand()->addColumn('test_pk', 'col_new', 'INT(11)')->execute();

        $mock = $this->getMockBuilder('bizley\tests\MockMigrationController')
            ->setConstructorArgs(['migration', Yii::$app])->setMethods(['generateFile'])->getMock();
        $mock->method('generateFile')->willReturn(false);

        $this->assertEquals(Controller::EXIT_CODE_ERROR, $mock->runAction('update', ['test_pk']));

        $output = $mock->flushStdOutBuffer();

        $this->assertContains('> Generating update migration for table \'test_pk\' ...ERROR!', $output);
        $this->assertContains('Migration file for table \'test_pk\' can not be generated!', $output);
    }

    public function testCreateSuccess()
    {
        $this->dbUp('test_pk');

        $mock = $this->getMockBuilder('bizley\tests\MockMigrationController')
            ->setConstructorArgs(['migration', Yii::$app])->setMethods(['generateFile'])->getMock();
        $mock->method('generateFile')->willReturn(true);

        $this->assertEquals(Controller::EXIT_CODE_NORMAL, $mock->runAction('create', ['test_pk']));

        $output = $mock->flushStdOutBuffer();

        $this->assertContains('> Generating create migration for table \'test_pk\' ...DONE!', $output);
        $this->assertContains('Generated 1 file(s).', $output);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    public function testUpdateNoNeeded()
    {
        $this->dbUp('test_pk');

        $controller = new MockMigrationController('migration', Yii::$app);

        $this->assertEquals(Controller::EXIT_CODE_NORMAL, $controller->runAction('update', ['test_pk']));

        $output = $controller->flushStdOutBuffer();

        $this->assertContains('> Generating update migration for table \'test_pk\' ...UPDATE NOT REQUIRED.', $output);
        $this->assertContains('No files generated.', $output);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\db\Exception
     */
    public function testUpdateSuccess()
    {
        $this->dbUp('test_pk');
        Yii::$app->db->createCommand()->addColumn('test_pk', 'col_new', 'INT(11)')->execute();

        $mock = $this->getMockBuilder('bizley\tests\MockMigrationController')
            ->setConstructorArgs(['migration', Yii::$app])->setMethods(['generateFile'])->getMock();
        $mock->method('generateFile')->willReturn(true);

        $this->assertEquals(Controller::EXIT_CODE_NORMAL, $mock->runAction('update', ['test_pk']));

        $output = $mock->flushStdOutBuffer();

        $this->assertContains('> Generating update migration for table \'test_pk\' ...DONE!', $output);
        $this->assertContains('Generated 1 file(s).', $output);
    }
}