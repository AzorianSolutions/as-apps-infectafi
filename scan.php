#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/spidermatt/infectafi/src/Infectafi/Service/Options.php';

// Load runtime option overrides from the command line (if any present)
$opts = \Spidermatt\Infectafi\Service\Options::getOptions(realpath(__DIR__));
// TODO: Update class to support custom options at runtime
$opts['scan-profile'] = 'all-channels';

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
	Db::setMongoClientAndDb(new MongoDB\Client($config->db->url), $config->db->name);

	// Change each required interface to monitor mode
	\Spidermatt\Infectafi\Airmon::startMonitor($config, $opts['scan-profile']);

	// Start a scan using the configured profile
	\Spidermatt\Infectafi\Airodump::startDump($config, $opts['scan-profile'], $opts['tmp-path']);

	$start = time();

	// Update the database every 1 second(s) with changes from the airodump-ng buffer file(s)
	while (time() - $start < $config->scan->limit) {
		Debug::log(Debug::LOG_TRACE2, "Loading database from buffer(s)");

		\Spidermatt\Infectafi\Airodump::loadDatabase($config, $opts['scan-profile'], $opts['tmp-path']);

		Debug::log(Debug::LOG_TRACE2, 'Database loaded from buffer(s)');
		Debug::log(Debug::LOG_TRACE2, "Sleeping for 1 second(s)");

		sleep(1);
	}

	// Change each required interface back to managed mode
	\Spidermatt\Infectafi\Airmon::stopMonitor($config, $opts['scan-profile']);

	Debug::log(Debug::LOG_DEBUG, 'Completed service cycle.');

	Debug::log(Debug::LOG_DEBUG, 'Sleeping for ' . $config->service->restartDelay->afterComplete . ' seconds...');

	sleep($config->service->restartDelay->afterComplete);
}