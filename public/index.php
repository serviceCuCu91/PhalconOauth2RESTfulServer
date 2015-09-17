<?php
use Phalcon\Exception as PhalconException;

use League\OAuth2\Server\ResourceServer;

use RelationalExample\Model;
use RelationalExample\Storage;


error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Taipei');

ob_start("ob_gzhandler");// compress content if possibile

try {
	
	/**
	 * Read the configuration
	 */
	if(!file_exists(__DIR__.'/../conf/config.deploy.php'))
		$config = include __DIR__.'/../conf/config.php';
	else
		$config = include __DIR__.'/../conf/config.deploy.php';
	
	/**
	 * Include loader
	 */
	include __DIR__ . '/../conf/loader.php';		
	include __DIR__.'/../vendor/autoload.php';
	
	// Create a events manager
	$eventManager = new Phalcon\Events\Manager();
	// Listen all the application events
	$eventManager->attach('micro', function ($event, $app) 
	{
	    if ($event->getType() == 'beforeExecuteRoute') 
	    {
	    	var_dump($app->request);
			$app->response->redirect("/")->sendHeaders();
			// Return (false) stop the operation
			return false;
	    }
	});

	/**
	 * Starting the application
	 */
	$app = new \Phalcon\Mvc\Micro();	
	/**
	 * Include Services
	 */
	include __DIR__ . '/../conf/services.php';	
	$app->setDi($di);
	
	include __DIR__ . '/../conf/routes.php';
	//$app->setEventsManager($eventManager);
	/**
	 * Handle the request
	 */
	$app->handle();

} catch (PhalconException $e) {
	echo $e->getMessage();
} catch (PDOException $e){
	echo $e->getMessage();
}

//ob_end_flush();