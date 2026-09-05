<?php

//ini_set( 'display_errors', true );
//error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// page given in URL parameter, default page is one
// $page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 100;
$from_record_num = 0;

// calculate for the query LIMIT clause
// $from_record_num = ($records_per_page * $page) - $records_per_page;

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/federacoes.php");

$database = new Database();
$db = $database->getConnection();

$federacao2 = new Federacao($db);
$pais = new Pais($db);
$jogador = new Jogador($db);
$time = new Time($db);
$tecnico = new Tecnico($db);
$liga = new Liga($db);
$estadio = new Estadio($db);

// query caixa de seleção países desse dono
$stmtPais = $pais->read();
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $sigla, $bandeira, $nome);
    $listaPaises[] = $addArray;
}

// query caixa de seleção de posições
$stmtPos = $jogador->selectPosicoes();
$listaPosicoes = array();
while ($row_pos = $stmtPos->fetch(PDO::FETCH_ASSOC)){
    extract($row_pos);
    $addArray = array($ID, $Sigla);
    $listaPosicoes[] = $addArray;
}



//lista de times da pessoa
$lista_times = array();

$id = $_GET['team'] ?? 0;
$idTime = $id;

// query times
$info = $time->readInfo($id);
$nome_time = $info['Nome'] ?? '';
$sigla_time = $info['TresLetras'] ?? '';
$estadio_time = $info['Estadio'] ?? '';
$estadio_capacidade = $info['Capacidade'] ?? 0;
$escudo_time = $info['Escudo'] ?? '';
$foto_estadio = $info['fotoEstadio'] ?? '';
$uniforme1_time = $info['Uniforme1'] ?? '';
$uniforme2_time = $info['Uniforme2'] ?? '';
$pais_time = $info['Pais'] ?? '';
$liga_time = $info['liga'] ?? '';
$liga_id = $info['liga_id'] ?? null;
$pais_id = $info['pais_id'] ?? null;
$donoPais = $info['donoPais'] ?? null;
$status_time = $info['status'] ?? null;


if(isset($_SESSION['user_id']) && $donoPais == $_SESSION["user_id"]){
    $donoLogado = true;
} else {
    $donoLogado = false;
}

if($status_time > 0){
    $is_selecao = true;
} else {
    $is_selecao = false;
}

//outras informações para infoblock
$mediaIdade = number_format((float)($info['mediaIdade'] ?? 0), 1);
$estrangeiros = $info['estrangeiros'] ?? 0;
$jogadores_selecao = $info['emSelecao'] ?? 0;
$valor_total_clube = number_format(((float)($info['valorTotal'] ?? 0))/1000000, 1) . "M";
$recorde_transferencia = $time->balancoTransferencias($idTime);
$recorde_transferencia = number_format(((float)($recorde_transferencia ?? 0))/1000000, 1) . "M";
$nivel_medio = number_format((float)($info['mediaNivel'] ?? 0), 1);
$nivel_medio_onze = number_format((float)($info['mediaNivelOnze'] ?? 0), 1);


if($liga_time != ''){
    $liga_time = " - ". $liga_time;
}

//$escudo_imagem = explode(".",$escudo_time);
//$uniforme1_imagem = explode(".",$uniforme1_time);
//$uniforme2_imagem = explode(".",$uniforme2_time);


$page_title = $nome_time;
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'ligas_team_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>

<script>


// on load of the page: switch to the currently selected tab
var hash = window.location.hash;
var isDataDirty = false;

window.onbeforeunload = function(e) {
    if(isDataDirty){
        e.returnValue = "Você tem alterações não salvas. Deseja realmente sair?";
        return "Você tem alterações não salvas. Deseja realmente sair?";
    }
};

window.onload = function(e) {
    var hash = window.location.hash;

    if(hash == ''){
        hash = '#Jogadores';
    }

    $(".tabcontent").each(function(index){
        $(this).hide();
    });

    if($(hash).length){
        $(hash).show();
        $('a[href="'+hash+'"]').addClass("active");
    } else {
        $('#Jogadores').show();
    }
}

function showToast(msg, type) {
    var color = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#0284c7';
    var icon = type === 'success' ? '✓' : type === 'error' ? '✕' : '⟳';
    var toast = $('<div>').css({
        position: 'fixed', bottom: '24px', right: '24px', zIndex: 9999,
        background: color, color: '#fff',
        padding: '12px 20px', borderRadius: '10px',
        fontFamily: 'Outfit, sans-serif', fontWeight: 600, fontSize: '0.95rem',
        boxShadow: '0 6px 24px rgba(0,0,0,0.2)',
        display: 'flex', alignItems: 'center', gap: '8px',
        opacity: 0, transition: 'opacity 0.3s'
    }).html(icon + ' ' + msg);
    $('body').append(toast);
    setTimeout(function() { toast.css('opacity', 1); }, 10);
    setTimeout(function() { toast.css('opacity', 0); setTimeout(function() { toast.remove(); }, 350); }, 2800);
}

function reloadPageContent() {
    showToast('Atualizando...', 'info');
    
    $.get(window.location.href + "&v=" + new Date().getTime(), function(data) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(data, "text/html");

        // Update parts of the page
        $("#info-jogos").html($(doc).find("#info-jogos").html());
        $("#Jogadores").html($(doc).find("#Jogadores").html());
        $("#Elenco").html($(doc).find("#Elenco").html());
        $("#Posicionamento").html($(doc).find("#Posicionamento").html());
        
        // Update error box only if there is something relevant in the new page, 
        // but usually we want to keep the success/error message from the AJAX call that triggered this.
        // So maybe we don't wipe errorbox immediately. 
        // Let's just remove the loading indicator.
        $("#errorbox").html($(doc).find("#errorbox").html());

        // Re-initialize drag and drop
        initDragDrop();
        
        // Re-bind sortable if needed (Posicionamento page uses #sortable but seemingly no JS init for it in the snippet?)
        // The snippet shows `echo '<div id="sortable" class="ui-state">';` but no `$("#sortable").sortable(...)`.
        // Checked file content again, it seems it is using manual drag/drop logic with specific IDs.
        
        // Re-apply background colors for selects if needed (from $(document).ready)
        if( $("#selectPenal1").val() == 0 ) $("#selectPenal1").css("background-color", "lightcoral");
        if( $("#selectPenal2").val() == 0 ) $("#selectPenal2").css("background-color", "lightcoral");
        if( $("#selectPenal3").val() == 0 ) $("#selectPenal3").css("background-color", "lightcoral");
        if( $("#selectCapitao").val() == 0 ) $("#selectCapitao").css("background-color", "lightcoral");
    });
}

</script>


<?php
if ($is_selecao) {
    $stmtInfo = $pais->readInfo($pais_id);
    $resultInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);
    $federacaoTime = $resultInfo['federacao'];
    $stmtNome = $federacao2->selFederacao($federacaoTime);
    $nomeFederacao = $stmtNome->fetchColumn();
}

//query elenco
$time_stmt = $jogador->selecionarElencoTime($id,$from_record_num,$records_per_page);
$total_rows = $jogador->countAllSingleTeam($id);
$perc_estrangeiros = $total_rows > 0 ? number_format(($estrangeiros / $total_rows)*100,2)."%" : "0%";
?>

<main class="propostas-container" style="padding-top: 80px; padding-bottom: 60px;">
<div class="propostas-card">
<div id="quadro-container" class="<?php echo $idTime; ?>" data-limite-idade="<?php echo htmlspecialchars($limite_idade_liga ?? ''); ?>">
    <div class="team-header-flex-wrapper" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
        <div class="team-header-title-container" style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
            <img id="bandeiraGrande" src="/images/escudos/<?php echo htmlspecialchars($escudo_time); ?>" style="height: 60px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <div>
                <h2 class="propostas-title" style="margin: 0; text-align: left;"><?php echo htmlspecialchars($nome_time); ?></h2>
                <h3 style="margin: 4px 0 0 0; font-size: 1rem;">
                    <?php if(!$is_selecao): ?>
                        <a href="paisstatus.php?country=<?php echo $pais_id; ?>" style="color: #0284c7; text-decoration: none; font-weight: 600;"><?php echo htmlspecialchars($pais_time); ?></a>
                        <a href="leaguestatus.php?league=<?php echo $liga_id; ?>" style="color: #0284c7; text-decoration: none; font-weight: 600;"><?php echo htmlspecialchars($liga_time); ?></a>
                        <?php if(!empty($limite_idade_liga)): ?>
                            <span style="background: rgba(2, 132, 199, 0.1); color: #0284c7; font-weight: 600; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem; margin-left: 6px;">Sub-<?php echo htmlspecialchars($limite_idade_liga); ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($nomeFederacao); ?></span>
                    <?php endif; ?>
                </h3>
            </div>
        </div>
        
        <!-- Apresentacao / Uniformes / Estadio container -->
        <div class="team-header-meta-container" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <!-- Uniformes -->
            <div style="display: flex; gap: 8px;">
                <?php if(!empty($uniforme1_time)): ?>
                    <img src="/images/uniformes/<?php echo htmlspecialchars($uniforme1_time); ?>" height="80px" title="Uniforme 1" style="filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.15));">
                <?php endif; ?>
                <?php if(!empty($uniforme2_time)): ?>
                    <img src="/images/uniformes/<?php echo htmlspecialchars($uniforme2_time); ?>" height="80px" title="Uniforme 2" style="filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.15));">
                <?php endif; ?>
            </div>
            
            <!-- Estadio Link/Button -->
            <?php if(!empty($foto_estadio)): ?>
                <div style="position: relative;" title="<?php echo htmlspecialchars($estadio_time); ?>">
                    <img src="/images/estadios/<?php echo htmlspecialchars($foto_estadio); ?>" style="height: 80px; width: 120px; object-fit: cover; border-radius: 8px; border: 1px solid rgba(0,0,0,0.1);">
                </div>
            <?php endif; ?>
            <!-- Botão de Apresentação -->
            <a href="/times/team_presentation_magazine.php?team=<?php echo $idTime; ?>" class="btn-apresentacao" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px; background: #0284c7; color: #fff; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 0.9rem; transition: background 0.2s;">
                <span class="material-symbols-outlined" style="font-size: 1.1rem;">auto_stories</span>
                <span>Apresentação</span>
            </a>
            <!-- Botão Time Traveler -->
            <a href="/times/time_traveler.php?team=<?php echo $idTime; ?>" class="btn-time-traveler" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px; background: #0f172a; color: #fff; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 0.9rem; transition: background 0.2s;" title="Reconstituir o elenco do time em qualquer data do passado">
                <span class="material-symbols-outlined" style="font-size: 1.1rem; color: #38bdf8;">history_toggle_off</span>
                <span>Time Traveler</span>
            </a>
        </div>
    </div>
    
    <hr style="border: none; border-bottom: 1px solid rgba(0,0,0,0.08); margin: 20px 0;">

    <div id="info-jogos">
        <div class="infoblock" title="Tamanho do elenco">
            <span class="material-symbols-outlined">groups</span>
            <div>
                <span class="informacao"><?php echo $total_rows; ?></span>
                <span style="font-size: 0.75rem; color: #64748b;">Elenco</span>
            </div>
        </div>
        <div class="infoblock" title="Média de idade">
            <span class="material-symbols-outlined">elderly</span>
            <div>
                <span class="informacao"><?php echo $mediaIdade; ?></span>
                <span style="font-size: 0.75rem; color: #64748b;">Média Idade</span>
            </div>
        </div>
        <?php if(!$is_selecao): ?>
            <div class="infoblock" title="Estrangeiros">
                <span class="material-symbols-outlined">globe_location_pin</span>
                <div>
                    <span class="informacao"><?php echo $estrangeiros; ?> <span class="informacao micro">(<?php echo $perc_estrangeiros; ?>)</span></span>
                    <span style="font-size: 0.75rem; color: #64748b;">Estrangeiros</span>
                </div>
            </div>
            <div class="infoblock" title="Jogadores em seleções nacionais">
                <span class="material-symbols-outlined">flag</span>
                <div>
                    <span class="informacao"><?php echo $jogadores_selecao; ?></span>
                    <span style="font-size: 0.75rem; color: #64748b;">Na Seleção</span>
                </div>
            </div>
        <?php endif; ?>
        <div class="infoblock" title="Estádio (capacidade)">
            <span class="material-symbols-outlined">stadium</span>
            <div>
                <span class="informacao menor"><?php echo number_format($estadio_capacidade, 0, ',', '.'); ?></span>
                <span style="font-size: 0.75rem; color: #64748b;"><?php echo htmlspecialchars($estadio_time); ?></span>
            </div>
        </div>
        <?php if(!$is_selecao): ?>
            <div class="infoblock clickable" id="Recorde" title="Balanço de caixa (em F$)" style="cursor: pointer;">
                <span class="material-symbols-outlined">account_balance</span>
                <div>
                    <span class="informacao menor"><?php echo $recorde_transferencia; ?></span>
                    <span style="font-size: 0.75rem; color: #64748b;">Balanço Caixa</span>
                </div>
            </div>
        <?php endif; ?>
        <div class="infoblock" title="Valor de mercado (em F$)">
            <span class="material-symbols-outlined">attach_money</span>
            <div>
                <span class="informacao menor"><?php echo $valor_total_clube; ?></span>
                <span style="font-size: 0.75rem; color: #64748b;">Valor total</span>
            </div>
        </div>
        <div class="infoblock" title="Média de Nível (titulares/total)">
            <span class="material-symbols-outlined">star_half</span>
            <div>
                <span class="informacao"><?php echo $nivel_medio_onze; ?> <span class="informacao micro">/ <?php echo $nivel_medio; ?></span></span>
                <span style="font-size: 0.75rem; color: #64748b;">Nível Tit./Geral</span>
            </div>
        </div>
    </div>

    <hr style="border: none; border-bottom: 1px solid rgba(0,0,0,0.08); margin: 20px 0;">
    <div id="errorbox"></div>
