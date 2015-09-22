<?php

// ensure we get report on all possible php errors
error_reporting(-1);

define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_DEBUG', true);
define('YII_ENV', 'test');

$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_NAME'] = 'yii2-tag-dependency-helper.test';

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

Yii::setAlias('@DevGroup/TagDependencyHelper/tests', __DIR__);
Yii::setAlias('@DevGroup/TagDependencyHelper', dirname(__DIR__).'/src/');

