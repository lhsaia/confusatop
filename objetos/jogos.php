<?php
class Jogo{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "jogos";

    // object properties
    public $id;
    public $timeA_id;
    public $timeA_gols;
    public $timeB_id;
    public $timeB_gols;
    public $timeA_penaltis;
    public $timeB_penaltis;
    public $data;
    public $campeonato;
    public $fase;
    public $timeA_rankingatual;
    public $timeB_rankingatual;
    public $timeA_pontos;
    public $timeB_pontos;
    public $calculado;


    public function __construct($db){
        $this->conn = $db;
    }

     // inserir jogo
    function inserir(){

        //write query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    timeA_id=:timeA_id, timeA_gols=:timeA_gols, timeB_id=:timeB_id, timeB_gols=:timeB_gols, timeA_penaltis=:timeA_penaltis, timeB_penaltis=:timeB_penaltis, data=:data, campeonato=:campeonato, calculado=0 ";

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->timeA_id=htmlspecialchars(strip_tags($this->timeA_id));
        $this->timeA_gols=htmlspecialchars(strip_tags($this->timeA_gols));
        $this->timeB_id=htmlspecialchars(strip_tags($this->timeB_id));
        $this->timeB_gols=htmlspecialchars(strip_tags($this->timeB_gols));
        $this->timeA_penaltis=htmlspecialchars(strip_tags($this->timeA_penaltis));
        $this->timeB_penaltis=htmlspecialchars(strip_tags($this->timeB_penaltis));
        $this->data=htmlspecialchars(strip_tags($this->data));
        $this->campeonato=htmlspecialchars(strip_tags($this->campeonato));


        //verificar se jogo não existe
        $tag_comparacao = "{$this->timeA_id}-{$this->timeB_id}-{$this->data}";


        $query_comparacao = "SELECT timeA_id,timeB_id,data FROM ". $this->table_name . " WHERE timeA_id = ? AND timeB_id = ? AND data = ?";
        $stmt_comparacao = $this->conn->prepare($query_comparacao);
        $stmt_comparacao->bindParam(1, $this->timeA_id);
        $stmt_comparacao->bindParam(2, $this->timeB_id);
        $stmt_comparacao->bindParam(3, $this->data);
        $stmt_comparacao->execute();
        $result_comp = $stmt_comparacao->fetch(PDO::FETCH_ASSOC);
        $tag_atual = "{$result_comp['timeA_id']}-{$result_comp['timeB_id']}-{$result_comp['data']}";

        // bind values
        $stmt->bindParam(":timeA_id", $this->timeA_id);
        $stmt->bindParam(":timeA_gols", $this->timeA_gols);
        $stmt->bindParam(":timeB_id", $this->timeB_id);
        $stmt->bindParam(":timeB_gols", $this->timeB_gols);
        $stmt->bindParam(":timeA_penaltis", $this->timeA_penaltis);
        $stmt->bindParam(":timeB_penaltis", $this->timeB_penaltis);
        $stmt->bindParam(":data", $this->data);
        $stmt->bindParam(":campeonato", $this->campeonato);

        if(strcmp($tag_atual, $tag_comparacao)){
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }


    }



    //selecionar jogos não calculados do ranking
    function selecionarNaoCalculados(){

    $query = "SELECT
                id, timeA_id, timeA_gols, timeB_id, timeB_gols, data, campeonato, CASE WHEN calculado = 0 THEN 'Sim' ELSE 'Não' END AS calculado
            FROM
                " . $this->table_name . "
            WHERE
                calculado=0
            ORDER BY
                data ASC";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    return $stmt;
    }

