<?php

/**
 * Registering an autoloader https://docs.phalconphp.com/en/latest/reference/loader.html
 */
$loader = new \Phalcon\Loader();

$loader->registerDirs(
	array(
		$config->application->modelsDir,
		$config->application->niumodelsDir,
		$config->application->chatmodelsDir,
		
		$config->application->controllersDir )
);

$loader->registerNamespaces([
    'Cucu\Phalcon\Oauth2\Storage' 	=> 	$config->application->storagesDir,
    'Cucu\Phalcon\Oauth2\Plugin' 	=> 	$config->application->oauthpluginsDir,
    'Cucu\Phalcon\Totp\Plugin' 		=> 	$config->application->totppluginsDir,
    'Cucu\Phalcon\Plugin' 		=> 	$config->application->pluginsDir,
]);


// Register some prefixes
/*$loader->registerPrefixes(
    array(
        "Example_Base"    => "vendor/example/base/",
        "Example_Adapter" => "vendor/example/adapter/",
        "Example_"        => "vendor/example/"
    )
);*/

// Register some classes
/*$loader->registerClasses(
    array(
        "Some"         => "library/OtherComponent/Other/Some.php",
        "Cucu\Phalcon\Oauth2\Plugin\OauthPlugin"         => $config->application->pluginsDir . "OauthPlugin.php"
    )
);*/

$loader->register();