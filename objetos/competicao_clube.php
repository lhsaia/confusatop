<?php
class Competicao_clube{
 
    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "competicao_lista";
 
    // object properties
    public $id;
    public $nome;
	public $ano;
    public $federacao;
	public $sede;
	public $path;
	public $logo;
	public $genero;
	public $dono;
 
    public function __construct($db){
        $this->conn = $db;
    }
 
    
    // used by select drop-down list
    function read(){
        //select all data
        $query = "SELECT
                    id, nome, ano, federacao, sede, path, logo, genero 
                FROM
                    " . $this->table_name . "
                ORDER BY
                    ano DESC, nome ASC";  
 
        $stmt = $this->conn->prepare( $query );
        $stmt->execute();
 
        return $stmt;
    }
    
    // used to read category name by its ID
    function readName(){
     
    $query = "SELECT nome FROM " . $this->table_name . " WHERE id = ? limit 0,1";
 
    $stmt = $this->conn->prepare( $query );
    $stmt->bindParam(1, $this->id);
    $stmt->execute();
 
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
     
    $this->name = $row['nome'];
    }
	
	function inserirOpcoes($id_competicao){

		$query = "INSERT INTO
			competicao_opcoes
		SET
			id_competicao=:idcomp";
		
		$stmt = $this->conn->prepare( $query );
		$stmt->bindParam(":idcomp", $id_competicao);
		if($stmt->execute()){
			return true;
		} else {
			return false;
		}
	
	}
	
	
	
	    function inserir(){

        //escrever query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    nome=:nome, ano=:ano, federacao=:federacao, logo=:logo, sede=:sede, genero=:genero, dono=:dono, path=:path ";
					
		if($this->logo == ""){
			$this->logo = "0.png";
		}

        $this->path = $this->ano . "-" . $this->nome;

        $stmt = $this->conn->prepare($query);
	

        // posted values
        $this->nome=htmlspecialchars(strip_tags($this->nome));
        $this->ano=htmlspecialchars(strip_tags($this->ano));
        $this->federacao=htmlspecialchars(strip_tags($this->federacao));
        $this->sede=htmlspecialchars(strip_tags($this->sede));
        $this->genero=htmlspecialchars(strip_tags($this->genero));
		$this->logo=htmlspecialchars(strip_tags($this->logo));
		$this->dono=htmlspecialchars(strip_tags($this->dono));
        $this->path=htmlspecialchars(strip_tags($this->path));

        // bind values
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":ano", $this->ano);
        $stmt->bindParam(":logo", $this->logo);
        $stmt->bindParam(":sede", $this->sede);
        $stmt->bindParam(":genero", $this->genero);
		$stmt->bindParam(":federacao", $this->federacao);
		$stmt->bindParam(":dono", $this->dono);
        $stmt->bindParam(":path", $this->path);

