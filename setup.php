<?php
//include the file with constant definitions
require('defines.php');

function generateAppId() {
    $chars = "0123456789abcdef";
    $val = '';
    //repeat 32 times
    for( $i=0; $i<32; $i++ ) {
        //add a random character
        $val .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $val;
}

function getCredentials($ipaddress, $psk) {
    //generate a random id with hexadecimal characters. 
    //That's what IKEA also seem to use in the app
    $identity = generateAppId();
    
    //construct the command we'll want to execute to query our credentials
    $cmd = "coap-client -u 'Client_identity' -k '$psk' -m post -e '{\"" . CLIENT_IDENTITY_PROPOSED . "\": \"$identity\"}' 'coaps://$ipaddress:5684/" . GATEWAY . "/" . AUTH_PATH . "'";

    //open the process
    $process = proc_open($cmd, [STDOUT => ['pipe', 'w'], STDERR => ['pipe', 'w']], $output);

    //read the outputs
    $stdout = stream_get_contents($output[STDOUT]);
    $stderr = stream_get_contents($output[STDERR]);

    //clean up and properly close our handles
    fclose($output[STDOUT]);
    fclose($output[STDERR]);
    $rc = proc_close($process);

    if($rc > 0) {
        //the command failed to be executed, abort
        errorCommandFailed($rc, $stderr);
        return;
    }

    //if we did read something from STDERR, discard the first line
    //since that should be information from coap-client about the connection
    if($stderr !== false) {
        $lineEnd = strpos($stderr, PHP_EOL);
        if($lineEnd > -1) {
            $stderr = trim(substr($stderr, $lineEnd + strlen(PHP_EOL)));
        }
    }

    //if we read something from STDOUT cut off any blank space/newlines
    if($stdout !== false) {
        $stdout = trim($stdout);
    }

    //did we get a 4.00 error?
    if($stderr == "4.00") {//Bad Request
        //This identity has been used before, so posting it again is considered a Bad Request.
        //Though considering the type of random id we're using chances for this should be pretty low.
        //Retry with a new identity. 
        return getCredentials($ipaddress, $psk);
    } else if ($stderr !== "") {//is there a message in this line?
        //not sure what error we got, just tell the user
        errorUnknownResponse($stderr);
        return;
    }

    //if we didn't get anything on STDERR and neither on STDOUT then 
    //coap-client must've been unable to reach the gateway with this address and key
    if($stdout === "") {
        errorGatewayUnreachable();
        return;
    }

    //we should've received json from the gateway, parse that
    $response = json_decode($stdout, true);
    
    //finally return our result
    return Array(
        'identity' => $identity,
        'key' => $response[NEW_PSK_BY_GW]
    );
}

function saveCredentials($ipaddress, $identity, $newKey) {
    //open the config file for writing
    $file = fopen('./config.php', 'w');
    
    //did it work?
    if($file === false) {
        //tell the user something went wrong
        errorConfigFileOpen();
        return;
    }

    //put the necessary data together into a string
    $contents  = "<?php" . PHP_EOL;
    $contents .= "\$gw_address = '$ipaddress';" . PHP_EOL;
    $contents .= "\$gw_user = '$identity';" . PHP_EOL;
    $contents .= "\$gw_key = '$newKey';" . PHP_EOL;

    //write it out
    $success = fwrite($file, $contents);
    
    //close the file, we're done
    fclose($file);

    //did writing fail (or didn't it write more than 0 bytes)?
    if($success === false or $success <= 0) { //frwite failed
        errorConfigFileWrite();
        return;
    }

    //spread the word we succeeded
    successConfigFileWrite();
}

//main HTML content
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Setup</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    </head>
    <body>
        <div class="container">
            <h1>Setup</h1>
            <p>
                In order to use these scripts a config file with user credentials needs to be created. <br />
                To simplify this process it can be performed with this page.
            </p>
            <p>In the fields below, enter the IP address of your gateway and the key printed on the bottom of your gateway.</p>
<?php
    if(!isset($_POST['ipaddress']) or !isset($_POST['key'])) { //got no post data
?>
            <form method="post" action="">
                <div class="form-group">
                    <label for="ipaddress">IP Address</label>
                    <input type="text" value="" placeholder="IP Address" name="ipaddress" id="ipaddress" class="form-control" required="true">
                </div>
                <div class="form-group">
                    <label for="key">Gateway key</label>
                    <input type="text" value="" placeholder="Gateway key" name="key" id="key" class="form-control" required="true">
                </div>
                <button type="submit" class="btn btn-default">Submit</button>
            </form>
<?php
    } else { //got post data
        $credentials = getCredentials($_POST['ipaddress'], $_POST['key']);
        if($credentials != null)
            saveCredentials($_POST['ipaddress'], $credentials['identity'], $credentials['key']);
    }
?>    
        </div>
    </body>
</html>
<?php
//HTML templates for error/success messages
function errorCommandFailed($rc, $stderr) {
?>
        <div class="panel panel-danger">
            <div class="panel-heading">
                <h3 class="panel-title"><span class="glyphicon glyphicon-warning-sign"></span> Error</h3>
            </div>
            <div class="panel-body">  
                <p>The command <code>coap-client</code> couldn't be executed. Check that you have it installed and that DTLS support is enabled. For convenience I recommend this script: <a href="https://github.com/ggravlingen/pytradfri/blob/master/script/install-coap-client.sh">install-coap-client.sh</a></p>
                <p><samp>
                    Exit Code: <?= $rc ?><br />
                    <?= $stderr ?>
                </samp></p>
                <p><a href="javascript:window.history.back();"><span class="glyphicon glyphicon-repeat"></span> Retry</a></p>
            </div>
        </div>
<?php        
}

function errorUnknownResponse($errorCode) {
?>
        <div class="panel panel-danger">
            <div class="panel-heading">
                <h3 class="panel-title"><span class="glyphicon glyphicon-warning-sign"></span> Error</h3>
            </div>
            <div class="panel-body">  
                <p>
                    Recieved an unkwown response from the gateway:<br />
                    <samp><?= $errorCode ?></samp>
                </p>
                <p><a href="javascript:window.history.back();"><span class="glyphicon glyphicon-repeat"></span> Retry</a></p>
            </div>
        </div>
<?php
}

function errorGatewayUnreachable() {
?>
        <div class="panel panel-danger">
            <div class="panel-heading">
                <h3 class="panel-title"><span class="glyphicon glyphicon-warning-sign"></span> Error</h3>
            </div>
            <div class="panel-body">  
                <p>Failed to connect to the gateway.</p>
                <p>Check that the IP address and the key you entered are correct.</p>
                <p><a href="javascript:window.history.back();"><span class="glyphicon glyphicon-repeat"></span> Retry</a></p>
            </div>
        </div>
<?php
}

function errorConfigFileOpen() {
?>    
        <div class="panel panel-danger">
            <div class="panel-heading">
                <h3 class="panel-title"><span class="glyphicon glyphicon-warning-sign"></span> Error</h3>
            </div>
            <div class="panel-body">    
                <p>Failed to open config.php for writing.</p>
                <p>Check that you have permissions.</p>
                <p><a href="javascript:window.history.back();"><span class="glyphicon glyphicon-repeat"></span> Retry</a></p>
            </div>
        </div>
<?php
}

function errorConfigFileWrite() {
?>
    <div class="panel panel-danger">
        <div class="panel-heading">
            <h3 class="panel-title"><span class="glyphicon glyphicon-warning-sign"></span> Error</h3>
        </div>
        <div class="panel-body">  
            <p>Failed to write to config.php.</p>
            <p>Check that enough space is available.</p>
            <p><a href="javascript:window.history.back();"><span class="glyphicon glyphicon-repeat"></span> Retry</a></p>
        </div>
    </div>
<?php
}

function successConfigFileWrite() {
?>
    <div class="panel panel-success">
        <div class="panel-heading">
            <h3 class="panel-title"><span class="glyphicon glyphicon-ok-sign"></span> Success</h3>
        </div>
        <div class="panel-body">  
            <p>The configuration file has been written.</p>
        </div>
    </div>
<?php  
}