<?php
if($donoLogado){
echo '<div class="tab">';
  echo '<a class="tablinks" href="#Jogadores">Jogadores</a>';
  echo '<a class="tablinks" href="#Elenco">Elenco</a>';
  echo '<a class="tablinks" href="#Posicionamento">Posicionamento</a>';
echo '</div>';
} else {
	echo '<div class="tab">';
  echo '<a class="tablinks" href="#Jogadores">Jogadores</a>';
  echo '<a class="tablinks" href="#Posicionamento">Posicionamento</a>';
echo '</div>';
}

echo "<div class='tabcontent' id='Jogadores'>";
echo "<div style='overflow-x: auto; width: 100%; margin-top: 15px;'>";
echo "<table id='tabelaElenco' class='table'>";
echo "<thead>";
echo "<tr>";
echo "<th></th>";
echo "<th>Nome</th>";
echo "<th>Posições</th>";
echo "<th>Nac.</th>";
echo "<th>Nasc. (idade)</th>";
echo "<th>Nivel (mod.)</th>";
echo "<th>Desde</th>";
echo "<th>Vindo de</th>";
echo "<th>Contrato até</th>";
echo "<th>Valor</th>";
echo "<th>Disp.</th>";
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    echo "<th id='dono".$_SESSION['user_id']."'>Opções</th>";
}
echo "</tr>";
echo "</thead>";
echo "<tbody>";

//recuperar informações técnico
$stmtTec = $tecnico->infoTecnico($idTime);
$rowTec = $stmtTec->fetch(PDO::FETCH_ASSOC);

if($rowTec) {
    $transferenciaTecnico = $tecnico->ultimaTransferencia($rowTec['ID'], $idTime);
    $encerramentoTecnico = ( $rowTec['encerramento'] == "0" ) ? 'indet.' : $rowTec['encerramento'] ;

    $rowTec['Nascimento'] = !empty($rowTec['Nascimento']) ? date("d-m-Y", strtotime($rowTec['Nascimento'])) : '';

    echo "<tr id='tec".$rowTec['ID']."' data-sexo='".$rowTec['Sexo']."'>";
    echo "<td class='nopadding'><div class='foto_jogador'><a href='/ligas/coachstatus.php?coach={$rowTec['ID']}'><img class='playerThumb' src='/images/tecnicos/".$rowTec['foto']."'></a></div></td>";
    echo "<td class='nopadding nomeJogador'><a href='/ligas/coachstatus.php?coach={$rowTec['ID']}' style='color:#0f172a; text-decoration:none; font-weight:600;'><span class='nomeEditavel'>{$rowTec['Nome']}</span></a><br><span class='posicao'>Técnico</span></td>";
    echo "<td data-label='Posições'><span class='cell-value'>T</span></td>";
    if($rowTec['idPais'] != 0){
        echo "<td class='nopadding' data-label='Nac.'><span class='cell-value'><img src='/images/bandeiras/{$rowTec['bandeiraPais']}' class='bandeira nomePais' id='ban".$rowTec['idPais']."'>  <span class='nomePais' id='pai".$rowTec['idPais']."'>{$rowTec['siglaPais']}</span>";
    } else {
        echo "<td data-label='Nac.'><span class='cell-value'>";
    }
    echo " <select class='comboPais editavel ' id='{$rowTec['idPais']}' style='display: none;'>'  ";
        for($i = 0; $i < count($listaPaises);$i++){
            echo "<option value='{$listaPaises[$i][0]}'>{$listaPaises[$i][3]}</option>";
        }
        echo "</select>";
    echo "</span></td>";
    echo "<td class='nopadding' data-label='Idade'><span class='cell-value'><span class='nascimentoEIdade'>{$rowTec['Nascimento']} (".$rowTec['idade'].")</span><input type='date' class='editavel nascimento' style='display: none;'/></span></td>";
    echo "<td class='nopadding' data-label='Nível'><span class='cell-value'><span class='nivel'>{$rowTec['Nivel']}</span></span></td>";
    echo "<td class='nopadding' data-label='Desde'><span class='cell-value'><span class='desdeFixo'>{$transferenciaTecnico["Data"]}</span><input type='date' class='editavel desde' style='display: none;'></span></td>";
    echo "<td class='nopadding ultimoClube' data-label='Origem' data-ultimo-clube='{$transferenciaTecnico["ID"]}'><span class='cell-value'>{$transferenciaTecnico["Clube"]}</span></td>";
    echo "<td class='nopadding' data-label='Contrato'><span class='cell-value'>{$encerramentoTecnico}</span></td>";
    echo "<td data-label='Valor'><span class='cell-value'>-</span></td>";
    echo "<td data-label='Disponível'><span class='cell-value'>-</span></td>";
    $tecOptions = "<td class='wide' data-label='Opções' id='dono{$rowTec['donoTecnico']}'><span class='cell-value'>";
    if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
        if(!$is_selecao){
            if(!$_SESSION['emTestes']){
                $tecOptions .= "<a id='proTec".$rowTec['ID']."' title='Fazer Proposta' class='clickable propostaTecnico' style='margin-right: 8px;'><span class='material-symbols-outlined inlineButton'>payment_arrow_down</span></a>";
            }
            if($donoLogado){
                $tecOptions .= "<a id='dem".$rowTec['ID']."' title='Editar técnico' class='clickable editarTecnico' style='margin-right: 8px;'><span class='material-symbols-outlined inlineButton azul'>person_edit</span></a>";
                $tecOptions .= "<a id='demTec".$rowTec['ID']."' title='Demitir técnico' class='clickable demitirTecnico' style='margin-right: 8px;'><span class='material-symbols-outlined inlineButton vermelho'>contract_delete</span></a>";
                $tecOptions .= "<a hidden id='sal".$rowTec['ID']."' title='Salvar' class='clickable salvarTecnico' style='margin-right: 8px;'><span class='material-symbols-outlined inlineButton positive'>save</span></a>";
                $tecOptions .= "<a hidden id='can".$rowTec['ID']."' title='Cancelar' class='clickable cancelarTecnico'><span class='material-symbols-outlined inlineButton vermelho'>cancel</span></a>";

            }
        } else {
            $tecOptions .= "<a id='desTec".$rowTec['ID']."' title='Desconvocar técnico' class='clickable desconvocarTecnico'><span class='material-symbols-outlined inlineButton vermelho'>travel</span></a>";
        }
    }


        $tecOptions .= "</span></td>";
        if($rowTec['ID'] != 0 && $rowTec['ID'] != null){
            echo $tecOptions;
        } else {
            echo "<td data-label='Opções'><span class='cell-value'></span></td>";
        }

    echo "</tr>";
}

$agora = date('Y-m-d');

 $lista_titulares = array();
 $lista_reservas = array();
 $lista_suplentes = array();

        while ($row = $time_stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);


            $Nascimento = !empty($Nascimento) ? date("d-m-Y", strtotime($Nascimento)) : '';
            $valor = ($valor/1000);
            $valor = "F$ ".number_format($valor,0,".","") . " k" ;
            if(!empty($encerramento) && $encerramento != "0000-00-00"){
                $encerramento = date("d-m-Y", strtotime($encerramento));
            } else {
                $encerramento = "indet.";
            }
            
            $disponibilidade = ($disponibilidade == 0 ) ? 'Não' : 'Sim';

            $dadosTransferencia = $jogador->ultimaTransferencia($idJogador, $id);

            //calcular posicao se não tiver base definida
            if($posicaoBase == 0){
                //$posicaoBase = $jogador->nomePosicaoPorCodigo((strpos($StringPosicoes, "1"))+1);
                $posicaoBase = '';
            } else {
                $posicaoBase = $jogador->nomePosicaoPorCodigo($posicaoBase);
            }

            $stringPosicoes = $jogador->listaPosicoes($StringPosicoes);

            switch($titularidade){
                case 1:
                    $titular = 'titular';
                    break;
                case 0:
                    $titular = 'reserva';
                    break;
                case -1:
                    $titular = 'suplente';
                    break;
                default:
                    $titular = 'suplente';
                    break;
                }
                
                $bloqueadoPorIdade = (!empty($limite_idade_liga) && intval($limite_idade_liga) > 0 && intval($Idade) > intval($limite_idade_liga));
                $lockIconElenco = $bloqueadoPorIdade ? " <span class='material-symbols-outlined locked-icon' style='font-size: 16px; color: #ef4444; vertical-align: middle;' title='Bloqueado para escalação: Idade ({$Idade} anos) acima do limite da liga ({$limite_idade_liga} anos)'>lock</span>" : "";

                if($titular == 'titular'){
                    $lista_titulares[] = ['nome' => $nomeJogador, 'nivel' => $Nivel, 'mod' => $ModificadorNivel, 'posicaoBase' => $posicaoBase, 'stringPosicoes' => $stringPosicoes, 'idJogador' => $idJogador, 'mentalidade' => $mentalidade, 'capitao' => $capitao, 'cobrancaPenalti' => $cobrancaPenalti, 'cobradorFalta' => $cobradorFalta, 'foto' => $foto, 'idade' => $Idade, 'bloqueadoIdade' => $bloqueadoPorIdade];
                } else if($titular == 'reserva'){
                    $lista_reservas[] = ['nome' => $nomeJogador, 'nivel' => $Nivel, 'mod' => $ModificadorNivel, 'posicaoBase' => $posicaoBase, 'stringPosicoes' => $stringPosicoes, 'idJogador' => $idJogador, 'idade' => $Idade, 'bloqueadoIdade' => $bloqueadoPorIdade];
                } else {
                    $lista_suplentes[] = ['nome' => $nomeJogador, 'nivel' => $Nivel, 'mod' => $ModificadorNivel, 'posicaoBase' => $posicaoBase, 'stringPosicoes' => $stringPosicoes, 'idJogador' => $idJogador, 'idade' => $Idade, 'bloqueadoIdade' => $bloqueadoPorIdade];
                }


            echo "<tr data-id-dono-vinculado='".$clubeVinculado."' data-sexo='".$sexoJogador."' id='".$idJogador."' class='".$titular." ".($bloqueadoPorIdade ? "jogador-bloqueado-idade" : "")."' data-bloqueado-idade='".($bloqueadoPorIdade ? "1" : "0")."'>";
            echo "<td class='nopadding'><div class='foto_jogador'><div class='imageUpload'><img class='playerThumb' src='/images/jogadores/".$foto."' /> <input type='file' hidden id='foto".$idJogador."' class='hiddenInput custom-file-upload' name='foto' accept='.jpg,.png,.jpeg,.webp'/></div>
                <div class='jersey-container'>
                    <div class='jersey-icon' title='Número da camisa'>{$numeroCamisa}</div>
                    <input type='number' class='editavel numeroCamisa' value='{$numeroCamisa}' min='1' max='99' style='display:none;'>
                </div>
                </div></td>";
                echo "<td class='nopadding nomeJogador'><a href='/ligas/playerstatus.php?player={$idJogador}' class='nomeEditavel'>{$nomeJogador}</a>{$lockIconElenco}<br><span class='posicao'>{$posicaoBase}</span></td>";
                echo "<td class='nopadding' data-label='Posições'><span class='cell-value'><span class='posicoesAtuais'>{$stringPosicoes}</span>";
                echo " <select multiple class='comboPosicoes editavel ' hidden>'  ";
                for($i = 0; $i < count($listaPosicoes);$i++){
                    echo "<option value='{$listaPosicoes[$i][0]}'>{$listaPosicoes[$i][1]}</option>";
                }
                echo "</select>";
                echo "</span></td>";
                if($idPais != 0){
                    echo "<td class='nopadding' data-label='Nac.'><span class='cell-value'><img src='/images/bandeiras/{$bandeiraPais}' class='bandeira nomePais' id='ban".$idPais."'>  <span class='nomePais' id='pai".$idPais."'>{$siglaPais}</span></span>";
                } else {
                    echo "<td data-label='Nac.'><span class='cell-value'>";
                }
                echo " <select class='comboPais editavel ' id='{$idPais}' style='display: none;'>'  ";
                    for($i = 0; $i < count($listaPaises);$i++){
                        echo "<option value='{$listaPaises[$i][0]}'> {$listaPaises[$i][3]}</option>";
                    }
                    echo "</select>";
                echo "</td>";
                echo "<td class='nopadding' data-label='Idade'><span class='cell-value'><span class='nascimentoEIdade'>{$Nascimento} (".$Idade.")</span><input type='date' class='editavel nascimento' style='display: none;'></span></td>";
                echo "<td class='nopadding' data-label='Nível'><span class='cell-value'><span class='nivelEMod'>{$Nivel} (".$ModificadorNivel.")</span><span class='editavel nivel' style='display: none;'></span></span></td>";
                echo "<td class='nopadding' data-label='Desde'><span class='cell-value'><span class='desdeFixo'>{$dadosTransferencia["Data"]}</span><input type='date' class='editavel desde' style='display: none;'></span></td>";
                echo "<td class='nopadding ultimoClube' data-label='Origem' data-ultimo-clube='{$dadosTransferencia["ID"]}'><span class='cell-value'>{$dadosTransferencia["Clube"]}</span></td>";
                echo "<td class='nopadding' data-label='Contrato'><span class='cell-value'><span class='encerramentoFixo'>{$encerramento}</span><input type='date' class='editavel encerramento' style='display: none;'></span></td>";
                echo "<td class='nopadding' data-label='Valor'><span class='cell-value'><span class='valorEditavel valor'>{$valor}</span></span></td>";
                echo "<td class='nopadding' data-label='Disponível'><span class='cell-value'>{$disponibilidade}</span></td>";
                $optionsString = "<td class='wide' data-label='Opções' id='dono{$donoJogador}'><span class='cell-value'>";
                if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
                  $optionsString .= "<a id='dow".$id."' title='Baixar arquivo .jog' class='clickable exportar' style='margin-right: 8px;'><span class='material-symbols-outlined inlineButton azul'>download</span></a>";

                    if(!$is_selecao){
						if(!$_SESSION['emTestes'] || $donoLogado){
							$optionsString .= "<a id='pro".$idJogador."' title='Fazer Proposta' class='clickable proposta' style='margin-right: 8px;'><span class='material-symbols-outlined inlineButton'>payment_arrow_down</span></a>";
						}
                        if($donoLogado){
                            $optionsString .= "<a id='edit".$idJogador."' title='Editar jogador' class='clickable editar' style='margin-right: 8px;'><span class='material-symbols-outlined inlineButton azul'>person_edit</span></a>";
                            $optionsString .= "<a id='disp".$idJogador."' title='Disponibilizar jogador' class='clickable disponibilizar' style='margin-right: 8px;'><span class='material-symbols-outlined inlineButton azul'>sell</span></a>";
                            $optionsString .= "<a id='demi".$idJogador."' title='Demitir jogador' class='clickable demitir' style='margin-right: 8px;'><span class='material-symbols-outlined inlineButton vermelho'>contract_delete</span></a>";
                            $optionsString .= "<a id='apos".$idJogador."' title='Aposentar jogador' class='clickable aposentar' style='margin-right: 8px;'><span class='material-symbols-outlined inlineButton vermelho'>assist_walker</span></a>";
							$optionsString .= "<a id='expa".$idJogador."' title='Expatriar jogador' class='clickable expatriar' style='margin-right: 8px;'><span class='material-symbols-outlined inlineButton vermelho'>flight_takeoff</span></a>"; 
                            $optionsString .= "<a hidden id='sal".$id."' title='Salvar' class='clickable salvar' style='margin-right: 8px;'><span class='material-symbols-outlined inlineButton positive'>save</span></a>";
                            $optionsString .= "<a hidden id='can".$id."' title='Cancelar' class='clickable cancelar'><span class='material-symbols-outlined inlineButton vermelho'>cancel</span></a>";

                        }
                    } else {
                        $optionsString .= "<a id='desc".$idJogador."' title='Desconvocar jogador' class='clickable desconvocar'><span class='material-symbols-outlined inlineButton vermelho'>travel</span></a>";
                    }



                    $optionsString .= "</span></td>";
                    echo $optionsString;
                }
            echo "</tr>";

        }

        echo "</tbody>";




