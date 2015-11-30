<?php
/*
$app->get('/resource/niu/cc/{uuid:[0-9]+}', function($uuid) use($app)
{
	try {
	
		/ *$user = NiuUsrInfo::findFirst("id = $uuid");
		
		if( $user==true)
		{
			$CashCardSs = NiuTransferableItem::findFirst(array(
					"ownerUUID = $uuid AND itemType = 'cashcard'",
					"order" => "maxDeposit",
					"bindTypes" => array( "n" => Column::BIND_PARAM_INT, "m" => Column::BIND_PARAM_INT),
					"columns" => "id,buyerUUID as b,maxDeposit as m,nowDeposit as n"
			));
			$app->sfunc->jsonOutput($app, array('status' => 200, 'cashcards' => $CashCardSs->toArray()) );
		}
		else
		{
			$app->sfunc->badRequest400($app, "UserNotFound");
		}* /
		
		$BettingSet = NiuGameSetting::findFirst("gskey = 'Niu_BettingSet'")->value;		
		$DiamondSet = NiuGameSetting::findFirst("gskey = 'Niu_DiamondSet'")->value;
		
		$JackpotReturnRate = NiuGameSetting::findFirst("gskey = 'Niu_JackpotReturnRate'")->value;
		
		$app->sfunc->jsonOutput($app, array('status' => 200, 'BettingSet' => $BettingSet, 'DiamondSet' => $DiamondSet, 'JackpotReturnRate' => $JackpotReturnRate ) );
		
	} catch (\Exception $e) {
		//var_dump($e);
        $app->oauth->catcher($e);
    }
});
*/
$app->get('/resource/niu/cashcard/{uuid:[0-9]+}', function($uuid) use($app)
{
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
			
		//get and check the user id by AccessToken
		$app->sfunc->isValidUUID($app, $uuid);
	
		$user = NiuUsrInfo::findFirst("id = $uuid");
		
		if( !$user )
			$app->sfunc->notFunction404($app, "UserNotFound");
		
		$CashCardSs = NiuTransferableItem::find(array(
				"ownerUUID = $uuid AND itemType = 'cashcard'",
				"order" => "maxDeposit",
				"columns" => "id,buyerUUID,maxDeposit,nowDeposit"
			));
		
		//whether there is a card (or not), always return (empty) array
		$app->sfunc->jsonOutput($app, array('status' => 200, 'cashcards' => $CashCardSs->toArray()) );
		
	} catch (\Exception $e) {
		//var_dump($e);
        $app->oauth->catcher($e);
    }
});

//deposit into specific CashCard
$app->post('/resource/niu/cashcard/{uuid:[0-9]+}/{targetdepositcard:[0-9]+}', function($uuid,$targetdepositcard) use($app)
{
	$inputs = $app->sfunc->getContentTypeFromPost();
			
	//check if parameter is present
	if(!isset($inputs["depositvalue"]))
		$app->sfunc->badRequest400($app, "DepositValueMissing");
	$valueToDeposit = (int)$inputs["depositvalue"];
	
	//block the number when the number > 2,147,483,647
	if( $valueToDeposit < 0 )
		$app->sfunc->badRequest400($app, "InvalidDepositValue");
	
	//check if card exists/user does own the card
	$card = NiuTransferableItem::findFirst(array("id = $targetdepositcard AND ownerUUID = $uuid AND itemType = 'cashcard'"));
	if(!$card)
		$app->sfunc->notFunction404($app, "CardNotFound");
	
	//make sure $valueToDeposit not exceed maxDeposit
	$MaxPossibileDeposit = $card->maxDeposit - $card->nowDeposit;
	if($MaxPossibileDeposit == 0)
		$app->sfunc->badRequest400($app, "CardFilled");
	
	$valueToDeposit = min($valueToDeposit, $MaxPossibileDeposit);
	
	if($card->NiuUsrInfo->cash < $valueToDeposit)
		$app->sfunc->badRequest400($app, "NotEnoughCash");
	
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
			
		//get and check the user id by AccessToken
		$app->sfunc->isValidUUID($app, $uuid);
		
		//deduct cash amount from user
		$card->NiuUsrInfo->cash -= $valueToDeposit;
		
		//add cash amount to the card
		$card->nowDeposit += $valueToDeposit;
		$card->save();
		
		//bank Record
		$bankRec = new NiuBankRecord();
		$bankRec->uuid = (int)$uuid;
		$bankRec->usingCash = $valueToDeposit; // using/purchasing diamond
		//$bankRec->ugid = -1; //outside game
		$bankRec->value = -$valueToDeposit;	
		$bankRec->usingDiamond = 0;
		$bankRec->gcardid = $targetdepositcard; // knew the card number from the beginning
		$bankRec->type = "InGameCashCard";//ingamecashcard
		$bankRec->save();
		
		$app->sfunc->jsonOutput($app, array('status' => 200, 'cashdeposit' =>$valueToDeposit));
		
	} catch (\Exception $e) {
		//var_dump($e);
        $app->oauth->catcher($e);
    }
});

