<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");

$database = new Database();
$db = $database->getConnection();

$jogador = new Jogador($db);
$time = new Time($db);

$pageType = $_GET['type'];
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$records_per_page = 18;
$from_record_num = ($records_per_page * $page) - $records_per_page;

switch($pageType){
    case 'maiores':
        $nomePagina = 'Maiores Transferências';


        $stmt = $time->maioresTransferencias($from_record_num, $records_per_page);
        $total_rows = $time->countAllTransfers();
        break;
    case 'ultimas':
        $nomePagina = 'Últimas Transferências';
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");

        $stmt = $time->ultimasTransferencias($from_record_num, $records_per_page);
        $total_rows = $time->countAllTransfers();
        break;
    case 'jogadores':
        $nomePagina = 'Jogadores mais Valiosos';
        $stmt = $jogador->maisValiosos($from_record_num, $records_per_page);
        $total_rows = $jogador->countAll();
        break;
    case 'janelas':
        $nomePagina = 'Janelas de Transferência';
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
        $pais = new Pais($db);
        $stmt = $pais->janelasTransferencia($from_record_num, $records_per_page);
        $total_rows = $pais->countAllActive();
        break;
    case 'busca':
        $nomePagina = 'Busca Avançada de Jogadores';
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
        $pais = new Pais($db);
        break;
    case 'buscaTecnico';
        $nomePagina = 'Busca Avançada de Técnicos';
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
        $pais = new Pais($db);
        $tecnico = new Tecnico($db);
        break;
	case 'usuario':
		$nomePagina = 'Transferências de ' . $_SESSION['nomereal'];
        $stmt = $time->todasTransferenciasUsuario($from_record_num, $records_per_page, $_SESSION['user_id']);
        $total_rows = $time->countAllTransfers($_SESSION['user_id']);
        break;
    default:
        $nomePagina = 'Essa página não existe';
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = $nomePagina;
$css_filename = "indexRanking";
$aux_css = "ligas";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>




<div id="quadro-container">
<div align="center" id="quadroTimes">
<h2><?php echo $nomePagina?></h2>

<hr>

<?php

if(isset($stmt)){
    $num = $stmt->rowCount();



// the page where this paging is used
$page_url = "transferencias.php?type=".$pageType."&";

// count all products in the database to calculate total pages
$total_rows = min($total_rows,$records_per_page*10);

// paging buttons here
echo "<div style='clear:both;'></div>";
include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");



echo "<hr>";

// display the products if there are any
if($num>0){

//tabela transferencias (maiores e últimas)
if($pageType == 'maiores' || $pageType == 'ultimas'){

    echo "<table id='tabelaPrincipal' class='table'>";
    echo "<thead>";
        echo "<tr>";
            echo "<th>Jogador</th>";
            echo "<th>Idade</th>";
            echo "<th>País</th>";
            echo "<th>Saiu de</th>";
            echo "<th>Foi para</th>";
            echo "<th>Data</th>";
            echo "<th>Valor</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            extract($row);

            if($sexo == 0){
                $genderCode = "M";
                $genderClass = "genderMas";
            } else {
                $genderCode = "F";
                $genderClass = "genderFem";
            }

            //calcular posicao se não tiver base definida
            if($posicaoBase == ''){
                $posicaoBase = $jogador->nomePosicaoPorCodigo((strpos($stringPosicoes, "1"))+1);
            }

            //acerto escudo
            //$escudosOrigem = explode(".",$escudoOrigem);
            //$escudosDestino = explode(".",$escudoDestino);

            //tratamento valor
            if($valor == 0){
                $valor = "F$ -";
            } else {
                $valor = "F$ ".round($valor/1000000,2)." M";
            }

            echo "<tr id='".$id."'>";
            echo "<td class='nopadding nomeJogador'>{$nomeJogador}<br><span class='posicao'>{$posicaoBase}</span><span class=' {$genderClass} genderSign'>{$genderCode}</span></td>";
            echo "<td class='nopadding'>{$idade}</td>";
            if($nacionalidade != 0){
                echo "<td class='nopadding'><a href='/ligas/paisstatus.php?country=".$nacionalidade."'><img src='/images/bandeiras/{$bandeiraJogador}' class='bandeira nomePais' id='ban".$nacionalidade."'/></a>";
            } else {
                echo "<td>";
            }
            echo "</td>";
            echo "<td class='nopadding'>";
            if($idClubeOrigem != 0){
                echo "<a href='/ligas/teamstatus.php?team=".$idClubeOrigem."'>";
            } else {
                echo "<span>";
            }
            echo "<img src='/images/escudos/".$escudoOrigem."' class='minithumb'/>{$clubeOrigem}";
            if($idClubeOrigem != 0){
            echo "</a>";
            echo "<br/><a class='posicao' href='/ligas/leaguestatus.php?league=".$idLigaOrigem."'><img src='/images/bandeiras/{$bandeiraClubeOrigem}' class='minithumb' id='ban".$paisClubeOrigem."'/>{$ligaOrigem}</a>";
            } else {
            echo "</span>";
            }
            echo "</td>";
            echo "<td class='nopadding'>";
            if($idClubeDestino != 0){
                echo "<a href='/ligas/teamstatus.php?team=".$idClubeDestino."'>";
            } else {
                echo "<span>";
            }
            echo "<img src='/images/escudos/".$escudoDestino."' class='minithumb'/>{$clubeDestino}";
            if($idClubeDestino != 0){
            echo "</a>";
            echo "<br/><a class='posicao' href='/ligas/leaguestatus.php?league=".$idLigaDestino."'><img src='/images/bandeiras/{$bandeiraClubeDestino}' class='minithumb' id='ban".$paisClubeDestino."'/>{$ligaDestino}</a>";
            }
            echo "</td>";
            echo "<td class='nopadding'>".date('d/m/Y', strtotime($data))."</td>";
            echo "<td class='nopadding'>{$valor}</td>";


            echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";

    }

    if($pageType == 'jogadores'){

        echo "<table id='tabelaPrincipal' class='table'>";
        echo "<thead>";
            echo "<tr>";
                echo "<th>Jogador</th>";
                echo "<th>Idade</th>";
                echo "<th>Nível</th>";
                echo "<th>País</th>";
                echo "<th>Time</th>";
                echo "<th>Valor</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);

                if($sexo == 0){
                    $genderCode = "M";
                    $genderClass = "genderMas";
                } else {
                    $genderCode = "F";
                    $genderClass = "genderFem";
                }


                //calcular posicao se não tiver base definida
                if($posicaoBase == ''){
                    $posicaoBase = $jogador->nomePosicaoPorCodigo((strpos($stringPosicoes, "1"))+1);
                }

                //tratamento valor
                if($valor == 0){
                    $valor = "F$ -";
                } else {
                    $valor = "F$ ".round($valor/1000000,2)." M";
                }

                //acerto escudo
                //$escudos = explode(".",$escudo);

                echo "<tr id='".$id."'>";
                echo "<td class='nopadding nomeJogador'>{$nomeJogador}<br><span class='posicao'>{$posicaoBase}</span><span class=' {$genderClass} genderSign'>{$genderCode}</span></td>";
                echo "<td class='nopadding'>{$idade}</td>";
                echo "<td class='nopadding'>{$Nivel}</td>";
                if($nacionalidade != 0){
                    echo "<td class='nopadding'><a href='/ligas/paisstatus.php?country=".$nacionalidade."'><img src='/images/bandeiras/{$bandeiraJogador}' class='bandeira nomePais' id='ban".$nacionalidade."'/></a>";
                } else {
                    echo "<td>";
                }
                echo "</td>";
                echo "<td class='nopadding'>";
                if($clube != 0){
                    echo "<a href='/ligas/teamstatus.php?team=".$clube."'>";
                    echo "<img src='/images/escudos/".$escudo."' class='smallthumb'/>";
                    echo "</a>";
                } else {
                    echo "<span>";
                    echo "<img src='/images/escudos/".$escudo."' class='smallthumb'/>";
                    echo "</span>";
                }
                echo "</td>";
                echo "<td class='nopadding'>{$valor}</td>";


                echo "</tr>";

                }

        echo "</tbody>";
        echo "</table>";

        }

        if($pageType == 'janelas'){

            echo "<table id='tabelaPrincipal' class='table'>";
            echo "<thead>";
                echo "<tr>";
                    echo "<th>País</th>";
                    echo "<th>Status</th>";
                    echo "<th>Transferências atuais</th>";
                    echo "<th>JAN</th>";
                    echo "<th>FEV</th>";
                    echo "<th>MAR</th>";
                    echo "<th>ABR</th>";
                    echo "<th>MAI</th>";
                    echo "<th>JUN</th>";
                    echo "<th>JUL</th>";
                    echo "<th>AGO</th>";
                    echo "<th>SET</th>";
                    echo "<th>OUT</th>";
                    echo "<th>NOV</th>";
                    echo "<th>DEZ</th>";
                    echo "<th>Opções</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);

                    if($statusAtual == 0){
                        $statusAtual = 'Fechado';
                        $contagem = 0;

                    } else {
                        $statusAtual = 'Aberto';

                        if($totalTransfer != NULL){
                            $contagem = $totalTransfer;
                        } else {
                            $contagem = 0;
                        }
                    }

                    if($padraoAbertura != NULL){
                        $calendario = str_split($padraoAbertura);
                    } else {
                        $calendario = array(1,1,1,1,1,1,1,1,1,1,1,1);
                    }

                    echo "<tr id='".$idPais."'>";
                    echo "<td class='leftalign nopadding";
                    echo "'><a href='/ligas/paisstatus.php?country=".$idPais."'><img src='/images/bandeiras/{$bandeira}' class='paddingright bandeira nomePais' id='ban".$idPais."'/>{$nome}</a>";
                    echo "<td class='nopadding mercado".$statusAtual."'>{$statusAtual}</td>";
                    echo "<td class='nopadding'>{$contagem}</td>";
                    foreach($calendario as $index => $mes){
                        if($mes == 1){
                            $statusMes = 'Aberto';
                            $icone = '<i class="fas fa-door-open"></i>';
                        } else {
                            $statusMes = 'Fechado';
                            $icone = '<i class="fas fa-door-closed"></i>';
                        }
                        echo "<td class='nopadding mercado".$statusMes."'><span class='nomSM'>{$icone}</span><select class='selSM".$index."' hidden><option value=1>O</option><option value=0>X</option></select></td>";
                    }
                    $optionString = '';
                    if($_SESSION['user_id']===$idDonoPais){
                        $optionString .= "<a id='dem".$idPais."' title='Editar janela' class='clickable editar'><i class='fas fa-edit inlineButton azul'></i></a>";
                        $optionString .= "<a hidden id='sal".$idPais."' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
                        $optionString .= "<a hidden id='can".$idPais."' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton vermelho'></i></a>";
                    }


                    echo "<td>".$optionString."</td>";

                    echo "</tr>";

                    }

            echo "</tbody>";
            echo "</table>";

            }

 }

