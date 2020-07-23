<?php
class Time{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "clube";

    // object properties
    public $id;
    public $nome;
    public $sigla;
    public $estadio;
    public $escudo;
    public $uniforme1cor1;
    public $uniforme1cor2;
    public $uniforme1cor3;
    public $uniforme1;
    public $uniforme2cor1;
    public $uniforme2cor2;
    public $uniforme2cor3;
    public $uniforme2;
    public $maxTorcedores;
    public $fidelidade;
    public $pais;
    public $liga;
    public $sexo;
    public $status;


    public function __construct($db){
        $this->conn = $db;
    }

    // criar time
    function create(){

        if(!isset($this->status)){
            $this->status = 0;
        }

        //escrever query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    Nome=:nome, TresLetras=:sigla, Estadio=:estadio, Escudo=:escudo, Uni1Cor1=:uniforme1cor1, Uni1Cor2=:uniforme1cor2, Uni1Cor3=:uniforme1cor3, Uni2Cor1=:uniforme2cor1, Uni2Cor2=:uniforme2cor2, Uni2Cor3=:uniforme2cor3, Uniforme1=:uniforme1, Uniforme2=:uniforme2, MaxTorcedores=:maxTorcedores, Fidelidade=:fidelidade, Pais=:pais, liga=:liga, Sexo=:sexo, status=:status ";

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->nome=htmlspecialchars(strip_tags($this->nome));
        $this->sigla=htmlspecialchars(strip_tags($this->sigla));
        $this->estadio=htmlspecialchars(strip_tags($this->estadio));
        $this->escudo=htmlspecialchars(strip_tags($this->escudo));
        $this->uniforme1cor1=htmlspecialchars(strip_tags($this->uniforme1cor1));
        $this->uniforme1cor2=htmlspecialchars(strip_tags($this->uniforme1cor2));
        $this->uniforme1cor3=htmlspecialchars(strip_tags($this->uniforme1cor3));
        $this->uniforme1=htmlspecialchars(strip_tags($this->uniforme1));
        $this->uniforme2cor1=htmlspecialchars(strip_tags($this->uniforme2cor1));
        $this->uniforme2cor2=htmlspecialchars(strip_tags($this->uniforme2cor2));
        $this->uniforme2cor3=htmlspecialchars(strip_tags($this->uniforme2cor3));
        $this->uniforme2=htmlspecialchars(strip_tags($this->uniforme2));
        $this->maxTorcedores=htmlspecialchars(strip_tags($this->maxTorcedores));
        $this->fidelidade=htmlspecialchars(strip_tags($this->fidelidade));
        $this->pais=htmlspecialchars(strip_tags($this->pais));
        $this->liga=htmlspecialchars(strip_tags($this->liga));
        $this->sexo=htmlspecialchars(strip_tags($this->sexo));
        $this->status=htmlspecialchars(strip_tags($this->status));

        // verificar sigla
        if($this->siglaDuplicada()){
            $this->sigla = $this->novaSiglaUnica();
        }

        // bind values
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":sigla", $this->sigla);
        $stmt->bindParam(":estadio", $this->estadio);
        $stmt->bindParam(":escudo", $this->escudo);
        $stmt->bindParam(":uniforme1cor1", $this->uniforme1cor1);
        $stmt->bindParam(":uniforme1cor2", $this->uniforme1cor2);
        $stmt->bindParam(":uniforme1cor3", $this->uniforme1cor3);
        $stmt->bindParam(":uniforme1", $this->uniforme1);
        $stmt->bindParam(":uniforme2cor1", $this->uniforme2cor1);
        $stmt->bindParam(":uniforme2cor2", $this->uniforme2cor2);
        $stmt->bindParam(":uniforme2cor3", $this->uniforme2cor3);
        $stmt->bindParam(":uniforme2", $this->uniforme2);
        $stmt->bindParam(":maxTorcedores", $this->maxTorcedores);
        $stmt->bindParam(":fidelidade", $this->fidelidade);
        $stmt->bindParam(":pais", $this->pais);
        $stmt->bindParam(":liga", $this->liga);
        $stmt->bindParam(":sexo", $this->sexo);
        $stmt->bindParam(":status", $this->status);

        try {
            //PDO query execution goes here.
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
        }
        catch (\PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                //The INSERT query failed due to a key constraint violation.
                return false;
            }
        }


    }

    //ler todos os jogadores para o quadro
    function readAll($from_record_num, $records_per_page, $dono = null, $liga = null){

        //ver se é por dono ou geral
        if($dono === null && $liga === null){
            $sub_query_inicio = "";
            $sub_query_fim = "";
        } else if($liga === null) {
            $sub_query_inicio = "SELECT * FROM (";
            $sub_query_fim = ") t1 WHERE idDonoPais = ? AND status = 0";

        } else if($dono === null){
            $sub_query_inicio = "SELECT * FROM (";
            $sub_query_fim = ") t1 WHERE t1.liga = ?";
        }

    $query = $sub_query_inicio."SELECT
                a.ID, a.Nome, a.TresLetras, a.Escudo, a.Uni1Cor1, a.Uni1Cor2, a.Uni1Cor3, a.Uni2Cor1, a.Uni2Cor2, a.Uni2Cor3, a.Uniforme1, a.Uniforme2, a.MaxTorcedores, a.Fidelidade, p.id as idPais, p.dono as idDonoPais, e.Nome as nomeEstadio, l.nome as nomeLiga, p.sigla as siglaPais, p.bandeira as bandeiraPais, a.liga, l.logo, e.Capacidade as capacidade, a.estadio as estadioId, a.Sexo as sexo, a.status
                FROM " . $this->table_name . " a
        LEFT JOIN paises p ON a.Pais = p.id
        LEFT JOIN estadio e ON a.Estadio = e.id
        LEFT JOIN liga l ON a.liga = l.id
        ORDER BY
            a.Nome ASC ".$sub_query_fim."
        LIMIT
            {$from_record_num}, {$records_per_page}";



$stmt = $this->conn->prepare( $query );

if($dono === null && $liga === null){
} else if($liga === null) {
    $stmt->bindParam(1, $dono);
} else if($dono === null) {
    $stmt->bindParam(1, $liga);
}
$stmt->execute();

return $stmt;



}



 // used for paging products
 public function countAll($dono = null, $liga = null){

    $dono = htmlspecialchars(strip_tags($dono));
    $liga = htmlspecialchars(strip_tags($liga));

    if($dono == null && $liga == null){

       $query = "SELECT id FROM " . $this->table_name . "";

    } else if($liga == null) {

        $query =    "SELECT a.id
                    FROM " . $this->table_name . " a
                     LEFT JOIN paises p ON a.pais = p.id
                      WHERE a.status = 0 AND p.dono = ".$dono;

    } else if($dono == null){
        $query =    "SELECT a.id
                    FROM " . $this->table_name . " a
                     LEFT JOIN paises p ON a.pais = p.id
                      WHERE a.liga = ".$liga;
    }

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    $num = $stmt->rowCount();

    return $num;
   }

