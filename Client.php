<?php

class Client /*extends Deploy*/ {

	public $projectPath = '';

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
		$chop = $this->projectPath ? strlen($this->projectPath) + 1 : 0;
		
		$file = fopen('files.txt', 'w');
		foreach( $this->files as $f ) {
			fwrite($file, md5_file($f) .' '. substr($f, $chop) . "\n");
		}
		fclose($file);
		
		$file = fopen('directories.txt', 'w');
		foreach( $this->directories as $d ) {
			fwrite($file, substr($d, $chop) . "\n");
		}
		fclose($file);

	}
	
	public function excludeDir( $dir ) {
		$chop = $this->projectPath ? strlen($this->projectPath) + 1 : 0;
		$newdirs	= array();
		$newfiles	= array();
		
		foreach( $this->directories as $d ) {
			$ret = preg_match($dir, substr($d, $chop));
			if( $ret === 0 ) {
				$newdirs[] = $d;
			} else if( $ret === false ) {
				//hiba
			}
		}
		foreach( $this->files as $f ) {
			$ret = preg_match("/(.*)[\/][^\/]*$/", substr($f, $chop), $m);
			if( $ret ) {
				$ret = preg_match($dir, $m[1]);
				if( $ret === 0 ) {
					$newfiles[] = $f;
				} else if( $ret === false ) {
					//hiba
				}
			} else if( $ret === false ) {
				//hiba
			}
			
		}
		
		$this->files = $newfiles;
		$this->directories = $newdirs;
	}
	
	private function zipFiles( $name = 'deploy.zip', $path = '' ) {
		$zip = new ZipArchive;
		$chop = $this->projectPath ? strlen($this->projectPath) + 1 : 0;
		
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
		$this->projectPath ? $this->readDir($this->projectPath) : $this->readDir('.');
		$this->preDeployScript();
		$this->writeDir();
		
		$this->zipFiles();
		
		$this->postDeployScript();
	}
}
