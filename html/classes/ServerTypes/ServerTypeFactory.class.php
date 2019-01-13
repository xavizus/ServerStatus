<?php
    namespace ServerTypes;

    class serverTypeFactory {

        protected $serverType = "";

        protected $serverAddress = "";

        protected $userName = "";

        protected $password = "";

        protected $voice_port = "";

        protected $serverport = "";

        protected $timeout = "";

        protected $resolve_dns = "";


        /**
         * matches[4] = Username
         * matches[5] = Password
         * matches[6] = serverAddress
         * matches[8] = Portnumber
         * matches[10] = Options
         */
        protected static $validServerTypes = array (
            'minecraft' => array(
                'serverAddress' => 6
            ),
            'teamspeak3' => array(
                'serverAddress' => 6,
                'userName' => 4,
                'password' => 5
            )
        );

        /**
         * Factory for serverType classes. $uri must be formatet as
         * "<serverType>://<user>:<pass>@<host>:<port>/<options>". 
         * serverType and host are the only required parameters.
         * Although some parameters are needed for specific servertypes,
         * you will be informed if you input incorrect information.
         * 
         * === Supported options ===
         *  - voice_port (Integer Default = 9987)
         *  - resolve_dns (Boolean Default = true) Only used by Minecraft so far.
         *  - timeout (Integear Default = 1 Minecraft server, 10 Teamspeak server)
         * 
         * === URI Examples ===
         *  - Teamspeak3://Username:Password@teamspeak.server.com
         *  - Teamspeak3://Username:Password@teamspeak.server.com:10012/?voice_port=9988
         *  - Minecraft://minecraft.server.com
         *  - Minecraft://minecraft.server.com:25566
         *  - Minecraft://minecraft.server.com/?resolve_dns=false
         * 
         * @param array 
         * @throws Exception
         * 
         */
        public function __construct($uri) {
            self::init($uri);
            self::loadClass($this->serverType);
            self::nullify();
        }

        public function Factory() {
            $ClassToLoad = __NAMESPACE__.'\\'.$this->serverType;

            switch ($this->serverType) {
                case "Teamspeak3":
                    $obj = new $ClassToLoad($this->serverAddress,$this->userName,$this->password,$this->voice_port,$this->serverport,$this->timeout);
                    break;
                case "Minecraft":
                    $obj = new $ClassToLoad($this->serverAddress,$this->serverport,$this->timeout, $this->resolve_dns);
                    break;
                default:
                    throw new \Exception("Could not load the class: ".$this->serverType);
                    break;
            }

            return $obj;
        }

        protected function nullify() {
            foreach ($this as $key => $item) {
                if($item == "") {
                    $this->$key = NULL;
                }
            }
        }

        protected function loadClass($className) {
            if(class_exists($className, FALSE)) {
                return;
            }
            $className = str_replace('\\', '/', $className);
            $file = __DIR__ . '/'.$className. '.class.php';
            if(!file_exists($file) || !is_readable($file)) {
                throw new \Exception("The file $file does not exsist or is note readable");
            }

            if(class_exists($className,FALSE)) {
                throw new \Exception("The class: $className does not exist");
            }

            return include_once($file);

        }
        
        protected function init($uri) {
            $uri = explode(":", strval($uri),2);

            $uri[0] = strtolower($uri[0]);
            if(array_key_exists($uri[0],self::$validServerTypes)) {
                $this->serverType = ucfirst($uri[0]);
                
            } else {
                throw new \Exception("ServerType is invalid. ServerType supplied: $uri[0]");
            }

            $status = preg_match("~(//)((([^:]*):([^@]*))@)?([^:$/]*)(:([^/$\D]*))?(/\?(.*))?~",$uri[1],$matches);
            
            if(!$status) {
                throw new \Exception("URI is invalid. URI supplied: $uri[0]$uri[1]");
            }

            $missingParameters = "";
            foreach (self::$validServerTypes[$uri[0]] as $key => $value) {
                if(!isset($matches[$value]) || $matches[$value] == "") {
                    if($missingParameters == "") {
                        $missingParameters = "Missing follwoing parameters: $key";
                    }
                    else {
                        $missingParameters .= ", $key";
                    }
                }
                else {
                    $this->$key = $matches[$value];
                }
            }

            if($missingParameters != "") {
                throw new \Exception($missingParameters);
            }
            if(isset($matches[8])) {
                if($matches[8] !== "" && is_numeric($matches[8])) {
                    $this->serverport = (int) $matches[8];
                }
            }

            $options = array();

            if(isset($matches[10])) {
                if($matches[10] != "") {
                    $items = explode("&",strtolower(strval($matches[10])));
                    foreach($items as $option) {
                        if($option != "") {
                            $values = explode("=",$option);
                            $options[$values[0]] = $values[1];
                        }
                    }
                }
            }
            foreach($options as $key => $value) {
                if(isset($this->$key)) {
                    if($value == "true" or $value == "false") {
                        $this->$key = (strpos($value, "true") !== FALSE) ? TRUE : FALSE;
                    }
                    else {
                        is_numeric($value) ? $this->$key = (int)$value : $this->$key = trim($value);
                    }
                }
                else {
                    throw new \Exception("Option $key does not exist!");
                }
            }
        }
    }
?>