<?php
class Jogo{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "jogos_clube";

    // object properties
    public $id;
    public $timeA_id;
    public $timeA_nome;
    public $timeA_gols;
    public $timeB_id;
    public $timeB_nome;
    public $timeB_gols;
    public $timeA_penaltis;
    public $timeB_penaltis;
    public $data;
    public $competicao_id;
    public $fase;
    public $estadio_nome;
    public $estadio_id;
    public $competicao_tipo; // 0 = Liga, 1 = Copa
    public $dono;
    public $status = 1; // 0 = Agendado/Pendente, 1 = Finalizado
    public $simulador_interno = 0; // 0 = Manual/Legado, 1 = Motor Hexacolor
    public $neutro = 0;
    public $grupo;
    public $path;
    public $arbitro_id;


    public function __construct($db){
        $this->conn = $db;
    }

     // inserir jogo
    function inserir(){

        //write query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    timeA_id=:timeA_id, timeA_nome=:timeA_nome, timeA_gols=:timeA_gols, timeB_id=:timeB_id, timeB_nome=:timeB_nome, timeB_gols=:timeB_gols, timeA_penaltis=:timeA_penaltis, timeB_penaltis=:timeB_penaltis, data=:data, competicao_id=:competicao_id, estadio_nome=:estadio_nome, estadio_id=:estadio_id, fase=:fase, competicao_tipo=:competicao_tipo, dono=:dono 	";

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->timeA_id=htmlspecialchars(strip_tags($this->timeA_id));
        $this->timeA_nome=htmlspecialchars(strip_tags($this->timeA_nome));
        $this->timeA_gols=htmlspecialchars(strip_tags($this->timeA_gols));
        $this->timeB_id=htmlspecialchars(strip_tags($this->timeB_id));
        $this->timeB_nome=htmlspecialchars(strip_tags($this->timeB_nome));
        $this->timeB_gols=htmlspecialchars(strip_tags($this->timeB_gols));
        $this->timeA_penaltis=htmlspecialchars(strip_tags($this->timeA_penaltis));
        $this->timeB_penaltis=htmlspecialchars(strip_tags($this->timeB_penaltis));
        $this->data=htmlspecialchars(strip_tags($this->data));
        $this->competicao_id=htmlspecialchars(strip_tags($this->competicao_id));
		$this->estadio_nome=htmlspecialchars(strip_tags($this->estadio_nome));
		$this->estadio_id=htmlspecialchars(strip_tags($this->estadio_id));
		$this->fase=htmlspecialchars(strip_tags($this->fase));


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
        $stmt->bindParam(":timeA_nome", $this->timeA_nome);
        $stmt->bindParam(":timeA_gols", $this->timeA_gols);
        $stmt->bindParam(":timeB_id", $this->timeB_id);
        $stmt->bindParam(":timeB_nome", $this->timeB_nome);
        $stmt->bindParam(":timeB_gols", $this->timeB_gols);
        $stmt->bindParam(":timeA_penaltis", $this->timeA_penaltis);
        $stmt->bindParam(":timeB_penaltis", $this->timeB_penaltis);
        $stmt->bindParam(":data", $this->data);
        $stmt->bindParam(":competicao_id", $this->competicao_id);
		$stmt->bindParam(":estadio_nome", $this->estadio_nome);
		$stmt->bindParam(":estadio_id", $this->estadio_id);
		$stmt->bindParam(":fase", $this->fase);
        $stmt->bindParam(":competicao_tipo", $this->competicao_tipo);
        $stmt->bindParam(":dono", $this->dono);

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
             j.id as idJogo, p.nome as nomeA, p.bandeira as bandeiraA,  j.timeA_gols, j.timeB_gols, c.nome as nomeB, c.bandeira as bandeiraB, j.data, COALESCE(li.nome, cc.nome) as nomeCampeonato, j.timeA_penaltis, j.timeB_penaltis, p.id as idA, c.id as idB
            FROM
                jogos_clube j
            LEFT JOIN
                paises p
            ON
                j.timeA_id = p.id
           LEFT JOIN
              paises c
            ON
               j.timeB_id = c.id
           LEFT JOIN
             liga li
             ON
             j.competicao_id = li.id AND j.competicao_tipo = 0
           LEFT JOIN
             campeonatos_clube cc
             ON
             j.competicao_id = cc.id AND j.competicao_tipo = 1
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
            competicao_id,
            competicao_tipo
        FROM jogos_clube j
        WHERE timeB_id = ? ";

        $multiple_query = " UNION
        SELECT
            timeB_id as time,
            timeB_gols as timeGols,
            timeA_gols as adversarioGols,
            timeA_id as adversarioId,
            data,
            competicao_id,
            competicao_tipo
        FROM jogos_clube j ";

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


        $query = "SELECT p.nome as nomeTime, timeGols, adversarioGols, c.nome as nomeAdversario, data, COALESCE(li.nome, cc.nome) as nomeCampeonato FROM

                        (SELECT
                            j.timeA_id as time,
                            j.timeA_gols as timeGols,
                            j.timeB_gols as adversarioGols,
                            j.timeB_id as adversarioId,
                            j.data,
                            j.competicao_id,
                            j.competicao_tipo
                        FROM jogos_clube j ".
                        $sub_query.
                        "ORDER BY timeGols ".$ord."
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
                            liga li
                        ON
                            t1.competicao_id = li.id AND t1.competicao_tipo = 0
                        LEFT JOIN
                            campeonatos_clube cc
                        ON
                            t1.competicao_id = cc.id AND t1.competicao_tipo = 1";

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
            FROM jogos_clube ";
        } else {
        $append_a ="SELECT timeB_id as time, timeA_id as adversario, id
        FROM jogos_clube WHERE timeA_id = ?
         UNION
        SELECT timeA_id as time, timeB_id as adversario, id
        FROM jogos_clube WHERE timeB_id = ?";
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
        if($id != 0){
            $stmt->bindParam(1, $id);
            $stmt->bindParam(2, $id);
        }
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

        $query = "SELECT p.nome as nomeTime, t1.golsPro as timeGols, t1.golsContra as adversarioGols, c.nome as nomeAdversario, t1.data, COALESCE(li.nome, cc.nome) as nomeCampeonato FROM

            (SELECT timeA_id as time, timeA_gols as golsPro, timeB_gols as golsContra, timeB_id as adversario,
                (timeA_gols - timeB_gols) as golsSaldo, id, data, competicao_id, competicao_tipo
            FROM jogos_clube
             ".$append_a."
            UNION
            SELECT timeB_id as time, timeB_gols as golsPro, timeA_gols as golsContra, timeA_id as adversario,
                (timeB_gols - timeA_gols) as golsSaldo, id, data, competicao_id, competicao_tipo
            FROM jogos_clube
             ".$append_b."
            ORDER BY golsSaldo DESC, golsPro DESC
            LIMIT 0,10) t1

        LEFT JOIN paises p
        ON t1.time = p.id
        LEFT JOIN paises c
        ON t1.adversario = c.id
        LEFT JOIN liga li ON t1.competicao_id = li.id AND t1.competicao_tipo = 0
        LEFT JOIN campeonatos_clube cc ON t1.competicao_id = cc.id AND t1.competicao_tipo = 1
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

        if($id == 0){
            $append_a = "";
            $append_b = "";
        } else {
            $append_a = "WHERE timeA_id = ? ";
            $append_b = "WHERE timeB_id = ? ";
        }

        $query = "SELECT p.nome as nomeTime, t1.golsPro as timeGols, t1.golsContra as adversarioGols, c.nome as nomeAdversario, t1.data, COALESCE(li.nome, cc.nome) as nomeCampeonato FROM

            (SELECT timeA_id as time, timeA_gols as golsPro, timeB_gols as golsContra, timeB_id as adversario,
                (timeA_gols - timeB_gols) as golsSaldo, id, data, competicao_id, competicao_tipo
            FROM jogos_clube
            ".$append_a."
            UNION
            SELECT timeB_id as time, timeB_gols as golsPro, timeA_gols as golsContra, timeA_id as adversario,
                (timeB_gols - timeA_gols) as golsSaldo, id, data, competicao_id, competicao_tipo
            FROM jogos_clube
            ".$append_b."
            ORDER BY golsSaldo ASC, golsContra DESC
            LIMIT 0,10) t1

        LEFT JOIN paises p
        ON t1.time = p.id
        LEFT JOIN paises c
        ON t1.adversario = c.id
        LEFT JOIN liga li ON t1.competicao_id = li.id AND t1.competicao_tipo = 0
        LEFT JOIN campeonatos_clube cc ON t1.competicao_id = cc.id AND t1.competicao_tipo = 1
        WHERE t1.golsSaldo < 0";

        $stmt = $this->conn->prepare( $query );
        if($id != 0){
            $stmt->bindParam(1, $id);
            $stmt->bindParam(2, $id);
        }
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
                FROM jogos_clube
                " .$append_a . "
                UNION
                SELECT timeB_id as time, timeA_id as adversario,
                    if((timeB_gols - timeA_gols)>0, 'V', if((timeB_gols - timeA_gols)<0, 'D', 'E' )) as resultado, id
                FROM jogos_clube
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
        $item_pesquisado = '%'.$item_pesquisado.'%';

        $query = "SELECT
                COALESCE(cA.Nome, j.timeA_nome) as nomeA, 
                COALESCE(cA.Escudo, '0.png') as escudoA,  
                j.timeA_gols as timeAgols, 
                j.timeB_gols as timeBgols, 
                COALESCE(cB.Nome, j.timeB_nome) as nomeB, 
                COALESCE(cB.Escudo, '0.png') as escudoB, 
                j.data, 
                DATE_FORMAT(j.data, '%d-%m-%Y') as data_formatada,
                COALESCE(li.nome, cc.nome) as campeonato, 
                j.timeA_penaltis as timeApenaltis, 
                j.timeB_penaltis as timeBpenaltis, 
                j.timeA_id as idA, 
                j.timeB_id as idB, 
                j.dono,
                j.id
            FROM
                jogos_clube j
            LEFT JOIN
                clube cA
            ON
                j.timeA_id = cA.ID
            LEFT JOIN
                clube cB
            ON
                j.timeB_id = cB.ID
            LEFT JOIN
                liga li
            ON
                j.competicao_id = li.id AND j.competicao_tipo = 0
            LEFT JOIN
                campeonatos_clube cc
            ON
                j.competicao_id = cc.id AND j.competicao_tipo = 1
            WHERE
                COALESCE(cA.Nome, j.timeA_nome) LIKE ?
                OR j.timeA_gols LIKE ?
                OR j.timeB_gols LIKE ?
                OR COALESCE(cB.Nome, j.timeB_nome) LIKE ?
                OR j.data LIKE ?
                OR COALESCE(li.nome, cc.nome) LIKE ?
                OR j.id LIKE ?
            ORDER BY
                data DESC";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1,$item_pesquisado);
        $stmt->bindParam(2,$item_pesquisado);
        $stmt->bindParam(3,$item_pesquisado);
        $stmt->bindParam(4,$item_pesquisado);
        $stmt->bindParam(5,$item_pesquisado);
        $stmt->bindParam(6,$item_pesquisado);
        $stmt->bindParam(7,$item_pesquisado);
        
        $stmt->execute();

        return $stmt;
    }

    public function recuperarInfoGeral(){

        $query = "SELECT SUM(if((timeA_gols != timeB_gols), 1, 0)) AS vitorias, SUM(timeA_gols)+SUM(timeB_gols) as gols, SUM(if((timeA_gols = timeB_gols) , 1, 0)) AS empates, count(*) as jogosTotais  FROM " . $this->table_name . "";

        $stmt = $this->conn->prepare( $query );
        $stmt->execute();

        return $stmt;
    }

    public function getSingleMatchInfo($match_id){

      $match_id = htmlspecialchars(strip_tags($match_id));
      $query = "SELECT 
                    j.id,
                    j.estadio_nome as estadio, 
                    j.estadio_id,
                    j.competicao_id,
                    j.competicao_tipo,
                    j.dono,
                    arbitros.nomeArbitro as nome_arbitro, 
                    COALESCE(ta.Nome, j.timeA_nome) as timeA_nome, 
                    COALESCE(ta.Escudo, '0.png') as timeA_bandeira, 
                    j.timeA_id, 
                    j.timeA_gols, 
                    COALESCE(tb.Nome, j.timeB_nome) as timeB_nome, 
                    COALESCE(tb.Escudo, '0.png') as timeB_bandeira, 
                    j.timeB_id, 
                    j.timeB_gols, 
                    j.timeA_penaltis, 
                    j.timeB_penaltis, 
                    DATE_FORMAT(j.data, '%Y-%m-%d') as data, 
                    COALESCE(li.nome, cc.nome) as competition_name, 
                    j.fase as phase 
                FROM jogos_clube j 
                LEFT JOIN liga li ON li.id = j.competicao_id AND j.competicao_tipo = 0
                LEFT JOIN campeonatos_clube cc ON cc.id = j.competicao_id AND j.competicao_tipo = 1
                LEFT JOIN clube ta ON ta.ID = j.timeA_id  
                LEFT JOIN clube tb ON tb.ID = j.timeB_id 
                LEFT JOIN arbitros ON arbitros.id = j.arbitro_id 
                WHERE j.id = :id";
      
      $stmt = $this->conn->prepare( $query );
      $stmt->bindParam(":id", $match_id);
      $stmt->execute();
      $results = $stmt->fetch(PDO::FETCH_ASSOC);
      return $results;
    }

public function getSingleMatchEvents($match_id){
  $match_id = htmlspecialchars(strip_tags($match_id));
  $query = "SELECT tempo, minutos, tipo, id_jogador, nome_jogador, id_time FROM jogos_clube_eventos WHERE id_jogo = :id ORDER BY tempo, minutos";
  $stmt = $this->conn->prepare( $query );
  $stmt->bindParam(":id", $match_id);
  $stmt->execute();
  return $stmt;
}

    // atualizar jogo
    function atualizar(){

        //write query
        $query = "UPDATE
                    " . $this->table_name . "
                SET
                    timeA_id=:timeA_id, timeA_nome=:timeA_nome, timeA_gols=:timeA_gols, timeB_id=:timeB_id, timeB_nome=:timeB_nome, timeB_gols=:timeB_gols, timeA_penaltis=:timeA_penaltis, timeB_penaltis=:timeB_penaltis, data=:data, competicao_id=:competicao_id, estadio_nome=:estadio_nome, estadio_id=:estadio_id, fase=:fase, competicao_tipo=:competicao_tipo, dono=:dono
                WHERE
                    id=:id";

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->timeA_id = (int)$this->timeA_id;
        $this->timeA_nome = htmlspecialchars(strip_tags($this->timeA_nome));
        $this->timeA_gols = (int)$this->timeA_gols;
        $this->timeB_id = (int)$this->timeB_id;
        $this->timeB_nome = htmlspecialchars(strip_tags($this->timeB_nome));
        $this->timeB_gols = (int)$this->timeB_gols;
        $this->timeA_penaltis = ($this->timeA_penaltis !== null && $this->timeA_penaltis !== "") ? (int)$this->timeA_penaltis : null;
        $this->timeB_penaltis = ($this->timeB_penaltis !== null && $this->timeB_penaltis !== "") ? (int)$this->timeB_penaltis : null;
        $this->data = htmlspecialchars(strip_tags($this->data));
        $this->competicao_id = (int)$this->competicao_id;
        $this->estadio_nome = htmlspecialchars(strip_tags($this->estadio_nome));
        $this->estadio_id = (int)$this->estadio_id;
        $this->fase = (int)$this->fase;
        $this->id = (int)$this->id;

        // bind values
        $stmt->bindParam(":timeA_id", $this->timeA_id);
        $stmt->bindParam(":timeA_nome", $this->timeA_nome);
        $stmt->bindParam(":timeA_gols", $this->timeA_gols);
        $stmt->bindParam(":timeB_id", $this->timeB_id);
        $stmt->bindParam(":timeB_nome", $this->timeB_nome);
        $stmt->bindParam(":timeB_gols", $this->timeB_gols);
        $stmt->bindParam(":timeA_penaltis", $this->timeA_penaltis);
        $stmt->bindParam(":timeB_penaltis", $this->timeB_penaltis);
        $stmt->bindParam(":data", $this->data);
        $stmt->bindParam(":competicao_id", $this->competicao_id);
		$stmt->bindParam(":estadio_nome", $this->estadio_nome);
		$stmt->bindParam(":estadio_id", $this->estadio_id);
		$stmt->bindParam(":fase", $this->fase);
        $stmt->bindParam(":competicao_tipo", $this->competicao_tipo);
        $stmt->bindParam(":dono", $this->dono);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }

public function importar(){
  // Check if $id is set, if so, update, else insert
  if($this->id != 0 && $this->id != null){
    return $this->atualizar();
  }

  $query = "INSERT INTO jogos_clube (timeA_id, timeA_nome, timeA_gols, timeB_id, timeB_nome, timeB_gols, timeA_penaltis, timeB_penaltis, data, competicao_id, estadio_nome, estadio_id, fase, competicao_tipo, dono) 
            VALUES (:timeA_id, :timeA_nome, :timeA_gols, :timeB_id, :timeB_nome, :timeB_gols, :timeA_penaltis, :timeB_penaltis, :data, :competicao_id, :estadio_nome, :estadio_id, :fase, :competicao_tipo, :dono) 
            ON DUPLICATE KEY UPDATE timeA_nome = VALUES(timeA_nome), timeB_nome = VALUES(timeB_nome)";
  $stmt = $this->conn->prepare( $query );

  // Sanitization
  $this->timeA_id = (int)$this->timeA_id;
  $this->timeA_nome = htmlspecialchars(strip_tags($this->timeA_nome));
  $this->timeA_gols = (int)$this->timeA_gols;
  $this->timeB_id = (int)$this->timeB_id;
  $this->timeB_nome = htmlspecialchars(strip_tags($this->timeB_nome));
  $this->timeB_gols = (int)$this->timeB_gols;
  $this->timeA_penaltis = ($this->timeA_penaltis !== null && $this->timeA_penaltis !== "") ? (int)$this->timeA_penaltis : null;
  $this->timeB_penaltis = ($this->timeB_penaltis !== null && $this->timeB_penaltis !== "") ? (int)$this->timeB_penaltis : null;
  $this->data = htmlspecialchars(strip_tags($this->data));
  $this->competicao_id = (int)$this->competicao_id;
  $this->estadio_nome = htmlspecialchars(strip_tags($this->estadio_nome));
  $this->estadio_id = (int)$this->estadio_id;
  $this->fase = (int)$this->fase;
  $this->dono = (int)$this->dono;
  
  $stmt->bindParam(":timeA_id", $this->timeA_id);
  $stmt->bindParam(":timeA_nome", $this->timeA_nome);
  $stmt->bindParam(":timeA_gols", $this->timeA_gols);
  $stmt->bindParam(":timeB_id", $this->timeB_id);
  $stmt->bindParam(":timeB_nome", $this->timeB_nome);
  $stmt->bindParam(":timeB_gols", $this->timeB_gols);
  $stmt->bindParam(":timeA_penaltis", $this->timeA_penaltis);
  $stmt->bindParam(":timeB_penaltis", $this->timeB_penaltis);
  $stmt->bindParam(":data", $this->data);
  $stmt->bindParam(":competicao_id", $this->competicao_id);
  $stmt->bindParam(":estadio_nome", $this->estadio_nome);
  $stmt->bindParam(":estadio_id", $this->estadio_id);
  $stmt->bindParam(":fase", $this->fase);
  $stmt->bindParam(":competicao_tipo", $this->competicao_tipo);
  $stmt->bindParam(":dono", $this->dono);

  if($stmt->execute()){
    return true;
  } else {
    return false;
  }
}

public function importarEventos($log_eventos, $idJogo){
  if($idJogo != 0){
    foreach($log_eventos as $single_event){
      $query = "INSERT INTO jogos_clube_eventos (id_jogo, tempo, minutos, tipo, id_jogador, nome_jogador, id_time, nome_time) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id_evento = id_evento";
      $stmt = $this->conn->prepare( $query );
      $stmt->bindParam(1, $idJogo);
      $stmt->bindParam(2, $single_event['tempo']);
      $stmt->bindParam(3, $single_event['minutos']);
      $stmt->bindParam(4, $single_event['tipo']);
      $stmt->bindParam(5, $single_event['idJogador']);
      $nomeJogador = $single_event['nomeJogador'];
      $stmt->bindParam(6, $nomeJogador);
      $stmt->bindParam(7, $single_event['idTime']);
      $stmt->bindParam(8, $single_event['nomeTime']);
      $stmt->execute();

    }
  }

  return true;
}

public function importarEscalacao($log_escalacao, $idJogo){
  if($idJogo != 0){
    foreach($log_escalacao as $single_player){
      $query = "INSERT INTO jogos_clube_escalacao (id_partida, id_time, nome_time, posicao, numero, id_jogador, nome_jogador, titular, entrada_tempo, entrada_minuto, saida_tempo, saida_minuto) VALUES (?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP";
      $stmt = $this->conn->prepare( $query );
      $stmt->bindParam(1, $idJogo);
      $stmt->bindParam(2, $single_player['idTime']);
      $stmt->bindParam(3, $single_player['nomeTime']);
      $stmt->bindParam(4, $single_player['posicao']);
      $stmt->bindParam(5, $single_player['numero']);
      $stmt->bindParam(6, $single_player['idJogador']);
	  $nomeJogador = addslashes($single_player['nomeJogador']);
	  $stmt->bindParam(7, $nomeJogador);
      $stmt->bindParam(8, $single_player['titular']);
      $stmt->bindParam(9, $single_player['entrada_tempo']);
      $stmt->bindParam(10, $single_player['entrada_minuto']);
      $stmt->bindParam(11, $single_player['saida_tempo']);
      $stmt->bindParam(12, $single_player['saida_minuto']);
      $stmt->execute();
    }
  }

  return true;
}

public function limparEventos($idJogo){
    if($idJogo != 0){
        $query = "DELETE FROM jogos_clube_eventos WHERE id_jogo = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idJogo);
        $stmt->execute();
    }
    return true;
}

public function limparEscalacao($idJogo){
    if($idJogo != 0){
        $query = "DELETE FROM jogos_clube_escalacao WHERE id_partida = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idJogo);
        $stmt->execute();
    }
    return true;
}

public function getMatchId(){
  $query = "SELECT id FROM jogos_clube WHERE timeA_id = ? AND timeB_id = ? AND data = ? LIMIT 0,1";
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
                FROM jogos_clube
                UNION
                SELECT timeB_id as time, timeA_id as adversario,
                    if((timeB_gols - timeA_gols)>0, 1, if((timeB_gols - timeA_gols)<0, -1, 0 )) as resultado, id, if((timeB_gols - timeA_gols)>0,1,0) as vitorias, if((timeB_gols - timeA_gols)<0, 1,0) as derrotas, if((timeB_gols - timeA_gols)=0, 1,0 ) as empates, timeB_gols as gols_pro, timeA_gols as gols_contra   
                FROM jogos_clube 
                 
                ) t2
            GROUP BY 1, 2, 3
            ORDER BY contagem DESC) t1
        LEFT JOIN paises tb1
        ON t1.time = tb1.id
        LEFT JOIN paises tb2
        ON t1.adversario = tb2.id
		
