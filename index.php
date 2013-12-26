<?php

require 'protected/ClientDeployScripts.php';

try {
$d = new ClientDeployScripts;
$d->projectPath = '../pannonia';
$d->deploy();
} catch( Exception $e ) {
	echo $e->getMessage();
}