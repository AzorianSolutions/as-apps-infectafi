#!/usr/bin/php
<?php

require_once 'lib/vendor/autoload.php';
require_once 'lib/Airmon.php';
require_once 'lib/Airodump.php';
require_once 'lib/Db.php';
require_once 'lib/Debug.php';
require_once 'lib/Misc.php';

// Load file based configuration
$config = json_decode(file_get_contents(__DIR__ . '/data/config.json'));

// Setup database connection and database
$mongoClient = new MongoDB\Client($config->db->url);
Db::setMongoClientAndDb($mongoClient, $config->db->name);

// Load DB based configuration
$dbConfig = Db::getConfig($config->config_revision);

// Set debug mode on debug manager from configuration
Debug::setDebugMode(isset($dbConfig->debug) ? $dbConfig->debug : (bool)$config->debug);
Debug::setPrintLevel((int)$dbConfig->scan->log_level);

Debug::log(Debug::LOG_DEBUG, Airmon::startMonitor($config->adapter));

$bufferFile = ($tmp_path = __DIR__ . '/' . $config->tmp_path . '/') . $dbConfig->scan->buffer_file . '-01.csv';

Debug::log(Debug::LOG_TRACE1, 'Buffer File: ' . $bufferFile);

// Remove existing AP buffer file if it exists
Misc::removeTmpFile($bufferFile);

Debug::log(Debug::LOG_INFO, 'Initializing Air Dump');

Debug::log(Debug::LOG_DEBUG, Airodump::startDump($dbConfig->scan->limit, $tmp_path, $dbConfig->scan->buffer_file, $config->adapter));

$dumpStart = new DateTime();

Debug::log(Debug::LOG_TRACE1, 'Air Dump Start: ' . ($dumpStartString = $dumpStart->format('Y-m-d H:i:s.u')));

// Set the delay (in seconds) to wait for airodump-ng to have created the buffer file
$airDumpDelay = 1;

Debug::log(Debug::LOG_TRACE1, "Sleeping for $airDumpDelay second(s)");

sleep($airDumpDelay);

// This will only run for the duration of the configured limit
while (time() - strtotime($dumpStartString) < $dbConfig->scan->limit) {
	// Load DB based configuration
	$dbConfig = Db::getConfig($config->config_revision);

	// Set debug mode on debug manager from configuration
	Debug::setDebugMode(isset($dbConfig->debug) ? $dbConfig->debug : (bool)$config->debug);
	Debug::setPrintLevel((int)$dbConfig->scan->log_level);

	Debug::log(Debug::LOG_TRACE2, "Loading database from buffer");

	Airodump::loadDatabase($bufferFile, Db::getDb());

	Debug::log(Debug::LOG_TRACE2, 'Database loaded from buffer');
	Debug::log(Debug::LOG_TRACE2, "Sleeping for 1 second");

	sleep(1);
}

Debug::log(Debug::LOG_DEBUG, Airmon::stopMonitor($config->adapter));
