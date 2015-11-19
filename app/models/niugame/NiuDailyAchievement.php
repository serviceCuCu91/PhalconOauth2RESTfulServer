<?php

class NiuGameSetting extends \Phalcon\Mvc\Model
{
	public function initialize()
    {
        $this->setSource('dailyachievement');
        $this->setConnectionService('niuniudb');
    }