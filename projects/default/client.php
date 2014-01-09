<?php

class DeployClientScripts extends DeployClient {

	public function preDeployScript() {
		$this->excludeDir("/^\.git/");
		$this->excludeFile("/^\.git/");
		$this->excludeFile("/^deploy.php/");
		$this->excludeDir("/work/");
		$this->excludeDir("/projects/");
		
		$this->includeDir("protected/work");
		//$this->includeFile("projects/default/server.php");
	}
	
}