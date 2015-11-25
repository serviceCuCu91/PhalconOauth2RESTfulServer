<?php

class NiuAnnouncement extends \Phalcon\Mvc\Model
{
	public $ID;
	public $AnnouncementType;
	public $AnnouncementTitle;
	public $AnnouncementContent;
	public $UrlToGo;
	public $InitiatedAt;
	public $ExpiredAt;
	
	public function initialize()
    {
        $this->setSource('niuannouncement');
        $this->setConnectionService('niuniudb');
    }
    
    public function columnMap()
    {
        //Keys are the real names in the table and the values their names in the application
        return array(
            'id' => 'ID',
            'atype'	=> 'AnnouncementType',
            'atitle' => 'AnnouncementTitle',
            'acontent' => 'AnnouncementContent',
            'gotourl'	=> 'UrlToGo',
			'initiated_at'	=> 'InitiatedAt',
            'expired_at' => 'ExpiredAt',
        );
    }
	
    public function beforeSave()
    {
    }
    
	public function afterFetch()
    {
    	$this->ID = (int)$this->ID;//int
    }	
}

?>