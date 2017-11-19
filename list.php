<?php
require('defines.php');
require('config.php');

function query ($path) {
	global $gw_key, $gw_address, $gw_user;
	//$cmd = "coap-client -m get -u 'Client_identity' -k '$gw_key' 'coaps://$gw_address:5684/$path'";// | awk 'NR==4'"; //Only required if debug output from tinydtls is enabled
	$cmd = "coap-client -m get -u '$gw_user' -k '$gw_key' 'coaps://$gw_address:5684/$path'";
	//open the process
	$process = proc_open($cmd, [STDOUT => ['pipe', 'w'], STDERR => ['pipe', 'w']], $output);
	
	//read the outputs
	$stdout = stream_get_contents($output[STDOUT]);
	$stderr = stream_get_contents($output[STDERR]);

	//clean up and properly close our handles
	fclose($output[STDOUT]);
	fclose($output[STDERR]);
	$rc = proc_close($process);
	
	$result = json_decode($stdout, true);
	
	return $result;
}

$devices = [];
//query all device IDs
$deviceIds = query(DEVICES) ?? [];
foreach($deviceIds as $deviceId) {
	//query details of this device
	$device = query(DEVICES . "/$deviceId");
	//is this a lightbulb? we want to skip light switches, dimmers and motion sensors
	switch($device[TYPE]) {
		case TYPE_LIGHT:
			$devices[] = array(
				'id'			=> $deviceId,
				'name' 			=> $device[NAME],
								//when the device is unreachable we won't get information whether it's on or off
				'status' 		=> array_key_exists(ONOFF, $device[LIGHT][0]) ? $device[LIGHT][0][ONOFF] : null,
								//when the device is unreachable we won't get information how bright it is
				'brightness'	=> array_key_exists(DIMMER, $device[LIGHT][0]) ? $device[LIGHT][0][DIMMER] : null,
				'type'			=> 'light'
			);
			break;

		case TYPE_REMOTE_CONTROL:
			$devices[] = array(
				'id'			=> $deviceId,
				'name' 			=> $device[NAME],
				'type'			=> 'remote_control',
				'battery'		=> $device[DEVICE_OBJECT_INSTANCE][DEVICE_BATTERY_LEVEL]
			);
			break;

		case TYPE_MOTION_SENSOR:	
			$devices[] = array(
				'id'			=> $deviceId,
				'name' 			=> $device[NAME],
				'type'			=> 'motion_sensor',
				'battery'		=> $device[DEVICE_OBJECT_INSTANCE][DEVICE_BATTERY_LEVEL]
			);
			break;
	}
}

$groups = [];
//query all group IDs
$groupIds = query(GROUPS) ?? [];
foreach($groupIds as $groupId) {
	
	//query details of this group
	$group = query(GROUPS . "/$groupId");

	$scenes = [];
	//query scenes available for this group
	$sceneIds = query(SCENE . "/$groupId") ?? [];
    foreach($sceneIds as $sceneId) {
		$scene = query(SCENE . "/$groupId/$sceneId");
        $scenes[] = array(
			'id'		=> $sceneId,
			'name'		=> $scene[NAME],
			'status' 	=> $sceneId == $group[SCENE_ID] ? 1 : 0
		);
    }

	$groups[] = array(
		'id' 			=> $groupId,
		'name' 			=> $group[NAME],
		'status' 		=> $group[ONOFF],
		'brightness'	=> $group[DIMMER],
		'devices'		=> $group[HS_ACCESSORY_LINK][HS_LINK][INSTANCE_ID],
		'scenes'		=> $scenes
	);
}

$output = array(
	'groups' => $groups,
	'devices' => $devices
);

header('Content-Type: application/json');
echo json_encode($output);