    //selecionar todos os jogos de um time
        function selecionarJogosTime($id_time,$from_record_num, $records_per_page){

            $id_time = htmlspecialchars(strip_tags($id_time));

    $query = "SELECT
             j.id as idJogo, p.nome as nomeA, p.bandeira as bandeiraA,  j.timeA_gols, j.timeB_gols, c.nome as nomeB, c.bandeira as bandeiraB, j.data, l.nome as nomeCampeonato, CASE WHEN j.calculado = 0 THEN 'Não' ELSE 'Sim' END AS calculo, j.timeA_penaltis, j.timeB_penaltis, p.id as idA, c.id as idB
            FROM
                jogos j
            LEFT JOIN
                paises p
            ON
                j.timeA_id = p.id
           LEFT JOIN
              paises c
            ON
               j.timeB_id = c.id
           LEFT JOIN
             campeonatos l
             ON
             j.campeonato = l.id
            WHERE
                timeA_id= ? OR timeB_id= ?
            ORDER BY
                data DESC
            LIMIT
                {$from_record_num}, {$records_per_page}";

    $stmt = $this->conn->prepare( $query );
    $stmt->bindParam(1,$id_time);
    $stmt->bindParam(2,$id_time);
    $stmt->execute();

    return $stmt;
    }

    //gravar pontos nos jogos
    function atualizarJogo($id, $timeA_pontos, $timeB_pontos, $timeA_rankingatual, $timeB_rankingatual){

        $id = htmlspecialchars(strip_tags($id));
        $timeA_pontos = htmlspecialchars(strip_tags($timeA_pontos));
        $timeB_pontos = htmlspecialchars(strip_tags($timeB_pontos));
        $timeA_rankingatual = htmlspecialchars(strip_tags($timeA_rankingatual));
        $timeB_rankingatual = htmlspecialchars(strip_tags($timeB_rankingatual));

    $query = "UPDATE
                " . $this->table_name . "
             SET
                calculado = '1', timeA_pontos = $timeA_pontos, timeB_pontos = $timeB_pontos, timeA_rankingatual = $timeA_rankingatual, timeB_rankingatual = $timeB_rankingatual
             WHERE
                id=$id";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    return $stmt;
    }


    public function countAll(){

    $query = "SELECT id FROM " . $this->table_name . "";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    $num = $stmt->rowCount();

    return $num;
    }

    public function countAllSingleTeam($timeId){

        $timeId = htmlspecialchars(strip_tags($timeId));

    $query = "SELECT id FROM " . $this->table_name . " WHERE timeA_id = ? OR timeB_id = ? ";

    $stmt = $this->conn->prepare( $query );
    $stmt->bindParam(1,$timeId);
    $stmt->bindParam(2,$timeId);
    $stmt->execute();

    $num = $stmt->rowCount();

    return $num;
    }

    public function recuperarInfoTime($id){

        $id = htmlspecialchars(strip_tags($id));


        $query = "SELECT SUM(if((timeA_gols > timeB_gols AND timeA_id = ?) OR (timeA_gols < timeB_gols AND timeB_id = ?) , 1, 0)) AS vitorias, SUM(case when timeA_id = ? then timeA_gols else 0 end) as golsProMandante, SUM(case when timeB_id = ? then timeB_gols else 0 end) as golsProVisitante, SUM(case when timeA_id = ? then timeB_gols else 0 end) as golsContraMandante, SUM(case when timeB_id = ? then timeA_gols else 0 end) as golsContraVisitante, SUM(if((timeA_gols < timeB_gols AND timeA_id = ?) OR (timeA_gols > timeB_gols AND timeB_id = ?) , 1, 0)) AS derrotas, SUM(if((timeA_gols = timeB_gols AND timeA_id = ?) OR (timeA_gols = timeB_gols AND timeB_id = ?) , 1, 0)) AS empates FROM " . $this->table_name . "";

    $stmt = $this->conn->prepare( $query );
    $stmt->bindParam(1, $id);
    $stmt->bindParam(2, $id);
    $stmt->bindParam(3, $id);
    $stmt->bindParam(4, $id);
    $stmt->bindParam(5, $id);
    $stmt->bindParam(6, $id);
    $stmt->bindParam(7, $id);
    $stmt->bindParam(8, $id);
    $stmt->bindParam(9, $id);
    $stmt->bindParam(10, $id);
    $stmt->execute();

    return $stmt;
    }

