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
		printf(ROOT_DIR);
		$this->config = new Settings('/var/www/xav-p-dev01.xavizus.com/Secret/config.ini');
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