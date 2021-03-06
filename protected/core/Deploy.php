<?php

require_once 'DeployClient.php';
require_once 'DeployServer.php';
require_once 'Ini.php';

class Deploy {
	public $workDir = null;

	private function getOptions( $iniPath ) {
		$optOuter = Ini::readIni($iniPath);
		if( $optOuter === false ) {
			throw new Exception("Failed to open file: $iniPath for initialization!");
		}
		$optInner = Ini::readIni('protected/core/deploy.ini');
		if( $optInner === false ) {
			throw new Exception("Failed to open file: protected/deploy.ini for initialization!");
		}
		
		return Ini::mergeIni($optInner, $optOuter);
	}

	public function deployClient( $ini, $workDir = null ) {
		$base = new DeployClient;					// create the basic client class
		
		$wd = $base->setWorkPath($workDir);			// create working directory
		$opt = $this->getOptions($ini);				// read options
		
		set_time_limit($opt['client']['timeout']);
		
		if( $opt['client']['script'] ) {
			require_once($opt['client']['script']);	// include deploying script
		}
		$deployer = new $opt['client']['class'];	// create the deploying class
		$deployer->init($base, $opt);				// initialize deploying class
		
		$deployer->deploy();						// deploy
		
		$base->zipFiles();							// zip the created files
		$this->workDir = $wd;
	}
	
	public function deployServer( $zip, $workDir = null ) {
		$base = new DeployServer;					// create the basic server class
		
		$wd = $base->setWorkPath($workDir);			// create working directory
		$base->unzipFiles($zip);					// unzip the deploying files
		$opt = $this->getOptions($wd.'deploy.ini');	// read options
		
		set_time_limit($opt['server']['timeout']);
		
		if( $opt['server']['script'] ) {
			require_once($opt['server']['script']);	// include deploying script
		}
		$deployer = new $opt['server']['class'];	// create the deploying class
		$deployer->init($base, $opt);				// initialize deploying class
		
		$deployer->deploy();						// deploy
		$this->workDir = $wd;
	}
	
}
