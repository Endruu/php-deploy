<?php

require 'DeployBase.php';

class DeployClient extends DeployBase {

	public $projectPath = '';

	protected $directories	= array();
	protected $files		= array();

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
		
		sort($this->files);
		$file = fopen('files.txt', 'w');
		if( !$file ) {
			throw new Exception("Failed to open file: files.txt for writing!");
		}
		foreach( $this->files as $f ) {
			fwrite($file, substr($f, $chop) . "\n");
		}
		fclose($file);
		
		sort($this->directories);
		$file = fopen('directories.txt', 'w');
		if( !$file ) {
			throw new Exception("Failed to open directories: files.txt for writing!");
		}
		foreach( $this->directories as $d ) {
			fwrite($file, substr($d, $chop) . "\n");
		}
		fclose($file);

	}
	
	public function excludeFile( $file, $withPath = false ) {
		$chop = $this->projectPath ? strlen($this->projectPath) + 1 : 0;
		$newfiles	= array();
		
		foreach( $this->files as $f ) {
			if( $withPath ) {
				$ret = preg_match($file, substr($f, $chop));
				if( $ret === 0 ) {
					$newfiles[] = $f;
				} else if( $ret === false ) {
					throw new Exception("Failed to parse filename for excluding file! (1)");
				}
			} else {
				$ret = preg_match("/.*[\/]([^\/]*)$/", substr($f, $chop), $m);
				if( $ret ) {
					$ret = preg_match($file, $m[1]);
					if( $ret === 0 ) {
						$newfiles[] = $f;
					} else if( $ret === false ) {
						throw new Exception("Failed to parse filename for excluding file! (2)");
					}
				} else if( $ret === false ) {
					throw new Exception("Failed to parse filename to get dirname for excluding file!");
				} else {
					$ret = preg_match($file, substr($f, $chop));
					if( $ret === 0 ) {
						$newfiles[] = $f;
					} else if( $ret === false ) {
						throw new Exception("Failed to parse filename for excluding file! (3)");
					}
				}
			}
		}
		
		$this->files = $newfiles;
	}
	
	public function includeFile( $file ) {
		if( $this->projectPath ) {
			$file = $this->projectPath .'/'. $file;
		}
		
		if( !file_exists($file) ) {
			throw new Exception("Failed to include file: $file (not found)");
		}
		
		$ret = preg_match("/(.*)[\/][^\/]*$/", $file, $m);
		if( $ret ) {
			$this->includeDir($m[1], false);
		} else if( $ret === false ) {
			throw new Exception("Failed to parse filename $file for including!");
		}
		$this->files[] = $file;
		$this->files = array_unique( $this->files );
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
				throw new Exception("Failed to parse dirname for excluding directory!");
			}
		}
		foreach( $this->files as $f ) {
			$ret = preg_match("/(.*)[\/][^\/]*$/", substr($f, $chop), $m);
			if( $ret ) {
				$ret = preg_match($dir, $m[1]);
				if( $ret === 0 ) {
					$newfiles[] = $f;
				} else if( $ret === false ) {
					throw new Exception("Failed to parse filename for excluding directory!");
				}
			} else if( $ret === false ) {
				throw new Exception("Failed to parse filename to get dirname for excluding directory!");
			} else {
				$newfiles[] = $f;
			}
			
		}
		
		$this->files = $newfiles;
		$this->directories = $newdirs;
	}
	
	public function includeDir( $dir, $prefix = true ) {
		if( $prefix && $this->projectPath ) {
			$dir = $this->projectPath .'/'. $dir;
		}
		$this->directories[] = $dir;
		$this->directories = array_unique( $this->directories );
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
		
		if( !$zip->addFile('files.txt') ) {
			throw new Exception("Can't add file: files.txt to archive!");
		}
		if( !$zip->addFile('directories.txt') ) {
			throw new Exception("Can't add file: directories.txt to archive!");
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
