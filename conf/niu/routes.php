<?

$app->post('/resource/niu/purchasebydiamond/{uuid:[0-9]+}', function($uuid) use($app) 
{
	$inputs = $app->sfunc->getContentTypeFromPost();
	
	//check if parameter is present
	if(!isset($inputs["objType"]))
			$app->sfunc->badRequest400($app, "objTypeMissing");
			
	if(!isset($inputs["objIndex"]))
			$app->sfunc->badRequest400($app, "objIndexMissing");
	
	if(isset($inputs["objExtra"]))
			$extraInfo = $inputs["objExtra"];
			
	$targetIndex = (int) $inputs["objIndex"];
	
	switch($inputs["objType"])
	{
		case "DepositCard": // cashcard pruchase		
			$CashCardSet = NiuGameSetting::findFirst("gskey = 'Niu_CashCardSet'")->value;
			$DiamondCost = $CashCardSet[$targetIndex][0];
			$DepositMax = $CashCardSet[$targetIndex][1];
			//check if this player owns this specific kind of GameCard Already
			//$ccard = NiuTransferableItem::findFirst("buyerUUID = $uuid AND maxDeposit = $DepositMax");	
			//if($ccard)		
			//	$app->sfunc->badRequest400($app, "Purchased");
		break;
		case "Cash": // cash purchase			
			$CashSet = NiuGameSetting::findFirst("gskey = 'Niu_CashSet'")->value;
			$DiamondCost = $CashSet[$targetIndex][0];
			$CashDeposit = $CashSet[$targetIndex][1];
		break;
		case "Diamond": // diamond purchase			
			$DiamondSet = NiuGameSetting::findFirst("gskey = 'Niu_DiamondSet'")->value;
			//$RealCashCost = $DiamondSet[$targetIndex][0];// not using at this moment?
			$DiamondDeposit = $DiamondSet[$targetIndex][1];
		break;
		default:
			//$targetType = "unknown";
			$targetItemType = $inputs["objType"];			
		break;
	}
	
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
			
		//get and check the user id by AccessToken
		$app->sfunc->isValidUUID($app, $uuid);
	
		$user = NiuUsrInfo::findFirst("id = " . $uuid);
		
		//check if this player has enough diamond
		if($user->diamond < $DiamondCost)	
			$app->sfunc->badRequest400($app, "NotEnoughDiamond");
		
		//deduce diamond fomr user
		$user->diamond -= $DiamondCost;
		
		//giving cash to User, if any
		if(isset($CashDeposit))
			$user->cash += $CashDeposit;

		$user->save();
		
		//insert a Bank record
		$bankRec = new NiuBankRecord();
		$bankRec->uuid = (int)$uuid;
		$bankRec->usingCash = 0;
		$bankRec->ugid = -1;
		
		
		$OutPutArray =  array('status' => 200);
		switch($inputs["objType"])
		{
			case "DepositCard": // cashcard pruchase
				//giving a CashCard
				$NTItem = new NiuTransferableItem();		
				$NTItem->buyerUUID = (int)$uuid;
				$NTItem->ownerUUID = (int)$uuid;
				$NTItem->itemType = "cashcard";
				$NTItem->maxDeposit = $DepositMax;
				$NTItem->created_at = $app->sfunc->getGMT();
				$NTItem->save();
				
				$OutPutArray['CashCardID'] = $NTItem->id;
				
				$bankRec->value = -$DiamondCost;	
				$bankRec->usingDiamond = $DiamondCost;
				$bankRec->gcardid = $NTItem->id;
				$bankRec->type = "InGameCashCard";//ingamecashcard
			break;
			case "Cash": // cash purchase
				$bankRec->value = $CashDeposit;	
				$bankRec->usingDiamond = $DiamondCost;
				$bankRec->gcardid = -1;
				$bankRec->type = "Deposit";
				$OutPutArray['CashDeposit'] = $CashDeposit;
			break;
			case "Diamond":
				$bankRec->value = $DiamondCost;
				$bankRec->usingDiamond = 0;
				$bankRec->gcardid = -1;
				$bankRec->type = "DiamondDeposit";
				$OutPutArray['DiamondDeposit'] = $DiamondDeposit;
			break;
			default:		
			break;
		}
		
		$bankRec->save();
		
		$app->sfunc->jsonOutput($app, $OutPutArray);

	} catch (\Exception $e) {
		//var_dump($e);
        $app->oauth->catcher($e);
    }
});

