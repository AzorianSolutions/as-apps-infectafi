<?php

namespace Spidermatt\Infectafi;

use Spidermatt\Infectafi\Objects\AccessPoint;
use Spidermatt\Infectafi\Objects\Station;

class Airodump
{
	const CMD_PATH = '/usr/sbin/airodump-ng';

	const CMD_TIMEOUT_PATH = '/usr/bin/timeout';

	public static function startDump($config, $profileName, $tmpDir)
	{
		$profile = $config->scan->profiles->{$profileName};
		$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
		);

		foreach ($profile->adapters as $name => $conf) {
			$bufferFileName = $config->scan->buffer_file . '-' . $name;
			$bufferPath = $tmpDir . '/' . $bufferFileName . '-01.csv';

			Debug::log(Debug::LOG_TRACE1, 'Adapter (' . $name . ') Buffer Path: ' . $bufferPath);

			// Remove existing buffer file if it exists
			\Spidermatt\Infectafi\Misc::removeTmpFile($bufferPath);

			$cmd = self::CMD_TIMEOUT_PATH . ' -k ' . $config->scan->limit . ' ' . $config->scan->limit . ' '
				. self::CMD_PATH . ' -M -W -K 1 -I 1 --output-format csv -w ' . $tmpDir . '/'
				. $bufferFileName . ' -b ' . implode(',', $conf->bands);

			$cmd .= ' ' . $name;

			if ($config->adapters->{$name}->useAirmon)
				$cmd .= 'mon';

			Debug::log(Debug::LOG_DEBUG, 'Starting airodump-ng for adapter ' . $name . ' on bands(s): '
				. implode(',', $conf->bands));

			Debug::log(Debug::LOG_TRACE1, 'airodump-ng command: ' . $cmd);

			proc_open($cmd, $descriptorspec, $pipes);
		}
	}

	public static function loadDatabase($config, $profileName, $tmpDir)
	{
		if(!is_dir($tmpDir)) {
			Debug::log(Debug::LOG_WARN, 'The provided tmp path is not a valid directory: ' . $tmpDir);
			return FALSE;
		}

		if(!is_readable($tmpDir)) {
			Debug::log(Debug::LOG_WARN, 'The provided tmp path is not readable: ' . $tmpDir);
			return FALSE;
		}

		$items = scandir($tmpDir, SCANDIR_SORT_ASCENDING);

		foreach($items as $name) {

			if($name == '.' || $name == '..') continue;

			$nameRegExp = '/^' . str_replace('-', '\-', $config->scan->buffer_file) . '\-[a-zA-Z0-9\_\.\-]+\-[0-9]{2}\.csv$/';
			$match = preg_match($nameRegExp, $name);

			if($match === 0) {
				Debug::log(Debug::LOG_TRACE1, 'Skipping file based on name matching: ' . $name);
				continue;
			} elseif($match === false) {
				Debug::log(Debug::LOG_TRACE1, 'Skipping file based on match execution failure: ' . $name);
				continue;
			}

			$buffer = explode("\n", file_get_contents($tmpDir . '/' . $name));

			foreach ($buffer as $line) {
				$line = explode(',', $line);

				// Validate line by testing the first indices value for a properly formatted MAC address
				if (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $line[0]) == 0) continue;

				if (15 == ($total = count($line))) {
					$ap = AccessPoint::creaateFromAirodumpCSVArray($line);

					$knownAccessPoint = Db::getKnownAccessPointsCollection()->findOne(['bssid' => $ap->bssid]);

					if ($knownAccessPoint instanceof \MongoDB\Model\BSONDocument) {
						Debug::log(Debug::LOG_TRACE3, 'AP ' . $ap->bssid . ' found in DB');
					} else {
						Debug::log(Debug::LOG_TRACE3, 'AP ' . $ap->bssid . ' not found in DB');
					}

					Db::getKnownAccessPointsCollection()->updateOne(['bssid' => $ap->bssid], ['$set' => AccessPoint::getMongoDocument($ap)], ['upsert' => true]);
				} elseif ($total >= 6) {
					$station = Station::creaateFromAirodumpCSVArray($line);

					$knownStation = Db::getKnownStationsCollection()->findOne(['macAddress' => $station->macAddress]);

					if ($knownStation instanceof \MongoDB\Model\BSONDocument) {
						Debug::log(Debug::LOG_TRACE3, 'Station ' . $station->macAddress . ' found in DB');
					} else {
						Debug::log(Debug::LOG_TRACE3, 'Station ' . $station->macAddress . ' not found in DB');
					}

					Db::getKnownStationsCollection()->updateOne(['macAddress' => $station->macAddress], ['$set' => Station::getMongoDocument($station)], ['upsert' => true]);
				}
			}
		}
	}
}