<?php

return new \Phalcon\Config(array(
    'oauthdb' => array(
        'adapter'    => 'Mysql',
        'host'       => 'localhost',
        'username'   => 'oauthapi',
        'password'   => 'oauthapi',
        'dbname'     => 'sumeko'
    ),
    
    'niudatabase' => array(
        'adapter'    => 'Mysql',
        'host'       => 'localhost',
        'username'   => 'NiuNiu',
        'password'   => 'niuniu',
        'dbname'     => 'niuniu'
    ),
    
    'redis' => array(
        'host'		=> 'localhost',
        'port'		=> 6379,
        'auth'		=> '',
        'dbname'	=> 'niuniu'
    ),
    
    'application' => array(    
		'controllersDir'      => __DIR__ . '/../app/controllers/',
		
        'modelsDir'      => __DIR__ . '/../app/models/',
		'niumodelsDir'      => __DIR__ . '/../app/models/niugame',
		'chatmodelsDir'      => __DIR__ . '/../app/models/chat',
		
		'oauthpluginsDir'      => __DIR__ . '/../app/plugins/oauth/',
		'totppluginsDir'      => __DIR__ . '/../app/plugins/totp/',
		'pluginsDir'      => __DIR__ . '/../app/plugins/',
		
        'storagesDir'      => __DIR__ . '/../app/storages',
        'baseUri'        => '/phalcon/PhalconOauth2RESTfulServer/'
    )
));
