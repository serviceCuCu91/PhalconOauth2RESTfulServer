<?php
//use Phalcon\Session\Adapter\Redis as sessionAdpt;

use Phalcon\Mvc\Model\Query;

class NiuUsrInfo extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('usrinfo');
        $this->setConnectionService('niuniudb');
        
        $this->hasOne("id", "NiuCharAttribute", "id");
        $this->hasMany("id", "NiuBankRecord", "uuid");
        $this->hasOne("id", "NiuBankRecord", "uuid");
        $this->hasOne("id", "NiuUsrOwnItem", "id");
    }
    

    public $id;
    public $gasecret;
    public $fbAccessToken;
    public $gpAccessToken;
    public $usrNickName;
    public $fname;
    public $lname;    
    public $usrID;
    protected $passwd;
    
    public $cash;
    public $diamond;
    
    public $created_at;
    public $updated_at;
    public $usrStatus;
    
    public function columnMap()
    {
        //Keys are the real names in the table and the values their names in the application
        return array(
            'id' => 'id',
            'gasecret'	=> 'gasecret',
            'fbAccessToken' => 'fbAccessToken',
            'gpAccessToken' => 'gpAccessToken',
            'twAccessToken'	=> 'twAccessToken',
            'usrID' => 'usrID',
            'usrNickName' => 'usrNickName',
            'fname'	=> 'fname',
            'lname'	=> 'lname',
            'passwd'	=> 'passwd',
            'cash' => 'cash',
            'diamond' => 'diamond',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'usrStatus' => 'usrStatus',
        );
    }
    
    public function updateCashDelta($uuid, $delta)
    {
		// Instantiate the Query
		$query = new Query("UPDATE NiuUsrInfo SET cash = cash + $delta WHERE id = $uuid", $this->getDI());
		// Execute the query returning a result if any
		$cars = $query->execute();
	}
	
    public function get($username = null)
    {
        $param = [];
        if ($username !== NULL) {
            $param[] = "username = '$username'";
        }
        
        $result = $this->find($param)->toArray();
        
        if (count($result) > 0) {
            return $result;
        }
        return null;
    }
    
    public function getByUUID($id = null)
    {
        $param = [];
        if ($id !== NULL) {
            $param[] = "id = '$id'";
        }
        
        $result = $this->find($param)->toArray();
        
        if (count($result) > 0) {
            return $result;
        }        
        return null;
    }
    
    /**
     * Validations and business logic
     */
    public function validation()
    {
    	return true;
    	
        $this->validate(
            new Email(
                array(
                    'field'    => 'email',
                    'required' => true,
                )
            )
        );
        
        if ($this->validationHasFailed() == true) {
            return false;
        }
    }
    
    public function beforeSave()
    {
        // Convert the array into a string
        $this->usrStatus = $this->convertArrayToInt($this->usrStatus);
    }
    
	public function afterFetch()
    {
        // Convert the string to an array
		$this->cash = (int)$this->cash;
		$this->diamond = (int)$this->diamond;
        $this->usrStatus = $this->convertStringToStrArray($this->usrStatus);
    }
    
    public function convertArrayToInt( $arrInput)
	{
		return bindec(strrev(implode($arrInput)));
	}
	
	public function convertStringToStrArray($strInput)
	{
		return str_split(strrev(str_pad(decbin($strInput), 10, '0', STR_PAD_LEFT)));
	}

}