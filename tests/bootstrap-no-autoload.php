<?php

error_reporting(-1);
define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_DEBUG', true);

$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/stubs/Yii.php';

Yii::setAlias('@bizley/migration', __DIR__ . '/../src/');
Yii::setAlias('@bizley/tests', __DIR__);
