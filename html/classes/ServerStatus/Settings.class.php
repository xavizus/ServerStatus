<?php
	namespace ServerStatus;
	Error_Reporting( E_ALL | E_STRICT );
	Ini_Set( 'display_errors', true );
	define ("ROOT_DIR", __DIR__ . "/../../../",true);
	define ("APPLICATION_DIR", ROOT_DIR.'/html',true);

class Settings {
	public $dbcon = NULL;
	public $servers = NULL;
	public $encryption = NULL;

	public function __construct ($filepath) {
		try {
			/*
			Quick and dirty way to load an ini-file as an object instead of an array.
			*/
			//$ini_array = json_decode(json_encode(parse_ini_file("/opt/Secrets/config.ini",TRUE, INI_SCANNER_TYPED))); //The second param. will include the sections as an array. Then the json_encode and decode, make the array to a stdObj.
			$ini_array = json_decode(json_encode(parse_ini_file(ROOT_DIR.$filepath,TRUE, INI_SCANNER_TYPED)));
		}
		catch (Exception $e) {
			$e->getMessage();
		}
		
		$this->encryption = $ini_array->Encryption;
		/*
		Loading the Database to the Settings class.
		*/
		$this->dbcon = new database($ini_array->Database);

		$this->servers = new \StdClass();

		$result = $this->dbcon->customExecute("SELECT",array("ServerType","ServerName","TimeOut","ResolveSRV","Username","QueryPort","VoicePort"),"servers");;
		if($result->num_rows > 0) {
			while($row = $result->fetch_assoc()){
				if($row['ServerType'] == 'MC'){
					$this->servers->MC->ServerName[] 	= 		$row['ServerName'];
					$this->servers->MC->TimeOut[] 		= 		$row['TimeOut'];
					$this->servers->MC->ResolveSRV[] 	= 		$row['ResolveSRV'];
				}
				else if($row['ServerType'] == 'TS3'){
					$this->servers->TS3->ServerName[] 	=		$row['ServerName'];
					$this->servers->TS3->UserName[] 	= 		$row['Username'];
					$this->servers->TS3->QueryPort[] 	=	 	$row['QueryPort'];
					$this->servers->TS3->VoicePort[] 	=	 	$row['VoicePort'];
				}
			}
		}

		var_dump($this->servers);

	}

	public function PrivateKey() {
		return $this->encryption->PrivateKey;
	}
	public function IV() {
		return $this->encryption->IV;
	}
	

}

?>