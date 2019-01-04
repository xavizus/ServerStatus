<?php
echo "Git Pull!";
$allow = array("192.168.2");
if (in_array($_SERVER['REMOTE_ADDR'], $allow ) {

    exec("cd /var/repo/git-site && git pull --no-ff https://UserName:Password@git.xavizus.com/Bonobo.Git.Server/ServerStatus.git Development",$result);
    echo "<pre>";
    foreach($result as $line) {
        print($line . "\n");
    }
    echo "</pre>";
}
else {
    echo "<h1> You are not on the correct network to use this function!</h1>";
}
?>