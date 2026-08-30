<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: /index.php");
    exit();
}

$idCompeticao = isset($_GET['comp']) ? intval($_GET['comp']) : 0;
$idTime       = isset($_GET['team']) ? intval($_GET['team']) : 0;
$idJogo       = isset($_GET['jogo']) ? intval($_GET['jogo']) : 0;

if (!$idCompeticao || !$idTime) {
    die("Competição ou Time não informados.");
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/config/database.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/config/sqliteDatabase.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/objetos/competicao_clube.php";

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

$infoComp = $competicaoObj->readInfo($idCompeticao);
$donoCompeticao = isset($infoComp['dono']) ? $infoComp['dono'] : 0;
$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);
$userLogado = $_SESSION['user_id'];

// Verificar o dono do time no SQLite
$sqliteDb = new SQLiteDatabase();
$sqliteDb->fileName = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $idCompeticao . "-database.db3";
$sdb = $sqliteDb->getConnection();

// Garantir que a tabela escalacao_jogo exista neste banco SQLite
$sdb->exec("CREATE TABLE IF NOT EXISTS `escalacao_jogo` (
    `Jogo`	int ( 10 ) NOT NULL,
    `Clube`	int ( 5 ) NOT NULL,
    `Jogador1`	int ( 5 ) NOT NULL,
    `Jogador2`	int ( 5 ) NOT NULL,
    `Jogador3`	int ( 5 ) NOT NULL,
    `Jogador4`	int ( 5 ) NOT NULL,
    `Jogador5`	int ( 5 ) NOT NULL,
    `Jogador6`	int ( 5 ) NOT NULL,
    `Jogador7`	int ( 5 ) NOT NULL,
    `Jogador8`	int ( 5 ) NOT NULL,
    `Jogador9`	int ( 5 ) NOT NULL,
    `Jogador10`	int ( 5 ) NOT NULL,
    `Jogador11`	int ( 5 ) NOT NULL,
    `Capitao`	int ( 5 ) NOT NULL,
    `Penalti1`	int ( 5 ) DEFAULT NULL,
    `Penalti2`	int ( 5 ) DEFAULT NULL,
    `Penalti3`	int ( 5 ) DEFAULT NULL,
    `Indisponiveis`	text,
    `PosicoesEscolhidas`	text,
    PRIMARY KEY(`Jogo`,`Clube`)
);");

try {
    $sdb->exec("ALTER TABLE `escalacao_jogo` ADD COLUMN `PosicoesEscolhidas` text;");
} catch (Exception $e) {}

// Obter o Nome do Time e o dono do País diretamente do MariaDB principal
$donoTime = 0;
$nomeTime = "Time";

$stmtMariaClube = $db->prepare("SELECT c.nome as NomeClube, p.dono as idDonoPais FROM clube c LEFT JOIN paises p ON c.Pais = p.id WHERE c.id = :idTime LIMIT 1");
$stmtMariaClube->bindParam(':idTime', $idTime);
$stmtMariaClube->execute();
$mariaClube = $stmtMariaClube->fetch(PDO::FETCH_ASSOC);

if ($mariaClube) {
    $nomeTime = $mariaClube['NomeClube'];
    $donoTime = intval($mariaClube['idDonoPais']);
} else {
    // Fallback básico para obter o nome do clube do SQLite caso não seja um ID do Portal
    $stmtClube = $sdb->prepare("SELECT Nome FROM clube WHERE ID = :id");
    $stmtClube->bindParam(':id', $idTime);
    $stmtClube->execute();
    $clubeRow = $stmtClube->fetch(PDO::FETCH_ASSOC);
    if ($clubeRow) {
        $nomeTime = $clubeRow['Nome'];
    }
}

// Simulamos a variável clube antiga contendo apenas o Nome
$clube = ['Nome' => $nomeTime];

// Permissão: Apenas Admin, Dono da Competição ou Dono do Time
if (!$isAdmin && $userLogado != $donoCompeticao && $userLogado != $donoTime) {
    die("Acesso negado. Apenas o responsável pelo time ou o administrador podem alterar a escalação.");
}

// Se for edição de escalação para um jogo específico, validar se o jogo já foi simulado/encerrado
if ($idJogo > 0) {
    $stmtStatusJogo = $db->prepare("SELECT status FROM jogos_clube WHERE id = :idJogo LIMIT 1");
    $stmtStatusJogo->bindParam(':idJogo', $idJogo, PDO::PARAM_INT);
    $stmtStatusJogo->execute();
    $jogoStatusRow = $stmtStatusJogo->fetch(PDO::FETCH_ASSOC);
    if ($jogoStatusRow && $jogoStatusRow['status'] == 1) {
        die("Esta partida já foi simulada/encerrada e sua escalação não pode mais ser alterada.");
    }
}