echo "</table>";
echo "</div>"; // close overflow-x auto wrapper
echo "</div>";

if($donoLogado){
// permitir arrastar elenco
$drag_players = "draggable";

	
//pagina do elenco
echo "<div class='tabcontent' id='Elenco' style='display: none;'>";

echo "<div class='grid_quadro_jogadores'>";
echo "<div class='tableHolder'><table id='tabelaTitulares'>";
echo "<caption>Titulares</caption>";
echo "<thead>";
echo "<tr>";
echo "<th>Jogador</th>";
echo "<th>Nivel (mod)</th>";
echo "<th>Posições</th>";
echo "<th>Ações</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";
foreach($lista_titulares as $jogador_tabela){
    $isBloq = !empty($jogador_tabela['bloqueadoIdade']);
    $lockBadge = $isBloq ? " <span class='material-symbols-outlined locked-icon' style='font-size: 15px; color: #ef4444; vertical-align: middle;' title='Bloqueado: Idade ({$jogador_tabela['idade']} anos) acima do limite da liga ({$limite_idade_liga} anos)'>lock</span>" : "";
    echo "<tr class='clickablerow_tit ".($isBloq ? "jogador-bloqueado-idade" : "")."' id='elenco".$jogador_tabela['idJogador']."' data-bloqueado-idade='".($isBloq ? "1" : "0")."'>";
    echo "<td class='nopadding nomeJogador'>{$jogador_tabela['nome']}{$lockBadge}<br><span class='posicao'>{$jogador_tabela['posicaoBase']}</span></td>";
    echo "<td class='nopadding' data-label='Nível'>{$jogador_tabela['nivel']} (".$jogador_tabela['mod'].")</td>";
    echo "<td class='nopadding' data-label='Posições'>{$jogador_tabela['stringPosicoes']}</td>";
    echo "<td class='nopadding actions-cell' data-label='Ações'>";
    echo "<a href='#' class='quick-move-btn demote-titular-btn' data-action='demote-titular' data-id='".$jogador_tabela['idJogador']."' title='Mover para Reserva'><span class='material-symbols-outlined'>arrow_downward</span></a>";
    echo "</td>";
    echo "</tr>";
}
echo "</tbody>";
echo "</table></div>";

echo "<div>";
echo '<a id="trocar_titular_reserva" title="Trocar jogadores selecionados" class="clickable"><span class="alto material-symbols-outlined inlineButton azul">sync_alt</span></a>';
echo '<a id="enviar_para_titular" title="Enviar jogador para titular" class="clickable"><span class="alto material-symbols-outlined inlineButton azul">arrow_back</span></a>';
echo '<a id="remover_titular" title="Enviar jogador para reserva" class="clickable"><span class="alto material-symbols-outlined inlineButton azul">arrow_forward</span></a>';
echo "</div>";

echo "<div class='tableHolder'><table id='tabelaReservas'>";
echo "<caption>Reservas</caption>";
echo "<thead>";
echo "<tr>";
echo "<th>Nome</th>";
echo "<th>Nivel (mod)</th>";
echo "<th>Posições</th>";
echo "<th>Ações</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";
foreach($lista_reservas as $jogador_tabela){
    $isBloq = !empty($jogador_tabela['bloqueadoIdade']);
    $lockBadge = $isBloq ? " <span class='material-symbols-outlined locked-icon' style='font-size: 15px; color: #ef4444; vertical-align: middle;' title='Bloqueado: Idade ({$jogador_tabela['idade']} anos) acima do limite da liga ({$limite_idade_liga} anos)'>lock</span>" : "";
    echo "<tr class='clickablerow_res ".($isBloq ? "jogador-bloqueado-idade" : "")."' id='elenco".$jogador_tabela['idJogador']."' data-bloqueado-idade='".($isBloq ? "1" : "0")."'>";
    echo "<td class='nopadding nomeJogador'>{$jogador_tabela['nome']}{$lockBadge}<br><span class='posicao'>{$jogador_tabela['posicaoBase']}</span></td>";
    echo "<td class='nopadding' data-label='Nível'>{$jogador_tabela['nivel']} (".$jogador_tabela['mod'].")</td>";
    echo "<td class='nopadding' data-label='Posições'>{$jogador_tabela['stringPosicoes']}</td>";
    echo "<td class='nopadding actions-cell' data-label='Ações'>";
    if(!$isBloq){
        echo "<a href='#' class='quick-move-btn promote-reserva-btn' data-action='promote-reserva' data-id='".$jogador_tabela['idJogador']."' title='Promover a Titular' style='margin-right: 5px;'><span class='material-symbols-outlined'>arrow_upward</span></a>";
    }
    echo "<a href='#' class='quick-move-btn demote-reserva-btn' data-action='demote-reserva' data-id='".$jogador_tabela['idJogador']."' title='Mover para Suplente'><span class='material-symbols-outlined'>arrow_downward</span></a>";
    echo "</td>";
    echo "</tr>";
}
echo "</tbody>";
echo "</table></div>";

echo "<div>";
echo '<a id="trocar_reserva_suplente" title="Trocar jogadores selecionados" class="clickable"><span class="alto material-symbols-outlined inlineButton azul">sync_alt</span></a>';
echo '<a id="enviar_para_suplente" title="Retirar jogador da reserva" class="clickable"><span class="alto material-symbols-outlined inlineButton azul">arrow_forward</span></a>';
echo '<a id="enviar_para_reserva" title="Enviar jogador para reserva" class="clickable"><span class="alto material-symbols-outlined inlineButton azul">arrow_back</span></a>';
echo "</div>";

echo "<div class='tableHolder'><table id='tabelaSuplentes'>";
echo "<caption>Suplentes</caption>";
echo "<thead>";
echo "<tr>";
echo "<th>Nome</th>";
echo "<th>Nivel (mod)</th>";
echo "<th>Posições</th>";
echo "<th>Ações</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";
foreach($lista_suplentes as $jogador_tabela){
    $isBloq = !empty($jogador_tabela['bloqueadoIdade']);
    $lockBadge = $isBloq ? " <span class='material-symbols-outlined locked-icon' style='font-size: 15px; color: #ef4444; vertical-align: middle;' title='Bloqueado: Idade ({$jogador_tabela['idade']} anos) acima do limite da liga ({$limite_idade_liga} anos)'>lock</span>" : "";
    echo "<tr class='clickablerow_sup ".($isBloq ? "jogador-bloqueado-idade" : "")."' id='elenco".$jogador_tabela['idJogador']."' data-bloqueado-idade='".($isBloq ? "1" : "0")."'>";
    echo "<td class='nopadding nomeJogador'>{$jogador_tabela['nome']}{$lockBadge}<br><span class='posicao'>{$jogador_tabela['posicaoBase']}</span></td>";
    echo "<td class='nopadding' data-label='Nível'>{$jogador_tabela['nivel']} (".$jogador_tabela['mod'].")</td>";
    echo "<td class='nopadding' data-label='Posições'>{$jogador_tabela['stringPosicoes']}</td>";
    echo "<td class='nopadding actions-cell' data-label='Ações'>";
    if(!$isBloq){
        echo "<a href='#' class='quick-move-btn' data-action='promote-suplente' data-id='".$jogador_tabela['idJogador']."' title='Promover a Reserva'><span class='material-symbols-outlined'>arrow_upward</span></a>";
    } else {
        echo "<span class='quick-move-btn disabled' title='Bloqueado: Acima da idade limite da liga' style='opacity: 0.35; cursor: not-allowed;'><span class='material-symbols-outlined'>lock</span></span>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</tbody>";
echo "</table></div>";




echo "</div>"; // close .grid_quadro_jogadores
echo "</div>"; // close #Elenco

} else {
	// permitir arrastar elenco
$drag_players = "nondraggable";
}

$zagueiro = array();
$volante = array();
$meia = array();
$armador = array();
$atacante = array();
foreach($lista_titulares as $jogador){
	
	if(strlen($jogador["nome"]) > 15){
		$temp_nome = explode(" ", $jogador["nome"]);
		$sobrenome_jogador = end($temp_nome);
		$primeira_letra = mb_substr($temp_nome[0], 0 ,1);
		$nome_final = $primeira_letra . ". " . $sobrenome_jogador;
	} else {
		$nome_final = $jogador["nome"];
	}
	
    switch($jogador['posicaoBase']){
        case "Goleiro":
            $goleiro = ["<p>".$nome_final."</p>", $jogador["idJogador"], $jogador["foto"]];
            break;
        case "Lateral-direito":
            $lateral_direito = ["<p>".$nome_final."</p>", $jogador["idJogador"], $jogador["foto"]];
            break;
        case "Lateral-esquerdo":
            $lateral_esquerdo = ["<p>".$nome_final."</p>", $jogador["idJogador"], $jogador["foto"]];
            break;
        case "Ala direito":
            $ala_direito = ["<p>".$nome_final."</p>", $jogador["idJogador"], $jogador["foto"]];
            break;
        case "Ala esquerdo":
            $ala_esquerdo = ["<p>".$nome_final."</p>", $jogador["idJogador"], $jogador["foto"]];
            break;
        case "Meia direito":
            $meia_direito = ["<p>".$nome_final."</p>", $jogador["idJogador"], $jogador["foto"]];
            break;
        case "Meia esquerdo":
            $meia_esquerdo = ["<p>".$nome_final."</p>", $jogador["idJogador"], $jogador["foto"]];
            break;
        case "Ponta direita":
            $ponta_direita = ["<p>".$nome_final."</p>", $jogador["idJogador"], $jogador["foto"]];
            break;
        case "Ponta esquerda":
            $ponta_esquerda = ["<p>".$nome_final."</p>", $jogador["idJogador"], $jogador["foto"]];
            break;
        case "Zagueiro":
            $zagueiro[] = ["<p>".$nome_final."</p>", $jogador["idJogador"], $jogador["foto"]];
            break;
        case "Volante":
            $volante[] = ["<p>".$nome_final."</p>", $jogador["idJogador"], $jogador["foto"]];
            break;
        case "Meia central":
            $meia[] = ["<p>".$nome_final."</p>", $jogador["idJogador"], $jogador["foto"]];
            break;
        case "Meia-atacante":
            $armador[] = ["<p>".$nome_final."</p>", $jogador["idJogador"], $jogador["foto"]];
            break;
        case "Atacante de movimentação":
            $atacante[] = ["<p>".$nome_final."</p>", $jogador["idJogador"], "Am", $jogador["foto"]];
            break;
        case "Atacante de área":
            $atacante[] = ["<p>".$nome_final."</p>", $jogador["idJogador"], "Aa", $jogador["foto"]];
            break;
        default:
        break;
    }
}

//controle de variaveis nao setadas
if(!isset($ponta_esquerda)){
  $ponta_esquerda[0] = '';
  $ponta_esquerda[1] = "PE";
  $ponta_esquerda[2] = '';
}

if(!isset($ponta_direita)){
  $ponta_direita[0] = '';
  $ponta_direita[1] = "PD";
  $ponta_direita[2] = '';
}

if(!isset($ala_direito)){
  $ala_direito[0] = '';
  $ala_direito[1] = "AD";
  $ala_direito[2] = '';
}

if(!isset($ala_esquerdo)){
  $ala_esquerdo[0] = '';
  $ala_esquerdo[1] = "AE";
  $ala_esquerdo[2] = '';
}

if(!isset($lateral_direito)){
  $lateral_direito[0] = '';
  $lateral_direito[1] = "LD";
  $lateral_direito[2] = '';
}

if(!isset($lateral_esquerdo)){
  $lateral_esquerdo[0] = '';
  $lateral_esquerdo[1] = "LE";
  $lateral_esquerdo[2] = '';
}

if(!isset($meia_direito)){
  $meia_direito[0] = '';
  $meia_direito[1] = "MD";
  $meia_direito[2] = '';
}

if(!isset($meia_esquerdo)){
  $meia_esquerdo[0] = '';
  $meia_esquerdo[1] = "ME";
  $meia_esquerdo[2] = '';
}

if(!isset($goleiro)){
  $goleiro = ['', 'GK', ''];
}

//pagina da escalacao
echo "<div class='tabcontent' id='Posicionamento' style='display: none;'>";
echo '<div class="pitch-scroll-wrapper" style="overflow-x: auto; width: 100%; padding: 10px 0; display: flex; justify-content: center; margin-bottom: 20px;">';
echo '<div id="sortable" class="ui-state">';

if (!function_exists('renderPitchPlayer')) {
    function renderPitchPlayer($drag_players, $playerData, $uniform_image, $default_pos, $className, $photo = '') {
        $posId = isset($playerData[1]) ? $playerData[1] : $default_pos;
        $nameHtml = (isset($playerData[0]) && $playerData[0] != '') ? $playerData[0] : '&nbsp;';
        
        $html = '<div id="' . $drag_players . $posId . '" class="' . $className . '">';
        if (isset($playerData[0]) && $playerData[0] != '') {
            $html .= '<span class="pitch-player-media">';
            $html .= '<img src="/images/uniformes/' . $uniform_image . '" class="pitch-uniform">';
            if (!empty($photo)) {
                $html .= '<img src="/images/jogadores/' . $photo . '" class="pitch-photo" onerror="this.style.display=\'none\'">';
            }
            $html .= '</span>';
        }
        $html .= '<div class="' . $default_pos . '"></div>';
        $html .= $nameHtml;
        $html .= '</div>';
        return $html;
    }
}

// ponta-esquerda
echo renderPitchPlayer($drag_players, $ponta_esquerda, $uniforme1_time, 'PE', 'pos-ataque', isset($ponta_esquerda[2]) ? $ponta_esquerda[2] : '');

// atacantes
$at1 = (count($atacante)==2 ? $atacante[0] : (count($atacante)==3 ? $atacante[0] : ['', 'AA', 'A0', '']));
$at1_pos = isset($at1[2]) ? $at1[2] : 'A0';
$at1_photo = isset($at1[3]) ? $at1[3] : '';
echo renderPitchPlayer($drag_players, $at1, $uniforme1_time, $at1_pos, 'pos-ataque', $at1_photo);

$at2 = (count($atacante)==1 ? $atacante[0] : (count($atacante)==3 ? $atacante[1] : ['', 'AB', 'A0', '']));
$at2_pos = isset($at2[2]) ? $at2[2] : 'A0';
$at2_photo = isset($at2[3]) ? $at2[3] : '';
echo renderPitchPlayer($drag_players, $at2, $uniforme1_time, $at2_pos, 'pos-ataque', $at2_photo);

$at3 = (count($atacante)==2 ? $atacante[1] : (count($atacante)==3 ? $atacante[2] : ['', 'AC', 'A0', '']));
$at3_pos = isset($at3[2]) ? $at3[2] : 'A0';
$at3_photo = isset($at3[3]) ? $at3[3] : '';
echo renderPitchPlayer($drag_players, $at3, $uniforme1_time, $at3_pos, 'pos-ataque', $at3_photo);

// ponta-direita
echo renderPitchPlayer($drag_players, $ponta_direita, $uniforme1_time, 'PD', 'pos-ataque', isset($ponta_direita[2]) ? $ponta_direita[2] : '');

// armadores
echo '<div id="nondraggable6">&nbsp</div>';

$arm1 = (count($armador)==2 ? $armador[0] : (count($armador)==3 ? $armador[0] : ['', 'AD', '']));
echo renderPitchPlayer($drag_players, $arm1, $uniforme1_time, 'MA', 'pos-meio-ataque', isset($arm1[2]) ? $arm1[2] : '');

$arm2 = (count($armador)==1 ? $armador[0] : (count($armador)==3 ? $armador[1] : ['', 'AE', '']));
echo renderPitchPlayer($drag_players, $arm2, $uniforme1_time, 'MA', 'pos-meio-ataque', isset($arm2[2]) ? $arm2[2] : '');

$arm3 = (count($armador)==2 ? $armador[1] : (count($armador)==3 ? $armador[2] : ['', 'AF', '']));
echo renderPitchPlayer($drag_players, $arm3, $uniforme1_time, 'MA', 'pos-meio-ataque', isset($arm3[2]) ? $arm3[2] : '');

echo '<div id="nondraggable10">&nbsp</div>';

// meia-esquerda
echo renderPitchPlayer($drag_players, $meia_esquerdo, $uniforme1_time, 'ME', 'pos-meio', isset($meia_esquerdo[2]) ? $meia_esquerdo[2] : '');

// meias-centrais
$m1 = (count($meia)==2 ? $meia[0] : (count($meia)==3 ? $meia[0] : ['', 'AG', '']));
echo renderPitchPlayer($drag_players, $m1, $uniforme1_time, 'MC', 'pos-meio', isset($m1[2]) ? $m1[2] : '');

$m2 = (count($meia)==1 ? $meia[0] : (count($meia)==3 ? $meia[1] : ['', 'AH', '']));
echo renderPitchPlayer($drag_players, $m2, $uniforme1_time, 'MC', 'pos-meio', isset($m2[2]) ? $m2[2] : '');

$m3 = (count($meia)==2 ? $meia[1] : (count($meia)==3 ? $meia[2] : ['', 'AI', '']));
echo renderPitchPlayer($drag_players, $m3, $uniforme1_time, 'MC', 'pos-meio', isset($m3[2]) ? $m3[2] : '');

// meia-direita
echo renderPitchPlayer($drag_players, $meia_direito, $uniforme1_time, 'MD', 'pos-meio', isset($meia_direito[2]) ? $meia_direito[2] : '');

// ala-esquerda
echo renderPitchPlayer($drag_players, $ala_esquerdo, $uniforme1_time, 'AE', 'pos-zaga-meio', isset($ala_esquerdo[2]) ? $ala_esquerdo[2] : '');

// volantes
$v1 = (count($volante)==2 ? $volante[0] : (count($volante)==3 ? $volante[0] : ['', 'AJ', '']));
echo renderPitchPlayer($drag_players, $v1, $uniforme1_time, 'V', 'pos-zaga-meio', isset($v1[2]) ? $v1[2] : '');

$v2 = (count($volante)==1 ? $volante[0] : (count($volante)==3 ? $volante[1] : ['', 'AK', '']));
echo renderPitchPlayer($drag_players, $v2, $uniforme1_time, 'V', 'pos-zaga-meio', isset($v2[2]) ? $v2[2] : '');

$v3 = (count($volante)==2 ? $volante[1] : (count($volante)==3 ? $volante[2] : ['', 'AL', '']));
echo renderPitchPlayer($drag_players, $v3, $uniforme1_time, 'V', 'pos-zaga-meio', isset($v3[2]) ? $v3[2] : '');

// ala-direita
echo renderPitchPlayer($drag_players, $ala_direito, $uniforme1_time, 'AD', 'pos-zaga-meio', isset($ala_direito[2]) ? $ala_direito[2] : '');

// lateral-esquerda
echo renderPitchPlayer($drag_players, $lateral_esquerdo, $uniforme1_time, 'LE', 'pos-zaga', isset($lateral_esquerdo[2]) ? $lateral_esquerdo[2] : '');

// zagueiros
$z1 = (count($zagueiro)==2 ? $zagueiro[0] : (count($zagueiro)==3 ? $zagueiro[0] : ['', 'AM', '']));
echo renderPitchPlayer($drag_players, $z1, $uniforme1_time, 'Z', 'pos-zaga', isset($z1[2]) ? $z1[2] : '');

$z2 = (count($zagueiro)==1 ? $zagueiro[0] : (count($zagueiro)==3 ? $zagueiro[1] : ['', 'AN', '']));
echo renderPitchPlayer($drag_players, $z2, $uniforme1_time, 'Z', 'pos-zaga', isset($z2[2]) ? $z2[2] : '');

$z3 = (count($zagueiro)==2 ? $zagueiro[1] : (count($zagueiro)==3 ? $zagueiro[2] : ['', 'AO', '']));
echo renderPitchPlayer($drag_players, $z3, $uniforme1_time, 'Z', 'pos-zaga', isset($z3[2]) ? $z3[2] : '');

// lateral-direita
echo renderPitchPlayer($drag_players, $lateral_direito, $uniforme1_time, 'LD', 'pos-zaga', isset($lateral_direito[2]) ? $lateral_direito[2] : '');

// goleiro
echo '<div id="nondraggable26">&nbsp</div>';
echo '<div id="nondraggable27">&nbsp</div>';

$gk = isset($goleiro) ? $goleiro : ['', 'GK', ''];
echo '<div id="nondraggable28" class="goleiro">';
echo '<span class="pitch-player-media">';
echo '<img src="/images/uniformes/' . $uniforme2_time . '" class="pitch-uniform">';
if (!empty($gk[2])) {
    echo '<img src="/images/jogadores/' . $gk[2] . '" class="pitch-photo" onerror="this.style.display=\'none\'">';
}
echo '</span>';
echo !empty($gk[0]) ? $gk[0] : '<p>&nbsp;</p>';
echo '</div>';

echo '<div id="nondraggable29">&nbsp</div>';
echo '<div id="nondraggable30">&nbsp</div>';
echo '</div>'; // close #sortable
echo '</div>'; // close scroll container

if($donoLogado){
echo '<div id="cobradoresCapitao">';
echo '<form action="" id="formCapitaoCobrancas">';
echo '<input type="hidden" name="clube" value="'.$idTime.'">';
echo '<label for="selectCapitao"> Capitão </label>';
echo '<select class="form-control" id="selectCapitao" name="capitaoSelect">';
echo '<option class="form-control" value="0">Selecione capitão...</option>';
foreach($lista_titulares as $titular){

    echo '<option class="form-control" value="'.$titular['idJogador'].'" '.($titular['capitao'] == 1? "selected" : "").'>'.$titular['nome']." (".$titular['mentalidade'].')</option>';
}
echo '</select>';
echo '<label for="selectPenal1"> Primeiro cobrador de pênalti </label>';

echo '<select class="form-control" id="selectPenal1" name="penal1Select">';
echo '<option class="form-control" value="0">Selecione cobrador...</option>';
foreach($lista_titulares as $titular){

    echo '<option class="form-control" value="'.$titular['idJogador'].'"'.($titular['cobrancaPenalti'] == 1? "selected" : "").'>'.$titular['nome']." (N-".$titular['nivel']." / C- ". $titular['cobradorFalta'] . ')</option>';
}
echo '</select>';
echo '<label for="selectPenal2"> Segundo cobrador de pênalti </label>';
echo '<select class="form-control" id="selectPenal2" name="penal2Select">';
echo '<option class="form-control" value="0">Selecione cobrador...</option>';
foreach($lista_titulares as $titular){

    echo '<option class="form-control" value="'.$titular['idJogador'].'"'.($titular['cobrancaPenalti'] == 2? "selected" : "").'>'.$titular['nome']." (N-".$titular['nivel']." / C- ". $titular['cobradorFalta'] .')</option>';
}
echo '</select>';
echo '<label for="selectPenal3"> Terceiro cobrador de pênalti </label>';
echo '<select class="form-control" id="selectPenal3" name="penal3Select">';
echo '<option class="form-control" value="0">Selecione cobrador...</option>';
foreach($lista_titulares as $titular){

    echo '<option class="form-control" value="'.$titular['idJogador'].'"'.($titular['cobrancaPenalti'] == 3? "selected" : "").'>'.$titular['nome']." (N-".$titular['nivel']." / C- ". $titular['cobradorFalta'] .')</option>';
}
echo '</select>';

echo '<input type="submit" class="submit-btn" value="Fazer alterações" name="cobradoresSubmit"/>';
echo '</form>';
echo '</div>'; // close #cobradoresCapitao
}

echo "</div>"; // close #Posicionamento

?>

<div id="modalProposta" class="modal">

  <form id='formProposta' method="POST" class="modal-content animate larger" action="/jogadores/fazer_proposta.php">
    <div class="imgcontainer">
      <span onclick="document.getElementById('modalProposta').style.display='none'" class="close" title="Close Modal">&times;</span>
    </div>

    <div class="container">
      <label for="nomeJogadorTransf"><b>Jogador</b></label>
      <input id="nomeJogadorTransf"  type="text" name="nomeJogador" disabled>
      
      <!--inclusão de empréstimo/tempo determinado-->
    
      <label for="tipoTransacao"><b>Tipo</b></label>
      <select id="tipoTransacao" name="tipoTransacao" class="form-control" required>
          <option selected value="0">Venda (tempo indeterminado)</option>
          <option value="1">Venda (com data de encerramento)</option>
          <option value="2">Empréstimo</option>
          <option value="3">Extensão de Empréstimo</option>
      </select>
      
      <label for="fimContrato"><b>Encerramento</b></label>
      <input id="fimContrato" type="date" name="fimContrato" class="form-control" min=<?php 
      
$date = new DateTime('now'); // Y-m-d
$date->add(new DateInterval('P30D'));
echo $date->format('Y-m-d');
?>

      <label for="valorJogadorTransf"><b>Proposta de transferência</b></label>
      <input id="valorJogadorTransf" type="number" name="valor" class='form-control' required>

      <label for="clubeDestinoTransf"><b>Clube de destino</b></label>
      <select id="clubeDestinoTransf"  name="clubeDestino" class="form-control" required>
          <?php
                $userId = (isset($_SESSION['user_id'])?$_SESSION['user_id']:0);
                $stmt = $time->read($userId);

                $closed_countries = [];
                $query_closed = "SELECT pais FROM janelas WHERE CASE WHEN padraoAbertura IS NULL THEN 1 ELSE CAST(SUBSTR(padraoAbertura, MONTH(NOW()), 1) AS UNSIGNED) END = 0";
                $stmt_closed = $db->prepare($query_closed);
                $stmt_closed->execute();
                while ($row_closed = $stmt_closed->fetch(PDO::FETCH_ASSOC)) {
                    $closed_countries[] = (int)$row_closed['pais'];
                }

                echo "<option value=''>Selecione time...</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    if (in_array((int)$paisTime, $closed_countries)) {
                        continue; // Não exibir times de países com janela fechada no período
                    }
                    echo "<option value='{$id}' data-sexo='{$Sexo}'>{$nome}</option>";
                }

                ?>

      </select>

      <label for="mensagemJogadorTransf"><b>Mensagem / Observação (opcional)</b></label>
      <textarea id="mensagemJogadorTransf" name="mensagem" class="form-control" rows="2" maxlength="500" placeholder="Ex: Aceitamos negociar parcelamento ou bônus..."></textarea>

      <input type="hidden" value="" name="idJogador" id="idJogadorTransf" required>
      <input type="hidden" value="<?php echo $idTime ?>" name="clubeOrigem" id="clubeOrigemTransf" required>
      <input type="hidden" value="<?php echo (isset($_SESSION['user_id'])?$_SESSION['user_id']:0); ?>" name="sorvete" required>

      <button type="submit" name="newsubmit" class="submitbtn">Propor transferência</button>
    </div>

    <div class="container">
      <button type="button" onclick="document.getElementById('modalProposta').style.display='none'" class="cancelbtn">✕ Cancelar</button>
    </div>
  </form>
