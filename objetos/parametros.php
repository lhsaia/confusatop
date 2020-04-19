<?php
class Parametro{
 
    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "parametrosHYMT";
 
    // object properties
    public $id;
    public $nome;
    public $dono;
    public $gols;
    public $faltas;
    public $impedimentos;
    public $cartoes;
    public $estilo;
    public $selecionado;
    public $paisPadrao;
    public $exibirBandeiras;

    public function __construct($db){
        $this->conn = $db;
    }
 
    // criar time
    function inserir(){

        // posted values
        $this->nome=htmlspecialchars(strip_tags($this->nome));
        $this->dono=htmlspecialchars(strip_tags($this->dono));
        $this->gols=htmlspecialchars(strip_tags($this->gols));
        $this->faltas=htmlspecialchars(strip_tags($this->faltas));
        $this->impedimentos=htmlspecialchars(strip_tags($this->impedimentos));
        $this->cartoes=htmlspecialchars(strip_tags($this->cartoes));
        $this->estilo=htmlspecialchars(strip_tags($this->estilo));
        $this->selecionado=htmlspecialchars(strip_tags($this->selecionado));
        $this->paisPadrao=htmlspecialchars(strip_tags($this->paisPadrao));
        $this->exibirBandeiras=htmlspecialchars(strip_tags($this->exibirBandeiras));

        if($this->selecionado == 1){
            $prequery = "UPDATE " . $this->table_name . " SET Selecionado=0 WHERE Dono=:dono";
            $stmt = $this->conn->prepare($prequery);
            $stmt->bindParam(":dono", $this->dono);
            $stmt->execute();
        }
 
        //escrever query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    Nome=:nome, Dono=:dono, Gols=:gols, Faltas=:faltas, Impedimentos=:impedimentos, Cartoes=:cartoes, Estilo=:estilo, Selecionado=:selecionado, PaisPadrao=:paisPadrao, ExibirBandeiras=:exibirBandeiras";
 
        $stmt = $this->conn->prepare($query);
 
        // bind values 
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":dono", $this->dono);
        $stmt->bindParam(":gols", $this->gols);
        $stmt->bindParam(":faltas", $this->faltas);
        $stmt->bindParam(":impedimentos", $this->impedimentos);
        $stmt->bindParam(":cartoes", $this->cartoes);
        $stmt->bindParam(":estilo", $this->estilo);
        $stmt->bindParam(":selecionado", $this->selecionado);
        $stmt->bindParam(":paisPadrao", $this->paisPadrao);
        $stmt->bindParam(":exibirBandeiras", $this->exibirBandeiras);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }
 
    }

    //ler todos os jogadores para o quadro
    function readAll($from_record_num, $records_per_page, $idDono){

        $idDono = htmlspecialchars(strip_tags($idDono));
 
     $query = "SELECT
                 a.ID, a.Nome, a.Gols, a.Faltas, a.Impedimentos, a.Cartoes, a.Estilo, a.Selecionado, a.PaisPadrao, p.bandeira, p.sigla, a.ExibirBandeiras 
             FROM
                 " . $this->table_name . " a

            LEFT JOIN paises p ON a.PaisPadrao = p.id 
             WHERE a.Dono = ?  

             ORDER BY
             a.Nome ASC 
             LIMIT
                 {$from_record_num}, {$records_per_page}";
 
     $stmt = $this->conn->prepare( $query );
     $stmt->bindParam(1,$idDono);
     $stmt->execute();

     return $stmt;
    }

    // used for paging products
    public function countAll($idDono){

     $idDono = htmlspecialchars(strip_tags($idDono));

         $query =    "SELECT a.id 
                     FROM " . $this->table_name . " a
                       WHERE a.Dono = ".$idDono;

     $stmt = $this->conn->prepare( $query );
     $stmt->execute();
 
     $num = $stmt->rowCount();
 
     return $num;
    }

    function coletarOpcoes($idUsuario){
        $idUsuario = htmlspecialchars(strip_tags($idUsuario));

        $query = "SELECT * FROM opcoesHYMT WHERE usuario = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idUsuario);
        $stmt->execute();

        return $stmt;

    }

    function exportacao($idPais){

        $idPais = htmlspecialchars(strip_tags($idPais));

        $query = "SELECT h.ID, h.Nome, h.Gols, h.Faltas, h.Impedimentos, h.Cartoes, (0.4 + 0.2 * h.Estilo) as Alto, (1.6 - 0.2 * h.Estilo) as Chao, CASE WHEN h.PaisPadrao <> 0 THEN p.bandeira ELSE '-' END as PaisPadrao, h.Selecionado, h.ExibirBandeiras FROM parametrosHYMT h LEFT JOIN paises p ON p.ID = h.PaisPadrao WHERE h.PaisPadrao = ? OR h.PaisPadrao = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$idPais);
        $stmt->execute();

        return $stmt; 
    }       

    function alterarOpcoes($dono, $sumulas, $lesoes, $porTempo, $porData, $VAR){
        $dono = htmlspecialchars(strip_tags($dono));
        $sumulas = htmlspecialchars(strip_tags($sumulas));
        $lesoes = htmlspecialchars(strip_tags($lesoes));
        $porTempo = htmlspecialchars(strip_tags($porTempo));
        $porData = htmlspecialchars(strip_tags($porData));
        $VAR = htmlspecialchars(strip_tags($VAR));


        $query = "INSERT INTO opcoesHYMT (usuario, mostrarSumula, limitarLesoes, porTempo, porData, videoAr) VALUES (?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE mostrarSumula = VALUES(mostrarSumula), limitarLesoes = VALUES(limitarLesoes), porTempo = VALUES(porTempo), porData = VALUES(porData), videoAr = VALUES(videoAr)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$dono);
        $stmt->bindParam(2,$sumulas);
        $stmt->bindParam(3,$lesoes);
        $stmt->bindParam(4,$porTempo);
        $stmt->bindParam(5,$porData);
        $stmt->bindParam(6,$VAR);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }


    }


    //alterar parametro
    function alterar(){

        $this->nome=htmlspecialchars(strip_tags($this->nome));
        $this->dono=htmlspecialchars(strip_tags($this->dono));
        $this->gols=htmlspecialchars(strip_tags($this->gols));
        $this->faltas=htmlspecialchars(strip_tags($this->faltas));
        $this->impedimentos=htmlspecialchars(strip_tags($this->impedimentos));
        $this->cartoes=htmlspecialchars(strip_tags($this->cartoes));
        $this->estilo=htmlspecialchars(strip_tags($this->estilo));
        $this->selecionado=htmlspecialchars(strip_tags($this->selecionado));
        $this->paisPadrao=htmlspecialchars(strip_tags($this->paisPadrao));
        $this->exibirBandeiras=htmlspecialchars(strip_tags($this->exibirBandeiras));
        $this->id=htmlspecialchars(strip_tags($this->id));

        if($this->selecionado == 1){
            $prequery = "UPDATE " . $this->table_name . " SET Selecionado=0 WHERE Dono=:dono";
            $stmt = $this->conn->prepare($prequery);
            $stmt->bindParam(":dono", $this->dono);
            $stmt->execute();
        }

        $query = "UPDATE " . $this->table_name . " SET Nome=:nome, Gols=:gols, Faltas=:faltas, Impedimentos=:impedimentos, Cartoes=:cartoes, Estilo=:estilo, Selecionado=:selecionado, PaisPadrao=:paisPadrao, ExibirBandeiras=:exibirBandeiras WHERE ID=:id";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":gols", $this->gols);
        $stmt->bindParam(":faltas", $this->faltas);
        $stmt->bindParam(":impedimentos", $this->impedimentos);
        $stmt->bindParam(":cartoes", $this->cartoes);
        $stmt->bindParam(":estilo", $this->estilo);
        $stmt->bindParam(":selecionado", $this->selecionado);
        $stmt->bindParam(":paisPadrao", $this->paisPadrao);
        $stmt->bindParam(":exibirBandeiras", $this->exibirBandeiras);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }
        
    }

