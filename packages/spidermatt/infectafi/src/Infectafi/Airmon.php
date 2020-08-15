<?php

namespace Spidermatt\Infectafi;

class Airmon
{
	const CMD_PATH = '/usr/sbin/airmon-ng';

	const CMD_IP_PATH = '/usr/sbin/ip';

	const CMD_IWCONFIG_PATH = '/usr/sbin/iwconfig';

	const CMD_IW_PATH = '/usr/sbin/iw';

	public static function startMonitor($adapter)
	{
		$cmds = [];
		$cmds[] = self::CMD_IP_PATH . ' link set ' . $adapter . ' down';
		$cmds[] = self::CMD_IWCONFIG_PATH . ' ' . $adapter . ' mode monitor';
		$cmds[] = self::CMD_IP_PATH . ' link set ' . $adapter . ' up';
		$cmds[] = self::CMD_IW_PATH . ' ' . $adapter . ' set txpower fixed 3000';
		return shell_exec( implode(' && ', $cmds));
	}

	public static function stopMonitor($adapter)
	{
		$cmds = [];
		$cmds[] = self::CMD_IP_PATH . ' link set ' . $adapter . ' down';
		$cmds[] = self::CMD_IWCONFIG_PATH . ' ' . $adapter . ' mode managed';
		$cmds[] = self::CMD_IP_PATH . ' link set ' . $adapter . ' up';
		$cmds[] = self::CMD_IW_PATH . ' ' . $adapter . ' set txpower auto';
		return shell_exec( implode(' && ', $cmds));
	}
}