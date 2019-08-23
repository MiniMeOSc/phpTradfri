<?php

//require_once('config.php');
require_once('define.php');

class tradfri
	{

	//IP Address of Trådfri Gateway
	const TRADFRIIP = '<your-ip>';
	//API User Trådfri Gateway
	const USER = '<user>';
	//API Key for User
	const SECRETKEY = '<generated secret>';

	function query($path){

		$cmd = "coap-client -m get -u '".self::USER."' -k '".self::SECRETKEY."' 'coaps://".self::TRADFRIIP.":5684/$path'";
		$process = proc_open($cmd, [STDOUT => ['pipe', 'w'], STDERR => ['pipe', 'w']], $output);

		//read the outputs
		$stdout = stream_get_contents($output[STDOUT]);
		$stderr = stream_get_contents($output[STDERR]);

		//clean up and properly close our handles
		fclose($output[STDOUT]);
		fclose($output[STDERR]);
		$rc = proc_close($process);

		//$result = json_decode(strstr($stdout,'{"'), true);
		$result = json_encode($stdout, true);

		return $stdout;
		//return $result;

		}

	function getDetails($path){

		return json_decode(strstr($this->query($path), '{"'), true);

		}

	}
//End of Class tradfri

?>
