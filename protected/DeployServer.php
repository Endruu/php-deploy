<?php

require 'DeployBase.php';

class DeployClient extends DeployBase {
	
	private function unzipFiles( $name = 'deploy.zip', $path = '' ) {
	}
	
	public function deploy() {
		$this->projectPath ? $this->readDir($this->projectPath) : $this->readDir('.');
		$this->createWorkingDirectory();
		
		$this->preDeployScript();
		$this->unzipFiles('deploy.zip', $this->workDir);
		$this->postDeployScript();
	}
}
