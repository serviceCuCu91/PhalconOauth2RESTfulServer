<?php


$app->get('/', function () use($app) {
	
	$app->response->setContentType('application/json', 'UTF-8');
	var_dump($app->totp->getTimeStep());
	var_dump($app->totp->calculate());
	var_dump($app->totp->getWindow());
	var_dump($app->totp->getSecret()->getValue());
	echo json_encode(array('status' => 300, 'message' => 'welcome!歡迎!'), JSON_UNESCAPED_UNICODE);
});

//stay for debug purpose
$app->get('/backdoor/{uuid:[0-9]+}', function($uuid) use($app) {
	try 
	{
		$user = NiuUsrInfo::findFirst("id = $uuid");
		$app->totp->setSecret($user->gasecret); 
		$totp = $app->totp->calculate(); // stay for debug
		echo ($totp);
	} catch (\Exception $e) {
        $app->oauth->catcher($e);
    }
});

$app->post('/resource/niupurchase/{uuid:[0-9]+}', function($uuid) use($app) 
{
	$inputs = $app->sfunc->getContentTypeFromPost();

	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();

		if(!isset($inputs["objIndex"]))
			$app->sfunc->badRequest400($app, "objIndexMissing");
		
		$idToPurchase = $inputs["objIndex"];
		
		//get and check the user id by AccessToken
		$app->sfunc->isValidUUID($app, $uuid);
		$user = NiuUsrInfo::findFirst("id = " . $inputs['uuid']);		
		//$ownedItem = NiuUsrOwnItem::findFirst("id = " . $inputs['uuid']); //$ownedItem = $user->NiuUsrOwnItem;
		
		//check if this player not yet own the item
		if( $user->NiuUsrOwnItem->checkPurchasedByID( $idToPurchase ))//if( $ownedItem->checkPurchasedByID( $idToPurchase ))
			$app->sfunc->badRequest400($app, "Purchased");

		//check this player have enough cash/diamond to pay
		$targetItem;
		
		if(!isset($inputs["ItemTable"]))
			$targetItem = NiuGameItem::findFirst("id = " . $idToPurchase);
		else
			$targetItem = NiuTableItem::findFirst("id = " . ($idToPurchase - 50) );
		
		if( $targetItem->cashCost > 0 && $user->cash < $targetItem->cashCost)
			$app->sfunc->badRequest400($app, "NotEnoughCash");
		else
			$user->cash -= $targetItem->cashCost;
		
		if($targetItem->diamondCost > 0 && $user->diamond < $targetItem->diamondCost )
			$app->sfunc->badRequest400($app, "NotEnoughDiamond");
		else
			$user->diamond -= $targetItem->diamondCost;
		
		//now pruchase the specific item
		$user->NiuUsrOwnItem->PurchaseByID($idToPurchase);//$ownedItem->PurchaseByID($idToPurchase);
		//$ownedItem->save();
		$user->save();
		
		//TODO: insert record to NiuBankRecord
		$record = new NiuBankRecord();
		
		$record->value = ($targetItem->cashCost > 0) ? -$targetItem->cashCost: -$targetItem->diamondCost;//int
		$record->type = "NiuNiu";
		$record->uuid = $inputs['uuid'];
		$record->usingDiamond = $targetItem->diamondCost;//int
		$record->usingCash = $targetItem->cashCost;//int
		$record->ugid = -1;//long?
		$record->gcardid = -1;//long?
		$record->save();
		
		$app->sfunc->jsonOutput($app, array('status' => 200));

	} catch (\Exception $e) {
		var_dump($e);
        $app->oauth->catcher($e);
    }
});

$app->get('/resource/chat/{uuid:[0-9]+}/{tuuid:[0-9]+}/{startid:[0-9]+}', function($uuid,$tuuid,$startid) use($app) {
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
		$chatLog = ChatLogs::find(
		array(
		"conditions" => "id > $startid AND ((og=$uuid AND ta=$tuuid) OR (og=$tuuid AND ta=$uuid) )",
		"columns" => "id,og,ot,ta,tt,co,json",
		"limit"		=> "50",
        "order"		=> "created_at DESC"
    	));
		
		if( $chatLog == true )
		{
			$app->sfunc->jsonOutput($app, $chatLog->toArray());
		}
		else
		{
			$app->sfunc->forbidden403($app);
		}
	} catch (\Exception $e) {
        $app->oauth->catcher($e);
    }
});

$app->get('/resource/chat/{uuid:[0-9]+}/{startid:[0-9]+}', function($uuid,$startid) use($app) 
{
	$waitForSecond = function() {		
		usleep(800000);//0.8 second //usleep(600000);//0.6 second
		return true;
	};
		
	try {
		
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
		$counter = 0;
		do
		{
			$counter++;
			$chatLog = ChatLogs::find(
			array(
			"conditions" => "id > $startid",
			"columns" => "id,og,ot,ta,tt,co",
			"limit"		=> "50",
			"order"		=> "created_at DESC"
			));
		} while ( $counter < 30 && count($chatLog) < 1 && $waitForSecond() );//long polling: counter 40 === 0.6s x 40 = 24 second
			
		if( $chatLog==true)
			$app->sfunc->jsonOutput($app, $chatLog->toArray());
		else
			$app->sfunc->forbidden403($app);
	} 
	catch (\Exception $e) 
	{
        $app->oauth->catcher($e);
    }
});