        if($stmt->execute()){
			return true;
        } else {
            return false;
        }

    }
	
	    //ler todos os jogadores para o quadro - versão para página com Ajax
    function readAllAjax($item_pesquisado){
		$item_pesquisado = htmlspecialchars(strip_tags($item_pesquisado));

		$query = "SELECT * FROM (SELECT
					a.id, a.nome, a.logo, f.nome as federacao, f.id as idFederacao, p.id as idSede, p.nome as sede, a.ano, a.genero, p.sigla as siglaSede, p.bandeira as bandeiraSede
					FROM " . $this->table_name . " a
					LEFT JOIN paises p ON a.sede = p.id
					LEFT JOIN federacoes f ON a.federacao = f.id
					ORDER BY
						a.Nome ASC ) t1 WHERE Nome LIKE ? LIMIT 150";

		$stmt = $this->conn->prepare( $query );
		$item_pesquisado = "%" . $item_pesquisado . "%";
		
		$stmt->bindParam(1, $item_pesquisado);

		$stmt->execute();

		return $stmt;
}


    function alterar($id,$nome,$sede,$ano,$federacao,$logo = null){

        $id = htmlspecialchars(strip_tags($id));
        $nome = htmlspecialchars(strip_tags($nome));
        $sede = htmlspecialchars(strip_tags($sede));
        $ano = htmlspecialchars(strip_tags($ano));
		$federacao = htmlspecialchars(strip_tags($federacao));
        $logo = htmlspecialchars(strip_tags($logo));

        if($logo != null){
            $subquery = ", logo=:logo";
        } else {
            $subquery = "";
        }

        $query = "UPDATE " . $this->table_name . " SET nome=:nome, sede=:sede, federacao=:federacao, ano=:ano ".$subquery." WHERE id=:id";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":sede", $sede);
        $stmt->bindParam(":ano", $ano);
		$stmt->bindParam(":federacao", $federacao);
        if($logo != null){
            $stmt->bindParam(":logo", $logo);
        }
        $stmt->bindParam(":id", $id);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }
	
	
	    function readInfo($id){

        $id = intval($id);

		$query = "SELECT
					a.nome, a.ano, f.nome as federacao, p.bandeira as sede, a.logo, a.genero, f.id as federacaoId, a.dono, o.numero_times as total_times, 
					(SELECT COUNT(DISTINCT codigo_time) FROM competicao_times WHERE id_competicao = a.id AND has_team = '1') as times_inseridos     
				FROM
					" . $this->table_name . " a
				LEFT JOIN federacoes f 
					ON f.id = a.federacao
				LEFT JOIN paises p
					ON p.id = a.sede 
				LEFT JOIN competicao_opcoes o ON o.id_competicao = a.id  
				WHERE
					a.id = :id";

		$stmt = $this->conn->prepare( $query );
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$info = $stmt->fetch(PDO::FETCH_ASSOC);

		// Fallback: se times_inseridos for 0, verificar contagem de clubes no arquivo SQLite da competição
		if(isset($info['times_inseridos']) && intval($info['times_inseridos']) == 0){
			$db3File = $_SERVER['DOCUMENT_ROOT']."/competicoes/databases/".$id."-database.db3";
			if(file_exists($db3File)){
				try {
					include_once($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");
					$sqliteDb = new SQLiteDatabase();
					$sqliteDb->fileName = $db3File;
					$sdb = $sqliteDb->getConnection();
					if($sdb){
						$stmtCount = $sdb->query("SELECT COUNT(*) as total FROM clube");
						if($stmtCount){
							$resCount = $stmtCount->fetch(PDO::FETCH_ASSOC);
							if($resCount && isset($resCount['total'])){
								$info['times_inseridos'] = intval($resCount['total']);
							}
						}
					}
				} catch (Exception $e) {
					// Ignora falhas no SQLite
				}
			}
		}

		return $info;
    }

	function getOptions($id){

		$id = htmlspecialchars(strip_tags($id));
		$query = "SELECT
                numero_times, limite_fichas, subir_live, sorteio, golfora, finalunica, tipocompeticao, criteriodesempate, criteriodesempatefinal, suspensao, zeraramarelos, alteracoeselenco, inicioalteracoes, fimalteracoes, jogadoresadicionais, estadios_times, desempate_grupos, num_grupos, times_por_grupo, tipo_preliminar, turnos_pontos_corridos  
            FROM
                competicao_opcoes 
            WHERE
                id_competicao = :idComp";

		$stmt = $this->conn->prepare( $query );
		$stmt->bindParam(":idComp", $id);
		$stmt->execute();
		$options = $stmt->fetch(PDO::FETCH_ASSOC);
		return $options;
    }
	
	function alterarOpcoes($idUsuario, $numero_times, $data_limite, $subir_live, $sorteio, $gol_fora, $final_unica, $tipo_competicao, $criterio_desempate, $criterio_desempate_final, $criterio_suspensao, $zerar_amarelos, $permitir_alteracoes, $inicio_alteracoes, $fim_alteracoes, $numero_alteracoes, $id_competicao, $estadios_times = 1, $desempate_grupos = 'SG,GP,VI,CD', $num_grupos = 4, $times_por_grupo = 4, $tipo_preliminar = 1, $turnos_pontos_corridos = 2){
	
			$idUsuario = htmlspecialchars(strip_tags($idUsuario));
			$numero_times = htmlspecialchars(strip_tags($numero_times));
			$data_limite = htmlspecialchars(strip_tags($data_limite));
			$subir_live = htmlspecialchars(strip_tags($subir_live));
			$sorteio = htmlspecialchars(strip_tags($sorteio));
			$gol_fora = htmlspecialchars(strip_tags($gol_fora));
			$final_unica = htmlspecialchars(strip_tags($final_unica));
			$tipo_competicao = htmlspecialchars(strip_tags($tipo_competicao));
			$criterio_desempate = htmlspecialchars(strip_tags($criterio_desempate));
			$criterio_desempate_final = htmlspecialchars(strip_tags($criterio_desempate_final));
			$criterio_suspensao = htmlspecialchars(strip_tags($criterio_suspensao));
			$zerar_amarelos = htmlspecialchars(strip_tags($zerar_amarelos));
			$permitir_alteracoes = htmlspecialchars(strip_tags($permitir_alteracoes));
			$inicio_alteracoes = htmlspecialchars(strip_tags($inicio_alteracoes));
			$fim_alteracoes = htmlspecialchars(strip_tags($fim_alteracoes));
			$numero_alteracoes = htmlspecialchars(strip_tags($numero_alteracoes));
			$estadios_times = htmlspecialchars(strip_tags($estadios_times));
			$desempate_grupos = htmlspecialchars(strip_tags($desempate_grupos));
			$num_grupos = intval($num_grupos) > 0 ? intval($num_grupos) : 4;
			$times_por_grupo = intval($times_por_grupo) > 0 ? intval($times_por_grupo) : 4;
			$tipo_preliminar = intval($tipo_preliminar);
			$turnos_pontos_corridos = (intval($turnos_pontos_corridos) >= 1 && intval($turnos_pontos_corridos) <= 4) ? intval($turnos_pontos_corridos) : 2;

			if($numero_alteracoes == "") $numero_alteracoes = 0;
			if($inicio_alteracoes == "") $inicio_alteracoes = null;
			if($fim_alteracoes == "") $fim_alteracoes = null;

			$id_competicao = htmlspecialchars(strip_tags($id_competicao));
			
			$query = "UPDATE competicao_opcoes 
            SET
                 numero_times =:numero_times, limite_fichas=:limite_fichas, subir_live=:subir_live, sorteio=:sorteio, golfora=:golfora, finalunica=:finalunica, tipocompeticao=:tipocompeticao, criteriodesempate=:criteriodesempate, criteriodesempatefinal=:criteriodesempatefinal, suspensao=:suspensao, zeraramarelos=:zeraramarelos, alteracoeselenco=:alteracoeselenco, inicioalteracoes=:inicioalteracoes, fimalteracoes=:fimalteracoes, jogadoresadicionais=:numeroalteracoes, estadios_times=:estadios_times, desempate_grupos=:desempate_grupos, num_grupos=:num_grupos, times_por_grupo=:times_por_grupo, tipo_preliminar=:tipo_preliminar, turnos_pontos_corridos=:turnos_pontos_corridos   
             WHERE
                id_competicao = :idComp";

		$stmt = $this->conn->prepare( $query );
		$stmt->bindParam(":idComp", $id_competicao);
		$stmt->bindParam(":numero_times", $numero_times);
		$stmt->bindParam(":limite_fichas", $data_limite);
		$stmt->bindParam(":subir_live", $subir_live);
		$stmt->bindParam(":sorteio", $sorteio);
		$stmt->bindParam(":golfora", $gol_fora);
		$stmt->bindParam(":finalunica", $final_unica);
		$stmt->bindParam(":tipocompeticao", $tipo_competicao);
		$stmt->bindParam(":criteriodesempate", $criterio_desempate);
		$stmt->bindParam(":criteriodesempatefinal", $criterio_desempate_final);
		$stmt->bindParam(":suspensao", $criterio_suspensao);
		$stmt->bindParam(":zeraramarelos", $zerar_amarelos);
		$stmt->bindParam(":alteracoeselenco", $permitir_alteracoes);
		$stmt->bindParam(":inicioalteracoes", $inicio_alteracoes);
		$stmt->bindParam(":fimalteracoes", $fim_alteracoes);
		$stmt->bindParam(":numeroalteracoes", $numero_alteracoes);
		$stmt->bindParam(":estadios_times", $estadios_times);
		$stmt->bindParam(":desempate_grupos", $desempate_grupos);
		$stmt->bindParam(":num_grupos", $num_grupos);
		$stmt->bindParam(":times_por_grupo", $times_por_grupo);
		$stmt->bindParam(":tipo_preliminar", $tipo_preliminar);
		$stmt->bindParam(":turnos_pontos_corridos", $turnos_pontos_corridos);
		
		if($stmt->execute()){
			return true;
		} else {
			return false;
		}
	}
	



	function checkOptionsFilled($id){
		
		$id = htmlspecialchars(strip_tags($id));
		
		$query = "select * from competicao_opcoes where concat(numero_times,limite_fichas,subir_live,sorteio,golfora,finalunica,tipocompeticao,criteriodesempate,
                                criteriodesempatefinal,suspensao,zeraramarelos,alteracoeselenco,jogadoresadicionais	)
								is null AND id_competicao=:id";
								
		$stmt = $this->conn->prepare( $query );
		$stmt->bindParam(":id", $id);
		
		$stmt->execute();
		$check = $stmt->fetch(PDO::FETCH_ASSOC);
		return $check;
	}
	
	function carregarListaTimes($idCompeticao){
		
		$idCompeticao = htmlspecialchars(strip_tags($idCompeticao));
		
		$query = "SELECT codigo_time, pais_time, has_team, id_time_portal, slot FROM competicao_times WHERE id_competicao = :id_competicao ORDER BY codigo_time ASC ";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(":id_competicao", $idCompeticao);

        $stmt->execute();
		return $stmt;
		
	}
	function carregarListaJogos($idCompeticao){
		$idCompeticao = htmlspecialchars(strip_tags($idCompeticao));
		
		$query = "SELECT j.id, j.timeA_id, j.timeB_id, j.timeA_nome, j.timeB_nome, j.timeA_gols, j.timeB_gols, j.timeA_penaltis, j.timeB_penaltis,
		                 j.data, j.competicao_id as competicao, j.estadio_id as estadio, j.neutro, j.arbitro_id as arbitro,
		                 j.fase, j.grupo, j.path, j.status, j.simulador_interno,
		                 (SELECT p.dono FROM clube c LEFT JOIN paises p ON c.Pais = p.id WHERE c.ID = j.timeA_id LIMIT 1) as idDonoPais
		          FROM jogos_clube j
		          WHERE j.competicao_id = :id_competicao AND j.simulador_interno = 1
		          ORDER BY j.data ASC ";
		$stmt = $this->conn->prepare( $query );

		$stmt->bindParam(":id_competicao", $idCompeticao);

		$stmt->execute();
		return $stmt;
	}
	
	function getMatchInfo($matchId){
		$matchId = htmlspecialchars(strip_tags($matchId));
		
		$query = "SELECT j.id, j.timeA_id, j.timeB_id, j.timeA_gols, j.timeB_gols, j.timeA_penaltis, j.timeB_penaltis,
		                 j.data, j.competicao_id as competicao, j.estadio_id as estadio, j.neutro, j.arbitro_id as arbitro,
		                 j.fase, j.grupo, j.path, j.status, j.simulador_interno,
		                 COALESCE(j.timeA_nome, cA.Nome) as timeA_nome,
		                 COALESCE(j.timeB_nome, cB.Nome) as timeB_nome
		          FROM jogos_clube j
		          LEFT JOIN clube cA ON j.timeA_id = cA.ID
		          LEFT JOIN clube cB ON j.timeB_id = cB.ID
		          WHERE j.id = :id";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(":id", $matchId);

        $stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return $result;

	}
	
	function alterarTimePortal($id_competicao, $codigo_time, $pais_time, $id_time_portal){
		$query = "REPLACE INTO competicao_times (id_competicao, codigo_time, pais_time, id_time_portal, has_team) VALUES (:id_competicao, :codigo_time, :pais_time, :id_time_portal, '1')";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(':id_competicao', $id_competicao);
		$stmt->bindParam(':codigo_time', $codigo_time);
		$stmt->bindParam(':pais_time', $pais_time);
		$stmt->bindParam(':id_time_portal', $id_time_portal);
		return $stmt->execute();
	}

	function alterarPaisTime($id_competicao, $codigo_time, $id_pais){
		// Busca se já existe um time portal ou se é apenas troca de país
		$query = "REPLACE INTO competicao_times (id_competicao, codigo_time, pais_time, id_time_portal, has_team) 
				  SELECT :id_competicao, :codigo_time, :id_pais, id_time_portal, has_team 
				  FROM (SELECT :id_competicao2 as id_c, :codigo_time2 as cod_t) AS tmp
				  LEFT JOIN competicao_times ON id_competicao = id_c AND codigo_time = cod_t";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(':id_competicao', $id_competicao);
		$stmt->bindParam(':codigo_time', $codigo_time);
		$stmt->bindParam(':id_pais', $id_pais);
		$stmt->bindParam(':id_competicao2', $id_competicao);
		$stmt->bindParam(':codigo_time2', $codigo_time);
		return $stmt->execute();
	}

	function definirMesmoPaisTodos($id_competicao, $id_pais, $numero_times){
		$id_competicao = intval($id_competicao);
		$id_pais = intval($id_pais);
		$numero_times = intval($numero_times);
		
		for($cod = 1; $cod <= $numero_times; $cod++){
			// Proteção: se a vaga já possui um time escalado (has_team = 1 ou id_time_portal definido), preserva o time e não sobrescreve
			$st = $this->conn->prepare("SELECT has_team, id_time_portal FROM competicao_times WHERE id_competicao = :idComp AND codigo_time = :cod LIMIT 1");
			$st->bindParam(':idComp', $id_competicao);
			$st->bindParam(':cod', $cod);
			$st->execute();
			$row = $st->fetch(PDO::FETCH_ASSOC);
			if ($row && (!empty($row['id_time_portal']) || $row['has_team'] == '1')) {
				continue;
			}

			$this->alterarPaisTime($id_competicao, $cod, $id_pais);
		}
		return true;
	}
	
	function gravarImportacao($id_competicao, $codigo_time, $pais_time){
		// Garante que a linha existe, marca como has_team = 1 e id_time_portal = NULL
		$query = "INSERT INTO competicao_times (id_competicao, codigo_time, pais_time, id_time_portal, has_team) 
				  VALUES (:id_competicao, :codigo_time, :pais_time, NULL, '1') 
				  ON DUPLICATE KEY UPDATE pais_time = :pais_time2, id_time_portal = NULL, has_team = '1'";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(':id_competicao', $id_competicao);
		$stmt->bindParam(':codigo_time', $codigo_time);
		$stmt->bindParam(':pais_time', $pais_time);
		$stmt->bindParam(':pais_time2', $pais_time);
		return $stmt->execute();
	}
	
	
		function lerFases(){
		
		$query = "SELECT id, nome FROM fase";
        $stmt = $this->conn->prepare( $query );

        $stmt->execute();
		return $stmt;
		
	}
	
	function inserirJogo($id_competicao,$timeA,$timeB,$fase,$arbitro,$estadio, $datetime, $neutro, $grupo = null, $dono = null, $nomeA = null, $nomeB = null){
		
		$id_competicao = htmlspecialchars(strip_tags($id_competicao));
		$fase = htmlspecialchars(strip_tags($fase));
		$arbitro = htmlspecialchars(strip_tags($arbitro));
		$estadio = htmlspecialchars(strip_tags($estadio));
		$datetime = htmlspecialchars(strip_tags($datetime));						
		$neutro = htmlspecialchars(strip_tags($neutro));	

		if($grupo != null){
			$grupo = htmlspecialchars(strip_tags($grupo));	
		}
		
		if($neutro == "false" || $neutro === false){
            $neutro = 0;
        } else if ($neutro == "true" || $neutro === true){
            $neutro = 1;
        }

		$timeA_id = (is_numeric($timeA) && intval($timeA) > 0) ? intval($timeA) : 0;
		$timeB_id = (is_numeric($timeB) && intval($timeB) > 0) ? intval($timeB) : 0;

		$timeA_nome = $nomeA ? htmlspecialchars(strip_tags($nomeA)) : (!is_numeric($timeA) && !empty($timeA) ? htmlspecialchars(strip_tags($timeA)) : null);
		$timeB_nome = $nomeB ? htmlspecialchars(strip_tags($nomeB)) : (!is_numeric($timeB) && !empty($timeB) ? htmlspecialchars(strip_tags($timeB)) : null);
		
		$query = "INSERT INTO jogos_clube (timeA_id, timeA_nome, timeB_id, timeB_nome, data, competicao_id, estadio_id, neutro, arbitro_id, fase, grupo, competicao_tipo, simulador_interno, status, dono) 
		          VALUES (:timeA, :nomeA, :timeB, :nomeB, :data, :competicao, :estadio, :neutro, :arbitro, :fase, :grupo, 1, 1, 0, COALESCE(:dono, (SELECT dono FROM " . $this->table_name . " WHERE id = :competicao_dono LIMIT 1), 0))";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(":timeA", $timeA_id);
        $stmt->bindParam(":nomeA", $timeA_nome);
        $stmt->bindParam(":timeB", $timeB_id);
        $stmt->bindParam(":nomeB", $timeB_nome);
		$stmt->bindParam(":data", $datetime);
		$stmt->bindParam(":competicao", $id_competicao);
		$stmt->bindParam(":competicao_dono", $id_competicao);
		$stmt->bindParam(":estadio", $estadio);
		$stmt->bindParam(":neutro", $neutro);
		$stmt->bindParam(":arbitro", $arbitro);
		$stmt->bindParam(":fase", $fase);
		$stmt->bindParam(":grupo", $grupo);
		$stmt->bindValue(":dono", $dono, $dono === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }
	}
	
	function getColors(){
		$query = "SELECT (SELECT  valor as cor1 FROM opcoes WHERE parametro = 'partidaCor1' ) as partidaCor1, (SELECT  valor as cor2 FROM opcoes WHERE parametro = 'partidaCor2') as partidaCor2, (SELECT  valor as cor3 FROM opcoes WHERE parametro = 'partidaCor3') as partidaCor3";
		$stmt = $this->conn->query($query);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return $result;
	}
	
	function uploadMatchResults($idPartida, $golsTimeA, $golsTimeB, $path, $penA = null, $penB = null){
		$idPartida = htmlspecialchars(strip_tags($idPartida));
		$golsTimeA = htmlspecialchars(strip_tags($golsTimeA));
		$golsTimeB = htmlspecialchars(strip_tags($golsTimeB));
		$path = htmlspecialchars(strip_tags($path));	

		$query = "UPDATE jogos_clube 
					SET 
						timeA_gols = :timeA_gols,
						timeB_gols = :timeB_gols,
						timeA_penaltis = :timeA_penaltis,
						timeB_penaltis = :timeB_penaltis,
						path = :path,
						status = 1 
					WHERE
						id = :id";
			
		$stmt = $this->conn->prepare( $query );

        $stmt->bindParam(":timeA_gols", $golsTimeA);
        $stmt->bindParam(":timeB_gols", $golsTimeB);
        if($penA !== null && $penA !== '') {
            $stmt->bindValue(":timeA_penaltis", (int)$penA, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":timeA_penaltis", null, PDO::PARAM_NULL);
        }
        if($penB !== null && $penB !== '') {
            $stmt->bindValue(":timeB_penaltis", (int)$penB, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":timeB_penaltis", null, PDO::PARAM_NULL);
        }
        $stmt->bindParam(":path", $path);
		$stmt->bindParam(":id", $idPartida);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }
	}
	
	function getTeamColors($uniforme, $idTime){
		
		$idTime = htmlspecialchars(strip_tags($idTime));
		
		if($uniforme == 2){
			$query = "SELECT Uni2Cor1 as cor1, Uni2Cor2 as cor2, Uni2Cor3 as cor3 FROM clube WHERE ID = :id";

		} else {
			$query = "SELECT Uni1Cor1 as cor1, Uni1Cor2 as cor2, Uni1Cor3 as cor3 FROM clube WHERE ID = :id";
		}
		
		$stmt = $this->conn->query($query);
		$stmt->bindParam(":id", $idTime);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return $result;
	}
	
	function getTeamUniform($uniforme, $idTime){
		
		$idTime = htmlspecialchars(strip_tags($idTime));
		
		if($uniforme == 2){
			$query = "SELECT Uniforme2 as uniforme FROM clube WHERE ID = :id";

		} else {
			$query = "SELECT Uniforme1 as uniforme FROM clube WHERE ID = :id";
		}
		
		$stmt = $this->conn->query($query);
		$stmt->bindParam(":id", $idTime);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return $result;
	}
	
	function getTeamPlayers($idTime){
		$idTime = htmlspecialchars(strip_tags($idTime));
		
		$query = "SELECT t1.jogador as ID, j.Nome, j.Idade, j.Nivel FROM (SELECT Jogador1 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador2 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador3 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador4 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador5 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador6 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador7 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador8 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador9 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador10 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador11 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador12 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador13 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador14 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador15 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador16 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador17 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador18 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador19 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador20 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador21 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador22 as jogador FROM elenco WHERE Clube = ?
			UNION
			SELECT Jogador23 as jogador FROM elenco WHERE Clube = ?) t1

			LEFT JOIN jogador j ON t1.jogador = j.ID";

		$stmt = $this->conn->query($query);
		for($i = 1;$i <= 23; $i++){
			$stmt->bindValue($i, $idTime);
		}
		
		$stmt->execute();
		
		$listaJogadores = array();
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			extract($row);
			$addArray = array("nome" => $Nome, "nivel" => $Nivel, "idade" => $Idade);
			$listaJogadores[$ID] = $addArray;
		}
		return $listaJogadores;
	}
	
	function getNomeEstadio($idEstadio){
		$idEstadio = htmlspecialchars(strip_tags($idEstadio));
		
		$query = "SELECT  Nome from estadio WHERE ID = :id";
		$stmt = $this->conn->query($query);
		$stmt->bindParam(":id", $idEstadio);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return $result['Nome'];
	}
	function limparJogos($id_competicao){
		$id_competicao = htmlspecialchars(strip_tags($id_competicao));
		
		// 1. Limpar eventos e escalações associados aos jogos desta competição
		$querySub = "SELECT id FROM jogos_clube WHERE competicao_id = :id_comp1 AND simulador_interno = 1";
		$stmtSub = $this->conn->prepare($querySub);
		$stmtSub->bindParam(":id_comp1", $id_competicao);
		$stmtSub->execute();
		$matchIds = $stmtSub->fetchAll(PDO::FETCH_COLUMN);
		
		if(!empty($matchIds)){
			$inQuery = implode(',', array_map('intval', $matchIds));
			$this->conn->exec("DELETE FROM jogos_clube_eventos WHERE id_jogo IN ($inQuery)");
			$this->conn->exec("DELETE FROM jogos_clube_escalacao WHERE id_partida IN ($inQuery)");
		}

		// 2. Apagar os jogos da tabela unificada
		$query = "DELETE FROM jogos_clube WHERE competicao_id = :id_competicao AND simulador_interno = 1";
		$stmt = $this->conn->prepare( $query );
		$stmt->bindParam(":id_competicao", $id_competicao);
		if($stmt->execute()){
			return true;
		} else {
			return false;
		}
	}

	function getSlotInfoForVaga($idCompeticao, $vagaIndex){
		$options = $this->getOptions($idCompeticao);
		$tipo = isset($options['tipocompeticao']) ? intval($options['tipocompeticao']) : 0;
		$vagaIndex = intval($vagaIndex);
		
		if ($tipo == 0) { // Misto
			$numGroups = (isset($options['num_grupos']) && intval($options['num_grupos']) > 0) ? intval($options['num_grupos']) : 4;
			$teamsPerGroup = (isset($options['times_por_grupo']) && intval($options['times_por_grupo']) > 0) ? intval($options['times_por_grupo']) : 4;
			$capacidadeGrupos = $numGroups * $teamsPerGroup;
			$numeroTimes = isset($options['numero_times']) ? intval($options['numero_times']) : $capacidadeGrupos;
			$totalTeams = max($numeroTimes, $capacidadeGrupos);
			$excedente = $totalTeams > $capacidadeGrupos ? ($totalTeams - $capacidadeGrupos) : 0;
			$numPreliminar = $excedente * 2;
			
			if ($vagaIndex <= $numPreliminar) {
				return ['slot' => "P" . $vagaIndex, 'fase' => 1, 'grupo' => 'P'];
			} else {
				$groupVaga = $vagaIndex - $numPreliminar - 1;
				$g = intdiv($groupVaga, $teamsPerGroup);
				$k = ($groupVaga % $teamsPerGroup) + 1;
				$groupLetter = chr(65 + $g);
				return ['slot' => $groupLetter . $k, 'fase' => 2, 'grupo' => $groupLetter];
			}
		} else if ($tipo == 1) { // Mata-mata
			return ['slot' => "Slot " . $vagaIndex, 'fase' => null, 'grupo' => null];
		} else { // Pontos corridos
			return ['slot' => "Slot " . $vagaIndex, 'fase' => 2, 'grupo' => null];
		}
	}

	function getSlotNameForVaga($idCompeticao, $vagaIndex){
		$info = $this->getSlotInfoForVaga($idCompeticao, $vagaIndex);
		return $info ? $info['slot'] : "Slot " . $vagaIndex;
	}

	function atualizarJogosPorSlot($idCompeticao, $vagaIndex, $idTimeReal, $prevTimeId = 0){
		$info = $this->getSlotInfoForVaga($idCompeticao, $vagaIndex);
		if (!$info) return false;
		
		$slotName = $info['slot'];
		$fase = $info['fase'];
		$grupo = $info['grupo'];
		
		$idCompeticao = intval($idCompeticao);
		$idTimeReal = intval($idTimeReal);
		$prevTimeId = intval($prevTimeId);
		
		$whereExtraA = "";
		$whereExtraB = "";
		if ($grupo !== null && $grupo !== '') {
			$whereExtraA = " AND (grupo = '$grupo' OR grupo IS NULL) ";
			$whereExtraB = " AND (grupo = '$grupo' OR grupo IS NULL) ";
		}
		
		if ($idTimeReal > 0) {
			// 1. Atualizar por nome do slot (placeholder)
			$this->conn->exec("UPDATE jogos_clube 
			                   SET timeA_id = $idTimeReal, timeA_nome = NULL 
			                   WHERE competicao_id = $idCompeticao 
			                     AND simulador_interno = 1 
			                     AND timeA_nome = '$slotName' $whereExtraA");
			                     
			$this->conn->exec("UPDATE jogos_clube 
			                   SET timeB_id = $idTimeReal, timeB_nome = NULL 
			                   WHERE competicao_id = $idCompeticao 
			                     AND simulador_interno = 1 
			                     AND timeB_nome = '$slotName' $whereExtraB");

			// 2. Se estava substituindo um time anterior
			if ($prevTimeId > 0 && $prevTimeId != $idTimeReal) {
				$this->conn->exec("UPDATE jogos_clube 
				                   SET timeA_id = $idTimeReal 
				                   WHERE competicao_id = $idCompeticao 
				                     AND simulador_interno = 1 
				                     AND timeA_id = $prevTimeId $whereExtraA");
				                     
				$this->conn->exec("UPDATE jogos_clube 
				                   SET timeB_id = $idTimeReal 
				                   WHERE competicao_id = $idCompeticao 
				                     AND simulador_interno = 1 
				                     AND timeB_id = $prevTimeId $whereExtraB");
			}
			
			// 3. Atualizar estádios dos jogos mandantes se estádio for por time
			$options = $this->getOptions($idCompeticao);
			$estadios_times = isset($options['estadios_times']) ? intval($options['estadios_times']) : 1;
			if ($estadios_times == 1) {
				$db3File = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $idCompeticao . "-database.db3";
				if (file_exists($db3File)) {
					$liteDb = new SQLiteDatabase();
					$liteDb->fileName = $db3File;
					$sdb = $liteDb->getConnection();
					if ($sdb) {
						$stClube = $sdb->prepare("SELECT Estadio FROM clube WHERE ID = :id");
						$stClube->bindParam(':id', $idTimeReal);
						$stClube->execute();
						$rC = $stClube->fetch(PDO::FETCH_ASSOC);
						if ($rC && !empty($rC['Estadio'])) {
							$estId = intval($rC['Estadio']);
							$stUpdEst = $this->conn->prepare("UPDATE jogos_clube 
							                                  SET estadio_id = :estId 
							                                  WHERE competicao_id = :idComp 
							                                    AND simulador_interno = 1 
							                                    AND timeA_id = :idTime");
							$stUpdEst->bindParam(':estId', $estId, PDO::PARAM_INT);
							$stUpdEst->bindParam(':idComp', $idCompeticao, PDO::PARAM_INT);
							$stUpdEst->bindParam(':idTime', $idTimeReal, PDO::PARAM_INT);
							$stUpdEst->execute();
						}
					}
				}
			}
		} else {
			// Resetando para o placeholder do slot
			if ($prevTimeId > 0) {
				$this->conn->exec("UPDATE jogos_clube 
				                   SET timeA_id = 0, timeA_nome = '$slotName' 
				                   WHERE competicao_id = $idCompeticao 
				                     AND simulador_interno = 1 
				                     AND timeA_id = $prevTimeId $whereExtraA");
				                     
				$this->conn->exec("UPDATE jogos_clube 
				                   SET timeB_id = 0, timeB_nome = '$slotName' 
				                   WHERE competicao_id = $idCompeticao 
				                     AND simulador_interno = 1 
				                     AND timeB_id = $prevTimeId $whereExtraB");
			}
		}
		
		return true;
	}

	function definirSlotTime($idCompeticao, $codigoTime, $slotName){
		$idCompeticao = intval($idCompeticao);
		$codigoTime = intval($codigoTime);
		$slotName = trim($slotName);
		
		// 1. Descobrir qual time está nesta vaga
		$st = $this->conn->prepare("SELECT pais_time, has_team, id_time_portal, slot FROM competicao_times WHERE id_competicao = :idComp AND codigo_time = :cod LIMIT 1");
		$st->bindParam(':idComp', $idCompeticao);
		$st->bindParam(':cod', $codigoTime);
		$st->execute();
		$row = $st->fetch(PDO::FETCH_ASSOC);
		if(!$row) return false;
		
		$prevSlot = trim($row['slot'] ?? '');
		$teamId = 0;
		if(!empty($row['id_time_portal']) && intval($row['id_time_portal']) > 0){
			$teamId = intval($row['id_time_portal']);
		} else if(isset($row['has_team']) && ($row['has_team'] == 1 || $row['has_team'] == '1')){
			$teamId = -1 * abs($codigoTime);
		}
		
		// 2. Se outro time já tinha esse mesmo $slotName, desvincular o outro time desse slot
		if($slotName !== ''){
			$stClearOther = $this->conn->prepare("UPDATE competicao_times SET slot = NULL WHERE id_competicao = :idComp AND slot = :slot AND codigo_time != :cod");
			$stClearOther->bindParam(':idComp', $idCompeticao);
			$stClearOther->bindParam(':slot', $slotName);
			$stClearOther->bindParam(':cod', $codigoTime);
			$stClearOther->execute();
		}
		
		// 3. Atualizar o slot deste time na tabela competicao_times
		$newSlotVal = ($slotName === '') ? null : $slotName;
		$stUpd = $this->conn->prepare("UPDATE competicao_times SET slot = :slot WHERE id_competicao = :idComp AND codigo_time = :cod");
		$stUpd->bindParam(':slot', $newSlotVal);
		$stUpd->bindParam(':idComp', $idCompeticao);
		$stUpd->bindParam(':cod', $codigoTime);
		$stUpd->execute();
		
		// 4. Se o time tinha um slot anterior e ele mudou, restaurar o placeholder do slot anterior nos jogos
		if($prevSlot !== '' && $prevSlot !== $slotName && $teamId != 0){
			$this->conn->exec("UPDATE jogos_clube SET timeA_id = 0, timeA_nome = '$prevSlot' WHERE competicao_id = $idCompeticao AND simulador_interno = 1 AND timeA_id = $teamId");
			$this->conn->exec("UPDATE jogos_clube SET timeB_id = 0, timeB_nome = '$prevSlot' WHERE competicao_id = $idCompeticao AND simulador_interno = 1 AND timeB_id = $teamId");
		}
		
		// 5. Se o novo slot foi definido e temos um time, preencher os jogos com este time
		if($slotName !== '' && $teamId != 0){
			$this->conn->exec("UPDATE jogos_clube SET timeA_id = $teamId, timeA_nome = NULL WHERE competicao_id = $idCompeticao AND simulador_interno = 1 AND timeA_nome = '$slotName'");
			$this->conn->exec("UPDATE jogos_clube SET timeB_id = $teamId, timeB_nome = NULL WHERE competicao_id = $idCompeticao AND simulador_interno = 1 AND timeB_nome = '$slotName'");
			
			// Atualizar estádio mandante se estadios_times == 1
			$options = $this->getOptions($idCompeticao);
			$estadios_times = isset($options['estadios_times']) ? intval($options['estadios_times']) : 1;
			if ($estadios_times == 1 && $teamId > 0) {
				$db3File = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $idCompeticao . "-database.db3";
				if (file_exists($db3File)) {
					$liteDb = new SQLiteDatabase();
					$liteDb->fileName = $db3File;
					$sdb = $liteDb->getConnection();
					if ($sdb) {
						$stClube = $sdb->prepare("SELECT Estadio FROM clube WHERE ID = :id");
						$stClube->bindParam(':id', $teamId);
						$stClube->execute();
						$rC = $stClube->fetch(PDO::FETCH_ASSOC);
						if ($rC && !empty($rC['Estadio'])) {
							$estId = intval($rC['Estadio']);
							$stUpdEst = $this->conn->prepare("UPDATE jogos_clube 
							                                  SET estadio_id = :estId 
							                                  WHERE competicao_id = :idComp 
							                                    AND simulador_interno = 1 
							                                    AND timeA_id = :idTime");
							$stUpdEst->bindParam(':estId', $estId, PDO::PARAM_INT);
							$stUpdEst->bindParam(':idComp', $idCompeticao, PDO::PARAM_INT);
							$stUpdEst->bindParam(':idTime', $teamId, PDO::PARAM_INT);
							$stUpdEst->execute();
						}
					}
				}
			}
		}
		
		return true;
	}

	function isClubeAtivoNaCompeticao($idCompeticao, $idClube) {
		$idCompeticao = (int)$idCompeticao;
		$idClube = (int)$idClube;
		if ($idCompeticao <= 0 || $idClube <= 0) return false;

		// 1. Se a Final (fase 8) já foi jogada, a competição encerrou para todos os times
		$stmtFinal = $this->conn->prepare("SELECT 1 FROM jogos_clube WHERE competicao_id = :idComp AND fase = 8 AND status = 1 LIMIT 1");
		$stmtFinal->execute([':idComp' => $idCompeticao]);
		if ($stmtFinal->fetch()) {
			return false;
		}

		// 2. Se o time possui qualquer partida agendada (status = 0), ele está 100% ativo
		$stmtJogosPendentes = $this->conn->prepare("
			SELECT 1 FROM jogos_clube 
			WHERE competicao_id = :idComp 
			  AND (timeA_id = :idClube OR timeB_id = :idClube) 
			  AND status = 0 
			LIMIT 1
		");
		$stmtJogosPendentes->execute([':idComp' => $idCompeticao, ':idClube' => $idClube]);
		if ($stmtJogosPendentes->fetch()) {
			return true;
		}

		// 3. Buscar todas as partidas simuladas do time nesta competição
		$stmtJogos = $this->conn->prepare("
			SELECT id, timeA_id, timeB_id, timeA_gols, timeB_gols, timeA_penaltis, timeB_penaltis, fase, status 
			FROM jogos_clube 
			WHERE competicao_id = :idComp 
			  AND (timeA_id = :idClube OR timeB_id = :idClube)
			ORDER BY fase DESC, id DESC
		");
		$stmtJogos->execute([':idComp' => $idCompeticao, ':idClube' => $idClube]);
		$jogos = $stmtJogos->fetchAll(PDO::FETCH_ASSOC);

		if (empty($jogos)) {
			return true;
		}

		// Identificar a maior fase disputada pelo clube
		$maiorFase = 0;
		foreach ($jogos as $j) {
			if ((int)$j['fase'] > $maiorFase) {
				$maiorFase = (int)$j['fase'];
			}
		}

		// Se disputou mata-mata (fase > 2)
		if ($maiorFase > 2) {
			$jogosFase = array_filter($jogos, function($j) use ($maiorFase) {
				return (int)$j['fase'] === $maiorFase;
			});

			$golsPro = 0;
			$golsContra = 0;
			$penPro = null;
			$penContra = null;
			$tevePenaltis = false;

			foreach ($jogosFase as $jf) {
				if ((int)$jf['status'] == 1) {
					$isA = ((int)$jf['timeA_id'] === $idClube);
					$golsPro += (int)($isA ? $jf['timeA_gols'] : $jf['timeB_gols']);
					$golsContra += (int)($isA ? $jf['timeB_gols'] : $jf['timeA_gols']);

					$penA = $jf['timeA_penaltis'];
					$penB = $jf['timeB_penaltis'];
					if ($penA !== null && $penA !== '') {
						$tevePenaltis = true;
						$penPro = (int)($isA ? $penA : $penB);
						$penContra = (int)($isA ? $penB : $penA);
					}
				}
			}

			// Se foi eliminado no agregado ou nos pênaltis, não está mais ativo
			if ($tevePenaltis && $penPro !== null && $penContra !== null) {
				if ($penPro < $penContra) return false;
			} else {
				if ($golsPro < $golsContra) return false;
			}

			return true;
		}

		// Se a competição já avançou para o mata-mata (fase > 2) e o time não está lá
		$stmtMataMataOutros = $this->conn->prepare("SELECT 1 FROM jogos_clube WHERE competicao_id = :idComp AND fase > 2 LIMIT 1");
		$stmtMataMataOutros->execute([':idComp' => $idCompeticao]);
		if ($stmtMataMataOutros->fetch()) {
			return false;
		}

		// Se todos os jogos da competição já foram simulados e não há mais partidas pendentes
		$stmtTotalNaoJogados = $this->conn->prepare("SELECT 1 FROM jogos_clube WHERE competicao_id = :idComp AND status = 0 LIMIT 1");
		$stmtTotalNaoJogados->execute([':idComp' => $idCompeticao]);
		if (!$stmtTotalNaoJogados->fetch()) {
			return false;
		}

		return true;
	}
}
?>
