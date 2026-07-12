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
    $addArray = array($id, $sigla, $bandeira);
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

$id = $_GET['team'];
$idTime = $id;

// query times
$info = $time->readInfo($id);
$nome_time = $info['Nome'];
$sigla_time = $info['TresLetras'];
$estadio_time = $info['Estadio'];
$estadio_capacidade = $info['Capacidade'];
$escudo_time = $info['Escudo'];
$foto_estadio = $info['fotoEstadio'];
$uniforme1_time = $info['Uniforme1'];
$uniforme2_time = $info['Uniforme2'];
$pais_time = $info['Pais'];
$liga_time = $info['liga'];
$liga_id = $info['liga_id'];
$pais_id = $info['pais_id'];
$donoPais = $info['donoPais'];
$status_time = $info['status'];


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
$mediaIdade = number_format($info['mediaIdade'],1);
$estrangeiros = $info['estrangeiros'];
$jogadores_selecao = $info['emSelecao'];
$valor_total_clube = number_format($info['valorTotal']/1000000,1) . "M";
$recorde_transferencia = $time->balancoTransferencias($idTime);
$recorde_transferencia = number_format($recorde_transferencia/1000000,1) . "M";
$nivel_medio = number_format($info['mediaNivel'], 1);
$nivel_medio_onze = number_format($info['mediaNivelOnze'],1);


if($liga_time != ''){
    $liga_time = " - ". $liga_time;
}

//$escudo_imagem = explode(".",$escudo_time);
//$uniforme1_imagem = explode(".",$uniforme1_time);
//$uniforme2_imagem = explode(".",$uniforme2_time);


$page_title = $nome_time;
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'ligas';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo "<div style='clear:both; float:center'></div>";