</div>

<div id="modalPropostaTecnico" class="modal">

  <form id='formPropostaTecnico' method="POST" class="modal-content animate larger" action="/ligas/fazer_proposta_tecnico.php">
    <div class="imgcontainer">
      <span onclick="document.getElementById('modalPropostaTecnico').style.display='none'" class="close" title="Close Modal">&times;</span>
    </div>

    <div class="container">
      <label for="nomeTecnicoTransf"><b>Técnico</b></label>
      <input id="nomeTecnicoTransf"  type="text" name="nomeTecnico" disabled>

      <label for="clubeDestinoTecnico"><b>Clube de destino</b></label>
      <select id="clubeDestinoTecnico"  name="clubeDestinoTecnico" class="form-control" required>
          <?php
      // ler times do banco de dados
                $userId = (isset($_SESSION['user_id'])?$_SESSION['user_id']:0);
                $newStmt = $time->read($userId);

                echo "<option value=''>Selecione time...</option>";

                while ($new_row_category = $newStmt->fetch(PDO::FETCH_ASSOC)){
                    extract($new_row_category);
                    echo "<option value='{$id}' data-sexo='{$Sexo}'>{$nome}</option>";
                }

                ?>

      </select>

      <label for="mensagemTecnicoTransf"><b>Mensagem / Observação (opcional)</b></label>
      <textarea id="mensagemTecnicoTransf" name="mensagem" class="form-control" rows="2" maxlength="500" placeholder="Ex: Proposta para assumir o comando técnico da equipe..."></textarea>

      <input type="hidden" value="" name="idTecnicoTransf" id="idTecnicoTransf" required>
      <input type="hidden" value="<?php echo $idTime ?>" name="clubeOrigemTecnico" id="clubeOrigemTecnico" required>
      <input type="hidden" value="<?php echo (isset($_SESSION['user_id'])?$_SESSION['user_id']:0); ?>" name="sorveteTec" required>

      <button type="submit" name="newsubmit" class="submitbtn">Propor transferência</button>
    </div>

    <div class="container" style="background-color:#f1f1f1">
      <button type="button" onclick="document.getElementById('modalPropostaTecnico').style.display='none'" class="cancelbtn">✕ Cancelar</button>
    </div>
  </form>
