<?php

//use Phalcon\Mvc\View;
use Phalcon\Mvc\Url as UrlResolver;

use Phalcon\DI\FactoryDefault;

use Phalcon\Db\Adapter\Pdo\Mysql as MysqlPdo;

use Phalcon\Cache\Frontend\Data as FrontendData;
use Phalcon\Cache\Backend\Memcache as BackendMemcache;
use Phalcon\Cache\Backend\Redis as BackendRedis;

use Cucu\Phalcon\Plugin\FunctionPlugin;

$di = new FactoryDefault();

/**
 * Sets the view component
 
$di['view'] = function () use ($config) {
    $view = new View();
    $view->setViewsDir($config->application->viewsDir);

    return $view;
};
*/

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function () use ($config) 
{
    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
});

// <editor-fold defaultstate="collapsed" desc="user-description">
/**
 * Database connection is created based in the parameters defined in the configuration file
 */

$di->set('niuniudb', function () use ($config) 
{
    return new MysqlPdo(
        array(
            "host" => $config->niudatabase->host,
        	"username" => $config->niudatabase->username,
        	"password" => $config->niudatabase->password,
        	"dbname" => $config->niudatabase->dbname
        )
    );
});

// the main db for oauth token/ client
$di->set('db', function () use ($config) 
{
    return new MysqlPdo(
        array(
			"host" => $config->oauthdb->host,
			"username" => $config->oauthdb->username,
			"password" => $config->oauthdb->password,
			"dbname" => $config->oauthdb->dbname
        )
    );
});

$di->setShared('oauthredis', function ($config) 
{
    $redis = new BackendRedis(new Phalcon\Cache\Frontend\Json(array(	"lifetime" => 1800, 'prefix' => 'accTok_.')), 
    array(
	    'host' => $config->redis->host,
	    'port' => $config->redis->port,
	    //'auth' => $config->redis->auth,
	    'index' => 1,
	    'persistent' => false
 	)); 	
 	return $redis;
});

$di->setShared('oauthcode', function ($config) 
{
    $redis = new BackendRedis(new Phalcon\Cache\Frontend\Json(array(	"lifetime" => 1800, 'prefix' => 'accTok_.')), 
    array(
	    'host' => $config->redis->host,
	    'port' => $config->redis->port,
	    //'auth' => $config->redis->auth,
	    'index' => 2,
	    'persistent' => false
 	)); 	
 	return $redis;
});

$di->setShared('oauthtoken', function ($config) 
{
    $redis = new BackendRedis(new Phalcon\Cache\Frontend\Json(array(	"lifetime" => 1800, 'prefix' => 'accTok_.')), 
    array(
	    'host' => $config->redis->host,
	    'port' => $config->redis->port,
	    //'auth' => $config->redis->auth,
	    'index' => 1,
	    'persistent' => false
 	)); 	
 	return $redis;
});
// </editor-fold>


$di->setShared('sfunc', function () 
{
    return new FunctionPlugin();
});

$di->setShared('totp', function () 
{
	$totp = new Rych\OTP\TOTP( Rych\OTP\Seed::generate(32) );
    return $totp;
});

$di['oauth'] = function () 
{
    $oauth = new Cucu\Phalcon\Oauth2\Plugin\OauthPlugin();
    $oauth->initAuthorizationServer();
    $oauth->initResourceServer();
    $oauth->enableAllGrants();
    return $oauth;
};


$di['acl'] = function () 
{

	$acl = new Phalcon\Acl\Adapter\Memory();
	$acl->setDefaultAction(Phalcon\Acl::DENY);
	
	// Create some roles
	//$roleAdmins = new Phalcon\Acl\Role("Administrators", "Super-User role");
	$roleGuests = new Phalcon\Acl\Role("Guests");
	// Add "Guests" role to ACL
	$acl->addRole($roleGuests);

	// Define the "NiuUsrInfo" resource
	$customersResource = new Phalcon\Acl\Resource("NiuUsrInfo");

	// Add "NiuUsrInfo" resource with a couple of operations
	$acl->addResource($customersResource, array("search", "update", "create"));
	
	// Set access level for roles into resources
	$acl->allow("Guests", "NiuUsrInfo", "search");
	$acl->deny("Guests", "NiuUsrInfo", "create");
	$acl->allow("Guests", "NiuUsrInfo", "update");

    return $acl;
};
