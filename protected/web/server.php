<?php
	
	if( isset($_GET['a']) ) {
		switch( $_GET['a'] ) {
			case 'upload'	:
				aUpload();
				break;
			case 'deploy'	:
				aDeploy();
				break;
			default :
				aError();
		}
	} else {
		rIndex();
	}


function rIndex($post = null) {
?>
	<form action="index.php?a=upload" method="post" enctype="multipart/form-data">
		<label for="file">Deploy file:</label>
		<input type="file" name="file" id="file"><br />
		<input type="submit" name="submit" value="Submit">
	</form>
<?php
}

function rUpload($post = null) {
?>
	<form action="index.php?a=upload" method="post" enctype="multipart/form-data">
		<label for="file">Deploy file:</label>
		<input type="file" name="file" id="file"><br />
		<input type="submit" name="submit" value="Submit">
	</form>
<?php
}

function aUpload() {
	$maxSize	= 25 * 1024 *1024;	//25MB

	$error = '';
	
	if( $_FILES['file']['error'] > 0 ) {
		$error = "file:$error";
	} else {
		$arr = explode('.', $_FILES['file']['name']);
		$ext = array_pop($arr);
		
		if( $ext == 'zip' || $ext == 'dep' ) {
			if( $_FILES['file']['size'] < $maxSize ) {
				$name = saveFile($_FILES['file']['tmp_name'], $ext);
				aDeploy($name);
				//log!
				echo $name;
			} else {
				$error = 'size';
			}
		} else {
			$error = 'ext';
		}
	}
	echo "<br />$error";
}

function aDeploy($file) {
	require_once 'protected/core/Deploy.php';
	$d = new Deploy;
	$d->deployServer('protected/work/upload/'.$file);
}

function aError() {}

function saveFile($tmpname, $ext, $path = 'protected/work/upload') {
	$maxLength	= 40;
	$newname = substr(sha1_file($tmpname), 0, $maxLength) .'.'. $ext;
	
	if( !is_dir($path) ) {
		if( !mkdir( $path, 0777, true ) ) {
			throw Exception("Can't create upload dir!");
		}
	}
	
	if( !file_exists($path.'/'.$newname) ) {
		move_uploaded_file($tmpname, $path.'/'.$newname);
	}
	
	return $newname;
}