// Processar formulário de escalação (POST)
$mensagem = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_escalacao'])) {
    // Salvar indisponíveis manuais na sessão apenas se for escalação global (sem idJogo)
    if ($idJogo === 0) {
        $indisponiveisKey = 'indisponiveis_' . $idCompeticao . '_' . $idTime;
        $_SESSION[$indisponiveisKey] = isset($_POST['indisponiveis']) ? array_map('intval', $_POST['indisponiveis']) : [];
    }
    // Salvar posições escolhidas na sessão apenas se for escalação global (sem idJogo)
    if ($idJogo === 0) {
        $posicoesEscolhidasKey = 'posicoes_' . $idCompeticao . '_' . $idTime;
        $_SESSION[$posicoesEscolhidasKey] = isset($_POST['posicao_jogador']) ? $_POST['posicao_jogador'] : [];
    }

    $titulares = isset($_POST['titulares']) ? $_POST['titulares'] : array();
    $capitao = isset($_POST['capitao']) ? intval($_POST['capitao']) : 0;
    
    if (count($titulares) !== 11) {
        $mensagem = "<div style='background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); color: #fca5a5; padding: 12px 16px; border-radius:8px; margin-bottom: 15px;'>Selecione exatamente 11 titulares (selecionados: " . count($titulares) . ").</div>";
    } else {
        try {
            // Obter o registro atual da escalação para saber as posições táticas (Pos1 a Pos11)
            $stmtCurrentEsc = $sdb->prepare("SELECT * FROM escalacao WHERE Clube = :clube LIMIT 1");
            $stmtCurrentEsc->bindParam(':clube', $idTime);
            $stmtCurrentEsc->execute();
            $currentEsc = $stmtCurrentEsc->fetch(PDO::FETCH_ASSOC);

            if (!$currentEsc) {
                throw new Exception("Escalação do clube não encontrada no banco de dados.");
            }

            // Mapeamento de posições abreviadas para colunas na tabela posicaojogador
            $posMap = [
                'G' => 'Goleiro',
                'Z' => 'Zagueiro',
                'LD' => 'LateralDireito',
                'LE' => 'LateralEsquerdo',
                'V' => 'Volante',
                'MC' => 'MeiaCentral',
                'MD' => 'MeiaDireita',
                'ME' => 'MeiaEsquerda',
                'MA' => 'MeiaCentral',
                'A' => 'Atacante',
                'Aa' => 'Atacante',
                'PE' => 'Atacante',
                'PD' => 'Atacante',
                'AD' => 'LateralDireito',
                'AE' => 'LateralEsquerdo',
            ];

            // Obter as posições preferidas dos titulares selecionados
            $playersPositions = [];
            $inClause = implode(',', array_map('intval', $titulares));
            $stmtPos = $sdb->query("SELECT * FROM posicaojogador WHERE Jogador IN ($inClause)");
            while ($row = $stmtPos->fetch(PDO::FETCH_ASSOC)) {
                $pId = (int)$row['Jogador'];
                $playersPositions[$pId] = [];
                if (isset($row['G']) && $row['G'] == 1) $playersPositions[$pId][] = 'Goleiro';
                if (isset($row['Z']) && $row['Z'] == 1) $playersPositions[$pId][] = 'Zagueiro';
                if ((isset($row['LD']) && $row['LD'] == 1) || (isset($row['AD']) && $row['AD'] == 1)) $playersPositions[$pId][] = 'LateralDireito';
                if ((isset($row['LE']) && $row['LE'] == 1) || (isset($row['AE']) && $row['AE'] == 1)) $playersPositions[$pId][] = 'LateralEsquerdo';
                if (isset($row['V']) && $row['V'] == 1) $playersPositions[$pId][] = 'Volante';
                if ((isset($row['MC']) && $row['MC'] == 1) || (isset($row['MA']) && $row['MA'] == 1)) $playersPositions[$pId][] = 'MeiaCentral';
                if (isset($row['MD']) && $row['MD'] == 1) $playersPositions[$pId][] = 'MeiaDireita';
                if (isset($row['ME']) && $row['ME'] == 1) $playersPositions[$pId][] = 'MeiaEsquerda';
                if ((isset($row['PD']) && $row['PD'] == 1) || (isset($row['PE']) && $row['PE'] == 1) || (isset($row['Am']) && $row['Am'] == 1) || (isset($row['Aa']) && $row['Aa'] == 1)) $playersPositions[$pId][] = 'Atacante';
            }

            // Garantir que todos tenham alguma entrada no array
            foreach ($titulares as $tId) {
                if (!isset($playersPositions[$tId])) {
                    $playersPositions[$tId] = ['Atacante'];
                }
            }

            $tacticalPositions = [];
            for ($i = 1; $i <= 11; $i++) {
                $tacticalPositions[$i] = $currentEsc['Pos' . $i];
            }

            $assigned = [];
            $playerUsed = [];

            // Função recursiva para fazer o matching dos jogadores com as posições táticas
            function assignPositions($posIdx, $tacticalPositions, $posMap, $playersPositions, &$assigned, &$playerUsed) {
                if ($posIdx > 11) {
                    return true;
                }
                
                $tacticalPos = $tacticalPositions[$posIdx];
                $preferredCol = isset($posMap[$tacticalPos]) ? $posMap[$tacticalPos] : 'Atacante';
                
                // Tenta atribuir um jogador livre que tenha essa posição ativada
                foreach ($playersPositions as $pId => $activeCols) {
                    if (!isset($playerUsed[$pId]) && in_array($preferredCol, $activeCols)) {
                        $playerUsed[$pId] = true;
                        $assigned[$posIdx] = $pId;
                        if (assignPositions($posIdx + 1, $tacticalPositions, $posMap, $playersPositions, $assigned, $playerUsed)) {
                            return true;
                        }
                        unset($playerUsed[$pId]);
                        unset($assigned[$posIdx]);
                    }
                }
                
                // Fallback: atribui qualquer jogador livre para essa posição
                foreach (array_keys($playersPositions) as $pId) {
                    if (!isset($playerUsed[$pId])) {
                        $playerUsed[$pId] = true;
                        $assigned[$posIdx] = $pId;
                        if (assignPositions($posIdx + 1, $tacticalPositions, $posMap, $playersPositions, $assigned, $playerUsed)) {
                            return true;
                        }
                        unset($playerUsed[$pId]);
                        unset($assigned[$posIdx]);
                    }
                }
                
                return false;
            }

            assignPositions(1, $tacticalPositions, $posMap, $playersPositions, $assigned, $playerUsed);

            if (!in_array($capitao, $titulares)) {
                $capitao = $titulares[0];
            }

            // Preservar os pênaltis apenas se os jogadores de pênalti ainda estiverem entre os titulares selecionados
            $pen1 = isset($currentEsc['Penalti1']) && in_array($currentEsc['Penalti1'], $titulares) ? $currentEsc['Penalti1'] : $capitao;
            $pen2 = isset($currentEsc['Penalti2']) && in_array($currentEsc['Penalti2'], $titulares) ? $currentEsc['Penalti2'] : $capitao;
            $pen3 = isset($currentEsc['Penalti3']) && in_array($currentEsc['Penalti3'], $titulares) ? $currentEsc['Penalti3'] : $capitao;

            if ($idJogo > 0) {
                // Obter a lista de desfalques manuais de $_POST['indisponiveis']
                $indispPost = isset($_POST['indisponiveis']) ? array_map('intval', $_POST['indisponiveis']) : [];
                $indispStr = implode(',', $indispPost);

                $insertFields = [
                    'Jogo', 'Clube', 'Jogador1', 'Jogador2', 'Jogador3', 'Jogador4', 'Jogador5',
                    'Jogador6', 'Jogador7', 'Jogador8', 'Jogador9', 'Jogador10', 'Jogador11',
                    'Capitao', 'Penalti1', 'Penalti2', 'Penalti3', 'Indisponiveis', 'PosicoesEscolhidas'
                ];
                $placeholders = array_map(function($f) { return ':' . strtolower($f); }, $insertFields);
                $stmtSave = $sdb->prepare("INSERT OR REPLACE INTO escalacao_jogo (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $placeholders) . ")");
                $stmtSave->bindValue(':jogo', $idJogo, PDO::PARAM_INT);
                $stmtSave->bindValue(':clube', $idTime, PDO::PARAM_INT);
                for ($i = 1; $i <= 11; $i++) {
                    $stmtSave->bindValue(':jogador' . $i, $assigned[$i], PDO::PARAM_INT);
                }
                $stmtSave->bindValue(':capitao', $capitao, PDO::PARAM_INT);
                $stmtSave->bindValue(':penalti1', $pen1, PDO::PARAM_INT);
                $stmtSave->bindValue(':penalti2', $pen2, PDO::PARAM_INT);
                $stmtSave->bindValue(':penalti3', $pen3, PDO::PARAM_INT);
                $stmtSave->bindValue(':indisponiveis', $indispStr, PDO::PARAM_STR);
                
                $posPost = isset($_POST['posicao_jogador']) ? $_POST['posicao_jogador'] : [];
                $posStr = json_encode($posPost);
                $stmtSave->bindValue(':posicoesescolhidas', $posStr, PDO::PARAM_STR);
                
                $stmtSave->execute();

                // Se marcado para salvar em definitivo para o clube, atualizar a tabela escalacao
                if (isset($_POST['salvar_definitivo']) && $_POST['salvar_definitivo'] == 1) {
                    $updateFields = [];
                    for ($i = 1; $i <= 11; $i++) {
                        $updateFields[] = "Jogador{$i} = :jog{$i}";
                    }
                    $updateFields[] = "Capitao = :capitao";
                    $updateFields[] = "Penalti1 = :pen1";
                    $updateFields[] = "Penalti2 = :pen2";
                    $updateFields[] = "Penalti3 = :pen3";

                    $stmtUpdate = $sdb->prepare("UPDATE escalacao SET " . implode(', ', $updateFields) . " WHERE Clube = :clube");
                    for ($i = 1; $i <= 11; $i++) {
                        $stmtUpdate->bindValue(':jog' . $i, $assigned[$i], PDO::PARAM_INT);
                    }
                    $stmtUpdate->bindValue(':capitao', $capitao, PDO::PARAM_INT);
                    $stmtUpdate->bindValue(':pen1', $pen1, PDO::PARAM_INT);
                    $stmtUpdate->bindValue(':pen2', $pen2, PDO::PARAM_INT);
                    $stmtUpdate->bindValue(':pen3', $pen3, PDO::PARAM_INT);
                    $stmtUpdate->bindValue(':clube', $idTime, PDO::PARAM_INT);
                    $stmtUpdate->execute();
                }
            } else {
                // Construir e executar o UPDATE na tabela escalacao do SQLite
                $updateFields = [];
                for ($i = 1; $i <= 11; $i++) {
                    $updateFields[] = "Jogador{$i} = :jog{$i}";
                }
                $updateFields[] = "Capitao = :capitao";
                $updateFields[] = "Penalti1 = :pen1";
                $updateFields[] = "Penalti2 = :pen2";
                $updateFields[] = "Penalti3 = :pen3";

                $stmtUpdate = $sdb->prepare("UPDATE escalacao SET " . implode(', ', $updateFields) . " WHERE Clube = :clube");
                for ($i = 1; $i <= 11; $i++) {
                    $stmtUpdate->bindValue(':jog' . $i, $assigned[$i], PDO::PARAM_INT);
                }
                $stmtUpdate->bindValue(':capitao', $capitao, PDO::PARAM_INT);
                $stmtUpdate->bindValue(':pen1', $pen1, PDO::PARAM_INT);
                $stmtUpdate->bindValue(':pen2', $pen2, PDO::PARAM_INT);
                $stmtUpdate->bindValue(':pen3', $pen3, PDO::PARAM_INT);
                $stmtUpdate->bindValue(':clube', $idTime, PDO::PARAM_INT);
                $stmtUpdate->execute();
            }
            
            $mensagem = "<div style='color: #10b981; margin-bottom: 15px;'>Escalação salva com sucesso!</div>";
        } catch (Exception $e) {
            $mensagem = "<div style='color: #ef4444; margin-bottom: 15px;'>Erro ao salvar: " . $e->getMessage() . "</div>";
        }
    }
}

