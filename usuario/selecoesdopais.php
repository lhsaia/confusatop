<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");

$database = new Database();
$db = $database->getConnection();

$time = new Time($db);
$pais = new Pais($db);
$estadio = new Estadio($db);

$codigoPais = $_GET['idPais'];
$stmtPais = $pais->readInfo($codigoPais);
$resultPais = $stmtPais->fetch(PDO::FETCH_ASSOC);
$nomePais = $resultPais['nome'];

$page_title = "Tela de seleções - " . $nomePais;
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){



    // query caixa de seleção países desse dono
$stmtPais = $pais->read($_SESSION['user_id']);
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $nome);
    $listaPaises[] = $addArray;
}

// query caixa de seleção estadios desse dono
$stmtEstadio = $estadio->read($_SESSION['user_id']);
$listaEstadios = array();
while ($row_estadio = $stmtEstadio->fetch(PDO::FETCH_ASSOC)){
    extract($row_estadio);
    $addArray = array($id, $nome, $capacidade);
    $listaEstadios[] = $addArray;
}

?>


<div id="quadro-container">
<div align="center" id="quadroTimes">
<button id='importar_time' onclick="window.location='/times/criar_selecao.php?idPais=<?php echo $codigoPais?>';">Criar seleção</button>
<h2>Tela de seleções - <?php echo $nomePais ?></h2>

<hr>

<div style='clear:both;'></div>

<hr>

<?php

//queries de ligas e estadios

//query de ligas
$stmt = $time->readSelecoes($codigoPais);

$num = $stmt->rowCount();

echo "<hr>";
echo "<div id='errorbox'></div>";

// display the products if there are any
if($num>0){

    echo "<table id='tabelaPrincipal' class='table'>";
    echo "<thead>";
        echo "<tr>";
           // echo "<th>Id</th>";
           echo "<th width='10%'>Categoria</th>";
           echo "<th width='2%'>Escudo</th>";
           echo "<th width='2%'>Uniforme 1</th>";
           echo "<th width='2%'>Cores 1</th>";
           echo "<th width='2%'>Uniforme 2</th>";
           echo "<th width='2%'>Cores 2</th>";
           echo "<th width='15%'>Estadio</th>";
           echo "<th width='2%'>Max Torcida</th>";
           echo "<th width='2%'>Fidelidade</th>";
           echo "<th width='2%'>País</th>";

           echo "<th width='5%' class=''>Opções</th>";

        echo "</tr>";
        echo "</thead>";

        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            //$escudo_imagem = explode(".",$Escudo);
            //$uniforme1_imagem = explode(".",$Uniforme1);
            //$uniforme2_imagem = explode(".",$Uniforme2);
            if($sexo == 0){
                $genderCode = "M";
                $genderClass = "genderMas";
            } else {
                $genderCode = "F";
                $genderClass = "genderFem";
            }



            echo "<tr id='".$ID."' data-sexo='".$sexo."'>";
                //echo "<td><span id=".$id.">{$id}</span></td>";
                echo "<td><a class='linkNome' href='/ligas/teamstatus.php?team={$ID}' ><span class='nomeEditavel' id='nom".$ID."'>{$Nome}</span><span class=' {$genderClass} genderSign'>{$genderCode}</span></a></td>";
                echo "<td><div class='imageUpload'><img class='thumb' src='/images/escudos/".$Escudo."' /> <input type='file' hidden id='escudo".$ID."' class='hiddenInput custom-file-upload' name='escudo' accept='.jpg,.png,.jpeg'/></div></td>";
                echo "<td><div class='imageUpload'><img class='thumb' src='/images/uniformes/".$Uniforme1."' /> <input type='file' hidden id='uni1".$ID."' class='hiddenInput custom-file-upload' name='uni1' accept='.jpg,.png,.jpeg'/></div></td>";
                echo "<td class='celula-uniforme'><div class='quadrado-uniforme' id='{$Uni1Cor1}'><input type='color' name='u1c1' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='{$Uni1Cor2}'><input type='color' name='u1c2' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='{$Uni1Cor3}'><input type='color' name='u1c3' hidden class='hiddenInput' /></div></td>";
                echo "<td><div class='imageUpload'><img class='thumb' src='/images/uniformes/".$Uniforme2."' /> <input type='file' hidden id='uni2".$ID."' class='hiddenInput custom-file-upload' name='uni2' accept='.jpg,.png,.jpeg'/></div></td>";
                echo "<td class='celula-uniforme'><div class='quadrado-uniforme' id='{$Uni2Cor1}'><input type='color' name='u2c1' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='{$Uni2Cor2}'><input type='color' name='u2c2' hidden class='hiddenInput' /></div><div class='quadrado-uniforme' id='{$Uni2Cor3}'><input type='color' name='u2c3' hidden class='hiddenInput' /></div></td>";

                    echo "<td class='wide'><span class='nomePais' id='est".$ID."'>{$nomeEstadio} ({$capacidade})</span>";
                echo " <select class='comboEstadio editavel ' id='selest{$estadioId}' hidden>  ";

                    for($i = 0; $i < count($listaEstadios);$i++){
                        echo "<option value='{$listaEstadios[$i][0]}'>{$listaEstadios[$i][1]} ({$listaEstadios[$i][2]})</option>";
                    }
                    echo "</select>";
                    echo "</td>";
                echo "<td><span class='nomeEditavel' id='max".$ID."'>{$MaxTorcedores}</span></td>";
                echo "<td><span class='nomeEditavel' id='fid".$ID."'>{$Fidelidade}</span></td>";

                if($idPais != 0){
                    echo "<td class='wide'><img src='/images/bandeiras/{$bandeiraPais}' class='bandeira nomePais' id='ban".$ID."'>  <span class='nomePais' id='pai".$ID."'>{$siglaPais}</span>";
                } else {
                    echo "<td>";
                }
                echo " <select class='comboPais editavel ' id='{$idPais}' hidden>'  ";

                    for($i = 0; $i < count($listaPaises);$i++){
                        echo "<option value='{$listaPaises[$i][0]}'>{$listaPaises[$i][1]}</option>";
                    }
                    echo "</select>";
                    echo "</td>";

                $optionsString = "<td class='wide'>";

                if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
                    if($_SESSION['admin_status'] == '1' || $_SESSION['user_id'] === $idDonoPais){
                        $optionsString .= "<a id='edi".$ID."' title='Editar' class='clickable editar'><i class='far fa-edit inlineButton'></i></a>";
                        $optionsString .= "<a id='dow".$id."' title='Baixar arquivo .ymt' class='clickable exportar'><i class='fas fa-download inlineButton azul'></i></a>";

                        $optionsString .= "<a hidden id='sal".$ID."' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
                        $optionsString .= "<a hidden id='can".$ID."' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton negative'></i></a>";
                    }
                    $optionsString .= "</td>";
                    echo $optionsString;
                }

                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há seleções para esse país</div>";
}

