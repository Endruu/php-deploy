<?php

require_once 'DeployBase.php';
require_once 'Ini.php';

class DeployClient extends DeployBase {

	public	$incZip		= false;
	private	$serverOpts	= array();
	
	public function init($base, $opt) {
		$this->initClient($base, $opt);
	}
	
	protected function initClient($base, $opt) {
		$this->initBase($base);
		$this->setProjectPath($opt['client']['path']);
		if(isset($opt['client']['incZip']))
			$this->incZip = $opt['client']['incZip'];
		if(isset($opt['server']))
			$this->serverOpts = $opt['server'];
	}
	
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
	
	
	public function zipFiles( $name = 'deploy.zip') {
		$zip = new ZipArchive;

		$ret = $zip->open($this->workDir.$name, ZipArchive::CREATE);
		if( $ret !== true ) {
			throw new Exception("Can't create archive: $name! Zip error code: $ret");
		}
		
		$files = glob($this->workDir.'*.*');
		
		foreach( $files as $f ) {
			$base = basename($f);
			
			if( !$zip->addFile($f, $base) ) {
				throw new Exception("Can't add file $f to archive!");
			}
			
			if( $this->incZip ) {
				if( !$zip->close() ) {
					throw new Exception("Can't close archive: src.zip @ $f!");
				}
				$ret = $zip->open($this->workDir.$name);
				if( $ret !== true ) {
					throw new Exception("Can't reopen archive: $name! Zip error code: $ret");
				}
			}
			
		}
		
		if( !$zip->close() ) {
			throw new Exception("Can't close archive: $name!");
		}
	}
	
	private function zipSrc() {
		$zip = new ZipArchive;
		$path = $this->projectPath ? $this->projectPath.'/' : '';
		
		$ret = $zip->open($this->workDir.'src.zip', ZipArchive::CREATE);
		if( $ret !== true ) {
			throw new Exception("Can't create archive: src.zip! Zip error code: $ret");
		}
		
		foreach( $this->files as $f ) {
			
			if( !$zip->addFile($path.$f, $f) ) {
				throw new Exception("Can't add file $f to archive!");
			}
			
			if( $this->incZip ) {
				if( !$zip->close() ) {
					throw new Exception("Can't close archive: src.zip @ $f!");
				}
				$ret = $zip->open($this->workDir.'src.zip');
				if( $ret !== true ) {
					throw new Exception("Can't reopen archive: src.zip! Zip error code: $ret");
				}
			}
			
		}
		
		if( !$zip->close() ) {
			throw new Exception("Can't close archive: src.zip!");
		}
	}
	
	private function prepareServerFiles() {
		$file = $this->serverOpts['script'];
		
		if( $file ) {
			$target = $this->workDir.basename($file);
			if( !file_exists($file) ) {
				throw new Exception("Missing server deployment file: $file!");
			}
			
			if( !copy($file, $target) ) {
				throw new Exception("Cant copy server deployment file: $file to working directory: ". $this->workDir);
			}
		} else {
			$target = '';
		}
		
		$this->serverOpts['script'] = $target;
		Ini::writeIni($this->workDir.'deploy.ini', array('server' => $this->serverOpts));
	}
	
	public function deploy() {
		$this->projectPath ? $this->readDir($this->projectPath) : $this->readDir('.');
		$this->preDeployScript();
		$this->writeDir($this->workDir);
		$this->zipSrc();
		$this->prepareServerFiles();
		$this->postDeployScript();
	}
}