// tell the user there are no products
else{
     echo "<div class='alert alert-info'>Não há registros</div>";
}
} else {
?>

<div id='painel_busca_jogador'></div>
<form id='form_busca_jogador'>


    <p>
        <label for='range_niveis'>Faixa de níveis:</label>
        <input type='text' readonly id='mostrador_niveis' name='niveis' class='mostrador_range'/>
    </p>
    <div id='range_niveis'></div>

    <?php if($pageType === 'busca'){?>
    <p>
        <label for='range_idade'>Faixa de idade:</label>
        <input type='text' readonly id='mostrador_idade' name='idades' class='mostrador_range'/>
    </p>
    <div id='range_idade'></div>

    <p>
        <label for='range_valor'>Faixa de valor:</label>
        <input type='text' readonly id='mostrador_valor' name='valores' class='mostrador_range'/>
    </p>
    <div id='range_valor'></div>

<br>
  <fieldset>
    <legend> Posições: </legend>
    <label for="checkbox-1">G</label>
    <input type="checkbox" name="1" id="checkbox-1">
    <label for="checkbox-2">LD</label>
    <input type="checkbox" name="2" id="checkbox-2">
    <label for="checkbox-3">LE</label>
    <input type="checkbox" name="3" id="checkbox-3">
    <label for="checkbox-4">Z</label>
    <input type="checkbox" name="4" id="checkbox-4">
    <label for="checkbox-5">AD</label>
    <input type="checkbox" name="5" id="checkbox-5">
    <label for="checkbox-6">AE</label>
    <input type="checkbox" name="6" id="checkbox-6">
    <label for="checkbox-7">V</label>
    <input type="checkbox" name="7" id="checkbox-7">
    <label for="checkbox-8">MD</label>
    <input type="checkbox" name="8" id="checkbox-8">
    <label for="checkbox-9">ME</label>
    <input type="checkbox" name="9" id="checkbox-9">
    <label for="checkbox-10">MC</label>
    <input type="checkbox" name="10" id="checkbox-10">
    <label for="checkbox-11">PD</label>
    <input type="checkbox" name="11" id="checkbox-11">
    <label for="checkbox-12">PE</label>
    <input type="checkbox" name="12" id="checkbox-12">
    <label for="checkbox-13">MA</label>
    <input type="checkbox" name="13" id="checkbox-13">
    <label for="checkbox-14">Am</label>
    <input type="checkbox" name="14" id="checkbox-14">
    <label for="checkbox-15">Aa</label>
    <input type="checkbox" name="15" id="checkbox-15">

  </fieldset>

  <?php
    }
?>
  <br>

<fieldset>
    <legend>Outros requisitos:</legend>

    <?php if($pageType === 'busca'){?>
    <label for="checkbox-16">Cobrador de Falta</label>
    <input type="checkbox" name="cfalta" id="checkbox-16">
    <label for="checkbox-17">Disponível</label>
    <input type="checkbox" name="disponivel" id="checkbox-17">
    <?php } ?>
    <label for="checkbox-18">Sem Clube</label>
    <input type="checkbox" name="semclube" id="checkbox-18">
    <label for='input_nome'>Nome:</label>
    <input type='text' id='input_nome' name='nomejogador' class='smallform'/>
    <br>
    <label for='input_nacionalidade'>Nacionalidade:</label>
    <select name='nacionalidade' id='input_nacionalidade' class='smallform'>
        <option selected value='0'>Qualquer uma</option>
        <?php
        // query caixa de seleção países desse dono
        $stmtPais = $pais->read();
        while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
            extract($row_pais);
            echo "<option value='".$id."'>".$nome."</option>";
        }
        ?>
    </select>
    <label for='input_mentalidade'>Mentalidade:</label>
    <select id='input_mentalidade' name='mentalidade' class='smallform'>
        <option selected value='0'>Qualquer uma</option>
        <?php if($pageType === 'busca'){?>
        <option value='1'>Mascarado</option>
        <option value='2'>Sangue Frio</option>
        <option value='3'>Pacificador</option>
        <option value='4'>Neutro</option>
        <option value='5'>Lider</option>
        <option value='6'>Provocador</option>
        <option value='7'>Explosivo</option>
        <?php } else if ($pageType === 'buscaTecnico'){ ?>
            <option value='1'>Retranca</option>
            <option value='2'>Defensiva</option>
            <option value='3'>Balanceada</option>
            <option value='4'>Ofensiva</option>
            <option value='5'>Ataque Total</option>
            </select>

            <label for='input_estilo'>Estilo:</label>
            <select id='input_estilo' name='estilo' class='smallform'>
        <option selected value='0'>Qualquer um</option>
            <option value='1'>Explorar contra-ataques</option>
            <option value='2'>Cadenciar o jogo</option>
            <option value='3'>Neutro</option>
            <option value='4'>Atacar pelas laterais</option>
            <option value='5'>Impôr ritmo ofensivo</option>

        <?php }?>
    </select>

  </fieldset>
  <br>
  <fieldset>
  <legend>Opções</legend>

  <label for='checkbox-21' id='toggleButtonLabel2'><span>Apenas CONFUSA</span>
    <input type="checkbox" id="checkbox-21" name='apenasConfusa'>
    </label>

    <label for='checkbox-22' id='toggleButtonLabel3'><span>Masculino</span>
    <input type="checkbox" id="checkbox-22" name='sexo'>
    </label>
    <?php if($pageType === 'busca'){?>
    <label for='checkbox-20' id='toggleButtonLabel'><span>Qualquer dentre as posições marcadas</span>
    <input type="checkbox" id="checkbox-20" name='contemtodos'>
    </label>
    <?php }?>
  </fieldset>