// 1. Obter os jogadores vinculados a este Clube pela tabela elenco
$stmtElencoIds = $sdb->prepare("SELECT * FROM elenco WHERE Clube = :clube LIMIT 1");
$stmtElencoIds->bindParam(':clube', $idTime);
$stmtElencoIds->execute();
$elencoRow = $stmtElencoIds->fetch(PDO::FETCH_ASSOC);

$playerIds = [];
if ($elencoRow) {
    for ($i = 1; $i <= 23; $i++) {
        if (!empty($elencoRow['Jogador' . $i])) {
            $playerIds[] = (int)$elencoRow['Jogador' . $i];
        }
    }
}

// 2. Obter titulares e capitão atuais pela tabela escalacao ou escalacao_jogo
$escalacaoRow = null;
$indisponiveisManual = [];
$posicoesEscolhidas = [];

if ($idJogo > 0) {
    $stmtEscalacaoJogo = $sdb->prepare("SELECT * FROM escalacao_jogo WHERE Jogo = :jogo AND Clube = :clube LIMIT 1");
    $stmtEscalacaoJogo->bindParam(':jogo', $idJogo, PDO::PARAM_INT);
    $stmtEscalacaoJogo->bindParam(':clube', $idTime, PDO::PARAM_INT);
    $stmtEscalacaoJogo->execute();
    $escalacaoRow = $stmtEscalacaoJogo->fetch(PDO::FETCH_ASSOC);
    if ($escalacaoRow) {
        $indisponiveisStr = isset($escalacaoRow['Indisponiveis']) ? trim($escalacaoRow['Indisponiveis']) : '';
        if ($indisponiveisStr !== '') {
            $indisponiveisManual = array_map('intval', explode(',', $indisponiveisStr));
        }
        $posStr = isset($escalacaoRow['PosicoesEscolhidas']) ? trim($escalacaoRow['PosicoesEscolhidas']) : '';
        if ($posStr !== '') {
            $posicoesEscolhidas = json_decode($posStr, true);
            if (!is_array($posicoesEscolhidas)) {
                $posicoesEscolhidas = [];
            }
        }
    } else {
        // Se ainda não tem escalação própria para este jogo, busca a escalação padrão do clube
        $stmtEscalacao = $sdb->prepare("SELECT * FROM escalacao WHERE Clube = :clube LIMIT 1");
        $stmtEscalacao->bindParam(':clube', $idTime);
        $stmtEscalacao->execute();
        $escalacaoRow = $stmtEscalacao->fetch(PDO::FETCH_ASSOC);
        
        // E nenhum jogador começa marcado como indisponível para este novo jogo
        $indisponiveisManual = [];
        $posicoesEscolhidas = [];
    }
} else {
    // Caso seja escalação global
    $stmtEscalacao = $sdb->prepare("SELECT * FROM escalacao WHERE Clube = :clube LIMIT 1");
    $stmtEscalacao->bindParam(':clube', $idTime);
    $stmtEscalacao->execute();
    $escalacaoRow = $stmtEscalacao->fetch(PDO::FETCH_ASSOC);
    
    $indisponiveisKey = 'indisponiveis_' . $idCompeticao . '_' . $idTime;
    $indisponiveisManual = isset($_SESSION[$indisponiveisKey]) ? $_SESSION[$indisponiveisKey] : [];
}

