<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
require($_SERVER['DOCUMENT_ROOT']."/lib/functions.php");

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 40;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

$idLiga = $_GET['league'];

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$jogador = new Jogador($db);
$time = new Time($db);
$tecnico = new Tecnico($db);
$liga = new Liga($db);
$estadio = new Estadio($db);

// query times
$info = $liga->readInfo($idLiga);
$nome_liga = $info['nome'];
$logo_liga = $info['logo'];
$pais_liga = $info['Pais'];
$tier_liga = $info['tier'];
$idPais = $info['idPais'];
$idDonoPais = $info['idDonoPais'];
$sexoLiga = $info['Sexo'];

// query caixa de seleção países desse dono
$stmtPais = $liga->lerPorPais($idPais,$sexoLiga);
$listaLigas = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $nome);
    $listaLigas[] = $addArray;
}

//outras informações para infoblock
$mediaIdade = number_format($info['mediaIdade'],1);
$estrangeiros = $info['estrangeiros'];
$valor_total_clube = number_format($info['valorTotal']/1000000000,2) . "B";
$jogadores = $info['jogadores'];
$nivel_medio = number_format($info['mediaNivel'], 1);
$nivel_medio_onze = number_format($info['mediaNivelOnze'],1);

$page_title = $nome_liga;
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'ligas';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if($_SESSION['user_id'] === $idDonoPais){     
	$baseLink = "/ligas/teamstatus";
} else {
	$baseLink = "/times/team_presentation";
}


echo "<div id='quadro-container'>";
echo "<img id='bandeiraGrande' class='margin-left' src='/images/ligas/".$logo_liga."' height='100' alt='Logo da liga ".$nome_liga."'>" ;
echo "<h2>" . $nome_liga ." </h2>";
echo "<h3><a href='paisstatus.php?country=".$idPais."'>" . $pais_liga ."</a> - Tier ".$tier_liga." </h3> ";
echo "<hr>";

//query jogos time
$time_stmt = $time->readAll($from_record_num,$records_per_page,null,$idLiga);

    // the page where this paging is used
    $page_url = "leaguestatus.php?league=" . $idLiga . "&";

    // count all products in the database to calculate total pages
    $total_rows = $time->countAll(null,$idLiga);

    $perc_estrangeiros = number_format(($estrangeiros / $jogadores)*100,1)."%";

echo "<div id='info-jogos'>";
echo "<div id='times' class='infoblock' title='Quantidade de times'><i class='fas fa-shield-alt'></i><span class='informacao'>{$total_rows}</span></div>";
echo "<div id='jogadores' class='infoblock' title='Quantidade de jogadores'><i class='fas fa-users'></i><span class='informacao'>{$jogadores}</span></div>";
echo "<div id='Idades' class='infoblock' title='Média de idade'><i class='fas fa-male'></i><span class='informacao'>{$mediaIdade}</span></div>";
echo "<div id='Estrangeiros' class='infoblock' title='Estrangeiros'><i class='fas fa-globe'></i><span class='informacao'>{$estrangeiros}</span><span class='informacao micro'>({$perc_estrangeiros})</span></div>";
echo "<div id='Valor' class='infoblock' title='Valor de mercado (em F$)'><i class='fas fa-dollar-sign'></i><span class='informacao menor'>{$valor_total_clube}</span></div>";
echo "<div id='MediaNivel' class='infoblock' title='Média de Nível (titulares/total)'><i class='fas fa-award'></i><span class='informacao mini'> {$nivel_medio_onze}   <span class='informacao mini'> &nbsp {$nivel_medio} </span></span></div>";
echo "</div>";
echo "<br>";

echo "<div style='clear:both; float:center'></div>";
echo "<hr>";
echo "<p align='center'>Times</p>";

    // paging buttons here
    echo "<div style='clear:both; float:center'></div>";
    echo "<div align='center'>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");
    echo "</div>";
echo "<hr>";

// display the products if there are any