echo "
<style>
.jersey-icon {
    width: 32px;
    height: 32px;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    background-image: url(\"data:image/svg+xml,%3Csvg width='800px' height='800px' viewBox='0 -63.5 1151 1151' version='1.1' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M902.984598 1024h-653.972493a27.200282 27.200282 0 0 1-27.200282-27.200282v-562.13916l-5.945204 7.16274a27.200282 27.200282 0 0 1-38.300588 3.561942L9.830959 306.184559a27.200282 27.200282 0 0 1-3.561942-38.300587L220.490667 9.831011A27.200282 27.200282 0 0 1 241.421931 0.000052h174.599905a27.200282 27.200282 0 0 1 13.341091 3.497179l146.143229 82.248472L711.002418 3.885806a27.200282 27.200282 0 0 1 14.066431-3.885754H910.56182a27.200282 27.200282 0 0 1 20.931264 9.830959l214.22165 258.052961a27.200282 27.200282 0 0 1-3.561942 38.300587l-167.735072 139.239539a27.200282 27.200282 0 0 1-38.300588-3.561942l-5.945204-7.162741v562.139161a27.200282 27.200282 0 0 1-27.18733 27.161424z m-626.772211-54.400564h599.57193V359.354634A27.200282 27.200282 0 0 1 923.915863 341.946454l36.694476 44.206934 125.872543-104.48794L897.816545 54.400616H732.633118l-142.568335 86.121273a27.200282 27.200282 0 0 1-27.407522 0.427433L408.885 54.400616H254.193111L65.500869 281.704305l125.872543 104.487941L228.08084 341.946454a27.200282 27.200282 0 0 1 48.131547 17.369323z' fill='%23cccccc' /%3E%3Cpath d='M574.54767 144.498312H336.946731a13.600141 13.600141 0 0 1-11.190973-21.31984l72.158462-104.526798a13.600141 13.600141 0 0 1 22.381946 15.45235l-57.431452 83.194006h211.682956a13.600141 13.600141 0 0 1 0 27.200282z' fill='%23cccccc' /%3E%3Cpath d='M802.900513 144.498312H565.312527a13.600141 13.600141 0 0 1 0-27.200282h211.682956l-57.431453-83.194006a13.600141 13.600141 0 0 1 22.381947-15.45235l72.158462 104.526798a13.600141 13.600141 0 0 1-11.190973 21.31984zM366.19351 92.234913H220.853337a13.600141 13.600141 0 0 1 0-27.200282h145.327221a13.600141 13.600141 0 0 1 0 27.200282z' fill='%23cccccc' /%3E%3Cpath d='M922.504039 97.143916H777.150913a13.600141 13.600141 0 0 1 0-27.200282h145.327221a13.600141 13.600141 0 1 1 0 27.200282z' fill='%23cccccc' /%3E%3C/svg%3E\");
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
    font-size: 13px;
    font-weight: bold;
    padding-top: 4px;
    margin-right: 5px;
    vertical-align: middle;
}
.foto_jogador {
    display: flex;
    flex-direction: row;
    align-items: center;
    padding: 5px;
}
.jersey-container {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    margin-left: 5px;
    min-width: 40px;
    min-height: 32px;
}
.numeroCamisa {
    width: 60px;
    text-align: center;
}
.inlineButton {
    font-size: 22px;
}
</style>
";

?>

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

$("#toolbar").html('<div id="irApresentacao"><span class="material-symbols-outlined">newspaper</span><span>Apresentação</span></div>');

$(document).on("click", "#irApresentacao", function(){
    if(isDataDirty && !confirm("Você tem alterações não salvas. Deseja sair desta página?")) return;
    window.location = "/times/team_presentation.php?team=" + <?php echo $idTime ?>;
});

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

function reloadPageContent() {
    // Show some loading indicator if desired
    $("#errorbox").html('<div class="alert alert-info">Atualizando...</div>');
    
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

echo "<div id='quadro-container' class='".$idTime."' style='margin-left: 5px; margin-right:5px;'>";
echo '<img id="bandeiraGrande" class="margin-left" src="/images/escudos/'.$escudo_time.'" height="100px">' ;
echo '<img class="uniformeGrande" src="/images/uniformes/'.$uniforme2_time.'" height="80px">' ;
echo '<img class="uniformeGrande" src="/images/uniformes/'.$uniforme1_time.'" height="80px">' ;
echo "<figure id='estadio'><img class='imagemEstadio' src='/images/estadios/{$foto_estadio}'><figcaption>{$estadio_time}<figcaption></figure>";
echo "<h2>" . $nome_time ." </h2>";
if(!$is_selecao){
    echo "<h3><a href='paisstatus.php?country=".$pais_id."'>" . $pais_time ."</a><a href='leaguestatus.php?league=".$liga_id."'>" . $liga_time ." </a></h3> ";
} else {
    $stmtInfo = $pais->readInfo($pais_id);
    $resultInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);
    $federacaoTime = $resultInfo['federacao'];
  //  echo gettype(tecnico);
    $stmtNome = $federacao2->selFederacao($federacaoTime);
    $nomeFederacao = $stmtNome->fetchColumn();
    echo "<h3><span>" . $nomeFederacao ."</span></h3> ";
}
echo "<hr>";

//query jogos time
$time_stmt = $jogador->selecionarElencoTime($id,$from_record_num,$records_per_page);

    // the page where this paging is used
    //$page_url = "teamstatus.php?team=" . $id . "&";

    // count all products in the database to calculate total pages
    $total_rows = $jogador->countAllSingleTeam($id);

    $perc_estrangeiros = $total_rows > 0 ? number_format(($estrangeiros / $total_rows)*100,2)."%" : "0%";

    echo "<div style='clear:both; float:center'></div>";
echo "<div id='info-jogos'>";
echo "<div id='TamElenco' class='infoblock' title='Tamanho do elenco'><span class='material-symbols-outlined'>groups</span><span class='informacao'>{$total_rows}</span></div>";
echo "<div id='Idades' class='infoblock' title='Média de idade'><span class='material-symbols-outlined'>elderly</span><span class='informacao'>{$mediaIdade}</span></div>";
if(!$is_selecao){
    echo "<div id='Estrangeiros' class='infoblock' title='Estrangeiros'><span class='material-symbols-outlined'>globe_location_pin</span><span class='informacao'>{$estrangeiros}</span><span class='informacao micro'>({$perc_estrangeiros})</span></div>";
echo "<div id='Selecionados' class='infoblock' title='Jogadores em seleções nacionais'><span class='material-symbols-outlined'>flag</span> <span class='informacao'>{$jogadores_selecao}</span></div>";
}
echo "<div id='Estádio' class='infoblock' title='Estádio (capacidade)'><span class='material-symbols-outlined'>stadium</span><span class='informacao menor'>{$estadio_capacidade}</span></div>";
if(!$is_selecao){
echo "<div id='Recorde' class='infoblock bevel' title='Balanço de caixa (em F$)'><span class='material-symbols-outlined'>account_balance</span><span class='informacao mini'>{$recorde_transferencia}</span></div>";
}
echo "<div id='Valor' class='infoblock' title='Valor de mercado (em F$)'><span class='material-symbols-outlined'>attach_money</span><span class='informacao menor'>{$valor_total_clube}</span></div>";
echo "<div id='MediaNivel' class='infoblock' title='Média de Nível (titulares/total)'><span class='material-symbols-outlined'>star_half</span><span class='informacao mini'> {$nivel_medio_onze}   <span class='informacao mini'> &nbsp {$nivel_medio} </span></span></div>";
echo "</div>";
echo "<br>";

echo "<div style='clear:both; float:center'></div>";
echo "<hr>";
echo "<div id='errorbox'></div>";
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
echo "<p align='center'>Jogadores</p>";

    echo "<div style='clear:both; float:center'></div>";
echo "<hr>";

// display the products if there are any

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

    $rowTec['Nascimento'] = date("d-m-Y", strtotime($rowTec['Nascimento']));

    echo "<tr id='tec".$rowTec['ID']."' data-sexo='".$rowTec['Sexo']."'>";
    echo "<td class='nopadding'><div class='foto_jogador'><img src='/images/tecnicos/".$rowTec['foto']."' height='55px'></div></td>";
    echo "<td class='nopadding nomeJogador'><span class='nomeEditavel'>{$rowTec['Nome']}</span><br><span class='posicao'>Técnico</span></td>";
    echo "<td>T</td>";
    if($rowTec['idPais'] != 0){
        echo "<td class='nopadding'><img src='/images/bandeiras/{$rowTec['bandeiraPais']}' class='bandeira nomePais' id='ban".$rowTec['idPais']."'>  <span class='nomePais' id='pai".$rowTec['idPais']."'>{$rowTec['siglaPais']}</span>";
    } else {
        echo "<td>";
    }
    echo " <select class='comboPais editavel ' id='{$rowTec['idPais']}' hidden>'  ";
        //echo "<option>Selecione país...</option>";
        for($i = 0; $i < count($listaPaises);$i++){
            echo "<option value='{$listaPaises[$i][0]}'>{$listaPaises[$i][1]}</option>";
        }
        echo "</select>";
    echo "</td>";
    echo "<td class='nopadding'><span class='nascimentoEIdade'>{$rowTec['Nascimento']} (".$rowTec['idade'].")</span><input type='date' class='editavel nascimento' hidden/></td>";
    echo "<td class='nopadding'><span class='nivel'>{$rowTec['Nivel']}</span></td>";
    echo "<td class='nopadding'><span class='desdeFixo'>{$transferenciaTecnico["Data"]}</span><input type='date' class='editavel desde' hidden></td>";
    echo "<td class='nopadding ultimoClube' data-ultimo-clube='{$transferenciaTecnico["ID"]}'>{$transferenciaTecnico["Clube"]}</td>";
    echo "<td class='nopadding'>{$encerramentoTecnico}</td>";
    echo "<td>-</td><td>-</td>";
    $tecOptions = "<td class='wide' id='dono{$rowTec['donoTecnico']}'>";
    if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
        if(!$is_selecao){
            if(!$_SESSION['emTestes']){
                $tecOptions .= "<a id='proTec".$rowTec['ID']."' title='Fazer Proposta' class='clickable propostaTecnico'><span class='material-symbols-outlined inlineButton'>payment_arrow_down</span></a>";
            }
            if($donoLogado){
              $tecOptions .= "<a id='dem".$rowTec['ID']."' title='Editar técnico' class='clickable editarTecnico'><span class='material-symbols-outlined inlineButton azul'>person_edit</span></a>";
                $tecOptions .= "<a id='demTec".$rowTec['ID']."' title='Demitir técnico' class='clickable demitirTecnico'><span class='material-symbols-outlined inlineButton vermelho'>contract_delete</span></a>";
                $tecOptions .= "<a hidden id='sal".$rowTec['ID']."' title='Salvar' class='clickable salvarTecnico'><span class='material-symbols-outlined inlineButton positive'>save</span></a>";
                $tecOptions .= "<a hidden id='can".$rowTec['ID']."' title='Cancelar' class='clickable cancelarTecnico'><span class='material-symbols-outlined inlineButton vermelho'>cancel</span></a>";

            }
        } else {
            $tecOptions .= "<a id='desTec".$rowTec['ID']."' title='Desconvocar técnico' class='clickable desconvocarTecnico'><span class='material-symbols-outlined inlineButton vermelho'>travel</span></a>";
        }
    }


        $tecOptions .= "</td>";
        if($rowTec['ID'] != 0 && $rowTec['ID'] != null){
            echo $tecOptions;
        } else {
            echo "<td></td>";
        }

    echo "</tr>";
}