//destroy my CashCard to be Someone's Gift
$app->post('/resource/niu/cashcard/{uuid:[0-9]+}', function($uuid) use($app)
{
	$inputs = $app->sfunc->getContentTypeFromPost();
			
	//check if parameter is present
	if(!isset($inputs["targetplayer"]))
		$app->sfunc->badRequest400($app, "TargetPlayerMissing");
	
	if(!isset($inputs["giftcardid"]))
		$app->sfunc->badRequest400($app, "GiftCardMissing");
	
	$giftID = (int)$inputs["giftcardid"];
	
	//check if card exists/user does own the card
	$card = NiuTransferableItem::findFirst(array("id = $giftID AND ownerUUID = $uuid AND itemType = 'cashcard'"));
	if(!$card)
		$app->sfunc->notFunction404($app, "CardNotFound");
	
	//now create a CASH giftcontent in Text
	$giftContent = "2_0_" . $card->nowDeposit;
	
	$targetPlayer = NiuUsrInfo::findFirst("id = " . (int)$inputs["targetplayer"]);
	if(!$targetPlayer)
		$app->sfunc->notFunction404($app, "TargetUserNotFound");
	
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
			
		//get and check the user id by AccessToken
		$app->sfunc->isValidUUID($app, $uuid);		
		
		//send a gift to targetgift
		$AGift = new GiftBox();
		$AGift->originid = $uuid;
		$AGift->origintype = 2; //niuniu
		$AGift->targetid = $targetPlayer->id;
		$AGift->targettype = 2; //niuniu
		$AGift->content = ( isset( $inputs['content']) ) ? $inputs['content'] : "";
		$AGift->json = $giftContent;
		$AGift->created_at = $app->sfunc->getGMT();
		$AGift->expired_at = ( isset($inputs['expired']) ) ? $app->sfunc->getGMT($inputs['expired']): $app->sfunc->getGMT(259200);//3 day by default
		$AGift->save();
		
		//disable the cashcard
		$card->ownerUUID = -$card->ownerUUID;
		$card->save();
		
		$app->sfunc->jsonOutput($app, array('status' => 200));
		
	} catch (\Exception $e) {
		//var_dump($e);
        $app->oauth->catcher($e);
    }
});

$app->post('/resource/niu/nicknamenation/{uuid:[0-9]+}', function($uuid) use($app) 
{
	$inputs = $app->sfunc->getContentTypeFromPost();
	//check if parameter is present
	if(!isset($inputs["nation"]))
			$app->sfunc->badRequest400($app, "NationMissing");
		//check if parameter is present
	if(!isset($inputs["nickname"]))
			$app->sfunc->badRequest400($app, "NicknameMissing");
		
	//$nname = $inputs["nickname"];
	
	if(NiuUsrInfo::findFirst("usrNickName='" . $inputs["nickname"] . "'"))
			$app->sfunc->badRequest400($app, "NicknameUsed");
		
	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
		
		//get and check the user id by AccessToken
		$app->sfunc->isValidUUID($app, $uuid);
		
		//find my player info
		$user = NiuUsrInfo::findFirst("id = " . $uuid);
		$user->usrNickName = $inputs["nickname"];
		$user->flag = $inputs["nation"];
		$user->save();
		
		$app->sfunc->jsonOutput($app, array('status' => 200));

	} catch (\Exception $e) {
		//var_dump($e);
        $app->oauth->catcher($e);
    }
});

