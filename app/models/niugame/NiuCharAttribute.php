<?php

class NiuCharAttribute extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('CharAttribute');
        $this->setConnectionService('niuniudb');
        
        $this->belongsTo("uuid", "NiuUsrInfo", "id");
    }
    

    public $id;
    public $model;
    public $headwear;
    public $eyewear;
    public $handwear;
    public $icon;
    public $customicon;
    public $background;
    
    public $create_at;
    public $updated_at;
    
    public function columnMap()
    {
        //Keys are the real names in the table and the values their names in the application
        return array(
            'id' => 'id',
            'model'	=> 'model',
            'headwear' => 'headwear',
            'eyewear' => 'eyewear',
            'handwear'	=> 'handwear',
            'icon' => 'icon',
            'customicon' => 'customicon',
            'cardback' => 'cardback',
            'background' => 'background',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        );
    }
    
    public function setValue($target, $value)
    {
    	switch($target)
    	{
			case 'model':
			break;
			case 'handwear':
			break;
			case 'headwear':
			break;
			case 'eyewear':
			break;
			case 'icon':
			break;
			case 'cardback':
			break;
			case 'background':
			break;
			default:
				return false;
			break;
		}
		
		$this->$target = $value;
		return true;
	}
	
    public function beforeSave()
    {
    }
    
	public function afterFetch()
    {
    }

}