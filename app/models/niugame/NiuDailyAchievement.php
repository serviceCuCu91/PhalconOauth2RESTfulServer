<?php

class NiuDailyAchievement extends \Phalcon\Mvc\Model
{
	public $ID;
	public $niuNiuGotten;
	public $dealerActing;
	public $gamePlayed;
	public $noNiu;
	public $dLogin;
	
	public function initialize()
    {
        $this->setSource('dailyachievement');
        $this->setConnectionService('niuniudb');
    }
    
    public function columnMap()
    {
        //Keys are the real names in the table and the values their names in the application
        return array(
            'id' => 'ID',
            'NiuNiuGotten'	=> 'niuNiuGotten',
            'DealerActing' => 'dealerActing',
            'GameTwenty' => 'gamePlayed',
            'NoNiuTen'	=> 'noNiu',
			'DailyLogin'	=> 'dLogin',
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