//diamond related operation. either diamond to cash/ currency to diamond/ diamond to cashcard
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
		break;
		case "Cash": // cash purchase			
			$CashSet = NiuGameSetting::findFirst("gskey = 'Niu_CashSet'")->value;
			$DiamondCost = $CashSet[$targetIndex][0];
			
			$CashDeposit = $CashSet[$targetIndex][1];
		break;
		case "Diamond": // diamond purchase
			if(!isset($inputs["ProductIdentifier"]))
				$app->sfunc->badRequest400($app, "ProductIDMissing");
				
			if(!isset($inputs["TransactionReceipt"]))
				$app->sfunc->badRequest400($app, "TransReceiptMissing");
				
			if(!isset($inputs["PurchaseAgency"]))
				$app->sfunc->badRequest400($app, "AgentMissing");
				
			if($inputs["PurchaseAgency"] == "google" && !isset($inputs["RawPurchaseData"]))
				$app->sfunc->badRequest400($app, "RawDataMissing");	
				
			$DiamondSet = NiuGameSetting::findFirst("gskey = 'Niu_DiamondSet'")->value; // int[]{123,340,464,1022,3100,5280}
			//$RealCashCost = $DiamondSet[$targetIndex][0];// not using/no value at this moment
			
			$DiamondCost = 0;
			$DiamondDeposit = $DiamondSet[$targetIndex];
			
			$numberFromProductID = preg_replace('/\D/', '', $inputs["ProductIdentifier"]); // "currency_sycee3100" -> 3100
			
			//make sure DiamondDeposit and the number client claimed are matched
			if($numberFromProductID !=  $DiamondDeposit)
				$app->sfunc->badRequest400($app, "ProductIDMisMatching");
		break;
		default:
			$targetType = "unknown";
			$targetItemType = $inputs["objType"];
			$app->sfunc->badRequest400($app, "UnknownTarget");
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
			
		//giving cash to User, if any.
		if(isset($CashDeposit))
			$user->cash += $CashDeposit;
			
		//deduce diamond fomr user, if any.
		if(isset($DiamondCost))
			$user->diamond -= $DiamondCost;
			
		//giving daiamond to User, if any.
		if(isset($DiamondDeposit))
			$user->diamond += $DiamondDeposit;
			
		$user->save();
		
		//insert a Bank record
		$bankRec = new NiuBankRecord();
		$bankRec->uuid = (int)$uuid;
		$bankRec->usingCash = 0; // using/purchasing diamond
		//$bankRec->ugid = -1; //at store, outside game
		
		$OutPutArray = array('status' => 200);
		
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
				//$NTItem->updated_at = $app->sfunc->getGMT();//default GMT
				$NTItem->save();
				
				$bankRec->value = -$DiamondCost;	
				$bankRec->usingDiamond = $DiamondCost;
				$bankRec->gcardid = $NTItem->id;
				$bankRec->type = "InGameCashCard";//ingamecashcard
				$bankRec->save();
				
				$OutPutArray['CashCardID'] = $NTItem->id;				
			break;
			case "Cash": // cash purchase
			
				$bankRec->value = $CashDeposit;	
				$bankRec->usingDiamond = $DiamondCost;
				//$bankRec->gcardid = -1;
				$bankRec->type = "Deposit";				
				$bankRec->save();
				
				$OutPutArray['CashDeposit'] = $CashDeposit;
			break;
			case "Diamond":
				$bankRec->value = $DiamondCost;
				$bankRec->usingDiamond = 0;
				//$bankRec->gcardid = -1;
				$bankRec->type = "DiamondDeposit";
				$bankRec->save();
				
				//TODO: insert/update the VIP record
				
				$Invoice = new NiuInvoiceRecord();
				$Invoice->bankid = $bankRec->id;
				$Invoice->uuid = (int)$uuid;
				$Invoice->productID = $inputs["ProductIdentifier"];//string				
				$Invoice->sType = $inputs["PurchaseAgency"];//string
				//$Invoice->created_at = $app->sfunc->getGMT();// server default GMT
				
				//only android store return this info
				if($Invoice->sType == "google")
				{
					$Invoice->rawData = $inputs["RawPurchaseData"];//string
					//$Invoice->serverVerifyStatus = -1;
					//TODO: android server side purchase verification
				}
				elseif($Invoice->sType == "apple")				
				{
					//server side purchase verification
					$result = $app->sfunc->doIOSReceipt($inputs["TransactionReceipt"]);
					
					$OutPutArray['serverVerificationStatus'] = $result["status"];//int
					$Invoice->serverVerifyStatus = $result["status"];
				}
				
				$Invoice->transReceipt = $inputs["TransactionReceipt"];//string
				$Invoice->save();
				
				$OutPutArray['invoiceID'] = $Invoice->id;
				$OutPutArray['bankID'] = $bankRec->id;
				$OutPutArray['DiamondDeposit'] = $DiamondDeposit;
			break;
			default:		
			break;
		}
		
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
		//$record->ugid = -1;//long?
		//$record->gcardid = -1;//long?
		//$record->created_at = $app->sfunc->getGMT();// server default GMT
		$record->save();
		
		$app->sfunc->jsonOutput($app, array('status' => 200));

	} catch (\Exception $e) {
		//var_dump($e);
        $app->oauth->catcher($e);
    }
});


