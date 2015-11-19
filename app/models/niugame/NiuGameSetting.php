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
			case "Niu_CashSet":
			case "Niu_DiamondSet":
			case "Niu_BettingSet":
			case "Niu_JackpotReturnRate":
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
			case "Niu_CashSet":
			case "Niu_DiamondSet":
			case "Niu_BettingSet":
			case "Niu_JackpotReturnRate":
				$this->value = $this->convertStringToIntArray($this->value);
			break;			
			default:
			break;
		}
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
		// $strInput contains no "second seperator", example: $strInput = 1_2_3_5 => (1,2,3,5)
		if(strpos($strInput, $SecondSep) == false)
		{
			$keyvalue = array_map( 'intval', explode($firstSep, $strInput) );
			return array_slice($keyvalue, 0);
		}
		
		//first element does contain "second seperator", example: $strInput = 1,3,5_2,4,6_3,6,9 => { (1,3,5),(2,4,6),(3,6,9)}		
		$stringArray = explode($firstSep, $strInput, 6);
		for($i=0; $i < count($stringArray); $i++)
		{
			$keyvalue = array_map( 'intval', explode( $SecondSep, $stringArray[$i] ) );
			$returnArray[$i] = array_slice($keyvalue, 0);
		}
		
		return $returnArray;
	}
}