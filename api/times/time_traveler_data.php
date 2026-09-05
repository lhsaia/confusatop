<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado. Faça login no sistema.']);
    exit;
}

$idTime = isset($_GET['team']) ? (int)$_GET['team'] : 0;
$dataAlvo = isset($_GET['date']) ? trim($_GET['date']) : '';

if ($idTime <= 0) {
    echo json_encode(['success' => false, 'error' => 'Selecione um clube válido.']);
    exit;
}

if (empty($dataAlvo) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataAlvo)) {
    echo json_encode(['success' => false, 'error' => 'Data inválida. Forneça uma data no formato YYYY-MM-DD.']);
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/tecnico.php");

$database = new Database();
$db = $database->getConnection();

$timeObj = new Time($db);
$jogadorObj = new Jogador($db);
$tecnicoObj = new Tecnico($db);

// 1. Obter informações básicas do clube
$infoClube = $timeObj->readInfo($idTime);
if (!$infoClube || empty($infoClube['Nome'])) {
    echo json_encode(['success' => false, 'error' => 'Clube não encontrado.']);
    exit;
}

// 2. Coletar todos os jogadores candidatos (jogadores atualmente no clube + jogadores que tiveram transferência no clube)
$candidatosIds = [];

// 2.1. Elenco atual do clube
$stmtAtual = $db->prepare("SELECT jogador FROM contratos_jogador WHERE clube = ? AND tipoContrato = 0");
$stmtAtual->execute([$idTime]);
while ($row = $stmtAtual->fetch(PDO::FETCH_ASSOC)) {
    $candidatosIds[(int)$row['jogador']] = true;
}

// 2.2. Jogadores envolvidos em transferências com o clube (origem ou destino)
$stmtTransf = $db->prepare("
    SELECT DISTINCT jogador 
    FROM transferencias 
    WHERE (clubeOrigem = ? OR clubeDestino = ?) 
      AND status_execucao = 1
");
$stmtTransf->execute([$idTime, $idTime]);
while ($row = $stmtTransf->fetch(PDO::FETCH_ASSOC)) {
    $candidatosIds[(int)$row['jogador']] = true;
}

$candidatosList = array_keys($candidatosIds);

// 3. Para cada candidato, determinar seu clube na dataAlvo e o histórico
$elencoHistorico = [];
$saidasDepois = [];
$chegadasDepois = [];

if (!empty($candidatosList)) {
    $inPlaceholders = implode(',', array_fill(0, count($candidatosList), '?'));

    // Carregar dados cadastrais de todos os candidatos
    $queryJogadores = "
        SELECT 
            j.ID as idJogador,
            j.Nome as nomeJogador,
            j.Nascimento,
            j.Nivel,
            j.StringPosicoes,
            j.foto,
            j.valor,
            j.Sexo as sexoJogador,
            j.disponibilidade,
            j.data_falecimento,
            p.id as idPais,
            p.nome as nomePais,
            p.bandeira as bandeiraPais,
            p.sigla as siglaPais,
            cj.clube as clubeAtualId,
            cAtual.Nome as clubeAtualNome,
            cAtual.Escudo as clubeAtualEscudo,
            cj.numeroCamisa as numeroCamisaAtual
        FROM jogador j
        LEFT JOIN paises p ON j.Pais = p.id
        LEFT JOIN contratos_jogador cj ON (cj.jogador = j.ID AND cj.tipoContrato = 0)
        LEFT JOIN clube cAtual ON cj.clube = cAtual.ID
        WHERE j.ID IN ($inPlaceholders)
    ";
    $stmtJ = $db->prepare($queryJogadores);
    $stmtJ->execute($candidatosList);
    $jogadoresMap = [];
    while ($row = $stmtJ->fetch(PDO::FETCH_ASSOC)) {
        $jogadoresMap[(int)$row['idJogador']] = $row;
    }

    // Carregar todas as transferências desses jogadores
    $queryAllTransf = "
        SELECT 
            t.ID as idTransferencia,
            t.jogador as idJogador,
            t.clubeOrigem,
            t.clubeDestino,
            t.valor,
            t.dataConclusao,
            t.data as dataCriacao,
            t.emprestimo,
            cOrigem.Nome as nomeOrigem,
            cOrigem.Escudo as escudoOrigem,
            cDestino.Nome as nomeDestino,
            cDestino.Escudo as escudoDestino
        FROM transferencias t
        LEFT JOIN clube cOrigem ON t.clubeOrigem = cOrigem.ID
        LEFT JOIN clube cDestino ON t.clubeDestino = cDestino.ID
        WHERE t.jogador IN ($inPlaceholders)
          AND t.status_execucao = 1
        ORDER BY COALESCE(NULLIF(t.dataConclusao, '0000-00-00 00:00:00'), t.data) ASC, t.ID ASC
    ";
    $stmtT = $db->prepare($queryAllTransf);
    $stmtT->execute($candidatosList);
    $transfPorJogador = [];
    while ($t = $stmtT->fetch(PDO::FETCH_ASSOC)) {
        $jId = (int)$t['idJogador'];
        if (!isset($transfPorJogador[$jId])) {
            $transfPorJogador[$jId] = [];
        }
        $transfPorJogador[$jId][] = $t;
    }

    $dateTimeAlvo = $dataAlvo . ' 23:59:59';

    foreach ($jogadoresMap as $jId => $jData) {
        $transfs = $transfPorJogador[$jId] ?? [];
        $clubeAtual = (int)($jData['clubeAtualId'] ?? 0);

        // Classificar transferências em: antes_ou_na_data e depois_da_data
        $transfAteData = [];
        $transfPosData = [];

        foreach ($transfs as $t) {
            $dt = !empty($t['dataConclusao']) && $t['dataConclusao'] !== '0000-00-00 00:00:00' ? $t['dataConclusao'] : $t['dataCriacao'];
            if ($dt <= $dateTimeAlvo) {
                $transfAteData[] = $t;
            } else {
                $transfPosData[] = $t;
            }
        }

        // Determinar o clube do jogador na dataAlvo
        $estavaNoClube = false;

        if (!empty($transfAteData)) {
            // A última transferência até a data define o clube onde ele estava
            $ultimaAte = end($transfAteData);
            if ((int)$ultimaAte['clubeDestino'] === $idTime) {
                $estavaNoClube = true;
            }
        } else {
            // Nenhuma transferência até a data. Se ele não teve transferência posterior de ENTRADA no clube,
            // e está no clube atualmente (ou teve sua primeira transferência saindo do clube após a data), ele foi formado/estava no clube.
            if (!empty($transfPosData)) {
                $primeiraPos = $transfPosData[0];
                if ((int)$primeiraPos['clubeOrigem'] === $idTime) {
                    $estavaNoClube = true;
                }
            } else {
                // Nunca teve nenhuma transferência no sistema: se está no clube hoje, já estava naquela data
                if ($clubeAtual === $idTime) {
                    $estavaNoClube = true;
                }
            }
        }

        // Se estava no clube na data
        if ($estavaNoClube) {
            $permaneceHoje = ($clubeAtual === $idTime);
            
            // Calcular idade na data selecionada
            $idadeNaData = 0;
            if (!empty($jData['Nascimento'])) {
                $dtNasc = new DateTime($jData['Nascimento']);
                $dtRef = new DateTime($dataAlvo);
                $diff = $dtRef->diff($dtNasc);
                $idadeNaData = $diff->y;
            }

            // Posição amigável
            $posicoesStr = $jData['StringPosicoes'] ?? '';
            $posicaoNome = $jogadorObj->listaPosicoes($posicoesStr);
            $posicaoSigla = !empty($posicaoNome) ? explode('-', $posicaoNome)[0] : 'JOG';

            // Setor tático
            $setor = 'Atacante';
            if (strpos($posicaoSigla, 'GK') !== false || (strlen($posicoesStr) > 0 && $posicoesStr[0] === '1')) {
                $setor = 'Goleiro';
            } elseif (strpos($posicaoSigla, 'DF') !== false || strpos($posicaoSigla, 'ZAG') !== false || strpos($posicaoSigla, 'LAT') !== false || strpos($posicaoSigla, 'SW') !== false || strpos($posicaoSigla, 'LB') !== false || strpos($posicaoSigla, 'RB') !== false || strpos($posicaoSigla, 'CB') !== false) {
                $setor = 'Defensor';
            } elseif (strpos($posicaoSigla, 'VOL') !== false || strpos($posicaoSigla, 'MEI') !== false || strpos($posicaoSigla, 'MC') !== false || strpos($posicaoSigla, 'ML') !== false || strpos($posicaoSigla, 'MR') !== false || strpos($posicaoSigla, 'DM') !== false || strpos($posicaoSigla, 'AM') !== false) {
                $setor = 'Meio-campista';
            }

            // Destino atual / Onde está hoje
            $statusAtualTexto = 'Permanece no clube';
            $clubeAtualNome = $jData['clubeAtualNome'] ?: 'Sem clube / Aposentado';
            $clubeAtualEscudo = $jData['clubeAtualEscudo'] ?: '';
            
            $saidaInfo = null;
            if (!$permaneceHoje) {
                // Procurar primeira saída do time após a dataAlvo
                foreach ($transfPosData as $tPos) {
                    if ((int)$tPos['clubeOrigem'] === $idTime) {
                        $saidaInfo = [
                            'data' => !empty($tPos['dataConclusao']) && $tPos['dataConclusao'] !== '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($tPos['dataConclusao'])) : date('d/m/Y', strtotime($tPos['dataCriacao'])),
                            'destinoNome' => $tPos['nomeDestino'] ?: 'Sem clube',
                            'destinoEscudo' => $tPos['escudoDestino'] ?: '',
                            'valor' => $tPos['valor']
                        ];
                        break;
                    }
                }

                if ((int)$jData['disponibilidade'] < 0 || !empty($jData['data_falecimento'])) {
                    $statusAtualTexto = 'Aposentado';
                } elseif ($clubeAtual > 0) {
                    $statusAtualTexto = 'Hoje no ' . $clubeAtualNome;
                } else {
                    $statusAtualTexto = 'Sem clube';
                }
            }

            $elencoHistorico[] = [
                'id' => $jId,
                'nome' => $jData['nomeJogador'],
                'nascimento' => $jData['Nascimento'],
                'idadeNaEpoca' => $idadeNaData,
                'nivel' => (int)$jData['Nivel'],
                'posicoes' => $posicaoNome,
                'posicaoSigla' => $posicaoSigla,
                'setor' => $setor,
                'stringPosicoes' => $posicoesStr,
                'foto' => $jData['foto'] ?: 'placeholder.png',
                'paisNome' => $jData['nomePais'] ?: '',
                'bandeiraPais' => $jData['bandeiraPais'] ?: '',
                'permaneceHoje' => $permaneceHoje,
                'statusAtualTexto' => $statusAtualTexto,
                'clubeAtualId' => $clubeAtual,
                'clubeAtualNome' => $clubeAtualNome,
                'clubeAtualEscudo' => $clubeAtualEscudo,
                'saidaInfo' => $saidaInfo
            ];

            // Se saiu depois, adicionar à lista de saídas posteriores
            if (!$permaneceHoje && $saidaInfo) {
                $saidasDepois[] = [
                    'idJogador' => $jId,
                    'nome' => $jData['nomeJogador'],
                    'foto' => $jData['foto'] ?: 'placeholder.png',
                    'data' => $saidaInfo['data'],
                    'destinoNome' => $saidaInfo['destinoNome'],
                    'destinoEscudo' => $saidaInfo['destinoEscudo'],
                    'valor' => $saidaInfo['valor']
                ];
            }
        } else {
            // Não estava no clube na data: verificar se chegou ao clube após a data
            foreach ($transfPosData as $tPos) {
                if ((int)$tPos['clubeDestino'] === $idTime) {
                    $chegadasDepois[] = [
                        'idJogador' => $jId,
                        'nome' => $jData['nomeJogador'],
                        'foto' => $jData['foto'] ?: 'placeholder.png',
                        'data' => !empty($tPos['dataConclusao']) && $tPos['dataConclusao'] !== '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($tPos['dataConclusao'])) : date('d/m/Y', strtotime($tPos['dataCriacao'])),
                        'origemNome' => $tPos['nomeOrigem'] ?: 'Sem clube',
                        'origemEscudo' => $tPos['escudoOrigem'] ?: '',
                        'valor' => $tPos['valor']
                    ];
                    break;
                }
            }
        }
    }
}

// 4. Ordenar o elenco histórico: Goleiros primeiro, depois Defensores, Meias e Atacantes; por Nível DESC
$setorOrder = ['Goleiro' => 1, 'Defensor' => 2, 'Meio-campista' => 3, 'Atacante' => 4];
usort($elencoHistorico, function($a, $b) use ($setorOrder) {
    $orderA = $setorOrder[$a['setor']] ?? 5;
    $orderB = $setorOrder[$b['setor']] ?? 5;
    if ($orderA !== $orderB) {
        return $orderA - $orderB;
    }
    return $b['nivel'] - $a['nivel'];
});

// 5. Determinar o Técnico da época
$tecnicoEpoca = null;

// Buscar transferências de técnico envolvendo o time
$stmtTecTransf = $db->prepare("
    SELECT 
        tt.ID,
        tt.tecnico,
        tt.clubeOrigem,
        tt.clubeDestino,
        COALESCE(NULLIF(tt.dataConclusao, '0000-00-00 00:00:00'), tt.data) as dtTransf,
        t.Nome as nomeTecnico,
        t.Nascimento,
        t.Nivel,
        t.foto,
        p.nome as nomePais,
        p.bandeira as bandeiraPais
    FROM transferencias_tecnico tt
    JOIN tecnico t ON tt.tecnico = t.ID
    LEFT JOIN paises p ON t.Pais = p.id
    WHERE (tt.clubeOrigem = ? OR tt.clubeDestino = ?)
      AND tt.status_execucao = 1
    ORDER BY dtTransf ASC, tt.ID ASC
");
$stmtTecTransf->execute([$idTime, $idTime]);
$transfsTec = $stmtTecTransf->fetchAll(PDO::FETCH_ASSOC);

$dateTimeAlvo = $dataAlvo . ' 23:59:59';
$tecnicoCandidatoId = 0;

if (!empty($transfsTec)) {
    foreach ($transfsTec as $tt) {
        if ($tt['dtTransf'] <= $dateTimeAlvo) {
            if ((int)$tt['clubeDestino'] === $idTime) {
                $tecnicoCandidatoId = (int)$tt['tecnico'];
                $tecnicoEpoca = [
                    'id' => (int)$tt['tecnico'],
                    'nome' => $tt['nomeTecnico'],
                    'nivel' => (int)$tt['Nivel'],
                    'foto' => $tt['foto'] ?: 'placeholder.png',
                    'paisNome' => $tt['nomePais'] ?: '',
                    'bandeiraPais' => $tt['bandeiraPais'] ?: ''
                ];
            } else if ((int)$tt['clubeOrigem'] === $idTime) {
                $tecnicoCandidatoId = 0;
                $tecnicoEpoca = null;
            }
        }
    }
}

// Se não encontrou por transferências passadas, verificar técnico com contrato atual se não teve transferência posterior de entrada
if (!$tecnicoEpoca) {
    $stmtTecAtual = $db->prepare("
        SELECT 
            t.ID as idTecnico,
            t.Nome as nomeTecnico,
            t.Nascimento,
            t.Nivel,
            t.foto,
            p.nome as nomePais,
            p.bandeira as bandeiraPais
        FROM contratos_tecnico ct
        JOIN tecnico t ON ct.tecnico = t.ID
        LEFT JOIN paises p ON t.Pais = p.id
        WHERE ct.clube = ?
        LIMIT 1
    ");
    $stmtTecAtual->execute([$idTime]);
    $tecAtual = $stmtTecAtual->fetch(PDO::FETCH_ASSOC);
    if ($tecAtual) {
        $stmtCheck = $db->prepare("
            SELECT ID FROM transferencias_tecnico 
            WHERE tecnico = ? AND clubeDestino = ? AND status_execucao = 1 
              AND COALESCE(NULLIF(dataConclusao, '0000-00-00 00:00:00'), data) > ?
        ");
        $stmtCheck->execute([$tecAtual['idTecnico'], $idTime, $dateTimeAlvo]);
        if ($stmtCheck->rowCount() === 0) {
            $tecnicoEpoca = [
                'id' => (int)$tecAtual['idTecnico'],
                'nome' => $tecAtual['nomeTecnico'],
                'nivel' => (int)$tecAtual['Nivel'],
                'foto' => $tecAtual['foto'] ?: '',
                'paisNome' => $tecAtual['nomePais'] ?: '',
                'bandeiraPais' => $tecAtual['bandeiraPais'] ?: ''
            ];
        }
    }
}

// 6. Estatísticas do Elenco Histórico
$totalJogadores = count($elencoHistorico);
$somaNivel = 0;
$somaIdade = 0;
$qtdRemanescentes = 0;
$qtdExJogadores = 0;

foreach ($elencoHistorico as $j) {
    $somaNivel += $j['nivel'];
    $somaIdade += $j['idadeNaEpoca'];
    if ($j['permaneceHoje']) {
        $qtdRemanescentes++;
    } else {
        $qtdExJogadores++;
    }
}

$mediaNivel = $totalJogadores > 0 ? round($somaNivel / $totalJogadores, 1) : 0;
$mediaIdade = $totalJogadores > 0 ? round($somaIdade / $totalJogadores, 1) : 0;

// Ordenar as 11 maiores notas para média do 11 ideal
$niveisOrdenados = array_map(function($j) { return $j['nivel']; }, $elencoHistorico);
rsort($niveisOrdenados);
$top11 = array_slice($niveisOrdenados, 0, 11);
$mediaNivel11 = !empty($top11) ? round(array_sum($top11) / count($top11), 1) : 0;

echo json_encode([
    'success' => true,
    'clube' => [
        'id' => (int)$infoClube['id'],
        'nome' => $infoClube['Nome'],
        'sigla' => $infoClube['TresLetras'],
        'escudo' => $infoClube['Escudo'],
        'estadio' => $infoClube['Estadio'],
        'capacidade' => $infoClube['Capacidade'],
        'pais' => $infoClube['Pais'],
        'liga' => $infoClube['liga'] ?? '',
        'uniforme1' => $infoClube['Uniforme1'],
        'uniforme2' => $infoClube['Uniforme2'],
        'uni1cor1' => $infoClube['Uni1Cor1'],
        'uni1cor2' => $infoClube['Uni1Cor2']
    ],
    'dataAlvo' => $dataAlvo,
    'dataAlvoFormatada' => date('d/m/Y', strtotime($dataAlvo)),
    'estatisticas' => [
        'totalJogadores' => $totalJogadores,
        'remanescentes' => $qtdRemanescentes,
        'exJogadores' => $qtdExJogadores,
        'mediaNivel' => $mediaNivel,
        'mediaNivel11' => $mediaNivel11,
        'mediaIdade' => $mediaIdade
    ],
    'tecnico' => $tecnicoEpoca,
    'elenco' => $elencoHistorico,
    'timeline' => [
        'saidasDepois' => $saidasDepois,
        'chegadasDepois' => $chegadasDepois
    ]
], JSON_UNESCAPED_UNICODE);
