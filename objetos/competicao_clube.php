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
	public $trofeu;
	public $genero;
	public $tipo;
	public $dono;
 
    public function __construct($db){
        $this->conn = $db;
        $this->checkAndMigrateSchema();
    }

    public function checkAndMigrateSchema(){
        if (!$this->conn) {
            return;
        }
        try {
            $driver = '';
            try {
                $driver = strtolower($this->conn->getAttribute(PDO::ATTR_DRIVER_NAME));
            } catch (\Throwable $e) {}

            // Migração de coluna trofeu nas tabelas principais do MySQL
            if ($driver !== 'sqlite') {
                $checkMysqlCol = function($tableName, $colName, $colDef) {
                    $res = $this->conn->query("SHOW TABLES LIKE '{$tableName}'");
                    if ($res && $res->rowCount() > 0) {
                        $resCol = $this->conn->query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$colName}'");
                        if ($resCol && $resCol->rowCount() == 0) {
                            $this->conn->exec("ALTER TABLE `{$tableName}` ADD COLUMN `{$colName}` {$colDef}");
                        }
                    }
                };
                $checkMysqlCol('competicao_lista', 'trofeu', 'VARCHAR(255) NULL');
                $checkMysqlCol('liga', 'trofeu', 'VARCHAR(255) NULL');
                $checkMysqlCol('campeonatos_clube', 'trofeu', 'VARCHAR(255) NULL');
            }

            // 1. Garantir tabela competicao_alteracoes_log
            if ($driver === 'sqlite') {
                $this->conn->exec("
                    CREATE TABLE IF NOT EXISTS competicao_alteracoes_log (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        id_competicao INTEGER NOT NULL,
                        id_time INTEGER NOT NULL,
                        tipo_acao TEXT NOT NULL,
                        id_jogador_saiu INTEGER NULL,
                        id_jogador_entrou INTEGER NULL,
                        data_alteracao DATETIME DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_cal_comp ON competicao_alteracoes_log (id_competicao)");
                $this->conn->exec("CREATE INDEX IF NOT EXISTS idx_cal_time ON competicao_alteracoes_log (id_time)");

                // 2. Garantir colunas em competicao_opcoes no SQLite se a tabela existir
                $cols = [];
                $res = $this->conn->query("PRAGMA table_info(competicao_opcoes)");
                if ($res) {
                    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                        $cols[strtolower($row['name'])] = true;
                    }
                }
                if (!empty($cols)) {
                    $columnsToAdd = [
                        'alteracoeselenco' => "INTEGER DEFAULT 0",
                        'inicioalteracoes' => "TEXT NULL",
                        'fimalteracoes' => "TEXT NULL",
                        'jogadoresadicionais' => "INTEGER DEFAULT 0",
                        'expulso_dois_amarelos' => "INTEGER DEFAULT 0",
                        'tipo_preliminar' => "INTEGER DEFAULT 1",
                        'turnos_pontos_corridos' => "INTEGER DEFAULT 2",
                        'data_inicial' => "TEXT NULL",
                        'max_jogos_dia' => "INTEGER DEFAULT 0",
                        'dias_semana' => "TEXT DEFAULT ''",
                        'intervalo_rodadas' => "INTEGER DEFAULT 1",
                        'horarios_jogos' => "TEXT DEFAULT '16:00'",
                        'tipo_times' => "INTEGER DEFAULT 0",
                        'campeonato_ranking' => "INTEGER DEFAULT 10",
                        'capacidade_minima_estadio' => "INTEGER DEFAULT 0",
                        'capacidade_minima_final' => "INTEGER DEFAULT 0"
                    ];
                    foreach ($columnsToAdd as $col => $def) {
                        if (!isset($cols[$col])) {
                            $this->conn->exec("ALTER TABLE competicao_opcoes ADD COLUMN {$col} {$def}");
                        }
                    }
                }
            } else {
                $this->conn->exec("
                    CREATE TABLE IF NOT EXISTS competicao_alteracoes_log (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        id_competicao INT NOT NULL,
                        id_time INT NOT NULL,
                        tipo_acao VARCHAR(30) NOT NULL,
                        id_jogador_saiu INT NULL,
                        id_jogador_entrou INT NULL,
                        data_alteracao DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_cal_comp (id_competicao),
                        INDEX idx_cal_time (id_time)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");

                // 2. Garantir colunas em competicao_opcoes no MySQL
                $columnsToAdd = [
                    'alteracoeselenco' => "TINYINT(1) DEFAULT 0",
                    'inicioalteracoes' => "DATE NULL",
                    'fimalteracoes' => "DATE NULL",
                    'jogadoresadicionais' => "INT DEFAULT 0",
                    'expulso_dois_amarelos' => "TINYINT(1) DEFAULT 0",
                    'tipo_preliminar' => "TINYINT(1) DEFAULT 1",
                    'turnos_pontos_corridos' => "TINYINT(1) DEFAULT 2",
                    'data_inicial' => "DATE NULL",
                    'max_jogos_dia' => "INT DEFAULT 0",
                    'dias_semana' => "VARCHAR(50) DEFAULT ''",
                    'intervalo_rodadas' => "INT DEFAULT 1",
                    'horarios_jogos' => "VARCHAR(255) DEFAULT '16:00'",
                    'zonas_tabela' => "TEXT NULL",
                    'tipo_times' => "TINYINT(1) DEFAULT 0",
                    'campeonato_ranking' => "INT DEFAULT 10",
                    'capacidade_minima_estadio' => "INT DEFAULT 0",
                    'capacidade_minima_final' => "INT DEFAULT 0"
                ];

                foreach ($columnsToAdd as $col => $def) {
                    $res = $this->conn->query("SHOW COLUMNS FROM competicao_opcoes LIKE '{$col}'");
                    if ($res && $res->rowCount() == 0) {
                        $this->conn->exec("ALTER TABLE competicao_opcoes ADD COLUMN {$col} {$def}");
                    }
                }

                // Garantir coluna tipo_times em jogos_clube no MySQL
                $resJc = $this->conn->query("SHOW COLUMNS FROM jogos_clube LIKE 'tipo_times'");
                if ($resJc && $resJc->rowCount() == 0) {
                    $this->conn->exec("ALTER TABLE jogos_clube ADD COLUMN tipo_times TINYINT(1) DEFAULT 0");
                }

                // 3. Garantir tabela competicoes_agrupadas (Visões agregadas salvas)
                $this->conn->exec("
                    CREATE TABLE IF NOT EXISTS competicoes_agrupadas (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome VARCHAR(150) NOT NULL,
                        id_competicao_1 INT NOT NULL,
                        id_competicao_2 INT NOT NULL,
                        dono INT NOT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_ca_dono (dono),
                        INDEX idx_ca_comp1 (id_competicao_1),
                        INDEX idx_ca_comp2 (id_competicao_2)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
        } catch (Exception $e) {
            error_log("Erro ao verificar/migrar schema de competicao_opcoes: " . $e->getMessage());
        }
    }
 
    
    // used by select drop-down list
    function read(){
        //select all data
        $query = "SELECT
                    id, nome, ano, federacao, sede, path, logo, trofeu, genero, tipo 
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
                    nome=:nome, ano=:ano, federacao=:federacao, logo=:logo, trofeu=:trofeu, sede=:sede, genero=:genero, tipo=:tipo, dono=:dono, path=:path ";
					
		if($this->logo == ""){
			$this->logo = "0.png";
		}
		if($this->trofeu == ""){
			$this->trofeu = null;
		}

        $this->path = $this->ano . "-" . $this->nome;

        $stmt = $this->conn->prepare($query);
	

        // posted values
        $this->nome=htmlspecialchars(strip_tags($this->nome));
        $this->ano=htmlspecialchars(strip_tags($this->ano));
        $this->federacao=htmlspecialchars(strip_tags($this->federacao));
        $this->sede=htmlspecialchars(strip_tags($this->sede));
        $this->genero=htmlspecialchars(strip_tags($this->genero));
        $this->tipo=intval($this->tipo ?? 0);
		$this->logo=htmlspecialchars(strip_tags($this->logo));
		$this->trofeu=($this->trofeu !== null) ? htmlspecialchars(strip_tags((string)$this->trofeu)) : null;
		$this->dono=htmlspecialchars(strip_tags($this->dono));
        $this->path=htmlspecialchars(strip_tags($this->path));

        // bind values
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":ano", $this->ano);
        $stmt->bindParam(":logo", $this->logo);
        $stmt->bindParam(":trofeu", $this->trofeu);
        $stmt->bindParam(":sede", $this->sede);
        $stmt->bindParam(":genero", $this->genero);
        $stmt->bindParam(":tipo", $this->tipo);
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
    function readAllAjax($item_pesquisado, $dono = null){
		$item_pesquisado = htmlspecialchars(strip_tags($item_pesquisado));

		if($dono === null || $dono === 0 || $dono === '0' || $dono === ''){
			$query = "SELECT * FROM (
                        SELECT
                            a.id, a.nome, a.logo, a.trofeu, a.tipo, f.nome as federacao, f.id as idFederacao, p.id as idSede, p.nome as sede, a.ano, a.genero, p.sigla as siglaSede, p.bandeira as bandeiraSede, a.dono, a.dono as idDonoPais,
                            (SELECT COUNT(*) FROM jogos_clube jc WHERE jc.competicao_id = a.id AND (jc.status = 1 OR jc.timeA_gols IS NOT NULL)) as jogos_simulados,
                            0 as is_agrupada, 0 as id_competicao_1, 0 as id_competicao_2
                        FROM " . $this->table_name . " a
                        LEFT JOIN paises p ON a.sede = p.id
                        LEFT JOIN federacoes f ON a.federacao = f.id
                        UNION ALL
                        SELECT
                            ca.id, ca.nome, '0.png' as logo, NULL as trofeu, 99 as tipo, 'Tabela Acumulada' as federacao, 0 as idFederacao, 0 as idSede, '-' as sede, '' as ano, 0 as genero, '' as siglaSede, '' as bandeiraSede, ca.dono, ca.dono as idDonoPais,
                            0 as jogos_simulados,
                            1 as is_agrupada, ca.id_competicao_1, ca.id_competicao_2
                        FROM competicoes_agrupadas ca
                     ) t1 WHERE Nome LIKE ? ORDER BY is_agrupada DESC, Nome ASC LIMIT 150";

			$stmt = $this->conn->prepare( $query );
			$item_pesquisado = "%" . $item_pesquisado . "%";
			
			$stmt->bindParam(1, $item_pesquisado);

			$stmt->execute();

			return $stmt;
		} else {
			$dono = htmlspecialchars(strip_tags($dono));
			$query = "SELECT * FROM (
                        SELECT
                            a.id, a.nome, a.logo, a.trofeu, a.tipo, f.nome as federacao, f.id as idFederacao, p.id as idSede, p.nome as sede, a.ano, a.genero, p.sigla as siglaSede, p.bandeira as bandeiraSede, a.dono, a.dono as idDonoPais,
                            (SELECT COUNT(*) FROM jogos_clube jc WHERE jc.competicao_id = a.id AND (jc.status = 1 OR jc.timeA_gols IS NOT NULL)) as jogos_simulados,
                            0 as is_agrupada, 0 as id_competicao_1, 0 as id_competicao_2
                        FROM " . $this->table_name . " a
                        LEFT JOIN paises p ON a.sede = p.id
                        LEFT JOIN federacoes f ON a.federacao = f.id
                        UNION ALL
                        SELECT
                            ca.id, ca.nome, '0.png' as logo, NULL as trofeu, 99 as tipo, 'Tabela Acumulada' as federacao, 0 as idFederacao, 0 as idSede, '-' as sede, '' as ano, 0 as genero, '' as siglaSede, '' as bandeiraSede, ca.dono, ca.dono as idDonoPais,
                            0 as jogos_simulados,
                            1 as is_agrupada, ca.id_competicao_1, ca.id_competicao_2
                        FROM competicoes_agrupadas ca
                     ) t1 WHERE dono = ? AND Nome LIKE ? ORDER BY is_agrupada DESC, Nome ASC LIMIT 150";

			$stmt = $this->conn->prepare( $query );
			$item_pesquisado = "%" . $item_pesquisado . "%";
			
			$stmt->bindParam(1, $dono);
			$stmt->bindParam(2, $item_pesquisado);

			$stmt->execute();

			return $stmt;
		}
    }

    function salvarCompeticaoAgrupada($nome, $idComp1, $idComp2, $dono) {
        $nome = trim(strip_tags($nome));
        $idComp1 = (int)$idComp1;
        $idComp2 = (int)$idComp2;
        $dono = (int)$dono;

        if ($nome === '' || $idComp1 <= 0 || $idComp2 <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare("INSERT INTO competicoes_agrupadas (nome, id_competicao_1, id_competicao_2, dono) VALUES (:nome, :c1, :c2, :dono)");
        return $stmt->execute([
            ':nome' => $nome,
            ':c1' => $idComp1,
            ':c2' => $idComp2,
            ':dono' => $dono
        ]);
    }

    function excluirCompeticaoAgrupada($idAgrupada, $dono, $isAdmin = false) {
        $idAgrupada = (int)$idAgrupada;
        if ($idAgrupada <= 0) return false;

        if ($isAdmin) {
            $stmt = $this->conn->prepare("DELETE FROM competicoes_agrupadas WHERE id = :id");
            return $stmt->execute([':id' => $idAgrupada]);
        } else {
            $stmt = $this->conn->prepare("DELETE FROM competicoes_agrupadas WHERE id = :id AND dono = :dono");
            return $stmt->execute([':id' => $idAgrupada, ':dono' => $dono]);
        }
    }


    function alterar($id,$nome,$sede,$ano,$federacao,$logo = null, $tipo = null, $trofeu = null){

        $id = htmlspecialchars(strip_tags($id));
        $nome = htmlspecialchars(strip_tags($nome));
        $sede = htmlspecialchars(strip_tags($sede));
        $ano = htmlspecialchars(strip_tags($ano));
		$federacao = htmlspecialchars(strip_tags($federacao));
        $logo = ($logo !== null && $logo !== '') ? htmlspecialchars(strip_tags((string)$logo)) : null;
        $trofeu = ($trofeu !== null && $trofeu !== '') ? htmlspecialchars(strip_tags((string)$trofeu)) : null;

        $subquery = "";
        if($logo != null){
            $subquery .= ", logo=:logo";
        }
        if($trofeu != null){
            $subquery .= ", trofeu=:trofeu";
        }
        if($tipo !== null){
            $subquery .= ", tipo=:tipo";
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
        if($trofeu != null){
            $stmt->bindParam(":trofeu", $trofeu);
        }
        if($tipo !== null){
            $tipo = intval($tipo);
            $stmt->bindParam(":tipo", $tipo);
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
					a.nome, a.ano, a.tipo, a.sede as sedeId, f.nome as federacao, p.bandeira as sede, p.nome as sedeNome, a.logo, a.trofeu, a.genero, f.id as federacaoId, a.dono, o.numero_times as total_times, 
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
                numero_times, limite_fichas, subir_live, sorteio, golfora, finalunica, tipocompeticao, criteriodesempate, criteriodesempatefinal, suspensao, zeraramarelos, expulso_dois_amarelos, alteracoeselenco, inicioalteracoes, fimalteracoes, jogadoresadicionais, estadios_times, desempate_grupos, num_grupos, times_por_grupo, tipo_preliminar, turnos_pontos_corridos, data_inicial, max_jogos_dia, dias_semana, intervalo_rodadas, horarios_jogos, zonas_tabela, tipo_times, campeonato_ranking, capacidade_minima_estadio, capacidade_minima_final  
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
	
	function alterarOpcoes($idUsuario, $numero_times, $data_limite, $subir_live, $sorteio, $gol_fora, $final_unica, $tipo_competicao, $criterio_desempate, $criterio_desempate_final, $criterio_suspensao, $zerar_amarelos, $permitir_alteracoes, $inicio_alteracoes, $fim_alteracoes, $numero_alteracoes, $id_competicao, $estadios_times = 1, $desempate_grupos = 'SG,GP,VI,CD', $num_grupos = 4, $times_por_grupo = 4, $tipo_preliminar = 1, $turnos_pontos_corridos = 2, $data_inicial = null, $max_jogos_dia = 0, $dias_semana = '', $intervalo_rodadas = 1, $horarios_jogos = '16:00', $expulso_dois_amarelos = 0, $zonas_tabela = null, $tipo_times = 0, $campeonato_ranking = 10, $capacidade_minima_estadio = 0, $capacidade_minima_final = 0){
	
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
			$expulso_dois_amarelos = intval($expulso_dois_amarelos);
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
			$data_inicial = !empty($data_inicial) ? htmlspecialchars(strip_tags($data_inicial)) : null;
			$max_jogos_dia = intval($max_jogos_dia) >= 0 ? intval($max_jogos_dia) : 0;
			$dias_semana = htmlspecialchars(strip_tags($dias_semana));
			$intervalo_rodadas = intval($intervalo_rodadas) > 0 ? intval($intervalo_rodadas) : 1;
			$horarios_jogos = !empty($horarios_jogos) ? htmlspecialchars(strip_tags($horarios_jogos)) : '16:00';
			$zonas_tabela = !empty($zonas_tabela) ? $zonas_tabela : null;
			$tipo_times = intval($tipo_times) === 1 ? 1 : 0;
			$campeonato_ranking = intval($campeonato_ranking) > 0 ? intval($campeonato_ranking) : 10;
			$capacidade_minima_estadio = max(0, intval($capacidade_minima_estadio));
			$capacidade_minima_final = max(0, intval($capacidade_minima_final));

			if($numero_alteracoes == "") $numero_alteracoes = 0;
			if($inicio_alteracoes == "") $inicio_alteracoes = null;
			if($fim_alteracoes == "") $fim_alteracoes = null;

			$id_competicao = htmlspecialchars(strip_tags($id_competicao));
			
			$query = "UPDATE competicao_opcoes 
            SET
                 numero_times =:numero_times, limite_fichas=:limite_fichas, subir_live=:subir_live, sorteio=:sorteio, golfora=:golfora, finalunica=:finalunica, tipocompeticao=:tipocompeticao, criteriodesempate=:criteriodesempate, criteriodesempatefinal=:criteriodesempatefinal, suspensao=:suspensao, zeraramarelos=:zeraramarelos, expulso_dois_amarelos=:expulso_dois_amarelos, alteracoeselenco=:alteracoeselenco, inicioalteracoes=:inicioalteracoes, fimalteracoes=:fimalteracoes, jogadoresadicionais=:numeroalteracoes, estadios_times=:estadios_times, desempate_grupos=:desempate_grupos, num_grupos=:num_grupos, times_por_grupo=:times_por_grupo, tipo_preliminar=:tipo_preliminar, turnos_pontos_corridos=:turnos_pontos_corridos, data_inicial=:data_inicial, max_jogos_dia=:max_jogos_dia, dias_semana=:dias_semana, intervalo_rodadas=:intervalo_rodadas, horarios_jogos=:horarios_jogos, zonas_tabela=:zonas_tabela, tipo_times=:tipo_times, campeonato_ranking=:campeonato_ranking, capacidade_minima_estadio=:capacidade_minima_estadio, capacidade_minima_final=:capacidade_minima_final   
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
		$stmt->bindParam(":expulso_dois_amarelos", $expulso_dois_amarelos);
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
		$stmt->bindParam(":data_inicial", $data_inicial);
		$stmt->bindParam(":max_jogos_dia", $max_jogos_dia);
		$stmt->bindParam(":dias_semana", $dias_semana);
		$stmt->bindParam(":intervalo_rodadas", $intervalo_rodadas);
		$stmt->bindParam(":horarios_jogos", $horarios_jogos);
		$stmt->bindParam(":zonas_tabela", $zonas_tabela);
		$stmt->bindParam(":tipo_times", $tipo_times);
		$stmt->bindParam(":campeonato_ranking", $campeonato_ranking);
		$stmt->bindParam(":capacidade_minima_estadio", $capacidade_minima_estadio);
		$stmt->bindParam(":capacidade_minima_final", $capacidade_minima_final);
		
		if($stmt->execute()){
			try {
				$stmtUpdJc = $this->conn->prepare("UPDATE jogos_clube SET tipo_times = :tipo_times WHERE competicao_id = :idComp");
				$stmtUpdJc->bindValue(':tipo_times', $tipo_times, PDO::PARAM_INT);
				$stmtUpdJc->bindValue(':idComp', $id_competicao, PDO::PARAM_INT);
				$stmtUpdJc->execute();
			} catch (\Throwable $e) {}
			return true;
		} else {
			return false;
		}
	}
	



	function checkOptionsFilled($id){
		
		$id = htmlspecialchars(strip_tags((string)($id ?? '')));
		
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
		
		$idCompeticao = htmlspecialchars(strip_tags((string)($idCompeticao ?? '')));
		
		$query = "SELECT codigo_time, pais_time, has_team, id_time_portal, slot FROM competicao_times WHERE id_competicao = :id_competicao ORDER BY codigo_time ASC ";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(":id_competicao", $idCompeticao);

        $stmt->execute();
		return $stmt;
		
	}
	function carregarListaJogos($idCompeticao){
		$idCompeticao = htmlspecialchars(strip_tags((string)($idCompeticao ?? '')));
		
		$query = "SELECT j.id, j.timeA_id, j.timeB_id, j.timeA_nome, j.timeB_nome, j.timeA_gols, j.timeB_gols, j.timeA_penaltis, j.timeB_penaltis,
		                 j.data, j.competicao_id as competicao, j.estadio_id as estadio, j.neutro, j.arbitro_id as arbitro,
		                 j.fase, j.grupo, j.path, j.status, j.simulador_interno, j.subir_live,
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
		$matchId = htmlspecialchars(strip_tags((string)($matchId ?? '')));
		if (empty($matchId)) {
			return false;
		}
		
		$query = "SELECT j.id, j.timeA_id, j.timeB_id, j.timeA_gols, j.timeB_gols, j.timeA_penaltis, j.timeB_penaltis,
		                 j.data, j.competicao_id as competicao, j.estadio_id as estadio, j.neutro, j.arbitro_id as arbitro,
		                 j.fase, j.grupo, j.path, j.status, j.simulador_interno, j.subir_live,
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
	
	function inserirJogo($id_competicao,$timeA,$timeB,$fase,$arbitro,$estadio, $datetime, $neutro, $grupo = null, $dono = null, $nomeA = null, $nomeB = null, $subir_live = null){
		
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

		$timeA_id = (is_numeric($timeA) && intval($timeA) != 0) ? intval($timeA) : 0;
		$timeB_id = (is_numeric($timeB) && intval($timeB) != 0) ? intval($timeB) : 0;

		$timeA_nome = $nomeA ? htmlspecialchars(strip_tags($nomeA)) : (!is_numeric($timeA) && !empty($timeA) ? htmlspecialchars(strip_tags($timeA)) : null);
		$timeB_nome = $nomeB ? htmlspecialchars(strip_tags($nomeB)) : (!is_numeric($timeB) && !empty($timeB) ? htmlspecialchars(strip_tags($timeB)) : null);
		
		$query = "INSERT INTO jogos_clube (timeA_id, timeA_nome, timeB_id, timeB_nome, data, competicao_id, estadio_id, neutro, arbitro_id, fase, grupo, competicao_tipo, simulador_interno, status, subir_live, dono, tipo_times) 
		          VALUES (:timeA, :nomeA, :timeB, :nomeB, :data, :competicao, :estadio, :neutro, :arbitro, :fase, :grupo, 1, 1, 0, COALESCE(:subir_live, (SELECT subir_live FROM competicao_opcoes WHERE id_competicao = :id_comp_opt LIMIT 1), 0), COALESCE(:dono, (SELECT dono FROM " . $this->table_name . " WHERE id = :competicao_dono LIMIT 1), 0), COALESCE((SELECT tipo_times FROM competicao_opcoes WHERE id_competicao = :id_comp_opt2 LIMIT 1), 0))";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(":timeA", $timeA_id);
        $stmt->bindParam(":nomeA", $timeA_nome);
        $stmt->bindParam(":timeB", $timeB_id);
        $stmt->bindParam(":nomeB", $timeB_nome);
		$stmt->bindParam(":data", $datetime);
		$stmt->bindParam(":competicao", $id_competicao);
		$stmt->bindParam(":id_comp_opt", $id_competicao);
		$stmt->bindParam(":id_comp_opt2", $id_competicao);
		$stmt->bindParam(":competicao_dono", $id_competicao);
		$stmt->bindParam(":estadio", $estadio);
		$stmt->bindParam(":neutro", $neutro);
		$stmt->bindParam(":arbitro", $arbitro);
		$stmt->bindParam(":fase", $fase);
		$stmt->bindParam(":grupo", $grupo);
		if ($subir_live !== null) {
			$stmt->bindValue(":subir_live", intval($subir_live), PDO::PARAM_INT);
		} else {
			$stmt->bindValue(":subir_live", null, PDO::PARAM_NULL);
		}
		$stmt->bindValue(":dono", $dono, $dono === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }
	}
	
	function getColors(){
		if (!$this->conn) return false;
		try {
			$query = "SELECT (SELECT  valor as cor1 FROM opcoes WHERE parametro = 'partidaCor1' ) as partidaCor1, (SELECT  valor as cor2 FROM opcoes WHERE parametro = 'partidaCor2') as partidaCor2, (SELECT  valor as cor3 FROM opcoes WHERE parametro = 'partidaCor3') as partidaCor3";
			$stmt = $this->conn->query($query);
			if (!$stmt) return false;
			$stmt->execute();
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result ?: false;
		} catch (\Throwable $e) {
			return false;
		}
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
        if($penA !== null && $penA !== '' && (int)$penA >= 0) {
            $stmt->bindValue(":timeA_penaltis", (int)$penA, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":timeA_penaltis", null, PDO::PARAM_NULL);
        }
        if($penB !== null && $penB !== '' && (int)$penB >= 0) {
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

	/**
	 * Registra uma partida de competição de seleções na tabela oficial do Ranking (tabela jogos).
	 * Também preenche jogos_eventos e jogos_escalacao caso os arquivos da súmula estejam disponíveis.
	 */
	public function registrarJogoNoRanking($idPartida, $hylFile = null, $hyjFile = null, $forcarAtualizacao = false){
		$idPartida = intval($idPartida);
		if ($idPartida <= 0) return false;

		$stmt = $this->conn->prepare("
			SELECT j.*, co.tipo_times, co.campeonato_ranking, cl.nome as competicao_nome
			FROM jogos_clube j
			LEFT JOIN competicao_opcoes co ON j.competicao_id = co.id_competicao
			LEFT JOIN competicao_lista cl ON j.competicao_id = cl.id
			WHERE j.id = :id LIMIT 1
		");
		$stmt->execute([':id' => $idPartida]);
		$partida = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$partida) return false;

		// Apenas para competições de seleções (tipo_times = 1) e partidas simuladas (status = 1)
		if (intval($partida['tipo_times'] ?? 0) !== 1 || intval($partida['status'] ?? 0) !== 1) {
			return false;
		}

		$idCompeticao = intval($partida['competicao_id']);
		$timeA_id = intval($partida['timeA_id']);
		$timeB_id = intval($partida['timeB_id']);
		$timeA_nome = $partida['timeA_nome'] ?? '';
		$timeB_nome = $partida['timeB_nome'] ?? '';

		$xml = null;
		$jsonHyj = null;
		$filesLoaded = false;

		$loadMatchFiles = function() use (&$hylFile, &$hyjFile, &$xml, &$jsonHyj, &$filesLoaded, $partida, $idCompeticao) {
			if ($filesLoaded) return;
			$filesLoaded = true;

			if (!$hylFile && !empty($partida['path'])) {
				$docRoot = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__);
				$candidateHyl = $docRoot . '/' . ltrim($partida['path'], '/\\');
				if (file_exists($candidateHyl)) {
					$hylFile = $candidateHyl;
				} else {
					$stCompInfo = $this->conn->prepare("SELECT ano, nome FROM competicao_lista WHERE id = ? LIMIT 1");
					$stCompInfo->execute([$idCompeticao]);
					if ($rComp = $stCompInfo->fetch(PDO::FETCH_ASSOC)) {
						$nomeComposto = $rComp['ano'] . " - " . $rComp['nome'];
						$baseDirPartidas = $docRoot . "/competicoes/hexacolor/Partidas/" . $nomeComposto;
						$foundHyls = @glob($baseDirPartidas . "/*/" . $partida['path'] . ".hyl");
						if (!empty($foundHyls)) {
							$hylFile = $foundHyls[0];
						}
					}
					if (!$hylFile) {
						$foundHyls = @glob($docRoot . "/competicoes/hexacolor/Partidas/*/*/" . $partida['path'] . ".hyl");
						if (!empty($foundHyls)) {
							$hylFile = $foundHyls[0];
						}
					}
				}
			}
			if ($hylFile && !$hyjFile) {
				$candidateHyj = str_replace('.hyl', '.hyj', $hylFile);
				if (file_exists($candidateHyj)) {
					$hyjFile = $candidateHyj;
				}
			}
			if ($hylFile && file_exists($hylFile)) {
				$xml = json_decode(file_get_contents($hylFile));
			}
			if ($hyjFile && file_exists($hyjFile)) {
				$jsonHyj = json_decode(file_get_contents($hyjFile));
			}
		};

		require_once __DIR__ . '/paises.php';
		$paisObj = new Pais($this->conn);

		$resolvePaisId = function($tId, $tNome, $side) use ($idCompeticao, $paisObj, $loadMatchFiles, &$xml) {
			// 1. Se tem id de clube positivo, buscar Pais do clube
			if ($tId > 0) {
				$stClube = $this->conn->prepare("SELECT Pais FROM clube WHERE ID = ? LIMIT 1");
				$stClube->execute([$tId]);
				$pId = intval($stClube->fetchColumn());
				if ($pId > 0) return $pId;
			}
			// 2. Buscar em competicao_times
			if ($tId != 0) {
				$absCod = abs($tId);
				$stCt = $this->conn->prepare("SELECT pais_time FROM competicao_times WHERE id_competicao = ? AND (id_time_portal = ? OR codigo_time = ?) LIMIT 1");
				$stCt->execute([$idCompeticao, $tId, $absCod]);
				$pId = intval($stCt->fetchColumn());
				if ($pId > 0) return $pId;
			}
			// 3. Buscar pelo nome da partida
			if (!empty($tNome)) {
				$pId = $paisObj->idPorNomeTratado($tNome);
				if ($pId) return intval($pId);
			}
			// 4. Se tId já for ID na tabela paises
			if ($tId > 0) {
				$stP = $this->conn->prepare("SELECT id FROM paises WHERE id = ? LIMIT 1");
				$stP->execute([$tId]);
				$pId = intval($stP->fetchColumn());
				if ($pId > 0) return $pId;
			}
			// 5. Buscar pelo nome no .hyl
			$loadMatchFiles();
			$hylTeamName = ($side === 1) ? (isset($xml->time1) ? (string)$xml->time1 : '') : (isset($xml->time2) ? (string)$xml->time2 : '');
			if (!empty($hylTeamName)) {
				$pId = $paisObj->idPorNomeTratado($hylTeamName);
				if ($pId) return intval($pId);
			}
			return 0;
		};

		$paisA_id = $resolvePaisId($timeA_id, $timeA_nome, 1);
		$paisB_id = $resolvePaisId($timeB_id, $timeB_nome, 2);

		if ($paisA_id <= 0 || $paisB_id <= 0) {
			error_log("CONFUSA Ranking: Não foi possível resolver países da partida #{$idPartida} (País A: {$paisA_id}, País B: {$paisB_id})");
			return false;
		}

		// Coluna data na tabela jogos é do tipo DATE (Y-m-d)
		$dataPartida = !empty($partida['data']) ? date('Y-m-d', strtotime($partida['data'])) : date('Y-m-d');

		// Sigilo Temporal: O jogo só pode aparecer no Ranking após seu término no tempo real
		// Regulamentar: 120min (2h) | Pênaltis: 150min (2h30)
		$now = time();
		$dtMatch = !empty($partida['data']) ? strtotime($partida['data']) : $now;
		$temPenaltis = ($partida['timeA_penaltis'] !== null && $partida['timeA_penaltis'] !== '' && (int)$partida['timeA_penaltis'] >= 0 && $partida['timeB_penaltis'] !== null && $partida['timeB_penaltis'] !== '' && (int)$partida['timeB_penaltis'] >= 0);
		$durMatchSec = $temPenaltis ? (150 * 60) : (120 * 60);
		if ($now < ($dtMatch + $durMatchSec)) {
			// A partida foi simulada antecipadamente, mas ainda não aconteceu / não encerrou no horário real.
			// Caso tenha sido inserida prematuramente na tabela jogos (calculado = 0), removê-la até o término real.
			$stPrem = $this->conn->prepare("SELECT id FROM jogos WHERE timeA_id = ? AND timeB_id = ? AND data = ? AND calculado = 0 LIMIT 1");
			$stPrem->execute([$paisA_id, $paisB_id, $dataPartida]);
			$idPrem = intval($stPrem->fetchColumn());
			if ($idPrem > 0) {
				$this->conn->prepare("DELETE FROM jogos_eventos WHERE id_jogo = ?")->execute([$idPrem]);
				$this->conn->prepare("DELETE FROM jogos_escalacao WHERE id_jogo = ?")->execute([$idPrem]);
				$this->conn->prepare("DELETE FROM jogos WHERE id = ?")->execute([$idPrem]);
			}
			return false;
		}

		// Verificar se jogo já existe na tabela jogos do ranking
		$stCheck = $this->conn->prepare("SELECT id, calculado FROM jogos WHERE timeA_id = ? AND timeB_id = ? AND data = ? LIMIT 1");
		$stCheck->execute([$paisA_id, $paisB_id, $dataPartida]);
		$existingRow = $stCheck->fetch(PDO::FETCH_ASSOC);
		$idJogoRanking = $existingRow ? intval($existingRow['id']) : 0;

		if ($idJogoRanking > 0 && !$forcarAtualizacao) {
			// Jogo já sincronizado anteriormente no ranking; evita resetar calculado = 0 ou reler arquivos
			return $idJogoRanking;
		}

		// Carregar arquivos .hyl e .hyj para gravar ou atualizar súmula
		$loadMatchFiles();

		// Placar
		$golsA = isset($partida['timeA_gols']) && $partida['timeA_gols'] !== null ? intval($partida['timeA_gols']) : (isset($xml->placarTime1) ? intval($xml->placarTime1) : 0);
		$golsB = isset($partida['timeB_gols']) && $partida['timeB_gols'] !== null ? intval($partida['timeB_gols']) : (isset($xml->placarTime2) ? intval($xml->placarTime2) : 0);
		$penA = null;
		$penB = null;
		if (isset($partida['timeA_penaltis']) && $partida['timeA_penaltis'] !== null && $partida['timeA_penaltis'] !== '' && (int)$partida['timeA_penaltis'] >= 0) {
			$penA = (int)$partida['timeA_penaltis'];
			$penB = (isset($partida['timeB_penaltis']) && $partida['timeB_penaltis'] !== null && $partida['timeB_penaltis'] !== '' && (int)$partida['timeB_penaltis'] >= 0) ? (int)$partida['timeB_penaltis'] : 0;
		} elseif (!empty($xml->penaltis)) {
			$penA = (isset($xml->placarPenaltisTime1) && (int)$xml->placarPenaltisTime1 >= 0) ? (int)$xml->placarPenaltisTime1 : null;
			$penB = (isset($xml->placarPenaltisTime2) && (int)$xml->placarPenaltisTime2 >= 0) ? (int)$xml->placarPenaltisTime2 : null;
		} elseif (!empty($jsonHyj->penaltis)) {
			$penA = (isset($jsonHyj->time1->placarPenaltis) && (int)$jsonHyj->time1->placarPenaltis >= 0) ? (int)$jsonHyj->time1->placarPenaltis : null;
			$penB = (isset($jsonHyj->time2->placarPenaltis) && (int)$jsonHyj->time2->placarPenaltis >= 0) ? (int)$jsonHyj->time2->placarPenaltis : null;
		}
		if ($penA !== null && (int)$penA < 0) $penA = null;
		if ($penB !== null && (int)$penB < 0) $penB = null;
		if ($penA === null || $penB === null) {
			$penA = null;
			$penB = null;
		}

		// Campeonato do Ranking
		$campeonatoId = intval($partida['campeonato_ranking'] ?? 0);
		if ($campeonatoId <= 0) {
			$cNome = mb_strtolower($partida['competicao_nome'] ?? '');
			if (mb_strpos($cNome, 'mundial sub-20') !== false || mb_strpos($cNome, 'sub-20') !== false) {
				$campeonatoId = 13;
			} elseif (mb_strpos($cNome, 'mundial sub-18') !== false || mb_strpos($cNome, 'sub-18') !== false) {
				$campeonatoId = 12;
			} elseif (mb_strpos($cNome, 'copa do mundo') !== false) {
				$campeonatoId = 1;
			} elseif (mb_strpos($cNome, 'olimp') !== false) {
				$campeonatoId = 2;
			} elseif (mb_strpos($cNome, 'feascopa') !== false) {
				$campeonatoId = 6;
			} elseif (mb_strpos($cNome, 'três mares') !== false || mb_strpos($cNome, 'tres mares') !== false) {
				$campeonatoId = 7;
			} elseif (mb_strpos($cNome, 'távola') !== false || mb_strpos($cNome, 'tavola') !== false) {
				$campeonatoId = 8;
			} elseif (mb_strpos($cNome, 'angehait') !== false) {
				$campeonatoId = 9;
			} elseif (mb_strpos($cNome, 'confederaç') !== false || mb_strpos($cNome, 'confederac') !== false) {
				$campeonatoId = 14;
			} elseif (mb_strpos($cNome, 'eliminat') !== false) {
				$campeonatoId = 3;
			} elseif (mb_strpos($cNome, 'amistoso de base') !== false) {
				$campeonatoId = 11;
			} else {
				$campeonatoId = 10; // Amistoso
			}
		}

		$estadioNome = !empty($partida['estadio_nome']) ? $partida['estadio_nome'] : (isset($xml->estadio) ? (string)$xml->estadio : '');
		if (empty($estadioNome) && !empty($partida['estadio_id'])) {
			$stEst = $this->conn->prepare("SELECT Nome FROM estadio WHERE ID = ? LIMIT 1");
			$stEst->execute([intval($partida['estadio_id'])]);
			$estadioNome = $stEst->fetchColumn() ?: '';
		}

		$arbitroId = !empty($partida['arbitro_id']) ? intval($partida['arbitro_id']) : 0;
		if ($arbitroId <= 0 && !empty($jsonHyj->idArbitragem)) {
			$arbitroId = intval($jsonHyj->idArbitragem);
		} elseif ($arbitroId <= 0 && !empty($xml->idArbitragem)) {
			$arbitroId = intval($xml->idArbitragem);
		}
		if ($arbitroId < 0) {
			$arbitroId = 0;
		}
		$fase = isset($partida['fase']) ? intval($partida['fase']) : 0;

		if ($idJogoRanking > 0) {
			$stUpd = $this->conn->prepare("
				UPDATE jogos SET
					timeA_gols = ?, timeB_gols = ?, timeA_penaltis = ?, timeB_penaltis = ?,
					campeonato = ?, estadio = ?, cod_arbitro = ?, fase = ?
				WHERE id = ?
			");
			$stUpd->execute([$golsA, $golsB, $penA, $penB, $campeonatoId, $estadioNome, $arbitroId, $fase, $idJogoRanking]);
		} else {
			$stIns = $this->conn->prepare("
				INSERT INTO jogos (timeA_id, timeA_gols, timeB_id, timeB_gols, timeA_penaltis, timeB_penaltis, data, campeonato, calculado, estadio, cod_arbitro, fase)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)
			");
			$stIns->execute([$paisA_id, $golsA, $paisB_id, $golsB, $penA, $penB, $dataPartida, $campeonatoId, $estadioNome, $arbitroId, $fase]);
			$idJogoRanking = intval($this->conn->lastInsertId());
		}

		if ($idJogoRanking <= 0) {
			return false;
		}

		// -------------------------------------------------------------
		// Gravar eventos detalhados na tabela jogos_eventos
		// -------------------------------------------------------------
		$this->conn->prepare("DELETE FROM jogos_eventos WHERE id_jogo = ?")->execute([$idJogoRanking]);

		$stInsEv = $this->conn->prepare("
			INSERT INTO jogos_eventos (id_jogo, tempo, minutos, tipo, id_jogador, nome_jogador, id_time)
			VALUES (?, ?, ?, ?, ?, ?, ?)
		");

		$playerMap1 = [];
		$playerMap2 = [];
		if ($xml) {
			if (isset($xml->escalacaoTime1) && is_array($xml->escalacaoTime1)) {
				foreach ($xml->escalacaoTime1 as $p) {
					$pId = (int)($p->id ?? 0);
					$pNome = (string)($p->nome ?? '');
					$pPos = (string)($p->posicao ?? '');
					$playerMap1[$pId] = ['nome' => $pNome, 'posicao' => $pPos];
				}
			}
			if (isset($xml->escalacaoTime2) && is_array($xml->escalacaoTime2)) {
				foreach ($xml->escalacaoTime2 as $p) {
					$pId = (int)($p->id ?? 0);
					$pNome = (string)($p->nome ?? '');
					$pPos = (string)($p->posicao ?? '');
					$playerMap2[$pId] = ['nome' => $pNome, 'posicao' => $pPos];
				}
			}

			if (isset($xml->eventos) && is_array($xml->eventos)) {
				foreach ($xml->eventos as $ev) {
					$evArray = (array)$ev;
					$tipoEvStr = $evArray['tipoEvento'] ?? '';
					$tipoEvento = 0;
					switch ($tipoEvStr) {
						case 'gol':       $tipoEvento = 1; break;
						case 'amarelo':   $tipoEvento = 2; break;
						case 'vermelho':  $tipoEvento = 3; break;
						case 'golContra': $tipoEvento = 4; break;
					}
					$tempoRaw = isset($evArray['tempo']) ? (int)$evArray['tempo'] : 1;
					if ($tempoRaw > 4) continue; // ignora pênaltis pós-jogo

					if ($tipoEvento > 0) {
						$minuto = isset($evArray['minutos']) ? (int)$evArray['minutos'] : null;
						$tempo = $tempoRaw;
						if ($minuto !== null && $minuto > 45 && $tempo == 1) $tempo = 2;

						$tempId = (int)($evArray['idJogador'] ?? 0);
						$nomeJog = '';
						$idTm = 0;

						if (isset($playerMap1[$tempId])) {
							$nomeJog = $playerMap1[$tempId]['nome'] ?? '';
							$idTm = $paisA_id;
						} elseif (isset($playerMap2[$tempId])) {
							$nomeJog = $playerMap2[$tempId]['nome'] ?? '';
							$idTm = $paisB_id;
						} else {
							$teamNum = (int)($evArray['time'] ?? 1);
							$idTm = ($teamNum === 2) ? $paisB_id : $paisA_id;
						}

						$stInsEv->execute([
							$idJogoRanking,
							$tempo,
							$minuto,
							$tipoEvento,
							$tempId,
							mb_substr($nomeJog, 0, 40),
							$idTm
						]);
					}
				}
			}
		}

		// -------------------------------------------------------------
		// Gravar escalações na tabela jogos_escalacao
		// -------------------------------------------------------------
		$this->conn->prepare("DELETE FROM jogos_escalacao WHERE id_jogo = ?")->execute([$idJogoRanking]);

		$stPaisNomeA = $this->conn->prepare("SELECT nome FROM paises WHERE id = ?");
		$stPaisNomeA->execute([$paisA_id]);
		$nomePaisA = $stPaisNomeA->fetchColumn() ?: 'Seleção A';

		$stPaisNomeB = $this->conn->prepare("SELECT nome FROM paises WHERE id = ?");
		$stPaisNomeB->execute([$paisB_id]);
		$nomePaisB = $stPaisNomeB->fetchColumn() ?: 'Seleção B';

		$stInsEsc = $this->conn->prepare("
			INSERT INTO jogos_escalacao (id_jogo, id_time, nome_time, posicao, numero, id_jogador, nome_jogador, titular, entrada_minuto, saida_minuto)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		");

		if ($jsonHyj) {
			$timesEsc = [];
			if (isset($jsonHyj->time1)) $timesEsc[] = ['obj' => $jsonHyj->time1, 'id' => $paisA_id, 'nome' => $nomePaisA, 'hMap' => $playerMap1];
			if (isset($jsonHyj->time2)) $timesEsc[] = ['obj' => $jsonHyj->time2, 'id' => $paisB_id, 'nome' => $nomePaisB, 'hMap' => $playerMap2];

			foreach ($timesEsc as $tmGroup) {
				$tmObj = $tmGroup['obj'];
				$tmId = $tmGroup['id'];
				$tmNome = $tmGroup['nome'];
				$hMap = $tmGroup['hMap'];

				if (isset($tmObj->jogadores) && is_array($tmObj->jogadores)) {
					foreach ($tmObj->jogadores as $idx => $jg) {
						$jgId = isset($jg->idJogador) ? (int)$jg->idJogador : 0;
						$jgNome = isset($jg->nome) && !empty($jg->nome) ? $jg->nome : ($hMap[$jgId]['nome'] ?? '');
						$pos = isset($jg->posicao) && !empty($jg->posicao) ? $jg->posicao : ($hMap[$jgId]['posicao'] ?? '');
						$num = isset($jg->numero) ? (int)$jg->numero : ($idx + 1);
						$titular = ($idx < 11) ? 1 : 0;
						$entMin = isset($jg->entradaMinuto) ? (int)$jg->entradaMinuto : null;
						$saiMin = isset($jg->saidaMinuto) ? (int)$jg->saidaMinuto : null;

						$stInsEsc->execute([
							$idJogoRanking,
							$tmId,
							$tmNome,
							$pos,
							$num,
							$jgId,
							$jgNome,
							$titular,
							$entMin,
							$saiMin
						]);
					}
				}
			}
		}

		return $idJogoRanking;
	}

	/**
	 * Sincroniza partidas de competições de seleções que já foram simuladas e já encerraram no tempo real,
	 * e remove da tabela jogos qualquer partida simulada que ainda esteja sob sigilo temporal.
	 */
	public function sincronizarJogosRankingEncerrados() {
		$sincronizados = 0;
		try {
			$stmt = $this->conn->query("
				SELECT j.id
				FROM jogos_clube j
				INNER JOIN competicao_opcoes co ON j.competicao_id = co.id_competicao
				WHERE co.tipo_times = 1
				  AND j.status = 1
			");
			if ($stmt) {
				$partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
				foreach ($partidas as $p) {
					$res = $this->registrarJogoNoRanking((int)$p['id']);
					if ($res) {
						$sincronizados++;
					}
				}
			}
		} catch (\Throwable $e) {
			error_log("CONFUSA Ranking Sync Error: " . $e->getMessage());
		}
		return $sincronizados;
	}
	
	function getTeamColors($uniforme, $idTime){
		if (!$this->conn || empty($idTime)) return false;
		try {
			$idTime = htmlspecialchars(strip_tags($idTime));
			
			if($uniforme == 2){
				$query = "SELECT Uni2Cor1 as cor1, Uni2Cor2 as cor2, Uni2Cor3 as cor3 FROM clube WHERE ID = :id";
			} else {
				$query = "SELECT Uni1Cor1 as cor1, Uni1Cor2 as cor2, Uni1Cor3 as cor3 FROM clube WHERE ID = :id";
			}
			
			$stmt = $this->conn->prepare($query);
			if (!$stmt) return false;
			$stmt->bindParam(":id", $idTime);
			$stmt->execute();
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result ?: false;
		} catch (\Throwable $e) {
			return false;
		}
	}
	
	function getTeamUniform($uniforme, $idTime){
		if (!$this->conn || empty($idTime)) return false;
		try {
			$idTime = htmlspecialchars(strip_tags($idTime));
			
			if($uniforme == 2){
				$query = "SELECT Uniforme2 as uniforme FROM clube WHERE ID = :id";
			} else {
				$query = "SELECT Uniforme1 as uniforme FROM clube WHERE ID = :id";
			}
			
			$stmt = $this->conn->prepare($query);
			if (!$stmt) return false;
			$stmt->bindParam(":id", $idTime);
			$stmt->execute();
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result ?: false;
		} catch (\Throwable $e) {
			return false;
		}
	}
	
	function getTeamPlayers($idTime){
		if (!$this->conn || empty($idTime)) return [];
		try {
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

			$stmt = $this->conn->prepare($query);
			if (!$stmt) return [];
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
		} catch (\Throwable $e) {
			return [];
		}
	}
	
	function getNomeEstadio($idEstadio){
		if (!$this->conn || empty($idEstadio)) return '';
		try {
			$idEstadio = htmlspecialchars(strip_tags($idEstadio));
			
			$query = "SELECT Nome from estadio WHERE ID = :id";
			$stmt = $this->conn->prepare($query);
			if (!$stmt) return '';
			$stmt->bindParam(":id", $idEstadio);
			$stmt->execute();
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result['Nome'] ?? '';
		} catch (\Throwable $e) {
			return '';
		}
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
		$idCompeticao = intval($idCompeticao);
		$vagaIndex = intval($vagaIndex);
		$idTimeReal = intval($idTimeReal);
		$prevTimeId = intval($prevTimeId);

		// Se a vaga já possui um slot explicitamente definido (ex: A3), usa definirSlotTime
		$stSlot = $this->conn->prepare("SELECT slot FROM competicao_times WHERE id_competicao = :idComp AND codigo_time = :cod LIMIT 1");
		$stSlot->bindParam(':idComp', $idCompeticao);
		$stSlot->bindParam(':cod', $vagaIndex);
		$stSlot->execute();
		$rSlot = $stSlot->fetch(PDO::FETCH_ASSOC);
		if ($rSlot && !empty($rSlot['slot'])) {
			return $this->definirSlotTime($idCompeticao, $vagaIndex, $rSlot['slot']);
		}

		$info = $this->getSlotInfoForVaga($idCompeticao, $vagaIndex);
		if (!$info) return false;
		
		$slotName = $info['slot'];
		$fase = $info['fase'];
		$grupo = $info['grupo'];
		
		$whereExtraA = "";
		$whereExtraB = "";
		if ($grupo !== null && $grupo !== '') {
			$whereExtraA = " AND (grupo = '$grupo' OR grupo IS NULL) ";
			$whereExtraB = " AND (grupo = '$grupo' OR grupo IS NULL) ";
		}
		
		if ($idTimeReal != 0) {
			$vagaAbs = abs($vagaIndex);
			$slotDefaultQuote = $this->conn->quote("Slot " . $vagaAbs);
			$vagaLabelQuote = $this->conn->quote("Vaga " . $vagaAbs);
			$vagaHashQuote = $this->conn->quote("Vaga #" . $vagaAbs);

			$slotClean = $slotName ? trim($slotName) : '';
			$slotCondA = "TRIM(timeA_nome) = $slotDefaultQuote OR TRIM(timeA_nome) = $vagaLabelQuote OR TRIM(timeA_nome) = $vagaHashQuote OR timeA_id = -$vagaAbs";
			if ($slotClean !== '') {
				$slotCleanQuote = $this->conn->quote($slotClean);
				$slotWithout = preg_replace('/^Slot\s+/i', '', $slotClean);
				$slotWithoutQuote = $this->conn->quote($slotWithout);
				$slotWithQuote = $this->conn->quote('Slot ' . $slotWithout);
				$slotCondA .= " OR TRIM(timeA_nome) = $slotCleanQuote OR TRIM(timeA_nome) = $slotWithoutQuote OR TRIM(timeA_nome) = $slotWithQuote";
			}

			$slotCondB = "TRIM(timeB_nome) = $slotDefaultQuote OR TRIM(timeB_nome) = $vagaLabelQuote OR TRIM(timeB_nome) = $vagaHashQuote OR timeB_id = -$vagaAbs";
			if ($slotClean !== '') {
				$slotCleanQuote = $this->conn->quote($slotClean);
				$slotWithout = preg_replace('/^Slot\s+/i', '', $slotClean);
				$slotWithoutQuote = $this->conn->quote($slotWithout);
				$slotWithQuote = $this->conn->quote('Slot ' . $slotWithout);
				$slotCondB .= " OR TRIM(timeB_nome) = $slotCleanQuote OR TRIM(timeB_nome) = $slotWithoutQuote OR TRIM(timeB_nome) = $slotWithQuote";
			}

			// 1. Atualizar por nome do slot (placeholder) ou vaga legado
			$this->conn->exec("UPDATE jogos_clube 
			                   SET timeA_id = $idTimeReal, timeA_nome = NULL 
			                   WHERE competicao_id = $idCompeticao 
			                     AND simulador_interno = 1 
			                     AND ($slotCondA) $whereExtraA");
			                     
			$this->conn->exec("UPDATE jogos_clube 
			                   SET timeB_id = $idTimeReal, timeB_nome = NULL 
			                   WHERE competicao_id = $idCompeticao 
			                     AND simulador_interno = 1 
			                     AND ($slotCondB) $whereExtraB");

			// 2. Se estava substituindo um time anterior específico desta vaga
			if ($prevTimeId != 0 && $prevTimeId != $idTimeReal) {
				$condsReplaceA = ["timeA_id = $prevTimeId"];
				$condsReplaceB = ["timeB_id = $prevTimeId"];
				// Se prevTimeId era negativo (ex: -2959 ou time temporário antigo), limpar também a versão de sinal oposto
				$condsReplaceA[] = "timeA_id = -$prevTimeId";
				$condsReplaceB[] = "timeB_id = -$prevTimeId";

				$whereReplaceA = implode(' OR ', array_unique($condsReplaceA));
				$whereReplaceB = implode(' OR ', array_unique($condsReplaceB));

				$this->conn->exec("UPDATE jogos_clube 
				                   SET timeA_id = $idTimeReal 
				                   WHERE competicao_id = $idCompeticao 
				                     AND simulador_interno = 1 
				                     AND ($whereReplaceA) $whereExtraA");
				                     
				$this->conn->exec("UPDATE jogos_clube 
				                   SET timeB_id = $idTimeReal 
				                   WHERE competicao_id = $idCompeticao 
				                     AND simulador_interno = 1 
				                     AND ($whereReplaceB) $whereExtraB");
			}
			
			// 3. Atualizar estádios dos jogos mandantes se estádio for por time
			$options = $this->getOptions($idCompeticao);
			$estadios_times = isset($options['estadios_times']) ? intval($options['estadios_times']) : 1;
			if ($estadios_times == 1) {
				$docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__);
				$db3File = $docRoot . "/competicoes/databases/" . $idCompeticao . "-database.db3";
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
			if ($prevTimeId != 0) {
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
		} else {
			// Fallback: verificar se existe clube no SQLite da competição
			$db3File = (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '') 
				? $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $idCompeticao . "-database.db3"
				: dirname(__DIR__) . "/competicoes/databases/" . $idCompeticao . "-database.db3";
			if (file_exists($db3File)) {
				try {
					$liteDb = new SQLiteDatabase();
					$liteDb->fileName = $db3File;
					$sdb = $liteDb->getConnection();
					if ($sdb) {
						$stC = $sdb->prepare("SELECT ID FROM clube WHERE ID = :negId OR ID = :posId LIMIT 1");
						$negId = -1 * abs($codigoTime);
						$posId = abs($codigoTime);
						$stC->bindParam(':negId', $negId);
						$stC->bindParam(':posId', $posId);
						$stC->execute();
						if ($stC->fetch()) {
							$teamId = -1 * abs($codigoTime);
							$this->conn->exec("UPDATE competicao_times SET has_team = '1' WHERE id_competicao = $idCompeticao AND codigo_time = $codigoTime");
						}
					}
				} catch (\Throwable $e) {}
			}
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
			$this->conn->exec("UPDATE jogos_clube SET timeA_id = 0, timeA_nome = '$prevSlot' WHERE competicao_id = $idCompeticao AND simulador_interno = 1 AND (timeA_id = $teamId OR timeA_id = -$teamId)");
			$this->conn->exec("UPDATE jogos_clube SET timeB_id = 0, timeB_nome = '$prevSlot' WHERE competicao_id = $idCompeticao AND simulador_interno = 1 AND (timeB_id = $teamId OR timeB_id = -$teamId)");
		}
		
		// 5. Se o novo slot foi definido e temos um time, preencher os jogos com este time
		if($slotName !== '' && $teamId != 0){
			$slotWithout = trim(preg_replace('/^Slot\s+/i', '', $slotName));
			$grpLetra = null;
			$slotNumInGroup = null;

			if (preg_match('/^([A-Z])\s*(\d+)$/i', $slotWithout, $mG)) {
				$grpLetra = strtoupper($mG[1]);
				$slotNumInGroup = intval($mG[2]);
			}

			// A. Se o slot pertence a um grupo específico (ex: A3 -> Grupo A),
			// limpa esse time de qualquer jogo pertencente a outros grupos (ex: Grupo B, C, etc.)
			// para corrigir dados corrompidos por cálculos antigos.
			if ($grpLetra !== null) {
				$this->conn->exec("UPDATE jogos_clube 
				                   SET timeA_id = 0 
				                   WHERE competicao_id = $idCompeticao 
				                     AND simulador_interno = 1 
				                     AND timeA_id = $teamId 
				                     AND grupo IS NOT NULL 
				                     AND grupo != '' 
				                     AND UPPER(grupo) != '$grpLetra'");

				$this->conn->exec("UPDATE jogos_clube 
				                   SET timeB_id = 0 
				                   WHERE competicao_id = $idCompeticao 
				                     AND simulador_interno = 1 
				                     AND timeB_id = $teamId 
				                     AND grupo IS NOT NULL 
				                     AND grupo != '' 
				                     AND UPPER(grupo) != '$grpLetra'");
			}

			// B. Construir variações estritas de nome de slot/placeholder
			$candidates = [];
			if ($grpLetra !== null) {
				// Slots de grupos (ex: F4, A1) - Estrito ao identificador do grupo
				// NUNCA incluir números soltos ou índices de vagas (como 4 ou -4) que colidem com times de outras vagas!
				$candidates = [
					$slotName,
					$slotWithout,
					'Slot ' . $slotWithout,
					$grpLetra . $slotNumInGroup,
					$grpLetra . ' ' . $slotNumInGroup,
					$grpLetra . '-' . $slotNumInGroup,
					$grpLetra . '.' . $slotNumInGroup
				];
			} else {
				// Slots genéricos de mata-mata ou pontos corridos (ex: Slot 1, Slot 4)
				$candidates = [
					$slotName,
					$slotWithout,
					'Slot ' . $slotWithout,
					'Slot ' . $codigoTime
				];
			}

			$condsA = [];
			$condsB = [];
			foreach (array_unique($candidates) as $cand) {
				$candTrim = trim($cand);
				if ($candTrim === '') continue;
				$qCand = $this->conn->quote($candTrim);
				$condsA[] = "LOWER(TRIM(timeA_nome)) = LOWER($qCand)";
				$condsB[] = "LOWER(TRIM(timeB_nome)) = LOWER($qCand)";
			}

			$whereA = implode(' OR ', $condsA);
			$whereB = implode(' OR ', $condsB);

			$extraGrupo = "";
			if ($grpLetra !== null) {
				$extraGrupo = " AND (UPPER(grupo) = '$grpLetra' OR grupo IS NULL OR grupo = '')";
			}

			$this->conn->exec("UPDATE jogos_clube 
			                   SET timeA_id = $teamId 
			                   WHERE competicao_id = $idCompeticao 
			                     AND simulador_interno = 1 
			                     AND ($whereA) $extraGrupo");

			$this->conn->exec("UPDATE jogos_clube 
			                   SET timeB_id = $teamId 
			                   WHERE competicao_id = $idCompeticao 
			                     AND simulador_interno = 1 
			                     AND ($whereB) $extraGrupo");
			
			// C. Atualizar estádio mandante se estadios_times == 1
			$options = $this->getOptions($idCompeticao);
			$estadios_times = isset($options['estadios_times']) ? intval($options['estadios_times']) : 1;
			if ($estadios_times == 1 && $teamId != 0) {
				$docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__);
				$db3File = $docRoot . "/competicoes/databases/" . $idCompeticao . "-database.db3";
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

	function sincronizarSlotsJogos($idCompeticao){
		$idCompeticao = intval($idCompeticao);
		if($idCompeticao <= 0) return false;
		
		try {

			// Conectar ao SQLite da competição se existir para validar times temporários
			$db3File = (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '') 
				? $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $idCompeticao . "-database.db3"
				: dirname(__DIR__) . "/competicoes/databases/" . $idCompeticao . "-database.db3";
			$sqliteClubeIds = [];
			if (file_exists($db3File)) {
				try {
					$liteDb = new SQLiteDatabase();
					$liteDb->fileName = $db3File;
					$sdb = $liteDb->getConnection();
					if ($sdb) {
						$stC = $sdb->query("SELECT ID FROM clube");
						if ($stC) {
							while ($rC = $stC->fetch(PDO::FETCH_ASSOC)) {
								$sqliteClubeIds[intval($rC['ID'])] = true;
							}
						}
					}
				} catch (\Throwable $e) {}
			}

			$stTimes = $this->conn->prepare("SELECT codigo_time, has_team, id_time_portal, slot FROM competicao_times WHERE id_competicao = :idComp ORDER BY codigo_time ASC");
			$stTimes->bindParam(':idComp', $idCompeticao);
			$stTimes->execute();
			while($r = $stTimes->fetch(PDO::FETCH_ASSOC)){
				$cod = intval($r['codigo_time']);
				$slot = trim($r['slot'] ?? '');
				$teamId = 0;
				if(!empty($r['id_time_portal']) && intval($r['id_time_portal']) > 0){
					$teamId = intval($r['id_time_portal']);
				} else if($r['has_team'] == 1 || $r['has_team'] == '1'){
					$teamId = -1 * abs($cod);
				} else if (isset($sqliteClubeIds[-1 * abs($cod)]) || isset($sqliteClubeIds[abs($cod)])) {
					$teamId = -1 * abs($cod);
					$this->conn->exec("UPDATE competicao_times SET has_team = '1' WHERE id_competicao = $idCompeticao AND codigo_time = $cod");
				}

				if($teamId != 0 && $slot !== ''){
					$this->definirSlotTime($idCompeticao, $cod, $slot);
				}
			}
			return true;
		} catch (\Throwable $e) {
			return false;
		}
	}

	function isClubeAtivoNaCompeticao($idCompeticao, $idClube) {
		$idCompeticao = (int)$idCompeticao;
		$idClube = (int)$idClube;
		if ($idCompeticao <= 0 || $idClube == 0) return false;

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
			  AND (timeA_id = :idClubeA OR timeB_id = :idClubeB) 
			  AND status = 0 
			LIMIT 1
		");
		$stmtJogosPendentes->execute([':idComp' => $idCompeticao, ':idClubeA' => $idClube, ':idClubeB' => $idClube]);
		if ($stmtJogosPendentes->fetch()) {
			return true;
		}

		// 3. Buscar todas as partidas simuladas do time nesta competição
		$stmtJogos = $this->conn->prepare("
			SELECT id, timeA_id, timeB_id, timeA_gols, timeB_gols, timeA_penaltis, timeB_penaltis, fase, status 
			FROM jogos_clube 
			WHERE competicao_id = :idComp 
			  AND (timeA_id = :idClubeA OR timeB_id = :idClubeB)
			ORDER BY fase DESC, id DESC
		");
		$stmtJogos->execute([':idComp' => $idCompeticao, ':idClubeA' => $idClube, ':idClubeB' => $idClube]);
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

	/**
	 * Verifica se todos os jogos da fase de mata-mata atual foram concluídos e gera os confrontos da próxima fase.
	 */
	function verificarEAvancarMataMata($idCompeticao, $faseAtual) {
		$idCompeticao = (int)$idCompeticao;
		$faseAtual = (int)$faseAtual;
		if ($idCompeticao <= 0 || $faseAtual <= 2) return false;

		$nextFaseMap = [
			11 => 10, // 64-avos -> 32-avos
			10 => 9,  // 32-avos -> 16-avos
			9  => 3,  // 16-avos -> Oitavas
			3  => 4,  // Oitavas -> Quartas
			4  => 5,  // Quartas -> Semifinal
			5  => 8   // Semifinal -> Final
		];

		if (!isset($nextFaseMap[$faseAtual])) return false;
		$proximaFase = $nextFaseMap[$faseAtual];

		// 1. Verificar se já existem jogos criados para a próxima fase
		$stmtJaExiste = $this->conn->prepare("SELECT 1 FROM jogos_clube WHERE competicao_id = :idComp AND fase = :proxFase AND simulador_interno = 1 LIMIT 1");
		$stmtJaExiste->execute([':idComp' => $idCompeticao, ':proxFase' => $proximaFase]);
		if ($stmtJaExiste->fetch()) {
			return false; // Próxima fase já foi criada
		}

		// 2. Carregar todos os jogos da fase atual
		$stmtJogosFase = $this->conn->prepare("SELECT * FROM jogos_clube WHERE competicao_id = :idComp AND fase = :fase AND simulador_interno = 1 ORDER BY id ASC");
		$stmtJogosFase->execute([':idComp' => $idCompeticao, ':fase' => $faseAtual]);
		$jogosFase = $stmtJogosFase->fetchAll(PDO::FETCH_ASSOC);
		if (empty($jogosFase)) return false;

		// 3. Verificar se todos os jogos da fase atual já foram simulados (status = 1) e já terminaram no tempo real
		$now = time();
		foreach ($jogosFase as $jg) {
			if ((int)$jg['status'] !== 1) {
				return false; // Ainda há jogos não simulados na fase atual
			}
			$dtMatch = !empty($jg['data']) ? strtotime($jg['data']) : $now;
			$temPenaltis = ($jg['timeA_penaltis'] !== null && $jg['timeB_penaltis'] !== null);
			$durMatchSec = $temPenaltis ? (150 * 60) : (120 * 60);
			if ($now < ($dtMatch + $durMatchSec)) {
				return false; // A partida ainda está acontecendo ou não encerrou no tempo real
			}
		}

		// 4. Determinar vencedores de cada confronto da fase atual (suportando jogo único ou ida e volta)
		$optComp = $this->getOptions($idCompeticao);
		$vencedoresJogos = [];
		$confrontosFase = [];
		foreach ($jogosFase as $jg) {
			$tA = (int)$jg['timeA_id'];
			$tB = (int)$jg['timeB_id'];
			$nA = trim($jg['timeA_nome'] ?? '');
			$nB = trim($jg['timeB_nome'] ?? '');
			$keyChave = !empty($jg['grupo']) ? ('chave_' . trim($jg['grupo'])) : null;
			if (!$keyChave) {
				$minId = min($tA, $tB);
				$maxId = max($tA, $tB);
				if ($minId > 0 && $maxId > 0) {
					$keyChave = "pair_{$minId}_{$maxId}";
				} else {
					$names = [$nA, $nB];
					sort($names);
					$keyChave = "pair_names_" . implode('_', $names);
				}
			}
			$confrontosFase[$keyChave][] = $jg;
		}

		foreach ($confrontosFase as $cKey => $cJogos) {
			$vencId = 0;
			$vencNome = null;

			if (count($cJogos) === 1) {
				$jg = $cJogos[0];
				$gA = (int)$jg['timeA_gols'];
				$gB = (int)$jg['timeB_gols'];
				$pA = $jg['timeA_penaltis'] !== null ? (int)$jg['timeA_penaltis'] : null;
				$pB = $jg['timeB_penaltis'] !== null ? (int)$jg['timeB_penaltis'] : null;

				if ($gA > $gB) {
					$vencId = (int)$jg['timeA_id'];
					$vencNome = $jg['timeA_nome'];
				} elseif ($gB > $gA) {
					$vencId = (int)$jg['timeB_id'];
					$vencNome = $jg['timeB_nome'];
				} elseif ($pA !== null && $pB !== null) {
					if ($pA > $pB) {
						$vencId = (int)$jg['timeA_id'];
						$vencNome = $jg['timeA_nome'];
					} else {
						$vencId = (int)$jg['timeB_id'];
						$vencNome = $jg['timeB_nome'];
					}
				} else {
					$vencId = (int)$jg['timeA_id'];
					$vencNome = $jg['timeA_nome'];
				}
			} else {
				// Confronto de Ida e Volta (2 partidas)
				usort($cJogos, function($a, $b) {
					$tA = strtotime($a['data'] ?? 0);
					$tB = strtotime($b['data'] ?? 0);
					if ($tA !== $tB) return $tA - $tB;
					return (int)$a['id'] - (int)$b['id'];
				});
				$j1 = $cJogos[0];
				$j2 = $cJogos[1];

				$idA = (int)$j1['timeA_id'];
				$nomeA = $j1['timeA_nome'];
				$idB = (int)$j1['timeB_id'];
				$nomeB = $j1['timeB_nome'];

				$golsA_j1 = (int)$j1['timeA_gols'];
				$golsB_j1 = (int)$j1['timeB_gols'];

				if ((int)$j2['timeA_id'] === $idB || (!empty($nomeB) && trim($j2['timeA_nome'] ?? '') === $nomeB)) {
					$golsB_j2 = (int)$j2['timeA_gols'];
					$golsA_j2 = (int)$j2['timeB_gols'];
					$penB_j2 = $j2['timeA_penaltis'] !== null ? (int)$j2['timeA_penaltis'] : null;
					$penA_j2 = $j2['timeB_penaltis'] !== null ? (int)$j2['timeB_penaltis'] : null;
				} else {
					$golsA_j2 = (int)$j2['timeA_gols'];
					$golsB_j2 = (int)$j2['timeB_gols'];
					$penA_j2 = $j2['timeA_penaltis'] !== null ? (int)$j2['timeA_penaltis'] : null;
					$penB_j2 = $j2['timeB_penaltis'] !== null ? (int)$j2['timeB_penaltis'] : null;
				}

				$totA = $golsA_j1 + $golsA_j2;
				$totB = $golsB_j1 + $golsB_j2;

				if ($totA > $totB) {
					$vencId = $idA;
					$vencNome = $nomeA;
				} elseif ($totB > $totA) {
					$vencId = $idB;
					$vencNome = $nomeB;
				} else {
					$golForaAtivo = isset($optComp['golfora']) && intval($optComp['golfora']) == 1;
					if ($golForaAtivo && $golsA_j2 !== $golsB_j1) {
						if ($golsA_j2 > $golsB_j1) {
							$vencId = $idA;
							$vencNome = $nomeA;
						} else {
							$vencId = $idB;
							$vencNome = $nomeB;
						}
					} elseif ($penA_j2 !== null && $penB_j2 !== null) {
						if ($penA_j2 > $penB_j2) {
							$vencId = $idA;
							$vencNome = $nomeA;
						} else {
							$vencId = $idB;
							$vencNome = $nomeB;
						}
					} else {
						$vencId = $idA;
						$vencNome = $nomeA;
					}
				}
			}

			// Preencher nome caso esteja vazio
			if (empty($vencNome) && $vencId > 0) {
				$stC = $this->conn->prepare("SELECT nome FROM clube WHERE id = ?");
				$stC->execute([$vencId]);
				$vencNome = $stC->fetchColumn() ?: null;
			}

			$vencedoresJogos[] = [
				'id' => $vencId,
				'nome' => $vencNome
			];
		}

		// 5. Identificar se há times de BYE (somente aplicável na primeira fase da competição de mata-mata)
		$byes = [];
		// As fases de mata-mata em ordem cronológica: 11 (64-avos), 10 (32-avos), 9 (16-avos), 3 (Oitavas), 4 (Quartas), 5 (Semi), 8 (Final)
		$stmtMinFase = $this->conn->prepare("
			SELECT fase 
			FROM jogos_clube 
			WHERE competicao_id = :idComp AND simulador_interno = 1 AND fase IN (11, 10, 9, 3, 4, 5, 8) 
			ORDER BY FIELD(fase, 11, 10, 9, 3, 4, 5, 8) ASC 
			LIMIT 1
		");
		$stmtMinFase->execute([':idComp' => $idCompeticao]);
		$minFaseRow = $stmtMinFase->fetch(PDO::FETCH_ASSOC);
		$isFirstRoundOfComp = ($minFaseRow && (int)$minFaseRow['fase'] === $faseAtual);

		if ($isFirstRoundOfComp) {
			$jogandoIds = [];
			$jogandoNomes = [];
			foreach ($jogosFase as $jg) {
				if ((int)$jg['timeA_id'] > 0) $jogandoIds[(int)$jg['timeA_id']] = true;
				if (!empty($jg['timeA_nome'])) $jogandoNomes[trim($jg['timeA_nome'])] = true;
				if ((int)$jg['timeB_id'] > 0) $jogandoIds[(int)$jg['timeB_id']] = true;
				if (!empty($jg['timeB_nome'])) $jogandoNomes[trim($jg['timeB_nome'])] = true;
			}

			// Carregar todos os participantes / slots da competição
			$assignedSlotTeams = [];
			$stTimesSlots = $this->carregarListaTimes($idCompeticao);
			while ($rSlot = $stTimesSlots->fetch(PDO::FETCH_ASSOC)) {
				$sName = !empty($rSlot['slot']) ? trim($rSlot['slot']) : ("Slot " . $rSlot['codigo_time']);
				if (!empty($rSlot['id_time_portal']) && intval($rSlot['id_time_portal']) > 0) {
					$assignedSlotTeams[$sName] = intval($rSlot['id_time_portal']);
				} else if ($rSlot['has_team'] == 1 || $rSlot['has_team'] == '1') {
					$assignedSlotTeams[$sName] = -1 * abs(intval($rSlot['codigo_time']));
				}
			}

			// Carregar clubes do SQLite caso existam
			$baseDir = dirname(__DIR__);
			$docRoot = (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '') ? $_SERVER['DOCUMENT_ROOT'] : $baseDir;
			$db3File = $baseDir . "/competicoes/databases/" . $idCompeticao . "-database.db3";
			if (!file_exists($db3File)) {
				$db3File = $docRoot . "/competicoes/databases/" . $idCompeticao . "-database.db3";
			}
			$clubesDb = [];
			if (file_exists($db3File)) {
				try {
					$cdb = new PDO("sqlite:" . $db3File);
					$stmtClubes = $cdb->query("SELECT ID, Nome FROM clube");
					if ($stmtClubes) {
						while ($rClube = $stmtClubes->fetch(PDO::FETCH_ASSOC)) {
							$clubesDb[(int)$rClube['ID']] = $rClube['Nome'];
						}
					}
				} catch (\Throwable $e) {}
			}

			if (!empty($assignedSlotTeams)) {
				foreach ($assignedSlotTeams as $sName => $cId) {
					$cIdInt = (int)$cId;
					$isNoJogo = false;
					if ($cIdInt > 0 && isset($jogandoIds[$cIdInt])) $isNoJogo = true;
					if (isset($jogandoNomes[$sName])) $isNoJogo = true;

					if (!$isNoJogo) {
						$nomeClube = isset($clubesDb[$cIdInt]) ? $clubesDb[$cIdInt] : $sName;
						$byes[] = [
							'id' => ($cIdInt > 0) ? $cIdInt : 0,
							'nome' => ($cIdInt > 0) ? null : $nomeClube
						];
					}
				}
			} else if (!empty($clubesDb)) {
				foreach ($clubesDb as $cId => $nomeClube) {
					if (!isset($jogandoIds[$cId])) {
						$byes[] = [
							'id' => $cId,
							'nome' => null
						];
					}
				}
			}
		}

		// 6. Montar confrontos da próxima fase
		$confrontosProximaFase = [];
		$numByes = count($byes);

		// Primeiro, parear cada BYE #i com o Vencedor do Jogo #i
		for ($i = 0; $i < $numByes; $i++) {
			$byeTeam = $byes[$i];
			$vencTeam = isset($vencedoresJogos[$i]) ? $vencedoresJogos[$i] : ['id' => 0, 'nome' => 'A definir'];
			$confrontosProximaFase[] = [
				'timeA' => $byeTeam,
				'timeB' => $vencTeam
			];
		}

		// Depois, parear os vencedores de jogos restantes entre si de 2 em 2
		for ($i = $numByes; $i < count($vencedoresJogos); $i += 2) {
			$team1 = $vencedoresJogos[$i];
			$team2 = isset($vencedoresJogos[$i+1]) ? $vencedoresJogos[$i+1] : ['id' => 0, 'nome' => 'A definir'];
			$confrontosProximaFase[] = [
				'timeA' => $team1,
				'timeB' => $team2
			];
		}

		if (empty($confrontosProximaFase)) return false;

		// 7. Obter estádios e árbitros para a nova fase
		$baseDir = dirname(__DIR__);
		$docRoot = (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '') ? $_SERVER['DOCUMENT_ROOT'] : $baseDir;
		$db3File = $baseDir . "/competicoes/databases/" . $idCompeticao . "-database.db3";
		if (!file_exists($db3File)) {
			$db3File = $docRoot . "/competicoes/databases/" . $idCompeticao . "-database.db3";
		}
		$ldb = null;
		$estadios = [];
		$arbitros = [];
		if (file_exists($db3File)) {
			try {
				$ldb = new PDO("sqlite:" . $db3File);
				$ldb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$stmtEst = $ldb->query("SELECT ID, Capacidade FROM estadio");
				if ($stmtEst) $estadios = $stmtEst->fetchAll(PDO::FETCH_ASSOC);
				$stmtArb = $ldb->query("SELECT ID FROM trioarbitragem");
				if (!$stmtArb) {
					$stmtArb = $ldb->query("SELECT ID FROM arbitro");
				}
				if ($stmtArb) $arbitros = $stmtArb->fetchAll(PDO::FETCH_ASSOC);
			} catch (\Throwable $e) {}
		}

		// Opções da próxima fase
		$isIdaEVolta = isset($optComp['tipo_preliminar']) && intval($optComp['tipo_preliminar']) == 1;
		$isFinalUnica = isset($optComp['finalunica']) && intval($optComp['finalunica']) == 1;
		$proximaTemVolta = ($isIdaEVolta && !($proximaFase == 8 && $isFinalUnica));
		$estadiosTimes = isset($optComp['estadios_times']) ? intval($optComp['estadios_times']) : 1;
		$capGeral = isset($optComp['capacidade_minima_estadio']) ? intval($optComp['capacidade_minima_estadio']) : 0;
		$capFinal = isset($optComp['capacidade_minima_final']) ? intval($optComp['capacidade_minima_final']) : 0;
		$minCapProxFase = ($proximaFase == 8 && $capFinal > 0) ? $capFinal : $capGeral;

		$getEstadioNeutro = function(&$usedInPhase, $teamA_id = 0, $teamB_id = 0) use ($estadios, $minCapProxFase, $ldb) {
			if (empty($estadios)) return 0;
			$estadioA = 0;
			$estadioB = 0;
			if ($ldb) {
				if ($teamA_id != 0) {
					$stA = $ldb->prepare("SELECT Estadio FROM clube WHERE ID = ? LIMIT 1");
					$stA->execute([$teamA_id]);
					$estadioA = (int)$stA->fetchColumn();
				}
				if ($teamB_id != 0) {
					$stB = $ldb->prepare("SELECT Estadio FROM clube WHERE ID = ? LIMIT 1");
					$stB->execute([$teamB_id]);
					$estadioB = (int)$stB->fetchColumn();
				}
			}
			$disponiveis = [];
			foreach ($estadios as $est) {
				$eId = (int)$est['ID'];
				if ($eId > 0 && $eId != $estadioA && $eId != $estadioB) {
					$disponiveis[] = $est;
				}
			}
			if (empty($disponiveis)) {
				$disponiveis = $estadios;
			}
			if ($minCapProxFase > 0) {
				$comCap = array_filter($disponiveis, function($e) use ($minCapProxFase) {
					return (int)($e['Capacidade'] ?? 0) >= $minCapProxFase;
				});
				if (!empty($comCap)) {
					$disponiveis = array_values($comCap);
				}
			}
			$dispIds = array_map(function($e) { return (int)$e['ID']; }, $disponiveis);
			$naoUsados = array_values(array_diff($dispIds, $usedInPhase));
			$escolhido = !empty($naoUsados) ? $naoUsados[array_rand($naoUsados)] : $dispIds[array_rand($dispIds)];
			$usedInPhase[] = $escolhido;
			return $escolhido;
		};

		$getEstadioClube = function($teamId) use ($ldb) {
			if (!$ldb || empty($teamId)) return 0;
			try {
				$st = $ldb->prepare("SELECT Estadio FROM clube WHERE ID = ? LIMIT 1");
				$st->execute([$teamId]);
				return (int)$st->fetchColumn() ?: 0;
			} catch (\Throwable $e) { return 0; }
		};

		// Data inicial para a próxima fase: 7 dias após a data do último jogo da fase atual, ou a partir de amanhã se já tiver passado
		$lastDate = !empty($jogosFase[count($jogosFase)-1]['data']) ? $jogosFase[count($jogosFase)-1]['data'] : date('Y-m-d H:i:s');
		$baseTimestamp = strtotime($lastDate . ' +7 days');
		if ($baseTimestamp < time()) {
			$baseTimestamp = strtotime('+1 day 16:00:00');
		}
		$baseDate = date('Y-m-d 16:00:00', $baseTimestamp);

		$usedStadiumsInPhase = [];

		// 8. Inserir os jogos da próxima fase no MariaDB (Ida)
		foreach ($confrontosProximaFase as $idxConf => $conf) {
			$tA_id = $conf['timeA']['id'];
			$tA_nome = $conf['timeA']['nome'];
			$tB_id = $conf['timeB']['id'];
			$tB_nome = $conf['timeB']['nome'];

			$dateMatch = date("Y-m-d 16:00:00", strtotime($baseDate . " +" . floor($idxConf / 2) . " days"));
			$arbId = count($arbitros) > 0 ? (int)$arbitros[array_rand($arbitros)]['ID'] : 0;

			if ($proximaTemVolta && $estadiosTimes == 1) {
				$estId = ($tA_id != 0) ? $getEstadioClube($tA_id) : 0;
				if (!$estId) $estId = $getEstadioNeutro($usedStadiumsInPhase, $tA_id, $tB_id);
				$neutro = "false";
			} else {
				$estId = $getEstadioNeutro($usedStadiumsInPhase, $tA_id, $tB_id);
				$neutro = "true";
			}

			$chaveProxNum = (string)($idxConf + 1);
			$this->inserirJogo($idCompeticao, $tA_id, $tB_id, $proximaFase, $arbId, $estId, $dateMatch, $neutro, $chaveProxNum, null, $tA_nome, $tB_nome);
		}

		// Se for ida e volta, inserir jogos de VOLTA 7 dias depois
		if ($proximaTemVolta) {
			$baseDateVolta = date("Y-m-d 16:00:00", strtotime($baseDate . " +7 days"));
			$usedStadiumsInVolta = [];
			foreach ($confrontosProximaFase as $idxConf => $conf) {
				$tA_id = $conf['timeA']['id'];
				$tA_nome = $conf['timeA']['nome'];
				$tB_id = $conf['timeB']['id'];
				$tB_nome = $conf['timeB']['nome'];

				$dateMatchVolta = date("Y-m-d 16:00:00", strtotime($baseDateVolta . " +" . floor($idxConf / 2) . " days"));
				$arbId = count($arbitros) > 0 ? (int)$arbitros[array_rand($arbitros)]['ID'] : 0;

				if ($estadiosTimes == 1) {
					$estIdVolta = ($tB_id != 0) ? $getEstadioClube($tB_id) : 0;
					if (!$estIdVolta) $estIdVolta = $getEstadioNeutro($usedStadiumsInVolta, $tB_id, $tA_id);
					$neutroVolta = "false";
				} else {
					$estIdVolta = $getEstadioNeutro($usedStadiumsInVolta, $tB_id, $tA_id);
					$neutroVolta = "true";
				}

				$chaveProxNum = (string)($idxConf + 1);
				$this->inserirJogo($idCompeticao, $tB_id, $tA_id, $proximaFase, $arbId, $estIdVolta, $dateMatchVolta, $neutroVolta, $chaveProxNum, null, $tB_nome, $tA_nome);
			}
		}

		return true;
	}

	public function temJogosSimulados($idCompeticao) {
		$idCompeticao = (int)$idCompeticao;
		$query = "SELECT COUNT(*) as total FROM jogos_clube WHERE competicao_id = :id AND (status = 1 OR timeA_gols IS NOT NULL)";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(':id', $idCompeticao, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return (intval($row['total'] ?? 0) > 0);
	}

	public function excluir($idCompeticao, $userId, $isAdmin = false) {
		$idCompeticao = (int)$idCompeticao;
		$userId = (int)$userId;

		// 1. Verificar se a competição existe e se o usuário tem permissão
		$queryComp = "SELECT id, nome, logo, dono FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
		$stmtComp = $this->conn->prepare($queryComp);
		$stmtComp->bindParam(':id', $idCompeticao, PDO::PARAM_INT);
		$stmtComp->execute();
		$comp = $stmtComp->fetch(PDO::FETCH_ASSOC);

		if (!$comp) {
			return ['success' => false, 'error' => 'Competição não encontrada.'];
		}

		if (!$isAdmin && intval($comp['dono']) !== $userId) {
			return ['success' => false, 'error' => 'Você não tem permissão para excluir esta competição.'];
		}

		// 2. Bloquear se houver jogos simulados ou finalizados
		if ($this->temJogosSimulados($idCompeticao)) {
			return ['success' => false, 'error' => 'Não é possível excluir esta competição pois ela já possui jogos simulados ou finalizados.'];
		}

		// 3. Executar limpeza em cascata com transação
		try {
			$this->conn->beginTransaction();

			// Remover jogos agendados/não simulados
			$stmtDelJogos = $this->conn->prepare("DELETE FROM jogos_clube WHERE competicao_id = :id");
			$stmtDelJogos->execute([':id' => $idCompeticao]);

			// Remover vínculos de times
			$stmtDelTimes = $this->conn->prepare("DELETE FROM competicao_times WHERE id_competicao = :id");
			$stmtDelTimes->execute([':id' => $idCompeticao]);

			// Remover opções/regulamento
			$stmtDelOpcoes = $this->conn->prepare("DELETE FROM competicao_opcoes WHERE id_competicao = :id");
			$stmtDelOpcoes->execute([':id' => $idCompeticao]);

			// Remover log de alterações
			$stmtDelLog = $this->conn->prepare("DELETE FROM competicao_alteracoes_log WHERE id_competicao = :id");
			$stmtDelLog->execute([':id' => $idCompeticao]);

			// Remover registro principal da competição
			$stmtDelComp = $this->conn->prepare("DELETE FROM " . $this->table_name . " WHERE id = :id");
			$stmtDelComp->execute([':id' => $idCompeticao]);

			$this->conn->commit();

			// 4. Remover arquivo SQLite da competição (.db3) se existir
			$baseDir = dirname(__DIR__);
			$docRoot = (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '') ? $_SERVER['DOCUMENT_ROOT'] : $baseDir;
			$db3Paths = [
				$baseDir . "/competicoes/databases/" . $idCompeticao . "-database.db3",
				$docRoot . "/competicoes/databases/" . $idCompeticao . "-database.db3"
			];
			foreach ($db3Paths as $p) {
				if (file_exists($p)) {
					@unlink($p);
				}
			}

			// 5. Remover logo do disco se existir e não for imagem padrão
			$logoFile = $comp['logo'] ?? '';
			if (!empty($logoFile) && $logoFile !== '0.png' && $logoFile !== 'flag.png' && $logoFile !== 'default.webp') {
				$logoPaths = [
					$baseDir . "/images/competicoes/" . $logoFile,
					$docRoot . "/images/competicoes/" . $logoFile
				];
				foreach ($logoPaths as $lp) {
					if (file_exists($lp)) {
						@unlink($lp);
					}
				}
			}

			return ['success' => true];
		} catch (\Exception $e) {
			if ($this->conn->inTransaction()) {
				$this->conn->rollBack();
			}
			return ['success' => false, 'error' => 'Falha ao excluir competição: ' . $e->getMessage()];
		}
	}
}
?>
