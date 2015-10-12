<?php

class NiuGameSetting extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('gamesetting');
        $this->setConnectionService('niuniudb');
    }
	
    public $gskey;
    public $value;
    public $desc;
    
    public function columnMap()
    {
        //Keys are the real names in the table and the values their names in the application
        return array(
            'gskey' => 'gskey',
            'value'	=> 'value',
            'desc' => 'desc',
        );
    }
	
	/***
	public function getJackpotValue()
	{
		if($this->gskey == "JackpotValue")
			return (int)$this->value;
		else
			return -1;
	}
	
	public function getCashCardSet()
	{
		if($this->gskey == "Niu_CashCardSet")
			return $this->convertStringToIntArray($this->value);
		else
			return -1;
	}
	
	public function getBettingSet()
	{
		if($this->gskey == "Niu_BettingSet")
			return $this->convertStringToIntArray($this->value);
		else
			return -1;
	}
	
	public function setJackpotValue()
	{
		if($this->gskey == "JackpotValue")
			return (string)$this->value;
		else
			return -1;
	}
	
	public function setCashCardSet()
	{
		if($this->gskey == "Niu_CashCardSet")
			return $this->convertArrayToString($this->value);
		else
			return -1;
	}
	
	public function setBettingSet()
	{
		if($this->gskey == "Niu_BettingSet")
			return $this->convertArrayToString($this->value);
		else
			return -1;
	}
	***/
	
    public function beforeSave()
    {
		switch( $this->gskey )
		{
			case "JackpotValue":
				return (string)$this->value;
			break;
			case "Niu_CashCardSet":
				return $this->convertArrayToString($this->value);
			break;
			case "Niu_CashSet":
				return $this->convertArrayToString($this->value);
			break;
			case "Niu_DiamondSet":
				return $this->convertArrayToString($this->value);
			break;
			case "Niu_BettingSet":
				return $this->convertArrayToString($this->value);
			break;
			default:
			break;
		}
    }
    
	public function afterFetch()
    {
		switch( $this->gskey )
		{
			case "JackpotValue":
				$this->value = (int)$this->value;
			break;
			case "Niu_CashCardSet":
				$this->value = $this->convertStringToIntArray($this->value);
			break;
			case "Niu_CashSet":
				$this->value = $this->convertStringToIntArray($this->value);
			break;
			case "Niu_DiamondSet":
				$this->value = $this->convertStringToIntArray($this->value);
			break;
			case "Niu_BettingSet":
				$this->value = $this->convertStringToIntArray($this->value);
			break;
			default:
			break;
		}
    }

	public function convertArrayToString( $arrInput )
	{
		$stringResult;
		
		if(is_array($arrInput[0])) //if(count(array_slice($arrInput,0,1)) > 1)
		{
			//$keys = array_keys($arrInput);
			for($i=0; $i < count($arrInput); $i++ )
			{
				$stringArray[$i] = implode(",", $arrInput[$i]);
			}
			$stringResult = implode(".", $stringArray);
		}
		else
		{
			$stringResult = implode(",", $arrInput);
		}
		return $stringResult;
	}
	
	public function convertStringToIntArray($strInput)
	{
		$stringArray = explode(".", $strInput, 6);
		for($i=0; $i < count($stringArray); $i++)
		{
			$keyvalue = array_map('intval', explode(',', $stringArray[$i]));
			//$returnArray[$keyvalue[0]] = array_slice($keyvalue,1);
			$returnArray[$i] = array_slice($keyvalue,0);
		}		
		return $returnArray;
	}
}