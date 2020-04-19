<?php
class Colegio{
 
    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "colegios";
 
    // object properties
    public $id;
    public $nome;
    public $cidade;
public $estado;
public $localizacao;
public $endComposto;


    public function __construct($db){
        $this->conn = $db;
    }


    function atualizarCidades(){

        $query = "UPDATE colegios c INNER JOIN cidades d ON c.nomeColegio = d.nomeOriginal SET c.cidade = d.id";
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }

    function listarColegios(){

        $query = "SELECT c.id, d.nomeAtual as cidade FROM colegios c LEFT JOIN cidades d ON c.cidade = d.id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;


    }

    function atualizarLocalizacao(){

        $this->localizacao = htmlspecialchars(strip_tags($this->localizacao));
        $this->endComposto = htmlspecialchars(strip_tags($this->endComposto));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $query = "UPDATE colegios SET localizacao = ?, enderecoComposto = ? WHERE id = ?";
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


