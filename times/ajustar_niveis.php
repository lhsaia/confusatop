<?php

if (!function_exists('calcularLimitesEfetivosNivel')) {
    /**
     * Calcula os limites mínimo e máximo de nível efetivo (Nivel + ModificadorNivel)
     * permitidos para um jogador conforme o tipo de ajuste aplicado.
     *
     * @param string $tipoAjuste 'nivel' ou 'modificador'
     * @param int    $modAtual   ModificadorNivel atual no contrato
     * @return array ['minEfetivo' => int, 'maxEfetivo' => int]
     */
    function calcularLimitesEfetivosNivel($tipoAjuste, $modAtual)
    {
        $modAtual = (int)$modAtual;
        if ($tipoAjuste === 'nivel') {
            $minBase = max(1, 1 - $modAtual);
            $maxBase = min(99, 99 - $modAtual);
            if ($minBase > $maxBase) {
                $minBase = 1;
                $maxBase = 99;
            }
            return [
                'minEfetivo' => $minBase + $modAtual,
                'maxEfetivo' => $maxBase + $modAtual,
            ];
        }

        return [
            'minEfetivo' => 1,
            'maxEfetivo' => 99,
        ];
    }
}

if (!function_exists('calcularAjusteGrupoNiveis')) {
    /**
     * Calcula os novos níveis efetivos, deltas aplicados e campos finais
     * para um grupo de atletas no modo 'delta' ou 'media_alvo'.
     *
     * @param array      $itens     Lista de itens elegíveis (passada por referência)
     * @param string     $modo      'delta' ou 'media_alvo'
     * @param int        $delta     Variação inteira para o modo 'delta'
     * @param float|null $mediaAlvo Média alvo desejada para o modo 'media_alvo'
     * @return void
     */
    function calcularAjusteGrupoNiveis(&$itens, $modo, $delta, $mediaAlvo)
    {
        $n = count($itens);
        if ($n === 0) {
            return;
        }

        if ($modo === 'delta') {
            $deltaInt = (int)$delta;
            foreach ($itens as $idx => $item) {
                $efetivoAtual = (int)$item['efetivoAtual'];
                $minEfetivo = (int)$item['minEfetivo'];
                $maxEfetivo = (int)$item['maxEfetivo'];

                if ($deltaInt === 0) {
                    $efetivoNovo = $efetivoAtual;
                    $atingiuLimite = false;
                } else {
                    $desejado = $efetivoAtual + $deltaInt;
                    $efetivoNovo = max($minEfetivo, min($maxEfetivo, $desejado));
                    $atingiuLimite = ($efetivoNovo !== $desejado);
                }

                $itens[$idx]['efetivoNovo'] = $efetivoNovo;
                $itens[$idx]['atingiuLimite'] = $atingiuLimite;
            }
        } elseif ($modo === 'media_alvo') {
            $somaAtual = 0;
            $somaMin = 0;
            $somaMax = 0;

            foreach ($itens as $idx => $item) {
                $efetivoAtual = (int)$item['efetivoAtual'];
                $itens[$idx]['efetivoNovo'] = $efetivoAtual;
                $itens[$idx]['atingiuLimite'] = false;
                $somaAtual += $efetivoAtual;
                $somaMin += (int)$item['minEfetivo'];
                $somaMax += (int)$item['maxEfetivo'];
            }

            if ($mediaAlvo !== null) {
                $somaAlvo = (int)round(((float)$mediaAlvo) * $n);
                $somaAlvo = max($somaMin, min($somaMax, $somaAlvo));
                $restante = $somaAlvo - $somaAtual;
                $maxIter = 200;

                while ($restante !== 0 && $maxIter-- > 0) {
                    $ativos = [];
                    foreach ($itens as $idx => $item) {
                        if ($restante > 0 && (int)$item['efetivoNovo'] < (int)$item['maxEfetivo']) {
                            $ativos[] = $idx;
                        } elseif ($restante < 0 && (int)$item['efetivoNovo'] > (int)$item['minEfetivo']) {
                            $ativos[] = $idx;
                        }
                    }

                    $qtdAtivos = count($ativos);
                    if ($qtdAtivos === 0) {
                        break;
                    }

                    if (abs($restante) >= $qtdAtivos) {
                        $passo = intdiv($restante, $qtdAtivos);
                        if ($passo === 0) {
                            $passo = ($restante > 0) ? 1 : -1;
                        }

                        $deslocamentoReal = 0;
                        foreach ($ativos as $idx) {
                            $antes = (int)$itens[$idx]['efetivoNovo'];
                            $minEf = (int)$itens[$idx]['minEfetivo'];
                            $maxEf = (int)$itens[$idx]['maxEfetivo'];
                            $depois = max($minEf, min($maxEf, $antes + $passo));
                            $itens[$idx]['efetivoNovo'] = $depois;
                            $deslocamentoReal += ($depois - $antes);
                        }

                        if ($deslocamentoReal === 0) {
                            break;
                        }
                        $restante -= $deslocamentoReal;
                    } else {
                        $direcao = ($restante > 0) ? 1 : -1;
                        usort($ativos, function ($idxA, $idxB) use ($itens, $direcao) {
                            $valA = (int)$itens[$idxA]['efetivoNovo'];
                            $valB = (int)$itens[$idxB]['efetivoNovo'];
                            if ($valA !== $valB) {
                                return ($direcao > 0) ? ($valA <=> $valB) : ($valB <=> $valA);
                            }
                            $idA = (int)$itens[$idxA]['idJogador'];
                            $idB = (int)$itens[$idxB]['idJogador'];
                            return ($idA <=> $idB);
                        });

                        $passosRestantes = abs($restante);
                        for ($k = 0; $k < $passosRestantes && $k < $qtdAtivos; $k++) {
                            $idx = $ativos[$k];
                            $itens[$idx]['efetivoNovo'] = (int)$itens[$idx]['efetivoNovo'] + $direcao;
                        }
                        $restante = 0;
                    }
                }
            }

            foreach ($itens as $idx => $item) {
                $efNovo = (int)$itens[$idx]['efetivoNovo'];
                $efAtual = (int)$item['efetivoAtual'];
                $minEf = (int)$item['minEfetivo'];
                $maxEf = (int)$item['maxEfetivo'];
                $itens[$idx]['atingiuLimite'] = (($efNovo === $maxEf || $efNovo === $minEf) && $efNovo !== $efAtual);
            }
        }

        foreach ($itens as $idx => $item) {
            $efNovo = isset($itens[$idx]['efetivoNovo']) ? (int)$itens[$idx]['efetivoNovo'] : (int)$item['efetivoAtual'];
            $efAtual = (int)$item['efetivoAtual'];
            $itens[$idx]['efetivoNovo'] = $efNovo;
            $itens[$idx]['deltaAplicado'] = $efNovo - $efAtual;

            if ($item['tipoAjuste'] === 'nivel') {
                $itens[$idx]['novoNivelBase'] = $efNovo - (int)$item['modAtual'];
                $itens[$idx]['novoMod'] = (int)$item['modAtual'];
            } else {
                $itens[$idx]['novoNivelBase'] = (int)$item['nivelBase'];
                $itens[$idx]['novoMod'] = $efNovo - (int)$item['nivelBase'];
            }
        }
    }
}

