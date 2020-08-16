<?php

namespace Spidermatt\Infectafi;

class Airmon
{
	const CMD_PATH = '/usr/sbin/airmon-ng';

	const CMD_IP_PATH = '/usr/sbin/ip';

	const CMD_IWCONFIG_PATH = '/usr/sbin/iwconfig';

	const CMD_IW_PATH = '/usr/sbin/iw';

	public static function startMonitor($config, $scanProfile)
	{
		$cmds = [];
		$profile = $config->scan->profiles->{$scanProfile};

		foreach($profile->adapters as $name => $conf) {
			if($config->adapters->{$name}->useAirmon) {
				$cmds[] = self::CMD_PATH . ' start ' . $name;
			} else {
				$cmds[] = self::CMD_IP_PATH . ' link set ' . $name . ' down';
				$cmds[] = self::CMD_IWCONFIG_PATH . ' ' . $name . ' mode monitor';
				$cmds[] = self::CMD_IP_PATH . ' link set ' . $name . ' up';
				$cmds[] = self::CMD_IW_PATH . ' ' . $name . ' set txpower fixed 3000';
			}
		}

		Debug::log(Debug::LOG_DEBUG, 'Starting monitor(s) on ' . implode(',', array_keys((array) $profile->adapters)));

		return shell_exec( implode(' && ', $cmds));
	}

	public static function stopMonitor($config, $scanProfile)
	{
		$cmds = [];
		$profile = $config->scan->profiles->{$scanProfile};

		foreach($profile->adapters as $name => $conf) {
			if($config->adapters->{$name}->useAirmon) {
				$cmds[] = self::CMD_PATH . ' stop ' . $name;
			} else {
				$cmds[] = self::CMD_IP_PATH . ' link set ' . $name . ' down';
				$cmds[] = self::CMD_IWCONFIG_PATH . ' ' . $name . ' mode managed';
				$cmds[] = self::CMD_IP_PATH . ' link set ' . $name . ' up';
			}
		}

		Debug::log(Debug::LOG_DEBUG, 'Stopping monitor(s) on ' . implode(',', array_keys((array) $profile->adapters)));

		return shell_exec( implode(' && ', $cmds));
	}
}