<br>

    <input type='hidden' name='tipoBusca' id='tipoBusca' value='<?php echo $pageType?>'>

    <input type='submit' value='Buscar' class='ui-button ui-widget ui-corner-all'/>
    <input type='reset' value='Limpar' class='ui-button ui-widget ui-corner-all'/>

</form>
<div id='errorbox'></div>
<img id='loading' src='/images/icons/ajax-loader.gif' hidden>
<div hidden id='maquinaSorvete'><?php echo $_SESSION['user_id']?></div>

<div id='tabela_busca_jogador' class='tbl_user_data'></div>




<?php
}
echo('</div>');
echo('</div>');




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
      if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != null){

            $stmt = $time->read($_SESSION['user_id']);


        echo "<option value=''>Selecione time...</option>";

        while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
            extract($row_category);
            //if($id != $idTime){
            echo "<option value='{$id}' data-sexo='{$Sexo}'>{$nome}</option>";
            //}
        }
      } else{
        echo "<option value='erro'>Usuário não logado</option>";
      }
                ?>

      </select>

      <input type="hidden" value="" name="idJogadorTransf" id="idJogadorTransf" required>
      <input type="hidden" value="" name="clubeOrigemTransf" id="clubeOrigemTransf" required>
      <input type="hidden" value="" name="sorvete" id="sorvete" required>

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
                $newStmt = $time->read($_SESSION['user_id'], false);

                echo "<option value=''>Selecione time...</option>";

                while ($new_row_category = $newStmt->fetch(PDO::FETCH_ASSOC)){
                    extract($new_row_category);
                    //if($id != $idTime){
                    echo "<option value='{$id}' data-sexo='{$Sexo}'>{$nome}</option>";
                    //}
                }

                ?>

      </select>

      <input type="hidden" value="" name="idTecnicoTransf" id="idTecnicoTransf" required>
      <input type="hidden" value="" name="clubeOrigemTecnico" id="clubeOrigemTecnico" required>
      <input type="hidden" value="<?php echo $_SESSION['user_id'] ?>" name="sorveteTec" required>

      <button type="submit" name="newsubmit" class="submitbtn">Propor transferência</button>
    </div>

    <div class="container" style="background-color:#f1f1f1">
      <button type="button" onclick="document.getElementById('modalPropostaTecnico').style.display='none'" class="cancelbtn">Cancelar</button>
    </div>
  </form>