$titularesIds = [];
$capitaoId = 0;
if ($escalacaoRow) {
    for ($i = 1; $i <= 11; $i++) {
        if (!empty($escalacaoRow['Jogador' . $i])) {
            $titularesIds[] = (int)$escalacaoRow['Jogador' . $i];
        }
    }
    $capitaoId = (int)$escalacaoRow['Capitao'];
}

// 3. Obter status de lesão (global na tabela jogador) e suspensão (tabela competicao_suspensos) do MariaDB
$statusMaria = [];
if (!empty($playerIds)) {
    $validPlayerIds = array_filter(array_map('intval', $playerIds), function($id) { return $id > 0; });
    if (!empty($validPlayerIds)) {
        $inClause = implode(',', array_unique($validPlayerIds));
        // Garantir que a coluna lesionado_ate exista no MariaDB competicao_suspensos
        try {
            $db->exec("ALTER TABLE competicao_suspensos ADD COLUMN lesionado_ate DATE DEFAULT NULL");
        } catch (Exception $e) {}

        try {
            $queryStatus = "SELECT val.ID, 
                                   IF((cs.lesionado_ate IS NOT NULL AND cs.lesionado_ate >= CURDATE()) OR (j.lesionado_ate IS NOT NULL AND j.lesionado_ate >= CURDATE()), 1, 0) as lesionado,
                                   COALESCE(cs.suspenso, 0) as suspenso 
                            FROM (
                                SELECT ID FROM jogador WHERE ID IN ($inClause)
                                UNION
                                SELECT id_jogador AS ID FROM competicao_suspensos WHERE id_competicao = :comp AND id_jogador IN ($inClause)
                            ) val
                            LEFT JOIN jogador j ON val.ID = j.ID
                            LEFT JOIN competicao_suspensos cs ON val.ID = cs.id_jogador AND cs.id_competicao = :comp2";
            $stmtStatus = $db->prepare($queryStatus);
            $stmtStatus->bindParam(':comp', $idCompeticao, PDO::PARAM_INT);
            $stmtStatus->bindParam(':comp2', $idCompeticao, PDO::PARAM_INT);
            $stmtStatus->execute();
            while ($row = $stmtStatus->fetch(PDO::FETCH_ASSOC)) {
                $statusMaria[(int)$row['ID']] = [
                    'lesionado' => (int)$row['lesionado'],
                    'suspenso' => (int)$row['suspenso']
                ];
            }
        } catch (Exception $e) {
            error_log("Erro ao consultar status de desfalques no MariaDB: " . $e->getMessage());
        }
    }
}