    function ganhoPontos($id,$maior){
        $id = htmlspecialchars(strip_tags($id));

        $individual_query = "WHERE j.timeA_id = ?
        UNION
        SELECT
            timeB_id as time,
            timeB_gols as timeGols,
            timeA_gols as adversarioGols,
            timeA_id as adversarioId,
            data,
            campeonato,
            timeB_pontos as pontos
        FROM jogos j
        WHERE timeB_id = ? ";

        $multiple_query = " UNION
        SELECT
            timeB_id as time,
            timeB_gols as timeGols,
            timeA_gols as adversarioGols,
            timeA_id as adversarioId,
            data,
            campeonato,
            timeB_pontos as pontos
        FROM jogos j ";

        if($maior == '1'){
            $ord = 'DESC';
        } else {
            $ord = 'ASC';
        }

        if($id != 0){
            $sub_query = $individual_query;
            $up_limit = 3;
        } else {
            $sub_query = $multiple_query;
            $up_limit = 10;
        }


        $query = "SELECT p.nome as nomeTime, timeGols, adversarioGols, c.nome as nomeAdversario, data, l.nome as nomeCampeonato, t1.pontos FROM

                        (SELECT
                            j.timeA_id as time,
                            j.timeA_gols as timeGols,
                            j.timeB_gols as adversarioGols,
                            j.timeB_id as adversarioId,
                            j.data,
                            j.campeonato,
                            j.timeA_pontos as pontos
                        FROM jogos j ".
                        $sub_query.
                        "ORDER BY pontos ".$ord."
                        LIMIT 0,".$up_limit.") t1

                        LEFT JOIN
                            paises p
                        ON
                            t1.time = p.id
                        LEFT JOIN
                            paises c
                        ON
                            t1.adversarioId = c.id
                        LEFT JOIN
                            campeonatos l
                        ON
                            t1.campeonato = l.id ";

        $stmt = $this->conn->prepare( $query );
        if($id != 0){
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $id);
        }
        $stmt->execute();