$agora = date('Y-m-d');

 $lista_titulares = array();
 $lista_reservas = array();
 $lista_suplentes = array();

        while ($row = $time_stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);


            $Nascimento = date("d-m-Y", strtotime($Nascimento));
            $valor = ($valor/1000);
            $valor = "F$ ".number_format($valor,0,".","") . " k" ;
            if($encerramento != "0000-00-00"){
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

                if($titular == 'titular'){
                    $lista_titulares[] = ['nome' => $nomeJogador, 'nivel' => $Nivel, 'mod' => $ModificadorNivel, 'posicaoBase' => $posicaoBase, 'stringPosicoes' => $stringPosicoes, 'idJogador' => $idJogador, 'mentalidade' => $mentalidade, 'capitao' => $capitao, 'cobrancaPenalti' => $cobrancaPenalti, 'cobradorFalta' => $cobradorFalta];
                } else if($titular == 'reserva'){
                    $lista_reservas[] = ['nome' => $nomeJogador, 'nivel' => $Nivel, 'mod' => $ModificadorNivel, 'posicaoBase' => $posicaoBase, 'stringPosicoes' => $stringPosicoes, 'idJogador' => $idJogador];
                } else {
                    $lista_suplentes[] = ['nome' => $nomeJogador, 'nivel' => $Nivel, 'mod' => $ModificadorNivel, 'posicaoBase' => $posicaoBase, 'stringPosicoes' => $stringPosicoes, 'idJogador' => $idJogador];
                }


            echo "<tr data-id-dono-vinculado='".$clubeVinculado."' data-sexo='".$sexoJogador."' id='".$idJogador."' class='".$titular."'>";
            echo "<td class='nopadding'><div class='foto_jogador'><div class='imageUpload'><img class='playerThumb' src='/images/jogadores/".$foto."' /> <input type='file' hidden id='foto".$idJogador."' class='hiddenInput custom-file-upload' name='foto' accept='.jpg,.png,.jpeg,.webp'/></div>
                <div class='jersey-container'>
                    <div class='jersey-icon' title='Número da camisa'>{$numeroCamisa}</div>
                    <input type='number' class='editavel numeroCamisa' value='{$numeroCamisa}' min='1' max='99' style='display:none;'>
                </div>
                </div></td>";
                echo "<td class='nopadding nomeJogador'><a href='/ligas/playerstatus.php?player={$idJogador}' class='nomeEditavel'>{$nomeJogador}</a><br><span class='posicao'>{$posicaoBase}</span></td>";
                echo "<td class='nopadding'><span class='posicoesAtuais'>{$stringPosicoes}</span>";
                echo " <select multiple class='comboPosicoes editavel ' hidden>'  ";
                //echo "<option>Selecione país...</option>";
                for($i = 0; $i < count($listaPosicoes);$i++){
                    echo "<option value='{$listaPosicoes[$i][0]}'>{$listaPosicoes[$i][1]}</option>";
                }
                echo "</select>";
                echo "</td>";
                if($idPais != 0){
                    echo "<td class='nopadding'><img src='/images/bandeiras/{$bandeiraPais}' class='bandeira nomePais' id='ban".$idPais."'>  <span class='nomePais' id='pai".$idPais."'>{$siglaPais}</span>";
                } else {
                    echo "<td>";
                }
                echo " <select class='comboPais editavel ' id='{$idPais}' hidden>'  ";
                    //echo "<option>Selecione país...</option>";
                    for($i = 0; $i < count($listaPaises);$i++){
                        echo "<option value='{$listaPaises[$i][0]}'> {$listaPaises[$i][1]}</option>";
                    }
                    echo "</select>";
                echo "</td>";
                echo "<td class='nopadding'><span class='nascimentoEIdade'>{$Nascimento} (".$Idade.")</span><input type='date' class='editavel nascimento' hidden></td>";
                echo "<td class='nopadding'><span class='nivelEMod'>{$Nivel} (".$ModificadorNivel.")</span><span class='editavel nivel' hidden></td>";
                echo "<td class='nopadding'><span class='desdeFixo'>{$dadosTransferencia["Data"]}</span><input type='date' class='editavel desde' hidden></td>";
                echo "<td class='nopadding ultimoClube' data-ultimo-clube='{$dadosTransferencia["ID"]}'>{$dadosTransferencia["Clube"]}</td>";
                echo "<td class='nopadding'><span class='encerramentoFixo'>{$encerramento}</span><input type='date' class='editavel encerramento' hidden></td>";
                echo "<td class='nopadding'><span class='valorEditavel valor'>{$valor}</span></td>";
                echo "<td class='nopadding'>{$disponibilidade}</td>";
                $optionsString = "<td class='wide' id='dono{$donoJogador}'>";
                if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
                  $optionsString .= "<a id='dow".$id."' title='Baixar arquivo .jog' class='clickable exportar'><span class='material-symbols-outlined inlineButton azul'>download</span></a>";

                    if(!$is_selecao){
						if(!$_SESSION['emTestes'] || $donoLogado){
							$optionsString .= "<a id='pro".$idJogador."' title='Fazer Proposta' class='clickable proposta'><span class='material-symbols-outlined inlineButton'>payment_arrow_down</span></a>";
						}
                        if($donoLogado){
                            $optionsString .= "<a id='edit".$idJogador."' title='Editar jogador' class='clickable editar'><span class='material-symbols-outlined inlineButton azul'>person_edit</span></a>";
                            $optionsString .= "<a id='disp".$idJogador."' title='Disponibilizar jogador' class='clickable disponibilizar'><span class='material-symbols-outlined inlineButton azul'>sell</span></a>";
                            $optionsString .= "<a id='demi".$idJogador."' title='Demitir jogador' class='clickable demitir'><span class='material-symbols-outlined inlineButton vermelho'>contract_delete</span></a>";
                            $optionsString .= "<a id='apos".$idJogador."' title='Aposentar jogador' class='clickable aposentar'><span class='material-symbols-outlined inlineButton vermelho'>assist_walker</span></a>";
							$optionsString .= "<a id='expa".$idJogador."' title='Expatriar jogador' class='clickable expatriar'><span class='material-symbols-outlined inlineButton vermelho'>flight_takeoff</span></a>"; 
                            $optionsString .= "<a hidden id='sal".$id."' title='Salvar' class='clickable salvar'><span class='material-symbols-outlined inlineButton positive'>save</span></a>";
                            $optionsString .= "<a hidden id='can".$id."' title='Cancelar' class='clickable cancelar'><span class='material-symbols-outlined inlineButton vermelho'>cancel</span></a>";

                        }
                    } else {
                        $optionsString .= "<a id='desc".$idJogador."' title='Desconvocar jogador' class='clickable desconvocar'><span class='material-symbols-outlined inlineButton vermelho'>travel</span></a>";
                    }



                    $optionsString .= "</td>";
                    echo $optionsString;
                }
            echo "</tr>";

        }

        echo "</tbody>";