</div>


<div id="modalConvocacao" class="modal">

  <form id='formConvocacao' method="POST" class="modal-content animate larger" action="">
    <div class="imgcontainer">
      <span onclick="document.getElementById('modalConvocacao').style.display='none'" class="close" title="Close Modal">&times;</span>
    </div>

    <div class="container">
      <label for="nomeJogadorConvoca"><b>Jogador</b></label>
      <input id="nomeJogadorConvoca"  type="text" name="nomeJogador" disabled>

      <label for="selecaoDestino"><b>Seleção</b></label>
      <select id="selecaoDestino"  name="selecaoDestino" class="form-control" required>
          <?php
      // ler times do banco de dados
      if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != null){

            $stmt = $time->read($_SESSION['user_id'],true);


        while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
            extract($row_category);
            //if($id != $idTime){
            echo "<option value='{$id}' data-sexo='{$Sexo}' data-pais='{$paisTime}' data-status='{$status}'>{$nome}</option>";

            //}
        }
      } else{
        echo "<option value='erro'>Usuário não logado</option>";
      }
                ?>

      </select>

      <input type="hidden" value="" name="idadeJogadorConvoca" id="idadeJogadorConvoca" required>
      <input type="hidden" value="" name="idJogadorConvoca" id="idJogadorConvoca" required>
      <input type="hidden" value="" name="nacionalidadeJogadorConvoca" id="nacionalidadeJogadorConvoca" required>


      <button type="submit" name="submit" class="submitbtn">Convocar</button>
    </div>

    <div class="container" style="background-color:#f1f1f1">
      <button type="button" onclick="document.getElementById('modalProposta').style.display='none'" class="cancelbtn">Cancelar</button>
    </div>
  </form>
</div>

<script>

var emTestes = <?php 
						if(isset($_SESSION['emTestes'])){
							echo $_SESSION['emTestes'];
							} else {
								echo "1";
								}; ?>;