$app->post('/resource/niu/purchase/{uuid:[0-9]+}', function($uuid) use($app) 
{
	// Check that an access token is present and is valid
	$app->oauth->resource->isValidRequest();
		
	//get and check the user id by AccessToken
	$app->sfunc->isValidUUID($app, $uuid);
		
	$inputs = $app->sfunc->getContentTypeFromPost();

	//check if parameter is present
	if(!isset($inputs["objType"]))
		$app->sfunc->badRequest400($app, "objTypeMissing");
	
	if(!isset($inputs["objIndex"]))
		$app->sfunc->badRequest400($app, "objIndexMissing");
	
	if(isset($inputs["objOffset"]))
		$offsetNumber = (int)$inputs["objOffset"];// 50 at this moment
	
	$targetItemType = $inputs["objType"];
	$idToPurchase = $inputs["objIndex"];
		
	$user = NiuUsrInfo::findFirst("id = " . $uuid);
	
	//check if this player own the item
	if( $user->NiuUsrOwnItem->checkPurchasedByID( $idToPurchase ))
		$app->sfunc->badRequest400($app, "Purchased");

	//check this player have enough cash/diamond to pay		
	switch($targetItemType)
	{
		case "Item"://model, eq
			$targetItem = NiuGameItem::findFirst("id = " . $idToPurchase);
		break;
		case "TableBG":// desktop, cardBack			
			$targetItem = NiuTableItem::findFirst("id = " . ($idToPurchase - $offsetNumber) );
		break;
		default:
		break;
	}
	
	if(!$targetItem)
		$app->sfunc->badRequest400($app, "ItemNotFound");	
	$app->sfunc->isValidPurchase($targetItem, $user, $app);
		
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
		
		//now pruchase the specific item
		$user->NiuUsrOwnItem->PurchaseByID($idToPurchase);
		$user->cash -= $targetItem->cashCost;
		$user->diamond -= $targetItem->diamondCost;
		$user->save();
		
		//insert record to NiuBankRecord
		$record = new NiuBankRecord();		
		$record->value = ($targetItem->cashCost > 0) ? -$targetItem->cashCost: -$targetItem->diamondCost;//int
		$record->type = "NiuNiu";
		$record->uuid = $uuid;
		$record->usingDiamond = $targetItem->diamondCost;//int
		$record->usingCash = $targetItem->cashCost;//int
		$record->ugid = -1;//long?
		$record->gcardid = -1;//long?
		$record->save();
		
		$app->sfunc->jsonOutput($app, array('status' => 200));

	} catch (\Exception $e) {
        $app->oauth->catcher($e);
    }
});

/*
$app->get('/resource/niu/ucharattribute/{uuid:[0-9]+}', function($uuid) use($app) {

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
*/

//update player Character Attribute
$app->post('/resource/niu/ucharattribute/{uuid:[0-9]+}/{target:[a-z]+}/{targetid:[0-9,A-Z,a-z,_]+}', function($uuid,$target,$targetid) use($app) 
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
			if($user->NiuCharAttribute->setValue($target, $targetid))
			{
				$user->save();
				$app->sfunc->jsonOutput($app, array(
				'status' => 200,				
				$target => $user->NiuCharAttribute->$target
				));
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

$app->get('/resource/niu/uinfo/{uuid:[0-9]+}', function($uuid) use($app) {

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

$app->post('/resource/niu/uinfo/{target:[a-z]+}/{targetid:[0-9]+}', function($uuid, $totp) use($app) 
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