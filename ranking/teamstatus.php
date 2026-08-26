<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// set number of records per page
$records_per_page = 15;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

$id = isset($_GET['team']) ? (int)$_GET['team'] : 0;

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/federacoes.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$jogo = new Jogo($db);
$federacao = new Federacao($db);

// query paises
$stmt = $pais->readInfo($id);
$info = $stmt->fetch(PDO::FETCH_ASSOC);
$nome_selecao = $info['nome'] ?? 'Seleção';
$federacao_selecao_id = $info['federacao'] ?? 0;
$pontos = $info['pontos'] ?? 0;
$bandeira = $info['bandeira'] ?? 'world.png';
$ativo = ($info['ativo'] ?? 0) ? 'ativo' : 'inativo';

//query federacao
$stmt = $federacao->selFederacao($federacao_selecao_id);
$info_fed = $stmt->fetch(PDO::FETCH_ASSOC);
$federacao_selecao = $info_fed['nome'] ?? 'CONFUSA';

$page_title = "Ranking de Seleções - " . $nome_selecao;
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'home_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once 'ranking_header.php';

//query jogos time
$jogo_stmt = $jogo->selecionarJogosTime($id, $from_record_num, $records_per_page);

// the page where this paging is used
$page_url = "teamstatus.php?team=" . $id . "&";

// count all products in the database to calculate total pages
$total_rows = $jogo->countAllSingleTeam($id);

//query informacoes time
$info_stmt = $jogo->recuperarInfoTime($id);
$info_stats = $info_stmt->fetch(PDO::FETCH_ASSOC);

$golsPro = ($info_stats['golsProVisitante'] ?? 0) + ($info_stats['golsProMandante'] ?? 0);
$golsContra = ($info_stats['golsContraVisitante'] ?? 0) + ($info_stats['golsContraMandante'] ?? 0);

?>

