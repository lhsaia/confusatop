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
 

    //selecionar coeficiente
    function selFederacao($idTime){

        $idTime = htmlspecialchars(strip_tags($idTime));
 
    $query = "SELECT
                nome
            FROM
                " . $this->table_name . "
            WHERE
                id = " . $idTime;
 
    $stmt = $this->conn->prepare( $query );
    $stmt->execute();
 
    return $stmt;
    }
    
}
?>
