<?php

class Debug
{
	const LOG_CRITICAL = 4;
	const LOG_ERROR = 3;
	const LOG_WARN = 2;
	const LOG_INFO = 1;
	const LOG_DEBUG = 0;
	const LOG_TRACE1 = -1;
	const LOG_TRACE2 = -2;
	const LOG_TRACE3 = -3;

	protected static $_levelLabels = [
		4 => 'CRITICAL',
		3 => 'ERROR',
		2 => 'WARN',
		1 => 'INFO',
		0 => 'DEBUG',
		-1 => 'TRACE1',
		-2 => 'TRACE2',
		-3 => 'TRACE3'
	];

	protected static $_debug = false;

	protected static $_printLevel = 1;

	public static function setDebugMode($isDebug)
	{
		self::$_debug = $isDebug;
	}

	public static function setPrintLevel($level)
	{
		self::$_printLevel = $level;
	}

	public static function isDebug()
	{
		return self::$_debug;
	}

	public static function getLogLevelLabel($level)
	{
		return self::$_levelLabels[$level];
	}

	public static function log($level, $message, $topPad = 1, $bottomPad = 1)
	{
		if(self::isDebug() && $level >= self::$_printLevel)
		{
			$timestamp = new DateTime();
			$label = self::getLogLevelLabel($level);
			echo $timestamp->format('Y-m-d H:i:s.u,T') . ' ' . $label . ' ' . $message, str_repeat("\n", $bottomPad);
		}
	}
}