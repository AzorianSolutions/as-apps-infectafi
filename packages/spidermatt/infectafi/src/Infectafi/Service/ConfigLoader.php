<?php

namespace Spidermatt\Infectafi\Service;

class ConfigLoader
{
	public static function load($configDirectory, $fileName)
	{
		// TODO: Exception handling
		return json_decode(file_get_contents($configDirectory . '/' . $fileName));
	}
}