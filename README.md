# phpTradfri
This project is a simple wrapper around [libcoap](https://github.com/obgm/libcoap)'s coap-client to allow controlling IKEA's Trådfri smart lighting products through a JSON HTTP API. For this, coap-client is used to communicate with the Trådfri Gateway through [COAP](https://tools.ietf.org/html/rfc7252) over TLS.
## Features
* Querying a list of the following items configured in the gateway:
  * Devices
    * Lamps
    * Remote Controls
    * Motion Sensors
  * Groups
  * Moods / Scenes
* Querying the status of the items 
  * On/Off
  * Brightness
  * Battery level
  * Names
* Turning lights and groups on and off
* Setting their brightness
* Activating moods
## Requirements
* IKEA Trådfri Gateway
* PHP7 (with minor adjustments older versions can also be used)
* libcoap (only supports Linux unfortunately)
## Installation
1. Install coap-client with DTLS support. Preferrably, debug output from [tinydtls](https://projects.eclipse.org/projects/iot.tinydtls) should be disabled (otherwise adjustments in the scripts are required, see inside list.php for details on this). The script [install-coap-client.sh](https://github.com/ggravlingen/pytradfri/blob/master/script/install-coap-client.sh) from the [pytradfri](https://github.com/ggravlingen/pytradfri) repository automates this.
2. Place the scripts in a web server directory accessible to web browsers. Ensure executing processes on the command line is allowed in your PHP installation.
3. Provide a configuration file with IP address of the gateway, identity and key ("username and password") to use when communicating with the gateway. Do this manually or guided by calling setup.php in a web browser.
## Configuration
A config.php file with the following example content is required:
~~~php
<?php
$gw_address = '192.168.1.123';
$gw_user = 'myUserName';
$gw_key = 'password123456789';
~~~
## Usage
### list.php
This script returns a full list of all supported items on the gateway. No options for filtering or to address a specific item are currently supported.

Example output:
~~~json
{
  "groups": [
    {
      "id": 159640,
      "name": "Bedroom",
      "status": 0,
      "brightness": 128,
      "devices": [
        65536,
        65537,
        65538
      ],
      "scenes": [
        {
          "id": 216905,
          "name": "EVERYDAY",
          "status": 0
        },
        {
          "id": 216317,
          "name": "RELAX",
          "status": 0
        },
        {
          "id": 223383,
          "name": "FOCUS",
          "status": 1
        }
      ]
    },
    {
      "id": 159641,
      "name": "Livingroom",
      "status": 0,
      "brightness": 128,
      "devices": [
        65539,
        65540
      ],
      "scenes": [
        {
          "id": 199468,
          "name": "EVERYDAY",
          "status": 1
        },
        {
          "id": 210921,
          "name": "RELAX",
          "status": 0
        },
        {
          "id": 212516,
          "name": "FOCUS",
          "status": 0
        }
      ]
    }
  ],
  "devices": [
    {
      "id": 65536,
      "name": "Remote control 1",
      "type": "remote_control",
      "battery": 74
    },
    {
      "id": 65539,
      "name": "Wireless motion sensor 1",
      "type": "motion_sensor",
      "battery": 87
    },
    {
      "id": 65537,
      "name": "Warm white bulb 1",
      "status": 0,
      "brightness": 254,
      "type": "light"
    },
    {
      "id": 65538,
      "name": "Warm white bulb 2",
      "status": 1,
      "brightness": 173,
      "type": "light"
    },
    {
      "id": 65540,
      "name": "Warm white bulb 3",
      "status": 0,
      "brightness": 230,
      "type": "light"
    }
  ]
}
~~~
### action.php
This script accepts HTTP POST requests with a JSON object in the body to execute a task. Only one task can be specified per request.

Example JSON objects POSTable:
#### Turn on a light
~~~json
{
  "type": "device",
  "id": 65537,
  "action": "power",
  "value": 1
}
~~~
#### Turn off a group
~~~json
{
  "type": "group",
  "id": 159641,
  "action": "power",
  "value": 0
}
~~~
#### Dim a light
~~~json
{
  "type": "device",
  "id": 65557,
  "action": "dim",
  "value": 192
}
~~~
#### Activate a scene
This syntax is a bit special. "id" is the group ID this scene belongs to and "value" is the ID of the scene itself.
~~~json
{
  "type": "scene",
  "id": 147230,
  "value": 199468
}
~~~
### index.html
This is a simple example page to show how to use the API or to test it. It's built with [AngularJS](https://angularjs.org) for quick prototyping of a template and AJAX requests.