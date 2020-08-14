<?php

class Station
{
	public $macAddress = null;
	public $firstRx = null;
	public $lastRx = null;
	public $power = null;
	public $packets = 0;
	public $bssid = null;
	public $probedSSIDs = null;

	public static function creaateFromAirodumpCSVArray($line)
	{
		$obj = new Station();
		$obj->macAddress = trim($line[0]);
		$obj->firstRx = trim($line[1]);
		$obj->lastRx = trim($line[2]);
		$obj->power = trim($line[3]);
		$obj->packets = trim($line[4]);
		$obj->bssid = trim($line[5]);
		$obj->probedSSIDs = trim($line[6]);
		return $obj;
	}

	public static function getMongoDocument($ap)
	{
		$doc = [];

		foreach($ap as $key => $value)
		{
			$doc[$key] = $value;
		}

		return $doc;
	}
}