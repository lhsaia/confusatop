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
    function readAll($from_record_num, $records_per_page, $dono = null, $liga = null, $pais = null){

        //ver se é por dono ou geral
        if($dono === null && $liga === null && $pais === null){
            $sub_query_inicio = "";
            $sub_query_fim = "";
        } else if($liga === null && $pais === null) {
            $sub_query_inicio = "SELECT * FROM (";
            $sub_query_fim = ") t1 WHERE idDonoPais = ? AND status = 0 ORDER BY Nome ASC";

        } else if($dono === null && $pais === null){
            $sub_query_inicio = "SELECT * FROM (";
            $sub_query_fim = ") t1 WHERE t1.liga = ?";
        } else if($liga === null && $dono === null){
            $sub_query_inicio = "SELECT * FROM (";
            $sub_query_fim = ") t1 WHERE t1.idPais = ?";
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

if($dono === null && $liga === null && $pais === null){
} else if($liga === null && $pais === null) {
    $stmt->bindParam(1, $dono);
} else if($dono === null && $pais === null) {
    $stmt->bindParam(1, $liga);
} else if($liga === null && $dono === null) {
    $stmt->bindParam(1, $pais);
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
                a.id, a.Nome, a.TresLetras, e.Nome as Estadio, e.Capacidade as Capacidade, p.Nome as Pais, a.Escudo, a.Uniforme1, a.Uniforme2, l.nome as liga, l.id as liga_id, p.id as pais_id, p.dono as donoPais, a.status, a.Uni1Cor1, a.Uni1Cor2, l.logo as logoLiga, e.foto as fotoEstadio   
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

    $query = "SELECT avg(DATEDIFF(NOW(), j.Nascimento)/365) as mediaIdade, avg(j.Nivel + c.ModificadorNivel) as mediaNivel, sum(case when c.titularidade > 0 then (j.Nivel + c.ModificadorNivel) else 0 end)/11 as mediaNivelOnze, SUM(j.valor) as valorTotal, sum(case when j.Pais != b.Pais then 1 else 0 end) as estrangeiros, count(*) as jogadores, (SELECT count(*) FROM (SELECT DISTINCT jogador FROM contratos_jogador WHERE tipoContrato > 0) t3
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
                        a.id, a.nome, a.Sexo, a.status, a.Pais as paisTime, p.nome as nomePais, a.escudo  
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

        function exportacao($idPais = null, $idTime = null, $idLiga = null, $multiple = null){

			$multiple = htmlspecialchars(strip_tags($multiple));
			$idPais = htmlspecialchars(strip_tags($idPais));
			$idTime = htmlspecialchars(strip_tags($idTime));
			$idLiga = htmlspecialchars(strip_tags($idLiga));
			

            if($idPais != null){
              $subquery = " Pais=:pais  ";
            } else if($idTime != null && ($multiple === null || !$multiple)){
              $subquery = " ID=:clube ";
            } else if($idLiga != null) {
			  $subquery = " liga=:liga ";
			} else if($idTime != null && $multiple){
				$teams = explode(",",$idTime);
				$subquery = " ID IN ( ";
				$first = true;
				foreach($teams as $key => $team){
					if(!$first){
						$subquery .= ",";
					} else {
						$first = !$first;
					}
					$subquery .= " ? ";
				}
			    $subquery .= " ) ";
			}

            $query = "SELECT DISTINCT c.ID, c.Nome, c.TresLetras, c.Estadio, c.Escudo, c.Uni1Cor1, c.Uni1Cor2, c.Uni1Cor3, c.Uni2Cor1, c.Uni2Cor2, c.Uni2Cor3, c.Uniforme1, c.Uniforme2, c.MaxTorcedores, c.Fidelidade, c.Sexo  FROM clube c WHERE " . $subquery;
			
            $stmt = $this->conn->prepare( $query );

            if($idPais != null){
              $stmt->bindParam(":pais", $idPais);
            } else if($idTime != null  && ($multiple === null || !$multiple) ){

              $stmt->bindParam(":clube", $idTime);
            } else if($idLiga != null){
              $stmt->bindParam(":liga", $idLiga);
            } else if($idTime != null && $multiple){
				foreach($teams as $key => $team){
					$stmt->bindValue($key + 1, $team);
				}
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

        function getElenco($idClube, $multiple = null){
            $idClube = htmlspecialchars(strip_tags($idClube));

			if($multiple === null || !$multiple){
				$query = "SELECT jogador as ID, clube FROM contratos_jogador WHERE clube = ? AND titularidade >= 0";
				$stmt = $this->conn->prepare( $query );
				$stmt->bindParam(1, $idClube);

			} else {
				$teams = explode(",",$idClube);
				$subquery = " clube IN ( ";
				$first = true;
				foreach($teams as $key => $team){
					if(!$first){
						$subquery .= ",";
					} else {
						$first = !$first;
					}
					$subquery .= " ? ";
				}
			    $subquery .= " ) ";
				
				$query = "SELECT jogador as ID, clube FROM contratos_jogador WHERE ". $subquery." AND titularidade >= 0";
				$stmt = $this->conn->prepare( $query );
				
				foreach($teams as $key => $team){
					$stmt->bindValue($key + 1, $team);
				}
			}

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

        function getTecnico($idClube, $multiple = null){
            $idClube = htmlspecialchars(strip_tags($idClube));
			
			if($multiple === null || !$multiple){
				$query = "SELECT tecnico, clube FROM contratos_tecnico WHERE clube = ? LIMIT 0,1";
				$stmt = $this->conn->prepare( $query );
				$stmt->bindParam(1, $idClube);

			} else {
				$teams = explode(",",$idClube);
				$subquery = " clube IN ( ";
				$first = true;
				foreach($teams as $key => $team){
					if(!$first){
						$subquery .= ",";
					} else {
						$first = !$first;
					}
					$subquery .= " ? ";
				}
			    $subquery .= " ) ";
				
				$query = "SELECT tecnico, clube FROM contratos_tecnico WHERE " . $subquery ;
				$stmt = $this->conn->prepare( $query );
				
				
				
				foreach($teams as $key => $team){
					$stmt->bindValue($key + 1, $team);
				
				}
			}


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
           if($idUsuario != null){
			 $idUsuario = htmlspecialchars(strip_tags($idUsuario));  
		   } 

			if($idUsuario != null){
			  $query = "SELECT c.Nome, count(CASE WHEN t.jogador IS NOT NULL THEN t.jogador ELSE 0 END) as total FROM `contratos_jogador` t RIGHT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? GROUP BY c.Nome HAVING total <13";
			  $stmt = $this->conn->prepare( $query );
			  $stmt->bindParam(1, $idUsuario);
			} else {
			  $subquery = " c.ID = ? ";
			  if(is_array($listaTimesExportados)){
				  $totalTimes = count($listaTimesExportados);
			  } else {
				  $totalTimes = 1;
			  }
			  
			  for($i = 1;$i < $totalTimes;$i++){
				$subquery .= " OR c.ID = ? ";
			  }
			  
			  $query = "SELECT c.Nome, count(CASE WHEN t.jogador IS NOT NULL THEN t.jogador ELSE 0 END) as total FROM `contratos_jogador` t RIGHT JOIN clube c ON c.id = t.clube WHERE ".$subquery." GROUP BY c.Nome HAVING total <13";
			  $stmt = $this->conn->prepare($query);
			  if(is_array($listaTimesExportados)){
				for($j = 0; $j < $totalTimes ; $j++){
					$stmt->bindParam($j+1, $listaTimesExportados[$j]);
				}
			  } else {
				  $stmt->bindParam(1, $listaTimesExportados);
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
           if($idUsuario != null){
			 $idUsuario = htmlspecialchars(strip_tags($idUsuario));  
		   } 

			if($idUsuario != null){
			  $query = "SELECT c.Nome, count(t.jogador) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? AND t.titularidade <> -1 GROUP BY clube HAVING count(t.jogador)>23";
			  $stmt = $this->conn->prepare( $query );
			  $stmt->bindParam(1, $idUsuario);
			} else {
			  $subquery = " c.ID = ? ";
			  if(is_array($listaTimesExportados)){
				  $totalTimes = count($listaTimesExportados);
			  } else {
				  $totalTimes = 1;
			  }
			  for($i = 1;$i < $totalTimes;$i++){
				$subquery .= " OR c.ID = ? ";
			  }
			  $query = "SELECT c.Nome, count(t.jogador) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube WHERE (".$subquery.") AND t.titularidade <> -1 GROUP BY clube HAVING count(t.jogador)>23";
			  $stmt = $this->conn->prepare($query);
			  if(is_array($listaTimesExportados)){
				for($j = 0; $j < $totalTimes ; $j++){
					$stmt->bindParam($j+1, $listaTimesExportados[$j]);
				}
			  } else {
				  $stmt->bindParam(1, $listaTimesExportados);
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
           if($idUsuario != null){
			 $idUsuario = htmlspecialchars(strip_tags($idUsuario));  
		   } 

            if($idUsuario != null){
              $query = "SELECT c.Nome, sum(t.capitao * t.titularidade) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? GROUP BY clube HAVING sum(t.capitao * t.titularidade) != 1";
              $stmt = $this->conn->prepare( $query );
              $stmt->bindParam(1, $idUsuario);
            } else {
              $subquery = " c.ID = ? ";
			  if(is_array($listaTimesExportados)){
				  $totalTimes = count($listaTimesExportados);
			  } else {
				  $totalTimes = 1;
			  }
              for($i = 1;$i < $totalTimes;$i++){
                $subquery .= " OR c.ID = ? ";
              }
              $query = "SELECT c.Nome, sum(t.capitao * t.titularidade) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube WHERE ".$subquery." GROUP BY clube HAVING sum(t.capitao * t.titularidade) != 1";
              $stmt = $this->conn->prepare($query);
			  if(is_array($listaTimesExportados)){
				for($j = 0; $j < $totalTimes ; $j++){
					$stmt->bindParam($j+1, $listaTimesExportados[$j]);
				}
			  } else {
				  $stmt->bindParam(1, $listaTimesExportados);
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
           if($idUsuario != null){
			 $idUsuario = htmlspecialchars(strip_tags($idUsuario));  
		   } 

            if($idUsuario != null){
              $query = "SELECT c.Nome, count(CASE WHEN t.tecnico IS NOT NULL THEN t.tecnico ELSE 0 END) as total FROM `contratos_tecnico` t RIGHT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? GROUP BY clube HAVING total <> 1";
              $stmt = $this->conn->prepare( $query );
              $stmt->bindParam(1, $idUsuario);
            } else {
              $subquery = " c.ID = ? ";
			  if(is_array($listaTimesExportados)){
				  $totalTimes = count($listaTimesExportados);
			  } else {
				  $totalTimes = 1;
			  }
              for($i = 1;$i < $totalTimes;$i++){
                $subquery .= " OR c.ID = ? ";
              }
              $query = "SELECT c.Nome, count(CASE WHEN t.tecnico IS NOT NULL THEN t.tecnico ELSE 0 END) as total FROM `contratos_tecnico` t RIGHT JOIN clube c ON c.id = t.clube WHERE ".$subquery." GROUP BY clube HAVING total <> 1";
              $stmt = $this->conn->prepare($query);
			  if(is_array($listaTimesExportados)){
				for($j = 0; $j < $totalTimes ; $j++){
					$stmt->bindParam($j+1, $listaTimesExportados[$j]);
				}
			  } else {
				  $stmt->bindParam(1, $listaTimesExportados);
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
           if($idUsuario != null){
			 $idUsuario = htmlspecialchars(strip_tags($idUsuario));  
		   } 

            if($idUsuario != null){
              $query = "SELECT c.Nome, sum(t.cobrancaPenalti * t.titularidade) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? GROUP BY clube HAVING sum(t.cobrancaPenalti * t.titularidade) != 6";
              $stmt = $this->conn->prepare( $query );
              $stmt->bindParam(1, $idUsuario);
            } else {
              $subquery = " c.ID = ? ";
			  if(is_array($listaTimesExportados)){
				  $totalTimes = count($listaTimesExportados);
			  } else {
				  $totalTimes = 1;
			  }
              for($i = 1;$i < $totalTimes;$i++){
                $subquery .= " OR c.ID = ? ";
              }
              $query = "SELECT c.Nome, sum(t.cobrancaPenalti * t.titularidade) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube WHERE ".$subquery." GROUP BY clube HAVING sum(t.cobrancaPenalti * t.titularidade) != 6";
              $stmt = $this->conn->prepare($query);
			  if(is_array($listaTimesExportados)){
				for($j = 0; $j < $totalTimes ; $j++){
					$stmt->bindParam($j+1, $listaTimesExportados[$j]);
				}
			  } else {
				  $stmt->bindParam(1, $listaTimesExportados);
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
           if($idUsuario != null){
			 $idUsuario = htmlspecialchars(strip_tags($idUsuario));  
		   } 

            if($idUsuario != null){
              $query = "SELECT c.Nome, sum(case when (t.posicaoBase = 1 AND t.titularidade = 1) THEN 1 ELSE 0 END) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? GROUP BY clube HAVING total != 1";
              $stmt = $this->conn->prepare( $query );
              $stmt->bindParam(1, $idUsuario);
            } else {
              $subquery = " c.ID = ? ";
			  if(is_array($listaTimesExportados)){
				  $totalTimes = count($listaTimesExportados);
			  } else {
				  $totalTimes = 1;
			  }
              for($i = 1;$i < $totalTimes;$i++){
                $subquery .= " OR c.ID = ? ";
              }
              $query = "SELECT c.Nome, sum(case when (t.posicaoBase = 1 AND t.titularidade = 1) THEN 1 ELSE 0 END) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube WHERE ".$subquery." GROUP BY clube HAVING total != 1";
              $stmt = $this->conn->prepare($query);
			  if(is_array($listaTimesExportados)){
				for($j = 0; $j < $totalTimes ; $j++){
					$stmt->bindParam($j+1, $listaTimesExportados[$j]);
				}
			  } else {
				  $stmt->bindParam(1, $listaTimesExportados);
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
           if($idUsuario != null){
			 $idUsuario = htmlspecialchars(strip_tags($idUsuario));  
		   } 

            if($idUsuario != null){
              $query = "SELECT c.Nome, sum(case when t.titularidade = 1 THEN 1 ELSE 0 END) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id WHERE p.dono = ? GROUP BY clube HAVING total != 11";
              $stmt = $this->conn->prepare( $query );
              $stmt->bindParam(1, $idUsuario);
            } else {
              $subquery = " c.ID = ? ";
			  if(is_array($listaTimesExportados)){
				  $totalTimes = count($listaTimesExportados);
			  } else {
				  $totalTimes = 1;
			  }
              for($i = 1;$i < $totalTimes;$i++){
                $subquery .= " OR c.ID = ? ";
              }
              $query = "SELECT c.Nome, sum(case when t.titularidade = 1 THEN 1 ELSE 0 END) as total FROM `contratos_jogador` t LEFT JOIN clube c ON c.id = t.clube  WHERE ".$subquery." GROUP BY clube HAVING total != 11";
              $stmt = $this->conn->prepare($query);
			  if(is_array($listaTimesExportados)){
				for($j = 0; $j < $totalTimes ; $j++){
					$stmt->bindParam($j+1, $listaTimesExportados[$j]);
				}
			  } else {
				  $stmt->bindParam(1, $listaTimesExportados);
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
           if($idUsuario != null){
			 $idUsuario = htmlspecialchars(strip_tags($idUsuario));  
		   } 

            if($idUsuario != null){
              $query = "SELECT c.Nome, sum(case when FLOOR((DATEDIFF(CURDATE(), j.Nascimento))/365) > 45 THEN 1 ELSE 0 END) as total FROM contratos_jogador t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN paises p ON c.Pais = p.id LEFT JOIN jogador j ON j.ID = t.jogador WHERE p.dono = ? GROUP BY clube HAVING total > 0";
              $stmt = $this->conn->prepare( $query );
              $stmt->bindParam(1, $idUsuario);
            } else {
              $subquery = " c.ID = ? ";
			  if(is_array($listaTimesExportados)){
				  $totalTimes = count($listaTimesExportados);
			  } else {
				  $totalTimes = 1;
			  }
              for($i = 1;$i < $totalTimes;$i++){
                $subquery .= " OR c.ID = ? ";
              }
              $query = "SELECT c.Nome, sum(case when FLOOR((DATEDIFF(CURDATE(), j.Nascimento))/365) > 45 THEN 1 ELSE 0 END) as total FROM contratos_jogador t LEFT JOIN clube c ON c.id = t.clube LEFT JOIN jogador j ON j.ID = t.jogador WHERE ".$subquery." GROUP BY clube HAVING total > 0";
              $stmt = $this->conn->prepare($query);
			  if(is_array($listaTimesExportados)){
				for($j = 0; $j < $totalTimes ; $j++){
					$stmt->bindParam($j+1, $listaTimesExportados[$j]);
				}
			  } else {
				  $stmt->bindParam(1, $listaTimesExportados);
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

        function alterarElenco($idJogador1, $idJogador2,$tipoAlteracao,$posJogador1 = null,$posJogador2 = null, $time = null){
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
        $this->nome=htmlspecialchars(strip_tags($this->nome), $double_encode = false);
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
		
		$this->nome = str_replace("amp;amp;","amp;",$this->nome);

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
        WHERE status_execucao = 1 AND o.tipoContrato = 0 
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
            $sub_query_fim = ") t1 WHERE idDonoPais = ? AND status = 0 AND (Nome LIKE ? OR TresLetras LIKE ?) ORDER BY Nome ASC LIMIT 150";

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

    //ler todos os jogadores para o quadro
function readAllMultiLeague($ligas){

	$subquery = " liga = ? ";
	$totalLigas = count($ligas);
	for($i = 1;$i < $totalLigas;$i++){
		$subquery .= " OR liga = ? ";
	}

    $query = "SELECT ID FROM " . $this->table_name . " WHERE " . $subquery;



	$stmt = $this->conn->prepare( $query );
	for($j = 0; $j < $totalLigas ; $j++){
		$stmt->bindParam($j+1, $ligas[$j]);
	}
	$stmt->execute();
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
		extract($row);
		$listaTimes[] = $ID;
	}

	return $listaTimes;



}

function getName($idClube){
	        $idClube = htmlspecialchars(strip_tags($idClube));

            $query = "SELECT Nome FROM " . $this->table_name . " WHERE ID = ?";
            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1, $idClube);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getDono($idClube){
	        $idClube = htmlspecialchars(strip_tags($idClube));

            $query = "SELECT p.dono FROM " . $this->table_name . " c LEFT JOIN paises p ON p.ID = c.Pais WHERE c.ID = ?";
            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1, $idClube);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
}

function readExtraInfo($id){

        $id = htmlspecialchars(strip_tags($id));

    $query = "SELECT
                apelido, fundacao, cidade, patrocinio, material_esportivo, titulos, sobre_titulo, sobre_subtitulo, sobre_texto    
            FROM
                " . $this->table_name . " a
            WHERE
                a.id={$id}";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();
    $info = $stmt->fetch(PDO::FETCH_ASSOC);




    return $info;



    }
	
	function alterarSobre($idTime,$cidade,$fundacao,$apelido,$patrocinio,$material_esportivo,$titulos,$sobre_titulo,$sobre_subtitulo,$sobre_texto){
		
			$idTime = htmlspecialchars(strip_tags($idTime));
			$cidade = trim(htmlspecialchars(strip_tags($cidade)));
			$fundacao = trim(htmlspecialchars(strip_tags($fundacao)));
			$apelido = trim(htmlspecialchars(strip_tags($apelido)));
			$patrocinio = trim(htmlspecialchars(strip_tags($patrocinio)));
			$material_esportivo = trim(htmlspecialchars(strip_tags($material_esportivo)));
			$titulos = trim(htmlspecialchars(strip_tags($titulos)));
			$sobre_titulo = trim(htmlspecialchars(strip_tags($sobre_titulo)));
			$sobre_subtitulo = trim(htmlspecialchars(strip_tags($sobre_subtitulo)));
			$sobre_texto = trim($sobre_texto);
		
		$query = "UPDATE " . $this->table_name . " SET 
					cidade=:cidade,  
					fundacao=:fundacao,
                    apelido=:apelido,
                    patrocinio=:patrocinio,
                    material_esportivo=:material_esportivo,
                    titulos=:titulos,
                    sobre_titulo=:sobre_titulo,
                    sobre_subtitulo=:sobre_subtitulo,
                    sobre_texto=:sobre_texto 
                    WHERE ID=:idTime";

        $stmt = $this->conn->prepare($query);

        // bind values
        $stmt->bindParam(":cidade", $cidade);
        $stmt->bindParam(":fundacao", $fundacao);
        $stmt->bindParam(":apelido", $apelido);
        $stmt->bindParam(":patrocinio", $patrocinio);
        $stmt->bindParam(":material_esportivo", $material_esportivo);
        $stmt->bindParam(":titulos", $titulos);
        $stmt->bindParam(":sobre_titulo", $sobre_titulo);
        $stmt->bindParam(":sobre_subtitulo", $sobre_subtitulo);
        $stmt->bindParam(":sobre_texto", $sobre_texto);
        $stmt->bindParam(":idTime", $idTime);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }
		
		
	}
	
	    function createSqlite(){

        //escrever query
        $query = "INSERT INTO
                    clube(ID, Nome, TresLetras, Estadio, Escudo, Uni1Cor1, Uni1Cor2, Uni1Cor3, Uni2Cor1, Uni2Cor2, Uni2Cor3, Uniforme1, Uniforme2, MaxTorcedores, Fidelidade)
                VALUES
                    (:id,:nome,:sigla,:estadio,:escudo,:uniforme1cor1,:uniforme1cor2,:uniforme1cor3,:uniforme2cor1,:uniforme2cor2,:uniforme2cor3,:uniforme1,:uniforme2, :maxTorcedores,:fidelidade) 
				ON CONFLICT DO NOTHING";

        $stmt = $this->conn->prepare($query);

        // posted values
		$this->id=htmlspecialchars(strip_tags($this->id));
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

        // bind values
		$stmt->bindParam(":id", $this->id);
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
	
	function inserirElencosSqlite($codigo_time, $arrayJogadores){
		//escrever query
        $query = "INSERT INTO
                    elenco(Clube, Jogador1, Jogador2, Jogador3, Jogador4, Jogador5, Jogador6, Jogador7, Jogador8, Jogador9, Jogador10, Jogador11, Jogador12, Jogador13, Jogador14, Jogador15, Jogador16, Jogador17, Jogador18, Jogador19, Jogador20, Jogador21, Jogador22, Jogador23, Tecnico) 
                VALUES 
                    (:id,:jogador1,:jogador2,:jogador3,:jogador4,:jogador5,:jogador6,:jogador7,:jogador8,:jogador9,:jogador10,:jogador11,:jogador12,:jogador13,:jogador14,:jogador15,:jogador16,:jogador17,:jogador18,:jogador19,:jogador20,:jogador21,:jogador22,:jogador23, :tecnico)
				ON CONFLICT DO NOTHING";
        $stmt = $this->conn->prepare($query);

        // posted values
		$codigo_time=htmlspecialchars(strip_tags($codigo_time));

        // bind values
		$stmt->bindParam(":id", $codigo_time);
		$stmt->bindParam(":tecnico", $codigo_time);
		
		$lastOne = 1;
		
		foreach($arrayJogadores as $key => $jogadorUnico){
			$codigo_jogador = $codigo_time * 1000 - $key;
			$paramBinder = ":jogador" . ($key + 1);
			$stmt->bindValue($paramBinder, $codigo_jogador);
			$lastOne++;
		}
		
		if($lastOne<23){
			$paramBinder = ":jogador" . $lastOne;
			$stmt->bindParam($paramBinder, '0');
		}
        

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
	
	function inserirEscalacaoSqlite($codigo_time, $capitaoNewId, $penaltisNewArray, $titularesNewArray){
		//escrever query
        $query = "INSERT INTO
                    escalacao(Clube, Pos1, Jogador1, Pos2, Jogador2, Pos3, Jogador3, Pos4, Jogador4, Pos5, Jogador5, Pos6, Jogador6, Pos7, Jogador7, Pos8, Jogador8, Pos9, Jogador9, Pos10, Jogador10, Pos11, Jogador11, Capitao, Penalti1, Penalti2, Penalti3)
                VALUES 
                    (:id,:pos1,:jogador1,:pos2,:jogador2, :pos3, :jogador3,:pos4,:jogador4, :pos5, :jogador5,:pos6, :jogador6, :pos7, :jogador7, :pos8,:jogador8, :pos9, :jogador9, :pos10, :jogador10, :pos11, :jogador11, :capitao, :penalti1,:penalti2, :penalti3) 
				ON CONFLICT DO NOTHING";

        $stmt = $this->conn->prepare($query);

        // posted values
		$codigo_time=htmlspecialchars(strip_tags($codigo_time));
		$capitaoNewId=htmlspecialchars(strip_tags($capitaoNewId));

        // bind values
		$stmt->bindParam(":id", $codigo_time);
		$stmt->bindParam(":capitao", $capitaoNewId);
		
		$stmt->bindParam(":penalti1" , $penaltisNewArray[1]);
		$stmt->bindParam(":penalti2" , $penaltisNewArray[2]);
		$stmt->bindParam(":penalti3" , $penaltisNewArray[3]);
		
		$playerCounter = 1;
	
		foreach($titularesNewArray as $key => $posicaoJogador){
			$paramBinder = ":pos" . $playerCounter;
			$stmt->bindValue($paramBinder, $posicaoJogador);
			$paramBinder = ":jogador" . $playerCounter;
			$stmt->bindValue($paramBinder, $key);
			$playerCounter++;
		}

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

	function encontrarTimeExterno($codigo_time){
		
		$codigo_time = htmlspecialchars(strip_tags($codigo_time));
		
		$codigo_time = -1 * $codigo_time;
		
		$query = "SELECT Nome, Escudo FROM clube WHERE ID = :codigo_time";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(":codigo_time", $codigo_time);

        $stmt->execute();
		$teamInfo = $stmt->fetch(PDO::FETCH_ASSOC);
		return $teamInfo;
		
	}
	
	function carregarListaTimesSqlite(){
	
		
		$query = "SELECT ID, Nome FROM clube";
        $stmt = $this->conn->prepare( $query );

        $stmt->execute();
		return $stmt;
		
	}
	
	function getSigla($idTime){
		
		$idTime = htmlspecialchars(strip_tags($idTime));

		$query = "SELECT
                TresLetras    
            FROM
                clube 
            WHERE
                ID = :id";

    $stmt = $this->conn->prepare( $query );
	$stmt->bindParam(":id", $idTime);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
	$sigla = $result['TresLetras'];
	
    return $sigla;
		
	}


}
