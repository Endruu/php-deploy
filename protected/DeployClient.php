<?php

require 'DeployBase.php';

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
	
	
	private function zipFiles( $name = 'deploy.zip', $path = '' ) {
		$zip = new ZipArchive;
		$chop = $this->projectPath ? strlen($this->projectPath) + 1 : 0;
		
		$ret = $zip->open($path.$name, ZipArchive::CREATE);
		if( $ret === true ) {
			foreach( $this->files as $f ) {
				if( !$zip->addFile($f, 'src/'.substr($f,$chop)) ) {
					throw new Exception("Can't add file to archive!\nArchive: $path$name\nFile:    $f");
				}
			}
		} else {
			throw new Exception("Can't create archive: $path$name! Zip error code: $ret");
		}
		
		if( !$zip->addFile($path.'files.txt') ) {
			throw new Exception("Can't add file: files.txt to archive!");
		}
		if( !$zip->addFile($path.'directories.txt') ) {
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