echo "</table>";



echo "</div>";

if($donoLogado){
// permitir arrastar elenco
$drag_players = "draggable";

	
//pagina do elenco
echo "<div class='tabcontent' id='Elenco' hidden>";

echo "<div class='tableHolder'><table id='tabelaTitulares'>";
echo "<caption>Titulares</caption>";
echo "<thead>";
echo "<tr>";
echo "<th>Jogador</th>";
echo "<th>Nivel (mod)</th>";
echo "<th>Posições</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";
foreach($lista_titulares as $jogador_tabela){
    echo "<tr class='clickablerow_tit' id='elenco".$jogador_tabela['idJogador']."'>";
    echo "<td class='nopadding nomeJogador'>{$jogador_tabela['nome']}<br><span class='posicao'>{$jogador_tabela['posicaoBase']}</span></td>";
    echo "<td class='nopadding'>{$jogador_tabela['nivel']} (".$jogador_tabela['mod'].")</td>";
    echo "<td class='nopadding'>{$jogador_tabela['stringPosicoes']}</td>";
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
echo "</tr>";
echo "</thead>";
echo "<tbody>";
foreach($lista_reservas as $jogador_tabela){
    echo "<tr class='clickablerow_res' id='elenco".$jogador_tabela['idJogador']."'>";
    echo "<td class='nopadding nomeJogador'>{$jogador_tabela['nome']}<br><span class='posicao'>{$jogador_tabela['posicaoBase']}</span></td>";
    echo "<td class='nopadding'>{$jogador_tabela['nivel']} (".$jogador_tabela['mod'].")</td>";
    echo "<td class='nopadding'>{$jogador_tabela['stringPosicoes']}</td>";
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
echo "</tr>";
echo "</thead>";
echo "<tbody>";
foreach($lista_suplentes as $jogador_tabela){
    echo "<tr class='clickablerow_sup' id='elenco".$jogador_tabela['idJogador']."'>";
    echo "<td class='nopadding nomeJogador'>{$jogador_tabela['nome']}<br><span class='posicao'>{$jogador_tabela['posicaoBase']}</span></td>";
    echo "<td class='nopadding'>{$jogador_tabela['nivel']} (".$jogador_tabela['mod'].")</td>";
    echo "<td class='nopadding'>{$jogador_tabela['stringPosicoes']}</td>";
    echo "</tr>";
}
echo "</tbody>";
echo "</table></div>";




echo "</div>";

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
            $goleiro = $jogador["nome"];
            break;
        case "Lateral-direito":
            $lateral_direito = ["<p>".$nome_final."</p>",$jogador["idJogador"]];
            break;
        case "Lateral-esquerdo":
            $lateral_esquerdo = ["<p>".$nome_final."</p>",$jogador["idJogador"]];
            break;
        case "Ala direito":
            $ala_direito = ["<p>".$nome_final."</p>",$jogador["idJogador"]];
            break;
        case "Ala esquerdo":
            $ala_esquerdo = ["<p>".$nome_final."</p>",$jogador["idJogador"]];
            break;
        case "Meia direito":
            $meia_direito = ["<p>".$nome_final."</p>",$jogador["idJogador"]];
            break;
        case "Meia esquerdo":
            $meia_esquerdo = ["<p>".$nome_final."</p>",$jogador["idJogador"]];
            break;
        case "Ponta direita":
            $ponta_direita = ["<p>".$nome_final."</p>",$jogador["idJogador"]];
            break;
        case "Ponta esquerda":
            $ponta_esquerda = ["<p>".$nome_final."</p>",$jogador["idJogador"]];
            break;
        case "Zagueiro":
            $zagueiro[] = ["<p>".$nome_final."</p>",$jogador["idJogador"]];
            break;
        case "Volante":
            $volante[] = ["<p>".$nome_final."</p>",$jogador["idJogador"]];
            break;
        case "Meia central":
            $meia[] = ["<p>".$nome_final."</p>",$jogador["idJogador"]];
            break;
        case "Meia-atacante":
            $armador[] = ["<p>".$nome_final."</p>",$jogador["idJogador"]];
            break;
        case "Atacante de movimentação":
            $atacante[] = ["<p>".$nome_final."</p>",$jogador["idJogador"],"Am"];
            break;
        case "Atacante de área":
            $atacante[] = ["<p>".$nome_final."</p>",$jogador["idJogador"],"Aa"];
            break;
        default:
        break;
    }
}

//controle de variaveis nao setadas
if(!isset($ponta_esquerda)){
  $ponta_esquerda[0] = '';
  $ponta_esquerda[1] = "PE";
}

if(!isset($ponta_direita)){
  $ponta_direita[0] = '';
  $ponta_direita[1] = "PD";
}

if(!isset($ala_direito)){
  $ala_direito[0] = '';
  $ala_direito[1] = "AD";
}

if(!isset($ala_esquerdo)){
  $ala_esquerdo[0] = '';
  $ala_esquerdo[1] = "AE";
}

if(!isset($lateral_direito)){
  $lateral_direito[0] = '';
  $lateral_direito[1] = "LD";
}

if(!isset($lateral_esquerdo)){
  $lateral_esquerdo[0] = '';
  $lateral_esquerdo[1] = "LE";
}

if(!isset($meia_direito)){
  $meia_direito[0] = '';
  $meia_direito[1] = "MD";
}

if(!isset($meia_esquerdo)){
  $meia_esquerdo[0] = '';
  $meia_esquerdo[1] = "ME";
}

if(!isset($goleiro)){
  $goleiro = '';
}

//pagina da escalacao
echo "<div class='tabcontent' id='Posicionamento' hidden>";
echo '<div id="sortable" class="ui-state">';
//echo '<div id= "background-sortable" ></div>';

// ponta-esquerda
echo '<div id="'.$drag_players.$ponta_esquerda[1].'" class="pos-ataque">'.($ponta_esquerda[0]!=''?'<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">':'').'<div class="PE"></div>'.($ponta_esquerda[0]!=''?$ponta_esquerda[0]:'&nbsp').'</div>';

// atacantes
echo '<div id="'.$drag_players.(count($atacante)==2 ? $atacante[0][1] : (count($atacante)==3 ? $atacante[0][1] : "AA")).'" class="pos-ataque">'.((count($atacante) == 2 OR count($atacante) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="'.(count($atacante)==2 ? $atacante[0][2] : (count($atacante)==3 ? $atacante[0][2] : "A0")).'" ></div>'.(count($atacante)==2 ? $atacante[0][0] : (count($atacante)==3 ? $atacante[0][0] : "&nbsp")).'</div>';

echo '<div id="'.$drag_players.(count($atacante)==1 ? $atacante[0][1] : (count($atacante)==3 ? $atacante[1][1] : "AB")).'" class="pos-ataque">'.((count($atacante) == 1 OR count($atacante) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="'.(count($atacante)==1 ? $atacante[0][2] : (count($atacante)==3 ? $atacante[1][2] : "A0")).'" ></div>'.(count($atacante)==1 ? $atacante[0][0] : (count($atacante)==3 ? $atacante[1][0] : "&nbsp")).'</div>';

echo '<div id="'.$drag_players.(count($atacante)==2 ? $atacante[1][1] : (count($atacante)==3 ? $atacante[2][1] : "AC")).'" class="pos-ataque">'.((count($atacante) == 2 OR count($atacante) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="'.(count($atacante)==2 ? $atacante[1][2] : (count($atacante)==3 ? $atacante[2][2] : "A0")).'" ></div>'.(count($atacante)==2 ? $atacante[1][0] : (count($atacante)==3 ? $atacante[2][0] : "&nbsp")).'</div>';

// ponta-direita
echo '<div id="'.$drag_players.$ponta_direita[1].'" class="pos-ataque">'.($ponta_direita[0]!=''?'<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">':'').'<div class="PD"></div>'.($ponta_direita[0]!=''?$ponta_direita[0]:'&nbsp').'</div>';

// armadores
echo '<div id="nondraggable6">&nbsp</div>';

echo '<div id="'.$drag_players.(count($armador)==2 ? $armador[0][1] : (count($armador)==3 ? $armador[0][1] : "AD")).'" class="pos-meio-ataque">'.((count($armador) == 2 OR count($armador) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="MA"></div>'.(count($armador)==2 ? $armador[0][0] : (count($armador)==3 ? $armador[0][0] : "&nbsp")).'</div>';

echo '<div id="'.$drag_players.(count($armador)==1 ? $armador[0][1] : (count($armador)==3 ? $armador[1][1] : "AE")).'" class="pos-meio-ataque">'.((count($armador) == 1 OR count($armador) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="MA"></div>'.(count($armador)==1 ? $armador[0][0] : (count($armador)==3 ? $armador[1][0] : "&nbsp")).'</div>';

echo '<div id="'.$drag_players.(count($armador)==2 ? $armador[1][1] : (count($armador)==3 ? $armador[2][1] : "AF")).'" class="pos-meio-ataque">'.((count($armador) == 2 OR count($armador) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="MA"></div>'.(count($armador)==2 ? $armador[1][0] : (count($armador)==3 ? $armador[2][0] : "&nbsp")).'</div>';

echo '<div id="nondraggable10">&nbsp</div>';

// meia-esquerda
echo '<div id="'.$drag_players.$meia_esquerdo[1].'" class="pos-meio">'.($meia_esquerdo[0]!=''?'<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">':'').'<div class="ME"></div>'.($meia_esquerdo[0]!=''?$meia_esquerdo[0]:'&nbsp').'</div>';

// meias-centrais
echo '<div id="'.$drag_players.(count($meia)==2 ? $meia[0][1] : (count($meia)==3 ? $meia[0][1] : "AG")).'" class="pos-meio">'.((count($meia) == 2 OR count($meia) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="MC"></div>'.(count($meia)==2 ? $meia[0][0] : (count($meia)==3 ? $meia[0][0] : "&nbsp")).'</div>';

echo '<div id="'.$drag_players.(count($meia)==1 ? $meia[0][1] : (count($meia)==3 ? $meia[1][1] : "AH")).'" class="pos-meio">'.((count($meia) == 1 OR count($meia) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="MC"></div>'.(count($meia)==1 ? $meia[0][0] : (count($meia)==3 ? $meia[1][0] : "&nbsp")).'</div>';

echo '<div id="'.$drag_players.(count($meia)==2 ? $meia[1][1] : (count($meia)==3 ? $meia[2][1] : "AI")).'" class="pos-meio">'.((count($meia) == 2 OR count($meia) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="MC"></div>'.(count($meia)==2 ? $meia[1][0] : (count($meia)==3 ? $meia[2][0] : "&nbsp")).'</div>';

// meia-direita
echo '<div id="'.$drag_players.$meia_direito[1].'" class="pos-meio">'.($meia_direito[0]!=''?'<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">':'').'<div class="MD"></div>'.($meia_direito[0]!=''?$meia_direito[0]:'&nbsp').'</div>';

// ala-esquerda
echo '<div id="'.$drag_players.$ala_esquerdo[1].'" class="pos-zaga-meio">'.($ala_esquerdo[0]!=''?'<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">':'').'<div class="AE"></div>'.($ala_esquerdo[0]!=''?$ala_esquerdo[0]:'&nbsp').'</div>';

// volantes
echo '<div id="'.$drag_players.(count($volante)==2 ? $volante[0][1] : (count($volante)==3 ? $volante[0][1] : "AJ")).'" class="pos-zaga-meio">'.((count($volante) == 2 OR count($volante) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="V"></div>'.(count($volante)==2 ? $volante[0][0] : (count($volante)==3 ? $volante[0][0] : "&nbsp")).'</div>';

echo '<div id="'.$drag_players.(count($volante)==1 ? $volante[0][1] : (count($volante)==3 ? $volante[1][1] : "AK")).'" class="pos-zaga-meio">'.((count($volante) == 1 OR count($volante) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="V"></div>'.(count($volante)==1 ? $volante[0][0] : (count($volante)==3 ? $volante[1][0] : "&nbsp")).'</div>';

echo '<div id="'.$drag_players.(count($volante)==2 ? $volante[1][1] : (count($volante)==3 ? $volante[2][1] : "AL")).'" class="pos-zaga-meio">'.((count($volante) == 2 OR count($volante) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="V"></div>'.(count($volante)==2 ? $volante[1][0] : (count($volante)==3 ? $volante[2][0] : "&nbsp")).'</div>';

// ala-direita
echo '<div id="'.$drag_players.$ala_direito[1].'" class="pos-zaga-meio">'.($ala_direito[0]!=''?'<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">':'').'<div class="AD"></div>'.($ala_direito[0]!=''?$ala_direito[0]:'&nbsp').'</div>';

// lateral-esquerda
echo '<div id="'.$drag_players.$lateral_esquerdo[1].'" class="pos-zaga">'.($lateral_esquerdo[0]!=''?'<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">':'').'<div class="LE"></div>'.($lateral_esquerdo[0]!=''?$lateral_esquerdo[0]:'&nbsp').'</div>';

// zagueiros
echo '<div id="'.$drag_players.(count($zagueiro)==2 ? $zagueiro[0][1] : (count($zagueiro)==3 ? $zagueiro[0][1] : "AM")).'" class="pos-zaga">'.((count($zagueiro) == 2 OR count($zagueiro) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="Z"></div>'.(count($zagueiro)==2 ? $zagueiro[0][0] : (count($zagueiro)==3 ? $zagueiro[0][0] : "&nbsp")).'</div>';

echo '<div id="'.$drag_players.(count($zagueiro)==1 ? $zagueiro[0][1] : (count($zagueiro)==3 ? $zagueiro[1][1] : "AN")).'" class="pos-zaga">'.((count($zagueiro) == 1 OR count($zagueiro) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="Z"></div>'.(count($zagueiro)==1 ? $zagueiro[0][0] : (count($zagueiro)==3 ? $zagueiro[1][0] : "&nbsp")).'</div>';

echo '<div id="'.$drag_players.(count($zagueiro)==2 ? $zagueiro[1][1] : (count($zagueiro)==3 ? $zagueiro[2][1] : "AO")).'" class="pos-zaga">'.((count($zagueiro) == 2 OR count($zagueiro) == 3) ? '<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">' : "").'<div class="Z"></div>'.(count($zagueiro)==2 ? $zagueiro[1][0] : (count($zagueiro)==3 ? $zagueiro[2][0] : "&nbsp")).'</div>';

// lateral-direita
echo '<div id="'.$drag_players.$lateral_direito[1].'" class="pos-zaga">'.($lateral_direito[0]!=''?'<img src="/images/uniformes/'.$uniforme1_time. '" height="60px">':'').'<div class="LD"></div>'.($lateral_direito[0]!=''?$lateral_direito[0]:'&nbsp').'</div>';

// goleiro
echo '<div id="nondraggable26">&nbsp</div>';
echo '<div id="nondraggable27">&nbsp</div>';
echo '<div id="nondraggable28" class="goleiro"><img src="/images/uniformes/'.$uniforme2_time. '" height="60px"><p>'.$goleiro.'</p></div>';
echo '<div id="nondraggable29">&nbsp</div>';
echo '<div id="nondraggable30">&nbsp</div>';
echo '</div>';

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
echo '</div>';
echo "</div>";
}


echo "</div>";

echo "</div>";

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
      // ler times do banco de dados
                $userId = (isset($_SESSION['user_id'])?$_SESSION['user_id']:0);
                $stmt = $time->read($userId);

                echo "<option value=''>Selecione time...</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}' data-sexo='{$Sexo}'>{$nome}</option>";
                }

                ?>

      </select>

      <input type="hidden" value="" name="idJogador" id="idJogadorTransf" required>
      <input type="hidden" value="<?php echo $idTime ?>" name="clubeOrigem" id="clubeOrigemTransf" required>
      <input type="hidden" value="<?php echo (isset($_SESSION['user_id'])?$_SESSION['user_id']:0); ?>" name="sorvete" required>

      <button type="submit" name="newsubmit" class="submitbtn">Propor transferência</button>
    </div>

    <div class="container" style="background-color:#f1f1f1">
      <button type="button" onclick="document.getElementById('modalProposta').style.display='none'" class="cancelbtn">Cancelar</button>
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

      <input type="hidden" value="" name="idTecnicoTransf" id="idTecnicoTransf" required>
      <input type="hidden" value="<?php echo $idTime ?>" name="clubeOrigemTecnico" id="clubeOrigemTecnico" required>
      <input type="hidden" value="<?php echo (isset($_SESSION['user_id'])?$_SESSION['user_id']:0); ?>" name="sorveteTec" required>

      <button type="submit" name="newsubmit" class="submitbtn">Propor transferência</button>
    </div>

    <div class="container" style="background-color:#f1f1f1">
      <button type="button" onclick="document.getElementById('modalPropostaTecnico').style.display='none'" class="cancelbtn">Cancelar</button>
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
    console.log(nome);
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
        'sorveteTec' : $('input[name=sorveteTec]').val()
    };

    console.log(formData);

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/ligas/fazer_proposta_tecnico.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
console.log(data);
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
    console.log("Erro");
    console.log(jqXHR);
    console.log(textStatus);
    console.log(errorThrown);
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
console.log(data);
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
    console.log("Erro");
    console.log(jqXHR);
    console.log(textStatus);
    console.log(errorThrown);
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

    var id = $(this).html();

    window.location.hash = '#'+id;

    $(".tabcontent").each(function(index){
        $(this).hide();

    });

    $('#'+id).show();

    $(".tablinks").each(function(index){
        $(this).removeClass("active");
    });
    $(this).addClass("active");



    event.preventDefault();

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
console.log(data);
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
console.log(data);
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
console.log(data);
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
console.log(data);
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
console.log(data);
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
console.log(data);
// window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    reloadPageContent();

}

// here we will handle errors and validation messages
});

});


</script>


<script>

function initDragDrop() {

    $("[id^=draggable]").contextmenu(function(event) {
        event.preventDefault();

       var idJogador = $(this).attr('id').replace( /\D/g, '');
       var idTime = $('#quadro-container').prop('class');
       var posicao = $(this).children('div').attr('class');
       var posJogador;
       if(posicao == 'Aa'){
            $(this).children('div').removeClass('Aa').addClass('Am');
            posJogador = 'Am';
       } else if (posicao == 'Am'){
            $(this).children('div').removeClass('Am').addClass('Aa');
            posJogador = 'Aa';
       }

       primeiraLetra = posicao.charAt(0);

       console.log(idJogador.length);
       console.log(primeiraLetra.localeCompare('A'));

       if((idJogador.length > 0) && (primeiraLetra.localeCompare('A') === 0)){

                  //efetuar a troca por AJAX
                  var formData = {
            'idJogador1' : idJogador,
            'tipoAlteracao' : 7,
            'posicao1' : posJogador,
            'clube' : idTime
        };

        console.log("id1:"+idJogador + "pos" + posJogador);

         $.ajax({
                type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
                url         : 'alteracao_elenco.php', // the url where we want to POST
                data        : formData, // our data object
                dataType    : 'json', // what type of data do we expect back from the server
                encode          : true
            })

                        .done(function(data) {


    // log data to the console so we can see
    console.log(data);
    //window.scrollTo(0, 0);

    if (! data.success) {

         $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


    } else {


        reloadPageContent();

    }

    // here we will handle errors and validation messages
    });
       }


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

    console.log("id1:"+idJogador1 + "pos" + posJogador1);
    console.log("id2:"+idJogador2 + "pos" + posJogador2);

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : 'alteracao_elenco.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
            encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
console.log(data);
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

    console.log($('#formCapitaoCobrancas').serialize());

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

    var idJogador = $(this).closest('tr').prop('id');
	
	let formData = new FormData();
		
	formData.append('idJogador',idJogador);
	formData.append('alteracao',1);

    ajaxCallJogador(formData);
});

$(document).on("click", '.aposentar', function(event){

var idJogador = $(this).closest('tr').prop('id');
var idTime = $('#quadro-container').prop('class');

let formData = new FormData();
	
formData.append('idJogador',idJogador);
formData.append('alteracao',4);
formData.append('idTime',idTime);

console.log(idJogador);

if(window.confirm("Deseja mesmo aposentar este jogador?")){
    ajaxCallJogador(formData);
}
});

$(document).on("click", '.expatriar', function(event){

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

var idTecnico = $(this).closest('tr').prop('id').replace(/\D/g, "");;
var idTime = $('#quadro-container').prop('class');

var formData = {
    'idTecnico' : idTecnico,
    'idTime' : idTime,
    'alteracao' : 2
};

console.log(formData);

if(window.confirm("Deseja mesmo desconvocar?")){
ajaxCallTecnico(formData);
}

});

$(document).on("click", '.desconvocar', function(event){

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

tbl_row.find(".salvar").show();
tbl_row.find(".cancelar").show();
tbl_row.find(".editar").hide();
tbl_row.find(".disponibilizar").hide();
tbl_row.find(".aposentar").hide();
tbl_row.find(".demitir").hide();
tbl_row.find(".proposta").hide();
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

console.log(isDono);

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
        tbl_row.find(".salvar").hide();
        tbl_row.find(".cancelar").hide();
        tbl_row.find(".editar").show();
        tbl_row.find(".disponibilizar").show();
        tbl_row.find(".demitir").show();
        tbl_row.find(".aposentar").show();
        tbl_row.find(".proposta").show();
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

        console.log(0);

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
        console.log("error");
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
            console.log(data);
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
                console.log("Erro");
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
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
        console.log(data);
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
            console.log("Erro");
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
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


tbl_row.find(".salvarTecnico").show();
tbl_row.find(".cancelarTecnico").show();
tbl_row.find(".editarTecnico").hide();
tbl_row.find(".demitirTecnico").hide();
tbl_row.find(".propostaTecnico").hide();

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
        tbl_row.find('.salvarTecnico').hide();
        tbl_row.find('.cancelarTecnico').hide();
        tbl_row.find('.editarTecnico').show();
        tbl_row.find('.propostaTecnico').show();
        tbl_row.find('.demitirTecnico').show();
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

<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
