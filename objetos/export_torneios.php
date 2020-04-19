<?php
class ExportTorneio{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "export_torneios";

    // object properties
    public $id;
    public $nome;
    public $federacao;
    public $num_participantes;
    public $participantes;
    public $genero;

    public function __construct($db){
        $this->conn = $db;
    }


   //ler todos os torneios
   function readAll(){

$query = "SELECT
            ID, Nome, Federacao, Genero, NumParticipantes, Participantes, Sede 
        FROM
            " . $this->table_name . "
        ORDER BY
            Nome DESC";

$stmt = $this->conn->prepare( $query );
$stmt->execute();

return $stmt;
}

function nome($codigo){
  $codigo = htmlspecialchars(strip_tags($codigo));

if($codigo != 0){
  $query = "SELECT Nome FROM " .$this->table_name . " WHERE ID = ?";
  $stmt = $this->conn->prepare($query);
  $stmt->bindParam(1,$codigo);
  $stmt->execute();
  $result = $stmt->fetchColumn();
} else {
  $result = "Outro";
}

  return $result;
}

function salvar($codigo,$federacao,$genero,$num_equipes,$listaTimes,$sede){
  $codigo = htmlspecialchars(strip_tags($codigo));
  $federacao = htmlspecialchars(strip_tags($federacao));
  $genero = htmlspecialchars(strip_tags($genero));
  $num_equipes = htmlspecialchars(strip_tags($num_equipes));
  $sede = htmlspecialchars(strip_tags($sede));

  $listaFinal = implode(',', $listaTimes);

  $query = "UPDATE " .$this->table_name . " SET Federacao = :federacao, NumParticipantes = :numParticipantes, Genero = :genero, Participantes = :listaParticipantes, Sede = :sede WHERE ID = :id";
  $stmt = $this->conn->prepare($query);
  $stmt->bindParam(":federacao", $federacao);
  $stmt->bindParam(":numParticipantes",$num_equipes);
  $stmt->bindParam(":genero",$genero);
  $stmt->bindParam(":listaParticipantes",$listaFinal);
  $stmt->bindParam(":id",$codigo);
  $stmt->bindParam(":sede",$sede);
  if($stmt->execute()){
    return true;
  } else {
    return false;
  }


}

}
?>