$(document).ready(function(){

$('.editar').on("click",function(event){
    var tbl_row = $(this).closest('tr');
    var idPais = tbl_row.attr('id');

    tbl_row.find('span[class*=nomSM]').each(function(){
         $(this).hide();
     });
    tbl_row.find('select[class*=selSM]').each(function(){
        if($(this).parent().hasClass('mercadoAberto')){
            $(this).val(1);
        } else {
            $(this).val(0);
        }
         $(this).show();
     });

     tbl_row.find('.salvar').show();
     tbl_row.find('.cancelar').show();
     $(this).hide();

});

$('.cancelar').on("click",function(event){
    var tbl_row = $(this).closest('tr');
    var idPais = tbl_row.attr('id');

    tbl_row.find('span[class*=nomSM]').each(function(){
         $(this).show();
     });
    tbl_row.find('select[class*=selSM]').each(function(){
         $(this).hide();
     });

     tbl_row.find('.salvar').hide();
     tbl_row.find('.editar').show();
     $(this).hide();

});

$('.salvar').on("click",function(event){
    var tbl_row = $(this).closest('tr');
    var idPais = tbl_row.attr('id');
    var codeArray = [];

    tbl_row.find('span[class*=nomSM]').each(function(){
         $(this).show();

     });
    tbl_row.find('select[class*=selSM]').each(function(){
        codeArray.push($(this).val());
        $(this).hide();
     });

     tbl_row.find('.cancelar').hide();
     tbl_row.find('.editar').show();
     $(this).hide();

     var codeString ="";
    for (var member in codeArray) {
        codeString += codeArray[member];
    }

    var formData = {
        'idPais' : idPais,
        'codeString' : codeString
    };

    $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : 'alterar_janela.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
            encode          : true
        })

            .done(function(data) {

                // log data to the console so we can see
                //console.log(data);

                if (data.success) {

                    location.reload();

                } else {

                }

            // here we will handle errors and validation messages
            });

});

    $( function() {
    $( "input[id*='checkbox']" ).checkboxradio({
      icon: false
    });
  } );

  var tipoPagina = $("#tipoBusca").val();
  if (tipoPagina.localeCompare('busca') == 0){
      var nivelMin = 0;
      var nivelMax = 100;
      var valMin = 50;
      var valMax = 90;
  } else if(tipoPagina.localeCompare('buscaTecnico') == 0){
    var nivelMin = 0;
    var nivelMax = 10;
    var valMin = 5;
    var valMax = 9;
  }

  $( function() {

    $( "#range_niveis" ).slider({
      range: true,
      min: nivelMin,
      max: nivelMax,
      values: [ valMin, valMax ],
      slide: function( event, ui ) {
        $( "#mostrador_niveis" ).val(  ui.values[ 0 ] + " - " + ui.values[ 1 ] );
      }
    });
    $( "#mostrador_niveis" ).val( $( "#range_niveis" ).slider( "values", 0 ) +
      " - " + $( "#range_niveis" ).slider( "values", 1 ) );

    $( "#range_idade" ).slider({
        range: true,
        min: 14,
        max: 45,
        values: [ 18, 30 ],
        slide: function( event, ui ) {
            $( "#mostrador_idade" ).val(  ui.values[ 0 ] + " - " + ui.values[ 1 ] );
        }
    });
    $( "#mostrador_idade" ).val( $( "#range_idade" ).slider( "values", 0 ) +
    " - " + $( "#range_idade" ).slider( "values", 1 ) );

    $( "#range_valor" ).slider({
        range: true,
        min: 0,
        max: 50000,
        step: 250,
        values: [ 500, 7000 ],
        slide: function( event, ui ) {
            $( "#mostrador_valor" ).val(  ui.values[ 0 ] + "k - " + ui.values[ 1 ] + "k" );
        }
    });
    $( "#mostrador_valor" ).val( $( "#range_valor" ).slider( "values", 0 ) +
    "k - " + $( "#range_valor" ).slider( "values", 1 ) + "k" );
    } );

    $('input[type=reset]').on("click",function(e){

  var $slider = $("#range_idade");
  $slider.slider("values", [18, 30]);
  var $slider = $("#range_niveis");
  $slider.slider("values",[50, 90]);
  $( "#mostrador_niveis" ).val("50 - 90");
  $( "#mostrador_idade" ).val("18 - 30");
  e.preventDefault();
  $("input[type=checkbox]").each(function(){
      $(this).prop("checked", false).change();
  });
  $("#input_nome").val("");
  $("select").each(function(){
      $(this).val("0");
      $(this).selectmenu("refresh");
  });

$('span', '#toggleButtonLabel').text('Qualquer dentre as posições marcadas');
$('span', '#toggleButtonLabel2').text('Apenas CONFUSA');
$('span', '#toggleButtonLabel3').text('Masculino');

    });

    $('#input_mentalidade').selectmenu();
    $('#input_nacionalidade').selectmenu();
    if($("#input_estilo").length != 0) {
        $('#input_estilo').selectmenu();
      }



    $('#toggleButtonLabel').click(function () {
        var checked = $('input', this).is(':checked');
        $('span', this).text(checked ? 'Todas as posições marcadas' : 'Qualquer dentre as posições marcadas');
    });

    $('#toggleButtonLabel2').click(function () {
        var checked = $('input', this).is(':checked');
        $('span', this).text(checked ? 'Incluir NC-Board e reais' : 'Apenas CONFUSA');
    });

    $('#toggleButtonLabel3').click(function () {
        var checked = $('input', this).is(':checked');
        $('span', this).text(checked ? 'Feminino' : 'Masculino');
    });

var localData = [];
var asc = true;
var activeSort = '';

