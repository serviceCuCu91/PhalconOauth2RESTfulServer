<?php
namespace Cucu\Phalcon\Oauth2\Plugin;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\InvalidGrantException;
use League\OAuth2\Server\Exception\OAuthException;
use League\OAuth2\Server\ResourceServer;

use Phalcon\Mvc\User\Plugin;

use Cucu\Phalcon\Oauth2\Storage\AccessToken as AccessTokenStorage;
use Cucu\Phalcon\Oauth2\Storage\AuthCode as AuthCodeStorage;
use Cucu\Phalcon\Oauth2\Storage\Client as ClientStorage;
use Cucu\Phalcon\Oauth2\Storage\RefreshToken as RefreshTokenStorage;
use Cucu\Phalcon\Oauth2\Storage\Scope as ScopeStorage;
use Cucu\Phalcon\Oauth2\Storage\Session as SessionStorage;

class OauthPlugin extends Plugin
{
    protected static $content = [];
    
    /** @var \League\OAuth2\Server\AuthorizationServer */
    public $authorize;
    
    /** @var ResourceServer */
    public $resource;
    
    protected $defaultScope = "basic";
    
    protected $grants = [
        'authorize_code'     => 'League\OAuth2\Server\Grant\AuthCodeGrant',
        'client_credentials' => 'League\OAuth2\Server\Grant\ClientCredentialsGrant',
        'password'           => 'League\OAuth2\Server\Grant\PasswordGrant',
        'refresh_token'      => 'League\OAuth2\Server\Grant\RefreshTokenGrant'
    ];
    
    public function initAuthorizationServer()
    {
        if (!$this->authorize) {
            $authorize = new AuthorizationServer;
            $authorize->setDefaultScope($this->defaultScope);
            $authorize->setSessionStorage(new SessionStorage($this->db));
            $authorize->setAccessTokenStorage(new AccessTokenStorage($this->db));
            $authorize->setRefreshTokenStorage(new RefreshTokenStorage($this->db));
            $authorize->setClientStorage(new ClientStorage($this->db));
            $authorize->setScopeStorage(new ScopeStorage($this->db));
            $authorize->setAuthCodeStorage(new AuthCodeStorage($this->db));
            $this->authorize = $authorize;
        }
        return $this;
    }
    
    public function initResourceServer()
    {
        if (!$this->resource) {
            $this->resource = new ResourceServer(
                new SessionStorage($this->db),
                new AccessTokenStorage($this->db),
                new ClientStorage($this->db),
                new ScopeStorage($this->db)
            );
        }
        return $this;
    }
    
    public function enableGrant($name)
    {
        if (!$this->authorize)
            $this->initAuthorizationServer();
        if (array_key_exists($name, $this->grants)) {
            $grants = $this->grants[$name];
            $this->authorize->addGrantType(new $grants);
        } else {
            throw new InvalidGrantException("$name grant unknown");
        }
        return $this;
    }
    
    public function enableAllGrants()
    {
        if (!$this->authorize)
            $this->initAuthorizationServer();
        foreach ($this->grants as $type => $grantName) {
            $type = new $grantName();
            $this->authorize->addGrantType($type);
            if ($type instanceof \League\OAuth2\Server\Grant\PasswordGrant) 
            {
                $type->setVerifyCredentialsCallback(function ($username, $password) 
                {
                	//$user = \Users::findFirstByUsername($username);
                	if(!isset($_POST['targetdb']))
                	{
						$user = \Users::findFirstByUsername($username);
						if ($user && $this->security->checkHash($password, $user->password))
                        	return $user->username;
					}
                	else
                	{
                		switch($_POST['targetdb'])
                		{
							case "NiuGame":
							$user = \NiuUsrInfo::findFirstById($username);
							$this->totp->setSecret($user->gasecret);
							$this->totp->setWindow(31); //31(sec) == (15s forward and backward)
							if($user && $this->totp->validate($password))
								return $user->id;								
							break;
						}						
					}
                    return false;
                });
            }
        }
    }
    
    public function setData($data)
    {
        static::$content = $data;
        if ('text/xml' == $this->request->getBestAccept()) {
            $this->xmlResponse();
        } else $this->jsonResponse();
        return $this->response;
    }
    
    public function cleanData()
    {
        static::$content = [];
    }
    
    public function catcher(\Exception $e)
    {
        $error = [
            'error' => 500,
            'type'  => 'internal',
            'message' => "Unknown internal error occured"
        ];
        if ($e instanceof OAuthException) {
            foreach ($e->getHttpHeaders() as $header) {
                $this->response->setRawHeader($header);
            }
            $error['error'] = $e->httpStatusCode;
            $error['type'] = $e->errorType;
            $error['message'] = $e->getMessage();
        }
        $this->setData($error);
    }
    
    public function jsonResponse()
    {
        $this->response->setContentType('application/json')->setJsonContent(static::$content);
    }
    
    public function xmlResponse($root = 'response')
    {
        $xmlRoot = new \SimpleXMLElement("<?xml version=\"1.0\"?><$root></$root>");
        static::arrayToXml(static::$content, $xmlRoot);
        $this->response->setContentType('text/xml')->setContent($xmlRoot->asXML());
    }
    
    private static function arrayToXml($content, \SimpleXMLElement &$xmlRoot)
    {
        foreach ($content as $key => $value) {
            if (is_array($value)) {
                $key = is_numeric($key) ? "item$key" : $key;
                $sub = $xmlRoot->addChild("$key");
                static::arrayToXml($value, $sub);
            } else {
                $key = is_numeric($key) ? "item$key" : $key;
                $xmlRoot->addChild("$key", "$value");
            }
        }
    }
}