$app->post('/resource/chat/{uuid:[0-9]+}/{targetid:[0-9]+}', function($uuid,$targetid) use($app) 
{
	$inputs = $app->sfunc->getContentTypeFromPost();
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();

		//get and check the user id by AccessToken
		$app->sfunc->isValidUUID($app, $uuid);
		
		$chatLog = new ChatLogs();
		
		$chatLog->og = $uuid;
		$chatLog->ot = isset($inputs['ot']) ? $inputs['ot'] : "0";
		
		$chatLog->ta = $targetid;
		$chatLog->tt = isset($inputs['tt']) ? $inputs['tt'] : "0";
		
		if( isset($inputs['co']) )
			$chatLog->co = $inputs['co'];
			
		if( isset($inputs['json']) )
			$chatLog->json = $inputs['json'];
			
		$chatLog->save();
		$app->sfunc->jsonOutput($app, array('status' => 200));

	} 
	catch (\Exception $e) 
	{
		var_dump($e);
        $app->oauth->catcher($e);
    }
});

$app->get('/resource/ucharattribute/{uuid:[0-9]+}', function($uuid) use($app) {

	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
		$userCA = NiuCharAttribute::findFirst("id = $uuid");
		
		if( $userCA==true)
		{
			$app->sfunc->jsonOutput($app, $userCA->toArray());
		}
		else
		{
			$app->sfunc->forbidden403($app);
		}
	} 
	catch (\Exception $e) 
	{
        $app->oauth->catcher($e);
    }
});

//update player Character Attribute
$app->post('/resource/ucharattribute/{uuid:[0-9]+}/{target:[a-z]+}/{targetid:[0-9,A-Z,a-z,_]+}', function($uuid,$target,$targetid) use($app) 
{
	$inputs = $app->sfunc->getContentTypeFromPost();
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
		
		//get and check the user id by AccessToken
		$app->sfunc->isValidUUID($app, $uuid);
		
		$user = NiuUsrInfo::findFirst("id = $uuid");	
		
		if( $user )
		{
			$usrCA = $user->NiuCharAttribute;
			if($usrCA->setValue($target, $targetid))
			{
				$usrCA->save();
				$app->sfunc->jsonOutput($app, array('status' => 200, $target => $usrCA->$target));
			}
			else
			{
				$app->sfunc->badRequest400($app);
			}
		}
		else
		{
			$app->sfunc->forbidden403($app);
		}
	} catch (\Exception $e) {
		//echo $e;
        $app->oauth->catcher($e);
    }
});

$app->get('/resource/uinfo/{uuid:[0-9]+}', function($uuid) use($app) {

	try {
		// Check that an access token is present and is valid
		//$app->oauth->resource->isValidRequest();
		$user = NiuUsrInfo::findFirst(
			array(
				"id = $uuid", 
				"columns" => "id,usrNickName,cash,usrID"
			)
		);
		
		if( $user==true)
		{
			$app->sfunc->jsonOutput($app, $user->toArray());
		}
		else
		{
			$app->sfunc->forbidden403($app);
		}
	} catch (\Exception $e) {
		//echo $e;
        $app->oauth->catcher($e);
    }
});

$app->post('/resource/uinfo/{target:[a-z]+}/{targetid:[0-9]+}', function($uuid, $totp) use($app) 
{
	$inputs = $app->sfunc->getContentTypeFromPost();
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
		
		//get and check the user id by AccessToken
		$app->sfunc->isValidUUID($app, $uuid);
		
		$user = NiuUsrInfo::findFirst("id=".$uuid);
		
		if( $user==true )
		{
			$user->updateCashDelta($uuid, -111);
			$app->sfunc->jsonOutput($app, array('status' => 200, 'cashBefore' => $user->cash, 'cashAfter' => ($user->cash - 111)));
		}
		else
		{
			$app->sfunc->forbidden403($app);
		}
	} catch (\Exception $e) {
		//echo $e;
        $app->oauth->catcher($e);
    }
});


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
	<form action='./authorize' method='post'>
		<input type='radio' name='db' value='NiuGame' checked />NiuGame <input type='radio' name='db' value='Other'>Other<br />
		UUID:<input name=uuid /><br />
		TOTP:<input name=totp /><br />
		<!-- ga:<input name=gasecret><br / debug purpose-->
		<input name=response_type value=code hidden /><input name=scope value=basic hidden />
		<input type='submit' name='SB' value='send' />
	</form>";
});

$app->post('/authorize', function () use ($app) {
	
	//replace $_POST
	$inputs = $app->sfunc->getContentTypeFromPost();
	array_merge($_POST, $inputs); //parse_str($inputs, $_POST);
	
	{
	$_GET['response_type'] = 'code';
	$_GET['scope'] = 'basic';
	//$_GET['access_type'] = 'offline';
	
	$_GET['redirect_uri'] = 'https://developers.google.com/oauthplayground';// debug only
	$_GET['client_id'] = 'testclient';
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
	
	if( isset($_POST['db']) )
		$targetDB = $_POST['db'];
		
	if( isset($_POST['uuid']) )
		$myUUID = $_POST['uuid'];
		
	if( isset($_POST['totp']) )
		$totp = $_POST['totp'];
	switch($targetDB)
	{
		case 'NiuGame':
			$user = NiuUsrInfo::findFirst("id=".$myUUID);
			break;
		default :
			$user = Users::findFirst("username=".$myUUID);
	}
	
	$app->totp->setSecret($user->gasecret); 
    if ( 
    $app->sfunc->totpVerifition($app, $user->gasecret, $totp) &&
    $authParams) {        
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