$(document).ready(function($){
    
    $('#fimContrato').hide();
    $('label[for="fimContrato"]').hide();
    $('#fimContrato').val('');

//adicionado para ocultar data de encerramento em caso de venda direta
$('#tipoTransacao').change(function(){
    if($(this).val() == 0){
        $('#fimContrato').hide();
        $('label[for="fimContrato"]').hide();
        $('#fimContrato').val('');
    } else {
        $('#fimContrato').show();
        $('label[for="fimContrato"]').show();
    }
});



$('#form_busca_jogador').submit(function(e){
    e.preventDefault();


        var searchForm = $('#form_busca_jogador').serialize();
        //$('#loading').show();  // show loading indicator

        //console.log(searchForm);
        $('#loading').show();  // show loading indicator

        $.ajax({
            url:"pesquisa.php",
            method:"POST",
            cache:false,
            data: searchForm,
            success:function(data){
                $('#loading').hide();  // hide loading indicator
                updateTable(JSON.parse(data),1,0,0);
                localData = JSON.parse(data);
                //console.log(localData);
            }
        });

});







function updateTable(ajax_data, current_page, highlighted, direction){

    var results_per_page = 17;
    var total_results = ajax_data.length;
    var total_pages = Math.ceil(total_results/results_per_page);

    var treated_page;
    if(current_page == 'final'){
        treated_page = total_pages;
    } else if(current_page == 'inicio'){
        treated_page = 1;
    } else {
        treated_page = current_page;
    }

    var from_result_num = (results_per_page * treated_page) - results_per_page;

    var pgn = pagination(treated_page,total_pages);

    //criar tabela dinamicamente
    var tbl = '';
    tbl += pgn;
    tbl += "<hr>";
    tbl += "<table id='tabelajogos' class='table'>";
        tbl += "<thead id='headings'>";
            tbl += "<tr>";
            if(tipoPagina.localeCompare('busca') == 0){
                tbl += "<th asc='' id='nomeJogador' class='headings'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspJogador</th>";
            }  if(tipoPagina.localeCompare('buscaTecnico') == 0){
                tbl += "<th asc='' id='nomeJogador' class='headings'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspTécnico</th>";
            }
                tbl +=  "<th asc='' id='posicoes' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspPosições</th>";
                tbl +=  "<th asc='' id='idadeJogador' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspIdade</th>";
                tbl +=  "<th asc='' id='bandeira' class='headings'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspNacionalidade</th>";
                tbl +=  "<th asc='' id='nivel' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspNivel</th>";
                tbl +=  "<th asc='' id='mentalidade' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspMentalidade</th>";
                if(tipoPagina.localeCompare('busca') == 0){
                tbl +=  "<th asc='' id='cobrancaFalta' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspCobrança de Falta</th>";
                } else if(tipoPagina.localeCompare('buscaTecnico') == 0){
                    tbl +=  "<th asc='' id='cobrancaFalta' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspEstilo</th>";
                }
                tbl +=  "<th asc='' id='nomeClube' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspClube</th>";
                if(tipoPagina.localeCompare('busca') == 0){
                tbl +=  "<th asc='' id='disponibilidade' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspDisp.</th>";
                tbl +=  "<th asc='' id='valor' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspValor</th>";
                }
                tbl += "<th asc=''  ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspOpções</td>";
            tbl +=  "</tr>";
        tbl +=  "</thead>";
        tbl +=  "<tbody>";

        // criar linhas
        $.each(ajax_data, function(index, val){

            if(index>=(from_result_num-1) && index<=(from_result_num+results_per_page-2)){

                // if(val['escudoClube'] != null){
                //     var escudo = val['escudoClube'].split(".");
                //     var escudoExt = escudo[1];
                //     var escudoImg = escudo[0];
                // }

                var valor = "F$ " + Math.round(parseFloat(val['valor']/10000))/100 + " M";


            tbl += "<tr id='"+val['idJogador']+"'>";
                if(tipoPagina.localeCompare('busca') == 0){
                    tbl += "<td class='nopadding nomeJogador'><a href='/ligas/playerstatus.php?player="+val['idJogador']+"'>"+val['nomeJogador']+"</a><br><span class='posicao'>"+val['posicaoBaseJogador']+"</span></td>";
                    tbl += "<td class='nopadding'>"+val['posicoes'].slice(0,-1)+"</td>";
                } else  if(tipoPagina.localeCompare('buscaTecnico') == 0){
                    tbl += "<td class='nopadding nomeJogador'>"+val['nomeJogador']+"<br><span class='posicao'>Técnico</span></td>";
                    tbl += "<td class='nopadding'>T</td>";
                }
                tbl += "<td class='nopadding'>"+val['idadeJogador']+"</td>";
                if(val['nacionalidade'] != 0){
                tbl += "<td class='nopadding'><a href='/ligas/paisstatus.php?country="+val['nacionalidade']+"'><img src='/images/bandeiras/"+val['bandeira']+"' class='bandeira nomePais' id='ban"+val['nacionalidade']+"'/></a>";
                } else {
                tbl += "<td>";
                }
                tbl += "</td>";
                tbl +=  "<td class='nopadding'>"+val['nivel']+"</td>";
                tbl +=  "<td class='nopadding'>"+val['mentalidade']+"</td>";
                if(tipoPagina.localeCompare('busca') == 0){
                tbl +=  "<td class='nopadding'>"+val['cobrancaFalta']+"</td>";
                } else if(tipoPagina.localeCompare('buscaTecnico') == 0){
                    tbl +=  "<td class='nopadding'>"+val['estilo']+"</td>";
                }
                tbl += "<td class='nopadding'>";
            if(val['idClube'] != 0){
                tbl += "<a href='/ligas/teamstatus.php?team="+val['idClube']+"'>";
            } else {
                tbl += "<span>";
            }
            tbl += "<img src='/images/escudos/"+val['escudoClube']+"' class='minithumb'/>"+val['nomeClube'];
            if(val['idClube'] != 0){
            tbl += "</a>";
            tbl += "<br/><a class='posicao' href='/ligas/leaguestatus.php?league="+val['idLiga']+"'><img src='/images/bandeiras/"+val['bandeiraClube']+"' class='minithumb' id='ban"+val['paisClube']+"'/>"+val['ligaClube']+"</a>";
            } else {
            tbl += "</span>";
            }
            tbl += "</td>";
            if(tipoPagina.localeCompare('busca') == 0){
                tbl +=  "<td class='nopadding'>"+val['disponibilidade']+"</td>";
                tbl +=  "<td class='nopadding'>"+valor+"</td>";

            }
                tbl +=  "<td class='nopadding'>";


                if((tipoPagina.localeCompare('busca') == 0 && !emTestes) || (tipoPagina.localeCompare('busca') == 0 && emTestes && val['donoJogador'] == 1) ){
                    tbl += "<a id='pro"+val['idJogador']+"' title='Fazer Proposta' class='clickable proposta'><i class='fas fa-money-bill inlineButton'></i></a>";
                } else if((tipoPagina.localeCompare('buscaTecnico') == 0 && !emTestes) || (tipoPagina.localeCompare('buscaTecnico') == 0 && emTestes && val['donoJogador'] == 1)){
                    tbl += "<a id='pro"+val['idJogador']+"' title='Fazer Proposta' class='clickable propostaTecnico'><i class='fas fa-money-bill inlineButton'></i></a>";
                }


                    if(val['donoJogador'] == 1) {
                        if(tipoPagina.localeCompare('busca') == 0){
                            tbl += "<a id='pro"+val['idJogador']+"' title='Convocar' class='clickable convocar'><i class='fas fa-globe inlineButton'></i></a>";
                        } else if(tipoPagina.localeCompare('buscaTecnico') == 0){
                            tbl += "<a id='pro"+val['idJogador']+"' title='Convocar' class='clickable convocarTecnico'><i class='fas fa-globe inlineButton'></i></a>";
                        }

                    }

                tbl += "</td>";

            tbl +=  "</tr>";
            }
        });

        tbl += '</tbody>';
    tbl += '</table>';

    //mostrar dados da tabela
    $(document).find('.tbl_user_data').html(tbl);
    addFilters();

    $(document).find('#'+highlighted).addClass('highlighted');

    if(direction == 1){
        asc = activeDirection;
    }
    if(asc){
        $(document).find('#'+highlighted).find('.descending').addClass('hidden');
        $(document).find('#'+highlighted).find('.ascending').removeClass('hidden');
    } else {
        $(document).find('#'+highlighted).find('.ascending').addClass('hidden');
        $(document).find('#'+highlighted).find('.descending').removeClass('hidden');
    }

    activeSort = highlighted;
    activeDirection = asc;
}

$(document).on('click', '.pagination_link', function(){
    var page = $(this).attr('id');
    updateTable(localData, page,activeSort, 1);
});


function pagination(current_page, total_pages){
var pgn = '';
pgn += "<ul class='pagination'>";

// button for first page
if(current_page>1){
    pgn +=  "<li><button class='pagination_link' id='inicio' title='Ir para o início'>";
    pgn +=  "Inicio";
    pgn +=  "</button></li>";
}

// range of links to show
const range = 2;

// display links to 'range of pages' around 'current page'
var initial_num = current_page - range;
var condition_limit_num = (+current_page + +range)  + +1;

// teste com While
var x;
if(initial_num > 0){
    x = initial_num;
} else {
    x = 1;
}

while(x <= total_pages && x < condition_limit_num){
    if (x == current_page) {
            pgn += "<li><button class='pagination_link' id='"+x+"' disabled>"+x+"<span class=\"sr-only\">(current)</span></button></li>";
        }
        else {
            pgn += "<li><button class='pagination_link' id='"+x+"'>"+x+"</button></li>";
        }
    x = x+1;
}

// button for last page
if(current_page<total_pages){
    pgn += "<li><button class='pagination_link' id='final' title='Última página é "+total_pages+".'>";
    pgn += "Final";
    pgn += "</button></li>";
}

pgn += "</ul>";

return pgn;
}

function addFilters(){
    $(document).find('.headings').click(function(){
        treatResults(this);
     });
}

$(document).ready(function(){
    addFilters();
});



function treatResults(item){
    var id = $(item).attr('id');

    sortResults(id, asc);

    if(asc){
        asc = false;
    } else {
        asc = true;
    }

}

function sortResults(prop, asc) {

//console.log(localData);

if(prop == 'nivelJogador' || prop == "idadeJogador" || prop == "valor"){

    localData = localData.sort(
        function(a,b){
            if (asc) return a[prop] - b[prop];
            if (!asc) return b[prop] - a[prop];
            else return 0;
        }
    );
} else {
    localData = localData.sort(
        function(a, b) {
            if (((a[prop] < b[prop]) && (!asc))||((a[prop] > b[prop]) && (asc))) return 1;
            else if (((a[prop] > b[prop]) && (!asc))||((a[prop] < b[prop]) && (asc))) return -1;
            else return 0;
        }
    );
}


    updateTable(localData, 1,prop,0);

    }

});


