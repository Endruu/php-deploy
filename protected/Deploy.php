<?php

require_once 'DeployClient.php';
require_once 'DeployServer.php';

class Deploy {
	public $workDir = null;

	private function getOptions( $iniPath ) {
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

	public function deployClient( $ini, $workDir ) {
		$base = new DeployClient;					// create the basic client class
		
		$wd = $base->setWorkPath($workDir);			// create working directory
		$opt = $this->getOptions($ini);				// read options
		
		require_once($opt['client']['script']);		// include deploying script
		$deployer = new $opt['client']['class'];	// create the deploying class
		$deployer->init($base, $opt);				// initialize deploying class
		
		$deployer->deploy();						// deploy
		
		$base->zipFiles();							// zip the created files
		$this->workDir = $wd;
	}
	
	public function deployServer( $zip, $workDir ) {
		$base = new DeployServer;					// create the basic server class
		
		$wd = $base->setWorkPath($workDir);			// create working directory
		$base->unzipFiles($zip);					// unzip the deploying files
		$opt = $this->getOptions($wd.'deploy.ini');	// read options
		
		require_once($opt['server']['script']);		// include deploying script
		$deployer = new $opt['server']['class'];	// create the deploying class
		$deployer->init($base, $opt);				// initialize deploying class
		
		$deployer->deploy();						// deploy
		$this->workDir = $wd;
	}
	
}