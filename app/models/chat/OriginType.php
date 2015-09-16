<?php
class OriginType extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('origintype');
        //$this->setConnectionService('db');
        
        $this->hasMany("id", "ChatLogs", "ot");
    }
	
    /**
     * @var integer
     */
    public $id;
    /**
     *
     * @var string
     */
    public $name;

    /**
     * Independent Column Mapping.
     */
    public function columnMap()
    {
        return array(
            'id' => 'id', 
            'name' => 'name'
        );
    }
}