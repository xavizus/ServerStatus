<?php
namespace ServerTypes;
/**
 * @file
 * @package ServerTypes
 * @author Stephan Ljungros
 */

 /**
  * @class Teamspeak3
  * @brief Provides possibiltiy to connect to a Teamspeak 3 server and retrive data.
  */
class Teamspeak3 {

    /**
     * Stores the escape patterns for some text that the Teamspeak 3 server may return
     * 
     * @var array
     */
    private $escape_patterns = array(
        "\\" => "\\\\", // backslash
        "/"  => "\\/",  // slash
        " "  => "\\s",  // whitespace
        "|"  => "\\p",  // pipe
        ";"  => "\\;",  // semicolon
        "\a" => "\\a",  // bell
        "\b" => "\\b",  // backspace
        "\f" => "\\f",  // formfeed
        "\n" => "\\n",  // newline
        "\r" => "\\r",  // carriage return
        "\t" => "\\t",  // horizontal tab
        "\v" => "\\v"   // vertical tab
      );

      /**
       * Stores the connection stream to the Teamspeak 3 server
       * @var stream_socket_client
       */
    private $fp;
    /**
     * Stores the adress to the Teamspeak 3 server
     * @var string
     */
    private $ServerAddress;

    /**
     * Stores the portnumber to the Teamspeak 3 server
     * @var integer
     */
    private $ServerPort;
    /**
     * Stores the timeout
     * @var integer
     */
    private $Timeout;
    /**
     * Stores the username for conneciton to the Teamspeak 3 server
     * @var string
     */
    private $Username;
    /**
     * Stores the password for the connetion to the Teamspeak 3 server
     * @var string
     */
    private $Password;
    /**
     * Stores the port for the virtual server that's used to get virtualhost ID to connect to the Teamspeak 3 server
     * @var integer
     */
    private $voicePort;
    /**
     * Stores the connection status to the Teamspeak 3 queryserver
     * @var boolean
     */
    private $Connected = FALSE;
    /**
     * Stores the recived data from the Teamspeak 3 queryserver
     * @var object if the connection is sucessfull
     * @var array if the connection was not sucessfull
     */
    private $data;

    private $tries = 0;

    public function __construct($ServerAddress, $Username, $Password, $voicePort = 9987, $Port = 10011, $Timeout = 10) {
        $this->voicePort = (int)$voicePort;
        $this->Username = $Username;
        $this->Password = $Password;
        $this->ServerAddress = $ServerAddress;
        $this->ServerPort = (int)$Port;
        $this->Timeout = (int)$Timeout;
        $this->Query();
    }

    public function __get($arg) {
        if($arg == "help") {
            foreach($this->data as $key => $value) {
                $html .= "$key => $value </br>";
            }
            return $html;
        }
        else {
            return array_key_exists($arg,$this->data) ? $this->data->$arg : null;

        }    
    }

    public function convertByte(int $data) {
        return sprintf("%.04f", $data/pow(1024,3))." GB";
    }

    public function convertTime(int $data) {
        $months = floor($data / (60*60*24*30));
        $days = floor($data /(60*60*24));
        $hours = floor(($data / 60 / 60) %24);
        $minutes = floor(($data/60)%60);
        $seconds = $data %60;
        return sprintf("%02d MM %02d DD %02d:%02d:%02d", $months, $days, $hours,$minutes,$seconds);
    }
    private function closeConnection() {
        if($this->fp) {
            fclose($this->fp);
        }
    }

    private function openConnection() {
        if(!$this->Connected) {
            try {
                $Address = "tcp://".$this->ServerAddress.":".$this->ServerPort;
                $options = array();
                $this->fp = @stream_socket_client($Address,$errno,$errstr,$this->Timeout, STREAM_CLIENT_CONNECT, stream_context_create($options));
                if($this->fp === FALSE) {
                    throw new \Exception ("$errno: ".utf8_encode($errstr));
                }
                $this->Connected = TRUE;
            }

            catch(\Exception $e) {
                $this->Connected = FALSE;
                return $e;
            }

            stream_set_timeout( $this->fp, $this->Timeout );
            stream_set_blocking($this->fp,1);
        }
    }
    
