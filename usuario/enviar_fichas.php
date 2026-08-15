<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Envio de Fichas - ".($_SESSION['nomereal'] ?? '');
$css_filename = "home_redesign";
$aux_css = "fichas_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");

$database = new Database();
$db = $database->getConnection();

$idUsuario = $_SESSION['user_id'];

// Consulta para buscar vagas pendentes de envio de fichas
$query_pending_rosters = "
    SELECT 
        c.id AS competicao_id, 
        c.nome AS competicao_nome, 
        c.ano AS competicao_ano, 
        c.logo AS competicao_logo,
        ct.codigo_time, 
        p.id AS pais_id,
        p.nome AS pais_nome, 
        p.bandeira AS pais_bandeira,
        co.limite_fichas,
        DATEDIFF(co.limite_fichas, CURDATE()) AS dias_restantes
    FROM competicao_times ct
    INNER JOIN competicao_lista c ON ct.id_competicao = c.id
    INNER JOIN competicao_opcoes co ON c.id = co.id_competicao
    INNER JOIN paises p ON ct.pais_time = p.id
    WHERE p.dono = ? 
      AND (ct.has_team IS NULL OR ct.has_team <> '1')
      AND (co.limite_fichas >= CURDATE() OR co.limite_fichas IS NULL OR co.limite_fichas = '0000-00-00' OR co.limite_fichas = '')
    ORDER BY co.limite_fichas IS NULL ASC, co.limite_fichas ASC
";
$stmt_pending = $db->prepare($query_pending_rosters);
$stmt_pending->execute([$idUsuario]);
$pending_rosters = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

