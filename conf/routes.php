<?php

$app->get('/', function () use($app) {
	
	$app->response->setContentType('application/json', 'UTF-8');
	/*
	if(!$app->oauthredis->exists(':AuthCode:51'))
		$app->oauthredis->save(':AuthCode:51', 'gsdfdsasdsdfewrewfddsfsd');
	
	$data = $app->oauthredis->get(':AuthCode:51');
	*/
	
	$apiserverTime = date("Y-m-d H:i:s");
	$modelAtNiuServer = new NiuUsrInfo();
	$modelAtOauthServer = new ChatLogs();
	
	echo json_encode(
		array(
			'status' => 200, 
			'NiuServer' => NiuUsrInfo::findFirst()->id,
			'OauthServer' => ChatLogs::findFirst()->id,
			//'redis' => $data,
			'message' => 'welcome!歡迎!',
			'apiserverTime' => $apiserverTime,
			'dbServerTime' => $modelAtNiuServer->getServerTime(),			
			'authServerTime' => $modelAtOauthServer->getServerTime(),
		), JSON_UNESCAPED_UNICODE);
});

/*
//stay for debug purpose
$app->get('/backdoor/{uuid:[0-9]+}', function($uuid) use($app) {
	
	$user = NiuUsrInfo::findFirst("id = $uuid");
	if(!$user)
		$app->sfunc->forbidden403($app);
	try 
	{
		$app->totp->setSecret($user->gasecret); 
		$totp = $app->totp->calculate(); // stay for debug
		echo ($totp);
	} catch (\Exception $e) {
		var_dump($e);
        $app->oauth->catcher($e);
    }
});

//stay for debug purpose
$app->get('/testDB', function() use($app) {
	try 
	{
		$user = NiuUsrInfo::findFirst();
		$OType = OriginType::findFirst();
		var_dump ($user->id);
		var_dump ($OType->name);
	} catch (\Exception $e) {
        $app->oauth->catcher($e);
    }
});
*/

$app->post('/resource', function() use($app)
{
	$inputs = $app->sfunc->getContentTypeFromPost();
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
		
		$app->response->setContentType('application/json', 'UTF-8');
		echo json_encode(array('status' => 300, 'message' => 'you pass!'));
	} catch (\Exception $e) {
        $app->oauth->catcher($e);
    }
});

$app->post('/access_token', function () use ($app) 
{
	$inputs = $app->sfunc->getContentTypeFromPost();
    try {
        $response = $app->oauth->authorize->issueAccessToken();
        $app->oauth->setData($response);
    } catch (\Exception $e) {
        $app->oauth->catcher($e);
    }
});

$app->get('/authorize', function () use ($app) {
	echo "
	<style type='text/css'>
		div#authorize { width: 320px; text-align: center; margin: 0 auto; border: 5px solid azure;}
	</style>
  
	<div id='authorize'>
		<form action='./authorize' method='post'>
			<input type='radio' name='db' value='NiuGame' checked />NiuGame <input type='radio' name='db' value='Other'>Other<br />
			<hr />
			UUID:<input name=uuid /><br />
			TOTP:<input name=totp /><br />
			<!-- ga:<input name=gasecret><br / debug purpose-->
			<hr />
			<input type='radio' name='client_id' value='testclient' checked />testclient 
			<input type='radio' name='client_id' value='niuapiserver'>niuapiserver
			<input type='radio' name='client_id' value='niugameserver'>niugameserver<br />
			<hr />
			<input name=response_type value=code hidden />
			<input name=scope value=basic hidden />
			<input type='submit' name='SB' value='send' />
		</form>
	</div>
	";
});

$app->post('/authorize', function () use ($app) {
	
	//replace $_POST
	$inputs = $app->sfunc->getContentTypeFromPost();
	$_POST = array_merge($_POST, $inputs); //parse_str($inputs, $_POST);
	$_GET = array_merge($_GET, $_POST); 
	
	//this is POST action, $_GET is always empty
	{
		$_GET['response_type'] = $app->sfunc->setFromInputOrDefault('response_type', $inputs, 'code');
		$_GET['scope'] = $app->sfunc->setFromInputOrDefault('scope', $inputs, 'basic');
		//$_GET['access_type'] = $app->sfunc->setFromInputOrDefault('access_type', $inputs, 'offline');
		$_GET['redirect_uri'] = $app->sfunc->setFromInputOrDefault('redirect_uri', $inputs, 'https://developers.google.com/oauthplayground');// debug only
		$_GET['client_id'] = $app->sfunc->setFromInputOrDefault('client_id', $inputs, 'testclient');//'niuapiserver'; // 'testclient';*/
	}
	
	// @var \League\OAuth2\Server\Grant\AuthCodeGrant $codeGrant
    $authParams = null;
	
    try 
    {
        $codeGrant = $app->oauth->authorize->getGrantType('authorization_code');
        $authParams = $codeGrant->checkAuthorizeParams();
    } 
    catch (\Exception $e) 
    {
        return $app->oauth->catcher($e);
    }
	
	$targetDB = ( isset($_POST['db']) ) ? $_POST['db']: 'default' ;
	
	if( isset($_POST['uuid']) )
		$myUUID = $_POST['uuid'];
		
	if( isset($_POST['totp']) )
		$totp = $_POST['totp'];
	
	switch($targetDB)
	{
		case 'NiuGame':
			$user = NiuUsrInfo::findFirst("id=".$myUUID);
			break;
		case 'default':
		default :
			$user = Users::findFirst("username=".$myUUID);
			break;
	}
	
	if(!$user)
		$app->sfunc->forbidden403($app);
		
	$app->totp->setSecret($user->gasecret);
	
    if ( $app->sfunc->totpVerifition($app, $user->gasecret, $totp) && $authParams) 
	{		
        $redirectUri = $codeGrant->newAuthorizeRequest( $targetDB, $myUUID, $authParams);
        $app->response->redirect($redirectUri,true)->sendHeaders();
    }
    else
    {
    	$app->sfunc->forbidden403($app);
	}
});

//special autheroize for game server
$app->get('/{uuid:[0-9]+}/{gasecret:[A-Z,0-9]+}/authorize', function ($uuid,$gasecret) use ($app) {
	
    $starttime = time();	
    $authParams = null;
	
    try {
        $codeGrant = $app->oauth->authorize->getGrantType('authorization_code');		
        $authParams = $codeGrant->checkAuthorizeParams();
    } catch (\Exception $e) {
        return $app->oauth->catcher($e);
    }
    
    $user = NiuUsrInfo::findFirst(array("gasecret=\"$gasecret\"", "id=$uuid"));
    if ($user && $authParams)
	{        
        $redirectUri = $codeGrant->newAuthorizeRequest('niugame', $uuid, $authParams);
		
		$outputArray = array('code'=>explode( "=", $redirectUri)[1]);		
		$outputArray['time'] = time() - $starttime;
		
        $app->sfunc->jsonOutput($app, $outputArray);
    }
	else
		$app->sfunc->badRequest400($app);
});

//after route wether succcess or not
$app->after(function () use ($app) {
    $returned = $app->getReturnedValue();
    $app->response->sendHeaders();
    
    if ($returned) {
        if(is_scalar($returned))
            echo $returned;
        else
            $app->oauth->setData($returned);
    }
    
    $app->response->send();
});

//after route done
$app->finish(function () use ($app) {
    $app->oauth->cleanData();
});

/**
 * Not found handler
 */
$app->notFound(function () use ($app) {
	$app->sfunc->notFunction404($app);
});