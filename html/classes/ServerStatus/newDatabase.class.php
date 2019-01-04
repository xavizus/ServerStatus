<?php

namespace ServerStatus;

class DatabaseFactory 
{
    protected $provider = NULL;
    protected $connection = NULL;

    public function __construct(callable $provider) {
        $this->provider = $provider;
    } 

    public function create ($name) {
        if ($this->connection === NULL) {
            $this->connection = call_user_func($this->provider);
        }
        return new $name ($this->connection);
    }

    public function __destruct() {
        $this->connection = NULL;
    }
}
?>