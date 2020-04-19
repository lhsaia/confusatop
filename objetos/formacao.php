<?php
class Formacao{
 
    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "formacoes";
 
    // object properties
    public $id;
    public $nome;
    public $LE;
    public $LD;
    public $Z;
    public $AE;
    public $AD;
    public $V;
    public $ME;
    public $MD;
    public $MC;
    public $PE;
    public $PD;
    public $MA;
    public $Am;
    public $Aa;

    public function __construct($db){
        $this->conn = $db;
    }
 
    // // criar time
    // function create(){
 
    //     //escrever query
    //     $query = "INSERT INTO
    //                 " . $this->table_name . "
    //             SET
    //                 Nome=:nome, TempVerao=:tempVerao, EstiloVerao=:estiloVerao, TempOutono=:tempOutono, EstiloOutono=:estiloOutono, TempInverno=:tempInverno, EstiloInverno=:estiloInverno, TempPrimavera=:tempPrimavera, EstiloPrimavera=:estiloPrimavera, Hemisferio=:hemisferio, Pais=:pais";
 
    //     $stmt = $this->conn->prepare($query);
 
    //     // posted values
    //     $this->nome=htmlspecialchars(strip_tags($this->nome));
    //     $this->tempVerao=htmlspecialchars(strip_tags($this->tempVerao));
    //     $this->estiloVerao=htmlspecialchars(strip_tags($this->estiloVerao));
    //     $this->tempOutono=htmlspecialchars(strip_tags($this->tempOutono));
    //     $this->estiloOutono=htmlspecialchars(strip_tags($this->estiloOutono));
    //     $this->tempInverno=htmlspecialchars(strip_tags($this->tempInverno));
    //     $this->estiloInverno=htmlspecialchars(strip_tags($this->estiloInverno));
    //     $this->tempPrimavera=htmlspecialchars(strip_tags($this->tempPrimavera));
    //     $this->estiloPrimavera=htmlspecialchars(strip_tags($this->estiloPrimavera));
    //     $this->hemisferio=htmlspecialchars(strip_tags($this->hemisferio));
    //     $this->pais=htmlspecialchars(strip_tags($this->pais));
         
    //     // bind values 
    //     $stmt->bindParam(":nome", $this->nome);
    //     $stmt->bindParam(":tempVerao", $this->tempVerao);
    //     $stmt->bindParam(":estiloVerao", $this->estiloVerao);
    //     $stmt->bindParam(":tempOutono", $this->tempOutono);
    //     $stmt->bindParam(":estiloOutono", $this->estiloOutono);
    //     $stmt->bindParam(":tempInverno", $this->tempInverno);
    //     $stmt->bindParam(":estiloInverno", $this->estiloInverno);
    //     $stmt->bindParam(":tempPrimavera", $this->tempPrimavera);
    //     $stmt->bindParam(":estiloPrimavera", $this->estiloPrimavera);
    //     $stmt->bindParam(":hemisferio", $this->hemisferio);
    //     $stmt->bindParam(":pais", $this->pais);

    //     if($stmt->execute()){
    //         return true;
    //     } else {
    //         return false;
    //     }
 
    // }

    //ler todos os jogadores para o quadro
    function read(){
 
    $query = "SELECT
                *
            FROM
                " . $this->table_name . " a
            ORDER BY
                nome ASC";
 
    $stmt = $this->conn->prepare( $query );
    $stmt->execute();
 
    return $stmt;
    }

    function arrayPosicoes($idFormacao){
        $idFormacao = htmlspecialchars(strip_tags($idFormacao));
        
        $query = "SELECT LD, LE, Z, AD, AE, V, MD, ME, MC, PD, PE, MA, Am, Aa FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$idFormacao);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $arrayPosicoes = array();
        $arrayPosicoes[] = 'G';
        foreach($result as $key => $value){
            while($value > 0){
                $arrayPosicoes[] = $key;
                $value = $value - 1;
            }
        }
        $arrayPosicoes[] = 'G';
        foreach($result as $key => $value){
            if($value > 0){
                $arrayPosicoes[] = $key;
            }
        }
        foreach($result as $key => $value){
            if($value > 1){
                $arrayPosicoes[] = $key;
            }
        }
        foreach($result as $key => $value){
            if($value > 2){
                $arrayPosicoes[] = $key;
            }
        }
        $arrayPosicoes[] = 'G';

        return $arrayPosicoes;
    }

