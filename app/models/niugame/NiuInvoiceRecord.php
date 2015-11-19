<?php

class NiuInvoiceRecord extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('invoicerecord');
        $this->setConnectionService('niuniudb');
        
        $this->belongsTo("uuid", "NiuUsrInfo", "id");
        $this->hasOne("bankid", "NiuBankRecord", "id");
    }
    
    public $id;
    public $bankid; // long
    public $uuid; // long
    
    public $sType;//string    
    public $productID;//string
    public $rawData;//string
    public $transReceipt;//string
    public $serverVerifyStatus; // int
    
    public $created_at;
	
	public function columnMap()
    {
        //Keys are the real names in the SQL table and 
        //the values their names in this php application
        return array(
            'id' => 'id',
            'bankRecordID'	=> 'bankid',//the only different...
            'uuid' => 'uuid',
            'storeType' => 'sType',
            'productIdentifier' => 'productID',
            'rawPurchaseData' => 'rawData',
            'transactionReceipt' => 'transReceipt',
            'serverVerify' => 'serverVerifyStatus',
            'created_at' => 'created_at',
        );
    }
    
    public function beforeSave()
    {
    	$this->serverVerifyStatus = (int)$this->serverVerifyStatus; //$this->value = (int)$this->value;
    }
    
	public function afterFetch()
    {
    }

}