    private function Query($length = 4096) {
    $return = $this->openConnection();
    if(!$this->Connected) {
        throw new \Exception ($return->getMessage());
    }
    
   

    $commandsToRun = array ("login  $this->Username $this->Password", "use port=$this->voicePort", "serverinfo");
    $data = $this->execute($commandsToRun);
    $this->data = $this->toObject($data);

    if(isset($this->data->id)) {
            if(isset($this->data->extra_msg)){
                throw new \Exception ("Error id: ". $this->data->id.". Error Message: ".$this->data->msg.". Extra message: ".$this->data->extra_msg);
            }
            else {
                throw new \Exception ("Error id: ". $this->data->id.". Error Message: ".$this->data->msg);
            }
            
        }
    }

    private function toObject(array $array) {
        $obj = new \stdClass();
        foreach ($array as $key => $value) {
            $obj->$key = $value;
            //$array[$key] = (object) $array[$key];
        }
        return $obj;
    }

    private function execute(array $array) {
        foreach ($array as $cmd) {
            $data = $this->request($cmd);

            if (isset($data['id'])) {
                return $data;
            }
        }
        $this->closeConnection();
        return $data;
    }

    private function formatData($data) {
        $err = $this->requestError(array_pop($data));

        if($err) {
            return $err;
        }
        foreach($data as $key => $val) {
            if((substr($val,0,strlen("Welcome")) === "Welcome") ) {
                unset($data[$key]);
            }
        }

        $table = array();
        
        foreach($data as $items) {
            $pairs = explode(" ",$items);
            $table[] = $pairs;
        }

        $array = array();

        for($i = 0; $i < count($table);$i++) {
            foreach($table[$i] as $pair) {
                list($ident, $value) = explode("=",$pair,2);
                $array[$i][strtr($ident, array_flip($this->escape_patterns))] = (is_numeric($value) && (strpos($value,".") === FALSE) && (strpos($value, "x")===FALSE)) ? intval($value) : strval(strtr($value, array_flip($this->escape_patterns)));
            }
        }

        $array = array_shift($array);

        return $array;
    }

    private function requestError($data) {
        $data = $this->section($data," ", 1,3);

        foreach(explode(" ", $data) as $pair) {
            list($ident, $value) = explode("=",$pair,2);
            $err[strval($ident)] = is_numeric($value) ? intval($value) : strval(strtr($value, array_flip($this->escape_patterns)));
        }
        
        if((array_key_exists("id",$err))? $err['id'] : 0x00 != 0x00) {
            return $err;
        }
        else {
            return FALSE;
        }
    }

    private function request($cmd) {

        $this->sendPackage($cmd);

        $rpl = array();
        do {
            $str = $this->getContent();
            $rpl[] = $str;
        } while(((strpos($str,"error")) === FALSE) && ($this->Connected));

        $data = $this->formatData($rpl);

        return $data;
    }

    private function getContent($endOfLine = "\n") {
        $string = "";
        while(!(substr($string,strlen($endOfLine)*-1) == $endOfLine) && ($this->Connected)) {
            $data = @fgets($this->fp, 4096);
            if($data === FALSE) {
                if(strlen($string)) {
                    $string .= strval($endOfLine);
                }
                else {
                    error_log("Connection to server lost");
                    $this->Connected = FALSE;
                    break;
                }
            }
            else {
                $string .= strval($data);
            }
        }
        return trim($string);
    }

    private function sendPackage($data) {
        $sizeOf = strlen($data);
        $maxSize = 4096;
        
        for ($i = 0; $i < $maxSize;) {
            $Remaining = $sizeOf - $i;
            if($Remaining < $maxSize) {
                $maxSize = $Remaining;
            }
            else {
                $maxSize = $maxSize;
            }
            $buffer = substr($data, $i, $maxSize);
            $i = $i+$maxSize;
            if($i >= $size) {
                $buffer .= "\n";
            }

            @fwrite($this->fp,$buffer);
        }
    }

    private function section($string, $separator, $first = 0, $last = 0) {
        $sections = explode($separator, $string);

        $total = count($sections);
        $first = intval($first);
        $last  = intval($last);
    
        if($first > $total) return null;
        if($first > $last) $last = $first;
    
        for($i = 0; $i < $total; $i++)
        {
          if($i < $first || $i > $last)
          {
            unset($sections[$i]);
          }
        }
    
        $string = implode($separator, $sections);

        return $string;
    }
    
}
?>