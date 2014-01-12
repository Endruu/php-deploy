<?php

require_once 'DeployClient.php';
require_once 'DeployServer.php';

class Deploy {
	public $workDir = null;

	private function getOptions( $iniPath ) {
		$optOuter = Ini::readIni($iniPath);
		if( $optOuter === false ) {
			throw new Exception("Failed to open file: $iniPath for initialization!");
		}
		$optInner = Ini::readIni('protected/deploy.ini');
		if( $optInner === false ) {
			throw new Exception("Failed to open file: protected/deploy.ini for initialization!");
		}
		
		return array_merge($optInner, $optOuter);
	}

	public function deployClient( $ini, $workDir = null ) {
		$base = new DeployClient;					// create the basic client class
		
		$wd = $base->setWorkPath($workDir);			// create working directory
		$opt = $this->getOptions($ini);				// read options
		Ini::writeIni($wd.'deploy.ini', array('server' => $opt['server']));
		
		require_once($opt['client']['script']);		// include deploying script
		$deployer = new $opt['client']['class'];	// create the deploying class
		$deployer->init($base, $opt);				// initialize deploying class
		
		$deployer->deploy();						// deploy
		
		$base->zipFiles();							// zip the created files
		$this->workDir = $wd;
	}
	
	public function deployServer( $zip, $workDir = null ) {
		$base = new DeployServer;					// create the basic server class
		
		$wd = $base->setWorkPath($workDir, false);			// create working directory
		$base->unzipFiles($zip);					// unzip the deploying files
		$opt = $this->getOptions($wd.'deploy.ini');	// read options
		
		require_once($opt['server']['script']);		// include deploying script
		$deployer = new $opt['server']['class'];	// create the deploying class
		$deployer->init($base, $opt);				// initialize deploying class
		
		$deployer->deploy();						// deploy
		$this->workDir = $wd;
	}
	
}

class Ini {

	public static function readIni($filename) {
		return parse_ini_file($filename, true);
	}

	public static function writeIni($filename, $ini) {
        $string = '';
        foreach(array_keys($ini) as $key) {
            $string .= '['.$key."]\n";
            $string .= Ini::stringify($ini[$key], '') . "\n";
        }
        file_put_contents($filename, $string);
    }

    private static function stringify($ini, $prefix) {
        $string = '';
        ksort($ini);
        foreach($ini as $key => $val) {
            if (is_array($val)) {
                $string .= Ini::stringify($ini[$key], $prefix.$key.'.');
            } else {
                $string .= $prefix.$key.' = '.str_replace("\n", "\\\n", Ini::value($val))."\n";
            }
        }
        return $string;
    }

    private static function value($val) {
        if ($val === true)
			return 'true';
        else if ($val === false)
			return 'false';
		else if ($val === null)
			return 'null';
		else
			return $val;
    }
}