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

    private $serverType = "Teamspeak3";

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

    private $isOnline = FALSE;
    /**
     * Stores the recived data from the Teamspeak 3 queryserver
     * @var object if the connection is sucessfull
     * @var array if the connection was not sucessfull
     */
    private $data;

    public $err;

    /**
     * Constructer for fetching data from a Teamspeak 3 server by the serverquery.
     * 
     * @param string $ServerAddress
     * @param string $Username
     * @param string $Password
     * @param integer $voicePort Default 9987
     * @param integer $ServerQueryPort Default 10011
     * @param integer $Timeout Default 10
     * @return Teamspeak3
     */

    public function __construct($ServerAddress, $Username, $Password, $voicePort = NULL, $ServerQueryPort = NULL, $Timeout = NULL) {
        if($voicePort == NULL) {
            $voicePort = 9987;
        }
        if($ServerQueryPort == NULL) {
            $ServerQueryPort = 10011;
        }
        if($Timeout == NULL) {
            $Timeout = 10;
        }

        if($ServerAddress == NULL || $Username == NULL || $Password == NULL) {
            throw new \Exception("You supplied wrong Serveraddress, Username or Password");
        }
        $this->voicePort = (int)$voicePort;
        $this->Username = $Username;
        $this->Password = $Password;
        $this->ServerAddress = $ServerAddress;
        $this->ServerPort = (int)$ServerQueryPort;
        $this->Timeout = (int)$Timeout;
        
        $this->isOnline = $this->Query();
    }

    /**
     * Gets unknown argument and try to find something that matches.
     * @param string $arg 
     * @return string html-tag if $arg == 'help'
     * @return string $this->data->$arg Value
     * @return null if nothing is found.
     */
    public function __get($arg) {
        $html = '';
        if($arg == "help") {
            if($this->isOnline) {
                foreach($this->data as $key => $value) {
                    $html .= "$key => $value </br>";
                }
            }
            else {
                $html .= "isOnline => Is the server online </br>";
            }
            return $html;
        }
        else {
            if(array_key_exists($arg,$this->data)) {
                return $this->data->$arg;
            }elseif(isset($this->$arg)) {
                return $this->$arg;
            }
            else {
                return NULL;
            }
        }    
    }

    /**
     * Converts Bytes to Gigabyte with 4 decimal precision.
     * @param integer $data
     * @return string Exemple return 1.2345 GB
     */
    public function convertByte(int $data) {
        return sprintf("%.04f", $data/pow(1024,3))." GB";
    }

    /**
     *  Converts seconds to a a  MM DD HH:mm::ss format.
     *  @param integer $data
     *  @return string MM DD HH:mm::ss
     */
    public function convertTime(int $data) {
        $months = floor($data / (60*60*24*30));
        $days = floor($data /(60*60*24));
        $hours = floor(($data / 60 / 60) %24);
        $minutes = floor(($data/60)%60);
        $seconds = $data %60;
        return sprintf("%02d MM %02d DD %02d:%02d:%02d", $months, $days, $hours,$minutes,$seconds);
    }

    /**
     * Closes network stream if it is not closed
     *  @return void;
     */
    private function closeConnection() {
        if($this->fp) {
            fclose($this->fp);
        }
    }

    /**
     * Open the connection to the server.
     * @throws LogicException
     * @return Exception if an error occur
     * @return void
     */
    private function openConnection() {
        if(!$this->Connected) {
            try {
                $Address = "tcp://".$this->ServerAddress.":".$this->ServerPort;
                $options = array();
                $this->fp = @stream_socket_client($Address,$errno,$errstr,$this->Timeout, STREAM_CLIENT_CONNECT, stream_context_create($options));
                if($this->fp === FALSE) {
                    throw new \Exception ("Line: ".__LINE__." Err: $errno: ".utf8_encode($errstr));
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
    
    /**
     * Prepears the query that should be run and open the connection to the server.
     * @throws Exception
     */
    private function Query() {
        $return = $this->openConnection();
        if(!$this->Connected) {
            $this->err = $return->getMessage();
            return FALSE;;
        }

        //This array contains the commands to run.
        $commandsToRun = array ("login  $this->Username $this->Password", "use port=$this->voicePort", "serverinfo");
        
        //Executes the commands.
        $data = $this->execute($commandsToRun);

        //converts the data recived to an object.
        $this->data = $this->toObject($data);

        //if we got an id match, something unexpected occured
        if(isset($this->data->id)) {
            if(isset($this->data->extra_msg)){
                throw new \Exception ("Error id: ". $this->data->id.". Error Message: ".$this->data->msg.". Extra message: ".$this->data->extra_msg);
            }
            else {
                throw new \Exception ("Error id: ". $this->data->id.". Error Message: ".$this->data->msg);
            }
            
        }

        return TRUE;
    }

    /**
     *  Converts an array to an object.
     * @param array
     * @return object
     */
    private function toObject(array $array) {
        $obj = new \stdClass();
        $obj->isOnline = 'Returns if the server is online or not';
        foreach ($array as $key => $value) {
            $obj->$key = $value;
        }
        return $obj;
    }

    /**
     * Unpack the array of commands and make a request.
     * @param array
     * @return array
     */
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

    /**
     * This format the data that we recive to make it easier to handle it in the PHP-script.
     * @param array
     * @return array
     */
    private function formatData($data) {

        //Error messages are always at the end of the array.
        $err = $this->requestError(array_pop($data));

        //If we recived error, then there are no idea to process any more data.
        if($err) {
            return $err;
        }

        //unset the welcome message. We are not intreasted that we get welcomed.
        foreach($data as $key => $val) {
            if((substr($val,0,strlen("Welcome")) === "Welcome") ) {
                unset($data[$key]);
            }
        }

        $table = array();
        
        //seperates all configurations
        foreach($data as $items) {
            $pairs = explode(" ",$items);
            $table[] = $pairs;
        }

        $array = array();

        //seperates $keys and $values and store then as an array.
        for($i = 0; $i < count($table);$i++) {
            foreach($table[$i] as $pair) {
                
                list($ident, $value) = array_pad(explode("=",$pair),2,null);

                //This long line does mutliple things.
                //Fist: Store the $key as multidimensional array and make sure to replease all escape characters from Teamspeak 3 query to normal characters.
                //Second: Store the value to said $key, and store this value as a int if possible else as an string and replace all escape characters.
                $array[$i][strtr($ident, array_flip($this->escape_patterns))] = (is_numeric($value) && (strpos($value,".") === FALSE) &&
                (strpos($value, "x")===FALSE)) ? intval($value) : strval(strtr($value, array_flip($this->escape_patterns)));
            }
        }
        //Removes makes the multidimensional array to a normal array.
        $array = array_shift($array);

        return $array;
    }

    /**
     * requestError checks if the array is an actually error or just an confirm that the request is executed.
     * @param array 
     * @return array Returns the error id and message if the request encountered an error 
     * @return boolean false if there is no problems.
     */
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

    /**
     * Request sends the request package and receive the whole response message
     * @param string
     * @return array
     */
    private function request($cmd) {

        //Send our message
        $this->sendPackage($cmd);

        $rpl = array();

        //get all lines of message until we encounter "error" string
        do {
            //Read line
            $str = $this->getContent();
            $rpl[] = $str;
        } while(((strpos($str,"error")) === FALSE) && ($this->Connected));

        //format our data
        $data = $this->formatData($rpl);

        return $data;
    }

    /**
     * Reads the content of the stream until next line character is encountered 
     * @param string
     * @return string
     */
    private function getContent($endOfLine = "\n") {
        $string = "";

        //While the pattern of $endOfLine is not encountered and we got connection
        while(!(substr($string,strlen($endOfLine)*-1) == $endOfLine) && ($this->Connected)) {
            //read 4096 bytes of data from the stream
            $data = @fgets($this->fp, 4096);
            
            //if the stream is empty.
            if($data === FALSE) {
                //check if we got any data already
                if(strlen($string)) {
                    //if we got data already, add our pattern manually.
                    $string .= strval($endOfLine);
                }
                else {
                    //if we didn't get any data at all, that means we lost the connection.
                    error_log("Connection to server lost");
                    $this->Connected = FALSE;
                    break;
                }
            }
            else {
                //If we don't encounter any problems, add the data from the stream to the string.
                $string .= strval($data);
            }
        }
        //Remove all white space from the begining and at the end.
        return trim($string);
    }

    /**
     * Sends our package to the server
     * @param string 
     * @return void
     */
    private function sendPackage($data) {
        //get the length of our package.
        $sizeOf = strlen($data);

        //Our max size we can send per package.
        $maxSize = 4096;
        
        //while $i is less than our maxSize variable
        for ($i = 0; $i < $maxSize;) {
            //set the remaining of lenght of the variable.
            $Remaining = $sizeOf - $i;

            //if the remaining data is less than our maxSize to send,
            if($Remaining < $maxSize) {
                //set our maxSize to the remaining size to send
                $maxSize = $Remaining;
            }
            else {
                //set our maxSize as our maxSize.
                $maxSize = $maxSize;
            }

            //prepare our buffer of data to send.
            $buffer = substr($data, $i, $maxSize);
            
            //increase our $i (so we can exit our for loop).
            $i .= $maxSize;

            //if $i is larger or equal to maxSize, then we need to add an end of line in our buffer.
            if($i >= $maxSize) {
                $buffer .= "\n";
            }

            //write our buffer message.
            @fwrite($this->fp,$buffer);
        }
    }

    /**
     * Section can remove specific section of an array by a seperator and positions.
     * @param string the string you want to remove sections of
     * @param string the seperator
     * @param integer remove everything before this value
     * @param integer remove everything after this value
     */
    private function section($string, $separator, $first = 0, $last = 0) {

        //seperate the string with the separator.
        $sections = explode($separator, $string);

        //count the number of keys there are in the array we just separated.
        $total = count($sections);
        
        //from which array key should be kept  
        $first = intval($first);

        //the last array key should be kept
        $last  = intval($last);
    
        //if the first value is larger than the total, then there is notning to return
        if($first > $total) return null;

        //if first value is larger than last value, then set last value as first value.
        if($first > $last) $last = $first;
    
        //go through every key in the array
        for($i = 0; $i < $total; $i++)
        {
            //if the array position is less than the first key to keep, or if the array postion is larger than the last key to be keep
          if($i < $first || $i > $last)
          {
              //unset (remove) the key and value.
            unset($sections[$i]);
          }
        }
        //make the array to a string again with the assigned seperator.
        $string = implode($separator, $sections);

        return $string;
    }
    
}
?>