        return $stmt;
    }


    function adversariosMaisEnfrentados($id){
        $id = htmlspecialchars(strip_tags($id));

        if($id == 0){
            $append_a = "SELECT least(timeB_id, timeA_id) as time, greatest(timeA_id, timeB_id) as adversario, id
            FROM jogos ";
        } else {
        $append_a ="SELECT timeB_id as time, timeA_id as adversario, id
        FROM jogos WHERE timeA_id = ?
         UNION
        SELECT timeA_id as time, timeB_id as adversario, id
        FROM jogos WHERE timeB_id = ?";
        }

        $query = "SELECT p.nome as nomeTime, c.nome as nomeAdversario, t1.contagem FROM

                        (SELECT time, adversario, count(*) as contagem, CONCAT(time,'-',adversario) as idConfronto
                        FROM
                            (".$append_a.") t2
                        GROUP BY idConfronto
                        ORDER BY contagem DESC
                        LIMIT 0,10) t1

                    LEFT JOIN paises p
                    ON t1.time = p.id
                    LEFT JOIN paises c
                    ON t1.adversario = c.id";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $id);
        $stmt->execute();

        return $stmt;
    }

    function maioresVitorias($id){
        $id = htmlspecialchars(strip_tags($id));

        if($id == 0){
            $append_a = "";
            $append_b = "";
        } else {
            $append_a = "WHERE timeA_id = ? ";
            $append_b = "WHERE timeB_id = ? ";
        }

        $query = "SELECT p.nome as nomeTime, t1.golsPro as timeGols, t1.golsContra as adversarioGols, c.nome as nomeAdversario, t1.data, l.nome as nomeCampeonato FROM

            (SELECT timeA_id as time, timeA_gols as golsPro, timeB_gols as golsContra, timeB_id as adversario,
                (timeA_gols - timeB_gols) as golsSaldo, id, data, campeonato
            FROM jogos
             ".$append_a."
            UNION
            SELECT timeB_id as time, timeB_gols as golsPro, timeA_gols as golsContra, timeA_id as adversario,
                (timeB_gols - timeA_gols) as golsSaldo, id, data, campeonato
            FROM jogos
             ".$append_b."
            ORDER BY golsSaldo DESC, golsPro DESC
            LIMIT 0,10) t1

        LEFT JOIN paises p
        ON t1.time = p.id
        LEFT JOIN paises c
        ON t1.adversario = c.id
        LEFT JOIN campeonatos l
        ON t1.campeonato = l.id
        WHERE t1.golsSaldo > 0";

        $stmt = $this->conn->prepare( $query );
        if($id != 0){
            $stmt->bindParam(1, $id);
            $stmt->bindParam(2, $id);
        }

        $stmt->execute();

        return $stmt;
    }

    function maioresDerrotas($id){
        $id = htmlspecialchars(strip_tags($id));
        $query = "SELECT p.nome as nomeTime, t1.golsPro as timeGols, t1.golsContra as adversarioGols, c.nome as nomeAdversario, t1.data, l.nome as nomeCampeonato FROM

            (SELECT timeA_id as time, timeA_gols as golsPro, timeB_gols as golsContra, timeB_id as adversario,
                (timeA_gols - timeB_gols) as golsSaldo, id, data, campeonato
            FROM jogos
            WHERE timeA_id = ?
            UNION
            SELECT timeB_id as time, timeB_gols as golsPro, timeA_gols as golsContra, timeA_id as adversario,
                (timeB_gols - timeA_gols) as golsSaldo, id, data, campeonato
            FROM jogos
            WHERE timeB_id = ?
            ORDER BY golsSaldo ASC, golsContra DESC
            LIMIT 0,10) t1

        LEFT JOIN paises p
        ON t1.time = p.id
        LEFT JOIN paises c
        ON t1.adversario = c.id
        LEFT JOIN campeonatos l
        ON t1.campeonato = l.id
        WHERE t1.golsSaldo < 0";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $id);
        $stmt->execute();

        return $stmt;
    }

    function maisVitoriasEmpatesDerrotas($id, $resultado){
        $id = htmlspecialchars(strip_tags($id));
        $resultado = htmlspecialchars(strip_tags($resultado));
        if($id == 0){
            if($resultado == 'V'){
                $append_a = " ";
                $append_b = " ";
            } else if($resultado == 'E'){
                $append_a = " WHERE timeA_id > timeB_id ";
                $append_b = " WHERE timeB_id > timeA_id ";
            }

        } else {
        $append_a = " WHERE timeA_id = ? ";
        $append_b = "WHERE timeB_id = ?";
        }
        $query = "SELECT p.nome as nomeTime, c.nome as nomeAdversario, t1.contagem, t1.resultado FROM
            (SELECT  time, adversario, resultado, count(*) as contagem FROM
                (SELECT timeA_id as time, timeB_id as adversario,
                    if((timeA_gols - timeB_gols)>0, 'V', if((timeA_gols - timeB_gols)<0, 'D', 'E' )) as resultado, id
                FROM jogos
                " .$append_a . "
                UNION
                SELECT timeB_id as time, timeA_id as adversario,
                    if((timeB_gols - timeA_gols)>0, 'V', if((timeB_gols - timeA_gols)<0, 'D', 'E' )) as resultado, id
                FROM jogos
                 ".$append_b . "
                ) t2
            GROUP BY 1, 2, 3
            ORDER BY contagem DESC) t1
        LEFT JOIN paises p
        ON t1.time = p.id
        LEFT JOIN paises c
        ON t1.adversario = c.id
        WHERE t1.resultado = ?
        LIMIT 0,10";

        $stmt = $this->conn->prepare( $query );
       if($id == 0){
        $stmt->bindParam(1, $resultado);
       } else {
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $id);
        $stmt->bindParam(3, $resultado);
       }

        $stmt->execute();

        return $stmt;
    }

    function pesquisaGeral($item_pesquisado){

        $item_pesquisado = htmlspecialchars(strip_tags($item_pesquisado));
        // $from_record_num = htmlspecialchars(strip_tags($from_record_num));
        // $records_per_page = htmlspecialchars(strip_tags($records_per_page));
        $item_pesquisado = '%'.$item_pesquisado.'%';

$query = "SELECT
         p.nome as nomeA, p.bandeira as bandeiraA,  j.timeA_gols as timeAgols, j.timeB_gols as timeBgols, c.nome as nomeB, c.bandeira as bandeiraB, j.data, l.nome as campeonato, CASE WHEN j.calculado = 0 THEN 'Não' ELSE 'Sim' END AS calculo, j.timeA_penaltis as timeApenaltis, j.timeB_penaltis as timeBpenaltis, p.id as idA, c.id as idB, j.id, ABS(j.timeA_pontos) as pontos
        FROM
            jogos j
        LEFT JOIN
            paises p
        ON
            j.timeA_id = p.id
       LEFT JOIN
          paises c
        ON
           j.timeB_id = c.id
       LEFT JOIN
         campeonatos l
         ON
         j.campeonato = l.id
        WHERE
            p.nome LIKE ?
            OR p.bandeira LIKE ?
            OR j.timeA_gols LIKE ?
            OR j.timeB_gols LIKE ?
            OR c.nome LIKE ?
            OR c.bandeira LIKE ?
            OR j.data LIKE ?
            OR l.nome LIKE ?
            OR j.timeA_penaltis LIKE ?
            OR j.timeB_penaltis LIKE ?
            OR j.id LIKE ?
            OR p.id LIKE ?
            OR c.id LIKE ?
        ORDER BY
            data DESC
        -- LIMIT
        --     ?, ?";

$stmt = $this->conn->prepare( $query );
for($i=1;$i<14;$i++){
    $stmt->bindParam($i,$item_pesquisado);
}
// $stmt->bindParam(14,$from_record_num);
// $stmt->bindParam(15,$records_per_page);
$stmt->execute();

return $stmt;
}

