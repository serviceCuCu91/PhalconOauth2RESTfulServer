<?php
use Phalcon\Mvc\Model\Query;

class ChatLogs extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('chatlog');
        $this->setConnectionService('db');
        
		$this->belongsTo("originid", "Origin", "id");
		$this->belongsTo("origintype", "OriginType", "id");
		
		$this->belongsTo("targetid", "Origin", "id");
		$this->belongsTo("targettype", "OriginType", "id");
		
        //$this->belongsTo("uuid", "NiuUsrInfo", "id");
    }
	
	/**
     * @var long/bigint log ID
     */
    public $id;	
	/**
     * @var long/bigint UUID
     */
    public $originid;
	/**
     * @var int 0:general, 1:niugame, 
     */
    public $origintype;
	
	/**
     * @var long/bigint target UUID
     */
    public $targetid;
	/**
     * @var int 0:general, 1:niugame, 
     */
    public $targettype;
	
	/**
     * @var string (64)
     */
	 public $content;
	 
	 /**
     * @var string (256)
     */
	 public $json;
	 
	 public $created_at;
	 
	 /**
     * Independent Column Mapping.
     */
    public function columnMap()
    {
    	//Keys are the real names in the table and the values their names in the application
        return array(
            'id' => 'id', 
            'origin' => 'originid',			// 'og' => 'origin', 
            'origintype' => 'origintype',	// 'ot' => 'origintype', 
            'target' => 'targetid',			// 'ta' => 'target', 
            'targettype' => 'targettype',			// 'tt' => 'targettype', 
            'content' => 'content',			// 'content' => 'content',
			'json' => 'json',
			'created_at' => 'created_at'
        );
    }
	
	public function getServerTime()
    {
        // A raw SQL statement
		$query = new Query('SELECT now() FROM NiuUsrInfo limit 1', $this->getDI());
		
        // Execute the query
		$result = $query->execute();
		return $result[0]->toArray()[0];
    }
	 
}