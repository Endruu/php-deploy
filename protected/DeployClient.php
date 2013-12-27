<?php

require_once 'DeployBase.php';

class DeployClient extends DeployBase {
	
	public function excludeFile( $file, $withPath = false ) {
		$this->removeFile( $file, $withPath );
	}
	
	public function includeFile( $file ) {
		$this->addFile( $file );
	}
	
	public function excludeDir( $dir ) {
		$this->removeDir( $dir );
	}
	
	public function includeDir( $dir, $prefix = true ) {
		$this->addDir( $dir, $prefix );
	}
	
	
	private function zipFiles( $name = 'deploy.zip') {
		$zip = new ZipArchive;
		
		// --- Zip source ---
		$ret = $zip->open($this->workDir.'src.zip', ZipArchive::CREATE);
		if( $ret !== true ) {
			throw new Exception("Can't create archive: src.zip! Zip error code: $ret");
		}
		
		foreach( $this->files as $f ) {
			if( !$zip->addFile($this->projectPath.'/'.$f, $f) ) {
				throw new Exception("Can't add file $f to archive!");
			}
		}
		
		if( !$zip->close() ) {
			throw new Exception("Can't close archive!");
		}
		
		// --- Zip deploy ---
		$ret = $zip->open($this->workDir.$name, ZipArchive::CREATE);
		if( $ret !== true ) {
			throw new Exception("Can't create archive: $name! Zip error code: $ret");
		}
			
		if( !$zip->addFile($this->workDir.'src.zip', 'src.zip') ) {
			throw new Exception("Can't add file: src.zip to archive!");
		}
		if( !$zip->addFile($this->workDir.'files.txt', 'files.txt') ) {
			throw new Exception("Can't add file: files.txt to archive!");
		}
		if( !$zip->addFile($this->workDir.'directories.txt', 'directories.txt') ) {
			throw new Exception("Can't add file: directories.txt to archive!");
		}
		
		if( !$zip->close() ) {
			throw new Exception("Can't close archive!");
		}
	}
	
	
	public function deploy() {
		$this->projectPath ? $this->readDir($this->projectPath) : $this->readDir('.');
		$this->createWorkingDirectory();
		
		$this->preDeployScript();
		$this->writeDir($this->workDir);
		$this->zipFiles('deploy.zip', $this->workDir);
		$this->postDeployScript();
	}
}
