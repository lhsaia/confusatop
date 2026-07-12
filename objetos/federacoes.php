<?php
class Federacao{
 
    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "federacoes";
 
    // object properties
    public $id;
    public $nome;
 
    public function __construct($db){
        $this->conn = $db;
    }
 

    function selFederacao($idTime){

        $idTime = htmlspecialchars(strip_tags($idTime));
        if (!is_numeric($idTime)) {
            $idTime = 0;
        }

        $query = "SELECT
                    nome
                FROM
                    " . $this->table_name . "
                WHERE
                    id = :idTime";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(':idTime', $idTime, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }
	
	function read(){

        //select all data
        $query = "SELECT
                    id, nome
                FROM
                    " . $this->table_name . " 
                ORDER BY
                    nome";

        $stmt = $this->conn->prepare( $query );

        $stmt->execute();

        return $stmt;
    }
    
}
?>
