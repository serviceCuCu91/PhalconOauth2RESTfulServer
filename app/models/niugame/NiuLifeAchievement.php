<?php

class NiuLifeAchievement extends \Phalcon\Mvc\Model
{
	public $ID;
	public $winTotal;	
	public $niuNiuGotten;
	public $crazyNiuGotten;
	public $dealerActing;
	public $noNiuTotal;
	public $niuOneTotal;
	
	public $getNiuTotal;
	public $betInAllRoom;
	public $realMoneyPurchased;
	
	public function initialize()
    {
        $this->setSource('niuachievement');
        $this->setConnectionService('niuniudb');
    }
    
    public function columnMap()
    {
        //Keys are the real names in the table and the values their names in the application
        return array(
            'id' => 'ID',
            'winTotal' => 'winTotal',
            'crazyNiuTotal'	=> 'niuNiuGotten',
            'fiveClubNiuTotal'	=> 'crazyNiuGotten',
            'dealerTotal' => 'dealerActing',            
            'noNiuTotal' => 'noNiuTotal',
            'niuOneTotal' => 'niuOneTotal',
            
            'getNiuTotal'	=> 'getNiuTotal',
			'betInAllRoom'	=> 'betInAllRoom',
			'realMoneyPurchased'	=> 'realMoneyPurchased',
        );
    }
	
    public function beforeSave()
    {
    	$this->getNiuTotal = $this->convertArrayToString( $this->getNiuTotal);
    	$this->betInAllRoom = $this->convertArrayToString( $this->betInAllRoom);
    }
    
	public function afterFetch()
    {
    	$this->ID = (int)$this->ID;//long
    	$this->winTotal = (int)$this->winTotal;//int
    	$this->niuNiuGotten = (int)$this->niuNiuGotten;//int
    	$this->crazyNiuGotten = (int)$this->crazyNiuGotten;//int
    	$this->dealerActing = (int)$this->dealerActing;//int
    	$this->noNiuTotal = (int)$this->noNiuTotal;//int
    	$this->niuOneTotal = (int)$this->niuOneTotal;//int
    	$this->realMoneyPurchased = (int)$this->realMoneyPurchased;//int
    	
    	
    	$this->getNiuTotal = $this->convertStringToIntArray( $this->getNiuTotal, 12);// length == 12 (0~11)
    	$this->betInAllRoom = $this->convertStringToIntArray( $this->betInAllRoom, 5);// length == 5 (1~4)
    }
    
    public function convertArrayToString( $arrInput )
	{
		return (string)bindec(strrev(implode($arrInput)));
	}
	
	public function convertStringToIntArray($strInput, $stringLength)
	{
		return array_map('intval', str_split(strrev(str_pad(decbin($strInput), $stringLength, "0", STR_PAD_LEFT))) );
	}
}