// $escudo_time = $info['Escudo'];
// $uniforme1_time = $info['Uniforme1'];
// $uniforme2_time = $info['Uniforme2'];
// $pais_time = $info['Pais'];
// $liga_time = $info['liga'];

function readInfo($id){

        $id = htmlspecialchars(strip_tags($id));

    $query = "SELECT
                a.Nome, a.TresLetras, e.Nome as Estadio, e.Capacidade as Capacidade, p.Nome as Pais, a.Escudo, a.Uniforme1, a.Uniforme2, l.nome as liga, l.id as liga_id, p.id as pais_id, p.dono as donoPais, a.status
            FROM
                " . $this->table_name . " a
            LEFT JOIN
                paises p ON a.Pais = p.id
            LEFT JOIN
                estadio e ON e.id = a.Estadio
            LEFT JOIN
                liga l ON l.id = a.liga
            WHERE
                a.id={$id}";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();
    $info1 = $stmt->fetch(PDO::FETCH_ASSOC);

    $query = "SELECT avg(DATEDIFF(NOW(), j.Nascimento)/365) as mediaIdade, avg(j.Nivel) as mediaNivel, sum(case when c.titularidade > 0 then j.Nivel else 0 end)/11 as mediaNivelOnze, SUM(j.valor) as valorTotal, sum(case when j.Pais != b.Pais then 1 else 0 end) as estrangeiros, count(*) as jogadores, (SELECT count(*) FROM (SELECT DISTINCT jogador FROM contratos_jogador WHERE tipoContrato > 0) t3
    INNER JOIN
    (SELECT jogador FROM contratos_jogador WHERE tipoContrato = 0 AND clube = {$id}) t4 USING(jogador)) as emSelecao
    FROM contratos_jogador c
    LEFT JOIN jogador j ON c.jogador = j.id
    LEFT JOIN paises p ON j.Pais = p.id
