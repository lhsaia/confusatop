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

<script type="text/javascript" src="/js/transferencias.js?ver=<?php echo date("Y-m-d H:i:s"); ?>"></script>   

<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
