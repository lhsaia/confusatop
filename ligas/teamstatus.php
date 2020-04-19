<?php

//ini_set( 'display_errors', true );
//error_reporting( E_ALL );
session_start();

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

?>

<script>

// on load of the page: switch to the currently selected tab
var hash = window.location.hash;

window.onbeforeunload = function(e) {
    e.preventDefault();
    window.location.href += hash;
    location.reload();


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

</script>


<?php

echo "<div id='quadro-container' class='".$idTime."'>";
echo "<img id='bandeiraGrande' class='margin-left' src='/images/escudos/".$escudo_time."' height='100px'>" ;
echo "<img class='uniformeGrande' src='/images/uniformes/".$uniforme2_time."' height='80px'>" ;
echo "<img class='uniformeGrande' src='/images/uniformes/".$uniforme1_time. "' height='80px'>" ;
echo "<figure id='estadio'><img class='imagemEstadio' src='/images/stadium.png'><figcaption>{$estadio_time}<figcaption></figure>";
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

    $perc_estrangeiros = number_format(($estrangeiros / $total_rows)*100,2)."%";

    echo "<div style='clear:both; float:center'></div>";
echo "<div id='info-jogos'>";
echo "<div id='TamElenco' class='infoblock' title='Tamanho do elenco'><i class='fas fa-users'></i><span class='informacao'>{$total_rows}</span></div>";
echo "<div id='Idades' class='infoblock' title='Média de idade'><i class='fas fa-male'></i><span class='informacao'>{$mediaIdade}</span></div>";
if(!$is_selecao){
    echo "<div id='Estrangeiros' class='infoblock' title='Estrangeiros'><i class='fas fa-globe'></i><span class='informacao'>{$estrangeiros}</span><span class='informacao micro'>({$perc_estrangeiros})</span></div>";
echo "<div id='Selecionados' class='infoblock' title='Jogadores em seleções nacionais'><i class='fas fa-clipboard-list'></i> <span class='informacao'>{$jogadores_selecao}</span></div>";
}
echo "<div id='Estádio' class='infoblock' title='Estádio (capacidade)'><i class='fas fa-map-marker-alt'></i><span class='informacao menor'>{$estadio_capacidade}</span></div>";
if(!$is_selecao){
echo "<div id='Recorde' class='infoblock' title='Balanço de caixa (em F$)'><i class='fas fa-hand-holding-usd'></i><span class='informacao mini'>{$recorde_transferencia}</span></div>";
}
echo "<div id='Valor' class='infoblock' title='Valor de mercado (em F$)'><i class='fas fa-dollar-sign'></i><span class='informacao menor'>{$valor_total_clube}</span></div>";
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
}

echo "<div class='tabcontent' id='Jogadores'>";
echo "<p align='center'>Jogadores</p>";

    echo "<div style='clear:both; float:center'></div>";
echo "<hr>";

// display the products if there are any

echo "<table id='tabelaElenco' class='table'>";
echo "<thead>";
echo "<tr>";
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

$transferenciaTecnico = $tecnico->ultimaTransferencia($rowTec['ID'], $idTime);
$encerramentoTecnico = ( $rowTec['encerramento'] == "0" ) ? 'indet.' : $rowTec['encerramento'] ;

