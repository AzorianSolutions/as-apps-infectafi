<?php

require_once 'objects/AccessPoint.php';

class Aireplay
{
	const CMD_PATH = '/usr/sbin/aireplay-ng';

	public static function sendDeauth($accessPointMAC, $station, $adapter)
	{
		return shell_exec(self::CMD_PATH . ' -0 2 -a ' . $accessPointMAC . ' -c ' . $station . ' ' . $adapter . 'mon >> /dev/null');
	}
}