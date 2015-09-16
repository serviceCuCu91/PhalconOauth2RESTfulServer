<?php
class Origin extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('Origin');
        //$this->setConnectionService('db');
        
        $this->hasMany("id", "ChatLogs", "og");
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