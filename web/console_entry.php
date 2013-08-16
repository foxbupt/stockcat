<?php

// change the following paths if necessary
$yii=dirname(__FILE__).'/../../yii-1.1.13/framework/yii.php';
$config=dirname(__FILE__).'/config/console.php';

// remove the following lines when in production mode
defined('YII_DEBUG') or define('YII_DEBUG',true);
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

require_once($yii);

$common = dirname(__FILE__) . '/../../common';
Yii::setPathOfAlias('common', $common);

Yii::createConsoleApplication($config)->run();
