<?php
class ChatLogs extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('chatlog');
        $this->setConnectionService('db');
        
		$this->belongsTo("og", "Origin", "id");
		$this->belongsTo("ot", "OriginType", "id");
		
		$this->belongsTo("ta", "Origin", "id");
		$this->belongsTo("tt", "OriginType", "id");
		
        //$this->belongsTo("uuid", "NiuUsrInfo", "id");
    }
	
	/**
     * @var long/bigint log ID
     */
    public $id;	
	/**
     * @var long/bigint UUID
     */
    public $og;
	/**
     * @var int 0:general, 1:niugame, 
     */
    public $ot;
	
	/**
     * @var long/bigint target UUID
     */
    public $ta;
	/**
     * @var int 0:general, 1:niugame, 
     */
    public $tt;
	
	/**
     * @var string (64)
     */
	 public $co;
	 
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
        return array(
            'id' => 'id', 
            'origin' => 'og',			// 'og' => 'origin', 
            'origintype' => 'ot',			// 'ot' => 'origintype', 
            'target' => 'ta',			// 'ta' => 'target', 
            'targettype' => 'tt',			// 'tt' => 'targettype', 
            'content' => 'co',			// 'content' => 'content',
			'json' => 'json',
			'created_at' => 'created_at'
        );
    }
	 
}