echo('</div>');
echo('</div>');

?>

<script>

$('.quadrado-uniforme').each(function(i, obj) {
    var cores = $(this).attr('id');
    cores = cores.match(/.{1,3}/g);
    var fundo = "rgb(";
    fundo += cores[0];
    fundo += ",";
    fundo += cores[1];
    fundo += ",";
    fundo += cores[2];
    fundo += ")";
    $(this).css({ 'background-color' : fundo, });

});


//var cor = $();
//$('.text_box').css({ 'color' : color, });

</script>

<script>

$(document).ready(function() {

  $('.exportar').click(function(){

    var tbl_row =  $(this).closest('tr');
    var idTime = $(this).closest('tr').attr("id");

    $.ajax({
      url: '/ligas/get_ymt_info.php',
      type: 'POST',
      dataType: 'json',
      data: {idTime: idTime}
    })
    .done(function(response) {

      var estilo = 1;
      var xmlData = "<clubeExportado>\n <clube>\n <ID>"+
      idTime+"</ID>\n  <Nome>"+
      response[0][0].Nome+"</Nome>\n  <TresLetras>"+
      response[0][0].TresLetras+"</TresLetras>\n  <bdEstadio>"+
      response[0][0].Estadio+"</bdEstadio>\n  <Escudo>"+
      "Escudos/team"+idTime+".png"+"</Escudo>\n <Uni1Cor1>"+
      response[0][0].Uni1Cor1+"</Uni1Cor1>\n <Uni1Cor2>"+
      response[0][0].Uni1Cor2+"</Uni1Cor2>\n <Uni1Cor3>"+
      response[0][0].Uni1Cor3+"</Uni1Cor3>\n <Uniforme1>"+
      "Uniformes/1-team"+idTime+".png"+"</Uniforme1>\n <Uni2Cor1>"+
      response[0][0].Uni2Cor1+"</Uni2Cor1>\n <Uni2Cor2>"+
      response[0][0].Uni2Cor2+"</Uni2Cor2>\n <Uni2Cor3>"+
      response[0][0].Uni2Cor3+"</Uni2Cor3>\n <Uniforme2>"+
      "Uniformes/2-team"+idTime+".png"+"</Uniforme2>\n <MaxTorcedores>"+
      response[0][0].MaxTorcedores+"</MaxTorcedores>\n <Fidelidade>"+
      response[0][0].Fidelidade+"</Fidelidade>\n <numJogadores>"+
      0+"</numJogadores>\n <numReservas>"+
      0+"</numReservas>\n <Moral>"+
      100+"</Moral>\n <bonusContraAtaque>"+
      0+"</bonusContraAtaque>\n <cobPenaltis/>\n </clube>\n <elenco>\n <Clube>"+
      idTime+"</Clube>\n <Jogador>\n ";
      for(let jogador of response[1]){
        xmlData += "<int>" + jogador.idJogador + "</int>\n";
      }
      xmlData += "</Jogador>\n <Tecnico>"+
      response[2][0].id+"</Tecnico>\n </elenco>\n <escalacao>\n <Clube>"+
      idTime+"</Clube>\n <Pos>\n";
      for(i = 0;i<11;i++){
        xmlData += "<string>" + response[1][i].siglaPosicao + "</string>\n";
      }
      xmlData += "</Pos>\n <Jogador>\n";
      for(i = 0;i<11;i++){
        xmlData += "<int>" + response[1][i].idJogador + "</int>\n";
      }
      xmlData += "</Jogador>\n <Capitao>";
      for(i = 0;i<11;i++){
        if(response[1][i].capitao == 1){
          xmlData += response[1][i].idJogador;
        }
      }
      xmlData += "</Capitao>\n <Penalti>\n";
      for(posicaoCobrador = 1;posicaoCobrador<4;posicaoCobrador++){
        for(i = 0;i<11;i++){
          if(response[1][i].cobrancaPenalti == posicaoCobrador){
            xmlData += "<int>" + response[1][i].idJogador + "</int>\n";
          }
        }
      }
      xmlData += "</Penalti>\n <JogadorImportado/>\n <CapitaoOriginal>0</CapitaoOriginal>\n <PenaltisOriginais/>\n </escalacao>\n <jogadores>\n";
      for(let jogador of response[1]){
        xmlData += "<jogador>\n <ID>" +
        jogador.idJogador + "</ID>\n <Nome>" +
        jogador.nomeJogador +  "</Nome>\n <Idade>" +
        jogador.Idade +  "</Idade>\n <Nivel>" +
        jogador.Nivel +  "</Nivel>\n <Potencial>" +
        0 +  "</Potencial>\n <CrescBase>" +
        0 +  "</CrescBase>\n <Mentalidade>" +
        jogador.Mentalidade +  "</Mentalidade>\n <CobradorFalta>" +
        jogador.CobradorFalta +  "</CobradorFalta>\n <apto>" +
        "true" +  "</apto>\n </jogador>\n";
      }
      xmlData += "</jogadores>\n <nacionalidades>\n ";
      for(let jogador of response[1]){
        xmlData += "<string>" + jogador.Nacionalidade + "</string>";
      }
      //console.log(response[2][0]);
      xmlData += "</nacionalidades>\n <tecnico>\n <ID>"+
      response[2][0].id +"</ID>\n <Nome>"+
      response[2][0].Nome+"</Nome>\n <Idade>"+
      response[2][0].Idade+"</Idade>\n <Nivel>"+
      response[2][0].Nivel+"</Nivel>\n <Mentalidade>"+
      response[2][0].Mentalidade+"</Mentalidade>\n <Estilo>"+
      response[2][0].Estilo+"</Estilo>\n </tecnico>\n <estadio>\n <ID>"+
      response[3][0].id+"</ID>\n <Nome>"+
      response[3][0].Nome+"</Nome>\n <Capacidade>"+
      response[3][0].Capacidade+"</Capacidade>\n <bdClima>"+
      response[3][0].Clima+"</bdClima>\n <Altitude>"+
      response[3][0].Altitude+"</Altitude>\n <Caldeirao>"+
      response[3][0].Caldeirao+"</Caldeirao>\n </estadio>\n <clima>\n <ID>"+
      response[4][0].id+"</ID>\n <Nome>"+
      response[4][0].Nome+"</Nome>\n <TempVerao>"+
      response[4][0].TempVerao+"</TempVerao>\n <EstiloVerao>"+
      response[4][0].EstiloVerao+"</EstiloVerao>\n <TempOutono>"+
      response[4][0].TempOutono+"</TempOutono>\n <EstiloOutono>"+
      response[4][0].EstiloOutono+"</EstiloOutono>\n <TempInverno>"+
      response[4][0].TempInverno+"</TempInverno>\n <EstiloInverno>"+
      response[4][0].EstiloInverno+"</EstiloInverno>\n <TempPrimavera>"+
      response[4][0].TempPrimavera+"</TempPrimavera>\n <EstiloPrimavera>"+
      response[4][0].EstiloPrimavera+"</EstiloPrimavera>\n <Hemisferio>"+
      response[4][0].Hemisferio+"</Hemisferio>\n </clima>\n <atributosJogador>\n ";
      for(let jogador of response[1]){
        if(jogador.StringPosicoes[0] == "0"){
         xmlData += "<atributosJogador>\n <Jogador>" +
         jogador.idJogador + "</Jogador>\n <Marcacao>" +
         jogador.Marcacao +  "</Marcacao>\n <Desarme>" +
         jogador.Desarme +  "</Desarme>\n <VisaoJogo>" +
         jogador.VisaoJogo +  "</VisaoJogo>\n <Movimentacao>" +
         jogador.Movimentacao +  "</Movimentacao>\n <Cruzamentos>" +
         jogador.Cruzamentos +  "</Cruzamentos>\n <Cabeceamento>" +
         jogador.Cabeceamento +  "</Cabeceamento>\n <Tecnica>" +
         jogador.Tecnica +  "</Tecnica>\n <ControleBola>" +
         jogador.ControleBola +  "</ControleBola>\n <Finalizacao>" +
         jogador.Finalizacao +  "</Finalizacao>\n <FaroGol>" +
         jogador.FaroGol +  "</FaroGol>\n <Velocidade>" +
         jogador.Velocidade +  "</Velocidade>\n <Forca>" +
         jogador.Forca +  "</Forca>\n <Determinacao>" +
         0 +  "</Determinacao>\n <DeterminacaoOriginal>" +
         jogador.DeterminacaoOriginal +  "</DeterminacaoOriginal>\n <CondicaoFisica>" +
         "100.0"+  "</CondicaoFisica>\n <modificador>" +
         "1.0" +  "</modificador>\n </atributosJogador>\n";
        }
      }

      xmlData += "</atributosJogador>\n <atributosGoleiro>\n ";
      for(let jogador of response[1]){
        if(jogador.StringPosicoes[0] == "1"){
         xmlData += "<atributosGoleiro>\n <Goleiro>" +
         jogador.idJogador + "</Goleiro>\n <Reflexos>" +
         jogador.Reflexos +  "</Reflexos>\n <Seguranca>" +
         jogador.Seguranca +  "</Seguranca>\n <Saidas>" +
         jogador.Saidas +  "</Saidas>\n <JogoAereo>" +
         jogador.JogoAereo +  "</JogoAereo>\n <Lancamentos>" +
         jogador.Lancamentos +  "</Lancamentos>\n <DefesaPenaltis>" +
         jogador.DefesaPenaltis +  "</DefesaPenaltis>\n <Determinacao>" +
         0 +  "</Determinacao>\n <DeterminacaoOriginal>" +
         jogador.DeterminacaoOriginal +  "</DeterminacaoOriginal>\n <CondicaoFisica>" +
         "100.0"+  "</CondicaoFisica>\n </atributosGoleiro>\n";
        }
      }
      xmlData += "</atributosGoleiro>\n <posicoesJogador>\n ";
      for(let jogador of response[1]){
         xmlData += "<posicoes>\n <Jogador>" +
         jogador.idJogador + "</Jogador>\n <G>" +
         !!+jogador.StringPosicoes[0] +  "</G>\n <LD>" +
         !!+jogador.StringPosicoes[1] +  "</LD>\n <LE>" +
         !!+jogador.StringPosicoes[2] +  "</LE>\n <Z>" +
         !!+jogador.StringPosicoes[3] +  "</Z>\n <AD>" +
         !!+jogador.StringPosicoes[4] +  "</AD>\n <AE>" +
         !!+jogador.StringPosicoes[5] +  "</AE>\n <V>" +
         !!+jogador.StringPosicoes[6] +  "</V>\n <MD>" +
         !!+jogador.StringPosicoes[7] +  "</MD>\n <ME>" +
         !!+jogador.StringPosicoes[8] +  "</ME>\n <MC>" +
         !!+jogador.StringPosicoes[9] +  "</MC>\n <PD>" +
         !!+jogador.StringPosicoes[10] +  "</PD>\n <PE>" +
         !!+jogador.StringPosicoes[11] +  "</PE>\n <MA>" +
         !!+jogador.StringPosicoes[12] +  "</MA>\n <Am>" +
         !!+jogador.StringPosicoes[13] +  "</Am>\n <Aa>" +
         !!+jogador.StringPosicoes[14] +  "</Aa>\n </posicoes>\n";
      }

      // recuperacao de imagens

      xmlData += "</posicoesJogador>\n <escudoBase64>\n"+
      response[5]+"</escudoBase64>\n <uniforme1Base64>\n"+
      response[6]+"</uniforme1Base64>\n <uniforme2Base64>"+
      response[7]+"</uniforme2Base64>\n <formatoEscudoBase64>"+
      (response[0][0].Escudo).slice(((response[0][0].Escudo).lastIndexOf(".") - 1 >>> 0) + 2)+"</formatoEscudoBase64>\n <formatoUniforme1Base64>"+
      (response[0][0].Uniforme1).slice(((response[0][0].Uniforme1).lastIndexOf(".") - 1 >>> 0) + 2)+"</formatoUniforme1Base64>\n <formatoUniforme2Base64>"+
      (response[0][0].Uniforme2).slice(((response[0][0].Uniforme2).lastIndexOf(".") - 1 >>> 0) + 2)+"</formatoUniforme2Base64>\n "+
      "</clubeExportado>";

      var fileName = response[0][0].Nome+".ymt";

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


     $('.editar').click(function(){
    var tbl_row =  $(this).closest('tr');
    tbl_row.find('span').each(function(index, val){
        $(this).attr('original_entry', $(this).html());

    });
    tbl_row.find('.linkNome').css("cursor","text");
    tbl_row.find('.linkNome').css("pointer-events","none");
    tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
    tbl_row.find('.salvar').show();
    tbl_row.find('.cancelar').show();
    tbl_row.find('.editar').hide();
    tbl_row.find('.exportar').hide();
    tbl_row.find('.deletar').hide();
    tbl_row.find('.nomePais').hide();
    tbl_row.find('.hiddenInput').show();

        //acertar questão cores
        tbl_row.find(".celula-uniforme :input").each(function(){
            var rgb = $(this).closest('.quadrado-uniforme').attr('id');
            var rgbp = rgb.match(/.{1,3}/g);
            var hex = rgbToHex(rgbp);
            $(this).val(hex);
        });

        //console.log(tbl_row.find(".celula-uniforme :input"));

    tbl_row.find('.thumb').addClass('editableThumb');

    var paisId = tbl_row.find('.comboPais').attr('id');
    tbl_row.find('.comboPais').show().val(paisId);



    var estadioId = tbl_row.find('.comboEstadio').attr('id').replace(/\D/g,'');

    tbl_row.find('.comboEstadio').show().val(estadioId);

});

    $('.cancelar').click(function(){
    var tbl_row =  $(this).closest('tr');
    tbl_row.find('.linkNome').css("cursor","pointer");
    tbl_row.find('.linkNome').css("pointer-events","auto");

    tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
    tbl_row.find('.comboPais').hide();

    tbl_row.find('.comboEstadio').hide();
    tbl_row.find('.nomePais').show();
    tbl_row.find('.salvar').hide();
    tbl_row.find('.cancelar').hide();
    tbl_row.find('.editar').show();
    tbl_row.find('.exportar').show();
    tbl_row.find('.deletar').show();
    tbl_row.find('.thumb').removeClass('editableThumb');
    tbl_row.find('.hiddenInput').hide();

    tbl_row.find('span').each(function(index, val){
        $(this).html($(this).attr('original_entry'));
    });
});



$('.salvar').click(function(){
    var tbl_row =  $(this).closest('tr');
    tbl_row.find('.linkNome').css("cursor","pointer");
    tbl_row.find('.linkNome').css("pointer-events","auto");

    tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
    tbl_row.find('.comboPais').hide();

    tbl_row.find('.comboEstadio').hide();
    tbl_row.find('.nomePais').show();
    tbl_row.find('.salvar').hide();
    tbl_row.find('.cancelar').hide();
    tbl_row.find('.editar').show();
    tbl_row.find('.exportar').show();
    tbl_row.find('.deletar').show();
    tbl_row.find('.thumb').removeClass('editableThumb');
    tbl_row.find('.hiddenInput').hide();

    var id = tbl_row.attr('id');
    var nomeTime = tbl_row.find('#nom'+id).html();
    var maxTorcedores = tbl_row.find('#max'+id).html();
    var fidelidade = tbl_row.find('#fid'+id).html();
    var estadio = tbl_row.find('.comboEstadio').val();
    var liga = -1;
    var pais = tbl_row.find('.comboPais').val();

    //cores1
    var uni1cor1hex = tbl_row.find('[name=u1c1]').val();
    var uni1cor2hex = tbl_row.find('[name=u1c2]').val();
    var uni1cor3hex = tbl_row.find('[name=u1c3]').val();

    var uni1cor1 = hexToRgb(uni1cor1hex);
    var uni1cor2 = hexToRgb(uni1cor2hex);
    var uni1cor3 = hexToRgb(uni1cor3hex);

    //cores2
    var uni2cor1hex = tbl_row.find('[name=u2c1]').val();
    var uni2cor2hex = tbl_row.find('[name=u2c2]').val();
    var uni2cor3hex = tbl_row.find('[name=u2c3]').val();

    var uni2cor1 = hexToRgb(uni2cor1hex);
    var uni2cor2 = hexToRgb(uni2cor2hex);
    var uni2cor3 = hexToRgb(uni2cor3hex);

    //escudo
    var inputEscudo = (tbl_row.find('#escudo'+id))[0];
    var escudo;

    if (inputEscudo.files.length > 0) {
       escudo = inputEscudo.files[0];
    } else {
       escudo = null;
    }

    //uniforme 1
    var inputUni1 = (tbl_row.find('#uni1'+id))[0];
    var uni1;

    if (inputUni1.files.length > 0) {
        uni1 = inputUni1.files[0];
    } else {
        uni1 = null;
    }

    //uniforme 2
    var inputUni2 = (tbl_row.find('#uni2'+id))[0];
    var uni2;

    if (inputUni2.files.length > 0) {
        uni2 = inputUni2.files[0];
    } else {
        uni2 = null;
    }

    var formData = new FormData();
    formData.append('id', id);
    formData.append('nomeTime', nomeTime);
    formData.append('maxTorcedores', maxTorcedores);
    formData.append('fidelidade', fidelidade);
    formData.append('pais', pais);
    formData.append('estadio', estadio);
    formData.append('liga', liga);
     if(escudo != null){
        formData.append('escudo', escudo);
     }
     if(uni1 != null){
        formData.append('uni1', uni1);
     }
     if(uni2 != null){
        formData.append('uni2', uni2);
     }
    formData.append('uni1cor1', uni1cor1);
    formData.append('uni1cor2', uni1cor2);
    formData.append('uni1cor3', uni1cor3);
    formData.append('uni2cor1', uni2cor1);
    formData.append('uni2cor2', uni2cor2);
    formData.append('uni2cor3', uni2cor3);


for (var key of formData.entries()) {
     console.log(key[0] + ', ' + key[1]);
 }

     $.ajax({
         url: 'alterar_time.php',
         processData: false,
        contentType: false,
        cache: false,
        type: "POST",
        dataType: 'json',
         data: formData,
              success: function(data) {
                  if(data.error != ''){
                    console.log(data.error)
                  } else {
                    location.reload();
                  }

              },
              error: function(data) {
                  successmessage = 'Error';
                  alert("Erro, o procedimento não foi realizado, tente novamente.");
                  //location.reload();
              }
          });
});

 });


</script>

<script>

var hexDigits = new Array
        ("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f");

function rgbToHex(rgb) {
    return "#" + hex(rgb[0]) + hex(rgb[1]) + hex(rgb[2]);
}

function hex(x) {
  return isNaN(x) ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x % 16];
 }



//alert( rgbToHex(0, 51, 255) ); // #0033ff

function hexToRgb(hex) {
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ?
        parseInt(result[1], 16).toString().padStart(3,'0').concat(parseInt(result[2], 16).toString().padStart(3,'0'),parseInt(result[3], 16).toString().padStart(3,'0'))
     : null;
}

</script>


<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
