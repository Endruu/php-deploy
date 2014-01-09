<?php

require_once 'DeployClient.php';
require_once 'DeployServer.php';

class Deploy {

	protected function initByIni( $iniPath ) {
		$optOuter = parse_ini_file($iniPath, true);
		if( $optOuter === false ) {
			throw new Exception("Failed to open file: $iniPath for initialization!");
		}
		$optInner = parse_ini_file('protected/deploy.ini', true);
		if( $optInner === false ) {
			throw new Exception("Failed to open file: protected/deploy.ini for initialization!");
		}
		
		return array_merge($optInner, $optOuter);
	}
	
	public function run( $config = 'deploy.ini', $host = 'client' ) {
	
		$opt = $this->initByIni($config);
		
		require_once($opt[$host]['script']);
		$deployClass = new $opt[$host]['class'];
		$deployClass->setProjectPath($opt[$host]['path']);
		$deployClass->incZip = false;
		
		$deployClass->deploy();
		
	}
}