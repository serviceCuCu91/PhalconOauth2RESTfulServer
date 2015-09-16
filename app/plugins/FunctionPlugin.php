<?php

namespace Cucu\Phalcon\Plugin;

use Phalcon\Mvc\User\Plugin;

class FunctionPlugin extends Plugin
{
	function isValidUUID($app, $uuid)
	{
		$ownerid = $app->sfunc->getUUIDbyAccessToken($app);	
		if($ownerid != $uuid)
			$app->sfunc->badRequest400($app);
	}
	
	function getUUIDbyAccessToken($app)
	{
		$AccessToken = $app->oauth->resource->getAccessToken();
		return $app->oauth->resource->getSessionStorage()->getOwnIDByAccessToken($AccessToken);
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

	function notFunction404($app)
	{
		$app->response->setContentType('application/json', 'UTF-8')
		->setStatusCode(404, "Not Found")->sendHeaders();
		echo json_encode(array('error' => 404, 'message' => "Resource Not Found"));
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