// 4. Buscar os dados dos jogadores do elenco no SQLite
$elenco = [];
if (!empty($playerIds)) {
    $inClause = implode(',', $playerIds);
    // Busca Nome, Nivel e StringPosicoes do MariaDB (posicaojogador não existe; posições ficam em StringPosicoes)
    $queryElenco = "SELECT ID, Nome, Nivel, StringPosicoes FROM jogador WHERE ID IN ({$inClause})";
    $stmtElenco = $db->prepare($queryElenco);
    $stmtElenco->execute();
    $elencoRaw = $stmtElenco->fetchAll(PDO::FETCH_ASSOC);

    // Mapeamento de índice (1-based) de StringPosicoes → sigla
    $posMapIdx = [1=>'G',2=>'LD',3=>'LE',4=>'Z',5=>'AD',6=>'AE',7=>'V',8=>'MD',9=>'ME',10=>'MC',11=>'PD',12=>'PE',13=>'MA',14=>'Am',15=>'Aa'];
    foreach ($elencoRaw as &$row) {
        $sp = $row['StringPosicoes'] ?? '';
        $posicoesDisponiveis = [];
        for ($pi = 1; $pi <= 15; $pi++) {
            if (isset($sp[$pi-1]) && $sp[$pi-1] === '1') {
                $posicoesDisponiveis[] = $posMapIdx[$pi];
            }
        }
        if (empty($posicoesDisponiveis)) $posicoesDisponiveis = ['A'];
        $row['PosicaoBase'] = $posicoesDisponiveis[0];
        $row['PosicoesDisponiveis'] = $posicoesDisponiveis;
    }
    unset($row);

    // Indisponíveis manuais: vêm do POST ou de cookie de sessão por time
    if (!isset($indisponiveisManual) || empty($indisponiveisManual)) {
        $indisponiveisKey = 'indisponiveis_' . $idCompeticao . '_' . $idTime;
        $indisponiveisManual = isset($_SESSION[$indisponiveisKey]) ? $_SESSION[$indisponiveisKey] : [];
    }
    // Posições escolhidas manualmente
    if (!isset($posicoesEscolhidas) || empty($posicoesEscolhidas)) {
        $posicoesEscolhidasKey = 'posicoes_' . $idCompeticao . '_' . $idTime;
        $posicoesEscolhidas = isset($_SESSION[$posicoesEscolhidasKey]) ? $_SESSION[$posicoesEscolhidasKey] : [];
    }

    foreach ($elencoRaw as $row) {
        $pId = (int)$row['ID'];
        $row['Titular'] = in_array($pId, $titularesIds) ? 1 : 0;
        $row['Capitao'] = ($pId === $capitaoId) ? 1 : 0;
        $row['Lesionado'] = isset($statusMaria[$pId]) ? $statusMaria[$pId]['lesionado'] : 0;
        $row['Suspenso'] = isset($statusMaria[$pId]) ? $statusMaria[$pId]['suspenso'] : 0;
        $row['IndisponiveisManual'] = in_array($pId, $indisponiveisManual) ? 1 : 0;
        // Posição escolhida pelo usuário para este jogador
        $row['PosicaoEscolhida'] = isset($posicoesEscolhidas[$pId]) ? $posicoesEscolhidas[$pId] : $row['PosicaoBase'];
        $elenco[] = $row;
    }

    // Ordenar elenco: Titular primeiro, depois por posição (GK -> DF -> MF -> FW), depois por Nível decrescente
    usort($elenco, function($a, $b) {
        if ($a['Titular'] != $b['Titular']) {
            return $b['Titular'] - $a['Titular'];
        }

        $posOrder = function($pos) {
            if ($pos === 'G') return 1; // GK
            if (in_array($pos, ['Z', 'LD', 'LE', 'AD', 'AE'])) return 2; // DF
            if (in_array($pos, ['V', 'MC', 'MD', 'ME', 'MA'])) return 3; // MF
            return 4; // FW
        };

        $pA = $posOrder($a['PosicaoEscolhida']);
        $pB = $posOrder($b['PosicaoEscolhida']);

        if ($pA != $pB) {
            return $pA - $pB;
        }

        return $b['Nivel'] - $a['Nivel'];
    });
}

