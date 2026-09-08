<?php

//ini_set( 'display_errors', true );
//error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
    
    if($_SESSION['emTestes'] ?? false){
        die(json_encode(['success' => false, 'error' => 'Usuários em período de testes não podem alterar opções de competições.']));
    }
    
$error_msg = "";
   
// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$competicao = new Competicao_clube($db);

// if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    $idUsuario = $_SESSION['user_id'];
    $estadios_times = isset($_POST['estadios_times']) ? intval($_POST['estadios_times']) : 0;
    $idCompeticao = intval($_POST['id_competicao']);
    $desempate_grupos = isset($_POST['desempate_grupos']) ? $_POST['desempate_grupos'] : 'SG,GP,VI,CD';
    $num_grupos = isset($_POST['num_grupos']) ? intval($_POST['num_grupos']) : 4;
    $times_por_grupo = isset($_POST['times_por_grupo']) ? intval($_POST['times_por_grupo']) : 4;
    $tipo_preliminar = isset($_POST['tipo_preliminar']) ? intval($_POST['tipo_preliminar']) : 1;
    $turnos_pontos_corridos = isset($_POST['turnos_pontos_corridos']) ? intval($_POST['turnos_pontos_corridos']) : 2;
    $data_inicial = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : null;
    $max_jogos_dia = isset($_POST['max_jogos_dia']) ? intval($_POST['max_jogos_dia']) : 0;
    $dias_semana = isset($_POST['dias_semana']) ? $_POST['dias_semana'] : '';
    $intervalo_rodadas = isset($_POST['intervalo_rodadas']) ? intval($_POST['intervalo_rodadas']) : 1;
    $horarios_jogos = !empty($_POST['horarios_jogos']) ? $_POST['horarios_jogos'] : '16:00';
    $expulso_dois_amarelos = isset($_POST['expulso_dois_amarelos']) ? intval($_POST['expulso_dois_amarelos']) : 0;
	
	if($competicao->alterarOpcoes($idUsuario, $_POST['numero_times'], $_POST['data_limite'], $_POST['subir_live'], $_POST['sorteio'], $_POST['gol_fora'], $_POST['final_unica'], $_POST['tipo_competicao'], $_POST['criterio_desempate'], $_POST['criterio_desempate_final'], $_POST['criterio_suspensao'], $_POST['zerar_amarelos'], $_POST['permitir_alteracoes'], $_POST['inicio_alteracoes'], $_POST['fim_alteracoes'], $_POST['numero_alteracoes'], $idCompeticao, $estadios_times, $desempate_grupos, $num_grupos, $times_por_grupo, $tipo_preliminar, $turnos_pontos_corridos, $data_inicial, $max_jogos_dia, $dias_semana, $intervalo_rodadas, $horarios_jogos, $expulso_dois_amarelos)){
		$is_success = true;
	} else {
		$is_success = false;
	}

    if ($is_success) {
        // Sincronizar Árbitros e Estádios no SQLite
        require_once $_SERVER['DOCUMENT_ROOT'] . "/config/sqliteDatabase.php";
        $sqliteDb = new SQLiteDatabase();
        $sqliteDb->fileName = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $idCompeticao . "-database.db3";
        $sdb = $sqliteDb->getConnection();

        if ($sdb) {
            try {
                // Garantir que as tabelas existam no SQLite
                $sqliteDb->prepareTables();

                // --- 1. Sincronizar Árbitros ---
                $sdb->exec("DELETE FROM trioarbitragem");

                $arbitroIds = isset($_POST['arbitros']) ? array_map('intval', $_POST['arbitros']) : [];
                $arbitroPais = isset($_POST['arbitros_pais']) ? intval($_POST['arbitros_pais']) : 0;
                $arbitroFed = isset($_POST['arbitros_federacao']) ? $_POST['arbitros_federacao'] : '';

                $arbitrosQuery = "SELECT id, nomeArbitro, nomeAuxiliarUm, nomeAuxiliarDois, estilo FROM arbitros WHERE 0";
                if (!empty($arbitroIds)) {
                    $arbitrosQuery .= " OR id IN (" . implode(',', $arbitroIds) . ")";
                }
                if ($arbitroPais > 0) {
                    $arbitrosQuery .= " OR pais = " . $arbitroPais;
                }
                if (!empty($arbitroFed) && $arbitroFed !== "0") {
                    $arbitrosQuery .= " OR pais IN (SELECT id FROM paises WHERE federacao = " . $db->quote($arbitroFed) . ")";
                }

                if ($arbitrosQuery !== "SELECT id, nomeArbitro, nomeAuxiliarUm, nomeAuxiliarDois, estilo FROM arbitros WHERE 0") {
                    $stmtArb = $db->query($arbitrosQuery);
                    if ($stmtArb) {
                        $stmtInsertArb = $sdb->prepare("INSERT INTO trioarbitragem (ID, Arbitro, Auxiliar1, Auxiliar2, Estilo) VALUES (:id, :nome, :aux1, :aux2, :estilo)");
                        while ($row = $stmtArb->fetch(PDO::FETCH_ASSOC)) {
                            $stmtInsertArb->bindValue(':id', (int)$row['id'], PDO::PARAM_INT);
                            $stmtInsertArb->bindValue(':nome', $row['nomeArbitro']);
                            $stmtInsertArb->bindValue(':aux1', $row['nomeAuxiliarUm']);
                            $stmtInsertArb->bindValue(':aux2', $row['nomeAuxiliarDois']);
                            $stmtInsertArb->bindValue(':estilo', (int)$row['estilo'], PDO::PARAM_INT);
                            $stmtInsertArb->execute();
                        }
                    }
                }

                // --- 2. Sincronizar Estádios ---
                $sdb->exec("DELETE FROM estadio");

                $estadioIds = isset($_POST['estadios']) ? array_map('intval', $_POST['estadios']) : [];
                if (!empty($estadioIds)) {
                    $estadiosQuery = "SELECT id, Nome, Capacidade, Clima, Altitude, Caldeirao FROM estadio WHERE id IN (" . implode(',', $estadioIds) . ")";
                    $stmtEst = $db->query($estadiosQuery);
                    if ($stmtEst) {
                        $stmtInsertEst = $sdb->prepare("INSERT INTO estadio (ID, Nome, Capacidade, Clima, Altitude, Caldeirao) VALUES (:id, :nome, :cap, :clima, :alt, :cald)");
                        while ($row = $stmtEst->fetch(PDO::FETCH_ASSOC)) {
                            $stmtInsertEst->bindValue(':id', (int)$row['id'], PDO::PARAM_INT);
                            $stmtInsertEst->bindValue(':nome', $row['Nome']);
                            $stmtInsertEst->bindValue(':cap', (int)$row['Capacidade'], PDO::PARAM_INT);
                            $stmtInsertEst->bindValue(':clima', (int)$row['Clima'], PDO::PARAM_INT);
                            $stmtInsertEst->bindValue(':alt', (int)$row['Altitude'], PDO::PARAM_INT);
                            $stmtInsertEst->bindValue(':cald', (int)$row['Caldeirao'], PDO::PARAM_INT);
                            $stmtInsertEst->execute();
                        }
                    }
                }

                // --- 3. Sincronizar Opções do Simulador Hexacolor (tabela opcoes) ---
                $hexa_balizamento = isset($_POST['hexa_balizamento']) ? intval($_POST['hexa_balizamento']) : 0;
                $hexa_subs = isset($_POST['hexa_subs']) ? max(1, min(7, intval($_POST['hexa_subs']))) : 3;
                $hexa_paradas = isset($_POST['hexa_paradas']) ? max(1, min(5, intval($_POST['hexa_paradas']))) : 3;
                $hexa_sub_extra = isset($_POST['hexa_sub_extra']) ? intval($_POST['hexa_sub_extra']) : 1;
                $hexa_var = isset($_POST['hexa_var']) ? intval($_POST['hexa_var']) : 1;
                $hexa_limitar_lesoes = isset($_POST['hexa_limitar_lesoes']) ? intval($_POST['hexa_limitar_lesoes']) : 0;
                $hexa_tempo_limite = isset($_POST['hexa_tempo_limite']) ? max(1, min(365, intval($_POST['hexa_tempo_limite']))) : 180;

                // Converter hex #RRGGBB para ARGB int32 assinado do Java
                $hexToArgbInt = function($hexStr, $defaultInt) {
                    if (empty($hexStr)) return $defaultInt;
                    $clean = ltrim($hexStr, '#');
                    if (strlen($clean) === 6) {
                        $rgb = hexdec($clean);
                        // ARGB com Alpha 255 (0xFF000000)
                        $argb = 0xFF000000 | ($rgb & 0xFFFFFF);
                        // Converter para 32-bit signed int
                        if ($argb > 0x7FFFFFFF) {
                            $argb -= 0x100000000;
                        }
                        return (int)$argb;
                    }
                    return $defaultInt;
                };

                $cor1Int = $hexToArgbInt($_POST['hexa_cor1'] ?? '', -1);
                $cor2Int = $hexToArgbInt($_POST['hexa_cor2'] ?? '', -16777216);
                $cor3Int = $hexToArgbInt($_POST['hexa_cor3'] ?? '', -16777216);

                $stmtOpUpsert = $sdb->prepare("INSERT OR REPLACE INTO opcoes (parametro, valor, valorLong) VALUES (:param, :val, :valLong)");

                $opcoesParaSalvar = [
                    'mostrarSumula' => [1, 0],
                    'balizamento' => [$hexa_balizamento, 0],
                    'VAR' => [$hexa_var, 0],
                    'substituicoes' => [$hexa_subs, 0],
                    'paradas' => [$hexa_paradas, 0],
                    'subExtraProrrogacao' => [$hexa_sub_extra, 0],
                    'limitarLesoes' => [$hexa_limitar_lesoes, 0],
                    'tempoLimite' => [$hexa_tempo_limite, 0],
                    'partidaCor1' => [$cor1Int, 0],
                    'partidaCor2' => [$cor2Int, 0],
                    'partidaCor3' => [$cor3Int, 0],
                ];

                foreach ($opcoesParaSalvar as $pName => $pValues) {
                    $stmtOpUpsert->bindValue(':param', $pName, PDO::PARAM_STR);
                    $stmtOpUpsert->bindValue(':val', $pValues[0], PDO::PARAM_INT);
                    $stmtOpUpsert->bindValue(':valLong', $pValues[1], PDO::PARAM_INT);
                    $stmtOpUpsert->execute();
                }

                // --- 4. Sincronizar Parâmetros de Jogo Hexacolor (tabelas parametros e paispadrao) ---
                $hexa_gols = isset($_POST['hexa_gols']) ? max(1, min(20, intval($_POST['hexa_gols']))) : 10;
                $hexa_faltas = isset($_POST['hexa_faltas']) ? max(1, min(20, intval($_POST['hexa_faltas']))) : 10;
                $hexa_impedimentos = isset($_POST['hexa_impedimentos']) ? max(1, min(10, intval($_POST['hexa_impedimentos']))) : 5;
                $hexa_cartoes = isset($_POST['hexa_cartoes']) ? max(1, min(10, intval($_POST['hexa_cartoes']))) : 5;
                $hexa_estilo = isset($_POST['hexa_estilo']) ? max(1, min(5, intval($_POST['hexa_estilo']))) : 3;
                $hexa_bandeiras = isset($_POST['hexa_bandeiras']) ? intval($_POST['hexa_bandeiras']) : 1;

                $chao = 1.6 - (0.2 * $hexa_estilo);
                $alto = 0.4 + (0.2 * $hexa_estilo);

                $sdb->exec("DELETE FROM parametros WHERE padrao = 1 OR ID = 1");
                $stmtInsertParam = $sdb->prepare("INSERT INTO parametros (ID, Nome, Gols, Faltas, Impedimentos, Cartoes, Chao, Alto, padrao) VALUES (1, 'Padrão', :gols, :faltas, :imp, :cart, :chao, :alto, 1)");
                $stmtInsertParam->bindValue(':gols', $hexa_gols, PDO::PARAM_INT);
                $stmtInsertParam->bindValue(':faltas', $hexa_faltas, PDO::PARAM_INT);
                $stmtInsertParam->bindValue(':imp', $hexa_impedimentos, PDO::PARAM_INT);
                $stmtInsertParam->bindValue(':cart', $hexa_cartoes, PDO::PARAM_INT);
                $stmtInsertParam->bindValue(':chao', $chao);
                $stmtInsertParam->bindValue(':alto', $alto);
                $stmtInsertParam->execute();

                $sdb->exec("DELETE FROM paispadrao WHERE ID_Parametro = 1");
                $stmtInsertPaisPadrao = $sdb->prepare("INSERT INTO paispadrao (ID_Parametro, PaisPadrao, ExibirBandeiras) VALUES (1, '-', :bandeiras)");
                $stmtInsertPaisPadrao->bindValue(':bandeiras', $hexa_bandeiras, PDO::PARAM_INT);
                $stmtInsertPaisPadrao->execute();

            } catch (Exception $e) {
                error_log("Erro no SQLite Sync em alteraropcoes: " . $e->getMessage());
            }
        }
    }

    die(json_encode([ 'success'=> $is_success, 'error'=> ""]));
} 
die(json_encode([ 'success'=> 'false', 'error'=> "Não foi feito POST request"]));

    } else {
        
        die(json_encode([ 'success'=> false, 'error'=> "Usuário, refaça o login!"]));


}
?>
