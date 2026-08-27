<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die("Acesso negado. Por favor faça o login.");
}

$is_admin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == '1' && $_SESSION['impersonated'] == false);
if (!$is_admin) {
    die("Acesso negado. Apenas administradores podem utilizar o assistente de associação.");
}

if (!isset($_SESSION['pending_import']) || empty($_SESSION['pending_import'])) {
    header("Location: /jogadores/importar_jogador.php");
    exit;
}

$pending = $_SESSION['pending_import'];
$players = $pending['players'];
$type = $pending['type']; // 1 = player, 2 = team

// Include headers
$page_title = "Associação de Jogadores Importados";
$css_filename = "home_redesign";
$aux_css = "arbitros_redesign";
$css_versao = date('h:i:s');

include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/header.php");
echo '<link rel="stylesheet" href="/css/associar_jogadores.css?v=' . $css_versao . '">';

$target_liga_name = "";
if (!empty($pending['liga'])) {
    $stmt_l = $db->prepare("SELECT Nome FROM liga WHERE ID = ?");
    $stmt_l->execute([$pending['liga']]);
    $target_liga_name = $stmt_l->fetchColumn();
}

$target_clube_name = "";
if (!empty($pending['time'])) {
    $stmt_tc = $db->prepare("SELECT Nome FROM clube WHERE ID = ?");
    $stmt_tc->execute([$pending['time']]);
    $target_clube_name = $stmt_tc->fetchColumn();
}
?>