public function recuperarInfoGeral(){

    $query = "SELECT SUM(if((timeA_gols != timeB_gols), 1, 0)) AS vitorias, SUM(timeA_gols)+SUM(timeB_gols) as gols, SUM(if((timeA_gols = timeB_gols) , 1, 0)) AS empates, SUM(ABS(timeA_pontos)) as pontosTrocados, count(*) as jogosTotais  FROM " . $this->table_name . "";

$stmt = $this->conn->prepare( $query );
$stmt->execute();

return $stmt;
}

public function getSingleMatchInfo($match_id){

  $match_id = htmlspecialchars(strip_tags($match_id));
  $query = "SELECT jogos.estadio, arbitros.nomeArbitro as nome_arbitro, ta.nome as timeA_nome, ta.bandeira as timeA_bandeira, timeA_id, timeA_gols, tb.nome as timeB_nome, tb.bandeira as timeB_bandeira, timeB_id, timeB_gols, timeA_penaltis, timeB_penaltis, data, campeonatos.nome as competition_name, fase as phase FROM jogos LEFT JOIN campeonatos ON campeonatos.id = jogos.campeonato LEFT JOIN paises ta ON ta.id = jogos.timeA_id  LEFT JOIN paises tb ON tb.id = jogos.timeB_id LEFT JOIN arbitros ON arbitros.id = jogos.cod_arbitro WHERE jogos.id = :id";
  $stmt = $this->conn->prepare( $query );
  $stmt->bindParam(":id", $match_id);
  $stmt->execute();
  $results = $stmt->fetch(PDO::FETCH_ASSOC);
  return $results;
}

