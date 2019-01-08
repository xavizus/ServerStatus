<?php
namespace ServerTypes;
/**
 * @file
 * @package ServerTypes
 * @author Stephan Ljungros
 */

/**
  * @class Minecraft
  * @brief Provides possibiltiy to connect to a Minecraft server and retrive data.
  */
class Minecraft {

    /**
     * Stores the socket connection
     * @var fsockopen 
     */
    private $fp;

    /**
     * Stores the Server address of the Minecraft server
     * @var string
     */
    private $ServerAddress;

    /**
     * Stores the port to the Minecraft server
     * @var integer
     */
    private $ServerPort;

    /**
     * Stores the timeout value
     * @var integer
     */
    private $Timeout;

    /**
     * Stores the connection status
     * @var boolean
     */
    private $connected = false;

    /**
     * Constructor for creating the connection to the Minecraft server and fetches data.
     * @param string
     * @param integer
     * @param integer
     * @param boolean
     */
    public function __construct ($Address, $port = 25566, $Timeout = 1, $ResolveSRV = true) {
        $this->ServerAddress = $Address;
        $this->ServerPort = (int)$port;
        $this->Timeout = $Timeout;

        //Used to get the port from the SRV.
        if($ResolveSRV) {
            $this->ResolveSRV();
        }

        //Open the connection to the server.
        $this->openConnection();
    }

    /**
     * Opens a socket to the Minecraft server.
     * @throws Exception
     * @return boolean 
     */
    private function openConnection() {
        //If we already are connected, why try to connect again?
        if(!$connected) {
            try {
                //Open connection the the server.
                $this->fp = @fsockopen( $this->ServerAddress, $this->ServerPort, $errno, $errstr, $this->Timeout );

                //Throw an Exception if the connection were not made.
                if(!$this->fp) {
                    throw new \Exception ("$errno: $errstr");
                }
            }
            catch(\Exception $e) {
                echo 'Caught exception: ', $e->getMessage(), "<br/>";
                return false;
            }

            //the fsockopen funciton just timesout while connecting to the socket.
            //stream_set_timeout sets a timeout for the read/write over the socket.
            stream_set_timeout( $this->fp, $this->Timeout );

            //inform the class that we are connected.
            $this->connected = true;

            //Return true that the connection is open.
            return true;
        }
        else {

            //Return true that the connection is open.
            return true;
        }

    }

    /**
     * Closes the socket stream
     * @return void
     */
    private function close(){
        fclose($this->fp);
    }

    /**
     * Sends a query to the Minecraft server and receive the response
     * @throws Exception
     * @return obj  
     */
    public function query() {

        //Make sure that we are connected to the server.
        if(!$this->connected) {
            throw new \Exception ("You are not connected to a server!");
        }

        //Documentation for this function:
        //https://wiki.vg/Protocol#Handshaking

        $data = "\x00"; //Packet ID 0

        $data .= "\x04";  //Protocol version. (As a varInt) More information about Protocol Versions are found at:
                          //https://wiki.vg/Protocol_version_numbers
                          //Using Protocol version from 1.7.2 for backward compability reasons. Ealier versions may not work.

        $data .= pack('c', strlen($this->ServerAddress)); //Pack string lenght to tell the server how long the string we are sending is.

        $data .= $this->ServerAddress; //Adding the string.

        $data .= pack('n', $this->ServerPort); //pack the portnumber as Unsigned Short varint.

        $data .= "\x01"; //Set state 1, for Status.

        //Lastly before we send the package, we need to inform how large our package are along with our data
        $data = pack('c', strlen($data)).$data;

        //Send the message to the connected server:
        fwrite($this->fp,$data);
        
        //The server now expect a ping message with package id 0x01 with a request ID 0x00
        fwrite($this->fp, "\x01\x00");

        //Get the lenght of the whole response.
        $length = $this->readVarInt();

        //Save the response
        $data = fread($this->fp, $length);

        //Close the connection
        $this->close();

        //Remove the 3 first nonsense characters
        $data = substr($data,3);
        
        //Decode the JSON so we can work with the data.
        $data = json_decode($data);

        return $data;
    }
    /**
     * Get's the serverport from the DNS SRV
     * @return null
     */
     private function ResolveSRV() {

        //Make sure that the Server Address is an DNS and not an IP-Address.
        if( ip2long( $this->ServerAddress ) !== false )
		{
			return;
		}
        $dnsRecord = dns_get_record('_minecraft._tcp.'.$this->ServerAddress,DNS_SRV);
        if(empty($dnsRecord)) {
            return;
        }
        if(isset($dnsRecord[0]['port'])) {
            $this->ServerPort = $dnsRecord[0]['port'];
        }
    }

    /**
     * This whole function is copied from Minecraft wiki, with some modifications so it can be used with PHP.
     * I don't fully comprehend this function, thy my comments may be incorrect.
     * https://wiki.vg/Protocol#VarInt_and_VarLong
     * 
     * What it basically does is to read the 7 first bits and check at the 8:th bit if there are any more data incoming.
     * @throws Exception
     * @return byte
     */
    
    private function readVarInt() {
        $numRead = 0;
        $result = 0;
        $read;
        do {
            //Store the byte in the variable Read.
            $read = fgetc($this->fp);

            //Convert the read byte to a value.
            $read = ord($read);

            //Get the 7 first bits from the read value
            $value = ($read & 0x7F);

            //Add the 7 fist bits to result.
            $result |= ($value << (7 * $numRead));
            
            //Add 1 to numRead variable, this for being able to read the next byte.
            $numRead++;

            //If NumRead is larger than 5, that means the VarInt is too large.
            if($numRead > 5) {
                throw new \Exception('VarInt is too big!');
            }

        //Do this while the read value last bit is not equal to 0.
        } while (($read & 0x80) != 0);

        //Return the value, which should be the lenght of the whole response.
        return $result;
    }
}
?>