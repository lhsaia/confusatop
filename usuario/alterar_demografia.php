<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$pais->id = isset($_GET['idPais']) ? (int)$_GET['idPais'] : 0;

$pais->readName();

$nomePagina = 'Demografia - ' . htmlspecialchars($pais->nome ?? '');
$stmt = $pais->demografias();

$num = $stmt->rowCount();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = $nomePagina;
$css_filename = "home_redesign";
$aux_css = "demografia_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true && $pais->checarDono($pais->id,$_SESSION['user_id'])){

    $somaPercentualNome = 0;
    $somaPercentualSobrenome = 0;
    $arrayNomes = array();
    $arraySobrenomes = array();
    $todasOrigens = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        extract($row);

        if(!isset($todasOrigens[$idOrigem])){
            $todasOrigens[$idOrigem] = $origem;
        }

        if($nomeOuSobrenome == 10){
            $somaPercentualNome += $fatorPercentual;
            $arrayNomes[] = [
                'origem' => $origem, 
                'fatorPercentual' => $fatorPercentual, 
                'idOrigem' => $idOrigem, 
                'ocorrenciaNomeDuplo' => $ocorrenciaNomeDuplo, 
                'indiceMiscigenacao' => $indiceMiscigenacao
            ];
        } else if($nomeOuSobrenome == 1){
            $somaPercentualSobrenome += $fatorPercentual;
            $arraySobrenomes[] = [
                'origem' => $origem, 
                'fatorPercentual' => $fatorPercentual, 
                'idOrigem' => $idOrigem
            ];
        } else {
            $somaPercentualNome += $fatorPercentual;
            $somaPercentualSobrenome += $fatorPercentual;
            $arrayNomes[] = [
                'origem' => $origem, 
                'fatorPercentual' => $fatorPercentual, 
                'idOrigem' => $idOrigem, 
                'ocorrenciaNomeDuplo' => $ocorrenciaNomeDuplo, 
                'indiceMiscigenacao' => $indiceMiscigenacao
            ];
            $arraySobrenomes[] = [
                'origem' => $origem, 
                'fatorPercentual' => $fatorPercentual, 
                'idOrigem' => $idOrigem
            ];
        }
    }

    // Mapa de cores consistente por ID de origem
    $colorPalette = [
        '#0284c7', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899',
        '#06b6d4', '#f97316', '#6366f1', '#14b8a6', '#84cc16',
        '#e11d48', '#a855f7', '#d97706', '#3b82f6', '#059669'
    ];
    $originColors = [];
    $cIdx = 0;
    foreach($todasOrigens as $idO => $nameO){
        $originColors[$idO] = $colorPalette[$cIdx % count($colorPalette)];
        $cIdx++;
    }
?>