public function getSingleMatchEvents($match_id){
  $match_id = htmlspecialchars(strip_tags($match_id));
  $query = "SELECT tempo, minutos, tipo, id_jogador, nome_jogador, id_time FROM jogos_eventos WHERE id_jogo = :id ORDER BY tempo, minutos";
  $stmt = $this->conn->prepare( $query );
  $stmt->bindParam(":id", $match_id);
  $stmt->execute();
  return $stmt;
}

public function importar(){
  $query = "INSERT IGNORE INTO jogos (timeA_id, timeA_gols, timeB_id, timeB_gols, timeA_penaltis, timeB_penaltis, data, campeonato, calculado, estadio, fase) VALUES (?,?,?,?,?,?,?,?,0,?,?)";
  $stmt = $this->conn->prepare( $query );
  $stmt->bindParam(1, $this->timeA_id);
  $stmt->bindParam(2, $this->timeA_gols);
  $stmt->bindParam(3, $this->timeB_id);
  $stmt->bindParam(4, $this->timeB_gols);
  $stmt->bindParam(5, $this->timeA_penaltis);
  $stmt->bindParam(6, $this->timeB_penaltis);
  $stmt->bindParam(7, $this->data);
  $stmt->bindParam(8, $this->campeonato);
  $stmt->bindParam(9, $this->estadio);
  $stmt->bindParam(10, $this->fase);

  if($stmt->execute()){
    return true;
  } else {
    return false;
  }
}

public function importarEventos($log_eventos, $idJogo){
  if($idJogo != 0){
    foreach($log_eventos as $single_event){
      $query = "INSERT INTO jogos_eventos (id_jogo, tempo, minutos, tipo, id_jogador, nome_jogador, id_time) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id_evento = id_evento";
      $stmt = $this->conn->prepare( $query );
      $stmt->bindParam(1, $idJogo);
      $stmt->bindParam(2, $single_event['tempo']);
      $stmt->bindParam(3, $single_event['minutos']);
      $stmt->bindParam(4, $single_event['tipo']);
      $stmt->bindParam(5, $single_event['idJogador']);
      $nomeJogador = addslashes($single_event['nomeJogador']);
      $stmt->bindParam(6, $nomeJogador);
      $stmt->bindParam(7, $single_event['idTime']);
      $stmt->execute();

    }
  }

  return true;
}

public function importarEscalacao($log_escalacao, $idJogo){
  if($idJogo != 0){
    foreach($log_escalacao as $single_player){
      $query = "INSERT INTO jogos_escalacao (id_jogo, id_jogador, nome_jogador, id_time, iniciou, saiu, posicao_jogador) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id_escalacao = id_escalacao";
      $stmt = $this->conn->prepare( $query );
      $stmt->bindParam(1, $idJogo);
      $stmt->bindParam(2, $single_player['idJogador']);
	  $nomeJogador = addslashes($single_event['nomeJogador']);
	  $stmt->bindParam(3, $nomeJogador);
      $stmt->bindParam(4, $single_player['idTime']);
      $stmt->bindParam(5, $single_player['iniciou']);
      $stmt->bindParam(6, $single_player['saiu']);
      $stmt->bindParam(7, $single_player['posicaoJogador']);
      $stmt->execute();

    }
  }

  return true;
}