echo "<tr id='tec".$rowTec['ID']."' data-sexo='".$rowTec['Sexo']."'>";
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
echo "<td class='nopadding'><span class='nomeNascimento'>{$rowTec['Nascimento']} (".$rowTec['idade'].")</span><input id='selnas".$ID."' class='nascimentoEditavel editavel' type='date' value='{$rowTec['Nascimento']}' hidden/></td>";
echo "<td class='nopadding'><span class='nivel'>{$rowTec['Nivel']}</span></td>";
echo "<td class='nopadding'>{$transferenciaTecnico["Data"]}</td>";
echo "<td class='nopadding'>{$transferenciaTecnico["Clube"]}</td>";
echo "<td class='nopadding'>{$encerramentoTecnico}</td>";
echo "<td>-</td><td>-</td>";
$tecOptions = "<td class='wide' id='dono{$rowTec['donoTecnico']}'>";
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    if(!$is_selecao){
        $tecOptions .= "<a id='proTec".$rowTec['ID']."' title='Fazer Proposta' class='clickable propostaTecnico'><i class='fas fa-money-bill inlineButton'></i></a>";
        if($donoLogado){
          $tecOptions .= "<a id='dem".$rowTec['ID']."' title='Editar técnico' class='clickable editarTecnico'><i class='fas fa-edit inlineButton azul'></i></a>";
            //$tecOptions .= "<a id='dem".$rowTec['ID']."' title='Disponibilizar jogador' class='clickable disponibilizar'><i class='fas fa-list-ul inlineButton azul'></i></a>";
            $tecOptions .= "<a id='demTec".$rowTec['ID']."' title='Demitir técnico' class='clickable demitirTecnico'><i class='fas fa-file-contract inlineButton vermelho'></i></a>";
            //$tecOptions .= "<a id='dem".$rowTec['ID']."' title='Aposentar técnico' class='clickable aposentarTecnico'><i class='fas fa-glasses inlineButton vermelho'></i></a>";
            $tecOptions .= "<a hidden id='sal".$rowTec['ID']."' title='Salvar' class='clickable salvarTecnico'><i class='fas fa-check inlineButton positive'></i></a>";
            $tecOptions .= "<a hidden id='can".$rowTec['ID']."' title='Cancelar' class='clickable cancelarTecnico'><i class='fas fa-times inlineButton vermelho'></i></a>";

        }
    } else {
        $tecOptions .= "<a id='desTec".$rowTec['ID']."' title='Desconvocar técnico' class='clickable desconvocarTecnico'><i class='fas fa-plane inlineButton vermelho'></i></a>";
    }
}


    $tecOptions .= "</td>";
    if($rowTec['ID'] != 0 && $rowTec['ID'] != null){
        echo $tecOptions;
    } else {
        echo "<td></td>";
    }

