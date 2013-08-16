<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');

// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'My Web Application',

	// preloading 'log' component
	'preload'=>array('log'),

	// autoloading model and component classes
	'import'=>array(
		'application.models.*',
		'application.components.*',
		// 'common.components.*',
	),

	'modules'=>array(
        'news', 'stock',
	),

	// application components
	'components'=>array(
		'db'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=db_stockcat',
			'emulatePrepare' => true,
			'username' => 'work',
			'password' => 'slanissue',
			'charset' => 'utf8',
		),
       'redis' => array(
            'class' => 'RedisCache',
            'servers' => array(
                array(
                    'host' => '127.0.0.1',
                    'port' => '6379',    
                    'timeout' => 0,
               ),    
           ),   
       ),
		'errorHandler'=>array(
			// use 'site/error' action to display errors
			'errorAction'=>'site/error',
		),
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
				),
				// uncomment the following to show log messages on web pages
				/*
				array(
					'class'=>'CWebLogRoute',
				),
				*/
			),
		),
	),

	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
	'params'=>array(
		// this is used in contact page
		'adminEmail'=>'webmaster@example.com',
        'memberDB' => 'db_stockcat',
        'commonDB' => 'db_stockcat',
        'stockDB' => 'db_stockcat',
        'serviceDB' => 'db_stockcat',
	),
);
