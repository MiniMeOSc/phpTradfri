# phpTradfri
This project is a set of PHP classes based on the PHP wrapper from [MiniMeOSc](https://github.com/MiniMeOSc/).

I will use it for controlling and monitoring my Tradfri enviroment via a [Cisco Webex Teams](https://www.webex.com/team-collaboration.html) Chat Bot.

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
insert Konfig Data in general.php
## Usage
