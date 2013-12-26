<?php

require 'ClientDeployScripts.php';

try {
$d = new ClientDeployScripts;
$d->deploy();
} catch( Exception $e ) {
	echo $e->getMessage();
}