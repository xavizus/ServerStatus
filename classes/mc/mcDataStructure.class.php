<?php	
	namespace mc;
	
	use xPaw\MinecraftPing;
	use xPaw\MinecraftPingException;
	
class mcDataStructure {
	private $online = false;
	private $description;
	private $maxplayers;
	private $onlineplayers;
	private $version;
	private $icon;
	private $protocol;
	
	private $ServerAddress;
	private $ServerPort;
	private $Timeout;
	private $ResolveSRV;
	private $Exception;
	private $Data = false;
	private $Query = NULL;
	private $oldQuery = NULL;
	
	public function __construct($Address, $Port = 25565, $Timeout = 1, $ResolveSRV = true) {
		$this->ServerAddress = $Address;
		$this->ServerPort = (int)$Port;
		$this->Timeout =(int)$Timeout;
		$this->ResolveSRV = (bool)$ResolveSRV;
		$TimeStart = microtime(true);
		if( $ResolveSRV )
		{
			$this->ResolveSRV();
		}
		
		
		try {
			$this->Query = new \xPaw\MinecraftPing($this->ServerAddress,$this->ServerPort,$this->Timeout, $this->ResolveSRV);
			if (!$this->Query->get_connected()) {
				throw new \xPaw\MinecraftPingException("Failed to connect or create a socket");
			}
			$this->Data = $this->Query->Query();
		}
		catch(MinecraftPingException $e) {
			$this->Exception = $e->getMessage();
		}
		
		if($this->Query !== NULL){
			$this->Query->Close();
		}
		
		if($this->Data !== false) {
			$this->online = true;
			foreach ($this->Data as $DataKey => $DataValue) {
				if($DataKey === 'description'){
					if (isset($DataValue['text'])){
						$this->description = $DataValue['text'];
					}
					else{
						$this->description = $DataValue;
					}
				}
				if($DataKey === 'players') {
					$this->maxplayers = $DataValue['max'];
					$this->onlineplayers = $DataValue['online'];
				}
				if($DataKey === 'version') {
					$this->version = $DataValue['name'];
					$this->protocol = $DataValue['protocol'];
				}
				if($DataKey === 'favicon')
					$this->icon = Str_Replace('\n','',$DataValue);
			}
		}	
	}
	
	public function get_data() {
		return $this->Data;
	}
	
	public function get_description() {
		return $this->description;
	}
	public function get_maxplayers() {
		return $this->maxplayers;
	}
	public function get_onlineplayers() {
		return $this->onlineplayers;
	}
	public function get_version() {
		return $this->version;
	}
	public function get_icon() {
		return $this->icon;
	}
	public function get_address() {
		return $this->ServerAddress;
	}
	public function get_port() {
		return $this->ServerPort;
	}
	public function get_exception() {
		return $this->Exception;
	}
	public function get_online() {
		return $this->online;
	}
	public function get_protocol() {
		return $this->protocol;
	}
	
	private function ResolveSRV(){
		if( ip2long( $this->ServerAddress ) !== false ){
			return;
		}
		
		$Record = dns_get_record( '_minecraft._tcp.' . $this->ServerAddress, DNS_SRV );
		
		if( empty( $Record ) ){
			return;
		}

		if( isset( $Record[ 0 ][ 'target' ] ) ){
			$this->ServerAddress = $Record[ 0 ][ 'target' ];
		}

		if( isset( $Record[ 0 ][ 'port' ] ) ){
			$this->ServerPort = $Record[ 0 ][ 'port' ];
		}
	}
}
?>