if (defined('AJUSTAR_NIVEIS_LIB_ONLY') && AJUSTAR_NIVEIS_LIB_ONLY) {
    return;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Sessão de usuário inválida.']);
    exit;
}

$isAdmin = (!empty($_SESSION['admin_status']) && (int)$_SESSION['admin_status'] === 1 && empty($_SESSION['impersonated']));

$acao = trim((string)($_POST['acao'] ?? 'diagnostico'));
if ($acao !== 'diagnostico' && $acao !== 'executar') {
    echo json_encode(['success' => false, 'error' => 'Ação inválida.']);
    exit;
}

$clubeId = (int)($_POST['clube'] ?? 0);
if ($clubeId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Clube inválido.']);
    exit;
}

$modo = trim((string)($_POST['modo'] ?? 'delta'));
if ($modo !== 'delta' && $modo !== 'media_alvo') {
    echo json_encode(['success' => false, 'error' => 'Modo de ajuste inválido.']);
    exit;
}

$deltaRaw = trim((string)($_POST['delta'] ?? ''));
$delta = ($deltaRaw === '') ? 0 : (int)$deltaRaw;
if ($delta < -98) {
    $delta = -98;
} elseif ($delta > 98) {
    $delta = 98;
}

$mediaAlvoRaw = trim((string)($_POST['media_alvo'] ?? ''));
$mediaAlvo = null;
if ($mediaAlvoRaw !== '') {
    $mediaNorm = str_replace(',', '.', $mediaAlvoRaw);
    if (!is_numeric($mediaNorm)) {
        echo json_encode(['success' => false, 'error' => 'Média alvo inválida.']);
        exit;
    }
    $mediaAlvo = (float)$mediaNorm;
    if ($mediaAlvo < 1.0 || $mediaAlvo > 99.0) {
        echo json_encode(['success' => false, 'error' => 'A média alvo deve estar entre 1 e 99.']);
        exit;
    }
}