    // used for paging products
    public function countAll($federacao){

    // $federacao = htmlspecialchars(strip_tags($federacao));

    // if($federacao == null){
 
    //     $query = "SELECT id FROM " . $this->table_name . "";

    // } else {

    //     $query =    "SELECT a.id 
    //                 FROM " . $this->table_name . " a
    //                  LEFT JOIN paises p ON a.pais = p.id
    //                   WHERE p.federacao=".$federacao;

    // }
 
    // $stmt = $this->conn->prepare( $query );
    // $stmt->execute();
 
    // $num = $stmt->rowCount();
 
    // return $num;
    }

    //apagar jogador
    // - se o jogador tiver em time ou transferencias, não apaga
    function apagar($idApagar){
        // $idApagar = htmlspecialchars(strip_tags($idApagar));
        // $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        // $stmt = $this->conn->prepare( $query );
        // $stmt->bindParam(1, $idApagar);
        // if($stmt->execute()){
        //     return true;
        // } else {
        //     return false;
        // }
        
    }

    //alterar jogador
    function alterar($idRecebida,$nomeJogadorRec,$nomeAux1Rec,$nomeAux2Rec,$cobradorFaltaRec,$paisRec = 0){

        // $idRecebida = htmlspecialchars(strip_tags($idRecebida));
        // $nomeJogadorRec = htmlspecialchars(strip_tags($nomeJogadorRec));
        // $nomeAux1Rec = htmlspecialchars(strip_tags($nomeAux1Rec));
        // $nomeAux2Rec = htmlspecialchars(strip_tags($nomeAux2Rec));
        // $cobradorFaltaRec = htmlspecialchars(strip_tags($cobradorFaltaRec));
        // $paisRec = htmlspecialchars(strip_tags($paisRec));

        // $query = "UPDATE " . $this->table_name . " SET nomeJogador = ?, nascimento = ?, mentalidade = ?, cobradorFalta = ?, pais = ? WHERE id = ?";
        // $stmt = $this->conn->prepare( $query );

        // $stmt->bindParam(1, $nomeJogadorRec);
        // $stmt->bindParam(2, $nomeAux1Rec);
        // $stmt->bindParam(3, $nomeAux2Rec);
        // $stmt->bindParam(4, $cobradorFaltaRec);
        // $stmt->bindParam(5, $paisRec);
        // $stmt->bindParam(6, $idRecebida);

        // if($stmt->execute()){
        //     return true;
        // } else {
        //     return false;
        // }
        
    }

    // //verificar se já existe
    // function verificar(){
        
    //     $query = "SELECT count(ID) as total FROM clima WHERE Nome = ? AND Pais = ?";
    //     $stmt = $this->conn->prepare($query);
    //     $stmt->bindParam(1,$this->nome);
    //     $stmt->bindParam(2,$this->pais);
    //     $stmt->execute();
    //     $row = $stmt->fetch(PDO::FETCH_ASSOC);
    //     $total = $row['total'];
        
    //     return $total;
    // }

    // function codigoPorNomeEPais(){

    //     $query = "SELECT ID FROM clima WHERE Nome = ? AND Pais = ? LIMIT 0,1";
    //     $stmt = $this->conn->prepare($query);
    //     $stmt->bindParam(1,$this->nome);
    //     $stmt->bindParam(2,$this->pais);
    //     $stmt->execute();
    //     $row = $stmt->fetch(PDO::FETCH_ASSOC);
    //     $ID = $row['ID'];
        
    //     return $ID;
    // }

    // function exportacao($idPais){

    //     $idPais = htmlspecialchars(strip_tags($idPais));

    //     $query = "SELECT c.ID as idClima, c.Nome as nomeClima, c.TempVerao, c.EstiloVerao, c.TempOutono, c.EstiloOutono, c.TempInverno, c.EstiloInverno, c.TempPrimavera, c.EstiloPrimavera, c.Hemisferio FROM clima c WHERE c.Pais=:pais";
    //     $stmt = $this->conn->prepare( $query );
    //     $stmt->bindParam(":pais", $idPais);
    //     $stmt->execute();

    //     return $stmt; 

    // }
}
?>