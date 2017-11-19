<?php
require('defines.php');
require('config.php');

function error($msg) {
	die("{\"error\": \"$msg\"}");
}

//parse json input from body
$json = file_get_contents('php://input');
$options = json_decode($json, true);

//check inputs given
if(!isset($options['id'])) error('Missing id');
//if(!isset($options['action'])) error('Missing action');//action can be omit when activating scene
if(!isset($options['type'])) error('Missing type');
if(!isset($options['value'])) error('Missing value');

//check input content
if(!is_int($options['id'])) error('Invalid id');
$action = isset($options['action']) ? $options['action'] : null;
//var_dump($options);
if($action == 'dim') {
	if(!is_int($options['value']) or $options['value'] < 0 or $options['value'] > 255) error('Invalid value');
} else if($action == 'power') {
	if(!is_int($options['value']) or $options['value'] < 0 or $options['value'] > 1) error('Invalid value');
} else if ($action == null) {
	if(!is_int($options['value'])) error('Invalid value');
	if($options['type'] != 'scene') error('Invalid action or type');
} else {
	error('Invalid action');
}

//construct the payload depending on the type and action
$payload = null;
if($options['type'] == 'group' or $options['type'] == 'scene') {
	$path = GROUPS . "/{$options['id']}";//id of the group
	if($options['type'] == 'group') {
		if($action == 'power') {
			$payload = '{ "' . ONOFF ."\" : {$options['value']} }";//value == 0/1
		} else if($action == 'dim') {
			$payload = '{ "' . DIMMER ."\" : {$options['value']} }";//value == 0..255
		}
	} else if($options['type'] == 'scene') {
		$payload = '{ "' . ONOFF .'" : 1, "' . SCENE_ID . "\" : {$options['value']} }";//value == scene ID
	}
} else if($options['type'] == 'device') {
	$path = DEVICES ."/{$options['id']}";//id of the device
	if($action == 'power') {
		$payload = '{ "' . LIGHT . '": [{ "' . ONOFF ."\": {$options['value']} }] }";//value == 0/1
	} else if($action == 'dim') {
		$payload = '{ "' . LIGHT . '": [{ "' . DIMMER ."\": {$options['value']} }] }";//value == 0..255
	}
} else {
	error('Invalid type');
}

$cmd = "coap-client -m put -u '$gw_user' -k '$gw_key' -e '$payload' 'coaps://$gw_address:5684/$path'";
exec($cmd);
