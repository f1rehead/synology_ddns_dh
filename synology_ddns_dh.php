<?php
# vi: ts=4 noexpandtab

// From https://forum.synology.com/enu/viewtopic.php?t=70027

# Output:
# When you write your own module, you can use the following words to tell user
# what happen by print it.
# You can use your own message, but there is no multiple-language support.
#
# good - Update successfully.
# nochg - Update successfully but the IP address have not changed.
# nohost - The hostname specified does not exist in this user account.
# abuse - The hostname specified is blocked for update abuse.
# notfqdn - The hostname specified is not a fully-qualified domain name.
# badauth - Authenticate failed.
# 911 - There is a problem or scheduled maintenance on provider side
# badagent - The user agent sent bad request(like HTTP method/parameters is not permitted)
# badresolv - Failed to connect to because failed to resolve provider address.
# badconn - Failed to connect to provider because connection timeout.

// DreamHost DNS API documentation is available here:
// https://help.dreamhost.com/hc/en-us/articles/217555707-DNS-API-commands


// Set DH_API_KEY to your key (per https://panel.dreamhost.com/?tree=home.api)
// You probably should limit this key to dns modifications only
$DH_API_KEY = "";

// set HOSTS to contain an array of fields that can be modified
// only entries in this list will be updated

$HOSTS = array("myhost.example.com");

// set a password.  If you wish to restrict this, set a password here
// and then pass make your url request to include 'passwd' key
// set to "" if you do not wish to have a password
$PASSWD = "";

// Used to prefix the UUID for the DreamHost API
// (and in the comment if you choose)
$APP_NAME = "synology-ddns-dh";

// Base URL for the DreamHost API. You probably don't need to change this.
$DH_API_BASE = "https://api.dreamhost.com/";

// Default comment if one is not passed in the query string. Leave blank
// for no comment.
$DEF_COMMENT = "Updated by " + $APP_NAME + " at " + date ("l jS \of F Y h:i:s A");


function dh_request($cmd,$aargs=false) {
	global $DH_API_BASE, $DH_API_KEY, $APP_NAME;
	$base = $API_BASE;
	$id = uniqid($APP_NAME);

	$args = "?key=$DH_API_KEY&cmd=$cmd&format=json";
	$args .= "&unique_id=" . uniqid($APP_NAME);

	if (is_array($aargs)) {
		foreach($aargs as $key => $val) {
			$args.="&" . urlencode($key) . "=" . urlencode($val);
		}
	}

	$url = $DH_API_BASE . $args;
	$curl_handle = curl_init();
	curl_setopt($curl_handle, CURLOPT_URL, $DH_API_BASE . $args);
	curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
	$buffer = curl_exec($curl_handle);
	curl_close($curl_handle);

	if (empty($buffer)) {
    	return(false);
	} else {
    	return(json_decode($buffer,true));
	}
}

function fail($str) {
	printf("error: %s",$str);
	exit(false);
}


$passwd = $_REQUEST["passwd"];
$host = $_REQUEST["host"];
$addr = $_REQUEST["myip"];
$comment = $_REQUEST["comment"];

if (empty($comment)) { $comment = $DEF_COMMENT; }

if ($PASSWD != $passwd) { fail("badauth - incorrect or missing password\n"); }
if (!in_array($host,$HOSTS)) { fail("nohost\n"); }

if (!$DH_API_KEY) {
	$DH_API_KEY = $_REQUEST["key"];
	if (!$DH_API_KEY) { fail("badagent - incorrect or missing DreamHost API key\n"); }
}

if (!$addr) { $addr=$_SERVER["REMOTE_ADDR"]; }

$ret = dh_request("dns-list_records");
error_log(json_encode($ret));

if ($ret["result"] != "success") {
	fail("911 - failed list records\n");
	return(1);
}

$found = false;

foreach ($ret["data"] as $key => $row) {
	if ($row["record"] == $host) {
		if ($row["editable"] == 0) {
			fail("nohost - $host not editable");
		}
		if ($row["type"] != "A") {
			fail("nohost - $host not a A record");
		}
		$found = $row;
	}
}

if ($found) {
	if ($addr == $found["value"]) {
		printf("nochg - %s => %s\n", $found["record"], $addr);
		return(0);
	}

	$ret = dh_request("dns-remove_record",
		            array("record" => $found["record"],
					      "type" => $found["type"],
						  "value" => $found["value"]));

	if ($ret["result"] != "success") {
		fail("911 - failed to remove record " + $ret["data"] + "\n");
		return(1);
	}

	$record = $found["record"];
	$type = $found["type"];
	printf("deleted %s. had value %s\n", $record, $found["value"]);

} else {
	$record = $host;
	$type = 'A';
}

$ret = dh_request("dns-add_record",
                array("record" => $record,
                  "type" => $type,
                  "value" => $addr,
				  "comment" => $comment));

if ($ret["result"] != "success") {
	fail("911 - failed to add $record of type $type to $addr " + $ret["data"]);
	return(1);
}

printf("good - set %s to %s\n", $record, $addr);
?>

