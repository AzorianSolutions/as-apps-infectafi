<?php

namespace Spidermatt\Infectafi\Service;

class Options
{
	public static function getOptions($basePath)
	{
		$options = getopt('c::l::s::v::', ['config-file::', 'config-path::', 'log-path::', 'service-path::',
			'vendor-path::', "tmp-path::"]);

		$opts = [
			'service-path' => $basePath
		];
		$opts['config-path'] = $opts['service-path'] . '/config';
		$opts['config-file'] = 'config.json';
		$opts['log-path'] = $opts['service-path'] . '/log';
		$opts['vendor-path'] = $opts['service-path'] . '/vendor';
		$opts['tmp-path'] = $opts['service-path'] . '/tmp';
		$loadOpts = ['s' => 'service-path', 'service-path' => 'service-path', 'config-file' => 'config-file',
			'c' => 'config-path', 'config-path' => 'config-path', 'l' => 'log-path', 'log-path' => 'log-path',
			'v' => 'vendor-path', 'vendor-path' => 'vendor-path',
			't' => 'tmp-path', 'tmp-path' => 'tmp-path'];

		foreach ($loadOpts as $key => $value) {
			if (array_key_exists($key, $options))
				$opts[$value] = $options[$key];
		}

		return $opts;
	}
}