$page_title = "Escalação Pré-Jogo - " . $clube['Nome'];
$css_filename = "home_redesign";
$aux_css = "lista_jogos_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
require_once $_SERVER['DOCUMENT_ROOT'] . "/elements/header.php";
?>
<link rel="stylesheet" href="/css/escalacao_jogo.css?versao=<?php echo $css_versao; ?>">

<main class="propostas-container" style="padding-top: 80px; padding-bottom: 60px;">
<div class="propostas-card" style="padding: 30px; border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: left;">
    <h2 class="propostas-title" style="margin-bottom: 5px; font-family: 'Outfit', sans-serif; text-align: left; font-size: 1.6rem; color: #1e293b;">⚽ Escalação Pré-Jogo: <?php echo htmlspecialchars($clube['Nome']); ?></h2>
    <p style="color: #64748b; margin-bottom: 25px;">Selecione os 11 titulares, o capitão e as posições antes da simulação.</p>
    
    <?php echo $mensagem; ?>

    <form method="POST">
        <div class="table-container" style="background: rgba(255, 255, 255, 0.9); border: 1px solid rgba(0,0,0,0.08); border-radius: 14px; padding: 20px; margin-bottom: 20px; overflow-x: auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <h3 style="margin: 0; color: #1e293b; font-family: 'Outfit', sans-serif;">Elenco do Clube</h3>
                <span id="contador-titulares" style="background: rgba(2,132,199,0.08); border: 1px solid rgba(2, 132, 199, 0.15); color: #0284c7; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">0/11 titulares selecionados</span>
            </div>
            <table class="escalacao-table">
                <thead>
                    <tr style="border-bottom: 1px solid rgba(0,0,0,0.08); color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        <th style="padding: 8px 10px;">Titular</th>
                        <th style="padding: 8px 10px;">Cap.</th>
                        <th style="padding: 8px 10px; min-width: 160px;">Jogador</th>
                        <th style="padding: 8px 10px; min-width: 120px;">Posição</th>
                        <th style="padding: 8px 10px;">Nível</th>
                        <th style="padding: 8px 10px;">Indisp.</th>
                        <th style="padding: 8px 10px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($elenco as $j): 
                        $isDesfalqueAuto = ($j['Suspenso'] == 1 || $j['Lesionado'] == 1);
                        $isIndisp = $j['IndisponiveisManual'] == 1;
                        $isBloqueado = $isDesfalqueAuto || $isIndisp;
                        $statusBadge = "";
                        if ($j['Suspenso'] == 1) {
                            $statusBadge .= "<span style='background: #ef4444; color: #fff; padding: 2px 7px; border-radius: 4px; font-size: 0.72rem;'>SUSPENSO</span>";
                        }
                        if ($j['Lesionado'] == 1) {
                            $statusBadge .= "<span style='background: #f59e0b; color: #fff; padding: 2px 7px; border-radius: 4px; font-size: 0.72rem;'>LESIONADO</span>";
                        }
                        if (!$isDesfalqueAuto && !$isIndisp) {
                            $statusBadge = "<span style='background: rgba(16,185,129,0.1); color: #047857; border: 1px solid rgba(16,185,129,0.2); padding: 2px 7px; border-radius: 4px; font-size: 0.72rem;'>DISPONÍVEL</span>";
                        }
                        if ($isIndisp && !$isDesfalqueAuto) {
                            $statusBadge = "<span style='background: rgba(100,116,139,0.1); color: #475569; border: 1px solid rgba(100,116,139,0.2); padding: 2px 7px; border-radius: 4px; font-size: 0.72rem;'>INDISP.</span>";
                        }
                        $rowOpacity = $isBloqueado ? 'opacity:0.5;' : '';
                        
                        $posBase = $j['PosicaoEscolhida'];
                        $posClass = "pos-fw"; // default Atacante
                        if ($posBase === 'G') {
                            $posClass = "pos-gk";
                        } elseif (in_array($posBase, ['Z', 'LD', 'LE', 'AD', 'AE'])) {
                            $posClass = "pos-df";
                        } elseif (in_array($posBase, ['V', 'MC', 'MD', 'ME', 'MA'])) {
                            $posClass = "pos-mf";
                        }
                    ?>
                        <tr class="<?php echo $posClass; ?>" style="border-bottom: 1px solid rgba(0,0,0,0.05); <?php echo $rowOpacity; ?>">
                            <td data-label="Titular" style="padding: 8px 10px; text-align: center;">
                                <span class="cell-value">
                                    <input type="checkbox" name="titulares[]" value="<?php echo $j['ID']; ?>"
                                        <?php echo ($j['Titular'] == 1 && !$isBloqueado) ? 'checked' : ''; ?>
                                        <?php echo $isBloqueado ? 'disabled' : ''; ?>
                                        class="chk-titular" data-desfalque="<?php echo $isBloqueado ? '1' : '0'; ?>">
                                </span>
                            </td>
                            <td data-label="Capitão" style="padding: 8px 10px; text-align: center;">
                                <span class="cell-value">
                                    <input type="radio" name="capitao" value="<?php echo $j['ID']; ?>"
                                        <?php echo ($j['Capitao'] == 1 && !$isBloqueado) ? 'checked' : ''; ?>
                                        <?php echo $isBloqueado ? 'disabled' : ''; ?>>
                                </span>
                            </td>
                            <td data-label="Jogador" class="cell-jogador" style="padding: 8px 10px; font-weight: 600; color: #1e293b;">
                                <span class="jogador-name"><?php echo htmlspecialchars($j['Nome']); ?></span>
                            </td>
                            <td data-label="Posição" style="padding: 8px 10px;">
                                <span class="cell-value">
                                    <?php if (!$isBloqueado && count($j['PosicoesDisponiveis']) > 1): ?>
                                        <select name="posicao_jogador[<?php echo $j['ID']; ?>]"
                                            style="background: #ffffff; color: #1e293b; border: 1px solid rgba(0,0,0,0.15); border-radius: 6px; padding: 4px 8px; font-size: 0.82rem; width: 100%;">
                                            <?php foreach ($j['PosicoesDisponiveis'] as $pos): ?>
                                                <option value="<?php echo $pos; ?>" <?php echo ($j['PosicaoEscolhida'] === $pos) ? 'selected' : ''; ?>>
                                                    <?php echo $pos; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <span style="color: #64748b; font-size: 0.85rem;"><?php echo htmlspecialchars($j['PosicaoBase']); ?></span>
                                        <input type="hidden" name="posicao_jogador[<?php echo $j['ID']; ?>]" value="<?php echo htmlspecialchars($j['PosicaoBase']); ?>">
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td data-label="Nível" style="padding: 8px 10px; color: #b45309; font-weight: bold; text-align: center;">
                                <span class="cell-value">
                                    <?php echo $j['Nivel']; ?>
                                </span>
                            </td>
                            <td data-label="Indisp." style="padding: 8px 10px; text-align: center;">
                                <span class="cell-value">
                                    <?php if (!$isDesfalqueAuto): ?>
                                        <input type="checkbox" name="indisponiveis[]" value="<?php echo $j['ID']; ?>"
                                            <?php echo $isIndisp ? 'checked' : ''; ?>
                                            class="chk-indisp" title="Marcar como indisponível para este jogo"
                                            style="cursor:pointer; accent-color: #ef4444;">
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">—</span>
                                        <input type="checkbox" style="display:none;" class="chk-indisp">
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td data-label="Status" class="cell-status" style="padding: 8px 10px;">
                                <span class="cell-value">
                                    <?php echo $statusBadge; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($idJogo > 0): ?>
            <div style="margin-bottom: 20px; background: rgba(2, 132, 199, 0.05); padding: 12px 18px; border-radius: 8px; border: 1px solid rgba(2, 132, 199, 0.15); width: fit-content;">
                <label style="display: inline-flex; align-items: center; gap: 8px; color: #0369a1; font-weight: 600; font-size: 0.95rem; cursor: pointer; user-select: none;">
                    <input type="checkbox" name="salvar_definitivo" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0284c7;">
                    Salvar esta escalação também como PADRÃO (definitiva) para o clube na competição
                </label>
            </div>
        <?php endif; ?>

        <div class="escalacao-actions" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-top: 4px;">
            <button type="submit" name="salvar_escalacao"
                style="width: fit-content; white-space: nowrap; background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff; border: none; padding: 12px 28px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.95rem; letter-spacing: 0.3px; box-shadow: 0 4px 12px rgba(2,132,199,0.25);">
                💾 Salvar Escalação
            </button>
            <a href="/competicoes/listajogos.php?id=<?php echo $idCompeticao; ?>"
                style="display: inline-block; width: fit-content; white-space: nowrap; padding: 12px 20px; background: rgba(0,0,0,0.03); color: #475569; border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: all 0.2s ease;"
                onmouseover="this.style.background='rgba(0,0,0,0.06)'" onmouseout="this.style.background='rgba(0,0,0,0.03)'">
                ← Voltar para Jogos
            </a>
            <a href="competitionstatus.php?id=<?php echo $idCompeticao; ?>"
                style="display: inline-block; width: fit-content; white-space: nowrap; padding: 12px 20px; background: rgba(0,0,0,0.03); color: #475569; border: 1px solid rgba(0,0,0,0.08); border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: all 0.2s ease;"
                onmouseover="this.style.background='rgba(0,0,0,0.06)'" onmouseout="this.style.background='rgba(0,0,0,0.03)'">
                ← Voltar para a Competição
            </a>
        </div>
    </form>
