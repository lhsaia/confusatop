<?php
class Paroquia{
 
    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "paroquias";
 
    // object properties
    public $id;
    public $nome;
    public $cidade;
public $estado;
public $localizacao;
public $colegio;
public $endComposto;
public $eleitores;


    public function __construct($db){
        $this->conn = $db;
    }


    function listarParoquias(){

        $query = "SELECT p.id, p.nomeParoquia, c.localizacao as centro  FROM paroquias p LEFT JOIN colegios c ON c.id = p.colegio WHERE c.localizacao <> 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;


    }

    function atualizarLocalizacao(){

        $this->localizacao = htmlspecialchars(strip_tags($this->localizacao));
        $this->endComposto = htmlspecialchars(strip_tags($this->endComposto));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $query = "UPDATE paroquias SET localizacao = ?, enderecoComposto = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$this->localizacao);
        $stmt->bindParam(2,$this->endComposto);
        $stmt->bindParam(3,$this->id);
        if($stmt->execute()){
            return true;
        } else {
            return false;
        }
    }
}
?>