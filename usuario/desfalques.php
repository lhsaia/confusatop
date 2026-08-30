<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Central de Desfalques & DM";
$css_filename = "home_redesign";
$aux_css = "competicoes_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    $database = new Database();
    $db = $database->getConnection();
    $idUsuario = $_SESSION['user_id'];

    // Mapeamento de posições abreviadas por índice do StringPosicoes
    $posMapIdx = [1=>'G',2=>'LD',3=>'LE',4=>'Z',5=>'AD',6=>'AE',7=>'V',8=>'MD',9=>'ME',10=>'MC',11=>'PD',12=>'PE',13=>'MA',14=>'Am',15=>'Aa'];

    // 1. Obter todos os Clubes sob o comando do usuário logado
    $queryClubes = "
        SELECT c.ID as id, c.Nome as nome, c.Escudo as escudo, p.sigla as pais_sigla
        FROM clube c
        INNER JOIN paises p ON c.Pais = p.id
        WHERE p.dono = :user_id
        ORDER BY c.Nome ASC
    ";
    $stmtClubes = $db->prepare($queryClubes);
    $stmtClubes->execute([':user_id' => $idUsuario]);
    $clubesUsuario = $stmtClubes->fetchAll(PDO::FETCH_ASSOC);

    // 2. Consulta de Jogadores Lesionados (Departamento Médico)
    $queryDM = "
        SELECT DISTINCT j.ID, j.Nome, j.Nivel, j.Foto, j.StringPosicoes, j.lesionado_ate,
               c.ID as clube_id, c.Nome as clube_nome, c.Escudo as clube_escudo,
               DATEDIFF(j.lesionado_ate, CURDATE()) as dias_restantes
        FROM contratos_jogador cj
        INNER JOIN jogador j ON cj.jogador = j.ID
        INNER JOIN clube c ON cj.clube = c.ID
        INNER JOIN paises p ON c.Pais = p.id
        WHERE p.dono = :user_id 
          AND cj.tipoContrato = 0
          AND j.lesionado_ate IS NOT NULL 
          AND j.lesionado_ate >= CURDATE()
        ORDER BY dias_restantes ASC, c.Nome ASC, j.Nome ASC
    ";
    $stmtDM = $db->prepare($queryDM);
    $stmtDM->execute([':user_id' => $idUsuario]);
    $jogadoresDM = $stmtDM->fetchAll(PDO::FETCH_ASSOC);

    // Processar posições formatadas para o DM
    foreach ($jogadoresDM as &$dmJog) {
        $sp = $dmJog['StringPosicoes'] ?? '';
        $posicoes = [];
        for ($i = 0; $i < strlen($sp); $i++) {
            if ($sp[$i] === '1' && isset($posMapIdx[$i + 1])) {
                $posicoes[] = $posMapIdx[$i + 1];
            }
        }
        $dmJog['posicoes_formatadas'] = !empty($posicoes) ? implode(', ', $posicoes) : '-';
    }
    unset($dmJog);

    // 3. Consulta de Jogadores Suspensos / Disciplina
    // Garantir que a tabela competicao_suspensos exista no MariaDB
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `competicao_suspensos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `id_competicao` INT NOT NULL,
            `id_jogador` INT NOT NULL,
            `cartoes_amarelos` INT DEFAULT 0,
            `suspenso` TINYINT(1) DEFAULT 0,
            `jogos_restantes` INT DEFAULT 0,
            `lesionado_ate` DATE DEFAULT NULL,
            INDEX (`id_competicao`),
            INDEX (`id_jogador`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}

    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");
    $competicaoHelper = new Competicao_clube($db);

    $jogadoresSuspensos = [];
    try {
        $querySuspensos = "
            SELECT cs.id_competicao, cs.id_jogador, cs.cartoes_amarelos, cs.suspenso, cs.jogos_restantes,
                   co.suspensao as criterio_suspensao,
                   j.Nome as jogador_nome, j.Nivel as jogador_nivel, j.Foto as jogador_foto, j.StringPosicoes,
                   c.ID as clube_id, c.Nome as clube_nome, c.Escudo as clube_escudo,
                   cl.nome as comp_nome, cl.ano as comp_ano
            FROM competicao_suspensos cs
            INNER JOIN jogador j ON cs.id_jogador = j.ID
            INNER JOIN contratos_jogador cj ON cj.jogador = j.ID AND cj.tipoContrato = 0
            INNER JOIN clube c ON cj.clube = c.ID
            INNER JOIN paises p ON c.Pais = p.id
            INNER JOIN competicao_lista cl ON cs.id_competicao = cl.id
            LEFT JOIN competicao_opcoes co ON cl.id = co.id_competicao
            WHERE p.dono = :user_id
              AND (cs.suspenso = 1 OR cs.jogos_restantes > 0 OR cs.cartoes_amarelos > 0)
            ORDER BY cs.suspenso DESC, cs.jogos_restantes DESC, cs.cartoes_amarelos DESC, cl.ano DESC, c.Nome ASC
        ";
        $stmtSuspensos = $db->prepare($querySuspensos);
        $stmtSuspensos->execute([':user_id' => $idUsuario]);
        $rawSuspensos = $stmtSuspensos->fetchAll(PDO::FETCH_ASSOC);

        // Filtrar apenas suspensos de competições e clubes ainda ativos
        $clubesAtivosCache = [];
        foreach ($rawSuspensos as $rSus) {
            $cId = (int)$rSus['id_competicao'];
            $tId = (int)$rSus['clube_id'];
            $cacheKey = "{$cId}_{$tId}";
            if (!isset($clubesAtivosCache[$cacheKey])) {
                $clubesAtivosCache[$cacheKey] = $competicaoHelper->isClubeAtivoNaCompeticao($cId, $tId);
            }
            if ($clubesAtivosCache[$cacheKey] === true) {
                $jogadoresSuspensos[] = $rSus;
            }
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar suspensos: " . $e->getMessage());
        $jogadoresSuspensos = [];
    }

    // Processar posições formatadas para os Suspensos e calcular pendurados reais
    $totalSuspensosEfetivos = 0;
    $totalPendurados = 0;
    foreach ($jogadoresSuspensos as &$susJog) {
        $sp = $susJog['StringPosicoes'] ?? '';
        $posicoes = [];
        for ($i = 0; $i < strlen($sp); $i++) {
            if ($sp[$i] === '1' && isset($posMapIdx[$i + 1])) {
                $posicoes[] = $posMapIdx[$i + 1];
            }
        }
        $susJog['posicoes_formatadas'] = !empty($posicoes) ? implode(', ', $posicoes) : '-';

        // Critério da competição: 0 = apenas vermelho, 1 = 2 amarelos, 2 = 3 amarelos
        $criterio = isset($susJog['criterio_suspensao']) ? (int)$susJog['criterio_suspensao'] : 0;
        $limiteAmarelos = ($criterio == 1) ? 2 : (($criterio == 2) ? 3 : 0);
        $amarelos = (int)$susJog['cartoes_amarelos'];

        $isSuspenso = ($susJog['suspenso'] == 1 || $susJog['jogos_restantes'] > 0);
        // Pendurado apenas se faltar exatamente 1 cartão amarelo para a suspensão automática
        $isPendurado = (!$isSuspenso && $limiteAmarelos > 0 && $amarelos === ($limiteAmarelos - 1));

        $susJog['is_suspenso'] = $isSuspenso;
        $susJog['is_pendurado'] = $isPendurado;
        $susJog['limite_amarelos'] = $limiteAmarelos;

        if ($isSuspenso) {
            $totalSuspensosEfetivos++;
        } else if ($isPendurado) {
            $totalPendurados++;
        }
    }
    unset($susJog);

    $totalDM = count($jogadoresDM);
    $totalDesfalques = $totalDM + $totalSuspensosEfetivos;
?>

<main class="propostas-container">
    <div class="propostas-card">
        <!-- Header da Página -->
        <div class="header-search-container">
            <div>
                <h1 class="propostas-title">Central de Desfalques & DM</h1>
                <p style="color: #64748b; font-size: 0.95rem; margin-top: 4px; margin-bottom: 0;">
                    Monitore o Departamento Médico e as punições disciplinares de todos os seus elencos.
                </p>
            </div>
            
            <!-- Quick Actions Toolbar -->
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <a href="jogos_ativos.php" class="btn-primary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 8px 14px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; background: #0284c7; color: #fff;">
                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">sports_soccer</span>
                    Próximos Jogos
                </a>
                <a href="index.php" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 8px 14px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; background: rgba(0,0,0,0.05); color: #334155;">
                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">arrow_back</span>
                    Minha Área
                </a>
            </div>
        </div>

        <!-- Cards de Métricas Resumo -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px;">
            <!-- Total de Desfalques -->
            <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 15px;">
                <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(239, 68, 68, 0.2); display: flex; align-items: center; justify-content: center; color: #dc2626;">
                    <span class="material-symbols-outlined" style="font-size: 28px;">person_off</span>
                </div>
                <div>
                    <div style="font-size: 1.6rem; font-weight: 700; color: #dc2626; line-height: 1.1; font-family: 'Outfit', sans-serif;">
                        <?php echo $totalDesfalques; ?>
                    </div>
                    <div style="font-size: 0.8rem; font-weight: 600; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px;">
                        Desfalques Ativos
                    </div>
                </div>
            </div>

            <!-- Departamento Médico -->
            <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 15px;">
                <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(245, 158, 11, 0.2); display: flex; align-items: center; justify-content: center; color: #d97706;">
                    <span class="material-symbols-outlined" style="font-size: 28px;">medical_services</span>
                </div>
                <div>
                    <div style="font-size: 1.6rem; font-weight: 700; color: #d97706; line-height: 1.1; font-family: 'Outfit', sans-serif;">
                        <?php echo $totalDM; ?>
                    </div>
                    <div style="font-size: 0.8rem; font-weight: 600; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px;">
                        No Depto. Médico
                    </div>
                </div>
            </div>

            <!-- Suspensos Ativos -->
            <div style="background: rgba(185, 28, 28, 0.08); border: 1px solid rgba(185, 28, 28, 0.25); border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 15px;">
                <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(185, 28, 28, 0.2); display: flex; align-items: center; justify-content: center; color: #b91c1c;">
                    <span class="material-symbols-outlined" style="font-size: 28px;">gavel</span>
                </div>
                <div>
                    <div style="font-size: 1.6rem; font-weight: 700; color: #b91c1c; line-height: 1.1; font-family: 'Outfit', sans-serif;">
                        <?php echo $totalSuspensosEfetivos; ?>
                    </div>
                    <div style="font-size: 0.8rem; font-weight: 600; color: #7f1d1d; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px;">
                        Suspensos p/ Jogos
                    </div>
                </div>
            </div>

            <!-- Pendurados em Cartões -->
            <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 15px;">
                <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(245, 158, 11, 0.2); display: flex; align-items: center; justify-content: center; color: #d97706;">
                    <span class="material-symbols-outlined" style="font-size: 28px;">warning</span>
                </div>
                <div>
                    <div style="font-size: 1.6rem; font-weight: 700; color: #d97706; line-height: 1.1; font-family: 'Outfit', sans-serif;">
                        <?php echo $totalPendurados; ?>
                    </div>
                    <div style="font-size: 0.8rem; font-weight: 600; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px;">
                        Pendurados (a 1 amarelo)
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtro Interativo por Clube -->
        <div style="background: rgba(248, 250, 252, 0.9); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 10px; padding: 12px 18px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 0.9rem; font-weight: 600;">
                <span class="material-symbols-outlined" style="font-size: 1.2rem; color: #0284c7;">filter_alt</span>
                Filtrar por Clube:
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex: 1; max-width: 350px;">
                <select id="filtroClube" onchange="filtrarTabelas()" style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.15); background: #fff; font-family: 'Montserrat', sans-serif; font-size: 0.9rem; color: #1e293b; outline: none;">
                    <option value="todos">Todos os meus clubes (<?php echo count($clubesUsuario); ?>)</option>
                    <?php foreach ($clubesUsuario as $clb): ?>
                        <option value="<?php echo $clb['id']; ?>"><?php echo htmlspecialchars($clb['nome'] . " (" . $clb['pais_sigla'] . ")"); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- SEÇÃO 1: DEPARTAMENTO MÉDICO (LESÕES) -->
        <div style="margin-bottom: 35px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; border-bottom: 2px solid rgba(245, 158, 11, 0.2); padding-bottom: 8px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="material-symbols-outlined" style="color: #d97706; font-size: 26px;">medical_services</span>
                    <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0;">
                        Departamento Médico (Lesões)
                    </h2>
                </div>
                <span style="font-size: 0.85rem; font-weight: 600; background: rgba(245, 158, 11, 0.15); color: #d97706; padding: 3px 10px; border-radius: 20px;">
                    <?php echo $totalDM; ?> atleta(s)
                </span>
            </div>

            <?php if (empty($jogadoresDM)): ?>
                <div style="text-align: center; padding: 35px; background: rgba(16, 185, 129, 0.05); border: 1px dashed rgba(16, 185, 129, 0.3); border-radius: 12px; color: #059669;">
                    <span class="material-symbols-outlined" style="font-size: 40px; margin-bottom: 8px;">check_circle</span>
                    <p style="margin: 0; font-weight: 600; font-size: 1rem;">Nenhum jogador no Departamento Médico!</p>
                    <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: #64748b;">Todos os atletas dos seus clubes estão com 100% de aptidão física.</p>
                </div>
            <?php else: ?>
                <div class="tbl_user_data" style="overflow-x: auto; background: #fff; border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;" id="tabelaDM">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <th style="padding: 12px 16px;">Jogador</th>
                                <th style="padding: 12px 16px;">Posição</th>
                                <th style="padding: 12px 16px;">Clube</th>
                                <th style="padding: 12px 16px;">Nível</th>
                                <th style="padding: 12px 16px;">Previsão de Retorno</th>
                                <th style="padding: 12px 16px; text-align: center;">Tempo Restante</th>
                                <th style="padding: 12px 16px; text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jogadoresDM as $jdm): 
                                $foto = !empty($jdm['Foto']) ? "/images/jogadores/" . $jdm['Foto'] : "/images/default-user.png";
                                $escudo = !empty($jdm['clube_escudo']) ? "/images/escudos/" . $jdm['clube_escudo'] : "/images/icons/shield.png";
                                $dataFmt = date('d/m/Y', strtotime($jdm['lesionado_ate']));
                                $dias = (int)$jdm['dias_restantes'];
                                $diasText = ($dias == 0) ? 'Retorno hoje' : (($dias == 1) ? '1 dia' : "{$dias} dias");
                            ?>
                                <tr class="linha-desfalque" data-clube="<?php echo $jdm['clube_id']; ?>" style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                                    <td style="padding: 12px 16px; display: flex; align-items: center; gap: 12px;">
                                        <img src="<?php echo $foto; ?>" alt="" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0; background: #f8fafc;" onerror="this.src='/images/default-user.png';" />
                                        <div>
                                            <a href="/ligas/playerstatus.php?player=<?php echo $jdm['ID']; ?>" style="font-weight: 600; color: #0284c7; text-decoration: none;" target="_blank">
                                                <?php echo htmlspecialchars($jdm['Nome']); ?>
                                            </a>
                                            <div style="font-size: 0.75rem; color: #94a3b8;">ID: <?php echo $jdm['ID']; ?></div>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 16px; font-weight: 500; color: #475569;">
                                        <span style="background: rgba(2, 132, 199, 0.08); color: #0284c7; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">
                                            <?php echo htmlspecialchars($jdm['posicoes_formatadas']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px 16px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <img src="<?php echo $escudo; ?>" alt="" style="width: 22px; height: 22px; object-fit: contain;" onerror="this.style.display='none';" />
                                            <span style="font-weight: 500; color: #1e293b;"><?php echo htmlspecialchars($jdm['clube_nome']); ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 16px; font-weight: 600; color: #334155;">
                                        <?php echo htmlspecialchars($jdm['Nivel']); ?>
                                    </td>
                                    <td style="padding: 12px 16px; color: #475569; font-weight: 500;">
                                        <span class="material-symbols-outlined" style="font-size: 15px; vertical-align: middle; color: #64748b; margin-right: 3px;">event</span>
                                        <?php echo $dataFmt; ?>
                                    </td>
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; background: rgba(239, 68, 68, 0.12); color: #dc2626;">
                                            <span class="material-symbols-outlined" style="font-size: 14px;">timer</span>
                                            <?php echo $diasText; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px 16px; text-align: right;">
                                        <a href="/ligas/playerstatus.php?player=<?php echo $jdm['ID']; ?>" target="_blank" title="Ver Perfil" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 6px; background: #f1f5f9; color: #475569; text-decoration: none; border: 1px solid #cbd5e1;">
                                            <span class="material-symbols-outlined" style="font-size: 16px;">visibility</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- SEÇÃO 2: SUSPENSÕES & DISCIPLINAR -->
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; border-bottom: 2px solid rgba(220, 38, 38, 0.2); padding-bottom: 8px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="material-symbols-outlined" style="color: #dc2626; font-size: 26px;">gavel</span>
                    <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 600; color: #1e293b; margin: 0;">
                        Suspensões & Disciplinar por Competição
                    </h2>
                </div>
                <span style="font-size: 0.85rem; font-weight: 600; background: rgba(220, 38, 38, 0.12); color: #dc2626; padding: 3px 10px; border-radius: 20px;">
                    <?php echo count($jogadoresSuspensos); ?> registro(s)
                </span>
            </div>

            <?php if (empty($jogadoresSuspensos)): ?>
                <div style="text-align: center; padding: 35px; background: rgba(16, 185, 129, 0.05); border: 1px dashed rgba(16, 185, 129, 0.3); border-radius: 12px; color: #059669;">
                    <span class="material-symbols-outlined" style="font-size: 40px; margin-bottom: 8px;">verified_user</span>
                    <p style="margin: 0; font-weight: 600; font-size: 1rem;">Nenhuma suspensão ou advertência registrada!</p>
                    <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: #64748b;">Todos os jogadores dos seus clubes estão liberados para atuar nas competições.</p>
                </div>
            <?php else: ?>
                <div class="tbl_user_data" style="overflow-x: auto; background: #fff; border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;" id="tabelaSuspensos">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <th style="padding: 12px 16px;">Jogador</th>
                                <th style="padding: 12px 16px;">Clube</th>
                                <th style="padding: 12px 16px;">Competição</th>
                                <th style="padding: 12px 16px; text-align: center;">Cartões Amarelos</th>
                                <th style="padding: 12px 16px; text-align: center;">Status Disciplinar</th>
                                <th style="padding: 12px 16px; text-align: center;">Jogos Restantes</th>
                                <th style="padding: 12px 16px; text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jogadoresSuspensos as $js): 
                                $foto = !empty($js['jogador_foto']) ? "/images/jogadores/" . $js['jogador_foto'] : "/images/default-user.png";
                                $escudo = !empty($js['clube_escudo']) ? "/images/escudos/" . $js['clube_escudo'] : "/images/icons/shield.png";
                                $isSuspenso = ($js['suspenso'] == 1 || $js['jogos_restantes'] > 0);
                            ?>
                                <tr class="linha-desfalque" data-clube="<?php echo $js['clube_id']; ?>" style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                                    <td style="padding: 12px 16px; display: flex; align-items: center; gap: 12px;">
                                        <img src="<?php echo $foto; ?>" alt="" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0; background: #f8fafc;" onerror="this.src='/images/default-user.png';" />
                                        <div>
                                            <a href="/ligas/playerstatus.php?player=<?php echo $js['id_jogador']; ?>" style="font-weight: 600; color: #0284c7; text-decoration: none;" target="_blank">
                                                <?php echo htmlspecialchars($js['jogador_nome']); ?>
                                            </a>
                                            <div style="font-size: 0.75rem; color: #94a3b8;">Nível: <?php echo $js['jogador_nivel']; ?></div>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 16px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <img src="<?php echo $escudo; ?>" alt="" style="width: 22px; height: 22px; object-fit: contain;" onerror="this.style.display='none';" />
                                            <span style="font-weight: 500; color: #1e293b;"><?php echo htmlspecialchars($js['clube_nome']); ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 16px;">
                                        <span style="font-size: 0.8rem; background: rgba(2, 132, 199, 0.08); color: #0284c7; padding: 3px 8px; border-radius: 4px; font-weight: 600; text-transform: uppercase;">
                                            <?php echo htmlspecialchars($js['comp_ano'] . " - " . $js['comp_nome']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <?php if ($js['cartoes_amarelos'] > 0): ?>
                                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 26px; background: #fbbf24; border: 1px solid #f59e0b; border-radius: 3px; font-weight: 700; color: #78350f; font-size: 0.85rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                                <?php echo $js['cartoes_amarelos']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #cbd5e1;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <?php if ($js['is_suspenso']): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; background: rgba(220, 38, 38, 0.12); color: #dc2626;">
                                                <span class="material-symbols-outlined" style="font-size: 14px;">block</span>
                                                Suspenso
                                            </span>
                                        <?php elseif ($js['is_pendurado']): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; background: rgba(245, 158, 11, 0.15); color: #d97706;" title="Falta 1 amarelo para suspensão">
                                                <span class="material-symbols-outlined" style="font-size: 14px;">warning</span>
                                                Pendurado (<?php echo $js['cartoes_amarelos'] . '/' . $js['limite_amarelos']; ?>)
                                            </span>
                                        <?php elseif ($js['cartoes_amarelos'] > 0): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; background: rgba(100, 116, 139, 0.1); color: #475569;">
                                                <span class="material-symbols-outlined" style="font-size: 14px;">check</span>
                                                Regular (<?php echo $js['cartoes_amarelos'] . ($js['limite_amarelos'] > 0 ? '/' . $js['limite_amarelos'] : ''); ?>)
                                            </span>
                                        <?php else: ?>
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; background: rgba(16, 185, 129, 0.1); color: #059669;">
                                                <span class="material-symbols-outlined" style="font-size: 14px;">verified</span>
                                                Liberado
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <?php if ($js['jogos_restantes'] > 0): ?>
                                            <span style="font-weight: 700; color: #dc2626;">
                                                <?php echo $js['jogos_restantes']; ?> jogo(s)
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 0.85rem;">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 16px; text-align: right;">
                                        <a href="/ligas/playerstatus.php?player=<?php echo $js['id_jogador']; ?>" target="_blank" title="Ver Perfil" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 6px; background: #f1f5f9; color: #475569; text-decoration: none; border: 1px solid #cbd5e1;">
                                            <span class="material-symbols-outlined" style="font-size: 16px;">visibility</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
function filtrarTabelas() {
    var selectedClube = document.getElementById('filtroClube').value;
    var rows = document.querySelectorAll('.linha-desfalque');
    
    rows.forEach(function(row) {
        var rowClube = row.getAttribute('data-clube');
        if (selectedClube === 'todos' || selectedClube === rowClube) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php
} else {
    echo "<div style='text-align:center; padding: 50px; font-family:sans-serif;'>Acesso restrito. Faça login para continuar.</div>";
}
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