		WHERE tb1.id = :idA1 AND tb2.id <> :idA2 
        GROUP BY 1, 2
		ORDER BY retrospecto DESC, SUM(vitorias ) DESC, (SUM(gols_pro) - SUM(gols_contra)) DESC, SUM(gols_pro) DESC";

		$stmt = $this->conn->prepare( $query );

		$stmt->bindParam(":idA1",$times[0]);
		$stmt->bindParam(":idA2",$times[0]);

		$stmt->execute();
		} else {

		$query = "SELECT
				 p.nome as nomeA, p.bandeira as bandeiraA,  j.timeA_gols as timeAgols, j.timeB_gols as timeBgols, c.nome as nomeB, c.bandeira as bandeiraB, j.data, l.nome as campeonato,  j.timeA_penaltis as timeApenaltis, j.timeB_penaltis as timeBpenaltis, p.id as idA, c.id as idB, j.id, IFNULL(j.fase, '0')  as fase, j.estadio_nome as estadio    
				FROM
					jogos_clube j
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
				 j.competicao_id = l.id 
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

    /**
     * Retorna os últimos jogos públicos e finalizados de uma equipe
     * Respeitando a regra de sigilo temporal (jogos simulados com antecedência só aparecem após o término real do jogo)
     * Regulamentar: 120min (2h) | Pênaltis: 150min (2h30)
     */
    public function buscarUltimosJogosTime($id_time, $limite = 3) {
        $id_time = (int)$id_time;
        $limite = (int)$limite;

        $query = "
            SELECT 
                j.id as match_id,
                j.data as data_jogo,
                COALESCE(cl.nome, li.nome, cc.nome, 'Amistoso / Competição') as competicao_nome,
                j.timeA_id,
                COALESCE(cA.Nome, j.timeA_nome) as timeA_nome,
                COALESCE(cA.Escudo, '0.png') as timeA_escudo,
                j.timeA_gols,
                j.timeB_id,
                COALESCE(cB.Nome, j.timeB_nome) as timeB_nome,
                COALESCE(cB.Escudo, '0.png') as timeB_escudo,
                j.timeB_gols,
                j.timeA_penaltis,
                j.timeB_penaltis,
                j.simulador_interno,
                j.competicao_id,
                j.competicao_tipo
            FROM jogos_clube j
            LEFT JOIN clube cA ON j.timeA_id = cA.ID
            LEFT JOIN clube cB ON j.timeB_id = cB.ID
            LEFT JOIN competicao_lista cl ON j.competicao_id = cl.id AND j.simulador_interno = 1
            LEFT JOIN liga li ON j.competicao_id = li.id AND (j.simulador_interno = 0 OR j.simulador_interno IS NULL) AND j.competicao_tipo = 0
            LEFT JOIN campeonatos_clube cc ON j.competicao_id = cc.id AND (j.simulador_interno = 0 OR j.simulador_interno IS NULL) AND j.competicao_tipo = 1
            WHERE (j.timeA_id = :id1 OR j.timeB_id = :id2)
              AND j.status = 1
              AND (
                  -- Partidas normais / manuais que já passaram
                  (j.timeA_penaltis IS NULL AND DATE_ADD(j.data, INTERVAL 120 MINUTE) <= NOW())
                  OR
                  -- Partidas com prorrogação/pênaltis que já passaram
                  (j.timeA_penaltis IS NOT NULL AND DATE_ADD(j.data, INTERVAL 150 MINUTE) <= NOW())
              )
            ORDER BY j.data DESC, j.id DESC
            LIMIT :limite
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id1', $id_time, PDO::PARAM_INT);
        $stmt->bindParam(':id2', $id_time, PDO::PARAM_INT);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna a próxima partida agendada da equipe (se houver)
     * Considera partidas pendentes (status = 0) ou partidas que ainda não terminaram no tempo real
     */
    public function buscarProximoJogoTime($id_time) {
        $id_time = (int)$id_time;

        $query = "
            SELECT 
                j.id as match_id,
                j.data as data_jogo,
                COALESCE(cl.nome, li.nome, cc.nome, 'Amistoso / Competição') as competicao_nome,
                j.timeA_id,
                COALESCE(cA.Nome, j.timeA_nome) as timeA_nome,
                COALESCE(cA.Escudo, '0.png') as timeA_escudo,
                j.timeB_id,
                COALESCE(cB.Nome, j.timeB_nome) as timeB_nome,
                COALESCE(cB.Escudo, '0.png') as timeB_escudo,
                j.fase,
                j.grupo,
                j.estadio_id,
                e.Nome as estadio_nome,
                f.nome as fase_nome,
                j.simulador_interno,
                j.competicao_id,
                j.competicao_tipo
            FROM jogos_clube j
            LEFT JOIN clube cA ON j.timeA_id = cA.ID
            LEFT JOIN clube cB ON j.timeB_id = cB.ID
            LEFT JOIN estadio e ON j.estadio_id = e.ID
            LEFT JOIN fase f ON j.fase = f.id
            LEFT JOIN competicao_lista cl ON j.competicao_id = cl.id AND j.simulador_interno = 1
            LEFT JOIN liga li ON j.competicao_id = li.id AND (j.simulador_interno = 0 OR j.simulador_interno IS NULL) AND j.competicao_tipo = 0
            LEFT JOIN campeonatos_clube cc ON j.competicao_id = cc.id AND (j.simulador_interno = 0 OR j.simulador_interno IS NULL) AND j.competicao_tipo = 1
            WHERE (j.timeA_id = :id1 OR j.timeB_id = :id2)
              AND (
                  j.status = 0
                  OR (j.timeA_penaltis IS NULL AND DATE_ADD(j.data, INTERVAL 120 MINUTE) > NOW())
                  OR (j.timeA_penaltis IS NOT NULL AND DATE_ADD(j.data, INTERVAL 150 MINUTE) > NOW())
              )
            ORDER BY j.data ASC, j.id ASC
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id1', $id_time, PDO::PARAM_INT);
        $stmt->bindParam(':id2', $id_time, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
