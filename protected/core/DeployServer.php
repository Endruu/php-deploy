<?php

require_once 'DeployBase.php';

class DeployServer extends DeployBase {

	protected $newFiles = array();
	protected $oldFiles = array();
	protected $existingFiles = array();
	protected $newDirectories = array();
	protected $oldDirectories = array();
	
	private $logFile = null;
	
	public function init($base, $opt) {
		$this->initServer($base, $opt);
	}
	
	protected function initServer($base, $opt) {
		$this->initBase($base);
		$this->setProjectPath($opt['server']['path']);
	}
	
	public function keepFile( $file, $withPath = false ) {
		$this->removeFile( $file, $withPath );
	}
	
	public function obsoleteFile( $file ) {
		$this->addFile( $file );
	}
	
	public function keepDir( $dir ) {
		$this->removeDir( $dir );
	}
	
	public function obsolateDir( $dir, $prefix = true ) {
		$this->addDir( $dir, $prefix );
	}
	
	public function unzipFiles( $name ) {
		$zip = new ZipArchive;
		
		$ret = $zip->open($name, ZipArchive::CHECKCONS);
		if( $ret !== true ) {
			throw new Exception("Can't open archive: $name! Zip error code: $ret");
		}

		if( !$zip->extractTo($this->workDir) ) {
			throw new Exception("Failed to extract from $name!");
		}
		
		if( !$zip->close() ) {
			throw new Exception("Can't close archive!");
		}
	}
	
	private function unzipSrc() {
		$zip = new ZipArchive;
		
		$ret = $zip->open($this->workDir.'src.zip', ZipArchive::CHECKCONS);
		if( $ret !== true ) {
			throw new Exception("Can't open archive: src.zip! Zip error code: $ret");
		}
		
		if( !$zip->extractTo($this->projectPath) ) {
			throw new Exception("Failed to extract src.zip to project!");
		}
		
		if( !$zip->close() ) {
			throw new Exception("Can't close archive!");
		}
	}
	
	protected function diffDir() {
		$dirs	= file($this->workDir.'directories.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$files	= file($this->workDir.'files.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		
		$this->newFiles = array_diff($files, $this->files);
		$this->oldFiles = array_diff($this->files, $files);
		$this->existingFiles = array_intersect($this->files, $files);
		
		$this->newDirectories = array_diff($dirs, $this->directories);
		$this->oldDirectories = array_diff($this->directories, $dirs);
	}
	
	protected function writeDiff() {
		$file = fopen($this->workDir.'difference.txt', 'w');
		if( !$file ) {
			throw new Exception("Failed to open file: difference.txt for writing!");
		}
		
		fwrite($file, "--- NEW FILES -------------------\n\n");
		foreach( $this->newFiles as $f ) {
			fwrite($file, $f . "\n");
		}
		fwrite($file, "\n\n--- OLD FILES -------------------\n\n");
		foreach( $this->oldFiles as $f ) {
			fwrite($file, $f . "\n");
		}
		fwrite($file, "\n\n--- EXISTING FILES --------------\n\n");
		foreach( $this->existingFiles as $f ) {
			fwrite($file, $f . "\n");
		}
		fwrite($file, "\n\n--- NEW DIRECTORIES -------------\n\n");
		foreach( $this->newDirectories as $f ) {
			fwrite($file, $f . "\n");
		}
		fwrite($file, "\n\n--- OLD DIRECTORIES -------------\n\n");
		foreach( $this->oldDirectories as $f ) {
			fwrite($file, $f . "\n");
		}
		
		fclose($file);
	}
	
	private function createNewDirectories() {
		foreach( $this->newDirectories as $d ) {
			if( !( file_exists($d) && is_dir($d) ) ) {
				if( !mkdir($this->projectPath.'/'.$d, 0777, true) ) {
					throw new Exception("Can't create directory: $d");
				}
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
		
		$this->diffDir();
		$this->preDeployScript();
		$this->writeDiff();
		
		$this->unzipSrc();
		$this->createNewDirectories();
		
		$this->postDeployScript();
		$this->closeLog();
	}
}