<div class="ranking-container">
    <div class="ranking-card">
        <div class="team-header-profile">
            <div class="team-profile-info">
                <img id="bandeiraGrande" src="/images/bandeiras/<?php echo $bandeira; ?>" alt="<?php echo $nome_selecao; ?>">
                <div class="team-profile-title">
                    <h2><?php echo $nome_selecao; ?></h2>
                    <h3>
                        <span><?php echo $federacao_selecao; ?></span>
                        <span class="<?php echo $ativo; ?>"><?php echo ucfirst($ativo); ?></span>
                    </h3>
                </div>
            </div>
            
            <a href="/ranking" class="ranking-nav-link" style="padding: 8px 16px;">
                <span class="material-symbols-outlined nav-icon">arrow_back</span>
                <span>Voltar ao Ranking</span>
            </a>
        </div>

        <div id="info-jogos">
            <a href="#" id="Pontos" class="infoblock togglebutton" title="Pontos totais no ranking">
                <span class="material-symbols-outlined">military_tech</span>
                <span><?php echo $pontos; ?></span>
                <span class="stat-label">Pontos Elo</span>
            </a>
            <a href="#" id="Jogos" class="infoblock togglebutton" title="Jogos totais disputados">
                <span class="material-symbols-outlined">calendar_today</span>
                <span><?php echo $total_rows; ?></span>
                <span class="stat-label">Partidas</span>
            </a>
            <a href="#" id="Vitoria" class="infoblock togglebutton" title="Número de vitórias">
                <span class="material-symbols-outlined vitoria">arrow_circle_up</span>
                <span class="vitoria"><?php echo ($info_stats['vitorias'] ?? 0); ?></span>
                <span class="stat-label">Vitórias</span>
            </a>
            <a href="#" id="Empate" class="infoblock togglebutton" title="Número de empates">
                <span class="material-symbols-outlined empate">do_not_disturb_on</span>
                <span class="empate"><?php echo ($info_stats['empates'] ?? 0); ?></span>
                <span class="stat-label">Empates</span>
            </a>
            <a href="#" id="Derrota" class="infoblock togglebutton" title="Número de derrotas">
                <span class="material-symbols-outlined derrota">arrow_circle_down</span>
                <span class="derrota"><?php echo ($info_stats['derrotas'] ?? 0); ?></span>
                <span class="stat-label">Derrotas</span>
            </a>
            <a href="#" id="GolsPro" class="infoblock togglebutton" title="Total de gols marcados">
                <span class="material-symbols-outlined vitoria">sports_soccer</span>
                <span class="vitoria"><?php echo $golsPro; ?></span>
                <span class="stat-label">Gols Pró</span>
            </a>
            <a href="#" id="GolsContra" class="infoblock togglebutton" title="Total de gols sofridos">
                <span class="material-symbols-outlined derrota">sports_soccer</span>
                <span class="derrota"><?php echo $golsContra; ?></span>
                <span class="stat-label">Gols Contra</span>
            </a>
        </div>

        <div style="margin-top: 1.5rem; border-top: 1px solid rgba(0, 0, 0, 0.06); padding-top: 1.25rem;">
            <h3 style="font-family: 'Kanit', sans-serif; font-size: 1.2rem; color: #1e293b; margin: 0 0 1rem 0;">Histórico de Partidas</h3>

            <?php
            include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");
            ?>

            <div class="tbl_user_data">
                <table id="tabelajogos" class="table">
                    <thead>
                        <tr>
                            <th style="text-align: left; width: 28%;">Time Mandante</th>
                            <th style="width: 7%;">Gols</th>
                            <th style="width: 8%;" class="penaltybox">Pênaltis</th>
                            <th style="width: 7%;">Gols</th>
                            <th style="text-align: left; width: 28%;">Time Visitante</th>
                            <th style="width: 12%;">Data</th>
                            <th style="width: 18%;">Campeonato</th>
                            <th style="width: 8%;">Calc?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = $jogo_stmt->fetch(PDO::FETCH_ASSOC)){
                            extract($row);

                            $pen = ($timeA_penaltis !== null && $timeB_penaltis !== null && $timeA_penaltis !== '' && $timeB_penaltis !== '') ? "({$timeA_penaltis}) pen. ({$timeB_penaltis})" : "-";
                            $calcBadge = ($calculo == '1' || $calculo == 1 || $calculo == 'Sim') ? "<span class='ativo' style='font-size:0.75rem; padding:2px 6px;'>Sim</span>" : "<span class='inativo' style='font-size:0.75rem; padding:2px 6px;'>Não</span>";

                            echo "<tr data-href='match_info.php?match_id={$idJogo}'>";
                                echo "<td style='text-align: left;'><div class='team-cell'><img src='/images/bandeiras/{$bandeiraA}' class='bandeira' alt='{$nomeA}'> <a href='./teamstatus.php?team={$idA}' class='team-link'>{$nomeA}</a></div></td>";
                                echo "<td style='font-weight: 700; font-size: 1.05rem;'>{$timeA_gols}</td>";
                                echo "<td class='penaltybox' style='color: #64748b; font-size: 0.8rem;'>{$pen}</td>";
                                echo "<td style='font-weight: 700; font-size: 1.05rem;'>{$timeB_gols}</td>";
                                echo "<td style='text-align: left;'><div class='team-cell'><img src='/images/bandeiras/{$bandeiraB}' class='bandeira' alt='{$nomeB}'> <a href='./teamstatus.php?team={$idB}' class='team-link'>{$nomeB}</a></div></td>";
                                echo "<td style='color: #475569; font-size: 0.85rem;'>{$data}</td>";
                                echo "<td style='font-weight: 500; font-size: 0.85rem;'>{$nomeCampeonato}</td>";
                                echo "<td>{$calcBadge}</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <?php
            include($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");
            ?>
        </div>
    </div>
</div>

<div class="modalOverlay closed" id="modalOverlay"></div>

<div class="moreInfoModal closed" id="moreInfoModal">
  <div id='modalPontos' class="modal-guts closed">
    <?php include_once 'modals/modalPontos.php'; ?>
  </div>
  <div id='modalJogos' class="modal-guts closed">
    <?php include_once 'modals/modalJogos.php'; ?>
  </div>
  <div id='modalVitoria' class="modal-guts closed">
    <?php
        $inicio_titulo = 'A quem';
        $final_titulo = 'venceu';
        $resultado_VED = 'V';
        include 'modals/modalResultados.php';
    ?>
  </div>
  <div id='modalEmpate' class="modal-guts closed">
    <?php
        $inicio_titulo = 'Com quem';
        $final_titulo = 'empatou';
        $resultado_VED = 'E';
        include 'modals/modalResultados.php';
    ?>
  </div>
  <div id='modalDerrota' class="modal-guts closed">
    <?php
        $inicio_titulo = 'De quem';
        $final_titulo = 'perdeu';
        $resultado_VED = 'D';
        include 'modals/modalResultados.php';
    ?>
  </div>
  <div id='modalGolsPro' class="modal-guts closed">
    <?php
        $titulo = 'aplicadas';
        $goleadasAplicadas = 1;
        include 'modals/modalGols.php';
    ?>
  </div>
  <div id='modalGolsContra' class="modal-guts closed">
    <?php
        $titulo = 'sofridas';
        $goleadasAplicadas = 0;
        include 'modals/modalGols.php';
    ?>
  </div>
  <div><button class="toggleButton" id="retornar">Fechar Janela</button></div>
</div>

<script>
jQuery(document).ready(function($) {
    $('*[data-href]').on('click', function() {
        window.location = $(this).data("href");
    });
});

$(document).on('click', ".toggleButton, .togglebutton", function() {
    var modalType = $(this).attr("id");

    if(modalType !== 'retornar'){
        $(".modalOverlay").fadeIn(200);
        $(".moreInfoModal").fadeIn(200);
        $(".modal-guts").hide();
        $("#modal"+modalType).show();
        $('#retornar').show();
    } else {
        $(".modalOverlay").fadeOut(200);
        $(".moreInfoModal").fadeOut(200);
        $(".modal-guts").hide();
        $('#retornar').hide();
    }
});

$(document).on('click', '.modalOverlay', function(e){
    $(".modalOverlay").fadeOut(200);
    $(".moreInfoModal").fadeOut(200);
    $(".modal-guts").hide();
    $('#retornar').hide();
});
</script>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