</div>


<script>


//adicionado para ocultar data de encerramento em caso de venda direta
$(document).on("change", "#tipoTransacao", function(){
    if($(this).val() == 0){
        $('#fimContrato').hide();
        $('label[for="fimContrato"]').hide();
        $('#fimContrato').val('');
    } else {
        $('#fimContrato').show();
        $('label[for="fimContrato"]').show();
    }
});

$(document).ready(function(){
    $('#fimContrato').hide();
    $('label[for="fimContrato"]').hide();
    $('#fimContrato').val('');
});

$(document).on("click", '.proposta', function(event){
    var tbl_row = $(this).closest('tr');
    var idJogador = tbl_row.prop('id');
    var nomeJogador = tbl_row.find('.nomeEditavel').text();
    var valorInicial = tbl_row.find('.valor').text().replace(/\D/g, "");
    valorInicial = parseInt(valorInicial) * 1000;
    
    var idDonoVinculado = tbl_row.attr('data-id-dono-vinculado');
    var clubeOrigem = $('#clubeOrigemTransf').val(); // Este é o ID do time atual (idTime)
    var sexoJogador = tbl_row.attr("data-sexo");

    if (idDonoVinculado != 0 && idDonoVinculado != null && typeof idDonoVinculado !== 'undefined') {
        $('#tipoTransacao option[value="3"]').show();
        $('#tipoTransacao option[value="2"]').hide();
        if ($('#tipoTransacao').val() == '2') {
            $('#tipoTransacao').val('0');
            $('#tipoTransacao').trigger('change');
        }

        $("#clubeDestinoTransf option").each(function(){
            if($(this).val() == clubeOrigem){
                $(this).show();
                $(this).removeAttr("disabled");
                $(this).prop("selected", true);
            } else {
                $(this).attr("disabled", "disabled");
                $(this).hide();
            }
        });
    } else {
        $('#tipoTransacao option[value="3"]').hide();
        $('#tipoTransacao option[value="2"]').show();
        if ($('#tipoTransacao').val() == '3') {
            $('#tipoTransacao').val('0');
            $('#tipoTransacao').trigger('change');
        }

        $("#clubeDestinoTransf option").each(function(){
            if($(this).attr("data-sexo") == sexoJogador){
                if($(this).val() == clubeOrigem){
                    $(this).prop("disabled", true);
                    $(this).hide();
                } else {
                    $(this).show();
                    $(this).prop("disabled", false);
                }
            } else {
                $(this).attr("disabled", "disabled");
                $(this).hide();
            }
        });
    }

    $('#valorJogadorTransf').val(valorInicial);
    $('#nomeJogadorTransf').val(nomeJogador);
    $('#idJogadorTransf').val(idJogador);
    $('#modalProposta').show();
});

$(".propostaTecnico").click(function(){
    var nome = $(this).closest('tr').find('.nomeTecnico').html();
    // console.log(nome);
    var id = $(this).attr("id");
    id = id.split("c");
    id = parseInt(id[1]);
    $('#nomeTecnicoTransf').val(nome);

    sexoTecnico = $(this).closest("tr").attr("data-sexo");

    $("#clubeDestinoTecnico option").each(function(){

    if($(this).attr("data-sexo") == sexoTecnico){
        $(this).show();
    } else {
        $(this).hide();
    }

    });

    $("#modalPropostaTecnico").show();
    $("#idTecnicoTransf").val(id);

});

$("#formPropostaTecnico").submit(function(event){
    var formData = {
        'idTecnico' : $('input[name=idTecnicoTransf]').val(),
        'clubeOrigem' : $('input[name=clubeOrigemTecnico]').val(),
        'clubeDestino' : $('select[name=clubeDestinoTecnico]').val(),
        'sorveteTec' : $('input[name=sorveteTec]').val(),
        'mensagem' : $('#mensagemTecnicoTransf').val()
    };

    // console.log(formData);

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/ligas/fazer_proposta_tecnico.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
// console.log(data);
window.scrollTo(0, 0);

if (! data.success) {
    $('#modalPropostaTecnico').hide();
     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {

$('#modalPropostaTecnico').hide();
     $('#errorbox').append("<div class='alert alert-success'>O pedido foi realizado com sucesso!</div>");

}

// here we will handle errors and validation messages
}).fail(function(jqXHR, textStatus, errorThrown ){
    // console.log("Erro");
    // console.log(jqXHR);
    // console.log(textStatus);
    // console.log(errorThrown);
});


    event.preventDefault();
});

$("#formProposta").submit(function(event){
    var formData = {
        'idJogador' : $('input[name=idJogadorTransf]').val(),
        'clubeOrigem' : $('input[name=clubeOrigemTransf]').val(),
        'clubeDestino' : $('select[name=clubeDestinoTransf]').val(),
        'valor' : $('input[name=valorJogadorTransf]').val(),
        'sorvete' : $('input[name=sorvete]').val(),
        'tipoTransacao' : $('select[name=tipoTransacao').val(),
        'fimContrato' : $('input[name=fimContrato').val()
    };

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/jogadores/fazer_proposta.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
// console.log(data);
window.scrollTo(0, 0);

if (! data.success) {
    $('#modalProposta').hide();
     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {

$('#modalProposta').hide();
     $('#errorbox').append("<div class='alert alert-success'>O pedido foi realizado com sucesso!</div>");

}

// here we will handle errors and validation messages
}).fail(function(jqXHR, textStatus, errorThrown ){
    // console.log("Erro");
    // console.log(jqXHR);
    // console.log(textStatus);
    // console.log(errorThrown);
});


    event.preventDefault();
});

$(document).ready(function(){
	
	$(document).on("click", "#Recorde", function(){
		let idTime = $('#quadro-container').prop('class');
		let financialLocation = "/times/resumo_financeiro.php?id=" + idTime;
		window.location = financialLocation;
	});
	
    if( $("#selectPenal1").val() == 0 ){
        $("#selectPenal1").css("background-color", "lightcoral");
    }
    if( $("#selectPenal2").val() == 0 ){
        $("#selectPenal2").css("background-color", "lightcoral");
    }
    if( $("#selectPenal3").val() == 0 ){
        $("#selectPenal3").css("background-color", "lightcoral");
    }
    if( $("#selectCapitao").val() == 0 ){
        $("#selectCapitao").css("background-color", "lightcoral");

    }
});

$(document).on("click", "select[id^='select']", function(){
$(this).css("background-color", "white");
});

</script>

<script>

$(document).on("click", '.tablinks', function(event){
    event.preventDefault(); // impede qualquer navegação/scroll do anchor

    var id = $(this).html();

    // Atualiza a hash na URL sem rolar a página
    if (history.replaceState) {
        history.replaceState(null, null, '#' + id);
    }

    $(".tabcontent").each(function(index){
        $(this).hide();
    });

    $('#'+id).show();

    $(".tablinks").each(function(index){
        $(this).removeClass("active");
    });
    $(this).addClass("active");

});

$(document).on("click", '.clickablerow_tit', function(event){
    if($(this).hasClass('selected')){
        $(this).removeClass('selected');
    } else {
        $('.clickablerow_tit').each(function(index){
        $(this).removeClass('selected');
    });
    $(this).addClass('selected');
    }
});

