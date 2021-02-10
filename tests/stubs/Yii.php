<?php

require __DIR__ . '/../../vendor/yiisoft/yii2/BaseYii.php';

class Yii extends \yii\BaseYii
{
}

// no autoloader
Yii::$classMap = require __DIR__ . '/../../vendor/yiisoft/yii2/classes.php';
Yii::$container = new yii\di\Container();
