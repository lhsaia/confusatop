<?php
class Clima{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "clima";

    // object properties
    public $id;
    public $nome;
    public $tempVerao;
    public $estiloVerao;
    public $tempOutono;
    public $estiloOutono;
    public $tempInverno;
    public $estiloInverno;
    public $tempPrimavera;
    public $estiloPrimavera;
    public $hemisferio;
    public $pais;

    public function __construct($db){
        $this->conn = $db;
    }

    // criar time
    function create(){

        //escrever query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    Nome=:nome, TempVerao=:tempVerao, EstiloVerao=:estiloVerao, TempOutono=:tempOutono, EstiloOutono=:estiloOutono, TempInverno=:tempInverno, EstiloInverno=:estiloInverno, TempPrimavera=:tempPrimavera, EstiloPrimavera=:estiloPrimavera, Hemisferio=:hemisferio, Pais=:pais";

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->nome=htmlspecialchars(strip_tags($this->nome));
        $this->tempVerao=htmlspecialchars(strip_tags($this->tempVerao));
        $this->estiloVerao=htmlspecialchars(strip_tags($this->estiloVerao));
        $this->tempOutono=htmlspecialchars(strip_tags($this->tempOutono));
        $this->estiloOutono=htmlspecialchars(strip_tags($this->estiloOutono));
        $this->tempInverno=htmlspecialchars(strip_tags($this->tempInverno));
        $this->estiloInverno=htmlspecialchars(strip_tags($this->estiloInverno));
        $this->tempPrimavera=htmlspecialchars(strip_tags($this->tempPrimavera));
        $this->estiloPrimavera=htmlspecialchars(strip_tags($this->estiloPrimavera));
        $this->hemisferio=htmlspecialchars(strip_tags($this->hemisferio));
        $this->pais=htmlspecialchars(strip_tags($this->pais));

        // bind values
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":tempVerao", $this->tempVerao);
        $stmt->bindParam(":estiloVerao", $this->estiloVerao);
        $stmt->bindParam(":tempOutono", $this->tempOutono);
        $stmt->bindParam(":estiloOutono", $this->estiloOutono);
        $stmt->bindParam(":tempInverno", $this->tempInverno);
        $stmt->bindParam(":estiloInverno", $this->estiloInverno);
        $stmt->bindParam(":tempPrimavera", $this->tempPrimavera);
        $stmt->bindParam(":estiloPrimavera", $this->estiloPrimavera);
        $stmt->bindParam(":hemisferio", $this->hemisferio);
        $stmt->bindParam(":pais", $this->pais);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }

   //ler todos os jogadores para o quadro
   function readAll($from_record_num, $records_per_page, $dono){

    $dono = htmlspecialchars(strip_tags($dono));

$query = "SELECT
            a.ID, a.Nome, a.TempVerao, a.EstiloVerao, a.TempOutono, a.EstiloOutono, a.TempInverno, a.EstiloInverno, a.TempPrimavera, a.EstiloPrimavera, a.Hemisferio, p.sigla as siglaPais, p.bandeira as bandeiraPais, p.id as idPais, p.dono as idDonoPais
        FROM
            " . $this->table_name . " a
        LEFT JOIN paises p ON a.Pais = p.id
        WHERE p.dono = ?
        ORDER BY
            a.Nome DESC
        LIMIT
            {$from_record_num}, {$records_per_page}";

$stmt = $this->conn->prepare( $query );
$stmt->bindParam(1, $dono);
$stmt->execute();

return $stmt;
}

// used for paging products
public function countAll($dono){

$dono = htmlspecialchars(strip_tags($dono));



    $query =    "SELECT a.id
                FROM " . $this->table_name . " a
                 LEFT JOIN paises p ON a.pais = p.id
                  WHERE p.dono =".$dono;


$stmt = $this->conn->prepare( $query );
$stmt->execute();

$num = $stmt->rowCount();

return $num;
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

    //verificar se já existe
    function verificar(){

        $query = "SELECT count(ID) as total FROM clima WHERE Nome = ? AND Pais = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$this->nome);
        $stmt->bindParam(2,$this->pais);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $row['total'];

        return $total;
    }

    function codigoPorNomeEPais(){

        $query = "SELECT ID FROM clima WHERE Nome = ? AND Pais = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$this->nome);
        $stmt->bindParam(2,$this->pais);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $ID = $row['ID'];

        return $ID;
    }

    function exportacao($idPais = null, $idTime = null){

        $idPais = htmlspecialchars(strip_tags($idPais));
        $idTime = htmlspecialchars(strip_tags($idTime));

        if($idPais != null){
          $query = "SELECT DISTINCT c.ID as idClima, c.Nome as nomeClima, c.TempVerao, c.EstiloVerao, c.TempOutono, c.EstiloOutono, c.TempInverno, c.EstiloInverno, c.TempPrimavera, c.EstiloPrimavera, c.Hemisferio FROM estadio e LEFT JOIN clima c ON e.Clima = c.ID WHERE e.Pais=:pais";
        } else {
          $query = "SELECT DISTINCT c.ID as idClima, c.Nome as nomeClima, c.TempVerao, c.EstiloVerao, c.TempOutono, c.EstiloOutono, c.TempInverno, c.EstiloInverno, c.TempPrimavera, c.EstiloPrimavera, c.Hemisferio FROM clube b LEFT JOIN estadio e ON b.Estadio = e.ID LEFT JOIN clima c ON c.ID = e.Clima WHERE b.ID=:clube";
        }
        $stmt = $this->conn->prepare( $query );
        if($idPais != null){
          $stmt->bindParam(":pais", $idPais);
        } else {
          $stmt->bindParam(":clube", $idTime);
        }
        $stmt->execute();

        return $stmt;

    }

        // used by select drop-down list
        function read($dono){

            //select all data
            $query = "SELECT
                        c.ID, c.Nome
                    FROM
                        " . $this->table_name . "  c
                    LEFT JOIN paises p ON c.Pais = p.id
                    WHERE p.dono = ?
                    ORDER BY
                        c.Nome";

            $stmt = $this->conn->prepare( $query );

                $stmt->bindParam(1, $dono);

            $stmt->execute();

            return $stmt;
        }

        public function coletarClimaTime($idTime){
          $idTime = htmlspecialchars(strip_tags($idTime));

          $query = "SELECT
                      a.id, a.Nome, a.TempVerao, a.EstiloVerao, a.TempOutono, a.EstiloOutono, a.TempInverno, a.EstiloInverno, a.TempPrimavera, a.EstiloPrimavera, a.Hemisferio FROM " . $this->table_name . " a LEFT JOIN estadio e ON e.clima = a.ID LEFT JOIN clube c ON c.Estadio = e.ID WHERE c.ID = ?";
          $stmt = $this->conn->prepare( $query );
          $stmt->bindParam(1, $idTime);
          $stmt->execute();

          return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
}
?>