$(document).on("click", '.convocar', function(event){
    let propId = $(this).prop("id");
    let jogId = parseInt(propId.replace(/\D/g,''));
	

    var arrayJogador = localData.find(jogador => jogador.idJogador === jogId);

    var nome = arrayJogador.nomeJogador;
    var sexoJogador = arrayJogador.sexoJogador;
    var idadeJogador = arrayJogador.idadeJogador;
    var nacionalidadeJogador = arrayJogador.nacionalidade;

    var counter = 0;
    $("#selecaoDestino option").each(function(){

        if($(this).attr("data-sexo") == sexoJogador && $(this).attr("data-pais") == nacionalidadeJogador){

            if($(this).attr("data-status") == 1){
                $(this).show();
                counter = counter + 1;
                $(this).prop('selected', true);
            } else if($(this).attr("data-status") == 2 && idadeJogador <= 21 ){
                $(this).show();
                counter = counter + 1;
            } else if($(this).attr("data-status") == 3 && idadeJogador <= 20 ){
                $(this).show();
                counter = counter + 1;
            } else if($(this).attr("data-status") == 4 && idadeJogador <= 18 ){
                $(this).show();
                counter = counter + 1;
            } else {
                $(this).hide();
            }

        } else {
            $(this).hide();
            $(this).prop('selected', false);
        }

    });

    if(counter == 0){
        $("#errorbox").html("<div class='alert alert-danger'>Não há seleções disponíveis para esse(a) jogador(a)!</div>");
    } else {
        $('#nomeJogadorConvoca').val(nome);
        $("#idJogadorConvoca").val(jogId);
        $("#idadeJogadorConvoca").val(idadeJogador);
        $("#nacionalidadeJogadorConvoca").val(nacionalidadeJogador);

        $('#modalConvocacao').show();
    }

});

$(document).on("click", '.convocarTecnico', function(event){
    let propId = $(this).prop("id");
    let jogId = parseInt(propId.replace(/\D/g,''));

    var arrayJogador = localData.find(jogador => jogador.idJogador === jogId);

    var nome = arrayJogador.nomeJogador;
    var nacionalidadeJogador = arrayJogador.nacionalidade;

    var counter = 0;
    $("#selecaoDestino option").each(function(){

        if($(this).attr("data-pais") == nacionalidadeJogador){

            $(this).show();
            counter = counter + 1;

        } else {
            $(this).hide();
            $(this).prop('selected', false);
        }

    });

    if(counter == 0){
        $("#errorbox").html("<div class='alert alert-danger'>Não há seleções disponíveis para esse(a) técnico(a)!</div>");
    } else {
        $('#nomeJogadorConvoca').val(nome);
        $("#idJogadorConvoca").val(jogId);

        $("#nacionalidadeJogadorConvoca").val(nacionalidadeJogador);

        $('#modalConvocacao').show();
    }

});


