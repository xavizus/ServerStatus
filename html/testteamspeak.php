<?php
namespace ServerStatus;

class Teamspeak3 {
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

    private $fp;
    private $ServerAddress;
    private $ServerPort;
    private $Timeout;
    private $Username;
    private $Password;
    private $voicePort;
    private $Connected = FALSE;

    public function __construct($ServerAddress, $Username, $Password, $voicePort = 9987, $Port = 10011, $Timeout = 10) {
        $this->voicePort = (int)$voicePort;
        $this->Username = $Username;
        $this->Password = $Password;
        $this->ServerAddress = $ServerAddress;
        $this->ServerPort = (int)$Port;
        $this->Timeout = (int)$Timeout;
    }

    private function closeConnection() {
        fclose($this->fp);
    }

    public function openConnection() {
        if(!$this->Connected) {
            try {
                $Address = "tcp://".$this->ServerAddress.":".$this->ServerPort;
                $options = array();
                $this->fp = @stream_socket_client($Address,$errno,$errstr,10, STREAM_CLIENT_CONNECT, stream_context_create($options));
                if($this->fp === FALSE) {
                    throw new \Exception ("$errno: $errstr");
                }
            }

            catch(\Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "<br/>";
                return FALSE;
            }

            stream_set_timeout( $this->fp, $this->Timeout );
            stream_set_blocking($this->fp,1);
        }
    }
    

    public function Query($length = 4096) {
    $this->openConnection();

    $toSend = "login  $this->Username $this->Password";
    $this->request($toSend);
 
    $toSend = "use port=$this->voicePort";
    $data = $this->request($toSend);

    $toSend = "serverinfo";
    $data = $this->request($toSend);
    //var_dump($data);
    $this->formatData($data);
    }

    private function formatData($data) {
        array_pop($data);
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
        print_r($array);
        echo "<br/>";

        print_r($array[0]['virtualserver_maxclients']);
    }

    private function request($cmd) {

        $this->sendPackage($cmd);

        $rpl = array();
        do {
            $str = $this->getContent();
            $rpl[] = $str;
        } while((strpos($str,"error")) === FALSE);

        return $rpl;
    }

    private function getContent($endOfLine = "\n") {
        $string = "";
        while(!(substr($string,strlen($endOfLine)*-1) == $endOfLine)) {
            $data = @fgets($this->fp, 4096);
            if($data === FALSE) {
                if(strlen($string)) {
                    $string .= strval($endOfLine);
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
    
}

$Username =       "Xavizus";   // Login
$Password =    "UiiyhKme";  // Password
$voicePort = 9988;

$ts3 = new Teamspeak3("192.168.2.38",$Username,$Password,$voicePort);
$ts3->openConnection();
$ts3->Query();
?>