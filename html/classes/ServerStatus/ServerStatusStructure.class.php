<?php

namespace ServerStatus;

class ServerStatusStructure {
	
	/*
	@private $config Contains all configurations for the site.
	*/
	private $config = NULL;
	/*
	@private $status Contains status of all servers.
	*/
	private $status = NULL;
	
	public function __construct() {
		/*
		Load settings.
		*/
		$this->config = new Settings();
	}

	public function GetPrivateKey() {
		return $this->config->PrivateKey();
	}
	public function GetIV() {
		return $this->config->IV();
	}

	public function updateServerPassword($pass, $server) {
		$this->config->dbcon->updateServerPassword($pass, $server, $this->GetPrivateKey(),$this->GetIV());

	}

	public function getServerPassword($server){
		return $this->config->dbcon->getServerPassword($server,$this->GetPrivateKey(),$this->GetIV() );
	}
}
?>