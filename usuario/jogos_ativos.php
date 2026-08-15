<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Próximos Jogos & Escalações";
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

    // Query to pull all pending matches for the user's teams in active competitions
    $query = "
        SELECT j.id as match_id, j.competicao as comp_id, cl.nome as comp_nome, cl.ano as comp_ano,
               j.timeA_id, cA.Nome as timeA_nome, pA.dono as timeA_dono, cA.Escudo as timeA_escudo,
               j.timeB_id, cB.Nome as timeB_nome, pB.dono as timeB_dono, cB.Escudo as timeB_escudo,
               j.data, j.fase, j.grupo, f.nome as fase_nome,
               
               -- Check if time A has injured or suspended players
               (SELECT COUNT(*) FROM contratos_jogador cj 
                INNER JOIN jogador jog ON cj.jogador = jog.ID 
                LEFT JOIN competicao_suspensos cs ON cs.id_jogador = jog.ID AND cs.id_competicao = j.competicao AND cs.suspenso = 1
                WHERE cj.clube = j.timeA_id AND cj.tipoContrato = 0 
                  AND ((jog.lesionado_ate IS NOT NULL AND jog.lesionado_ate >= CURDATE()) OR cs.suspenso = 1)
               ) as desfalques_timeA,
               
               -- Check if time B has injured or suspended players
               (SELECT COUNT(*) FROM contratos_jogador cj 
                INNER JOIN jogador jog ON cj.jogador = jog.ID 
                LEFT JOIN competicao_suspensos cs ON cs.id_jogador = jog.ID AND cs.id_competicao = j.competicao AND cs.suspenso = 1
                WHERE cj.clube = j.timeB_id AND cj.tipoContrato = 0 
                  AND ((jog.lesionado_ate IS NOT NULL AND jog.lesionado_ate >= CURDATE()) OR cs.suspenso = 1)
               ) as desfalques_timeB

        FROM competicao_jogos j
        INNER JOIN competicao_lista cl ON j.competicao = cl.id
        INNER JOIN clube cA ON j.timeA_id = cA.ID
        INNER JOIN paises pA ON cA.Pais = pA.id
        INNER JOIN clube cB ON j.timeB_id = cB.ID
        INNER JOIN paises pB ON cB.Pais = pB.id
        LEFT JOIN fase f ON j.fase = f.id
        WHERE j.status = 0
          AND (pA.dono = :user_id_a OR pB.dono = :user_id_b)
        ORDER BY j.data ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':user_id_a' => $idUsuario,
        ':user_id_b' => $idUsuario
    ]);
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="redesign-container" style="max-width: 1000px; padding-top: 40px; padding-bottom: 40px;">
    <div class="propostas-card">
        <h2 class="propostas-title" style="margin-bottom: 10px !important;">Próximos Jogos & Escalações</h2>
        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 25px;">Aqui estão os próximos jogos de times sob seu comando. Jogos com desfalques ativos (jogadores suspensos ou lesionados) estão realçados em vermelho.</p>
        
        <?php if (empty($jogos)): ?>
            <div style="text-align: center; padding: 40px; color: #64748b;">
                <span class="material-symbols-outlined" style="font-size: 4rem; margin-bottom: 15px;">sports_soccer</span>
                <p>Nenhum jogo pendente encontrado para os seus times em competições ativas.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($jogos as $j): 
                    $isDonoA = ($j['timeA_dono'] == $idUsuario);
                    $isDonoB = ($j['timeB_dono'] == $idUsuario);
                    
                    $temDesfalqueA = ($isDonoA && $j['desfalques_timeA'] > 0);
                    $temDesfalqueB = ($isDonoB && $j['desfalques_timeB'] > 0);
                    $destacarJogo = ($temDesfalqueA || $temDesfalqueB);
                    
                    $cardBorder = $destacarJogo ? '1px solid rgba(239, 68, 68, 0.4)' : '1px solid rgba(0, 0, 0, 0.08)';
                    $cardBg = $destacarJogo ? 'rgba(239, 68, 68, 0.04)' : 'rgba(248, 250, 252, 0.8)';
                ?>
                    <div style="background: <?php echo $cardBg; ?>; border: <?php echo $cardBorder; ?>; border-radius: 12px; padding: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; transition: transform 0.2s ease, box-shadow 0.2s ease;"
                         onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 15px rgba(0,0,0,0.06)';"
                         onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                        
                        <!-- Left Info: Comp e Data -->
                        <div style="flex: 1; min-width: 200px;">
                            <span style="font-size: 0.75rem; background: rgba(2, 132, 199, 0.1); color: #0284c7; padding: 3px 8px; border-radius: 4px; font-weight: 600; text-transform: uppercase;">
                                <?php echo htmlspecialchars($j['comp_ano'] . " - " . $j['comp_nome']); ?>
                            </span>
                            <div style="font-size: 0.85rem; color: #64748b; margin-top: 8px;">
                                📅 <?php echo date('d/m/Y H:i', strtotime($j['data'])); ?> | 
                                🏆 <?php echo htmlspecialchars($j['fase_nome']); ?><?php echo !empty($j['grupo']) ? " (Grupo " . $j['grupo'] . ")" : ""; ?>
                            </div>
                        </div>

                        <!-- Center Info: Placar / Confronto -->
                        <div style="display: flex; align-items: center; justify-content: center; gap: 15px; flex: 2; min-width: 300px;">
                            <!-- Time A -->
                            <div style="display: flex; flex-direction: column; align-items: center; width: 120px; text-align: center;">
                                <img src="/images/escudos/<?php echo $j['timeA_escudo'] ?: '0.png'; ?>" style="height: 35px; width: auto; object-fit: contain; margin-bottom: 5px;">
                                <span style="font-weight: <?php echo $isDonoA ? 'bold' : 'normal'; ?>; color: <?php echo $isDonoA ? '#0284c7' : '#334155'; ?>; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($j['timeA_nome']); ?>
                                </span>
                                <?php if ($temDesfalqueA): ?>
                                    <span style="color: #ef4444; font-size: 0.72rem; font-weight: 600; margin-top: 4px; background: rgba(239, 68, 68, 0.1); padding: 1px 5px; border-radius: 3px;">
                                        ⚠️ <?php echo $j['desfalques_timeA']; ?> Desfalque(s)
                                    </span>
                                <?php endif; ?>
                            </div>

                            <span style="font-weight: bold; color: #64748b; font-size: 1.2rem;">VS</span>

                            <!-- Time B -->
                            <div style="display: flex; flex-direction: column; align-items: center; width: 120px; text-align: center;">
                                <img src="/images/escudos/<?php echo $j['timeB_escudo'] ?: '0.png'; ?>" style="height: 35px; width: auto; object-fit: contain; margin-bottom: 5px;">
                                <span style="font-weight: <?php echo $isDonoB ? 'bold' : 'normal'; ?>; color: <?php echo $isDonoB ? '#0284c7' : '#334155'; ?>; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($j['timeB_nome']); ?>
                                </span>
                                <?php if ($temDesfalqueB): ?>
                                    <span style="color: #ef4444; font-size: 0.72rem; font-weight: 600; margin-top: 4px; background: rgba(239, 68, 68, 0.1); padding: 1px 5px; border-radius: 3px;">
                                        ⚠️ <?php echo $j['desfalques_timeB']; ?> Desfalque(s)
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right Info: Ações de Escalação -->
                        <div style="display: flex; flex-direction: column; gap: 8px; justify-content: center; align-items: flex-end; min-width: 150px;">
                            <?php if ($isDonoA): ?>
                                <a href="/competicoes/escalacao_jogo.php?comp=<?php echo $j['comp_id']; ?>&team=<?php echo $j['timeA_id']; ?>&jogo=<?php echo $j['match_id']; ?>" 
                                   style="display: inline-flex; align-items: center; gap: 5px; background: #0284c7; color: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: background 0.2s;"
                                   onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">
                                    📋 Escalar <?php echo htmlspecialchars($j['timeA_nome']); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($isDonoB): ?>
                                <a href="/competicoes/escalacao_jogo.php?comp=<?php echo $j['comp_id']; ?>&team=<?php echo $j['timeB_id']; ?>&jogo=<?php echo $j['match_id']; ?>" 
                                   style="display: inline-flex; align-items: center; gap: 5px; background: #0284c7; color: #fff; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: background 0.2s;"
                                   onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">
                                    📋 Escalar <?php echo htmlspecialchars($j['timeB_nome']); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="index.php" style="display: inline-block; padding: 10px 20px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;"
               onmouseover="this.style.background='rgba(0, 0, 0, 0.06)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.03)'">
                ← Voltar para Minha Área
            </a>
        </div>
    </div>
</main>

<?php
} else {
    echo "<div style='padding:50px; color:#fff;'>Por favor faça o login para acessar esta página.</div>";
}
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
