<?php

class Scheduler
{
	protected $_config = null;

	public function __construct($config)
	{
		$this->_config = $config;
	}

	public function run()
	{
		// TODO: Check last run times and determine what goes next
	}
}