<?php
class Competicao{
 
    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "campeonatos";
 
    // object properties
    public $id;
    public $nome;
    public $coeficiente;
 
    public function __construct($db){
        $this->conn = $db;
    }
 

    //selecionar coeficiente
    function selCoeficiente($idJogo){

        $idJogo = htmlspecialchars(strip_tags($idJogo));
 
    $query = "SELECT
                coeficiente
            FROM
                " . $this->table_name . "
            WHERE
                id = " . $idJogo;
 
    $stmt = $this->conn->prepare( $query );
    $stmt->execute();
 
    return $stmt;
    }

    //determinarHomeAway
   function detCasaFora($idJogo){

    $idJogo = htmlspecialchars(strip_tags($idJogo));
       
       if($idJogo == 3 or $idJogo == 4 or $idJogo == 5 or $idJogo ==  10 or $idJogo ==  11){
           
           $casaFora = 100;
       } else {
           $casaFora = 0;
       }
       
       return $casaFora;
       
   }
    
    // used by select drop-down list
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
    
    // used to read category name by its ID
    function readName(){
     
    $query = "SELECT nome FROM " . $this->table_name . " WHERE id = ? limit 0,1";
 
    $stmt = $this->conn->prepare( $query );
    $stmt->bindParam(1, $this->id);
    $stmt->execute();
 
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
     
    $this->name = $row['nome'];
    }
}
?>
