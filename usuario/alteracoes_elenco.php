<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Alterações de Elenco - " . ($_SESSION['nomereal'] ?? '');
$css_filename = "home_redesign";
$aux_css = "fichas_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {

    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

    $database = new Database();
    $db = $database->getConnection();
    $competicaoHelper = new Competicao_clube($db);
    $idUsuario = $_SESSION['user_id'];
    $isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);

    // Buscar todas as competições internacionais (tipo = 0) onde o usuário possui clubes inscritos
    $query_comps = "
        SELECT DISTINCT 
            c.id AS competicao_id, 
            c.nome AS competicao_nome, 
            c.ano AS competicao_ano, 
            c.logo AS competicao_logo,
            co.alteracoeselenco,
            co.jogadoresadicionais,
            co.inicioalteracoes,
            co.fimalteracoes,
            DATEDIFF(co.fimalteracoes, CURDATE()) AS dias_restantes
        FROM competicao_times ct
        INNER JOIN competicao_lista c ON ct.id_competicao = c.id
        LEFT JOIN competicao_opcoes co ON c.id = co.id_competicao
        INNER JOIN clube cl ON ct.id_time_portal = cl.ID
        INNER JOIN paises p ON cl.Pais = p.id
        WHERE (p.dono = ? OR ? = 1)
          AND ct.has_team = '1'
          AND c.tipo = 0
        ORDER BY c.ano DESC, c.nome ASC
    ";

    $stmt_comps = $db->prepare($query_comps);
    $stmt_comps->execute([$idUsuario, $isAdmin ? 1 : 0]);
    $competicoesAbertas = $stmt_comps->fetchAll(PDO::FETCH_ASSOC);

    // Para cada competição aberta, carregar os times do usuário participantes
    $dadosCompeticoes = [];

    foreach ($competicoesAbertas as $comp) {
        $cId = (int)$comp['competicao_id'];
        $dbPath = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $cId . "-database.db3";
        if (!file_exists($dbPath)) {
            continue;
        }

        $sqliteDb = new SQLiteDatabase();
        $sqliteDb->fileName = $dbPath;
        $sqldb = $sqliteDb->getConnection();
        if (!$sqldb) {
            continue;
        }

        // Buscar times do usuário inscritos nesta competição
        $query_times = "
            SELECT 
                ct.codigo_time,
                ct.id_time_portal,
                cl.ID AS clube_id,
                cl.Nome AS clube_nome,
                cl.Escudo AS clube_escudo,
                p.id AS pais_id,
                p.nome AS pais_nome,
                p.bandeira AS pais_bandeira
            FROM competicao_times ct
            INNER JOIN clube cl ON ct.id_time_portal = cl.ID
            INNER JOIN paises p ON cl.Pais = p.id
            WHERE ct.id_competicao = ?
              AND ct.has_team = '1'
              AND (p.dono = ? OR ? = 1)
            ORDER BY cl.Nome ASC
        ";
        $stmt_times = $db->prepare($query_times);
        $stmt_times->execute([$cId, $idUsuario, $isAdmin ? 1 : 0]);
        $timesInscritos = $stmt_times->fetchAll(PDO::FETCH_ASSOC);

        $timesComElenco = [];

        foreach ($timesInscritos as $time) {
            $tId = (int)$time['clube_id'];
            $codigoTime = (int)$time['codigo_time'];

            // Quantas alterações de inclusão/troca já realizou (remoções não debitam cota)
            $stmtCountAlt = $db->prepare("SELECT COUNT(*) as total FROM competicao_alteracoes_log WHERE id_competicao = ? AND id_time = ? AND tipo_acao IN ('substituir', 'adicionar')");
            $stmtCountAlt->execute([$cId, $tId]);
            $totalRealizadas = (int)($stmtCountAlt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            $maxAlteracoes = (int)($comp['jogadoresadicionais'] ?? 0);
            $saldoRestante = max(0, $maxAlteracoes - $totalRealizadas);

            // Ler elenco do SQLite para este time (Clube = ID do clube no SQLite/portal)
            $stmtSqliteElenco = $sqldb->prepare("SELECT * FROM elenco WHERE Clube = :clube_id LIMIT 1");
            $stmtSqliteElenco->bindValue(':clube_id', $tId, PDO::PARAM_INT);
            $stmtSqliteElenco->execute();
            $elencoRow = $stmtSqliteElenco->fetch(PDO::FETCH_ASSOC);

            // Fallback caso o registro use codigo_time
            if (!$elencoRow) {
                $stmtSqliteElenco = $sqldb->prepare("SELECT * FROM elenco WHERE Clube = :codigo_time LIMIT 1");
                $stmtSqliteElenco->bindValue(':codigo_time', $codigoTime, PDO::PARAM_INT);
                $stmtSqliteElenco->execute();
                $elencoRow = $stmtSqliteElenco->fetch(PDO::FETCH_ASSOC);
            }

            $jogadoresInscritosIds = [];
            $slots = [];

            if ($elencoRow) {
                for ($slot = 1; $slot <= 23; $slot++) {
                    $jId = (int)($elencoRow["Jogador{$slot}"] ?? 0);
                    if ($jId > 0) {
                        $jogadoresInscritosIds[] = $jId;
                    }
                    $slots[$slot] = $jId;
                }
            } else {
                for ($slot = 1; $slot <= 23; $slot++) {
                    $slots[$slot] = 0;
                }
            }

            // Buscar dados detalhados dos jogadores inscritos (Nome, Nivel/Força, Idade e Posição através de StringPosicoes) no MariaDB
            $posMapIdx = [1=>'G',2=>'LD',3=>'LE',4=>'Z',5=>'AD',6=>'AE',7=>'V',8=>'MD',9=>'ME',10=>'MC',11=>'PD',12=>'PE',13=>'MA',14=>'Am',15=>'Aa'];
            $jogadoresSqliteData = [];
            if (!empty($jogadoresInscritosIds)) {
                $placeholders = implode(',', array_fill(0, count($jogadoresInscritosIds), '?'));
                $stmtJogs = $db->prepare("
                    SELECT 
                        ID, 
                        Nome, 
                        FLOOR(DATEDIFF(CURDATE(), Nascimento)/365) as Idade, 
                        Nivel as Forca, 
                        StringPosicoes
                    FROM jogador
                    WHERE ID IN ($placeholders)
                ");
                $stmtJogs->execute($jogadoresInscritosIds);
                while ($row = $stmtJogs->fetch(PDO::FETCH_ASSOC)) {
                    $sp = $row['StringPosicoes'] ?? '';
                    $posicoesDisponiveis = [];
                    for ($pi = 1; $pi <= 15; $pi++) {
                        if (isset($sp[$pi-1]) && $sp[$pi-1] === '1') {
                            $posicoesDisponiveis[] = $posMapIdx[$pi];
                        }
                    }
                    $row['PosAbrev'] = !empty($posicoesDisponiveis) ? implode('/', $posicoesDisponiveis) : 'A';
                    $row['Posicao'] = $row['PosAbrev'];
                    $jogadoresSqliteData[$row['ID']] = $row;
                }
            }

            // Buscar IDs de jogadores que já saíram/foram removidos desta competição para não permitir que joguem de novo
            $stmtRemovidos = $db->prepare("SELECT id_jogador_saiu FROM competicao_alteracoes_log WHERE id_competicao = ? AND id_time = ? AND id_jogador_saiu > 0");
            $stmtRemovidos->execute([$cId, $tId]);
            $jogadoresRemovidosIds = $stmtRemovidos->fetchAll(PDO::FETCH_COLUMN);

            // Buscar todos os jogadores do clube no MariaDB para opções de substituição / adição
            // Filtro: tipoContrato = 0 (profissional), NÃO pode estar inscrito no SQLite nem ter sido removido desta competição
            $query_disponiveis = "
                SELECT 
                    j.ID, 
                    j.Nome, 
                    FLOOR(DATEDIFF(CURDATE(), j.Nascimento)/365) as Idade, 
                    j.Nivel as Forca, 
                    j.StringPosicoes,
                    j.lesionado_ate
                FROM contratos_jogador cj
                INNER JOIN jogador j ON cj.jogador = j.ID
                WHERE cj.clube = ? AND cj.tipoContrato = 0
                ORDER BY j.Nivel DESC, j.Nome ASC
            ";
            $stmt_disp = $db->prepare($query_disponiveis);
            $stmt_disp->execute([$tId]);
            $todosDoClube = $stmt_disp->fetchAll(PDO::FETCH_ASSOC);

            $disponiveisParaInscricao = [];
            foreach ($todosDoClube as $atleta) {
                $aId = (int)$atleta['ID'];
                if (!in_array($aId, $jogadoresInscritosIds) && !in_array($aId, $jogadoresRemovidosIds)) {
                    $sp = $atleta['StringPosicoes'] ?? '';
                    $posicoesDisponiveis = [];
                    for ($pi = 1; $pi <= 15; $pi++) {
                        if (isset($sp[$pi-1]) && $sp[$pi-1] === '1') {
                            $posicoesDisponiveis[] = $posMapIdx[$pi];
                        }
                    }
                    $atleta['PosAbrev'] = !empty($posicoesDisponiveis) ? implode('/', $posicoesDisponiveis) : 'A';
                    $atleta['Posicao'] = $atleta['PosAbrev'];
                    $disponiveisParaInscricao[] = $atleta;
                }
            }

            $timesComElenco[] = [
                'time_info' => $time,
                'saldo_restante' => $saldoRestante,
                'max_alteracoes' => $maxAlteracoes,
                'total_realizadas' => $totalRealizadas,
                'slots' => $slots,
                'jogadores_sqlite' => $jogadoresSqliteData,
                'jogadores_disponiveis' => $disponiveisParaInscricao
            ];
        }

        if (!empty($timesComElenco)) {
            $comp['times'] = $timesComElenco;
            $dadosCompeticoes[] = $comp;
        }
    }
?>

<main class="propostas-container" style="max-width: 1400px;">
    <div id='errorbox'></div>

    <div class="propostas-card" style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <h2 class="propostas-title" style="margin-bottom: 0;">Alterações de Elenco - <?php echo htmlspecialchars($_SESSION['nomereal']); ?></h2>
            <a href="index.php" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 8px 16px; background: rgba(0, 0, 0, 0.04); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;"
               onmouseover="this.style.background='rgba(0, 0, 0, 0.08)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.04)'">
                ← Voltar para Minha Área
            </a>
        </div>

        <?php if (!empty($dadosCompeticoes)): ?>
            <?php 
            $hoje = date('Y-m-d');
            foreach ($dadosCompeticoes as $comp): 
                $temAlteracoes = !empty($comp['alteracoeselenco']);
                $inicioValido = empty($comp['inicioalteracoes']) || $comp['inicioalteracoes'] === '0000-00-00' || $comp['inicioalteracoes'] <= $hoje;
                $fimValido = empty($comp['fimalteracoes']) || $comp['fimalteracoes'] === '0000-00-00' || $comp['fimalteracoes'] >= $hoje;
                $janelaAberta = $temAlteracoes && $inicioValido && $fimValido;
                
                $has_deadline = !empty($comp['fimalteracoes']) && $comp['fimalteracoes'] !== '0000-00-00';
                $is_urgent = $has_deadline && ($comp['dias_restantes'] < 7);
            ?>
                <div style="background: rgba(255, 255, 255, 0.9); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid rgba(0, 0, 0, 0.06); padding-bottom: 1rem; margin-bottom: 1.25rem;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <img src="/images/competicoes/<?php echo $comp['competicao_logo'] ?: 'flag.png'; ?>" alt="Logo" style="width: 48px; height: 48px; object-fit: contain; border-radius: 8px; background: #fff; border: 1px solid #e2e8f0; padding: 4px;" onerror="this.src='/images/competicoes/flag.png';" />
                            <div>
                                <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 0;">
                                    <?php echo htmlspecialchars($comp['competicao_nome'] . " " . $comp['competicao_ano']); ?>
                                </h3>
                                <div style="font-size: 0.85rem; color: #64748b; margin-top: 2px;">
                                    <?php if ($janelaAberta): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; color: #10b981; font-weight: 600;">
                                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                                            Janela de Inscrições/Trocas Aberta
                                        </span>
                                        <span style="color: #94a3b8; margin: 0 4px;">•</span>
                                        <span>
                                            <?php echo !empty($comp['inicioalteracoes']) && $comp['inicioalteracoes'] !== '0000-00-00' ? date('d/m/Y', strtotime($comp['inicioalteracoes'])) : 'Início'; ?>
                                            até 
                                            <?php echo !empty($comp['fimalteracoes']) && $comp['fimalteracoes'] !== '0000-00-00' ? date('d/m/Y', strtotime($comp['fimalteracoes'])) : 'Indeterminado'; ?>
                                        </span>
                                    <?php elseif (!$temAlteracoes): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; color: #64748b; font-weight: 500;">
                                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #94a3b8;"></span>
                                            Competição sem inscrições/trocas adicionais (Remoção livre permitida)
                                        </span>
                                    <?php else: ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; color: #ef4444; font-weight: 600;">
                                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #ef4444;"></span>
                                            Janela de Inscrições/Trocas Fechada
                                        </span>
                                        <span style="color: #94a3b8; margin: 0 4px;">•</span>
                                        <span style="color: #64748b;">(Remoção de atletas permanece livre)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($janelaAberta && $has_deadline): ?>
                            <div style="text-align: right;">
                                <span style="font-size: 0.85rem; color: #64748b;">Encerramento em</span>
                                <div style="font-weight: 700; font-size: 1.1rem; color: <?php echo $is_urgent ? '#ef4444' : '#10b981'; ?>;">
                                    <?php echo $comp['dias_restantes'] == 0 ? 'Último dia!' : $comp['dias_restantes'] . ' dia(s)'; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php foreach ($comp['times'] as $timeIndex => $timeData): ?>
                        <?php 
                        $tInfo = $timeData['time_info'];
                        $saldo = $timeData['saldo_restante'];
                        $cotaTotal = $timeData['max_alteracoes'];
                        $realizadas = $timeData['total_realizadas'];
                        $uniqueTimeId = $comp['competicao_id'] . '_' . $tInfo['clube_id'];
                        ?>
                        <div class="clube-elenco-card" style="background: #ffffff; border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; margin-bottom: 1.25rem; box-shadow: 0 2px 6px rgba(0,0,0,0.02); overflow: hidden; transition: all 0.2s;">
                            <!-- Header do Clube Clicável (Accordion) -->
                            <div class="clube-accordion-header" data-target="elenco-time-<?php echo $uniqueTimeId; ?>" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; padding: 1.1rem 1.25rem; cursor: pointer; background: #ffffff; user-select: none; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                                <div style="display: flex; align-items: center; gap: 0.85rem;">
                                    <img src="/images/escudos/<?php echo $tInfo['clube_escudo'] ?: 'default.png'; ?>" alt="Escudo" style="width: 40px; height: 40px; object-fit: contain;" onerror="this.src='/images/escudos/default.png';" />
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <h4 style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 700; color: #1e293b; margin: 0;">
                                                <?php echo htmlspecialchars($tInfo['clube_nome']); ?>
                                            </h4>
                                            <span class="material-symbols-outlined accordion-icon" style="font-size: 20px; color: #64748b; transition: transform 0.25s;">expand_more</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: #64748b; margin-top: 2px;">
                                            <img src="/images/bandeiras/<?php echo $tInfo['pais_bandeira']; ?>" style="width: 18px; height: 12px; object-fit: cover; border-radius: 2px;" onerror="this.src='/images/bandeiras/flag.png';" />
                                            <span><?php echo htmlspecialchars($tInfo['pais_nome']); ?></span>
                                            <span style="color: #cbd5e1;">•</span>
                                            <span style="font-size: 0.8rem; color: #0284c7; font-weight: 600;">Clique para ver o elenco</span>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <?php if ($temAlteracoes): ?>
                                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 14px; text-align: center;">
                                            <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Inscrições/Trocas Restantes</div>
                                            <div style="font-size: 1.1rem; font-weight: 700; color: <?php echo ($janelaAberta && $saldo > 0) ? '#0284c7' : '#94a3b8'; ?>;">
                                                <?php echo $saldo; ?> <span style="font-size: 0.8rem; font-weight: 500; color: #64748b;">restante(s) de <?php echo $cotaTotal; ?></span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 14px; text-align: center;">
                                            <div style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Status</div>
                                            <div style="font-size: 0.95rem; font-weight: 600; color: #64748b;">
                                                Apenas Remoções
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Conteúdo do Elenco (Aninhado / Escondido por padrão) -->
                            <div id="elenco-time-<?php echo $uniqueTimeId; ?>" class="clube-elenco-body" style="display: none; padding: 0 1.25rem 1.25rem 1.25rem; border-top: 1px solid #f1f5f9;">
                                <!-- Tabela de Slots do Elenco -->
                                <div class="tbl_user_data" style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 10px; margin-top: 1rem;">
                                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left;">
                                        <thead>
                                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 600;">
                                                <th style="padding: 10px 14px; width: 60px;">Slot</th>
                                                <th style="padding: 10px 14px;">Jogador Inscrito</th>
                                                <th style="padding: 10px 14px; width: 80px;">Posição</th>
                                                <th style="padding: 10px 14px; width: 70px; text-align: center;">Idade</th>
                                                <th style="padding: 10px 14px; width: 70px; text-align: center;">Força</th>
                                                <th style="padding: 10px 14px; width: 220px; text-align: right;">Ações de Alteração</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php for ($s = 1; $s <= 23; $s++): ?>
                                                <?php 
                                                 $jId = $timeData['slots'][$s] ?? 0;
                                                $jData = $timeData['jogadores_sqlite'][$jId] ?? null;
                                                ?>
                                                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                                    <td style="padding: 8px 14px; font-weight: 700; color: #94a3b8;">
                                                        #<?php echo $s; ?>
                                                    </td>
                                                    <td style="padding: 8px 14px;">
                                                        <?php if ($jData): ?>
                                                            <strong style="color: #0f172a;"><?php echo htmlspecialchars($jData['Nome']); ?></strong>
                                                        <?php else: ?>
                                                            <span style="color: #94a3b8; font-style: italic;">[ Slot Vazio ]</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="padding: 8px 14px;">
                                                        <?php if ($jData): ?>
                                                            <span style="background: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 6px; font-weight: 600; font-size: 0.8rem;">
                                                                <?php echo htmlspecialchars($jData['PosAbrev'] ?: $jData['Posicao'] ?: '-'); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="padding: 8px 14px; text-align: center; color: #64748b;">
                                                        <?php echo $jData ? $jData['Idade'] : '-'; ?>
                                                    </td>
                                                    <td style="padding: 8px 14px; text-align: center; font-weight: 700; color: #0284c7;">
                                                        <?php echo $jData ? $jData['Forca'] : '-'; ?>
                                                    </td>
                                                    <td style="padding: 8px 14px; text-align: right; white-space: nowrap;">
                                                        <?php if ($jData): ?>
                                                            <?php if ($janelaAberta && $saldo > 0): ?>
                                                                <!-- Opção de Substituir -->
                                                                <button 
                                                                    type="button" 
                                                                    class="btn-substituir"
                                                                    data-comp-id="<?php echo $comp['competicao_id']; ?>"
                                                                    data-time-id="<?php echo $tInfo['clube_id']; ?>"
                                                                    data-slot-pos="<?php echo $s; ?>"
                                                                    data-jogador-saiu="<?php echo $jData['ID']; ?>"
                                                                    data-jogador-nome="<?php echo htmlspecialchars($jData['Nome']); ?>"
                                                                    style="background: #0284c7; color: #fff; border: none; padding: 5px 10px; border-radius: 6px; font-weight: 600; font-size: 0.8rem; cursor: pointer; margin-right: 4px; transition: background 0.2s;"
                                                                    onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">
                                                                    Substituir
                                                                </button>
                                                            <?php endif; ?>
                                                            <!-- Remover sempre disponível -->
                                                            <button 
                                                                type="button" 
                                                                class="btn-remover"
                                                                data-comp-id="<?php echo $comp['competicao_id']; ?>"
                                                                data-time-id="<?php echo $tInfo['clube_id']; ?>"
                                                                data-slot-pos="<?php echo $s; ?>"
                                                                data-jogador-saiu="<?php echo $jData['ID']; ?>"
                                                                data-jogador-nome="<?php echo htmlspecialchars($jData['Nome']); ?>"
                                                                style="background: #ef4444; color: #fff; border: none; padding: 5px 10px; border-radius: 6px; font-weight: 600; font-size: 0.8rem; cursor: pointer; transition: background 0.2s;"
                                                                onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                                                                Remover
                                                            </button>
                                                        <?php else: ?>
                                                            <?php if ($janelaAberta && $saldo > 0): ?>
                                                                <!-- Opção de Adicionar Jogador no Slot Vazio -->
                                                                <button 
                                                                    type="button" 
                                                                    class="btn-adicionar"
                                                                    data-comp-id="<?php echo $comp['competicao_id']; ?>"
                                                                    data-time-id="<?php echo $tInfo['clube_id']; ?>"
                                                                    data-slot-pos="<?php echo $s; ?>"
                                                                    style="background: #10b981; color: #fff; border: none; padding: 5px 12px; border-radius: 6px; font-weight: 600; font-size: 0.8rem; cursor: pointer; transition: background 0.2s;"
                                                                    onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                                                                + Inscrever Atleta
                                                                </button>
                                                            <?php else: ?>
                                                                <span style="font-size: 0.8rem; color: #94a3b8; font-style: italic;">Slot Vazio</span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Armazenar lista JSON de jogadores disponíveis para esse clube -->
                                <script type="application/json" id="disp-time-<?php echo $tInfo['clube_id']; ?>">
                                    <?php echo json_encode($timeData['jogadores_disponiveis']); ?>
                                </script>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding: 3.5rem 1.5rem; text-align: center; background: rgba(255, 255, 255, 0.7); border-radius: 16px; border: 1px dashed rgba(2, 132, 199, 0.3); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
                <div style="width: 72px; height: 72px; margin: 0 auto 1.25rem auto; background: rgba(2, 132, 199, 0.08); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined" style="font-size: 38px; color: #0284c7;">published_with_changes</span>
                </div>
                <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.35rem; font-weight: 700; color: #1e293b; margin: 0 0 0.5rem 0;">Nenhuma janela de alteração de elenco aberta no momento</h3>
                <p style="font-family: 'Montserrat', sans-serif; font-size: 0.95rem; color: #64748b; max-width: 550px; margin: 0 auto 1.5rem auto; line-height: 1.6;">
                    As competições em que seus clubes participam e que possuem a opção de <strong>Alterações de Elenco</strong> ativada serão listadas automaticamente aqui durante o período definido para as trocas.
                </p>
                <div style="display: inline-flex; flex-direction: column; gap: 0.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.5rem; text-align: left; max-width: 500px; margin-bottom: 1.5rem;">
                    <span style="font-size: 0.85rem; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="material-symbols-outlined" style="font-size: 18px; color: #0284c7;">info</span>
                        Como funcionam as alterações:
                    </span>
                    <span style="font-size: 0.82rem; color: #64748b;">
                        • <strong>Substituir:</strong> Troque um atleta já inscrito por outro do clube que não esteja na competição.<br>
                        • <strong>Inscrever (+):</strong> Adicione atletas em slots vazios caso o clube tenha menos de 23 inscritos.<br>
                        • <strong>Remover:</strong> Desinscreva um jogador de vez da competição.
                    </span>
                </div>
                <div>
                    <a href="index.php" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 10px 22px; background: #0284c7; color: #ffffff; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;"
                       onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">
                        ← Voltar para Minha Área
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal Dinâmico de Seleção de Atleta para Substituição / Inscrição -->
<div id="modalAlteracao" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 1.5rem;">
    <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 520px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); overflow: hidden; border: 1px solid rgba(0,0,0,0.1);">
        <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitulo" style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; color: #0f172a; margin: 0;"></h3>
            <button type="button" id="btnFecharModal" style="background: none; border: none; font-size: 1.5rem; line-height: 1; color: #64748b; cursor: pointer;">&times;</button>
        </div>

        <div style="padding: 1.5rem;">
            <div id="modalDescricao" style="font-size: 0.9rem; color: #475569; margin-bottom: 1.25rem;"></div>

            <div style="margin-bottom: 1.5rem;">
                <label for="selectNovoJogador" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 0.5rem;">Selecione o jogador a inscrever:</label>
                <select id="selectNovoJogador" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; font-family: 'Montserrat', sans-serif; outline: none; background: #fff;">
                    <option value="">Carregando...</option>
                </select>
                <small style="color: #64748b; font-size: 0.8rem; margin-top: 0.35rem; display: block;">Apenas atletas do clube que ainda não estão inscritos nesta competição.</small>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" id="btnCancelarModal" style="padding: 8px 16px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; color: #475569; font-weight: 600; cursor: pointer;">Cancelar</button>
                <button type="button" id="btnConfirmarAcao" style="padding: 8px 18px; background: #0284c7; border: none; border-radius: 8px; color: #fff; font-weight: 600; cursor: pointer;">Confirmar Alteração</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Popup de Confirmação para Remoção de Atleta -->
<div id="modalConfirmarRemocao" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 1.5rem;">
    <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); overflow: hidden; border: 1px solid rgba(0,0,0,0.1);">
        <div style="background: #fef2f2; border-bottom: 1px solid #fee2e2; padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <span class="material-symbols-outlined" style="font-size: 28px; color: #ef4444;">warning</span>
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; color: #991b1b; margin: 0;">Confirmar Remoção</h3>
        </div>

        <div style="padding: 1.5rem;">
            <p id="modalRemocaoTexto" style="font-size: 0.95rem; color: #334155; line-height: 1.6; margin: 0 0 1rem 0;"></p>
            <div style="background: #f8fafc; border-left: 4px solid #f59e0b; padding: 0.75rem 1rem; border-radius: 4px; font-size: 0.85rem; color: #64748b; margin-bottom: 1.5rem;">
                O jogador será desinscrito desta competição e removido da escalação se estiver escalado. Suas estatísticas de jogos anteriores serão preservadas.
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" id="btnCancelarRemocao" style="padding: 8px 16px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; color: #475569; font-weight: 600; cursor: pointer;">Cancelar</button>
                <button type="button" id="btnConfirmarRemocaoFinal" style="padding: 8px 18px; background: #ef4444; border: none; border-radius: 8px; color: #fff; font-weight: 600; cursor: pointer;">Sim, Remover Atleta</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 0. Accordion para expandir/recolher elenco do clube
    $(document).on('click', '.clube-accordion-header', function(e) {
        var targetId = $(this).data('target');
        var $body = $('#' + targetId);
        var $icon = $(this).find('.accordion-icon');
        
        $body.slideToggle(200, function() {
            if ($body.is(':visible')) {
                $icon.text('expand_less');
            } else {
                $icon.text('expand_more');
            }
        });
    });

    var acaoAtual = '';
    var compIdAtual = 0;
    var timeIdAtual = 0;
    var slotPosAtual = 0;
    var jogadorSaiuAtual = 0;

    var remocaoCompId = 0;
    var remocaoTimeId = 0;
    var remocaoSlotPos = 0;
    var remocaoJogadorSaiu = 0;

    function abrirModal(titulo, desc, timeId, callbackOpcoes) {
        $('#modalTitulo').text(titulo);
        $('#modalDescricao').html(desc);
        
        var $select = $('#selectNovoJogador');
        $select.empty();

        var jsonStr = $('#disp-time-' + timeId).text();
        var lista = [];
        try {
            lista = JSON.parse(jsonStr);
        } catch(e) {
            lista = [];
        }

        if (lista.length === 0) {
            $select.append('<option value="">Nenhum atleta disponível no clube para inscrição</option>');
            $('#btnConfirmarAcao').prop('disabled', true).css('opacity', '0.5');
        } else {
            $select.append('<option value="">-- Escolha um atleta --</option>');
            lista.forEach(function(jog) {
                var pos = jog.PosAbrev || jog.Posicao || '';
                var les = (jog.lesionado_ate && jog.lesionado_ate >= '<?php echo date('Y-m-d'); ?>') ? ' [DM]' : '';
                $select.append('<option value="' + jog.ID + '">' + jog.Nome + ' (' + pos + ' | Força: ' + jog.Forca + les + ')</option>');
            });
            $('#btnConfirmarAcao').prop('disabled', false).css('opacity', '1');
        }

        $('#modalAlteracao').css('display', 'flex');
    }

    function fecharModal() {
        $('#modalAlteracao').hide();
        $('#btnConfirmarAcao').prop('disabled', false).text('Confirmar Alteração').css('opacity', '1');
    }

    $('#btnFecharModal, #btnCancelarModal').on('click', function() {
        fecharModal();
    });

    function fecharModalRemocao() {
        $('#modalConfirmarRemocao').hide();
        $('#btnConfirmarRemocaoFinal').prop('disabled', false).text('Sim, Remover Atleta');
    }

    $('#btnCancelarRemocao').on('click', function() {
        fecharModalRemocao();
    });

    // 1. Ação Substituir
    $(document).on('click', '.btn-substituir', function(e) {
        e.stopPropagation();
        acaoAtual = 'substituir';
        compIdAtual = $(this).data('comp-id');
        timeIdAtual = $(this).data('time-id');
        slotPosAtual = $(this).data('slot-pos');
        jogadorSaiuAtual = $(this).data('jogador-saiu');
        var nomeSai = $(this).data('jogador-nome');

        abrirModal(
            'Substituir Jogador',
            'Substituindo <strong>' + nomeSai + '</strong> (Slot #' + slotPosAtual + ') por um novo atleta do seu clube na competição:',
            timeIdAtual
        );
    });

    // 2. Ação Adicionar (Slot vazio)
    $(document).on('click', '.btn-adicionar', function(e) {
        e.stopPropagation();
        acaoAtual = 'adicionar';
        compIdAtual = $(this).data('comp-id');
        timeIdAtual = $(this).data('time-id');
        slotPosAtual = $(this).data('slot-pos');
        jogadorSaiuAtual = 0;

        abrirModal(
            'Inscrever Atleta no Slot Vazio',
            'Preenchendo o <strong>Slot #' + slotPosAtual + '</strong> com um novo atleta do seu clube:',
            timeIdAtual
        );
    });

    // 3. Ação Remover -> Abre Modal Popup de Confirmação
    $(document).on('click', '.btn-remover', function(e) {
        e.stopPropagation();
        remocaoCompId = $(this).data('comp-id');
        remocaoTimeId = $(this).data('time-id');
        remocaoSlotPos = $(this).data('slot-pos');
        remocaoJogadorSaiu = $(this).data('jogador-saiu');
        var nomeSai = $(this).data('jogador-nome');

        $('#modalRemocaoTexto').html('Deseja realmente remover o atleta <strong>' + nomeSai + '</strong> (Slot #' + remocaoSlotPos + ') desta competição?');
        $('#modalConfirmarRemocao').css('display', 'flex');
    });

    // Confirmar Remoção no Modal Popup
    $('#btnConfirmarRemocaoFinal').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Removendo...');

        $.ajax({
            type: 'POST',
            url: '/competicoes/processar_alteracao_elenco.php',
            data: {
                id_competicao: remocaoCompId,
                id_time: remocaoTimeId,
                acao: 'remover',
                id_jogador_saiu: remocaoJogadorSaiu,
                id_jogador_entrou: 0,
                slot_pos: remocaoSlotPos
            },
            dataType: 'json'
        }).done(function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert('Erro ao remover: ' + (res.error || 'Erro desconhecido'));
                btn.prop('disabled', false).text('Sim, Remover Atleta');
            }
        }).fail(function() {
            alert('Erro inesperado na comunicação com o servidor.');
            btn.prop('disabled', false).text('Sim, Remover Atleta');
        });
    });

    // Confirmar modal (Substituir ou Adicionar)
    $('#btnConfirmarAcao').on('click', function() {
        var jogEntrou = $('#selectNovoJogador').val();
        if (!jogEntrou) {
            alert('Por favor, selecione um jogador.');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).text('Processando...');

        $.ajax({
            type: 'POST',
            url: '/competicoes/processar_alteracao_elenco.php',
            data: {
                id_competicao: compIdAtual,
                id_time: timeIdAtual,
                acao: acaoAtual,
                id_jogador_saiu: jogadorSaiuAtual,
                id_jogador_entrou: jogEntrou,
                slot_pos: slotPosAtual
            },
            dataType: 'json'
        }).done(function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert('Erro na alteração: ' + (res.error || 'Erro desconhecido'));
                btn.prop('disabled', false).text('Confirmar Alteração');
            }
        }).fail(function() {
            alert('Erro inesperado na comunicação com o servidor.');
            btn.prop('disabled', false).text('Confirmar Alteração');
        });
    });
});
</script>

<?php
} else {
    echo "<main class='redesign-container'><p>Usuário, por favor refaça o login.</p></main>";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