$(document).on("click", '.clickablerow_res', function(event){
    if($(this).hasClass('selected')){
        $(this).removeClass('selected');
    } else {
    $('.clickablerow_res').each(function(index){
        $(this).removeClass('selected');
    });
    $(this).addClass('selected');
    }

});

$(document).on("click", '.clickablerow_sup', function(event){
    if($(this).hasClass('selected')){
        $(this).removeClass('selected');
    } else {
    $('.clickablerow_sup').each(function(index){
        $(this).removeClass('selected');
    });
    $(this).addClass('selected');
    }

});

$(document).on("click", "#trocar_titular_reserva", function(event){

try {
    var idTitular = $('.clickablerow_tit.selected').attr("id").replace(/\D/g, "");
    var idReserva = $('.clickablerow_res.selected').attr("id").replace(/\D/g, "");
    var idTime = $('#quadro-container').prop('class');
    var stringPosicaoReserva = $('.clickablerow_res.selected').find('td:nth(2)').html();
    var stringPosicaoTitular = $('.clickablerow_tit.selected').find('td:nth(2)').html();
} catch(err){
    alert('Por favor, selecione os dois jogadores!');
    return;
}

//verificar se são goleiros
if(stringPosicaoReserva == 'G' && stringPosicaoTitular != 'G' || stringPosicaoReserva != 'G' && stringPosicaoTitular == 'G'){
    alert('Impossível trocar goleiro por jogador de linha!');
    return;
}

if($('.clickablerow_res.selected').data('bloqueado-idade') == 1){
    alert('Jogador não pode ser promovido a titular: idade acima do limite da liga!');
    return;
}

//efetuar a troca por AJAX
var formData = {
        'idJogador1' : idTitular,
        'idJogador2' : idReserva,
        'tipoAlteracao' : 0,
        'clube' : idTime
    };

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : 'alteracao_elenco.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
            encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
// console.log(data);
// window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    reloadPageContent();

}

// here we will handle errors and validation messages
});


});

$(document).on("click", "#trocar_reserva_suplente", function(event){

try {
    var idTitular = $('.clickablerow_res.selected').attr("id").replace(/\D/g, "");
    var idReserva = $('.clickablerow_sup.selected').attr("id").replace(/\D/g, "");
    var idTime = $('#quadro-container').prop('class');

} catch(err){
    alert('Por favor, selecione os dois jogadores!');
    return;
}

if($('.clickablerow_sup.selected').data('bloqueado-idade') == 1){
    alert('Jogador não pode ser promovido a reserva: idade acima do limite da liga!');
    return;
}

//efetuar a troca por AJAX
var formData = {
        'idJogador1' : idTitular,
        'idJogador2' : idReserva,
        'tipoAlteracao' : 1,
        'clube' : idTime
    };

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : 'alteracao_elenco.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
            encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
// console.log(data);
// window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    reloadPageContent();

}

// here we will handle errors and validation messages
});


});

$(document).on("click", "#enviar_para_suplente", function(event){

try {
    var idTitular = $('.clickablerow_res.selected').attr("id").replace(/\D/g, "");
    var idTime = $('#quadro-container').prop('class');

} catch(err){
    alert('Por favor, selecione o jogador!');
    return;
}

//efetuar a troca por AJAX
var formData = {
        'idJogador1' : idTitular,
        'tipoAlteracao' : 3,
        'clube' : idTime
    };

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : 'alteracao_elenco.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
            encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
// console.log(data);
// window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    reloadPageContent();

}

// here we will handle errors and validation messages
});


});



$(document).on("click", "#enviar_para_reserva", function(event){

try {
    var idTitular = $('.clickablerow_sup.selected').attr("id").replace(/\D/g, "");
    var idTime = $('#quadro-container').prop('class');

} catch(err){
    alert('Por favor, selecione o jogador!');
    return;
}

if($('.clickablerow_sup.selected').data('bloqueado-idade') == 1){
    alert('Jogador não pode ser promovido a reserva: idade acima do limite da liga!');
    return;
}

var total_reserva = $('.clickablerow_res').length;
if(total_reserva > 11){
    alert('Já existem 12 jogadores na reserva!');
    return;
}

//efetuar a troca por AJAX
var formData = {
        'idJogador1' : idTitular,
        'tipoAlteracao' : 2,
        'clube' : idTime
    };

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : 'alteracao_elenco.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
            encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
// console.log(data);
// window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    reloadPageContent();

}

// here we will handle errors and validation messages
});


});

$(document).on("click", "#enviar_para_titular", function(event){

  try {
      var idTitular = $('.clickablerow_res.selected').attr("id").replace(/\D/g, "");
      var idTime = $('#quadro-container').prop('class');

  } catch(err){
      alert('Por favor, selecione o jogador!');
      return;
  }

  if($('.clickablerow_res.selected').data('bloqueado-idade') == 1){
      alert('Jogador não pode ser promovido a titular: idade acima do limite da liga!');
      return;
  }

  var total_titular = $('.clickablerow_tit').length;
  if(total_titular > 10){
      alert('Já existem 11 jogadores titulares!');
      return;
  }

  //efetuar a troca por AJAX
  var formData = {
          'idJogador1' : idTitular,
          'tipoAlteracao' : 4,
          'clube' : idTime
      };

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : 'alteracao_elenco.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
            encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
// console.log(data);
// window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    reloadPageContent();

}

// here we will handle errors and validation messages
});


});


$(document).on("click", "#remover_titular", function(event){

  try {
      var idTitular = $('.clickablerow_tit.selected').attr("id").replace(/\D/g, "");
      var idTime = $('#quadro-container').prop('class');

  } catch(err){
      alert('Por favor, selecione o jogador!');
      return;
  }

  var total_reserva = $('.clickablerow_res').length;
  if(total_reserva > 11){
      alert('Já existem 12 jogadores reservas!');
      return;
  }

  //efetuar a troca por AJAX
  var formData = {
          'idJogador1' : idTitular,
          'tipoAlteracao' : 5,
          'clube' : idTime
      };

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : 'alteracao_elenco.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
            encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
// console.log(data);
// window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    reloadPageContent();

}

// here we will handle errors and validation messages
});

});

$(document).on("click", ".quick-move-btn", function(e) {
    e.preventDefault();
    e.stopPropagation();
    var idJogador = $(this).data('id');
    var action = $(this).data('action');
    var idTime = $('#quadro-container').prop('class');
    var total_titular = $('.clickablerow_tit').length;
    var total_reserva = $('.clickablerow_res').length;

    var $row = $(this).closest('tr');
    if ((action === 'promote-suplente' || action === 'promote-reserva') && $row.data('bloqueado-idade') == 1) {
        alert('Jogador não pode ser escalado: idade acima do limite da liga!');
        return;
    }

    var tipoAlteracao;
    if (action === 'promote-suplente') {
        tipoAlteracao = 2; // Suplente -> Reserva
    } else if (action === 'demote-reserva') {
        tipoAlteracao = 3; // Reserva -> Suplente
    } else if (action === 'promote-reserva') {
        if (total_titular >= 11) {
            alert('Já existem 11 jogadores titulares!');
            return;
        }
        tipoAlteracao = 4; // Reserva -> Titular
    } else if (action === 'demote-titular') {
        if (total_reserva >= 12) {
            alert('Já existem 12 jogadores reservas!');
            return;
        }
        tipoAlteracao = 5; // Titular -> Reserva
    }

    var formData = {
        'idJogador1': idJogador,
        'tipoAlteracao': tipoAlteracao,
        'clube': idTime
    };

    $.ajax({
        type: 'POST',
        url: 'alteracao_elenco.php',
        data: formData,
        dataType: 'json',
        encode: true
    }).done(function(data) {
        if (!data.success) {
            $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');
        } else {
            reloadPageContent();
        }
    });
});


</script>


<script>

function initDragDrop() {

    function togglePlayerStyle(element) {
        var idJogador = element.attr('id').replace( /\D/g, '');
        var idTime = $('#quadro-container').prop('class');
        var posicao = element.children('div').attr('class');
        var posJogador;
        if(posicao == 'Aa'){
             element.children('div').removeClass('Aa').addClass('Am');
             posJogador = 'Am';
        } else if (posicao == 'Am'){
             element.children('div').removeClass('Am').addClass('Aa');
             posJogador = 'Aa';
        }

        var primeiraLetra = posicao.charAt(0);

        if((idJogador.length > 0) && (primeiraLetra.localeCompare('A') === 0)){
            var formData = {
                'idJogador1' : idJogador,
                'tipoAlteracao' : 7,
                'posicao1' : posJogador,
                'clube' : idTime
            };

            $.ajax({
                type        : 'POST',
                url         : 'alteracao_elenco.php',
                data        : formData,
                dataType    : 'json',
                encode      : true
            })
            .done(function(data) {
                if (!data.success) {
                     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');
                } else {
                    reloadPageContent();
                }
            });
        }
    }

    $("[id^=draggable]").contextmenu(function(event) {
        event.preventDefault();
        togglePlayerStyle($(this));
    });

    var touchTimer;
    $("[id^=draggable]").on("touchstart", function(event) {
        var element = $(this);
        touchTimer = setTimeout(function() {
            togglePlayerStyle(element);
        }, 700);
    }).on("touchend touchmove", function() {
        clearTimeout(touchTimer);
    });
    
    // Unbind previous draggable/droppable to avoid memory leaks or double binds if called multiple times?
    // jQuery UI destroys automatically if we remove elements, but here we might be re-binding to new elements.
    // If elements are new (replaced by AJAX), we don't need to unbind.
    
    $("[id^=draggable]").
    draggable({ revert: true, revertDuration: 0 }).
    droppable({
        drop:function(event,ui){
            swapNodes($(this).get(0),$(ui.draggable).get(0));
            var classeA;
            var classeB;
            var atrA = $(this).children('div').attr("class");
            var atrB = $(ui.draggable).children('div').attr("class");
            var atrC = false;
            if(atrA.localeCompare('A0') == 0){
                atrA = 'Aa';
            }
            if(atrB.localeCompare('A0') == 0){
                atrB = 'Aa';
            }

            if($(this).children('p').html() == undefined && ((($(ui.draggable).children('div').attr("class")).localeCompare('Am') == 0) || (($(ui.draggable).children('div').attr("class")).localeCompare('Aa') == 0))){
                atrC = true;
            }
            $(this).removeClass(function (index, className) {
                classeA = className.match (/(^|\s)pos-\S+/g);
                return (className.match (/(^|\s)pos-\S+/g) || []).join(' ');
            });
            $(ui.draggable).removeClass(function (index, className) {
                classeB = className.match (/(^|\s)pos-\S+/g);
                return (className.match (/(^|\s)pos-\S+/g) || []).join(' ');
            });
                $(ui.draggable).addClass(classeA);
                $(this).addClass(classeB);
                if(atrC){
                    $(this).children('div').removeClass(atrA).addClass("A0");
                } else {
                    $(this).children('div').removeClass(atrA).addClass(atrB);
                }

                $(ui.draggable).children('div').removeClass(atrB).addClass(atrA);

                //chamar AJAX para fazer a troca
                var idJogador2 = $(this).attr('id').replace( /\D/g, '');
                var posJogador2 = $(this).children('div').attr('class');
                var idJogador1 = $(ui.draggable).attr('id').replace( /\D/g, '');
                var posJogador1 = $(ui.draggable).children('div').attr('class');
                var idTime = $('#quadro-container').prop('class');

                if(posJogador1.localeCompare(posJogador2) == 0){
                    return;
                }

                if(idJogador1 == '' && idJogador2 == ''){
                    return;
                }

               //efetuar a troca por AJAX
var formData = {
        'idJogador1' : idJogador1,
        'idJogador2' : idJogador2,
        'tipoAlteracao' : 6,
        'posicao1' : posJogador1,
        'posicao2' : posJogador2,
        'clube' : idTime
    };

    // console.log("id1:"+idJogador1 + "pos" + posJogador1);
    // console.log("id2:"+idJogador2 + "pos" + posJogador2);

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : 'alteracao_elenco.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
            encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
// console.log(data);
//window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    reloadPageContent();

}

// here we will handle errors and validation messages
});



        }});
} // end initDragDrop

$(function() {
    initDragDrop();
});

function swapNodes(a, b) {
    var aparent= a.parentNode;
    var asibling= a.nextSibling===b? a : a.nextSibling;
    b.parentNode.insertBefore(a, b);
    aparent.insertBefore(b, asibling);

}

</script>

<script>

    $(document).on("submit", "#formCapitaoCobrancas", function(event) {
    event.preventDefault();

    // console.log($('#formCapitaoCobrancas').serialize());

    var errosForm;
    var successForm;
    $.post('alterar_capitao_cobradores.php', $('#formCapitaoCobrancas').serialize(),function( data ) {
        errosForm = data.error;
        successForm = data.success;
}, "json").done(function(data){
    reloadPageContent();
});



});

</script>

<script>

$(document).on("click", '.disponibilizar', function(event){
    event.preventDefault();

    var idJogador = $(this).closest('tr').prop('id');
	
	let formData = new FormData();
		
	formData.append('idJogador',idJogador);
	formData.append('alteracao',1);

    ajaxCallJogador(formData);
});

$(document).on("click", '.aposentar', function(event){
    event.preventDefault();

var idJogador = $(this).closest('tr').prop('id');
var idTime = $('#quadro-container').prop('class');

let formData = new FormData();
	
formData.append('idJogador',idJogador);
formData.append('alteracao',4);
formData.append('idTime',idTime);

// console.log(idJogador);

if(window.confirm("Deseja mesmo aposentar este jogador?")){
    ajaxCallJogador(formData);
}
});

