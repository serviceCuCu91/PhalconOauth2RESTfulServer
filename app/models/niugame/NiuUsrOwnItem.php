<?php
//use Phalcon\Session\Adapter\Redis as sessionAdpt;

class NiuUsrOwnItem extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('UsrOwnItem');
        $this->setConnectionService('niuniudb');
        
        $this->belongsTo("id", "NiuUsrInfo", "id");
    }
	
    public $id;
    public $item_id_1_10;
    public $item_id_11_20;
    public $item_id_21_30;
    public $item_id_31_40;
    public $item_id_41_50;
	
	public $tableitem_id_1_10;

	public function PurchaseByID($itemID)
	{
		$targetArray = (int)(((int)$itemID - 1) / 10);
		$targetIndex = (int)(((int)$itemID - 1) % 10);
		
		switch( $targetArray )
		{
			case 0:
			$this->item_id_1_10[$targetIndex] = 1;
			break;
			case 1:
			$this->item_id_11_20[$targetIndex] = 1;
			break;
			case 2:
			$this->item_id_21_30[$targetIndex] = 1;
			break;
			case 3:
			$this->item_id_31_40[$targetIndex] = 1;
			break;
			case 4:
			$this->item_id_41_50[$targetIndex] = 1;
			break;
			
			case 5:
			$this->tableitem_id_1_10[$targetIndex] = 1;
			break;
		}
	}
	
	public function checkPurchasedByID($itemID)
	{
		if($this->getByID($itemID) > 0 )
			return true;
		return false;
	}
	
	public function getByID($itemID)
	{
		$targetArray = (int)(((int)$itemID - 1) / 10);
		$targetIndex = (int)(((int)$itemID - 1) % 10);
		
		switch( $targetArray )
		{
			case 0:
			return $this->item_id_1_10[$targetIndex];
			break;
			case 1:
			return $this->item_id_11_20[$targetIndex];
			break;
			case 2:
			return $this->item_id_21_30[$targetIndex];
			break;
			case 3:
			return $this->item_id_31_40[$targetIndex];
			break;
			case 4:
			return $this->item_id_41_50[$targetIndex];
			break;
			
			case 5:
			return $this->tableitem_id_1_10[$targetIndex];
			break;
		}
	}
	
	public function getArrayByID($itemID)
	{
		$target = (int)(((int)$itemID - 1) / 10);
		switch( $target )
		{
			case 0:
			return $this->item_id_1_10;
			break;
			case 1:
			return $this->item_id_11_20;
			break;
			case 2:
			return $this->item_id_21_30;
			break;
			case 3:
			return $this->item_id_31_40;
			break;
			case 4:
			return $this->item_id_41_50;
			break;
			
			case 5:
			return $this->tableitem_id_1_10;
			break;
		}
	}
    
    public function beforeSave()
    {
        // Convert the array into a string
        $this->item_id_1_10 = $this->convertArrayToString($this->item_id_1_10);
        $this->item_id_11_20 = $this->convertArrayToString($this->item_id_11_20);
        $this->item_id_21_30 = $this->convertArrayToString($this->item_id_21_30);
        $this->item_id_31_40 = $this->convertArrayToString($this->item_id_31_40);
        $this->item_id_41_50 = $this->convertArrayToString($this->item_id_41_50);
		
		$this->tableitem_id_1_10 = (string)$this->convertArrayToString($this->tableitem_id_1_10);
    }
    
	public function afterFetch()
    {
        // Convert the string to an array
        $this->item_id_1_10 = $this->convertStringToIntArray( $this->item_id_1_10 );
        $this->item_id_11_20 = $this->convertStringToIntArray( $this->item_id_11_20 );
        $this->item_id_21_30 = $this->convertStringToIntArray( $this->item_id_21_30 );
        $this->item_id_31_40 = $this->convertStringToIntArray( $this->item_id_31_40 );
        $this->item_id_41_50 = $this->convertStringToIntArray( $this->item_id_41_50 );
		
		$this->tableitem_id_1_10 = $this->convertStringToIntArray( $this->tableitem_id_1_10 );
    }

	public function convertArrayToString( $arrInput )
	{
		return (string)bindec(strrev(implode($arrInput)));
	}
	
	public function convertStringToIntArray($strInput)
	{
		//return str_split(strrev(str_pad(decbin($strInput), 10, "0", STR_PAD_LEFT)));
		return array_map('intval', str_split(strrev(str_pad(decbin($strInput), 10, "0", STR_PAD_LEFT))) );
	}
}