<div class="demo-container">
    <!-- Header Card -->
    <div class="demo-header-card">
        <div class="demo-breadcrumb">
            <a href="/usuario/meuspaises.php">Meus Países</a>
            <span class="material-symbols-outlined" style="font-size: 14px;">chevron_right</span>
            <span>Demografia de <?php echo htmlspecialchars($pais->nome ?? ''); ?></span>
        </div>
        
        <div class="demo-header-top">
            <div class="demo-title-area">
                <h1>
                    <span class="material-symbols-outlined" style="font-size: 32px; color: #0284c7;">public</span>
                    Demografia - <?php echo htmlspecialchars($pais->nome ?? ''); ?>
                </h1>
            </div>
            
            <div class="demo-actions">
                <a href="/usuario/meuspaises.php" class="btn-demo-secondary">
                    <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span>
                    Voltar aos Países
                </a>
                <a href="/usuario/criar_demografia.php?idTime=<?php echo (int)$pais->id; ?>" class="btn-demo-primary">
                    <span class="material-symbols-outlined" style="font-size: 20px;">add_circle</span>
                    Inserir Fatia Demográfica
                </a>
            </div>
        </div>

        <!-- Banner Informativo -->
        <div class="demo-info-banner">
            <span class="material-symbols-outlined">info</span>
            <div class="demo-info-content">
                <strong>Como funciona a geração de nomes:</strong> A demografia define a probabilidade cultural ao sortear novos atletas nascidos neste país. A composição é normalizada em 100% de forma proporcional aos pesos. O <strong>Índice de Miscigenação</strong> determina a chance de cruzar o primeiro nome de uma cultura com o sobrenome de outra.
            </div>
        </div>
    </div>

    <div id="error_box"></div>

    <?php if($num > 0): ?>
    <!-- Grid com Seções Separadas de Nomes e Sobrenomes -->
    <div class="demo-grid">

        <!-- ================= CARD 1: NOMES ================= -->
        <div class="demo-section-card">
            <div class="demo-section-header">
                <div class="demo-section-title">
                    <span class="material-symbols-outlined" style="color: #0284c7; font-size: 24px;">badge</span>
                    <h2>Nomes Próprios</h2>
                </div>
                <span class="demo-count-badge"><?php echo count($arrayNomes); ?> <?php echo count($arrayNomes) == 1 ? 'origem' : 'origens'; ?></span>
            </div>

            <?php if(count($arrayNomes) > 0): ?>
                <!-- Barra de Composição Empilhada -->
                <div class="demo-stacked-bar-container">
                    <div class="demo-stacked-bar-label">
                        <span>Composição Visual dos Nomes</span>
                        <span>100%</span>
                    </div>
                    <div class="demo-stacked-bar">
                        <?php foreach($arrayNomes as $itemNome): 
                            $perc = ($somaPercentualNome > 0) ? round(($itemNome['fatorPercentual'] / $somaPercentualNome) * 100, 1) : 0;
                            $cor = $originColors[$itemNome['idOrigem']] ?? '#0284c7';
                        ?>
                            <div class="demo-bar-segment" style="width: <?php echo $perc; ?>%; background-color: <?php echo $cor; ?>;" title="<?php echo htmlspecialchars($itemNome['origem']); ?>: <?php echo $perc; ?>%"></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="demo-bar-legend">
                        <?php foreach($arrayNomes as $itemNome): 
                            $perc = ($somaPercentualNome > 0) ? round(($itemNome['fatorPercentual'] / $somaPercentualNome) * 100, 1) : 0;
                            $cor = $originColors[$itemNome['idOrigem']] ?? '#0284c7';
                        ?>
                            <div class="demo-legend-item">
                                <span class="demo-legend-color" style="background-color: <?php echo $cor; ?>;"></span>
                                <span><?php echo htmlspecialchars($itemNome['origem']); ?> (<?php echo $perc; ?>%)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tabela de Nomes -->
                <div class="demo-table-wrapper">
                    <table class="demo-table">
                        <thead>
                            <tr>
                                <th>Origem</th>
                                <th>Frequência</th>
                                <th class="text-center">Nome Duplo</th>
                                <th class="text-center">Miscigenação</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($arrayNomes as $itemNome): 
                                $perc = ($somaPercentualNome > 0) ? round(($itemNome['fatorPercentual'] / $somaPercentualNome) * 100, 1) : 0;
                                $cor = $originColors[$itemNome['idOrigem']] ?? '#0284c7';
                            ?>
                                <tr>
                                    <td>
                                        <div class="demo-origin-cell">
                                            <span class="demo-origin-dot" style="background-color: <?php echo $cor; ?>;"></span>
                                            <span class="demo-origin-name"><?php echo htmlspecialchars($itemNome['origem']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="demo-perc-cell">
                                            <span class="demo-perc-val"><?php echo $perc; ?>%</span>
                                            <div class="demo-perc-track">
                                                <div class="demo-perc-fill" style="width: <?php echo min(100, $perc); ?>%; background-color: <?php echo $cor; ?>;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="demo-badge-metric <?php echo $itemNome['ocorrenciaNomeDuplo'] > 0 ? 'accent-blue' : 'accent-neutral'; ?>">
                                            <?php echo (int)$itemNome['ocorrenciaNomeDuplo']; ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="demo-badge-metric <?php echo $itemNome['indiceMiscigenacao'] > 0 ? 'accent-emerald' : 'accent-neutral'; ?>">
                                            <?php echo (int)$itemNome['indiceMiscigenacao']; ?>%
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <button type="button" class="btn-demo-delete apagar-demografia" data-origem="<?php echo (int)$itemNome['idOrigem']; ?>" data-nome="<?php echo htmlspecialchars($itemNome['origem']); ?>" title="Excluir fatia">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="demo-empty-state">
                    <span class="material-symbols-outlined">person_off</span>
                    <p>Nenhuma origem cadastrada para nomes próprios.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ================= CARD 2: SOBRENOMES ================= -->
        <div class="demo-section-card">
            <div class="demo-section-header">
                <div class="demo-section-title">
                    <span class="material-symbols-outlined" style="color: #8b5cf6; font-size: 24px;">family_restroom</span>
                    <h2>Sobrenomes</h2>
                </div>
                <span class="demo-count-badge" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6; border-color: rgba(139, 92, 246, 0.2);"><?php echo count($arraySobrenomes); ?> <?php echo count($arraySobrenomes) == 1 ? 'origem' : 'origens'; ?></span>
            </div>

            <?php if(count($arraySobrenomes) > 0): ?>
                <!-- Barra de Composição Empilhada -->
                <div class="demo-stacked-bar-container">
                    <div class="demo-stacked-bar-label">
                        <span>Composição Visual dos Sobrenomes</span>
                        <span>100%</span>
                    </div>
                    <div class="demo-stacked-bar">
                        <?php foreach($arraySobrenomes as $itemSobrenome): 
                            $perc = ($somaPercentualSobrenome > 0) ? round(($itemSobrenome['fatorPercentual'] / $somaPercentualSobrenome) * 100, 1) : 0;
                            $cor = $originColors[$itemSobrenome['idOrigem']] ?? '#8b5cf6';
                        ?>
                            <div class="demo-bar-segment" style="width: <?php echo $perc; ?>%; background-color: <?php echo $cor; ?>;" title="<?php echo htmlspecialchars($itemSobrenome['origem']); ?>: <?php echo $perc; ?>%"></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="demo-bar-legend">
                        <?php foreach($arraySobrenomes as $itemSobrenome): 
                            $perc = ($somaPercentualSobrenome > 0) ? round(($itemSobrenome['fatorPercentual'] / $somaPercentualSobrenome) * 100, 1) : 0;
                            $cor = $originColors[$itemSobrenome['idOrigem']] ?? '#8b5cf6';
                        ?>
                            <div class="demo-legend-item">
                                <span class="demo-legend-color" style="background-color: <?php echo $cor; ?>;"></span>
                                <span><?php echo htmlspecialchars($itemSobrenome['origem']); ?> (<?php echo $perc; ?>%)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tabela de Sobrenomes -->
                <div class="demo-table-wrapper">
                    <table class="demo-table">
                        <thead>
                            <tr>
                                <th>Origem</th>
                                <th>Frequência</th>
                                <th class="text-center">Peso Bruto</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($arraySobrenomes as $itemSobrenome): 
                                $perc = ($somaPercentualSobrenome > 0) ? round(($itemSobrenome['fatorPercentual'] / $somaPercentualSobrenome) * 100, 1) : 0;
                                $cor = $originColors[$itemSobrenome['idOrigem']] ?? '#8b5cf6';
                            ?>
                                <tr>
                                    <td>
                                        <div class="demo-origin-cell">
                                            <span class="demo-origin-dot" style="background-color: <?php echo $cor; ?>;"></span>
                                            <span class="demo-origin-name"><?php echo htmlspecialchars($itemSobrenome['origem']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="demo-perc-cell">
                                            <span class="demo-perc-val"><?php echo $perc; ?>%</span>
                                            <div class="demo-perc-track">
                                                <div class="demo-perc-fill" style="width: <?php echo min(100, $perc); ?>%; background-color: <?php echo $cor; ?>;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="demo-badge-metric accent-neutral">
                                            <?php echo (int)$itemSobrenome['fatorPercentual']; ?> pts
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <button type="button" class="btn-demo-delete apagar-demografia" data-origem="<?php echo (int)$itemSobrenome['idOrigem']; ?>" data-nome="<?php echo htmlspecialchars($itemSobrenome['origem']); ?>" title="Excluir fatia">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="demo-empty-state">
                    <span class="material-symbols-outlined">group_off</span>
                    <p>Nenhuma origem cadastrada para sobrenomes.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
    <?php else: ?>
        <div class="demo-section-card" style="text-align: center; padding: 4rem 2rem;">
            <div class="demo-empty-state">
                <span class="material-symbols-outlined" style="font-size: 64px; color: #94a3b8;">travel_explore</span>
                <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.4rem; color: #1e293b; margin: 10px 0;">Nenhuma fatia demográfica cadastrada</h3>
                <p style="color: #64748b; max-width: 460px; margin: 0 auto 1.5rem auto;">Cadastre as origens culturais deste país para definir como os nomes e sobrenomes dos novos jogadores serão gerados.</p>
                <a href="/usuario/criar_demografia.php?idTime=<?php echo (int)$pais->id; ?>" class="btn-demo-primary" style="display: inline-flex;">
                    <span class="material-symbols-outlined">add_circle</span>
                    Cadastrar Primeira Origem
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function(){
    var idPais = <?php echo (int)$pais->id; ?>;

    $(".apagar-demografia").on("click", function(e){
        e.preventDefault();
        var origemId = $(this).data("origem");
        var origemNome = $(this).data("nome") || "esta origem";

        if(confirm("Tem certeza que deseja apagar a fatia demográfica '" + origemNome + "' deste país?")){
            var $btn = $(this);
            $btn.css("opacity", "0.5").prop("disabled", true);

            $.ajax({
                type: 'POST',
                url: '/usuario/apagar_demografia.php',
                data: {
                    origem: origemId,
                    pais: idPais
                },
                dataType: 'json'
            })
            .done(function(data) {
                if (data && data.success) {
                    location.reload();
                } else {
                    $btn.css("opacity", "1").prop("disabled", false);
                    var msg = (data && data.error) ? data.error : "Erro desconhecido";
                    alert("Não foi possível excluir: " + msg);
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                $btn.css("opacity", "1").prop("disabled", false);
                alert("Erro de comunicação com o servidor.");
            });
        }
    });
});
</script>

<?php
} else {
    echo "<div class='demo-container'><div class='alert alert-danger'>Usuário sem permissão para acessar a demografia deste país.</div></div>";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
