<?php

class Database{
  
    // credenciais do banco de dados
    private $host = "mysql1004.mochahost.com";
    private $db_name = "lhsaia_colegioseleitorais";
    private $username = "lhsaia_fabio";
    private $password = "jogproof";
    public $conn;
  
    // estabelecer conexão
    public function getConnection(){
  
        $this->conn = null;
  
        try{
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", $this->username, $this->password);
            
            //verificar se os dois itens abaixo não vão gerar problemas.
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
        }
  
        return $this->conn;
    }
}
?>