<main class="propostas-container narrow-container" style="padding-top: 80px; padding-bottom: 60px;">
    <div class="propostas-card">
        <div class="wizard-header" style="border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 1.5rem; margin-bottom: 2rem;">
            <h1 style="margin: 0 0 10px 0; font-family: 'Kanit', sans-serif; font-size: 1.8rem; color: #1e293b; font-weight: 700;">📥 Associação de Jogadores</h1>
            <p style="margin: 0; color: #64748b; font-size: 1rem;">Encontramos possíveis correspondências no banco de dados. Escolha se deseja atualizar um jogador existente (preservando o histórico de gols, jogos e transferências dele) ou criar um novo registro.</p>
        </div>

        <form action="/jogadores/processar_associacao.php" method="POST" id="wizard-form">
            <?php 
            $team_matches = isset($pending['team_matches']) ? $pending['team_matches'] : null;
            if ($team_matches): 
                foreach (['clube' => 'Time', 'tecnico' => 'Técnico', 'estadio' => 'Estádio'] as $key => $label):
                    $nome = htmlspecialchars($team_matches[$key]['nome']);
                    $matches = $team_matches[$key]['matches'];
                    $field_id = "team_assoc_" . $key;
            ?>
                <div class="player-card" data-xml-index="<?php echo $field_id; ?>" style="background: rgba(2, 132, 199, 0.04); border-color: rgba(2, 132, 199, 0.15);">
                    <div class="player-info">
                        <h3 class="player-name"><?php echo $nome; ?></h3>
                        <div class="player-meta">
                            <span style="background: #0284c7; color: #fff;"><?php echo $label; ?></span>
                        </div>
                    </div>

                    <div class="player-actions">
                        <input type="hidden" name="team_associations[<?php echo $key; ?>][action]" id="action-<?php echo $field_id; ?>" value="<?php echo empty($matches) ? 'new' : 'match'; ?>">
                        
                        <div class="choice-group">
                            <button type="button" class="btn-choice <?php echo empty($matches) ? 'active-new' : ''; ?>" onclick="setPlayerAction('<?php echo $field_id; ?>', 'new')">
                                Criar como Novo
                            </button>
                            <button type="button" class="btn-choice <?php echo !empty($matches) ? 'active-match' : ''; ?>" onclick="setPlayerAction('<?php echo $field_id; ?>', 'match')">
                                Aproveitar Existente
                            </button>
                        </div>

                        <div class="match-selector-wrapper <?php echo !empty($matches) ? 'active' : ''; ?>" id="wrapper-<?php echo $field_id; ?>">
                            <select name="team_associations[<?php echo $key; ?>][player_id]" class="match-select" onchange="checkDivergence('<?php echo $field_id; ?>', '<?php echo $key; ?>')">
                                <?php if (!empty($matches)): ?>
                                    <?php foreach ($matches as $m): ?>
                                        <?php if ($key === 'clube'): ?>
                                            <option value="<?php echo $m['ID']; ?>" data-liga-id="<?php echo $m['liga']; ?>" data-liga-nome="<?php echo htmlspecialchars($m['NomeLiga'] ?? 'Nenhuma'); ?>">
                                                <?php echo htmlspecialchars($m['Nome']); ?> (ID: <?php echo $m['ID']; ?>)
                                            </option>
                                        <?php elseif ($key === 'tecnico'): ?>
                                            <option value="<?php echo $m['ID']; ?>" data-clube-id="<?php echo $m['idClube']; ?>" data-clube-nome="<?php echo htmlspecialchars($m['NomeClube'] ?? 'Sem Clube'); ?>" data-nivel="<?php echo $m['Nivel']; ?>" data-idade="<?php echo $m['Idade']; ?>" data-sexo="<?php echo $m['Sexo']; ?>">
                                                <?php echo htmlspecialchars($m['Nome']); ?> (ID: <?php echo $m['ID']; ?>)
                                            </option>
                                        <?php else: ?>
                                            <option value="<?php echo $m['ID']; ?>">
                                                <?php echo htmlspecialchars($m['Nome']); ?> (ID: <?php echo $m['ID']; ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">Nenhuma sugestão encontrada</option>
                                <?php endif; ?>
                            </select>
                            <div class="divergence-warning" id="warning-<?php echo $field_id; ?>" style="margin-top: 8px; font-size: 0.85rem; color: #b45309; display: none; padding: 6px 10px; background: rgba(217, 119, 6, 0.1); border-radius: 4px; border: 1px solid rgba(217, 119, 6, 0.2);"></div>
                        </div>
                    </div>
                </div>
            <?php 
                endforeach;
            endif;
            ?>

            <?php foreach ($players as $p): ?>
                <?php 
                $xml_index = $p['xml_index'];
                $nome = htmlspecialchars($p['nome']);
                $idade = $p['idade'];
                $nivel = $p['nivel'];
                $matches = $p['matches'];
                ?>
                <div class="player-card" data-xml-index="<?php echo $xml_index; ?>" data-xml-nivel="<?php echo $nivel; ?>" data-xml-idade="<?php echo $idade; ?>">
                    <div class="player-info">
                        <h3 class="player-name"><?php echo $nome; ?></h3>
                        <div class="player-meta">
                            <span>Idade: <?php echo $idade; ?></span>
                            <span>Nível: <?php echo $nivel; ?></span>
                        </div>
                    </div>

                    <div class="player-actions">
                        <input type="hidden" name="associations[<?php echo $xml_index; ?>][action]" id="action-<?php echo $xml_index; ?>" value="<?php echo empty($matches) ? 'new' : 'match'; ?>">
                        
                        <div class="choice-group">
                            <button type="button" class="btn-choice <?php echo empty($matches) ? 'active-new' : ''; ?>" onclick="setPlayerAction(<?php echo $xml_index; ?>, 'new')">
                                Criar como Novo
                            </button>
                            <button type="button" class="btn-choice <?php echo !empty($matches) ? 'active-match' : ''; ?>" onclick="setPlayerAction(<?php echo $xml_index; ?>, 'match')">
                                Aproveitar Existente
                            </button>
                        </div>

                        <div class="match-selector-wrapper <?php echo !empty($matches) ? 'active' : ''; ?>" id="wrapper-<?php echo $xml_index; ?>" style="display: flex; flex-direction: column; align-items: flex-start; gap: 8px; width: 100%;">
                            <div style="display: flex; align-items: center; gap: 8px; width: 100%;">
                                <?php 
                                $default_flag = '';
                                if (!empty($matches)) {
                                    $default_flag = $matches[0]['Bandeira'];
                                }
                                ?>
                                <img class="select-flag-preview" src="<?php echo $default_flag ? '/images/bandeiras/' . $default_flag : '/images/bandeiras/default.png'; ?>" style="width: 20px; height: 14px; border: 1px solid rgba(0,0,0,0.08); border-radius: 2px; <?php echo !$default_flag ? 'display: none;' : ''; ?>">
                                <select name="associations[<?php echo $xml_index; ?>][player_id]" class="match-select" onchange="updateSelectFlag(this); checkDivergence('<?php echo $xml_index; ?>', 'jogador')">
                                    <?php if (!empty($matches)): ?>
                                        <?php foreach ($matches as $m): ?>
                                            <option value="<?php echo $m['ID']; ?>" data-flag="<?php echo $m['Bandeira']; ?>" data-clube-id="<?php echo $m['idClube']; ?>" data-clube-nome="<?php echo htmlspecialchars($m['NomeClube'] ?? 'Sem Clube'); ?>" data-nivel="<?php echo $m['Nivel']; ?>" data-idade="<?php echo $m['Idade']; ?>" data-sexo="<?php echo $m['Sexo']; ?>">
                                                <?php echo htmlspecialchars($m['Nome']); ?> (Nível: <?php echo $m['Nivel']; ?><?php echo !empty($m['NomePais']) ? ' - ' . htmlspecialchars($m['NomePais']) : ''; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="">Nenhuma sugestão encontrada</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="divergence-warning" id="warning-<?php echo $xml_index; ?>" style="margin-top: 8px; font-size: 0.85rem; color: #b45309; display: none; padding: 6px 10px; background: rgba(217, 119, 6, 0.1); border-radius: 4px; border: 1px solid rgba(217, 119, 6, 0.2); width: 100%;"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="wizard-footer">
                <button type="button" class="btn-cancel" onclick="window.location.href='/jogadores/importar_jogador.php'">
                    Cancelar
                </button>
                <button type="submit" class="btn-submit">
                    Finalizar Importação
                </button>
            </div>
        </form>
    </div>
</main>

<script>
const targetLigaId = "<?php echo (int)($pending['liga'] ?? 0); ?>";
const targetLigaName = "<?php echo htmlspecialchars($target_liga_name); ?>";
const targetClubName = "<?php echo htmlspecialchars($target_clube_name ?? $team_matches['clube']['nome'] ?? ''); ?>";

function setPlayerAction(xmlIndex, action) {
    const input = document.getElementById('action-' + xmlIndex);
    const wrapper = document.getElementById('wrapper-' + xmlIndex);
    
    input.value = action;
    
    // Toggle active classes
    const card = document.querySelector(`.player-card[data-xml-index="${xmlIndex}"]`);
    const buttons = card.querySelectorAll('.btn-choice');
    
    buttons[0].classList.remove('active-new');
    buttons[1].classList.remove('active-match');
    
    if (action === 'new') {
        buttons[0].classList.add('active-new');
        wrapper.classList.remove('active');
    } else {
        buttons[1].classList.add('active-match');
        wrapper.classList.add('active');
    }
}

function updateSelectFlag(select) {
    const option = select.options[select.selectedIndex];
    const flag = option.getAttribute('data-flag');
    const wrapper = select.parentElement;
    const img = wrapper.querySelector('.select-flag-preview');
    if (img) {
        if (flag) {
            img.src = '/images/bandeiras/' + flag;
            img.style.display = 'inline-block';
        } else {
            img.style.display = 'none';
        }
    }
}

const targetSexo = "<?php echo (int)($pending['sexo'] ?? 0); ?>";
const coachXmlIdade = <?php echo (int)($team_matches['tecnico']['idade'] ?? 0); ?>;
const coachXmlNivel = <?php echo (int)($team_matches['tecnico']['nivel'] ?? 0); ?>;

function checkDivergence(fieldId, key) {
    const wrapper = document.getElementById('wrapper-' + fieldId);
    const select = wrapper.querySelector('select');
    const warningDiv = document.getElementById('warning-' + fieldId);
    if (!select || !warningDiv) return;
    
    const option = select.options[select.selectedIndex];
    if (!option || option.value === "") {
        warningDiv.style.display = 'none';
        return;
    }
    
    let warningMsg = "";
    const card = document.querySelector(`.player-card[data-xml-index="${fieldId}"]`);
    
    // Check gender mismatch (sexo: 0 = Male, 1 = Female)
    const optionSexo = option.getAttribute('data-sexo');
    if (optionSexo !== null && targetSexo !== null && optionSexo !== targetSexo) {
        const dbGender = optionSexo === '1' ? 'Feminino' : 'Masculino';
        const importGender = targetSexo === '1' ? 'Feminino' : 'Masculino';
        warningMsg += `⚠️ <strong>Divergência Crítica (Gênero):</strong> O perfil no banco é de gênero <strong>${dbGender}</strong>, mas você está importando como <strong>${importGender}</strong>.<br>`;
    }
    
    if (key === 'clube') {
        const ligaId = option.getAttribute('data-liga-id');
        const ligaNome = option.getAttribute('data-liga-nome');
        if (ligaId && targetLigaId && ligaId !== targetLigaId) {
            warningMsg += `⚠️ <strong>Divergência Crítica (Liga):</strong> Este clube está atualmente cadastrado na liga <strong>"${ligaNome}"</strong>, mas você está importando na liga <strong>"${targetLigaName}"</strong>.<br>`;
        }
    } else if (key === 'tecnico') {
        const clubeNome = option.getAttribute('data-clube-nome');
        if (clubeNome && targetClubName && clubeNome.toLowerCase().trim() !== targetClubName.toLowerCase().trim()) {
            warningMsg += `⚠️ <strong>Divergência Crítica (Vínculo):</strong> Este técnico está vinculado ao clube <strong>"${clubeNome}"</strong>, mas você está importando no clube <strong>"${targetClubName}"</strong>.<br>`;
        }
        
        // Age and level warning
        const optIdade = parseInt(option.getAttribute('data-idade'));
        const optNivel = parseInt(option.getAttribute('data-nivel'));
        if (coachXmlIdade && optIdade && Math.abs(optIdade - coachXmlIdade) >= 2) {
            warningMsg += `⚠️ <strong>Aviso de Idade:</strong> A idade no banco (${optIdade} anos) difere do arquivo (${coachXmlIdade} anos). Verifique se é a mesma pessoa.<br>`;
        }
        if (coachXmlNivel && optNivel && Math.abs(optNivel - coachXmlNivel) > 5) {
            warningMsg += `⚠️ <strong>Aviso de Nível:</strong> O nível no banco (${optNivel}) difere significativamente do arquivo (${coachXmlNivel}).<br>`;
        }
    } else if (key === 'jogador') {
        const clubeNome = option.getAttribute('data-clube-nome');
        if (clubeNome && targetClubName && clubeNome.toLowerCase().trim() !== targetClubName.toLowerCase().trim()) {
            warningMsg += `⚠️ <strong>Divergência Crítica (Vínculo):</strong> Este jogador está atualmente vinculado ao clube <strong>"${clubeNome}"</strong>, mas será transferido para o clube <strong>"${targetClubName}"</strong>.<br>`;
        }
        
        // Age and level warning
        const optIdade = parseInt(option.getAttribute('data-idade'));
        const optNivel = parseInt(option.getAttribute('data-nivel'));
        const xmlIdade = card ? parseInt(card.getAttribute('data-xml-idade')) : 0;
        const xmlNivel = card ? parseInt(card.getAttribute('data-xml-nivel')) : 0;
        
        if (xmlIdade && optIdade && Math.abs(optIdade - xmlIdade) >= 2) {
            warningMsg += `⚠️ <strong>Aviso de Idade:</strong> A idade no banco (${optIdade} anos) difere do arquivo (${xmlIdade} anos). Verifique se é o mesmo jogador.<br>`;
        }
        if (xmlNivel && optNivel && Math.abs(optNivel - xmlNivel) > 5) {
            warningMsg += `⚠️ <strong>Aviso de Nível:</strong> O nível no banco (${optNivel}) difere significativamente do arquivo (${xmlNivel}).<br>`;
        }
    }
    
    // Remove trailing <br>
    if (warningMsg.endsWith('<br>')) {
        warningMsg = warningMsg.substring(0, warningMsg.length - 4);
    }
    
    if (warningMsg) {
        warningDiv.innerHTML = warningMsg;
        warningDiv.style.display = 'block';
    } else {
        warningDiv.style.display = 'none';
    }
}

// Trigger check on page load
document.addEventListener("DOMContentLoaded", function() {
    // Check team associations
    <?php if ($team_matches): ?>
        <?php foreach (['clube', 'tecnico', 'estadio'] as $key): ?>
            checkDivergence('team_assoc_<?php echo $key; ?>', '<?php echo $key; ?>');
        <?php endforeach; ?>
    <?php endif; ?>
    
    // Check players
    <?php foreach ($players as $p): ?>
        checkDivergence('<?php echo $p['xml_index']; ?>', 'jogador');
    <?php endforeach; ?>
});
</script>

<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/footer.php");
?>
