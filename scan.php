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
Debug::setPrintLevel($config->scan->log_level);

// Log $opts values to log
foreach ($opts as $key => $value) {
	Debug::log(Debug::LOG_DEBUG, 'Option [' . $key . ']: ' . $value);
}

// Log config file contents
Debug::log(Debug::LOG_DEBUG, 'Service Config: ' . json_encode($config));

while (true) {
	Debug::log(Debug::LOG_DEBUG, 'Starting service cycle...');

	// Setup database connection and database
	$mongoClient = new MongoDB\Client($config->db->url);
	Db::setMongoClientAndDb($mongoClient, $config->db->name);

	Debug::log(Debug::LOG_DEBUG, 'Starting monitor on ' . $config->adapter);

	Debug::log(Debug::LOG_DEBUG, \Spidermatt\Infectafi\Airmon::startMonitor($config->adapter));

	$bufferFile = ($tmp_path = __DIR__ . '/' . $config->tmp_path . '/') . $config->scan->buffer_file . '-01.csv';

	Debug::log(Debug::LOG_TRACE1, 'Buffer File: ' . $bufferFile);

	// Remove existing AP buffer file if it exists
	\Spidermatt\Infectafi\Misc::removeTmpFile($bufferFile);

	Debug::log(Debug::LOG_INFO, 'Initializing Air Dump');

	Debug::log(Debug::LOG_DEBUG, \Spidermatt\Infectafi\Airodump::startDump($config->scan->limit, $tmp_path, $config->scan->buffer_file, $config->adapter));

	$dumpStart = new DateTime();

	Debug::log(Debug::LOG_TRACE1, 'Air Dump Start: ' . ($dumpStartString = $dumpStart->format('Y-m-d H:i:s.u')));

	// Set the delay (in seconds) to wait for airodump-ng to have created the buffer file
	$airDumpDelay = 1;

	Debug::log(Debug::LOG_TRACE1, "Sleeping for $airDumpDelay second(s)");

	sleep($airDumpDelay);

	// Update the database every one second with any updates to the buffer file
	while (time() - strtotime($dumpStartString) < $config->scan->limit) {
		Debug::log(Debug::LOG_TRACE2, "Loading database from buffer");

		\Spidermatt\Infectafi\Airodump::loadDatabase($bufferFile, Db::getDb());

		Debug::log(Debug::LOG_TRACE2, 'Database loaded from buffer');
		Debug::log(Debug::LOG_TRACE2, "Sleeping for 1 second");

		sleep(1);
	}

	Debug::log(Debug::LOG_DEBUG, 'Stopping monitor on ' . $config->adapter);

	Debug::log(Debug::LOG_DEBUG, \Spidermatt\Infectafi\Airmon::stopMonitor($config->adapter));

	Debug::log(Debug::LOG_DEBUG, 'Completed service cycle.');

	Debug::log(Debug::LOG_DEBUG, 'Sleeping for ' . $config->service->restartDelay->afterComplete . ' seconds...');

	sleep($config->service->restartDelay->afterComplete);
}