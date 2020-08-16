<?php

namespace Spidermatt\Infectafi\Model;

class AccessPoint extends Model
{
	protected static $attrs = array(
		'bssid' => array('type' => Model::DATA_TYPE_STRING),
		'essid' => array('type' => Model::DATA_TYPE_STRING),
		'channel' => array('type' => Model::DATA_TYPE_INTEGER),
		'power' => array('type' => Model::DATA_TYPE_INTEGER),
		'speed' => array('type' => Model::DATA_TYPE_INTEGER),
		'encryption' => array('type' => Model::DATA_TYPE_STRING),
		'cypher' => array('type' => Model::DATA_TYPE_STRING),
		'authentication' => array('type' => Model::DATA_TYPE_STRING),
		'totalBeacons' => array('type' => Model::DATA_TYPE_INTEGER),
		'totalIV' => array('type' => Model::DATA_TYPE_INTEGER),
		'firstSeenOn' => array('type' => Model::DATA_TYPE_TIMESTAMP),
		'lastSeenOn' => array('type' => Model::DATA_TYPE_TIMESTAMP),
		'createdOn' => array('type' => Model::DATA_TYPE_TIMESTAMP),
		'updatedOn' => array('type' => Model::DATA_TYPE_TIMESTAMP),
	);
}