</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function atualizarLimite() {
        var marcados = document.querySelectorAll('.chk-titular:checked').length;
        document.querySelectorAll('.chk-titular').forEach(function(chk) {
            if (chk.dataset.desfalque === '1') return;
            chk.disabled = (!chk.checked && marcados >= 11);
        });
        var contador = document.getElementById('contador-titulares');
        if (contador) {
            contador.textContent = marcados + '/11 titulares selecionados';
            contador.style.background = marcados === 11 ? 'rgba(16,185,129,0.15)' : 'rgba(2,132,199,0.08)';
            contador.style.borderColor = marcados === 11 ? 'rgba(16,185,129,0.25)' : 'rgba(2,132,199,0.15)';
            contador.style.color = marcados === 11 ? '#047857' : '#0284c7';
        }
    }
    document.querySelectorAll('.chk-titular').forEach(function(chk) {
        chk.addEventListener('change', atualizarLimite);
    });

    // Indisponível manual: ao marcar, desabilita checkbox de titular e capitão da linha
    document.querySelectorAll('.chk-indisp').forEach(function(chk) {
        chk.addEventListener('change', function() {
            var row = chk.closest('tr');
            var chkTitular = row.querySelector('.chk-titular');
            var radioCapitao = row.querySelector('input[type=radio]');
            if (chk.checked) {
                if (chkTitular && chkTitular.checked) chkTitular.checked = false;
                if (chkTitular) { chkTitular.disabled = true; chkTitular.dataset.desfalque = '1'; }
                if (radioCapitao) radioCapitao.disabled = true;
                row.style.opacity = '0.5';
            } else {
                if (chkTitular) { chkTitular.disabled = false; chkTitular.dataset.desfalque = '0'; }
                if (radioCapitao) radioCapitao.disabled = false;
                row.style.opacity = '1';
            }
            atualizarLimite();
        });
    });

    atualizarLimite();
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . "/elements/footer.php"; ?>
