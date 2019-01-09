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
         */
        public function __construct($uri) {
            self::init($uri);
        }

        protected static function init($uri) {
            
        }

    }


    $testServertype;
?>