echo "</tr>";

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
                    $lista_titulares[] = ['nome' => $nomeJogador, 'nivel' => $Nivel, 'mod' => $ModificadorNivel, 'posicaoBase' => $posicaoBase, 'stringPosicoes' => $stringPosicoes, 'idJogador' => $idJogador, 'mentalidade' => $mentalidade, 'capitao' => $capitao, 'cobrancaPenalti' => $cobrancaPenalti];
                } else if($titular == 'reserva'){
                    $lista_reservas[] = ['nome' => $nomeJogador, 'nivel' => $Nivel, 'mod' => $ModificadorNivel, 'posicaoBase' => $posicaoBase, 'stringPosicoes' => $stringPosicoes, 'idJogador' => $idJogador];
                } else {
                    $lista_suplentes[] = ['nome' => $nomeJogador, 'nivel' => $Nivel, 'mod' => $ModificadorNivel, 'posicaoBase' => $posicaoBase, 'stringPosicoes' => $stringPosicoes, 'idJogador' => $idJogador];
                }


            echo "<tr data-sexo='".$sexoJogador."' id='".$idJogador."' class='".$titular."'>";
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
                echo "<td class='nopadding'><span class='nascimentoEIdade'>{$Nascimento} (".$Idade.")</span><input type='date' class='editavel nascimento' hidden></span></td>";
                echo "<td class='nopadding'><span class='nivelEMod'>{$Nivel} (".$ModificadorNivel.")</span><span class='editavel nivel' hidden></span></td>";
                echo "<td class='nopadding'>{$dadosTransferencia["Data"]}</td>";
                echo "<td class='nopadding'>{$dadosTransferencia["Clube"]}</td>";
                echo "<td class='nopadding'>{$encerramento}</td>";
                echo "<td class='nopadding'><span class='valorEditavel valor'>{$valor}</span></td>";
                echo "<td class='nopadding'>{$disponibilidade}</td>";
                $optionsString = "<td class='wide' id='dono{$donoJogador}'>";
                if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
                  $optionsString .= "<a id='dow".$id."' title='Baixar arquivo .jog' class='clickable exportar'><i class='fas fa-download inlineButton azul'></i></a>";

                    if(!$is_selecao){
                        $optionsString .= "<a id='pro".$idJogador."' title='Fazer Proposta' class='clickable proposta'><i class='fas fa-money-bill inlineButton'></i></a>";
                        if($donoLogado){
                            $optionsString .= "<a id='dem".$idJogador."' title='Editar jogador' class='clickable editar'><i class='fas fa-edit inlineButton azul'></i></a>";
                            $optionsString .= "<a id='dem".$idJogador."' title='Disponibilizar jogador' class='clickable disponibilizar'><i class='fas fa-list-ul inlineButton azul'></i></a>";
                            $optionsString .= "<a id='dem".$idJogador."' title='Demitir jogador' class='clickable demitir'><i class='fas fa-file-contract inlineButton vermelho'></i></a>";
                            $optionsString .= "<a id='dem".$idJogador."' title='Aposentar jogador' class='clickable aposentar'><i class='fas fa-glasses inlineButton vermelho'></i></a>";
                            $optionsString .= "<a hidden id='sal".$id."' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
                            $optionsString .= "<a hidden id='can".$id."' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton vermelho'></i></a>";

                        }
                    } else {
                        $optionsString .= "<a id='dem".$idJogador."' title='Desconvocar jogador' class='clickable desconvocar'><i class='fas fa-plane inlineButton vermelho'></i></a>";
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
//pagina do elenco
echo "<div class='tabcontent' id='Elenco' hidden>";

echo "<table id='tabelaTitulares'>";
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
echo "</table>";

echo "<div>";
echo '<a id="trocar_titular_reserva" title="Trocar jogadores selecionados" class="clickable"><i class="alto fas fa-exchange-alt inlineButton azul"></i></a>';
echo '<a id="enviar_para_titular" title="Enviar jogador para titular" class="clickable"><i class="alto fas fa-long-arrow-alt-left inlineButton azul"></i></a>';
echo '<a id="remover_titular" title="Enviar jogador para reserva" class="clickable"><i class="alto fas fa-long-arrow-alt-right inlineButton azul"></i></a>';
echo "</div>";

echo "<table id='tabelaReservas'>";
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
echo "</table>";

echo "<div>";
echo '<a id="trocar_reserva_suplente" title="Trocar jogadores selecionados" class="clickable"><i class="alto fas fa-exchange-alt inlineButton azul"></i></a>';
echo '<a id="enviar_para_suplente" title="Retirar jogador da reserva" class="clickable"><i class="alto fas fa-long-arrow-alt-right inlineButton azul"></i></a>';
echo '<a id="enviar_para_reserva" title="Enviar jogador para reserva" class="clickable"><i class="alto fas fa-long-arrow-alt-left inlineButton azul"></i></a>';
echo "</div>";

echo "<table id='tabelaSuplentes'>";
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
echo "</table>";




echo "</div>";

$zagueiro = array();
$volante = array();
$meia = array();
$armador = array();
$atacante = array();
foreach($lista_titulares as $jogador){
    switch($jogador['posicaoBase']){
        case "Goleiro":
            $goleiro = $jogador["nome"];
            break;
        case "Lateral-direito":
            $lateral_direito = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"]];
            break;
        case "Lateral-esquerdo":
            $lateral_esquerdo = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"]];
            break;
        case "Ala direito":
            $ala_direito = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"]];
            break;
        case "Ala esquerdo":
            $ala_esquerdo = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"]];
            break;
        case "Meia direito":
            $meia_direito = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"]];
            break;
        case "Meia esquerdo":
            $meia_esquerdo = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"]];
            break;
        case "Ponta direita":
            $ponta_direita = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"]];
            break;
        case "Ponta esquerda":
            $ponta_esquerda = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"]];
            break;
        case "Zagueiro":
            $zagueiro[] = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"]];
            break;
        case "Volante":
            $volante[] = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"]];
            break;
        case "Meia central":
            $meia[] = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"]];
            break;
        case "Meia-atacante":
            $armador[] = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"]];
            break;
        case "Atacante de movimentação":
            $atacante[] = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"],"Am"];
            break;
        case "Atacante de área":
            $atacante[] = ["<p>".$jogador["nome"]."</p>",$jogador["idJogador"],"Aa"];
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

//pagina da escalacao
echo "<div class='tabcontent' id='Posicionamento' hidden>";
echo '<div id="sortable" class="ui-state">';
//echo '<div id= "background-sortable" ></div>';
echo '<div id="draggable'.$ponta_esquerda[1].'" class="pos-ataque"><div class="PE"></div>'.($ponta_esquerda[0]!=''?$ponta_esquerda[0]:'&nbsp').'</div>';
echo '<div id="draggable'.(count($atacante)==2 ? $atacante[0][1] : (count($atacante)==3 ? $atacante[0][1] : "AA")).'" class="pos-ataque"><div class="'.(count($atacante)==2 ? $atacante[0][2] : (count($atacante)==3 ? $atacante[0][2] : "A0")).'" ></div>'.(count($atacante)==2 ? $atacante[0][0] : (count($atacante)==3 ? $atacante[0][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.(count($atacante)==1 ? $atacante[0][1] : (count($atacante)==3 ? $atacante[1][1] : "AB")).'" class="pos-ataque"><div class="'.(count($atacante)==1 ? $atacante[0][2] : (count($atacante)==3 ? $atacante[1][2] : "A0")).'" ></div>'.(count($atacante)==1 ? $atacante[0][0] : (count($atacante)==3 ? $atacante[1][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.(count($atacante)==2 ? $atacante[1][1] : (count($atacante)==3 ? $atacante[2][1] : "AC")).'" class="pos-ataque"><div class="'.(count($atacante)==2 ? $atacante[1][2] : (count($atacante)==3 ? $atacante[2][2] : "A0")).'" ></div>'.(count($atacante)==2 ? $atacante[1][0] : (count($atacante)==3 ? $atacante[2][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.$ponta_direita[1].'" class="pos-ataque"><div class="PD"></div>'.($ponta_direita[0]!=''?$ponta_direita[0]:'&nbsp').'</div>';
echo '<div id="nondraggable6">&nbsp</div>';
echo '<div id="draggable'.(count($armador)==2 ? $armador[0][1] : (count($armador)==3 ? $armador[0][1] : "AD")).'" class="pos-meio-ataque"><div class="MA"></div>'.(count($armador)==2 ? $armador[0][0] : (count($armador)==3 ? $armador[0][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.(count($armador)==1 ? $armador[0][1] : (count($armador)==3 ? $armador[1][1] : "AE")).'" class="pos-meio-ataque"><div class="MA"></div>'.(count($armador)==1 ? $armador[0][0] : (count($armador)==3 ? $armador[1][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.(count($armador)==2 ? $armador[1][1] : (count($armador)==3 ? $armador[2][1] : "AF")).'" class="pos-meio-ataque"><div class="MA"></div>'.(count($armador)==2 ? $armador[1][0] : (count($armador)==3 ? $armador[2][0] : "&nbsp")).'</div>';
echo '<div id="nondraggable10">&nbsp</div>';
echo '<div id="draggable'.$meia_esquerdo[1].'" class="pos-meio"><div class="ME"></div>'.($meia_esquerdo[0]!=''?$meia_esquerdo[0]:'&nbsp').'</div>';
echo '<div id="draggable'.(count($meia)==2 ? $meia[0][1] : (count($meia)==3 ? $meia[0][1] : "AG")).'" class="pos-meio"><div class="MC"></div>'.(count($meia)==2 ? $meia[0][0] : (count($meia)==3 ? $meia[0][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.(count($meia)==1 ? $meia[0][1] : (count($meia)==3 ? $meia[1][1] : "AH")).'" class="pos-meio"><div class="MC"></div>'.(count($meia)==1 ? $meia[0][0] : (count($meia)==3 ? $meia[1][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.(count($meia)==2 ? $meia[1][1] : (count($meia)==3 ? $meia[2][1] : "AI")).'" class="pos-meio"><div class="MC"></div>'.(count($meia)==2 ? $meia[1][0] : (count($meia)==3 ? $meia[2][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.$meia_direito[1].'" class="pos-meio"><div class="MD"></div>'.($meia_direito[0]!=''?$meia_direito[0]:'&nbsp').'</div>';
echo '<div id="draggable'.$ala_esquerdo[1].'" class="pos-zaga-meio"><div class="AE"></div>'.($ala_esquerdo[0]!=''?$ala_esquerdo[0]:'&nbsp').'</div>';
echo '<div id="draggable'.(count($volante)==2 ? $volante[0][1] : (count($volante)==3 ? $volante[0][1] : "AJ")).'" class="pos-zaga-meio"><div class="V"></div>'.(count($volante)==2 ? $volante[0][0] : (count($volante)==3 ? $volante[0][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.(count($volante)==1 ? $volante[0][1] : (count($volante)==3 ? $volante[1][1] : "AK")).'" class="pos-zaga-meio"><div class="V"></div>'.(count($volante)==1 ? $volante[0][0] : (count($volante)==3 ? $volante[1][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.(count($volante)==2 ? $volante[1][1] : (count($volante)==3 ? $volante[2][1] : "AL")).'" class="pos-zaga-meio"><div class="V"></div>'.(count($volante)==2 ? $volante[1][0] : (count($volante)==3 ? $volante[2][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.$ala_direito[1].'" class="pos-zaga-meio"><div class="AD"></div>'.($ala_direito[0]!=''?$ala_direito[0]:'&nbsp').'</div>';
echo '<div id="draggable'.$lateral_esquerdo[1].'" class="pos-zaga"><div class="LE"></div>'.($lateral_esquerdo[0]!=''?$lateral_esquerdo[0]:'&nbsp').'</div>';
echo '<div id="draggable'.(count($zagueiro)==2 ? $zagueiro[0][1] : (count($zagueiro)==3 ? $zagueiro[0][1] : "AM")).'" class="pos-zaga"><div class="Z"></div>'.(count($zagueiro)==2 ? $zagueiro[0][0] : (count($zagueiro)==3 ? $zagueiro[0][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.(count($zagueiro)==1 ? $zagueiro[0][1] : (count($zagueiro)==3 ? $zagueiro[1][1] : "AN")).'" class="pos-zaga"><div class="Z"></div>'.(count($zagueiro)==1 ? $zagueiro[0][0] : (count($zagueiro)==3 ? $zagueiro[1][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.(count($zagueiro)==2 ? $zagueiro[1][1] : (count($zagueiro)==3 ? $zagueiro[2][1] : "AO")).'" class="pos-zaga"><div class="Z"></div>'.(count($zagueiro)==2 ? $zagueiro[1][0] : (count($zagueiro)==3 ? $zagueiro[2][0] : "&nbsp")).'</div>';
echo '<div id="draggable'.$lateral_direito[1].'" class="pos-zaga"><div class="LD"></div>'.($lateral_direito[0]!=''?$lateral_direito[0]:'&nbsp').'</div>';
echo '<div id="nondraggable26">&nbsp</div>';
echo '<div id="nondraggable27">&nbsp</div>';
echo '<div id="nondraggable28" class="goleiro"><p>'.$goleiro.'</p></div>';
echo '<div id="nondraggable29">&nbsp</div>';
echo '<div id="nondraggable30">&nbsp</div>';
echo '</div>';
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

    echo '<option class="form-control" value="'.$titular['idJogador'].'"'.($titular['cobrancaPenalti'] == 1? "selected" : "").'>'.$titular['nome']." (N-".$titular['nivel'].')</option>';
}
echo '</select>';
echo '<label for="selectPenal2"> Segundo cobrador de pênalti </label>';
echo '<select class="form-control" id="selectPenal2" name="penal2Select">';
echo '<option class="form-control" value="0">Selecione cobrador...</option>';
foreach($lista_titulares as $titular){

    echo '<option class="form-control" value="'.$titular['idJogador'].'"'.($titular['cobrancaPenalti'] == 2? "selected" : "").'>'.$titular['nome']." (N-".$titular['nivel'].')</option>';
}
echo '</select>';
echo '<label for="selectPenal3"> Terceiro cobrador de pênalti </label>';
echo '<select class="form-control" id="selectPenal3" name="penal3Select">';
echo '<option class="form-control" value="0">Selecione cobrador...</option>';
foreach($lista_titulares as $titular){

    echo '<option class="form-control" value="'.$titular['idJogador'].'"'.($titular['cobrancaPenalti'] == 3? "selected" : "").'>'.$titular['nome']." (N-".$titular['nivel'].')</option>';
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
      </select>
      
      <label for="fimContrato"><b>Encerramento</b></label>
      <input id="fimContrato" type="date" name="fimContrato" class="form-control" min=<?php 
      
$date = new DateTime('now'); // Y-m-d
$date->add(new DateInterval('P30D'));
echo $date->format('Y-m-d');
?>

      <label for="valorJogadorTransf"><b>Proposta de transferência</b></label>
      <input id="valorJogadorTransf" type="number" name="valorJogadorTransf" class='form-control' required>

      <label for="clubeDestinoTransf"><b>Clube de destino</b></label>
      <select id="clubeDestinoTransf"  name="clubeDestinoTransf" class="form-control" required>
          <?php
      // ler times do banco de dados
                $userId = (isset($_SESSION['user_id'])?$_SESSION['user_id']:0);
                $stmt = $time->read($userId);

                echo "<option value=''>Selecione time...</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    if($id != $idTime){
                    echo "<option value='{$id}' data-sexo='{$Sexo}'>{$nome}</option>";
                    }
                }

                ?>

      </select>

      <input type="hidden" value="" name="idJogadorTransf" id="idJogadorTransf" required>
      <input type="hidden" value="<?php echo $idTime ?>" name="clubeOrigemTransf" id="clubeOrigemTransf" required>
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
                    if($id != $idTime){
                    echo "<option value='{$id}' data-sexo='{$Sexo}'>{$nome}</option>";
                    }
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

$(".proposta").click(function(){
    var nome = $(this).closest('tr').find('.nomeEditavel').html();
    var valorInicial = $(this).closest('tr').find('td:nth(8)').html();
    valorInicial = valorInicial.replace(/\D/g, "");
    valorInicial = parseInt(valorInicial) * 1000;
    var id = $(this).attr("id");
    id = id.split("o");
    id = parseInt(id[1]);
    $('#valorJogadorTransf').val(valorInicial);
    $('#nomeJogadorTransf').val(nome);

    sexoJogador = $(this).closest("tr").attr("data-sexo");

    $("#clubeDestinoTransf option").each(function(){

    if($(this).attr("data-sexo") == sexoJogador){
        $(this).show();
    } else {
        $(this).hide();
    }

    });

    $("#modalProposta").show();
    $("#idJogadorTransf").val(id);

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

$("select[id^='select']").on("click", function(){
$(this).css("background-color", "white");
});

</script>

<script>

$('.tablinks').on("click", function(event){

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

$('.clickablerow_tit').on("click",function(event){
    if($(this).hasClass('selected')){
        $(this).removeClass('selected');
    } else {
        $('.clickablerow_tit').each(function(index){
        $(this).removeClass('selected');
    });
    $(this).addClass('selected');
    }
});

$('.clickablerow_res').on("click",function(event){
    if($(this).hasClass('selected')){
        $(this).removeClass('selected');
    } else {
    $('.clickablerow_res').each(function(index){
        $(this).removeClass('selected');
    });
    $(this).addClass('selected');
    }

});

$('.clickablerow_sup').on("click",function(event){
    if($(this).hasClass('selected')){
        $(this).removeClass('selected');
    } else {
    $('.clickablerow_sup').each(function(index){
        $(this).removeClass('selected');
    });
    $(this).addClass('selected');
    }

});

$('#trocar_titular_reserva').on("click",function(event){

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
window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    location.reload();

}

// here we will handle errors and validation messages
});


});

$('#trocar_reserva_suplente').on("click",function(event){

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
window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    location.reload();

}

// here we will handle errors and validation messages
});


});

$('#enviar_para_suplente').on("click",function(event){

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
window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    location.reload();

}

// here we will handle errors and validation messages
});


});



$('#enviar_para_reserva').on("click",function(event){

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
window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    location.reload();

}

// here we will handle errors and validation messages
});


});

$('#enviar_para_titular').on("click",function(event){

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
window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    location.reload();

}

// here we will handle errors and validation messages
});


});


$('#remover_titular').on("click",function(event){

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
window.scrollTo(0, 0);

if (! data.success) {

     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {


    location.reload();

}

// here we will handle errors and validation messages
});

});


</script>


<script>

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


    //location.reload();

}

// here we will handle errors and validation messages
});
   }


});

$(function() {
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


    //location.reload();

}

// here we will handle errors and validation messages
});



        }});
});


function swapNodes(a, b) {
    var aparent= a.parentNode;
    var asibling= a.nextSibling===b? a : a.nextSibling;
    b.parentNode.insertBefore(a, b);
    aparent.insertBefore(b, asibling);

}

</script>

<script>

    $("#formCapitaoCobrancas").on("submit", function(event) {
    event.preventDefault();

    console.log($('#formCapitaoCobrancas').serialize());

    var errosForm;
    var successForm;
    $.post('alterar_capitao_cobradores.php', $('#formCapitaoCobrancas').serialize(),function( data ) {
        errosForm = data.error;
        successForm = data.success;
}, "json").done(function(data){
    location.reload();
});



});

</script>

<script>

$('.disponibilizar').on("click", function(event){

    var idJogador = $(this).closest('tr').prop('id');

     var formData = {
        'idJogador' : idJogador,
        'alteracao' : 1,
    };

    ajaxCallJogador(formData);
});

$('.aposentar').on("click", function(event){

var idJogador = $(this).closest('tr').prop('id');
var idTime = $('#quadro-container').prop('class');

 var formData = {
    'idJogador' : idJogador,
    'alteracao' : 4,
    'idTime' : idTime,
};

ajaxCallJogador(formData);
});

$('.demitir').on("click", function(event){

    var idJogador = $(this).closest('tr').prop('id');
    var idTime = $('#quadro-container').prop('class');

    var formData = {
        'idJogador' : idJogador,
        'idTime' : idTime,
        'alteracao' : 2,
    };

    if(window.confirm("Deseja mesmo demitir?")){
    ajaxCallJogador(formData);
}

});

$('.demitirTecnico').on("click", function(event){

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

$('.desconvocarTecnico').on("click", function(event){

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

$('.desconvocar').on("click", function(event){

var idJogador = $(this).closest('tr').prop('id');
var idTime = $('#quadro-container').prop('class');

var formData = {
    'idJogador' : idJogador,
    'idTime' : idTime,
    'alteracao' : 2,
};

if(window.confirm("Deseja mesmo desconvocar?")){
    ajaxCallJogador(formData);
}


});

$('.editar').on("click", function(event){

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



if(isDono){
    tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
    tbl_row.find('.nascimentoEIdade').hide();
    tbl_row.find('.nascimento').show();
    tbl_row.find('.posicao').hide();

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

        $('.cancelar').click(function(){
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

        tbl_row.find('span').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });
    });

    $('.exportar').click(function(){

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

    $('.salvar').click(function(){
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
        tbl_row.find('.posicao').show();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.comboPais').hide();

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

        if(isDono){
            var nome = tbl_row.find('.nomeEditavel').html();
            var nacionalidade = tbl_row.find(".comboPais").val();
            var nascimento = tbl_row.find(".nascimento").val();
        }

        var valor = parseInt(tbl_row.find(".valorEditavel").html());
        var nivel = tbl_row.find(".nivel").html();
        var posicoes = tbl_row.find(".comboPosicoes").val();
        var idTime = $('#quadro-container').prop('class');

        var formData = {
            'idJogador' : idJogador,
            'alteracao' : 3,
            'valor' : valor,
            'posicoes' : posicoes,
            'nivel' : nivel,
            'idTime' : idTime
        }



if(isDono){
    var moreData = {
            'nome' : nome,
            'nacionalidade' : nacionalidade,
            'nascimento' : nascimento,

        }

    $.extend(formData,moreData);
}

//         for (var pair of formData.entries()) {
//     console.log(pair[0]+ ', ' + pair[1]);
// }

//console.log(formData);

    ajaxCallJogador(formData);

    });







function ajaxCallJogador(formData){

    $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/jogadores/editar_jogador.php', // the url where we want to POST
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
                $('#modalProposta').hide();
                $('#errorbox').append('<div class="alert alert-danger">Não foi possível concluir a ação, '+data.error+'</div>');


            } else {

            $('#modalProposta').hide();
                //$('#errorbox').append("<div class='alert alert-success'>A ação foi concluída com sucesso!</div>");

                location.reload();

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

            location.reload();

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

$('.editarTecnico').on("click", function(event){

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


    tbl_row.find('.nomeNascimento').hide();
    tbl_row.find('.nascimentoEditavel').show();

    tbl_row.find('.posicao').hide();


  }

  tbl_row.find('.nivel').attr('contenteditable', 'true').addClass('editavel');


});


        $('.cancelarTecnico').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nivel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nomeNascimento').show();
        tbl_row.find('.nascimentoEditavel').hide();
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.posicao').show();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.salvarTecnico').hide();
        tbl_row.find('.cancelarTecnico').hide();
        tbl_row.find('.editarTecnico').show();
        tbl_row.find('.propostaTecnico').show();
        tbl_row.find('.demitirTecnico').show();

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


    $('.salvarTecnico').click(function(){
      var tbl_row =  $(this).closest('tr');
      tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
      tbl_row.find('.nivel').attr('contenteditable', 'false').removeClass('editavel');
      tbl_row.find('.nomeNascimento').show();
      tbl_row.find('.nascimentoEditavel').hide();
      tbl_row.find('.comboPais').hide();
      tbl_row.find('.nomePais').show();
      tbl_row.find('.salvarTecnico').hide();
      tbl_row.find('.cancelarTecnico').hide();
      tbl_row.find('.editarTecnico').show();

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
            var nascimento = tbl_row.find(".nascimentoEditavel").val();
            var pais = tbl_row.find('.comboPais').val();

            var moreData = {
                    'nome' : nome,
                    'pais' : pais,
                    'nascimento' : nascimento,

                }

            $.extend(formData,moreData);

            // formData.append('pais', pais);
            // formData.append('nascimento', nascimento);
            // formData.append('nome', nome);
        }
         // formData.append('idTecnico', id);
         // formData.append('nivel', nivel);
         // formData.append('alteracao', alteracao);
         // formData.append('idTime', idTime);


     ajaxCallTecnico(formData);

     });


</script>

<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
