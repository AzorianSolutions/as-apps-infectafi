<?php

require_once 'objects/AccessPoint.php';
require_once 'objects/Station.php';
require_once 'Db.php';
require_once 'Debug.php';

class Airodump
{
	const CMD_PATH = '/usr/sbin/airodump-ng';

	public static function startDump($length, $tmpDir, $bufferFile, $adapter, AccessPoint $accessPoint = null)
	{
		$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
		);

		$cmd = "/usr/bin/timeout -k $length $length " . self::CMD_PATH
			. ' --write-interval 1 --output-format csv '
			. (!is_null($accessPoint) ? ' --bssid ' . $accessPoint->macAddress . ' --channel ' . $accessPoint->channel
				. ' ' : ' ')
			. ' -w ' . $tmpDir . $bufferFile . ' -b abg ' . $adapter . 'mon';

		return proc_open($cmd, $descriptorspec, $pipes);
	}

	public static function loadDatabase($filePath, $db)
	{
		$buffer = explode("\n", file_get_contents($filePath));

		foreach($buffer as $line)
		{
			$line = explode(',', $line);

			// Validate line by testing the first indices value for a properly formatted MAC address
			if (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $line[0]) == 0) continue;

			if(15 == ($total = count($line)))
			{
				$ap = AccessPoint::creaateFromAirodumpCSVArray($line);

				$knownAccessPoint = Db::getKnownAccessPointsCollection()->findOne(['bssid' => $ap->bssid]);

				if($knownAccessPoint instanceof MongoDB\Model\BSONDocument)
				{
					Debug::log(Debug::LOG_TRACE3, 'AP ' . $ap->bssid . ' found in DB');
				}else{
					Debug::log(Debug::LOG_TRACE3, 'AP ' . $ap->bssid . ' not found in DB');
				}

				Db::getKnownAccessPointsCollection()->updateOne(['bssid' => $ap->bssid], ['$set' => AccessPoint::getMongoDocument($ap)], ['upsert' => true]);
			}elseif($total >= 6){
				$station = Station::creaateFromAirodumpCSVArray($line);

				$knownStation = Db::getKnownStationsCollection()->findOne(['macAddress' => $station->macAddress]);

				if($knownStation instanceof MongoDB\Model\BSONDocument)
				{
					Debug::log(Debug::LOG_TRACE3, 'Station ' . $station->macAddress . ' found in DB');
				}else{
					Debug::log(Debug::LOG_TRACE3, 'Station ' . $station->macAddress . ' not found in DB');
				}

				Db::getKnownStationsCollection()->updateOne(['macAddress' => $station->macAddress], ['$set' => Station::getMongoDocument($station)], ['upsert' => true]);
			}
		}
	}
}