public function getMatchId(){
  $query = "SELECT id FROM jogos WHERE timeA_id = ? AND timeB_id = ? AND data = ? LIMIT 0,1";
  $stmt = $this->conn->prepare( $query );
  $stmt->bindParam(1, $this->timeA_id);
  $stmt->bindParam(2, $this->timeB_id);
  $corrected_date = (String)$this->data ;
  $stmt->bindParam(3, $corrected_date);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  return $result['id'];
}


    function pesquisaRetrospecto($times){

        $times[0] = htmlspecialchars(strip_tags($times[0]));
		$times[1] = htmlspecialchars(strip_tags($times[1]));
		
		if($times[1] == 0){
			$query = "SELECT tb1.nome as nomeTime, tb2.nome as nomeAdversario, tb1.bandeira as bandeiraA, tb2.bandeira as bandeiraB, tb1.id as idA, tb2.id as idB, SUM(t1.contagem * t1.resultado) as retrospecto, SUM(vitorias) as vitorias, SUM(empates) as empates, SUM(derrotas) as derrotas, SUM(gols_pro) as gols_pro, SUM(gols_contra) as gols_contra, (SUM(gols_pro) - SUM(gols_contra)) as saldo_gols, ((SUM(vitorias) * 3 + SUM(empates) * 1)/((SUM(vitorias)+SUM(empates)+SUM(derrotas)) *3)) as aproveitamento FROM
            (SELECT  time, adversario, resultado, count(*) as contagem, SUM(vitorias) as vitorias, SUM(empates) as empates, SUM(derrotas) as derrotas, SUM(gols_pro) as gols_pro, SUM(gols_contra) as gols_contra FROM
                (SELECT timeA_id as time, timeB_id as adversario,
                    if((timeA_gols - timeB_gols)>0, 1, if((timeA_gols - timeB_gols)<0, -1, 0 )) as resultado, id, if((timeA_gols - timeB_gols)>0,1,0) as vitorias, if((timeA_gols - timeB_gols)<0, 1,0) as derrotas, if((timeA_gols - timeB_gols)=0, 1,0 ) as empates, timeA_gols as gols_pro, timeB_gols as gols_contra   
                FROM jogos
                UNION
                SELECT timeB_id as time, timeA_id as adversario,
                    if((timeB_gols - timeA_gols)>0, 1, if((timeB_gols - timeA_gols)<0, -1, 0 )) as resultado, id, if((timeB_gols - timeA_gols)>0,1,0) as vitorias, if((timeB_gols - timeA_gols)<0, 1,0) as derrotas, if((timeB_gols - timeA_gols)=0, 1,0 ) as empates, timeB_gols as gols_pro, timeA_gols as gols_contra   
                FROM jogos 
                 
                ) t2
            GROUP BY 1, 2, 3
            ORDER BY contagem DESC) t1
        LEFT JOIN paises tb1
        ON t1.time = tb1.id
        LEFT JOIN paises tb2
        ON t1.adversario = tb2.id
		
		WHERE tb1.id = :idA1 AND tb2.id <> :idA2 
        GROUP BY 1, 2
		ORDER BY retrospecto DESC, vitorias DESC, saldo_gols DESC, gols_pro DESC";

		$stmt = $this->conn->prepare( $query );

		$stmt->bindParam(":idA1",$times[0]);
		$stmt->bindParam(":idA2",$times[0]);

		$stmt->execute();
		} else {

		$query = "SELECT
				 p.nome as nomeA, p.bandeira as bandeiraA,  j.timeA_gols as timeAgols, j.timeB_gols as timeBgols, c.nome as nomeB, c.bandeira as bandeiraB, j.data, l.nome as campeonato,  j.timeA_penaltis as timeApenaltis, j.timeB_penaltis as timeBpenaltis, p.id as idA, c.id as idB, j.id, IFNULL(j.fase, '0')  as fase, estadio    
				FROM
					jogos j
				LEFT JOIN
					paises p
				ON
					j.timeA_id = p.id
			   LEFT JOIN
				  paises c
				ON
				   j.timeB_id = c.id
			   LEFT JOIN
				 campeonatos l
				 ON
				 j.campeonato = l.id 
				WHERE
					(j.timeA_id = :idA1 AND j.timeB_id = :idB1) OR (j.timeA_id = :idB2 AND j.timeB_id = :idA2)
				ORDER BY
					data DESC";

		$stmt = $this->conn->prepare( $query );

		$stmt->bindParam(":idA1",$times[0]);
		$stmt->bindParam(":idA2",$times[0]);
		$stmt->bindParam(":idB1",$times[1]);
		$stmt->bindParam(":idB2",$times[1]);

		$stmt->execute();
		}

		return $stmt;
}

}
?>
