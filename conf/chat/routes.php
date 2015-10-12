<?

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
			$chatlogArray = $chatLog->toArray();
			
			if(count($chatlogArray)>0)
				$app->sfunc->jsonOutput($app, array('status' => 200, 'chatlog' => $chatlogArray));				
			else
				$app->sfunc->notModified304($app); //$app->sfunc->jsonOutput($app, array('status' => 304));
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
			"columns" => "id,og,ot,ta,tt,co",
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
				$app->sfunc->notModified304($app);//$app->sfunc->jsonOutput($app, array('status' => 304));
			//$app->sfunc->jsonOutput($app, array('status' => 200, 'chatlog' => $chatLog->toArray()));//$app->sfunc->jsonOutput($app, $chatLog->toArray());
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