<?php

class NiuBankRecord extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('bankrecord');
        $this->setConnectionService('niuniudb');
        
        $this->belongsTo("uuid", "NiuUsrInfo", "id");
    }
    

    public $id;
    public $value;//int
    public $type;    
    public $uuid;//long
    
    public $usingDiamond;//int
    public $usingCash;//int
    
    public $ugid;//long
    public $gcardid;//long
    public $giftid;//long
     
    public $create_at;
	
	public function columnMap()
    {
        //Keys are the real names in the table and the values their names in the application
        return array(
            'id' => 'id',
            'value'	=> 'value',
            'type' => 'type',
            'uuid' => 'uuid',
            'usingDiamond'	=> 'usingDiamond',
            'usingCash' => 'usingCash',
            'ugid' => 'ugid',
            'gcardid'	=> 'gcardid',
            'giftid'	=> 'giftid',
            'created_at'	=> 'created_at'
        );
    }
    
    public function beforeSave()
    {
    }
    
	public function afterFetch()
    {
		$this->value = (int)$this->value;//int
		
		$this->usingDiamond = (int)$this->usingDiamond;//int
		$this->usingCash = (int)$this->usingCash;//int
    }

}