$incluirEstrangeiros = ((string)($_POST['incluir_estrangeiros'] ?? '1') === '1');
$recalcularPasse = ((string)($_POST['recalcular_passe'] ?? '0') === '1');

if ($acao === 'executar') {
    if ($modo === 'delta' && $delta === 0) {
        echo json_encode(['success' => false, 'error' => 'Informe um ajuste diferente de zero.']);
        exit;
    }
    if ($modo === 'media_alvo' && $mediaAlvo === null) {
        echo json_encode(['success' => false, 'error' => 'Informe uma média alvo válida entre 1 e 99.']);
        exit;
    }
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/jogador.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/usuarios.php';

$database = new Database();
$db = $database->getConnection();
$jogadorObj = new Jogador($db);
$usuarioObj = new Usuario($db);

try {
    $stmtClube = $db->prepare("
        SELECT c.ID AS clubeId, c.Nome AS clubeNome, COALESCE(p.dono, 0) AS donoClube
        FROM clube c
        LEFT JOIN paises p ON c.Pais = p.id
        WHERE c.ID = ? AND c.status = 0
        LIMIT 1
    ");
    $stmtClube->execute([$clubeId]);
    $clubeRow = $stmtClube->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao consultar clube em ajustar_niveis.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao consultar o clube selecionado.']);
    exit;
}

if (!$clubeRow) {
    echo json_encode(['success' => false, 'error' => 'Clube não encontrado.']);
    exit;
}

if (!$isAdmin && (int)$clubeRow['donoClube'] !== $userId) {
    echo json_encode(['success' => false, 'error' => 'Você não tem permissão para alterar os níveis deste clube.']);
    exit;
}

$clubeNome = html_entity_decode((string)($clubeRow['clubeNome'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

$queryElenco = "
    SELECT
        j.ID AS idJogador,
        j.Nome AS nomeJogador,
        j.Nivel AS nivelBase,
        j.Nascimento AS nascimento,
        j.CobradorFalta AS cobradorFalta,
        j.StringPosicoes AS stringPosicoes,
        j.disponibilidade AS disponibilidade,
        j.data_falecimento AS dataFalecimento,
        COALESCE(pj.dono, 0) AS donoJogador,
        cj.clube AS clubeId,
        cj.tipoContrato AS tipoContrato,
        COALESCE(cj.ModificadorNivel, 0) AS modificadorNivel,
        cj.titularidade AS titularidade,
        COALESCE(pc.dono, 0) AS donoClube
    FROM contratos_jogador cj
    INNER JOIN jogador j ON cj.jogador = j.ID
    INNER JOIN clube c ON cj.clube = c.ID
    LEFT JOIN paises pj ON j.Pais = pj.id
    LEFT JOIN paises pc ON c.Pais = pc.id
    WHERE cj.clube = ?
      AND cj.tipoContrato = 0
      AND c.status = 0
    ORDER BY cj.titularidade DESC, j.Nome ASC
";

try {
    $stmtElenco = $db->prepare($queryElenco);
    $stmtElenco->execute([$clubeId]);
    $rows = $stmtElenco->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao consultar elenco em ajustar_niveis.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao carregar os jogadores do clube.']);
    exit;
}

$elegiveis = [];
$ignorados = [];
$ordemOriginal = [];
$totalFalecidosIgnorados = 0;
$totalNativos = 0;
$totalEstrangeiros = 0;

foreach ($rows as $row) {
    if ((int)($row['disponibilidade'] ?? 0) === -3 || !empty($row['dataFalecimento'])) {
        $totalFalecidosIgnorados++;
        continue;
    }

    $idJog = (int)$row['idJogador'];
    $nivelBase = (int)$row['nivelBase'];
    $modAtual = (int)$row['modificadorNivel'];
    $efetivoAtual = $nivelBase + $modAtual;
    $donoJogador = (int)$row['donoJogador'];
    $donoClube = (int)$row['donoClube'];
    $isProprio = ($donoJogador === 0 || $donoJogador === $donoClube);

    $item = [
        'idJogador'      => $idJog,
        'nomeJogador'    => html_entity_decode((string)($row['nomeJogador'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'clubeId'        => $clubeId,
        'clubeNome'      => $clubeNome,
        'tipoContrato'   => (int)$row['tipoContrato'],
        'tipoAjuste'     => $isProprio ? 'nivel' : 'modificador',
        'nivelBase'      => $nivelBase,
        'modAtual'       => $modAtual,
        'efetivoAtual'   => $efetivoAtual,
        'efetivoNovo'    => $efetivoAtual,
        'deltaAplicado'  => 0,
        'novoNivelBase'  => $nivelBase,
        'novoMod'        => $modAtual,
        'atingiuLimite'  => false,
        'minEfetivo'     => 1,
        'maxEfetivo'     => 99,
        'nascimento'     => $row['nascimento'],
        'cobradorFalta'  => (int)$row['cobradorFalta'],
        'stringPosicoes' => (string)($row['stringPosicoes'] ?? ''),
    ];

    if (!$isProprio && !$incluirEstrangeiros) {
        $item['tipoAjuste'] = 'ignorado';
        $idxIgn = count($ignorados);
        $ignorados[] = $item;
        $ordemOriginal[] = ['lista' => 'ignorados', 'idx' => $idxIgn];
    } else {
        if ($isProprio) {
            $totalNativos++;
        } else {
            $totalEstrangeiros++;
        }
        $limites = calcularLimitesEfetivosNivel($item['tipoAjuste'], $modAtual);
        $item['minEfetivo'] = $limites['minEfetivo'];
        $item['maxEfetivo'] = $limites['maxEfetivo'];
        $idxEl = count($elegiveis);
        $elegiveis[] = $item;
        $ordemOriginal[] = ['lista' => 'elegiveis', 'idx' => $idxEl];
    }
}

$deveCalcular = ($modo === 'delta') || ($modo === 'media_alvo' && $mediaAlvo !== null);
if ($deveCalcular && !empty($elegiveis)) {
    calcularAjusteGrupoNiveis($elegiveis, $modo, $delta, $mediaAlvo);
}

$totalElegiveis = count($elegiveis);
$totalIgnoradosEstrangeiros = count($ignorados);
$totalJogadores = $totalElegiveis + $totalIgnoradosEstrangeiros;

$somaElegiveisAtual = 0;
$somaElegiveisPrevista = 0;
$totalAlterados = 0;
$totalNativosAlterados = 0;
$totalEstrangeirosAlterados = 0;
$totalAtingiramLimite = 0;

foreach ($elegiveis as $item) {
    $somaElegiveisAtual += $item['efetivoAtual'];
    $somaElegiveisPrevista += $item['efetivoNovo'];
    if ($item['deltaAplicado'] !== 0) {
        $totalAlterados++;
        if ($item['tipoAjuste'] === 'nivel') {
            $totalNativosAlterados++;
        } else {
            $totalEstrangeirosAlterados++;
        }
    }
    if (!empty($item['atingiuLimite'])) {
        $totalAtingiramLimite++;
    }
}

$somaIgnorados = 0;
foreach ($ignorados as $item) {
    $somaIgnorados += $item['efetivoAtual'];
}

$somaTodosAtual = $somaElegiveisAtual + $somaIgnorados;
$somaTodosPrevista = $somaElegiveisPrevista + $somaIgnorados;

$mediaElegiveisAtual = ($totalElegiveis > 0) ? round($somaElegiveisAtual / $totalElegiveis, 1) : 0.0;
$mediaElegiveisPrevista = ($totalElegiveis > 0) ? round($somaElegiveisPrevista / $totalElegiveis, 1) : 0.0;
$mediaAtual = ($totalJogadores > 0) ? round($somaTodosAtual / $totalJogadores, 1) : 0.0;
$mediaPrevista = ($totalJogadores > 0) ? round($somaTodosPrevista / $totalJogadores, 1) : 0.0;

if ($acao === 'diagnostico') {
    $jogadoresPrevia = [];
    foreach ($ordemOriginal as $ref) {
        $item = ($ref['lista'] === 'elegiveis') ? $elegiveis[$ref['idx']] : $ignorados[$ref['idx']];
        $jogadoresPrevia[] = [
            'idJogador'     => $item['idJogador'],
            'nome'          => $item['nomeJogador'],
            'nivelAtual'    => $item['efetivoAtual'],
            'nivelNovo'     => $item['efetivoNovo'],
            'delta'         => $item['deltaAplicado'],
            'tipoAjuste'    => $item['tipoAjuste'],
            'atingiuLimite' => !empty($item['atingiuLimite']),
            'modAtual'      => $item['modAtual'],
            'novoMod'       => $item['novoMod'],
        ];
    }

    echo json_encode([
        'success'                    => true,
        'clubeId'                    => $clubeId,
        'clubeNome'                  => $clubeNome,
        'totalJogadores'             => $totalJogadores,
        'totalElegiveis'             => $totalElegiveis,
        'totalNativos'               => $totalNativos,
        'totalEstrangeiros'          => $totalEstrangeiros,
        'totalIgnoradosEstrangeiros' => $totalIgnoradosEstrangeiros,
        'totalFalecidosIgnorados'    => $totalFalecidosIgnorados,
        'totalAlterados'             => $totalAlterados,
        'totalNativosAlterados'      => $totalNativosAlterados,
        'totalEstrangeirosAlterados' => $totalEstrangeirosAlterados,
        'totalAtingiramLimite'       => $totalAtingiramLimite,
        'mediaElegiveisAtual'        => $mediaElegiveisAtual,
        'mediaElegiveisPrevista'     => $mediaElegiveisPrevista,
        'mediaAtual'                 => $mediaAtual,
        'mediaPrevista'              => $mediaPrevista,
        'jogadores'                  => $jogadoresPrevia,
    ]);
    exit;
}

if ($totalAlterados === 0) {
    echo json_encode(['success' => false, 'error' => 'Nenhuma alteração de nível a aplicar com os parâmetros informados.']);
    exit;
}

$paramsValores = $jogadorObj->obterParametrosValores($userId);
$percentualSalario = (float)($paramsValores['percentual_salario'] ?? 0.50);

$stmtUpdateJogadorSemValor = $db->prepare("UPDATE jogador SET Nivel = ?, valorAtualizado = ? WHERE ID = ?");
$stmtUpdateJogadorComValor = $db->prepare("UPDATE jogador SET Nivel = ?, valorAtualizado = ?, valor = ? WHERE ID = ?");
$stmtUpdateJogadorValorApenas = $db->prepare("UPDATE jogador SET valorAtualizado = ?, valor = ? WHERE ID = ?");
$stmtUpdateContratoMod = $db->prepare("UPDATE contratos_jogador SET ModificadorNivel = ? WHERE jogador = ? AND clube = ? AND tipoContrato = ?");
$stmtUpdateContratoSalario = $db->prepare("UPDATE contratos_jogador SET salario = ? WHERE jogador = ? AND clube = ? AND tipoContrato = ?");

$db->beginTransaction();
try {
    foreach ($elegiveis as $item) {
        if ((int)$item['deltaAplicado'] === 0) {
            continue;
        }

        $idJog = (int)$item['idJogador'];
        $tipoContrato = (int)$item['tipoContrato'];
        $nascArg = !empty($item['nascimento']) ? $item['nascimento'] : null;
        $cobArg = ($item['cobradorFalta'] > 0) ? (int)$item['cobradorFalta'] : null;
        $posArg = ($item['stringPosicoes'] !== '') ? $item['stringPosicoes'] : null;

        if ($item['tipoAjuste'] === 'nivel') {
            $novoNivelBase = (int)$item['novoNivelBase'];
            $novoValor = $jogadorObj->calcularPasse($idJog, $novoNivelBase, $nascArg, $cobArg, $posArg, $paramsValores);

            if ($recalcularPasse) {
                $stmtUpdateJogadorComValor->execute([$novoNivelBase, $novoValor, $novoValor, $idJog]);
                $novoSalario = $jogadorObj->calcularSalario($novoValor, $percentualSalario);
                $stmtUpdateContratoSalario->execute([$novoSalario, $idJog, $clubeId, $tipoContrato]);
            } else {
                $stmtUpdateJogadorSemValor->execute([$novoNivelBase, $novoValor, $idJog]);
            }
        } else {
            $novoMod = (int)$item['novoMod'];
            $stmtUpdateContratoMod->execute([$novoMod, $idJog, $clubeId, $tipoContrato]);

            if ($recalcularPasse) {
                $efetivoNovo = (int)$item['efetivoNovo'];
                $novoValor = $jogadorObj->calcularPasse($idJog, $efetivoNovo, $nascArg, $cobArg, $posArg, $paramsValores);
                $stmtUpdateJogadorValorApenas->execute([$novoValor, $novoValor, $idJog]);
                $novoSalario = $jogadorObj->calcularSalario($novoValor, $percentualSalario);
                $stmtUpdateContratoSalario->execute([$novoSalario, $idJog, $clubeId, $tipoContrato]);
            }
        }
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    error_log('Erro em ajustar_niveis.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Falha ao salvar os novos níveis no banco de dados.']);
    exit;
}

$usuarioObj->atualizarAlteracao($userId);

$mediaAnteriorFmt = number_format($mediaAtual, 1, '.', '');
$mediaNovaFmt = number_format($mediaPrevista, 1, '.', '');
$nomeClubeTexto = htmlspecialchars($clubeNome, ENT_QUOTES, 'UTF-8');

$msg = "Níveis atualizados em <strong>{$nomeClubeTexto}</strong>: <strong>{$totalAlterados}</strong> jogador(es) alterado(s) (média de <strong>{$mediaAnteriorFmt}</strong> para <strong>{$mediaNovaFmt}</strong>).";
if ($totalEstrangeirosAlterados > 0) {
    $msg .= " ({$totalNativosAlterados} no nível base e {$totalEstrangeirosAlterados} via modificador).";
}
if ($recalcularPasse) {
    $msg .= " Passe e salário recalculados.";
}

echo json_encode([
    'success'                    => true,
    'mensagem'                   => $msg,
    'totalAlterados'             => $totalAlterados,
    'totalNativosAlterados'      => $totalNativosAlterados,
    'totalEstrangeirosAlterados' => $totalEstrangeirosAlterados,
    'mediaAnterior'              => $mediaAtual,
    'mediaNova'                  => $mediaPrevista,
    'recalculouPasse'            => $recalcularPasse,
]);
