<?php

class AccessPoint
{
	public $bssid = null;
	public $essid = null;
	public $channel = null;
	public $speed = null;
	public $privacy = null;
	public $cipher = null;
	public $authentication = null;
	public $power = null;
	public $firstRx = null;
	public $lastRx = null;
	public $beacons = 0;
	public $ivs = 0;

	public static function creaateFromAirodumpCSVArray($line)
	{
		$obj = new AccessPoint();
		$obj->bssid = trim($line[0]);
		$obj->essid = trim($line[13]);
		$obj->channel = trim($line[3]);
		$obj->speed = trim($line[4]);
		$obj->privacy = trim($line[5]);
		$obj->cipher = trim($line[6]);
		$obj->authentication = trim($line[7]);
		$obj->power = trim($line[8]);
		$obj->firstRx = trim($line[1]);
		$obj->lastRx = trim($line[2]);
		$obj->beacons = trim($line[9]);
		$obj->ivs = trim($line[10]);
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