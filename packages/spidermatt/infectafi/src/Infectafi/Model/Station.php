<?php

namespace Spidermatt\Infectafi\Model;

class Station extends Model
{
	protected static $attrs = array(
		'stationMac' => array('type' => Model::DATA_TYPE_STRING),
		'bssid' => array('type' => Model::DATA_TYPE_STRING),
		'channel' => array('type' => Model::DATA_TYPE_INTEGER),
		'power' => array('type' => Model::DATA_TYPE_INTEGER),
		'totalPackets' => array('type' => Model::DATA_TYPE_INTEGER),
		'probedESSID' => array('type' => Model::DATA_TYPE_ARRAY),
		'firstSeenOn' => array('type' => Model::DATA_TYPE_TIMESTAMP),
		'lastSeenOn' => array('type' => Model::DATA_TYPE_TIMESTAMP),
		'createdOn' => array('type' => Model::DATA_TYPE_TIMESTAMP),
		'updatedOn' => array('type' => Model::DATA_TYPE_TIMESTAMP),
	);
}