$(document).on("click", '.proposta', function(event) {

    let propId = $(this).prop("id");
    let jogId = parseInt(propId.replace(/\D/g,''));

	var arrayJogador = localData.find( jogador => jogador.idJogador === jogId );

	//console.log(localData);

var nome = arrayJogador.nomeJogador;
var valorInicial = arrayJogador.valor;
var clube = arrayJogador.idClube;
var sorvete = $("#maquinaSorvete").html();
var sexoJogador = arrayJogador.sexoJogador;

if(clube != 0){
    $('#valorJogadorTransf').val(valorInicial);
} else {
    $('#valorJogadorTransf').val(0);
}

$("#clubeDestinoTransf option").each(function(){

    if($(this).attr("data-sexo") == sexoJogador){
        if($(this).val() == clube){
            $(this).attr("disabled", "disabled");
            $(this).hide();


        } else {
            $(this).show();
            $(this).removeAttr("disabled");
        }
    } else {
        $(this).attr("disabled", "disabled");
        $(this).hide();
    }

});


$('#nomeJogadorTransf').val(nome);
$("#idJogadorTransf").val(jogId);
$("#clubeOrigemTransf").val(clube);
$('#sorvete').val(sorvete);
$('#modalProposta').show();
});

$(document).on("click", '.propostaTecnico', function(event) {

    let propId = $(this).prop("id");
    let jogId = parseInt(propId.replace(/\D/g,''));


var arrayJogador = localData.find(jogador => jogador.idJogador === jogId);

var nome = arrayJogador.nomeJogador;
var clube = arrayJogador.idClube;
var sorvete = $("#maquinaSorvete").html();
var sexoJogador = arrayJogador.sexoJogador;

$("#clubeDestinoTecnico option").each(function(){


        if($(this).val() == clube){
          $(this).attr("disabled", "disabled");
            $(this).hide();
        } else {
            $(this).show();
            $(this).removeAttr("disabled");
        }


});

$('#nomeTecnicoTransf').val(nome);
$("#idTecnicoTransf").val(jogId);
$("#clubeOrigemTecnico").val(clube);
$('#sorveteTec').val(sorvete);
$('#modalPropostaTecnico').show();
});

$("#formPropostaTecnico").submit(function(event){

    var clubeOrigem = $('input[name=clubeOrigemTecnico]').val();
    var clubeDestino = $('select[name=clubeDestinoTecnico]').val();
    var formData = {
        'idTecnico' : $('input[name=idTecnicoTransf]').val(),
        'clubeOrigem' : clubeOrigem,
        'clubeDestino' : clubeDestino,
        'sorveteTec' : $('input[name=sorveteTec]').val()
    };

    if(clubeOrigem == clubeDestino){
        $('#errorbox').html("<div class='alert alert-danger'>O técnico não pode ir para seu time atual!</div>");
    }

    //console.log(formData);

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/ligas/fazer_proposta_tecnico.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
//console.log(data);
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

    event.preventDefault();

    var clubeOrigem = $('input[name=clubeOrigemTransf]').val();
    var clubeDestino = $('select[name=clubeDestinoTransf]').val();
    var formData = {
        'idJogador' : $('input[name=idJogadorTransf]').val(),
        'clubeOrigem' : clubeOrigem,
        'clubeDestino' : clubeDestino,
        'valor' : $('input[name=valorJogadorTransf]').val(),
        'sorvete' : $('input[name=sorvete]').val(),
        'tipoTransacao' : $('select[name=tipoTransacao').val(),
        'fimContrato' : $('input[name=fimContrato').val()
    };

    //console.log(formData);

    if(clubeOrigem == clubeDestino){
        $('#errorbox').html("<div class='alert alert-danger'>O jogador não pode ir para seu time atual!</div>");

    } else {
        $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/jogadores/fazer_proposta.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
//console.log(data);
window.scrollTo(0, 0);

if (! data.success) {
    $('#modalProposta').hide();
     $('#errorbox').html('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {

$('#modalProposta').hide();
     $('#errorbox').html("<div class='alert alert-success'>O pedido foi realizado com sucesso!</div>");

}

// here we will handle errors and validation messages
});
    }





});


$("#formConvocacao").submit(function(event){

    event.preventDefault();

    var selecaoDestino = $('select[name=selecaoDestino]').val();
    var idJogador = $('input[name=idJogadorConvoca]').val();
    var tipoContrato = $('option:selected', $('select[name=selecaoDestino]')).attr('data-status');
    var formData = {
        'selecaoDestino' : selecaoDestino,
        'idJogador' : idJogador,
        'tipoContrato' : tipoContrato
    };

    //console.log(formData);

    if(tipoPagina.localeCompare('busca') == 0){
        var url = '/jogadores/convocar.php';
    } else if(tipoPagina.localeCompare('buscaTecnico') == 0){
        var url = '/ligas/convocar_tecnico.php';
    }

        $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : url, // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
//console.log(data);
window.scrollTo(0, 0);

if (! data.success) {
    $('#modalConvocacao').hide();
     $('#errorbox').html('<div class="alert alert-danger">Não foi possível fazer a convocação, '+data.error+'</div>');


} else {

$('#modalConvocacao').hide();
     $('#errorbox').html("<div class='alert alert-success'>Convocação realizada com sucesso!</div>");

}

// here we will handle errors and validation messages
});






});

});
</script>   

<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
