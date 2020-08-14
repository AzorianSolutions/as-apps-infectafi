<?php

class Db
{
	protected static $_mongoClient = null;
	protected static $_db = null;

	public static function getDb()
	{
		return self::$_db;
	}

	public static function setMongoClientAndDb($client, $dbname)
	{
		self::$_mongoClient = $client;
		self::$_db = self::$_mongoClient->{$dbname};
	}

	public static function getConfig($revision)
	{
		$cfgDb = self::$_db->config;
		$cfg = $cfgDb->findOne(['revision' => $revision]);
		return $cfg instanceof MongoDB\Model\BSONDocument ? $cfg : false;
	}

	public static function getKnownAccessPointsCollection()
	{
		return self::$_db->known_access_points;
	}

	public static function getKnownStationsCollection()
	{
		return self::$_db->known_stations;
	}

	public static function getAccessPoints()
	{
		return self::$_db->known_access_points->find();
	}

	public static function getStations()
	{
		return self::$_db->known_stations->find();
	}
}