<?php
    namespace ServerType;

    class serverTypeFactory {

        protected $serverType = NULL;

        protected $serverAddress = NULL;

        protected $userName = NULL;

        protected $password = NULL;

        protected $voicePort = NULL;

        protected $serverPort = NULL;

        protected $timeOut = NULL;

        protected $resolveDNS = NULL;

        protected static $validServerTypes = array (
            'minecraft' => array(
                'serverAddress'
            ),
            'teamspeak3' => array(
                'serverAddress',
                'userName',
                'password'
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
        }
        
        /**
         * matches[4] = Username
         * matches[5] = Password
         * matches[6] = serverAddress
         * matches[8] = Portnumber
         * matches[10] = Options
         */
        protected function init($uri) {
            $uri = explode(":", strtolower(strval($uri)),2);

            if(array_key_exists($uri[0],self::$validServerTypes)) {
                $this->serverType = $uri[0];
            } else {
                throw new \Exception("ServerType is invalid. ServerType supplied: $uri[0]");
            }

            $status = preg_match("~(//)((([^:]*):([^@]*))@)?([^:$/]*)(:([^/$\D]*))?(/\?(.*))?~",$uri[1],$matches);
            if(!$status) {
                throw new \Exception("URI is invalid. URI supplied: $uri[0]$uri[1]");
            }


        }
    }

    try {
        $test = new serverTypeFactory("Teamspeak3://username:xavizus:teamspeak.xavizus.com");
        //$test = new serverTypeFactory("Teamspeak3://UserName:Password@teamspeak.xavizus.com:10011/?voice_port=9988");
        
    }
    catch(\Exception $e) {
        echo $e->getMessage();
    }
    
?>