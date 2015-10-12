<?php

class NiuTransferableItem extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('transferableItem');
        $this->setConnectionService('niuniudb');
        
        $this->belongsTo("ownerUUID", "NiuUsrInfo", "id");
    }
	
    public $id;
    public $buyerUUID;
    public $ownerUUID;
    public $itemType;
	public $maxDeposit;
    public $nowDeposit;
    public $created_at;
    public $updated_at;
    
    public function columnMap()
    {
        //Keys are the real names in the table and the values their names in the application
        return array(
            'id' => 'id',
            'buyer_id'	=> 'buyerUUID',
            'owner_id' => 'ownerUUID',
            'type' => 'itemType',
            'max_deposit'	=> 'maxDeposit',
            'deposit' => 'nowDeposit',
			'created_at'	=> 'created_at',
            'updated_at' => 'updated_at',
        );
    }
	
    public function beforeSave()
    {
    }
    
	public function afterFetch()
    {
    }

}