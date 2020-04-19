<?php

class OctamotorDatabase{

    // credenciais do banco de dados
    private $host = "mysql1004.mochahost.com";
    private $db_name = "lhsaia_octamotor";
    private $username = "lhsaia_octamotor";
    private $password = "xZIVsc#+T;pD";
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
            echo "Erro de conexão: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>
