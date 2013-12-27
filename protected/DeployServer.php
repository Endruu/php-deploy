<?php

require_once 'DeployBase.php';

class DeployServer extends DeployBase {

	protected $newFiles = array();
	protected $oldFiles = array();
	protected $owFiles = array();		// files to be overwritten
	protected $newDirectories = array();
	protected $oldDirectories = array();
	
	private $logFile = null;
	
	private function unzipFiles( $name = 'deploy.zip', $path = '' ) {
		$zip = new ZipArchive;
		
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
	
	protected function diffDir() {
		$dirs	= file($this->workDir.'directories.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$files	= file($this->workDir.'files.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		
		$this->newFiles = array_diff($files, $this->files);
		$this->oldFiles = array_diff($this->files, $files);
		$this->owFiles = array_diff($this->files, $files);
		
		$this->newDirectories = array_diff($files, $this->files);
		$this->oldDirectories = array_diff($this->files, $files);
	}
	
	protected function writeDiff() {
		$file = fopen($path.'difference.txt', 'w');
		if( !$file ) {
			throw new Exception("Failed to open file: $path"."difference.txt for writing!");
		}
		
		foreach( $this->newFiles as $d ) {
			fwrite($file, substr($d, $chop) . "\n");
		}
	}
	
	private function getDeployFile( $file = 'deploy.zip', $from = null ) {
		if( $from === null ) {
			// upload
		} else {
			if( !copy($from.'/'.$file, $this->workDir.$file) ) {
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
		if( $this->logFile !== null ) {
			fclose( $this->logFile );
			$this->logFile = null;
		}
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