///any user grab someone's info including uuid,iconid,iconurl,nation
$app->get('/resource/niu/ucharattribute/{uuid:[0-9]+}', function($uuid) use($app) {

	try {
		// Check that an access token is present and is valid
		$app->oauth->resource->isValidRequest();
		
		$user = NiuUsrInfo::findFirst( "id=$uuid" );
		
		if( !$user)
			$app->sfunc->notFunction404($app, "TargetUserNotFound");
		
		$resultArr["id"] = $user->id;
		$resultArr["nn"] = $user->usrNickName; // nickname
		$resultArr["na"] = $user->flag; // nation or flag
		$resultArr["ic"] = $user->NiuCharAttribute->icon;
		$resultArr["ci"] = $user->NiuCharAttribute->customicon;
		$app->sfunc->jsonOutput($app, array('status' => 200, 'puinfo' => $resultArr ));
	} 
	catch (\Exception $e) 
	{
		//var_dump($e);
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
$app->post('/resource/niu/ucharattribute/{uuid:[0-9]+}/{target:[a-z]+}/{targetitemid:[0-9,A-Z,a-z,_]+}', function($uuid,$target,$targetitemid) use($app) 
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
			if($user->NiuCharAttribute->setValue($target, $targetitemid))
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
		//var_dump($e);
        $app->oauth->catcher($e);
    }
});


$app->get('/resource/niu/announcement', function() use($app) {

	try {
		// Check that an access token is present and is valid
		//$app->oauth->resource->isValidRequest();//it is announcement... no need for security check
		
		$thisTime = "'" . $app->sfunc->getGMT() . "'";// use '' to quote the time string
		
		$announce = NiuAnnouncement::find(
		array(
		"conditions" => "InitiatedAt < $thisTime AND ExpiredAt > $thisTime",
		"columns" => "ID as id,AnnouncementType as ty, AnnouncementTitle as ti,AnnouncementContent as co, UrlToGo as ur",
		"limit"		=> "10",
		"order"		=> "ExpiredAt DESC"
		));
		
		if( !$announce)
			$app->sfunc->notModified304($app);

		$app->sfunc->jsonOutput($app, array('status' => 200, 'ann' => $announce->toArray() ));
	} catch (\Exception $e) {
		var_dump($e);
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
		//var_dump($e);
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
		//var_dump($e);
        $app->oauth->catcher($e);
    }
});