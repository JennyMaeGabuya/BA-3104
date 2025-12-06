<?php 

class Database {
  private $host = "localhost";
  private $user = "root";
  private $pass = "";
  private $db = "parking_db";
  private $port = 3307;
  public $conn;
  
  public function __construct()
  {
    $this->conn = new mysqli($this->host, $this->user ,$this->pass, $this->db, $this->port);
    if($this->conn->connect_error) {
      die("Connection Failed: " . $this->conn->connect_error);
    }
  }

  public function close() {
    $this->conn->close();
  }
}