<?php

namespace Cucu\Phalcon\Plugin;

use Phalcon\Mvc\User\Plugin;

class FunctionPlugin extends Plugin
{
	function isValidUUID($app, $uuid)
	{
		//$ownerid = $this->getUUIDbyAccessToken($app);
		$ownerid = $app->sfunc->getUUIDbyAccessToken($app);
		if($ownerid != $uuid)
			$this->badRequest400($app);
	}
	
	function isValidPurchase($targetItem, $user, $app)
	{
		if( $targetItem->cashCost > 0 && $user->cash < $targetItem->cashCost)
			$this->badRequest400($app, "NotEnoughCash");
		
		if($targetItem->diamondCost > 0 && $user->diamond < $targetItem->diamondCost )
			$this->badRequest400($app, "NotEnoughDiamond");
	}
	
	function getUUIDbyAccessToken($app)
	{
		$AccessToken = $app->oauth->resource->getAccessToken();
		//var_dump($AccessToken);
		$SessionStorage = $app->oauth->resource->getSessionStorage();
		//var_dump($SessionStorage);
		$OwnerID = $SessionStorage->getOwnIDByAccessToken($AccessToken);
		//var_dump($OwnerID);
		return $OwnerID;
	}
	
	//already in Base64 format
	/*function setReceipt($receipt) {
        if (strpos($receipt, '{') !== false) {
            return base64_encode($receipt);
        } else {
            return $receipt;
        }
    }*/    
	
	function doIOSReceipt($receipt, $withSandBox = false)
	{		
		if($withSandBox)
			$normalResultJson = $this->postIOSReceiptBase("https://sandbox.itunes.apple.com/verifyReceipt", $receipt);//$this->postIOSReceiptSandbox($receipt);
		else
			$normalResultJson = $this->postIOSReceiptBase("https://buy.itunes.apple.com/verifyReceipt", $receipt);//$this->postIOSReceipt($receipt);
		
		if(isset($normalResultJson["status"]))
		{
			switch($normalResultJson["status"])
			{
			case 0:
				return array( "status" => 0 );
				break;
			case 21007: // This receipt is from the test environment, but it was sent to the production environment for verification. Send it to the test environment instead.
				return $this->doIOSReceipt($receipt, true);
				break;
			default:
				return array( "status" => $normalResultJson["status"] );
				break;
			}							
		}
		else
		{
			return  array( "status" => -1 );
		}
	}	
	
	function postIOSReceiptBase($targetUrl, $receipt)
	{
		$ch = curl_init();
		//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		curl_setopt($ch, CURLOPT_URL, $targetUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // don't print result directly
		curl_setopt($ch, CURLOPT_POST, true); // 啟用POST
		curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json'));  //curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json', 'Authorization: '.$authToken)); 
		
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode( array( 'receipt-data' => $receipt) ) ); //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( array( "receipt-data"=> $receipt) )); 
		
		$response = curl_exec($ch);
		$errno    = curl_errno($ch);
        $errmsg   = curl_error($ch);
		curl_close($ch);
		
		if ($errno != 0) 
		{
            return array("status" => $errno, "message" => $errmsg);
        }
        
		// Decode the response to json
		$jsonResponse = json_decode($response, true);
		return $jsonResponse;
	}
	
	public function convertArrayToString( $arrInput, $firstSep = '_', $SecondSep = ',' )
	{
		$stringResult;
		
		if(is_array($arrInput[0])) //if(count(array_slice($arrInput,0,1)) > 1)
		{
			//$keys = array_keys($arrInput);
			for($i=0; $i < count($arrInput); $i++ )
			{
				$stringArray[$i] = implode($SecondSep, $arrInput[$i]);
			}
			$stringResult = implode($firstSep, $stringArray);
		}
		else
		{
			$stringResult = implode($SecondSep, $arrInput);
		}
		return $stringResult;
	}
	
	public function convertStringToIntArray($strInput, $firstSep = '_', $SecondSep = ',')
	{
		// $strInput contains no "second seperator", example: $strInput = 1_2_3_5 => int[](1,2,3,5)
		if(strpos($strInput, $SecondSep) == false)
		{
			$keyvalue = array_map( 'intval', explode($firstSep, $strInput) );
			return array_slice($keyvalue, 0);
		}
		
		//first element does contain "second seperator", example: $strInput = 1,3,5_2,4,6_3,6,9 => int[,]{ (1,3,5),(2,4,6),(3,6,9)}		
		$stringArray = explode($firstSep, $strInput, 6);
		for($i=0; $i < count($stringArray); $i++)
		{
			$keyvalue = array_map( 'intval', explode( $SecondSep, $stringArray[$i] ) );
			$returnArray[$i] = array_slice($keyvalue, 0);
		}
		
		return $returnArray;
	}
	
	public function setFromInputOrDefault($targetKey, $infoArray, $defaultValue)
	{
		var_dump( isset( $_GET[$targetKey] ) );
		
		if(!isset($_GET[$targetKey]))
			if(!isset($infoArray[$targetKey]))
				return $defaultValue;				
			return $infoArray[$targetKey];
		return $_GET[$targetKey];
	}
	
	//return server time in CST
	function getCST($assignedTime = null)
	{
		if($assignedTime == null)
			return date("Y-m-d H:i:s");
		else
			return date("Y-m-d H:i:s", $assignedTime + time() );
	}
	
	//return time in GMT
	function getGMT($assignedTime = null)
	{
		if($assignedTime == null)
			return gmdate("Y-m-d H:i:s");
		else
			return gmdate("Y-m-d H:i:s", $assignedTime + time() );
	}
	
	function getContentTypeFromPost()
	{
		$ctype = array_change_key_case(getallheaders(), CASE_LOWER);
		
		switch( $ctype["content-type"] )
		{			
			default:
			case 'application/json':
				return json_decode(file_get_contents('php://input'), true);
			break;
			case 'application/x-www-form-urlencoded':
				return $_POST;
			break;
		}
	}

	function jsonOutput($app, $content)
	{
		//$app->response->setContentType('application/json', 'UTF-8');		
		//echo json_encode($content, JSON_UNESCAPED_UNICODE);
		
		$app->response
		->setJsonContent($content, JSON_UNESCAPED_UNICODE)
		->setContentType('application/json', 'UTF-8');
	}

	function totpVerifition($app, $gasecret, $totp)
	{
		$app->totp->setSecret($gasecret);			
		return $app->totp->validate($totp);
	}

	function notModified304($app)
	{
		$app->response->setContentType('application/json', 'UTF-8')
		->setStatusCode(304, "Not Modified")->sendHeaders();
		echo json_encode(array('status' => 304));
		exit();
	}
	
	function notFunction404($app, $reason = null)
	{
		$app->response->setContentType('application/json', 'UTF-8')
		->setStatusCode(404, "Not Found")->sendHeaders();
		$output = array('error' => 404, 'message' => "Resource Not Found");
		if($reason != null)
			$output["reason"] = $reason;
		echo json_encode($output);
		exit();
	}

	function forbidden403($app)
	{
		$app->response->setContentType('application/json', 'UTF-8')
		->setStatusCode(403, "Forbidden")->sendHeaders();
		echo json_encode(array('error' => 403, 'message' => "Forbidden"));
		exit();
	}

	function badRequest400($app, $reason = null)
	{
		$app->response->setContentType('application/json', 'UTF-8')
		->setStatusCode(400, "Bad Request")->sendHeaders();
		$output = array('error' => 400, 'message' => "Bad Request");
		if($reason != null)
			$output["reason"] = $reason;
		echo json_encode($output);
		exit();
	}
}