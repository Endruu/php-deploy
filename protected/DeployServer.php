<?php

require_once 'DeployBase.php';

class DeployServer extends DeployBase {

	protected $newFiles = array();
	protected $oldFiles = array();
	protected $newDirectories = array();
	protected $oldDirectories = array();
	
	private $logFile = null;
	
	private function unzipFiles( $name = 'deploy.zip', $path = '' ) {
		$zip = new ZipArchive;
		$chop = $this->projectPath ? strlen($this->projectPath) + 1 : 0;
		
		$ret = $zip->open($path.$name, ZipArchive::CHECKCONS);
		if( $ret !== true ) {
			throw new Exception("Can't open archive: $path$name! Zip error code: $ret");
		}
		
		if( !$zip->extractTo($path, 'files.txt') ) {
		}
		
		if( !$zip->extractTo($path, 'directories.txt') ) {
		}
		
		if( !$zip->extractTo($path, 'src') ) {
		}
	}
	
	private function getDeployFile( $file = 'deploy.zip', $from = null ) {
		if( $from === null ) {
			// upload
		} else {
			if( !rename($from.'/'.$file, $this->workDir.$file) ) {
				throw new Exception("Can't copy deploy file from: $from to: " . $this->workDir . " (file: $file)\n");
			}
		}
	}
	
	public function log( $msg ) {
		if( $this->logFile === null ) {
			$file = fopen($this->workDir.'log.txt', 'a');
			if( !$file ) {
				throw new Exception("Failed to open log!");
			}
			$this->logFile = $file;
		}
		
		fwrite($this->logFile, $msg."\n");
	}
	
	public function closeLog() {
		fclose( $this->logFile );
		$this->logFile = null;
	}
	
	public function deploy() {
		$this->projectPath ? $this->readDir($this->projectPath) : $this->readDir('.');
		$this->createWorkingDirectory();
		$this->getDeployFile('deploy.zip', 'protected/work');
		
		$this->preDeployScript();
		$this->unzipFiles('deploy.zip', $this->workDir);
		$this->postDeployScript();
		$this->closeLog();
	}
}
