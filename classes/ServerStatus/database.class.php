<?php

namespace ServerStatus;

class database {
    private $conn = NULL;

    public function __construct($config) {
        $this->conn = new \mysqli($config->Serverdatabase,$config->Username,$config->Password, $config->DatabaseName, $config->Port);

        if($this->conn->connect_error){
            die("Connection failed: ".$this->conn->connec_error);
        }
    }


    public function getEnums_array($table, $field) {    
        $query = "SHOW FIELDS FROM `{$table}` LIKE '{$field}'";
        $result = $db->query($sql);
        $row = $result->fetchRow();
        preg_match('#^enum\((.*?)\)$#ism', $row['Type'], $matches);
        $enum = str_getcsv($matches[1], ",", "'");
        return $enum;
    }

    public function insertServer(array $data) {
        /*
        The array for MC should look like:
        (
            ['ServerType'] => "MC"
            ['ServerName'] => "minecraft5.xavizus.com",
            ['Username']    => NULL,
            ['Password']    => NULL,
        )
        */
    }

    public function getServerPassword($serverName,$key, $iv){
        $string = "SELECT Password FROM servers WHERE ServerName='".$serverName."'";

        $data = $this->execute($string)->fetch_assoc();

        
        return openssl_decrypt($data["Password"],'aes-256-cbc',$key,OPENSSL_RAW_DATA,$iv);
    }


    public function updateServerPassword($pass, $serverName, $key, $iv){

        $data = openssl_encrypt($pass, 'aes-256-cbc',$key,OPENSSL_RAW_DATA,$iv);
        $string = 'UPDATE servers SET Password ="' . $data . '" WHERE ServerName="'.$serverName.'"';
        
        if(!$this->execute($string)) {
            echo $this->execute($string);
        }
    }

    /**
     *  @param string Set they type of database querey you want to do, UPDATE, SELECT, INSERT, CREATE
     */
    public function customExecute(string $type, $columnName, string $tableName, string $where = NULL){
        try {
            $allowedTypes = array("UPDATE","SELECT","INSERT","CREATE");
            if(!(in_array(strtoupper($type),$allowedTypes))){
                throw new \Exception("Not allowed database querey is used, you used: ". $type);
            }

            $string = $type;
            if(is_array($columnName)) {
                $count = count($columnName);
                $string .=" ". $columnName[0];
                for($i = 1; $i < $count ; $i++) {
                    $string .=', '. $columnName[$i];
                }
            }
            else if(is_string($columnName)) {
                $string .=" $columnName";
            }
            else {
                throw new \Exception("Invalid variable type used as \$columneName, you used: ". $gettype($columnName) );
            }

            $string .= " FROM";

            if(is_string($tableName)) {
                $string .=" $tableName";
            }
            else {
                throw new \Exception("Invalid variable type used as \$tableName, you used: ". $gettype($tableName) );
            }

            if(isset($where)){
                $string .= " WHERE $where";
            }

            $result = $this->execute($string);

            if(!$result){
                throw new \Exception($this->conn->error);
            }
            return $result;

        }

        catch(\Exception $e) {
            die("Message: " .$e->getMessage());
        }
    }

    private function execute($sql) {
        
        if($this->conn->query($sql)){
            return $this->conn->query($sql);
        }
        else {
            return false;
        }

    }
}

?>