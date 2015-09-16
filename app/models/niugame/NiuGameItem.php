<?php

class NiuGameItem extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('niugameitem');
        $this->setConnectionService('niuniudb');
        
        //$this->belongsTo("id", "NiuUsrOwnItem", "id");
    }
	
    public $id;
    public $prefabLoc;
    public $location;
    public $description;
    public $cashCost;
    public $diamondCost;
    
    public function columnMap()
    {
        //Keys are the real names in the table and the values their names in the application
        return array(
            'id' => 'id',
            'prefab_loc'	=> 'prefabLoc',
            'location' => 'location',
            'description' => 'description',
            'cash'	=> 'cashCost',
            'diamond' => 'diamondCost',
        );
    }
	
    public function beforeSave()
    {
    }
    
	public function afterFetch()
    {
		$this->cashCost = (int)$this->cashCost;
		$this->diamondCost = (int)$this->diamondCost;
    }

}