LEFT JOIN clube b ON c.clube = b.id
    WHERE c.clube = {$id}";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();
    $info2 = $stmt->fetch(PDO::FETCH_ASSOC);

    $info = array_merge($info1, $info2);



    return $info;



    }

         // used by select drop-down list
         function read($dono = null, $selecao = null){

            if($selecao === false){
                $subquery = " ";
            } else if($selecao != null){
                $subquery = "AND a.status > 0 ";
            } else {
                $subquery = "AND a.status = 0 ";
            }

            if($dono != null){
              $donoQuery = " p.dono = ? ";
            } else {
              $donoQuery = " 1 = 1 ";
            }

            //select all data
            $query = "SELECT
                        a.id, a.nome, a.Sexo, a.status, a.Pais as paisTime
                    FROM
                        " . $this->table_name . " a
                    LEFT JOIN
                        paises p ON a.Pais = p.id
                    WHERE " . $donoQuery  . $subquery . "
                    ORDER BY
                        a.nome";

            $stmt = $this->conn->prepare( $query );
            if($dono != null){
                $stmt->bindParam(1, $dono);
            }

            $stmt->execute();

            return $stmt;
        }

        function exportacao($idPais = null, $idTime = null){

            $idPais = htmlspecialchars(strip_tags($idPais));
            $idTime = htmlspecialchars(strip_tags($idTime));

            if($idPais != null){
              $subquery = " Pais=:pais  ";
            } else {
              $subquery = " ID=:clube ";
            }

            $query = "SELECT DISTINCT c.ID, c.Nome, c.TresLetras, c.Estadio, c.Escudo, c.Uni1Cor1, c.Uni1Cor2, c.Uni1Cor3, c.Uni2Cor1, c.Uni2Cor2, c.Uni2Cor3, c.Uniforme1, c.Uniforme2, c.MaxTorcedores, c.Fidelidade, c.Sexo  FROM clube c WHERE " . $subquery;
            $stmt = $this->conn->prepare( $query );
            if($idPais != null){
              $stmt->bindParam(":pais", $idPais);
            } else {
              $stmt->bindParam(":clube", $idTime);
            }
            $stmt->execute();

            return $stmt;

        }

        function verificarHomonimo($nomeTime, $idPais){
            $idPais = htmlspecialchars(strip_tags($idPais));

            //checagem de homônimos
            $query = "SELECT count(ID) FROM clube WHERE Pais=:pais AND Nome=:nome";
            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(":pais", $idPais);
            $stmt->bindParam(":nome", $nomeTime);
            $stmt->execute();
            $num = $stmt->fetchColumn();

            if($num > 1){
                return true;
            } else {
                return false;
            }

        }

        function getElenco($idClube){
            $idClube = htmlspecialchars(strip_tags($idClube));

            $query = "SELECT jogador as ID FROM contratos_jogador WHERE clube = ? AND titularidade >= 0";
            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1, $idClube);
            $stmt->execute();
            return $stmt;

        }

        function getSizeElenco($idClube){
            $idClube = htmlspecialchars(strip_tags($idClube));

            $query = "SELECT jogador as ID FROM contratos_jogador WHERE clube = ? AND titularidade >= 0";
            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1, $idClube);
            $stmt->execute();
            $num = $stmt->rowCount();

            return $num;

        }

        function getTecnico($idClube){
            $idClube = htmlspecialchars(strip_tags($idClube));

            $query = "SELECT tecnico FROM contratos_tecnico WHERE clube = ?";
            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1, $idClube);
            $stmt->execute();
            return $stmt;
        }



        function getEscalacao($idClube){
            $idClube = htmlspecialchars(strip_tags($idClube));

            $query = "SELECT c.jogador, p.Sigla as posicaoBase FROM contratos_jogador c LEFT JOIN posicoes p ON p.ID = c.posicaoBase WHERE c.clube = ? AND c.titularidade > 0 ORDER BY c.posicaoBase";
            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1, $idClube);
            $stmt->execute();
            return $stmt;
        }

        function getCapitao($idClube){
            $idClube = htmlspecialchars(strip_tags($idClube));

            $query = "SELECT jogador FROM contratos_jogador WHERE clube = ? AND titularidade > 0 AND capitao = 1";
            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1, $idClube);
            $stmt->execute();
            return $stmt;
        }

        function getPenaltis($idClube){
            $idClube = htmlspecialchars(strip_tags($idClube));

            $query = "SELECT jogador FROM contratos_jogador WHERE clube = ? AND titularidade > 0 AND cobrancaPenalti > 0 ORDER BY cobrancaPenalti ASC";
            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1, $idClube);
            $stmt->execute();
            return $stmt;
        }

        function verificarElencoMenor($idUsuario = null, $listaTimesExportados = null){
            $idUsuario = htmlspecialchars(strip_tags($idUsuario));

if($idUsuario != null){
  $query = "SELECT c.Nome, count(CASE WHEN t.jogador IS NOT NULL THEN t.jogador ELSE 0 END) as total FROM `contratos_jogador` t RIGHT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? GROUP BY c.Nome HAVING total <13";
  $stmt = $this->conn->prepare( $query );
  $stmt->bindParam(1, $idUsuario);
} else {
  $subquery = " c.ID = ? ";
  $totalTimes = count($listaTimesExportados);
  for($i = 1;$i < $totalTimes;$i++){
    $subquery .= " OR c.ID = ? ";
  }
  $query = "SELECT c.Nome, count(CASE WHEN t.jogador IS NOT NULL THEN t.jogador ELSE 0 END) as total FROM `contratos_jogador` t RIGHT JOIN clube c ON c.id = t.clube WHERE ".$subquery." GROUP BY c.Nome HAVING total <13";
  $stmt = $this->conn->prepare($query);
  for($j = 0; $j < $totalTimes ; $j++){
    $stmt->bindParam($j+1, $listaTimesExportados[$j]);
  }
}
            $stmt->execute();
            $listaTimes = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);
                $listaTimes[] = [$Nome,$total];
            }

            return $listaTimes;
        }

        function verificarElencoMaior($idUsuario = null, $listaTimesExportados = null){
            $idUsuario = htmlspecialchars(strip_tags($idUsuario));

if($idUsuario != null){
  $query = "SELECT c.Nome, count(t.jogador) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? AND t.titularidade <> -1 GROUP BY clube HAVING count(t.jogador)>23";
  $stmt = $this->conn->prepare( $query );
  $stmt->bindParam(1, $idUsuario);
} else {
  $subquery = " c.ID = ? ";
  $totalTimes = count($listaTimesExportados);
  for($i = 1;$i < $totalTimes;$i++){
    $subquery .= " OR c.ID = ? ";
  }
  $query = "SELECT c.Nome, count(t.jogador) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube WHERE (".$subquery.") AND t.titularidade <> -1 GROUP BY clube HAVING count(t.jogador)>23";
  $stmt = $this->conn->prepare($query);
  for($j = 0; $j < $totalTimes ; $j++){
    $stmt->bindParam($j+1, $listaTimesExportados[$j]);
  }
}

            $stmt->execute();
            $listaTimes = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);
                $listaTimes[] = [$Nome,$total];
            }

            return $listaTimes;
        }

        function verificarCapitao($idUsuario = null, $listaTimesExportados = null){
            $idUsuario = htmlspecialchars(strip_tags($idUsuario));

            if($idUsuario != null){
              $query = "SELECT c.Nome, sum(t.capitao * t.titularidade) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? GROUP BY clube HAVING sum(t.capitao * t.titularidade) != 1";
              $stmt = $this->conn->prepare( $query );
              $stmt->bindParam(1, $idUsuario);
            } else {
              $subquery = " c.ID = ? ";
              $totalTimes = count($listaTimesExportados);
              for($i = 1;$i < $totalTimes;$i++){
                $subquery .= " OR c.ID = ? ";
              }
              $query = "SELECT c.Nome, sum(t.capitao * t.titularidade) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube WHERE ".$subquery." GROUP BY clube HAVING sum(t.capitao * t.titularidade) != 1";
              $stmt = $this->conn->prepare($query);
              for($j = 0; $j < $totalTimes ; $j++){
                $stmt->bindParam($j+1, $listaTimesExportados[$j]);
              }
            }


            $stmt->execute();
            $listaTimes = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);
                $listaTimes[] = [$Nome,$total];
            }

            return $listaTimes;
        }

        function verificarTecnicos($idUsuario = null, $listaTimesExportados = null){
            $idUsuario = htmlspecialchars(strip_tags($idUsuario));

            if($idUsuario != null){
              $query = "SELECT c.Nome, count(CASE WHEN t.tecnico IS NOT NULL THEN t.tecnico ELSE 0 END) as total FROM `contratos_tecnico` t RIGHT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? GROUP BY clube HAVING total <> 1";
              $stmt = $this->conn->prepare( $query );
              $stmt->bindParam(1, $idUsuario);
            } else {
              $subquery = " c.ID = ? ";
              $totalTimes = count($listaTimesExportados);
              for($i = 1;$i < $totalTimes;$i++){
                $subquery .= " OR c.ID = ? ";
              }
              $query = "SELECT c.Nome, count(CASE WHEN t.tecnico IS NOT NULL THEN t.tecnico ELSE 0 END) as total FROM `contratos_tecnico` t RIGHT JOIN clube c ON c.id = t.clube WHERE ".$subquery." GROUP BY clube HAVING total <> 1";
              $stmt = $this->conn->prepare($query);
              for($j = 0; $j < $totalTimes ; $j++){
                $stmt->bindParam($j+1, $listaTimesExportados[$j]);
              }
            }


            $stmt->execute();
            $listaTimes = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);
                $listaTimes[] = [$Nome,$total];
            }

            return $listaTimes;
        }

        function verificarPenaltis($idUsuario = null, $listaTimesExportados = null){
            $idUsuario = htmlspecialchars(strip_tags($idUsuario));

            if($idUsuario != null){
              $query = "SELECT c.Nome, sum(t.cobrancaPenalti * t.titularidade) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? GROUP BY clube HAVING sum(t.cobrancaPenalti * t.titularidade) != 6";
              $stmt = $this->conn->prepare( $query );
              $stmt->bindParam(1, $idUsuario);
            } else {
              $subquery = " c.ID = ? ";
              $totalTimes = count($listaTimesExportados);
              for($i = 1;$i < $totalTimes;$i++){
                $subquery .= " OR c.ID = ? ";
              }
              $query = "SELECT c.Nome, sum(t.cobrancaPenalti * t.titularidade) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube WHERE ".$subquery." GROUP BY clube HAVING sum(t.cobrancaPenalti * t.titularidade) != 6";
              $stmt = $this->conn->prepare($query);
              for($j = 0; $j < $totalTimes ; $j++){
                $stmt->bindParam($j+1, $listaTimesExportados[$j]);
              }
            }


            $stmt->execute();
            $listaTimes = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);
                $listaTimes[] = [$Nome,$total];
            }

            return $listaTimes;
        }

        function verificarGoleiros($idUsuario = null, $listaTimesExportados = null){
            $idUsuario = htmlspecialchars(strip_tags($idUsuario));

            if($idUsuario != null){
              $query = "SELECT c.Nome, sum(case when (t.posicaoBase = 1 AND t.titularidade = 1) THEN 1 ELSE 0 END) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? GROUP BY clube HAVING total != 1";
              $stmt = $this->conn->prepare( $query );
              $stmt->bindParam(1, $idUsuario);
            } else {
              $subquery = " c.ID = ? ";
              $totalTimes = count($listaTimesExportados);
              for($i = 1;$i < $totalTimes;$i++){
                $subquery .= " OR c.ID = ? ";
              }
              $query = "SELECT c.Nome, sum(case when (t.posicaoBase = 1 AND t.titularidade = 1) THEN 1 ELSE 0 END) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube WHERE ".$subquery." GROUP BY clube HAVING total != 1";
              $stmt = $this->conn->prepare($query);
              for($j = 0; $j < $totalTimes ; $j++){
                $stmt->bindParam($j+1, $listaTimesExportados[$j]);
              }
            }


            $stmt->execute();
            $listaTimes = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);
                $listaTimes[] = [$Nome,$total];
            }

            return $listaTimes;
        }

        function verificarEscalacoes($idUsuario = null, $listaTimesExportados = null){
            $idUsuario = htmlspecialchars(strip_tags($idUsuario));

            if($idUsuario != null){
              $query = "SELECT c.Nome, sum(case when t.titularidade = 1 THEN 1 ELSE 0 END) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? GROUP BY clube HAVING total != 11";
              $stmt = $this->conn->prepare( $query );
              $stmt->bindParam(1, $idUsuario);
            } else {
              $subquery = " c.ID = ? ";
              $totalTimes = count($listaTimesExportados);
              for($i = 1;$i < $totalTimes;$i++){
                $subquery .= " OR c.ID = ? ";
              }
              $query = "SELECT c.Nome, sum(case when t.titularidade = 1 THEN 1 ELSE 0 END) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube  WHERE ".$subquery." GROUP BY clube HAVING total != 11";
              $stmt = $this->conn->prepare($query);
              for($j = 0; $j < $totalTimes ; $j++){
                $stmt->bindParam($j+1, $listaTimesExportados[$j]);
              }
            }


            $stmt->execute();
            $listaTimes = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);
                $listaTimes[] = [$Nome,$total];
            }

            return $listaTimes;
        }

        function verificarAposentados($idUsuario = null, $listaTimesExportados = null){
            $idUsuario = htmlspecialchars(strip_tags($idUsuario));

            if($idUsuario != null){
              $query = "SELECT c.Nome, sum(case when FLOOR((DATEDIFF(CURDATE(), j.Nascimento))/365) > 45 THEN 1 ELSE 0 END) as total FROM contratos_jogador t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id LEFT JOIN jogador j ON j.ID = t.jogador WHERE p.dono = ? GROUP BY clube HAVING total > 0";
              $stmt = $this->conn->prepare( $query );
              $stmt->bindParam(1, $idUsuario);
            } else {
              $subquery = " c.ID = ? ";
              $totalTimes = count($listaTimesExportados);
              for($i = 1;$i < $totalTimes;$i++){
                $subquery .= " OR c.ID = ? ";
              }
              $query = "SELECT c.Nome, sum(case when FLOOR((DATEDIFF(CURDATE(), j.Nascimento))/365) > 45 THEN 1 ELSE 0 END) as total FROM contratos_jogador t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN jogador j ON j.ID = t.jogador WHERE ".$subquery." GROUP BY clube HAVING total > 0";
              $stmt = $this->conn->prepare($query);
              for($j = 0; $j < $totalTimes ; $j++){
                $stmt->bindParam($j+1, $listaTimesExportados[$j]);
              }
            }


            $stmt->execute();
            $listaTimes = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);
                $listaTimes[] = [$Nome,$total];
            }

            return $listaTimes;
        }

        function moverLiga($idTime, $idLiga){
            $idTime = htmlspecialchars(strip_tags($idTime));
            $idLiga = htmlspecialchars(strip_tags($idLiga));

            $query = "UPDATE " . $this->table_name . " SET liga = ? WHERE id = ?";
            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1, $idLiga);
            $stmt->bindParam(2, $idTime);
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }

        }

        function alterarElenco($idJogador1, $idJogador2,$tipoAlteracao,$posJogador1 = null,$posJogador2 = null, $time){
            $idJogador1 = htmlspecialchars(strip_tags($idJogador1));
            $idJogador2 = htmlspecialchars(strip_tags($idJogador2));
            $tipoAlteracao = htmlspecialchars(strip_tags($tipoAlteracao));

            //tipos de alteraçao
            //0 - troca entre titular e reserva
            //1 - troca entre reserva e suplente
            //2 - suplente para reserva
            //3 - reserva para suplente
            //6 - troca de posicionamento
            //7 - alternar entre Am e Aa
            //4 - reserva para titular
            //5 - titular para reserva

            switch($tipoAlteracao){
                case 0:
                    //check and adjust positions
                    $query = "SELECT c.posicaoBase, j.StringPosicoes, c.capitao, c.cobrancaPenalti FROM contratos_jogador c LEFT JOIN jogador j ON j.ID = c.jogador WHERE c.clube = ? AND (c.jogador = ? OR c.jogador = ?) ORDER BY c.titularidade DESC";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1,$time);
                    $stmt->bindParam(2,$idJogador1);
                    $stmt->bindParam(3,$idJogador2);
                    $stmt->execute();
                    $dataArray = array();
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                        $dataArray[] = [$row['posicaoBase'],$row['StringPosicoes'],$row['capitao'],$row['cobrancaPenalti']];
                    }

                    if($dataArray[1][1][$dataArray[0][0] - 1] == 1){
                        $posicaoBase2 = $dataArray[0][0];
                    } else {
                        $posicaoBase2 = (strpos($dataArray[1][1],'1'))+1;
                    }
                    
                    if($posicaoBase2 == 0){
                        $posicaoBase2 = "8";
                    }


                    //code - just query and change
                    $query = "UPDATE contratos_jogador SET titularidade = 0, posicaoBase = 0, capitao = 0, cobrancaPenalti = 0 WHERE jogador = ? AND clube = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1,$idJogador1);
                    $stmt->bindParam(2,$time);
                    $stmt->execute();

                    $query = "UPDATE contratos_jogador SET titularidade = 1, posicaoBase = ?, capitao = ?, cobrancaPenalti = ?  WHERE jogador = ? AND clube = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1,$posicaoBase2);
                    $stmt->bindParam(2,$dataArray[0][2]);
                    $stmt->bindParam(3,$dataArray[0][3]);
                    $stmt->bindParam(4,$idJogador2);
                    $stmt->bindParam(5,$time);
                    if($stmt->execute()){
                        return true;
                    } else {
                        return false;
                    }

                    break;
                case 1:
                    //code - just query and change
                    $query = "UPDATE contratos_jogador SET titularidade = -1 WHERE jogador = ? AND clube = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1,$idJogador1);
                    $stmt->bindParam(2,$time);
                    $stmt->execute();

                    $query = "UPDATE contratos_jogador SET titularidade = 0  WHERE jogador = ? AND clube = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1,$idJogador2);
                    $stmt->bindParam(2,$time);
                    if($stmt->execute()){
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 2:
                    //code - just query and change
                    $query = "UPDATE contratos_jogador SET titularidade = 0 WHERE jogador = ? AND clube = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1,$idJogador1);
                    $stmt->bindParam(2,$time);
                    if($stmt->execute()){
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 3:
                    //code - just query and change
                    $query = "UPDATE contratos_jogador SET titularidade = -1 WHERE jogador = ? AND clube = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1,$idJogador1);
                    $stmt->bindParam(2,$time);
                    if($stmt->execute()){
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 4:
                    //check and adjust positions
                    $query = "SELECT j.StringPosicoes FROM contratos_jogador c LEFT JOIN jogador j ON j.ID = c.jogador WHERE c.jogador = ? AND c.clube = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1,$idJogador1);
                    $stmt->bindParam(2,$time);
                    $stmt->execute();
                    $dataArray = array();
                    $stringPosicoes = $stmt->fetchColumn();
                    $posicaoBase = (strpos($stringPosicoes,'1'))+1;

                    //code - just query and change
                    $query = "UPDATE contratos_jogador SET titularidade = 1, posicaoBase = ? WHERE jogador = ? AND clube = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1,$posicaoBase);
                    $stmt->bindParam(2,$idJogador1);
                    $stmt->bindParam(3,$time);
                    if($stmt->execute()){
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 5:

                    //code - just query and change
                    $query = "UPDATE contratos_jogador SET titularidade = 0, posicaoBase = 0 WHERE jogador = ? AND clube = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1,$idJogador1);
                    $stmt->bindParam(2,$time);
                    if($stmt->execute()){
                        return true;
                    } else {
                        return false;
                    }
                    break;
                case 6:
                    //jogador 1
                    $err = 0;
                    if($idJogador1 != ''){
                        $query = "SELECT ID FROM posicoes WHERE Sigla = ?";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(1,$posJogador1);
                        if($stmt->execute()){
                        } else {
                            $err++;
                        }
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $posicaoNova = $row['ID'];
                        $query = "UPDATE contratos_jogador SET posicaoBase = ?  WHERE jogador = ? AND clube = ?";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(1,$posicaoNova);
                        $stmt->bindParam(2,$idJogador1);
                        $stmt->bindParam(3,$time);
                        if($stmt->execute()){
                        } else {
                            $err++;
                        }
                    }
                    //jogador 2
                    if($idJogador2 != ''){
                        $query2 = "SELECT ID FROM posicoes WHERE Sigla = ?";
                        $stmt2 = $this->conn->prepare($query2);
                        $stmt2->bindParam(1,$posJogador2);
                        if($stmt2->execute()){
                        } else {
                            $err++;
                        }
                        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                        $posicaoNova2 = $row2['ID'];
                        $query = "UPDATE contratos_jogador SET posicaoBase = ?  WHERE jogador = ? AND clube = ?";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(1,$posicaoNova2);
                        $stmt->bindParam(2,$idJogador2);
                        $stmt->bindParam(3,$time);
                        if($stmt->execute()){
                        } else {
                            $err++;
                        }
                    }

                    if($err > 0){
                        return false;
                    } else {
                        return true;
                    }

                    break;
                case 7:
                    $err = 0;
                    if($idJogador1 != ''){
                        $query = "SELECT ID FROM posicoes WHERE Sigla = ?";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(1,$posJogador1);
                        if($stmt->execute()){
                        } else {
                            $err++;
                        }
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $posicaoNova = $row['ID'];
                        $query = "UPDATE contratos_jogador SET posicaoBase = ?  WHERE jogador = ? AND clube = ?";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(1,$posicaoNova);
                        $stmt->bindParam(2,$idJogador1);
                        $stmt->bindParam(3,$time);
                        if($stmt->execute()){
                        } else {
                            $err++;
                        }
                    }
                    if($err > 0){
                        return false;
                    } else {
                        return true;
                    }
                    break;
                default:
                return false;
            }

        }


        function alterarCapitaoCobrador($capitao,$penal1,$penal2,$penal3,$clube){
            $capitao = htmlspecialchars(strip_tags($capitao));
            $penal1 = htmlspecialchars(strip_tags($penal1));
            $penal2 = htmlspecialchars(strip_tags($penal2));
            $penal3 = htmlspecialchars(strip_tags($penal3));
            $err = 0;

            $query = "UPDATE contratos_jogador SET capitao = 0 WHERE clube = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$clube);
            if($stmt->execute()){
            } else {
                $err++;
            }
            $query = "UPDATE contratos_jogador SET capitao = 1 WHERE clube = ? AND jogador = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$clube);
            $stmt->bindParam(2,$capitao);
            if($stmt->execute()){
            } else {
                $err++;
            }

            $query = "UPDATE contratos_jogador SET cobrancaPenalti = 0 WHERE clube = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$clube);
            if($stmt->execute()){
            } else {
                $err++;
            }

            $query = "UPDATE contratos_jogador SET cobrancaPenalti = 1 WHERE clube = ? AND jogador = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$clube);
            $stmt->bindParam(2,$penal1);
            if($stmt->execute()){
            } else {
                $err++;
            }

            $query = "UPDATE contratos_jogador SET cobrancaPenalti = 2 WHERE clube = ? AND jogador = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$clube);
            $stmt->bindParam(2,$penal2);
            if($stmt->execute()){
            } else {
                $err++;
            }

            $query = "UPDATE contratos_jogador SET cobrancaPenalti = 3 WHERE clube = ? AND jogador = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$clube);
            $stmt->bindParam(2,$penal3);
            if($stmt->execute()){
            } else {
                $err++;
            }

            if($err > 0){
                return false;
            } else {
                return true;
            }

        }

            // alterar time
    function alterar(){

        $query = "UPDATE
        " . $this->table_name . "
    SET
        Nome=:nome, ";

        //verificar se existem arquivos
        if(isset($this->escudo)){
            $query .= " Escudo=:escudo, ";
        }

        if(isset($this->uniforme1)){
            $query .= " Uniforme1=:uniforme1, ";
        }

        if(isset($this->uniforme2)){
            $query .= " Uniforme2=:uniforme2, ";
        }

        //escrever query
        $query .= " Estadio=:estadio,
                    Uni1Cor1=:uniforme1cor1,
                    Uni1Cor2=:uniforme1cor2,
                    Uni1Cor3=:uniforme1cor3,
                    Uni2Cor1=:uniforme2cor1,
                    Uni2Cor2=:uniforme2cor2,
                    Uni2Cor3=:uniforme2cor3,
                    MaxTorcedores=:maxTorcedores,
                    Fidelidade=:fidelidade,
                    Pais=:pais,
                    liga=:liga
                    WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        if(isset($this->escudo)){
            $this->escudo=htmlspecialchars(strip_tags($this->escudo));
            $stmt->bindParam(":escudo", $this->escudo);
        }

        if(isset($this->uniforme1)){
            $this->uniforme1=htmlspecialchars(strip_tags($this->uniforme1));
            $stmt->bindParam(":uniforme1", $this->uniforme1);
        }

        if(isset($this->uniforme2)){
            $this->uniforme2=htmlspecialchars(strip_tags($this->uniforme2));
            $stmt->bindParam(":uniforme2", $this->uniforme2);
        }

        // posted values
        $this->nome=htmlspecialchars(strip_tags($this->nome));
        $this->sigla=htmlspecialchars(strip_tags($this->sigla));
        $this->estadio=htmlspecialchars(strip_tags($this->estadio));
        $this->uniforme1cor1=htmlspecialchars(strip_tags($this->uniforme1cor1));
        $this->uniforme1cor2=htmlspecialchars(strip_tags($this->uniforme1cor2));
        $this->uniforme1cor3=htmlspecialchars(strip_tags($this->uniforme1cor3));
        $this->uniforme2cor1=htmlspecialchars(strip_tags($this->uniforme2cor1));
        $this->uniforme2cor2=htmlspecialchars(strip_tags($this->uniforme2cor2));
        $this->uniforme2cor3=htmlspecialchars(strip_tags($this->uniforme2cor3));
        $this->maxTorcedores=htmlspecialchars(strip_tags($this->maxTorcedores));
        $this->fidelidade=htmlspecialchars(strip_tags($this->fidelidade));
        $this->pais=htmlspecialchars(strip_tags($this->pais));
        $this->liga=htmlspecialchars(strip_tags($this->liga));
        $this->id=htmlspecialchars(strip_tags($this->id));

        // bind values
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":estadio", $this->estadio);
        $stmt->bindParam(":uniforme1cor1", $this->uniforme1cor1);
        $stmt->bindParam(":uniforme1cor2", $this->uniforme1cor2);
        $stmt->bindParam(":uniforme1cor3", $this->uniforme1cor3);
        $stmt->bindParam(":uniforme2cor1", $this->uniforme2cor1);
        $stmt->bindParam(":uniforme2cor2", $this->uniforme2cor2);
        $stmt->bindParam(":uniforme2cor3", $this->uniforme2cor3);
        $stmt->bindParam(":maxTorcedores", $this->maxTorcedores);
        $stmt->bindParam(":fidelidade", $this->fidelidade);
        $stmt->bindParam(":pais", $this->pais);
        $stmt->bindParam(":liga", $this->liga);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }

    function ultimasTransferencias($from_record_num, $records_per_page){
        $query = "SELECT j.Sexo as sexo, j.id as id, j.Nome as nomeJogador, s.Nome as posicaoBase, j.StringPosicoes as stringPosicoes, j.Pais as nacionalidade, p.Bandeira as bandeiraJogador, FLOOR(DATEDIFF(t.data,j.Nascimento)/365) as idade, c.Nome as clubeOrigem, d.Nome as clubeDestino, t.data, c.Pais as paisClubeOrigem, q.Bandeira as bandeiraClubeOrigem, d.Pais as paisClubeDestino, r.Bandeira as bandeiraClubeDestino, l.Nome as ligaOrigem, m.Nome as ligaDestino, c.Escudo as escudoOrigem, d.Escudo as escudoDestino, t.clubeOrigem as idClubeOrigem, t.clubeDestino as idClubeDestino, l.ID as idLigaOrigem, m.ID as idLigaDestino, CASE WHEN t.clubeOrigem = 0 OR t.clubeDestino = 0 THEN 0 ELSE t.valor END as valor
        FROM transferencias t
        LEFT JOIN jogador j ON t.jogador = j.ID
        LEFT JOIN paises p ON j.Pais = p.ID
        LEFT JOIN clube c ON t.clubeOrigem = c.ID
        LEFT JOIN paises q ON c.Pais = q.ID
        LEFT JOIN liga l ON c.Liga = l.ID
        LEFT JOIN clube d ON t.clubeDestino = d.ID
        LEFT JOIN paises r ON d.Pais = r.ID
        LEFT JOIN liga m ON d.Liga = m.ID
        LEFT JOIN contratos_jogador o ON o.jogador = t.jogador
        LEFT JOIN posicoes s ON o.posicaoBase = s.ID
        WHERE status_execucao = 1
        ORDER BY data DESC
        LIMIT {$from_record_num},{$records_per_page}";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    function maioresTransferencias($from_record_num, $records_per_page){
        $query = "SELECT j.Sexo as sexo, j.id as id, j.Nome as nomeJogador, s.Nome as posicaoBase, j.StringPosicoes as stringPosicoes, j.Pais as nacionalidade, p.Bandeira as bandeiraJogador, FLOOR(DATEDIFF(t.data,j.Nascimento)/365) as idade, c.Nome as clubeOrigem, d.Nome as clubeDestino, t.data, c.Pais as paisClubeOrigem, q.Bandeira as bandeiraClubeOrigem, d.Pais as paisClubeDestino, r.Bandeira as bandeiraClubeDestino, l.Nome as ligaOrigem, m.Nome as ligaDestino, c.Escudo as escudoOrigem, d.Escudo as escudoDestino, t.clubeOrigem as idClubeOrigem, t.clubeDestino as idClubeDestino, l.ID as idLigaOrigem, m.ID as idLigaDestino, CASE WHEN t.clubeOrigem = 0 OR t.clubeDestino = 0 THEN 0 ELSE t.valor END as valor
        FROM transferencias t
        LEFT JOIN jogador j ON t.jogador = j.ID
        LEFT JOIN paises p ON j.Pais = p.ID
        LEFT JOIN clube c ON t.clubeOrigem = c.ID
        LEFT JOIN paises q ON c.Pais = q.ID
        LEFT JOIN liga l ON c.Liga = l.ID
        LEFT JOIN clube d ON t.clubeDestino = d.ID
        LEFT JOIN paises r ON d.Pais = r.ID
        LEFT JOIN liga m ON d.Liga = m.ID
        LEFT JOIN contratos_jogador o ON o.jogador = t.jogador
        LEFT JOIN posicoes s ON o.posicaoBase = s.ID
        WHERE status_execucao = 1
        ORDER BY valor DESC LIMIT {$from_record_num},{$records_per_page}";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    function countAllTransfers(){
        $query = "SELECT count(*) as total FROM transferencias WHERE status_execucao = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];

    }

    function donoClube($clubeOrigem,$idJogador){
        $clubeOrigem = htmlspecialchars(strip_tags($clubeOrigem));
        $idJogador = htmlspecialchars(strip_tags($idJogador));

        $query = "SELECT COUNT(p.dono) as checkClube, p.dono as dono FROM paises p
        LEFT JOIN clube c ON c.Pais = p.id
        WHERE c.ID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$clubeOrigem);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $checkClube = $row['checkClube'];
        if($checkClube != 0){
            $idDono = $row['dono'];
            return $idDono;
        } else {
          return 0;
        }
        // else {
        //     $queryJogador = "SELECT COUNT(p.dono) as checkJogador, p.dono as donoJogador FROM paises p
        //     LEFT JOIN jogador j ON j.Pais = p.id
        //     WHERE j.ID = ?";
        //     $stmtJogador = $this->conn->prepare($queryJogador);
        //     $stmtJogador->bindParam(1,$idJogador);
        //     $stmtJogador->execute();
        //     $rowJogador = $stmtJogador->fetch(PDO::FETCH_ASSOC);
        //     $checkJogador = $rowJogador['checkJogador'];
        //
        //     if($checkJogador != 0){
        //         $idDono = $rowJogador['donoJogador'];
        //         return $idDono;
        //     } else {
        //         return 0;
        //     }
        // }

    }

    // function conferenciaNome($nome,$dono,$sexo){
    //     $nome = htmlspecialchars(strip_tags($nome));
    //     $dono = htmlspecialchars(strip_tags($dono));

    //     $query = "SELECT c.ID FROM " . $this->table_name . "  c LEFT JOIN paises p ON p.id = c.Pais WHERE c.Nome = ? AND p.dono = ?";
    //     $stmt = $this->conn->prepare($query);
    //     $stmt->bindParam(1,$nome);
    //     $stmt->bindParam(2,$dono);
    //     $stmt->execute();
    //     $number_of_rows = $stmt->fetchColumn();

    //     if($number_of_rows == 0){
    //         return false;
    //     } else {
    //         return true;
    //     }
    // }

    function balancoTransferencias($idTime){

        $idTime = htmlspecialchars(strip_tags($idTime));

        $query = "SELECT SUM(valor) as recebido FROM transferencias WHERE clubeOrigem = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$idTime);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $recebido = $row['recebido'];

        $query = "SELECT SUM(valor) as pago FROM transferencias WHERE clubeDestino = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$idTime);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $pago = $row['pago'];
        $balanco = $recebido - $pago;

        return $balanco;

    }

    function mediaNiveis($idTime){

        $idTime = htmlspecialchars(strip_tags($idTime));

        $query = "SELECT (SUM(j.Nivel) + SUM(c.ModificadorNivel))/COUNT(c.jogador) as mediaNiveis FROM contratos_jogador c LEFT JOIN jogador j ON j.ID = c.jogador WHERE c.clube = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$idTime);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $media = $result['mediaNiveis'];


        return $media;

    }

    function escalarHexagen($arrayTitulares, $idClube){
        $error_count = 0;
        foreach($arrayTitulares as $titular){
            $id = $titular['id'];
            $posicaoBase = $titular['posicaoBase'];
            $query = "UPDATE contratos_jogador SET titularidade = 1, posicaoBase = ? WHERE clube = ? AND jogador = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $posicaoBase);
            $stmt->bindParam(2, $idClube);
            $stmt->bindParam(3,$id);
            if($stmt->execute()){

            } else {
                $error_count++;

            }


        }

        if($error_count > 0){
            return false;
        } else {
            return true;
        }

    }

    function escudoPadrao(){
        $query = "SELECT escudo FROM clube WHERE id = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result;

    }

    function uniforme1Padrao(){
        $query = "SELECT uniforme1 FROM clube WHERE id = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result;

    }


    function uniforme2Padrao(){
        $query = "SELECT uniforme2 FROM clube WHERE id = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result;

    }

    function readSelecoes($idPais){
        $idPais = htmlspecialchars(strip_tags($idPais));

        $query = "SELECT
                        a.ID, a.Nome, a.TresLetras, a.Escudo, a.Uni1Cor1, a.Uni1Cor2, a.Uni1Cor3, a.Uni2Cor1, a.Uni2Cor2, a.Uni2Cor3, a.Uniforme1, a.Uniforme2, a.MaxTorcedores, a.Fidelidade, p.id as idPais, p.dono as idDonoPais, e.Nome as nomeEstadio, l.nome as nomeLiga, p.sigla as siglaPais, p.bandeira as bandeiraPais, a.liga, l.logo, e.Capacidade as capacidade, a.estadio as estadioId, a.Sexo as sexo
                        FROM " . $this->table_name . " a
                LEFT JOIN paises p ON a.Pais = p.id
                LEFT JOIN estadio e ON a.Estadio = e.id
                LEFT JOIN liga l ON a.liga = l.id
                WHERE a.Pais = ? AND a.status > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$idPais);
        $stmt->execute();
        return $stmt;

    }

    function siglaDuplicada(){

        $query = "SELECT TresLetras FROM  " . $this->table_name . " WHERE TresLetras = ? AND Pais = ? AND sexo = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$this->sigla);
        $stmt->bindParam(2,$this->pais);
        $stmt->bindParam(3,$this->sexo);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$row){
            return false;
        } else {
            return true;
        }

    }

    function novaSiglaUnica(){

        for($i = 0; $i < 10; $i++){
            $novaSigla = substr($this->sigla, 0, -1).$i;
            $query = "SELECT TresLetras FROM  " . $this->table_name . " WHERE TresLetras = ? AND Pais = ? AND sexo = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$novaSigla);
            $stmt->bindParam(2,$this->pais);
            $stmt->bindParam(3,$this->sexo);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$row){
                return $novaSigla;
            }
        }

    }

    public function coletarInformacoesTime($idTime){
      $idTime = htmlspecialchars(strip_tags($idTime));

      $query = "SELECT c.ID, c.Nome, c.TresLetras, c.Estadio, c.Escudo, c.Uni1Cor1, c.Uni1Cor2, c.Uni1Cor3, c.Uni2Cor1, c.Uni2Cor2, c.Uni2Cor3, c.Uniforme1, c.Uniforme2, c.MaxTorcedores, c.Fidelidade FROM clube c WHERE id = ?";
      $stmt = $this->conn->prepare( $query );
      $stmt->bindParam(1, $idTime);
      $stmt->execute();

      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function adicionarEstadio($idEstadio, $idTime){
      $idTime = htmlspecialchars(strip_tags($idTime));
      $idEstadio = htmlspecialchars(strip_tags($idEstadio));

            $query = "UPDATE " . $this->table_name . " SET Estadio = ? WHERE id = ?";
      $stmt = $this->conn->prepare( $query );
      $stmt->bindParam(1, $idEstadio);
      $stmt->bindParam(2, $idTime);
      if($stmt->execute()){
          return true;
      } else {
          return false;
      };
    }
	
	
    //ler todos os jogadores para o quadro - versão para página com Ajax
    function readAllAjax($item_pesquisado, $dono = null){
		$item_pesquisado = htmlspecialchars(strip_tags($item_pesquisado));
		$dono = htmlspecialchars(strip_tags($dono));

        //ver se é por dono ou geral
        if($dono === null){
            $sub_query_inicio = "SELECT * FROM (";
            $sub_query_fim = ") t1 WHERE (Nome LIKE ? OR TresLetras LIKE ?) LIMIT 150";
        } else {
            $sub_query_inicio = "SELECT * FROM (";
            $sub_query_fim = ") t1 WHERE idDonoPais = ? AND status = 0 AND (Nome LIKE ? OR TresLetras LIKE ?) LIMIT 150";

        } 

    $query = $sub_query_inicio."SELECT
                a.ID as id, a.Nome, a.TresLetras, a.Escudo, a.Uni1Cor1, a.Uni1Cor2, a.Uni1Cor3, a.Uni2Cor1, a.Uni2Cor2, a.Uni2Cor3, a.Uniforme1, a.Uniforme2, a.MaxTorcedores, a.Fidelidade, p.id as idPais, p.dono as idDonoPais, e.Nome as nomeEstadio, l.nome as nomeLiga, p.sigla as siglaPais, p.bandeira as bandeiraPais, a.liga, l.logo, e.Capacidade as capacidade, a.estadio as estadioId, a.Sexo as sexo, a.status
                FROM " . $this->table_name . " a
        LEFT JOIN paises p ON a.Pais = p.id
        LEFT JOIN estadio e ON a.Estadio = e.id
        LEFT JOIN liga l ON a.liga = l.id
        ORDER BY
            a.Nome ASC ".$sub_query_fim;

$stmt = $this->conn->prepare( $query );
$item_pesquisado = "%" . $item_pesquisado . "%";
	
if($dono === null){
	$stmt->bindParam(1, $item_pesquisado);
	$stmt->bindParam(2, $item_pesquisado);
} else {
    $stmt->bindParam(1, $dono);
	$stmt->bindParam(2, $item_pesquisado);
	$stmt->bindParam(3, $item_pesquisado);
} 

$stmt->execute();

return $stmt;



}





}