echo "<table id='tabelaElenco' class='table'>";
echo "<thead>";
echo "<tr>";
echo "<th>Time</th>";
echo "<th>Elenco</th>";
echo "<th>Média de idade</th>";
echo "<th>Estrangeiros</th>";
echo "<th>Valor de mercado</th>";
echo "<th>Valor médio (por jogador)</th>";
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && $_SESSION['user_id'] === $idDonoPais){
    echo "<th class='wide'>Opções</th>";
}
echo "</tr>";
echo "</thead>";
echo "<tbody>";

$agora = date('Y-m-d');



        while ($row = $time_stmt->fetch(PDO::FETCH_ASSOC)){

            //extract($row);

            $idTime = $row['ID'];
            $info = $time->readInfo($idTime);

            $elencoPorTime = $info['jogadores'];
            $mediaIdadePorTime = number_format($info['mediaIdade'],1);
            $estrangeirosPorTime = $info['estrangeiros'];
            $valorMercadoPorTime = "F$ ". number_format(($info['valorTotal']/1000000),2)."M";
            $valorMedioJogador = "F$ ". number_format(($info['valorTotal']/($elencoPorTime*1000000)),2)."M";
            $escudos = $info['Escudo'];


            echo "<tr id='".$idTime."' class='".$idLiga."'>";
                echo "<td class='nopadding'><img class='logoliga' src='/images/escudos/".$escudos."' height='30px'/><a href='{$baseLink}.php?team=".$idTime."'>{$row['Nome']}</a></td>";
                echo "<td class='nopadding'>{$elencoPorTime}</td>";
                echo "<td class='nopadding'>{$mediaIdadePorTime}</td>";
                echo "<td class='nopadding'>{$estrangeirosPorTime}</td>";
                echo "<td class='nopadding'>{$valorMercadoPorTime}</td>";
                echo "<td class='nopadding'>{$valorMedioJogador}</td>";
                if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
                    
                    echo "<td><a id='dow".$id."' title='Baixar arquivo .ymt' class='clickable exportar'><i class='fas fa-download inlineButton azul'></i></a>";
                    if($_SESSION['user_id'] === $idDonoPais){                    
                    echo "<a id='mov".$id."' title='Mover' class='clickable mover'><i class='fas fa-arrows-alt-v inlineButton azul'></i></a>";
                    echo "<select id='sel".$id."' title='Selecionar liga' class='selecionar_liga' hidden>";
                    for($i = 0; $i < count($listaLigas);$i++){
                        echo "<option value='{$listaLigas[$i][0]}'>{$listaLigas[$i][1]}</option>";
                    }
                    echo "</select>";
                    echo "<a hidden id='sal".$id."' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton'></i></a>";
                    echo "<a hidden id='can".$id."' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton vermelho'></i></a>";
                    echo "";
                    echo "</td>";
                    }
                }
            echo "</tr>";

        }

        echo "</tbody>";




echo "</table>";



echo "</div>";
//echo "</div>";

?>