$(document).on("click", '.expatriar', function(event){
    event.preventDefault();

var idJogador = $(this).closest('tr').prop('id');
var idTime = $('#quadro-container').prop('class');

let formData = new FormData();
	
formData.append('idJogador',idJogador);
formData.append('alteracao',7);
formData.append('idTime',idTime);

if(window.confirm("Deseja mesmo expatriar este jogador?")){
    ajaxCallJogador(formData);
}
});

$(document).on("click", '.demitir', function(event){
    event.preventDefault();

    var idJogador = $(this).closest('tr').prop('id');
    var idTime = $('#quadro-container').prop('class');
	
	let formData = new FormData();
	
	formData.append('idJogador',idJogador);
	formData.append('alteracao',2);
	formData.append('idTime',idTime);

    if(window.confirm("Deseja mesmo demitir?")){
    ajaxCallJogador(formData);
}

});

$(document).on("click", '.demitirTecnico', function(event){
    event.preventDefault();

var idTecnico = $(this).closest('tr').prop('id').replace(/\D/g, "");;
var idTime = $('#quadro-container').prop('class');

var formData = {
    'idTecnico' : idTecnico,
    'idTime' : idTime,
    'alteracao' : 2
};

if(window.confirm("Deseja mesmo demitir?")){
ajaxCallTecnico(formData);
}

});

$(document).on("click", '.desconvocarTecnico', function(event){
    event.preventDefault();

var idTecnico = $(this).closest('tr').prop('id').replace(/\D/g, "");;
var idTime = $('#quadro-container').prop('class');

var formData = {
    'idTecnico' : idTecnico,
    'idTime' : idTime,
    'alteracao' : 2
};

// console.log(formData);

if(window.confirm("Deseja mesmo desconvocar?")){
ajaxCallTecnico(formData);
}

});

$(document).on("click", '.desconvocar', function(event){
    event.preventDefault();

var idJogador = $(this).closest('tr').prop('id');
var idTime = $('#quadro-container').prop('class');

let formData = new FormData();
	
formData.append('idJogador',idJogador);
formData.append('alteracao',2);
formData.append('idTime',idTime);

if(window.confirm("Deseja mesmo desconvocar?")){
    ajaxCallJogador(formData);
}


});

$(document).on("click", '.editar', function(event){
    isDataDirty = true;
var tbl_row = $(this).closest('tr');

        tbl_row.find('span').each(function(index, val){
            $(this).attr('original_entry', $(this).html());

        });

tbl_row.find("td:last-child .cell-value > a").hide();
tbl_row.find(".salvar").show();
tbl_row.find(".cancelar").show();
tbl_row.find('.hiddenInput').show();
tbl_row.find('.playerThumb').addClass('editableThumb');

//garantir que o dono do time está logado e que ele é o dono do jogador também (duplo check, JS e PHP)
var donoTime = $("tr th:last-child").prop("id").replace(/\D/g, "");
var donoJogador = tbl_row.find("td:last-child").prop("id").replace(/\D/g, "");
//var donoJogador =9;

if(donoTime.localeCompare(donoJogador) == 0 || donoJogador == 0){
    var isDono = true;
} else {
    var isDono = false;
}

var idJogador = tbl_row.prop('id');

// console.log(isDono);

    // Jersey (always editable if permitted by role context, but here logic assumes viewing user has rights to toggle edit mode in general)
    tbl_row.find('.jersey-icon').hide();
    tbl_row.find('input.numeroCamisa').css('display', 'inline-block');

    if(isDono){
    tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
    tbl_row.find('.nascimentoEIdade').hide();
	tbl_row.find('.encerramentoFixo').hide();
    tbl_row.find('.encerramento').show();
    tbl_row.find('.nascimento').show();
    tbl_row.find('.posicao').hide();

    //valor original pais

    //valor original pais
    var idPais = tbl_row.find('.comboPais').prop("id");
    tbl_row.find(".comboPais").val(idPais);

    tbl_row.find('.nomePais').hide();
    tbl_row.find('.comboPais').show();

    //valor original nascimento
    var nascimento = tbl_row.find(".nascimentoEIdade").html().split(" ")[0];
    var day = nascimento.split("-")[0];
    var month = nascimento.split("-")[1];
    var year = nascimento.split("-")[2];
    var nascimentoInicial = year + "-" + month + "-" + day;
    tbl_row.find('.nascimento').prop("value",nascimentoInicial);
	
		//console.log(nascimentoInicial);
	
	//valor original encerramento
    var encerramento = tbl_row.find(".encerramentoFixo").html();

		var day = encerramento.split("-")[0];
		var month = encerramento.split("-")[1];
		var year = encerramento.split("-")[2];

		var encerramentoInicial = year + "-" + month + "-" + day;

    tbl_row.find('.encerramento').prop("value",encerramentoInicial);
	
		//valor original desde
	let ultimo_clube = tbl_row.find(".ultimoClube").attr("data-ultimo-clube");
	if(ultimo_clube == 0){
		
    var desde = tbl_row.find(".desdeFixo").html();

		let desde_day = desde.split("-")[0];
		let desde_month = desde.split("-")[1];
		let desde_year = desde.split("-")[2];

		var desdeInicial = desde_year + "-" + desde_month + "-" + desde_day;

    tbl_row.find('.desde').prop("value",desdeInicial);
	
	tbl_row.find('.desdeFixo').hide();
	tbl_row.find('.desde').show();
	}
	
}


tbl_row.find('.valorEditavel').attr('contenteditable', 'true').addClass('editavel');

tbl_row.find('.nivelEMod').hide();
tbl_row.find('.nivel').attr('contenteditable', 'true').show();

//verificar se é goleiro
var stringPosicoes = tbl_row.find('.posicoesAtuais').html();
var isGoleiro = stringPosicoes.localeCompare("G");

if(isGoleiro){
    tbl_row.find('.posicoesAtuais').hide();
    tbl_row.find('.comboPosicoes').show();
}

//valor original posicoes
var arrPosicoes = stringPosicoes.split('-');

tbl_row.find('.comboPosicoes option').each(function(){
    if($.inArray($(this).html(), arrPosicoes) !== -1){
        $(this).prop("selected","selected");
    }
});

//valor original nivel
var nivel = tbl_row.find(".nivelEMod").html().split(" ")[0];
var mod = tbl_row.find(".nivelEMod").html().split(" ")[1].replace(/[{()}]/g, '');
tbl_row.find('.nivel').html(parseInt(nivel)+parseInt(mod));

//valor original valor
var valor = tbl_row.find(".valor").html().replace(/\D/g, "");
var valor = parseInt(valor)*1000;
tbl_row.find(".valor").html(valor);

});

        $(document).on("click", '.cancelar', function(){
            isDataDirty = false;
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find("td:last-child .cell-value > a").show();
        tbl_row.find(".salvar").hide();
        tbl_row.find(".cancelar").hide();
        tbl_row.find('.posicoesAtuais').show();
        tbl_row.find('.comboPosicoes').hide();
        tbl_row.find('.valorEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nivelEMod').show();
        tbl_row.find('.nivel').attr('contenteditable', 'false').hide();
        tbl_row.find('.nascimentoEIdade').show();
        tbl_row.find('.nascimento').hide();
        tbl_row.find('.posicao').show();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.comboPais').hide();
		tbl_row.find('.encerramentoFixo').show();
		tbl_row.find('.encerramento').hide();
		tbl_row.find('.desdeFixo').show();
		tbl_row.find('.desde').hide();
		tbl_row.find('.hiddenInput').hide();
		tbl_row.find('.playerThumb').removeClass('editableThumb');
        
        // Jersey
        tbl_row.find('.jersey-icon').show();
        tbl_row.find('input.numeroCamisa').css('display', 'none');

        tbl_row.find('span').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });
    });

    $(document).on("click", '.exportar', function(){

      var tbl_row =  $(this).closest('tr');
      var idJogador = $(this).closest('tr').attr("id");
      var idTime = $('#quadro-container').prop('class');



      $.ajax({
        url: 'get_jog_info.php',
        type: 'POST',
        dataType: 'json',
        data: {idJogador: idJogador,
                  idTime: idTime}
      })
      .done(function(response) {

        if(response.error){
            window.scrollTo(0, 0);
            $('#errorbox').html('<div class="alert alert-danger"><span class="closebtn" style="float: right; cursor: pointer; font-weight: bold;" onclick="$(this).parent().fadeOut();">&times;</span>' + response.error + '</div>');
            return;
        }

        var xmlData = "<jogadorExportacao>\n <jogador>\n <ID>" +
          response[0][0].idJogador + "</ID>\n <Nome>" +
          response[0][0].nomeJogador +  "</Nome>\n <Idade>" +
          response[0][0].Idade +  "</Idade>\n <Nivel>" +
          response[0][0].Nivel +  "</Nivel>\n <Potencial>" +
          0 +  "</Potencial>\n <CrescBase>" +
          0 +  "</CrescBase>\n <Mentalidade>" +
          response[0][0].Mentalidade +  "</Mentalidade>\n <CobradorFalta>" +
          response[0][0].CobradorFalta +  "</CobradorFalta>\n <apto>" +
          "true" +  "</apto>\n </jogador>\n";


          if(response[0][0].StringPosicoes[0] == "0"){
           xmlData += "<atributosJogador>\n <Jogador>" +
           response[0][0].idJogador + "</Jogador>\n <Marcacao>" +
           response[0][0].Marcacao +  "</Marcacao>\n <Desarme>" +
           response[0][0].Desarme +  "</Desarme>\n <VisaoJogo>" +
           response[0][0].VisaoJogo +  "</VisaoJogo>\n <Movimentacao>" +
           response[0][0].Movimentacao +  "</Movimentacao>\n <Cruzamentos>" +
           response[0][0].Cruzamentos +  "</Cruzamentos>\n <Cabeceamento>" +
           response[0][0].Cabeceamento +  "</Cabeceamento>\n <Tecnica>" +
           response[0][0].Tecnica +  "</Tecnica>\n <ControleBola>" +
           response[0][0].ControleBola +  "</ControleBola>\n <Finalizacao>" +
           response[0][0].Finalizacao +  "</Finalizacao>\n <FaroGol>" +
           response[0][0].FaroGol +  "</FaroGol>\n <Velocidade>" +
           response[0][0].Velocidade +  "</Velocidade>\n <Forca>" +
           response[0][0].Forca +  "</Forca>\n <Determinacao>" +
           '1' +  "</Determinacao>\n <DeterminacaoOriginal>" +
           '1' +  "</DeterminacaoOriginal>\n <CondicaoFisica>" +
           "100.0"+  "</CondicaoFisica>\n <modificador>" +
           "1.0" +  "</modificador>\n </atributosJogador>\n";
         } else if(response[0][0].StringPosicoes[0] == "1"){
            xmlData += "<atributosGoleiro>\n <Goleiro>" +
            response[0][0].idJogador + "</Goleiro>\n <Reflexos>" +
            response[0][0].Reflexos +  "</Reflexos>\n <Seguranca>" +
            response[0][0].Seguranca +  "</Seguranca>\n <Saidas>" +
            response[0][0].Saidas +  "</Saidas>\n <JogoAereo>" +
            response[0][0].JogoAereo +  "</JogoAereo>\n <Lancamentos>" +
            response[0][0].Lancamentos +  "</Lancamentos>\n <DefesaPenaltis>" +
            response[0][0].DefesaPenaltis +  "</DefesaPenaltis>\n <Determinacao>" +
            '1' +  "</Determinacao>\n <DeterminacaoOriginal>" +
            '1' +  "</DeterminacaoOriginal>\n <CondicaoFisica>" +
            "100.0"+  "</CondicaoFisica>\n </atributosGoleiro>\n";
         }

        xmlData += "<posicoesJogador>\n ";

           xmlData += "<Jogador>" +
           response[0][0].idJogador + "</Jogador>\n <G>" +
           !!+response[0][0].StringPosicoes[0] +  "</G>\n <LD>" +
           !!+response[0][0].StringPosicoes[1] +  "</LD>\n <LE>" +
           !!+response[0][0].StringPosicoes[2] +  "</LE>\n <Z>" +
           !!+response[0][0].StringPosicoes[3] +  "</Z>\n <AD>" +
           !!+response[0][0].StringPosicoes[4] +  "</AD>\n <AE>" +
           !!+response[0][0].StringPosicoes[5] +  "</AE>\n <V>" +
           !!+response[0][0].StringPosicoes[6] +  "</V>\n <MD>" +
           !!+response[0][0].StringPosicoes[7] +  "</MD>\n <ME>" +
           !!+response[0][0].StringPosicoes[8] +  "</ME>\n <MC>" +
           !!+response[0][0].StringPosicoes[9] +  "</MC>\n <PD>" +
           !!+response[0][0].StringPosicoes[10] +  "</PD>\n <PE>" +
           !!+response[0][0].StringPosicoes[11] +  "</PE>\n <MA>" +
           !!+response[0][0].StringPosicoes[12] +  "</MA>\n <Am>" +
           !!+response[0][0].StringPosicoes[13] +  "</Am>\n <Aa>" +
           !!+response[0][0].StringPosicoes[14] +  "</Aa>\n </posicoesJogador>\n";



          xmlData += "<nacionalidade>" + response[0][0].Nacionalidade + "</nacionalidade> \n";
          xmlData += "</jogadorExportacao>";

        var fileName = response[0][0].nomeJogador+".jog";

        function download(filename, text) {
            var element = document.createElement('a');
            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
            element.setAttribute('download', filename);

            element.style.display = 'none';
            document.body.appendChild(element);

            element.click();

            document.body.removeChild(element);
        }

        download(fileName,xmlData);
      })
      .fail(function() {
        // console.log("error");
      });


    });
    
    // Proposta handler consolidated above


    $(document).on("submit", "#formProposta", function(event) {
        event.preventDefault();
        
        $.ajax({
            type: 'POST',
            url: '/jogadores/fazer_proposta.php',
            data: $(this).serialize(),
            dataType: 'json',
            encode: true
        })
        .done(function(data) {
            $('#modalProposta').hide();
            if (!data.success) {
                $('#errorbox').append('<div class="alert alert-danger">' + data.error + '</div>');
            } else {
                 if(data.error && data.error != "") {
                    $('#errorbox').append('<div class="alert alert-warning">' + data.error + '</div>');
                 } else {
                    $('#errorbox').append('<div class="alert alert-success">Proposta enviada com sucesso!</div>');
                 }
                 reloadPageContent();
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            $('#modalProposta').hide();
            $('#errorbox').append('<div class="alert alert-danger">Erro de conexão: ' + errorThrown + '</div>');
        });
    });

    $(document).on("click", '.salvar', function(){
        isDataDirty = false;
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find(".salvar").hide();
        tbl_row.find(".cancelar").hide();
        tbl_row.find(".editar").show();
        tbl_row.find(".aposentar").show();
        tbl_row.find(".disponibilizar").show();
        tbl_row.find(".demitir").show();
        tbl_row.find(".proposta").show();
        tbl_row.find('.posicoesAtuais').show();
        tbl_row.find('.comboPosicoes').hide();
        tbl_row.find('.valorEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nivelEMod').show();
        tbl_row.find('.nivel').attr('contenteditable', 'false').hide();
        tbl_row.find('.nascimentoEIdade').show();
        tbl_row.find('.nascimento').hide();

        // Jersey cleanup
        tbl_row.find('.jersey-icon').show();
        tbl_row.find('input.numeroCamisa').css('display', 'none');
        tbl_row.find('.posicao').show();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.comboPais').hide();
		tbl_row.find('.encerramentoFixo').show();
		tbl_row.find('.encerramento').hide();
		tbl_row.find('.desdeFixo').show();
		tbl_row.find('.desde').hide();
		tbl_row.find('.hiddenInput').hide();
		tbl_row.find('.playerThumb').removeClass('editableThumb');
		
        //coleta de valores

        //check se é dono do jogador
        //garantir que o dono do time está logado e que ele é o dono do jogador também (duplo check, JS e PHP)
        var donoTime = $("tr th:last-child").prop("id").replace(/\D/g, "");
        var donoJogador = tbl_row.find("td:last-child").prop("id").replace(/\D/g, "");
        //var donoJogador =9;

        if(donoTime.localeCompare(donoJogador) == 0 || donoJogador == 0){
            var isDono = true;
        } else {
            var isDono = false;
        }

        var idJogador = tbl_row.prop('id');
		
		let ultimo_clube = tbl_row.find(".ultimoClube").attr("data-ultimo-clube");
	

        if(isDono){
            var nome = tbl_row.find('.nomeEditavel').html();
            var nacionalidade = tbl_row.find(".comboPais").val();
            var nascimento = tbl_row.find(".nascimento").val();
			var encerramento = tbl_row.find(".encerramento").val();
			
			
			
			if(ultimo_clube == 0){
				var desde = tbl_row.find(".desde").val();
			}
        }
		
		//foto
		var inputFoto = (tbl_row.find('#foto'+idJogador))[0];
		var foto;

		if (inputFoto.files.length > 0) {
		   foto = inputFoto.files[0];
		} else {
		   foto = null;
		}

        var valor = parseInt(tbl_row.find(".valorEditavel").html());
        var nivel = tbl_row.find(".nivel").html();
        var idTime = $('#quadro-container').prop('class');
		
		var stringPosicoes = tbl_row.find('.posicoesAtuais').html();
		var isGoleiro = stringPosicoes.localeCompare("G");
	
		if(isGoleiro == 0){
			var posicoes = ["1"];
		} else {
			var posicoes = tbl_row.find(".comboPosicoes").val();
		}
		
		var formData = new FormData();
		
		formData.append('idJogador',idJogador);
		formData.append('alteracao',3);
		formData.append('posicoes',posicoes);
		formData.append('nivel',nivel);
		formData.append('valor',valor);
		formData.append('idTime',idTime);


if(isDono){
	formData.append('nome',nome);
	formData.append('nacionalidade',nacionalidade);
	formData.append('nascimento',nascimento);
	formData.append('encerramento',encerramento);

	if(ultimo_clube ==0){
		formData.append('desde',desde);
	}
}

    var numeroCamisa = tbl_row.find("input.numeroCamisa").val();
    formData.append('numeroCamisa', numeroCamisa);

     if(foto != null){
		formData.append('foto',foto);
     }

        // for (var pair of formData.entries()) {
    // console.log(pair[0]+ ', ' + pair[1]);
// }
    ajaxCallJogador(formData);


    });







function ajaxCallJogador(formData){

    $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/jogadores/editar_jogador.php', // the url where we want to POST
            data        : formData, // our data object
            processData : false,
            contentType : false,
			cache: false,
            dataType    : 'json', // what type of data do we expect back from the server
            //encode          : true
        })

                    .done(function(data) {

            // log data to the console so we can see
            // console.log(data);
            window.scrollTo(0, 0);

            if (! data.success) {
                $('#modalProposta').hide();
                $('#errorbox').append('<div class="alert alert-danger">Não foi possível concluir a ação, '+data.error+'</div>');


            } else {

            $('#modalProposta').hide();
                //$('#errorbox').append("<div class='alert alert-success'>A ação foi concluída com sucesso!</div>");

                reloadPageContent();

            }

            // here we will handle errors and validation messages
            }).fail(function(jqXHR, textStatus, errorThrown ){
                // console.log("Erro");
                // console.log(jqXHR);
                // console.log(textStatus);
                // console.log(errorThrown);
                $('#modalProposta').hide();
                $('#errorbox').append('<div class="alert alert-danger">Não foi possível concluir, '+errorThrown+'</div>');
            });
}

