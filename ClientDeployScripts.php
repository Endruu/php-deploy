<?php

require 'Client.php';

class ClientDeployScripts extends Client {

	public function preDeployScript() {
		$this->excludeDir("/^.git/");
	}
	
}