<?php

class Airmon
{
	const CMD_PATH = '/usr/sbin/airmon-ng';

	public static function startMonitor($adapter)
	{
		return shell_exec(self::CMD_PATH . ' start ' . $adapter);
	}

	public static function stopMonitor($adapter)
	{
		return shell_exec(self::CMD_PATH . ' stop ' . $adapter . 'mon') . "\n\n"
			. shell_exec('/usr/sbin/service network-manager restart');
	}
}