//     function readInfo($id){

//         $id = htmlspecialchars(strip_tags($id));
        
//     $query = "SELECT
//                 a.nome, a.tier, a.logo, p.Nome as Pais, a.Pais as idPais 
//             FROM
//                 " . $this->table_name . " a
//             LEFT JOIN
//                 paises p ON a.Pais = p.id
//             WHERE
//                 a.id={$id}";
 
//     $stmt = $this->conn->prepare( $query );
//     $stmt->execute();
//     $info1 = $stmt->fetch(PDO::FETCH_ASSOC);

//     $query = "SELECT avg(DATEDIFF(NOW(), j.Nascimento)/365) as mediaIdade, SUM(j.valor) as valorTotal, sum(case when j.Pais != b.Pais then 1 else 0 end) as estrangeiros, count(*) as jogadores, p.dono as idDonoPais
//     FROM contratos_jogador c 
//     LEFT JOIN jogador j ON c.jogador = j.id 
//     LEFT JOIN paises p ON j.Pais = p.id
// LEFT JOIN clube b ON c.clube = b.id
//     WHERE b.liga = {$id}";
    
//     $stmt = $this->conn->prepare( $query );
//     $stmt->execute();
//     $info2 = $stmt->fetch(PDO::FETCH_ASSOC);
//     $info = array_merge($info1,$info2);



//     return $info;   

    
        
//     }

//      // used by select drop-down list
//      function read($dono){
        
//         //select all data
//         $query = "SELECT
//                     a.id, a.nome
//                 FROM
//                     " . $this->table_name . " a
//                 LEFT JOIN
//                     paises p ON a.Pais = p.id
//                 WHERE p.dono = ?
//                 ORDER BY
//                     nome";  
 
//         $stmt = $this->conn->prepare( $query );
//             $stmt->bindParam(1, $dono);
    
//         $stmt->execute();
 
//         return $stmt;
//     }

//     function lerPorPais($pais){
        
//         //select all data
//         $query = "SELECT
//                     a.id, a.nome
//                 FROM
//                     " . $this->table_name . " a
//                 LEFT JOIN
//                     paises p ON a.Pais = p.id
//                 WHERE p.id = ?
//                 ORDER BY
//                     a.tier ASC";  
 
//         $stmt = $this->conn->prepare( $query );
//             $stmt->bindParam(1, $pais);
    
//         $stmt->execute();
 
//         return $stmt;
//     }
}
?>