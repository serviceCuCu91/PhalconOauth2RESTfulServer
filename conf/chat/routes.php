<?php

$app->get('/resource/chat/{uuid:[0-9]+}/{tuuid:[0-9]+}/{startid:[0-9]+}', function($uuid,$tuuid,$startid) use($app) {
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
		$chatLog = ChatLogs::find(
		array(
		"conditions" => "id > $startid AND ((originid=$uuid AND targetid=$tuuid) OR (originid=$tuuid AND targetid=$uuid) )",
		"columns" => "id,originid as og, origintype as ot,targetid as ta,targettype as tt,content as co",
		"limit"		=> "50",
        "order"		=> "created_at DESC"
    	));
		
		if( $chatLog == true )
		{
			$chatlogArray = $chatLog->toArray();
			
			if(count($chatlogArray) > 0)
				$app->sfunc->jsonOutput($app, array('status' => 200, 'chatlog' => $chatlogArray));				
			else
				$app->sfunc->notModified304($app);
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
		usleep(2000000);//2 second //usleep(600000);//0.6 second
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
			"columns" => "id,originid as og, origintype as ot,targetid as ta,targettype as tt,content as co", //"id,og,ot,ta,tt,co",
			"limit"		=> "50",
			"order"		=> "created_at DESC"
			));
		} while ( $counter < 12 && count($chatLog) < 1 && $waitForSecond() );//long polling: counter 12 === 2s x 12 = 24 second
			
		if( $chatLog == true)
		{
			$chatlogArray = $chatLog->toArray();
			if(count($chatlogArray)>0)
				$app->sfunc->jsonOutput($app, array('status' => 200, 'chatlog' => $chatlogArray));				
			else
				$app->sfunc->notModified304($app);
		}
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
		
		$chatLog->originid = $uuid;
		$chatLog->origintype = isset($inputs['ot']) ? $inputs['ot'] : "0";
		
		$chatLog->targetid = $targetid;
		$chatLog->targettype = isset($inputs['tt']) ? $inputs['tt'] : "0";
		
		if( isset($inputs['co']) )
			$chatLog->content = $inputs['co'];
			
		if( isset($inputs['json']) )
			$chatLog->json = $inputs['json'];
		
		$chatLog->save();
		$app->sfunc->jsonOutput($app, array('status' => 200, 'logid'=>$chatLog->id));
	} 
	catch (\Exception $e) 
	{
		var_dump($e);
        $app->oauth->catcher($e);
    }
});

// for any user to grab his/her own non-expired gift partial list
$app->get('/resource/niugift/{uuid:[0-9]+}/{startid:[0-9]+}', function($uuid,$startid) use($app) 
{		
	try {		
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
		
		$thisTime = "'" . $app->sfunc->getCST() . "'";// use ' to quote the time string
		
		$gBoxEntries = GiftBox::find(
		array(
		"conditions" => "id>$startid AND targetid=$uuid AND expired_at>$thisTime",
		"columns" => "id,originid as og, origintype as ot,content as co,json", //"id,og,ot,co,json"
		"limit"		=> "10",
		"order"		=> "created_at DESC"
		));
			
		if( $gBoxEntries == true)
		{
			$logArray = $gBoxEntries->toArray();
			if(count($logArray) > 0)
				$app->sfunc->jsonOutput($app, array('status' => 200, 'gift' => $logArray));				
			else
				$app->sfunc->notModified304($app);
		}
		else
			$app->sfunc->forbidden403($app);
	} 
	catch (\Exception $e) 
	{
		var_dump($e);
        $app->oauth->catcher($e);
    }
});