function ajaxCallTecnico(formData){

$.ajax({
        type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
        url         : '/ligas/editar_tecnico.php', // the url where we want to POST
        data        : formData, // our data object
        // processData : false,
        // contentType : false,
        dataType    : 'json', // what type of data do we expect back from the server
                    encode          : true
    })

                .done(function(data) {

        // log data to the console so we can see
        // console.log(data);
        window.scrollTo(0, 0);

        if (! data.success) {
            //$('#modalPropostaTecnico').hide();
            $('#errorbox').append('<div class="alert alert-danger">Não foi possível concluir a ação, '+data.error+'</div>');


        } else {

        //$('#modalPropostaTecnico').hide();
            //$('#errorbox').append("<div class='alert alert-success'>A ação foi concluída com sucesso!</div>");

            reloadPageContent();

        }

        // here we will handle errors and validation messages
        }).fail(function(jqXHR, textStatus, errorThrown ){
            // console.log("Erro");
            // console.log(jqXHR);
            // console.log(textStatus);
            // console.log(errorThrown);
            $('#modalPropostaTecnico').hide();
            $('#errorbox').append('<div class="alert alert-danger">Não foi possível concluir, '+errorThrown+'</div>');
        });
}

$(document).on("click", '.editarTecnico', function(event){
    isDataDirty = true;
var tbl_row = $(this).closest('tr');

        tbl_row.find('span').each(function(index, val){
            $(this).attr('original_entry', $(this).html());

        });


tbl_row.find("td:last-child .cell-value > a").hide();
tbl_row.find(".salvarTecnico").show();
tbl_row.find(".cancelarTecnico").show();

//garantir que o dono do time está logado e que ele é o dono do jogador também (duplo check, JS e PHP)
var donoTime = $("tr th:last-child").prop("id").replace(/\D/g, "");
var donoTecnico = tbl_row.find("td:last-child").prop("id").replace(/\D/g, "");
//var donoJogador =9;

if(donoTime.localeCompare(donoTecnico) == 0 || donoTecnico == 0){
    var isDono = true;
} else {
    var isDono = false;
}

var idTecnico = tbl_row.prop('id');

  if(isDono){

    tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
    // tbl_row.find('.nomeEditavel').css("cursor","text");
    // tbl_row.find('.nomeEditavel').css("pointer-events","none");
    tbl_row.find('.nomePais').hide();

    var paisId = tbl_row.find('.comboPais').attr('id');
    tbl_row.find('.comboPais').show().val(paisId);




    tbl_row.find('.posicao').hide();
	
	    //valor original nascimento
    nascimento = tbl_row.find(".nascimentoEIdade").html().split(" ")[0];
    day = nascimento.split("-")[0];
    month = nascimento.split("-")[1];
    year = nascimento.split("-")[2];
    nascimentoInicial = year + "-" + month + "-" + day;
    tbl_row.find('.nascimento').val(nascimentoInicial);

	tbl_row.find('.nascimentoEIdade').hide();
    tbl_row.find('.nascimento').show();

			//valor original desde
	let ultimo_clube = tbl_row.find(".ultimoClube").attr("data-ultimo-clube");
	if(ultimo_clube == 0){
		
    var desde = tbl_row.find(".desdeFixo").html();

		let desde_day = desde.split("-")[0];
		let desde_month = desde.split("-")[1];
		let desde_year = desde.split("-")[2];

		var desdeInicial = desde_year + "-" + desde_month + "-" + desde_day;

    tbl_row.find('.desde').prop("value",desdeInicial);
	
	tbl_row.find('.desdeFixo').hide();
	tbl_row.find('.desde').show();
	}
  }

  tbl_row.find('.nivel').attr('contenteditable', 'true').addClass('editavel');


});


        $(document).on("click", '.cancelarTecnico', function(){
            isDataDirty = false;
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nivel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nascimentoEIdade').show();
        tbl_row.find('.nascimento').hide();
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.posicao').show();
        tbl_row.find('.nomePais').show();
        tbl_row.find("td:last-child .cell-value > a").show();
        tbl_row.find('.salvarTecnico').hide();
        tbl_row.find('.cancelarTecnico').hide();
		tbl_row.find('.desdeFixo').show();
		tbl_row.find('.desde').hide();

        tbl_row.find('a').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });

        tbl_row.find('span').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });

        tbl_row.find('input').each(function(index, val){
            $(this).val($(this).attr('data-original-entry'));
        });


    });


    $(document).on("click", '.salvarTecnico', function(){
        isDataDirty = false;
      var tbl_row =  $(this).closest('tr');
      tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
      tbl_row.find('.nivel').attr('contenteditable', 'false').removeClass('editavel');
      tbl_row.find('.nascimentoEIdade').show();
      tbl_row.find('.nascimento').hide();
      tbl_row.find('.comboPais').hide();
      tbl_row.find('.nomePais').show();
      tbl_row.find('.salvarTecnico').hide();
      tbl_row.find('.cancelarTecnico').hide();
      tbl_row.find('.editarTecnico').show();
	  		tbl_row.find('.desdeFixo').show();
		tbl_row.find('.desde').hide();

        var idTecnico = tbl_row.attr('id').replace(/\D/g, "");

        //check se é dono do jogador
        //garantir que o dono do time está logado e que ele é o dono do jogador também (duplo check, JS e PHP)
        var donoTime = $("tr th:last-child").prop("id").replace(/\D/g, "");
        var donoTecnico = tbl_row.find("td:last-child").prop("id").replace(/\D/g, "");
        //var donoTime = "9";

        if (typeof donoTime === 'undefined'){
            donoTime = donoTecnico;
        }

        if(donoTime.localeCompare(donoTecnico) == 0){

            var isDono = true;
        } else {
            var isDono = false;
        }

        var nivel = tbl_row.find(".nivel").html();
        var idTime = $('#quadro-container').prop('class');

        // var formData = new FormData();
        var formData = {
            'idTecnico' : idTecnico,
            'alteracao' : 3,
            'nivel' : nivel,
            'idTime' : idTime
        }

        if(isDono){
            var nome = tbl_row.find('.nomeEditavel').html();
            var nascimento = tbl_row.find(".nascimento").val();
            var pais = tbl_row.find('.comboPais').val();
			
			let ultimo_clube = tbl_row.find(".ultimoClube").attr("data-ultimo-clube");
			
			if(ultimo_clube == 0){
				var desde = tbl_row.find(".desde").val();
			}

            var moreData = {
                    'nome' : nome,
                    'pais' : pais,
                    'nascimento' : nascimento,

                }

            $.extend(formData,moreData);
			
				if(ultimo_clube ==0){
		    var evenMoreData = {
            'desde' : desde

        }
				}
		$.extend(formData,evenMoreData);


        }



     ajaxCallTecnico(formData);

     });


</script>

</div> <!-- close #quadro-container -->
</div> <!-- close .propostas-card -->
</main> <!-- close .propostas-container -->

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
