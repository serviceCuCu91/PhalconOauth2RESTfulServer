<?php

class NiuBankRecord extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('BankRecord');
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
    public $gcardid;//long?
    
    public $create_at;
	
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