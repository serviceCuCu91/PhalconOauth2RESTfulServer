<?php

class GiftBox extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('niugiftbox');
        $this->setConnectionService('db');
        
        $this->belongsTo("originid", "Origin", "id");
		$this->belongsTo("origintype", "OriginType", "id");
		
		$this->belongsTo("targetid", "Origin", "id"); //this gift must be given to someone
		$this->belongsTo("targettype", "OriginType", "id");
    }
    

    /** @var long/bigint log ID  */
    public $id;	
    
	/** @var long/bigint UUID     */
    public $originid;
    
	/** @var int 0:general, 1:niugame,      */
    public $origintype;
	
	/**     @var long/bigint target UUID     */
    public $targetid;
    
	/**@var int 0:general, 1:niugame, */
    public $targettype;
	
	/** @var string (64)     */
	 public $content;
	 
	 /** @var text ()     */
	 public $json;
	 
	 public $created_at;
	 
	 public $expired_at;
	 
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
			'created_at' => 'created_at',
			'expired_at' => 'expired_at'
        );
    }

}

?>