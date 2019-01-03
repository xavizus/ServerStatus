<?php
require_once(__DIR__."/classes/TeamSpeak3/TeamSpeak3.php"); // path to teamspeak framework

$login =       "Xavizus";   // Login
$password =    "UiiyhKme";  // Password
$host =        "192.168.2.38";   // Server IP adress
$voiceport =   9988;        // voice port (default 9987)
$queryport =   10011;       // query port (default 10011)


try {
	$uri = "serverquery://".rawurlencode($login).":".rawurlencode($password)."@".$host.":".$queryport."/?server_port=".$voiceport;
    $ts3_connect = TeamSpeak3::factory($uri);
    echo "<table><tr>
    <td>Server Status:</td><td class='server_online'>".$ts3_connect->virtualserver_status."</td>
    </tr><tr>
    <td>Server Name</td><td class='server_name'>".$ts3_connect->virtualserver_name."</td>
    </tr><tr>
    <td>Server Adress:</td><td class='server_adress'>".$ts3_connect->getAdapterHost()."</td>
    </tr><tr>
    <td>Server Uptime:</td><td class='server_uptame'>".TeamSpeak3_Helper_Convert::seconds($ts3_connect->virtualserver_uptime)."</td>
    </tr><tr>
    <td>Users:</td><td class='server_users'>".($ts3_connect->virtualserver_clientsonline-$ts3_connect->virtualserver_queryclientsonline)."/".$ts3_connect->virtualserver_maxclients."</td>
    </tr><tr>
    <td>Channels:</td><td class='server_channels'>".$ts3_connect->virtualserver_channelsonline."</td>
    </tr><tr>
    <td>Download:</td><td class='server_download'>".TeamSpeak3_Helper_Convert::bytes($ts3_connect->connection_filetransfer_bytes_received_total + $ts3_connect->connection_bytes_received_total)."</td>
    </tr><tr>
    <td>Upload:</td><td class='server_upload'>".TeamSpeak3_Helper_Convert::bytes($ts3_connect->connection_filetransfer_bytes_sent_total + $ts3_connect->connection_bytes_sent_total)."</td>
    </tr></table>";

} catch(Exception $e) {
    echo "<p class='server_error'> ERROR: </p>".$e->getMessage();
}
?>