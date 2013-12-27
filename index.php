<?php

require_once 'protected/ClientDeployScripts.php';
require_once 'protected/DeployServer.php';

try {
//$d = new ClientDeployScripts;
$d = new DeployServer;
$d->projectPath = '../pannonia';
$d->deploy();
} catch( Exception $e ) {
	echo $e->getMessage();
}