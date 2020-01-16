<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "This is a git pull!";
if (preg_match("/192.168.2./i", $_SERVER['REMOTE_ADDR'])) {

    exec("cd /var/repo/git-site && /usr/bin/git pull --no-ff --no-edit https://@git.xavizus.com/Bonobo.Git.Server/ServerStatus.git Development 2>&1",$result);
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