<script>

        $(document).ready(function() {

$('.mover').click(function(){
    var idLiga = $(this).closest('tr').attr("class");
    var tbl_row =  $(this).closest('tr');

    tbl_row.find('.selecionar_liga').show().val(idLiga);
    tbl_row.find('.salvar').show();
    tbl_row.find('.cancelar').show();
    tbl_row.find('.mover').hide();
    tbl_row.find('.exportar').hide();

});

 $('.cancelar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.mover').show();
        tbl_row.find('.selecionar_liga').hide();
        tbl_row.find('.exportar').show();

    });

    $('.salvar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.mover').show();
        tbl_row.find('.exportar').show();
        tbl_row.find('.selecionar_liga').hide();

        var idTime = $(this).closest('tr').attr("id");
        var idNovaLiga = tbl_row.find('.selecionar_liga').val();

         var formData = new FormData();
         formData.append('idTime', idTime);
         formData.append('idNovaLiga', idNovaLiga);

         $.ajax({
             url: 'mover_time_liga.php',
             processData: false,
            contentType: false,
            cache: false,
            type: "POST",
            dataType: 'json',
             data: formData,
                  success: function(data) {
                      if(data.error != ''){
                        alert(data.error)
                      }
                      location.reload();
                  },
                  error: function(data) {
                      successmessage = 'Error';
                      alert("Erro, o procedimento não foi realizado, tente novamente.");
                  }
              });
     });


     $('.exportar').click(function(){

       var tbl_row =  $(this).closest('tr');
       var idTime = $(this).closest('tr').attr("id");
       

       $.ajax({
         url: 'get_ymt_info.php',
         type: 'POST',
         dataType: 'json',
         data: {idTime: idTime}
       })
       .done(function(response) {
		   
		   console.log(response);

         let arquivoEsc;
         let arquivoUni1;
         let arquivoUni2;

         if(response[5] != ""){
           arquivoEsc = "Escudos/team"+idTime+".png";
         } else {
           arquivoEsc = "null";
         }

         if(response[6] != ""){
           arquivoUni1 = "Uniformes/1-team"+idTime+".png";
         } else {
           arquivoUni1 = "null";
         }

         if(response[7] != ""){
           arquivoUni2 = "Uniformes/2-team"+idTime+".png";
         } else {
           arquivoUni2 = "null";
         }


         var estilo = 1;
         var xmlData = "<clubeExportado>\n <clube>\n <ID>"+
         idTime+"</ID>\n  <Nome>"+
         response[0][0].Nome+"</Nome>\n  <TresLetras>"+
         response[0][0].TresLetras+"</TresLetras>\n  <bdEstadio>"+
         response[0][0].Estadio+"</bdEstadio>\n  <Escudo>"+
         arquivoEsc+"</Escudo>\n <Uni1Cor1>"+
         response[0][0].Uni1Cor1+"</Uni1Cor1>\n <Uni1Cor2>"+
         response[0][0].Uni1Cor2+"</Uni1Cor2>\n <Uni1Cor3>"+
         response[0][0].Uni1Cor3+"</Uni1Cor3>\n <Uniforme1>"+
         arquivoUni1+"</Uniforme1>\n <Uni2Cor1>"+
         response[0][0].Uni2Cor1+"</Uni2Cor1>\n <Uni2Cor2>"+
         response[0][0].Uni2Cor2+"</Uni2Cor2>\n <Uni2Cor3>"+
         response[0][0].Uni2Cor3+"</Uni2Cor3>\n <Uniforme2>"+
         arquivoUni2+"</Uniforme2>\n <MaxTorcedores>"+
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
            "1" +  "</Determinacao>\n <DeterminacaoOriginal>" +
            "1" +  "</DeterminacaoOriginal>\n <CondicaoFisica>" +
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
            '1' +  "</Determinacao>\n <DeterminacaoOriginal>" +
            '1' +  "</DeterminacaoOriginal>\n <CondicaoFisica>" +
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

         let formatoEsc;
         let formatoUni1;
         let formatoUni2;

         if(response[5] != ""){
           formatoEsc = (response[0][0].Escudo).slice(((response[0][0].Escudo).lastIndexOf(".") - 1 >>> 0) + 2);
         } else {
           formatoEsc = "null";
         }

         if(response[6] != ""){
           formatoUni1 = (response[0][0].Uniforme1).slice(((response[0][0].Uniforme1).lastIndexOf(".") - 1 >>> 0) + 2);
         } else {
          formatoUni1 = "null";
         }

         if(response[7] != ""){
           formatoUni2 = (response[0][0].Uniforme2).slice(((response[0][0].Uniforme2).lastIndexOf(".") - 1 >>> 0) + 2);
         } else {
           formatoUni2 = "null";
         }

         xmlData += "</posicoesJogador>\n <escudoBase64>"+
         response[5]+"</escudoBase64>\n <uniforme1Base64>"+
         response[6]+"</uniforme1Base64>\n <uniforme2Base64>"+
         response[7]+"</uniforme2Base64>\n <formatoEscudoBase64>"+
         formatoEsc +"</formatoEscudoBase64>\n <formatoUniforme1Base64>"+
         formatoUni1 +"</formatoUniforme1Base64>\n <formatoUniforme2Base64>"+
         formatoUni2 +"</formatoUniforme2Base64>\n "+
         "</clubeExportado>";

         var fileName = response[0][0].Nome+".ymt";
         
         console.log("here");

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


});
</script>

<?php


include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