$countries_with_pending = [];
foreach ($pending_rosters as $pr) {
    $countries_with_pending[$pr['pais_id']] = true;
}
$clubs_by_country = [];
if (!empty($countries_with_pending)) {
    foreach (array_keys($countries_with_pending) as $paisId) {
        $query_clubs = "SELECT ID, Nome, Escudo FROM clube WHERE Pais = ? ORDER BY Nome ASC";
        $stmt_clubs = $db->prepare($query_clubs);
        $stmt_clubs->execute([$paisId]);
        $clubs_by_country[$paisId] = $stmt_clubs->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<main class="propostas-container">
    <div id='errorbox'></div>

    <div class="propostas-card" style="margin-bottom: 2rem;">
        <h2 class="propostas-title">Quadro de envio de fichas - <?php echo $_SESSION['nomereal']?></h2>
        <h4 class="drawer-title">Fichas Pendentes</h4>
        <?php if (count($pending_rosters) > 0): ?>
            <div class="pending-rosters-list">
                <?php foreach ($pending_rosters as $pr): ?>
                    <?php 
                    $has_deadline = !empty($pr['limite_fichas']) && $pr['limite_fichas'] !== '0000-00-00';
                    $is_urgent = $has_deadline && ($pr['dias_restantes'] < 7);
                    if ($has_deadline) {
                        $dias_text = $pr['dias_restantes'] == 1 ? "1 dia" : $pr['dias_restantes'] . " dias";
                        if ($pr['dias_restantes'] == 0) $dias_text = "Hoje!";
                    } else {
                        $dias_text = "Sem prazo";
                    }
                    ?>
                    <div class="pending-roster-item <?php echo $is_urgent ? 'item-urgent' : ''; ?>" data-comp-id="<?php echo $pr['competicao_id']; ?>" data-slot-id="<?php echo $pr['codigo_time']; ?>" data-pais-id="<?php echo $pr['pais_id']; ?>">
                        <div class="roster-info">
                            <img class="comp-logo" src="/images/competicoes/<?php echo $pr['competicao_logo'] ?: 'flag.png'; ?>" alt="Comp Logo" onerror="this.src='/images/competicoes/flag.png';" />
                            <div class="roster-meta">
                                <strong><?php echo htmlspecialchars($pr['competicao_nome'] . " " . $pr['competicao_ano']); ?></strong>
                                <span class="roster-country">
                                    <img src="/images/bandeiras/<?php echo $pr['pais_bandeira']; ?>" class="bandeira-inline" onerror="this.src='/images/bandeiras/flag.png';" />
                                    <?php echo htmlspecialchars($pr['pais_nome']); ?> (Vaga #<?php echo $pr['codigo_time']; ?>)
                                </span>
                            </div>
                        </div>
                        <div class="roster-deadline">
                            <span class="deadline-label">
                                <?php if ($has_deadline): ?>
                                    Prazo: <?php echo date('d/m/Y', strtotime($pr['limite_fichas'])); ?>
                                <?php else: ?>
                                    Sem prazo
                                <?php endif; ?>
                            </span>
                            <?php if ($has_deadline): ?>
                                <span class="days-remaining <?php echo $is_urgent ? 'text-urgent' : ''; ?>">
                                    <?php echo $dias_text; ?> restante<?php echo $pr['dias_restantes'] != 1 ? 's' : ''; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="roster-actions">
                            <div class="action-select-container">
                                <select class="select-portal-time">
                                    <option value="0">Selecionar time do portal...</option>
                                    <?php 
                                    $clubs = $clubs_by_country[$pr['pais_id']] ?? [];
                                    foreach ($clubs as $club) {
                                        echo "<option value='{$club['ID']}'>" . htmlspecialchars($club['Nome']) . "</option>";
                                    }
                                    ?>
                                </select>
                                <button class="btn-save-portal">Enviar</button>
                            </div>
                            <div class="action-divider">ou</div>
                            <div class="action-upload-container">
                                <label class="btn-upload-ymt">
                                    Importar .ymt
                                    <input type="file" class="input-ymt-file" accept=".ymt" style="display: none;" />
                                </label>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: #475569; font-family: 'Montserrat', sans-serif;">Nenhuma ficha pendente de envio para seus países.</p>
        <?php endif; ?>
        <div style="margin-top: 30px;">
            <a href="index.php" style="display: inline-block; padding: 10px 20px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;"
               onmouseover="this.style.background='rgba(0, 0, 0, 0.06)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.03)'">
                ← Voltar para Minha Área
            </a>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    // Enviar time do portal
    $(document).on('click', '.btn-save-portal', function() {
        var item = $(this).closest('.pending-roster-item');
        var compId = item.data('comp-id');
        var slotId = item.data('slot-id');
        var paisId = item.data('pais-id');
        var timePortal = item.find('.select-portal-time').val();

        if (timePortal == "0") {
            alert("Selecione um time do portal.");
            return;
        }

        var formData = new FormData();
        formData.append('tipo_alteracao', 1);
        formData.append('codigo_competicao', compId);
        formData.append('codigo_time', slotId);
        formData.append('pais_time', paisId);
        formData.append('time_portal', timePortal);

        $.ajax({
            type: 'POST',
            url: '/competicoes/alterar_times_competicao.php',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            cache: false
        }).done(function(response) {
            if (response.success) {
                item.fadeOut(300, function() {
                    $(this).remove();
                    checkEmptyList();
                });
            } else {
                alert("Erro ao salvar: " + (response.errors || response.error || "Erro desconhecido"));
            }
        }).fail(function() {
            alert("Erro não esperado ao salvar.");
        });
    });

    // Upload de arquivo .ymt
    $(document).on('change', '.input-ymt-file', function() {
        var item = $(this).closest('.pending-roster-item');
        var compId = item.data('comp-id');
        var slotId = item.data('slot-id');
        var paisId = item.data('pais-id');
        var file = this.files[0];
        if (!file) return;

        var formData = new FormData();
        formData.append('files', file);
        formData.append('codigo_time', slotId);
        formData.append('id_competicao', compId);
        formData.append('pais_time', paisId);

        $.ajax({
            type: 'POST',
            url: '/competicoes/importar_time_ymt.php',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            cache: false
        }).done(function(response) {
            if (response.success) {
                item.fadeOut(300, function() {
                    $(this).remove();
                    checkEmptyList();
                });
            } else {
                alert("Erro ao importar: " + (response.error || "Erro desconhecido"));
            }
        }).fail(function() {
            alert("Erro não esperado durante o upload.");
        });
    });

    function checkEmptyList() {
        if ($('.pending-roster-item').length === 0) {
            $('.pending-rosters-list').replaceWith('<p style="color: #475569; font-family: \'Montserrat\', sans-serif;">Nenhuma ficha pendente de envio para seus países.</p>');
        }
    }
});
</script>

<?php
} else {
    echo "<main class='redesign-container'><p>Usuário, por favor refaça o login.</p></main>";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