$app->post('/resource/niugift/retrieve/{uuid:[0-9]+}', function($uuid) use($app) 
{
	$inputs = $app->sfunc->getContentTypeFromPost();
	
	//make sure what gifts are we going to receive by id
	if(!isset($inputs["targets"]))
			$app->sfunc->badRequest400($app, "targetMissing");
			
	$targets = $app->sfunc->convertStringToIntArray( $inputs["targets"] );
	
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();

		//get and check the user id by AccessToken
		$app->sfunc->isValidUUID($app, $uuid);
		
		$thisTime = "'" . $app->sfunc->getCST() . "'";// use ' to quote the time string
		$outputString = "";
		$outputString2 = "";
		$thsUser = NiuUsrInfo::findFirst("id = " . $uuid);
		
		foreach($targets as $val)
		
		{
			$gBoxEntry = //GiftBox::findFirst("id = " . $val . " AND targetid = " . $uuid. " AND expired_at > " . $thisTime);
			GiftBox::findFirst(
				array("conditions" => "id = $val AND targetid = $uuid AND expired_at > $thisTime"    //"id>$startid AND targetid=$uuid AND expired_at>$thisTime",
			));
			
			if(!$gBoxEntry)
			{
				$outputString2 = $outputString2 . "," . $val;
				continue;	
			}
			
			$giftContent = $app->sfunc->convertStringToIntArray( $gBoxEntry->json );//now we get int[,]
			
			//$giftContent[0][] is the type based on public enum NiuPurchaseType, 
			//$giftContent[1][] is the index of the item, 
			//$giftContent[2][] is the amount in general
			for($i = 0; $i < count($giftContent); $i++)
			{
				switch($giftContent[0][$i])
				{
					case 0: //cashcard
					//TODO:
					break;
					case 1: //diamond
						$thsUser->diamond += $giftContent[2][$i];
					break;
					case 2: //cash
						$thsUser->cash += $giftContent[2][$i];
					break;
					case 3: //eq
					break;
					case 4: // TableBG
					break;
					case 5: // CardBack
					break;
					default:
					break;
				}
			}
			
			$gBoxEntry->targetid = $gBoxEntry->targetid * -1;
			$gBoxEntry->save();
			
			$outputString = $outputString . "," . $gBoxEntry->id;
		}
		
		$app->sfunc->jsonOutput( $app, array( 'status' => 200, 'success' => trim($outputString, ","), 'fail' =>  trim($outputString2, ",") ) );
	} 
	catch (\Exception $e) 
	{
		var_dump($e);
        $app->oauth->catcher($e);
    }
});

$app->post('/resource/niugift/sendgift/{uuid:[0-9]+}', function($uuid) use($app) 
{
	$inputs = $app->sfunc->getContentTypeFromPost();
	
	if(!isset($inputs["targets"]))
			$app->sfunc->badRequest400($app, "targetMissing");
	
	$targets = $app->sfunc->convertStringToIntArray( $inputs["targets"] );
	
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();

		//get and check the user id by AccessToken
		$app->sfunc->isValidUUID($app, $uuid);
			
		$outputString = "";
		foreach($targets as $val)
		
		{
			$gBoxEntry = new GiftBox();
		
			$gBoxEntry->originid = $uuid;
			$gBoxEntry->created_at = $app->sfunc->getCST();
			$gBoxEntry->origintype = isset($inputs['ot']) ? $inputs['ot'] : "0";
			$gBoxEntry->targettype = isset($inputs['tt']) ? $inputs['tt'] : "0";
			
			if( isset($inputs['co']) )
				$gBoxEntry->content = $inputs['co'];
				
			if( isset($inputs['json']) )
				$gBoxEntry->json = $inputs['json'];// example: 2_0_1000
				
			$gBoxEntry->expired_at = ( isset($inputs['expired']) ) ? $app->sfunc->getCST($inputs['expired']): $app->sfunc->getCST(259200);//3 day by default
			$gBoxEntry->targetid = $val;
			$gBoxEntry->save();
			$outputString = $outputString . "," . $gBoxEntry->id;
		}
		
		$app->sfunc->jsonOutput( $app, array( 'status' => 200, 'giftid' => trim($outputString, ",") ) );
	} 
	catch (\Exception $e) 
	{
		var_dump($e);
        $app->oauth->catcher($e);
    }
});