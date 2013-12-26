<?php

class Client /*extends Deploy*/ {

	public $projectPath = '.';

	protected $directories	= array();
	protected $files		= array();

	public function preDeployScript() {}
	public function postDeployScript() {}
	
	public function readDir( $dir = '.' ) {
		$scan = array_diff(scandir($dir), array('..', '.'));
		
		if( $dir === '.' ) {
			$dir = '';
		} else {
			$dir .= '/';
		}
		
		foreach( $scan as $s ) {
			$path = $dir . $s;
			if( is_file($path) ) {
				$this->files[] = $path;
			} else {
				$this->directories[] = $path;
				$subdir_found = true;
				$this->readDir($path);
			}
		}
	}
	
	public function writeDir() {
		$out = array('files', 'directories');
		$chop = strlen($this->projectPath) + 1;
		
		foreach( $out as $o ) {
			$file = fopen( $o.'.txt', 'w');
			foreach( $this->$o as $f ) {
				fwrite($file, substr($f, $chop) . "\n");
			}
			fclose($file);
		}
	}
	
	public function excludeDir( $dir ) {
		$chop = strlen($this->projectPath) + 1;
		$newdirs	= array();
		
		foreach( $this->directories as $d ) {
			$ret = preg_match($dir, substr($d, $chop));
			if( $ret === 0 ) {
			
				$newdirs[] = $d;
				
				$newfiles	= array();
				foreach( $this->files as $f ) {
					echo "$f - $d\n";
					if( strpos($f, $d) === false ) {	// if dir is not found in files name
						$newfiles[] = $f;				// keep it
					}
				}
				$this->files = $newfiles;
				
			} else if( $ret === false ) {
				//hiba
			}
		}
		
		$this->directories = $newdirs;
	}
	
	private function zipFiles( $name = 'deploy.zip', $path = '' ) {
		$zip = new ZipArchive;
		$chop = strlen($this->projectPath) + 1;
		
		$ret = $zip->open($path.$name, ZipArchive::EXCL );
		if( $ret === true ) {
			foreach( $this->files as $f ) {
				if( !$zip->addFile($f, 'src/'.substr($f,$chop)) ) {
					throw new Exception("Can't add file to archive!\nArchive: $path$name\nFile:    $f");
				}
			}
		} else {
			throw new Exception("Can't create archive: $path$name! Zip error code: $ret");
		}
		
		if( !$zip->close() ) {
			throw new Exception("Can't close archive!");
		}
	}
	
	public function deploy() {
		$this->readDir($this->projectPath);
		$this->preDeployScript();
		$this->writeDir();
		
		$this->zipFiles();
		
		$this->postDeployScript();
	}
}
