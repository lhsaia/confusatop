<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
?>
<!DOCTYPE html>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
require($_SERVER['DOCUMENT_ROOT']."/lib/functions.php");

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 40;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

$idLiga = $_GET['league'] ?? '';

if (empty($idLiga)) {
    header('Location: /index.php');
    exit;
}

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
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'ligas_status_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['user_id']) && $_SESSION['user_id'] === $idDonoPais){     
	$baseLink = "/ligas/teamstatus";
} else {
	$baseLink = "/times/team_presentation";
}
?>

<iframe id="results_sheet" hidden></iframe>

<main class="propostas-container" style="padding-top: 80px; padding-bottom: 60px;">
<div class="propostas-card">
    <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
        <img id="bandeiraGrande" src="/images/ligas/<?php echo htmlspecialchars($logo_liga); ?>" style="height: 60px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <div>
            <h2 class="propostas-title" style="margin: 0; text-align: left;"><?php echo htmlspecialchars($nome_liga); ?></h2>
            <h3 style="margin: 4px 0 0 0; font-size: 1rem;"><a href="paisstatus.php?country=<?php echo $idPais; ?>" style="color: #0284c7; text-decoration: none; font-weight: 600;"><?php echo htmlspecialchars($pais_liga); ?></a> - Tier <?php echo $tier_liga; ?></h3>
        </div>
    </div>
    
    <hr style="border: none; border-bottom: 1px solid rgba(0,0,0,0.08); margin: 20px 0;">

    <?php
    //query jogos time
    $time_stmt = $time->readAll($from_record_num,$records_per_page,null,$idLiga);

    // the page where this paging is used
    $page_url = "leaguestatus.php?league=" . $idLiga . "&";

    // count all products in the database to calculate total pages
    $total_rows = $time->countAll(null,$idLiga);

    $perc_estrangeiros = $jogadores > 0 ? number_format(($estrangeiros / $jogadores)*100,1)."%" : "0%";
    ?>

    <div id="info-jogos">
        <div class="infoblock" title="Quantidade de times">
            <span class="material-symbols-outlined">shield</span>
            <div>
                <span class="informacao"><?php echo $total_rows; ?></span>
                <span style="font-size: 0.75rem; color: #64748b;">Times</span>
            </div>
        </div>
        <div class="infoblock" title="Quantidade de jogadores">
            <span class="material-symbols-outlined">groups</span>
            <div>
                <span class="informacao"><?php echo $jogadores; ?></span>
                <span style="font-size: 0.75rem; color: #64748b;">Jogadores</span>
            </div>
        </div>
        <div class="infoblock" title="Média de idade">
            <span class="material-symbols-outlined">elderly</span>
            <div>
                <span class="informacao"><?php echo $mediaIdade; ?></span>
                <span style="font-size: 0.75rem; color: #64748b;">Média Idade</span>
            </div>
        </div>
        <div class="infoblock" title="Estrangeiros">
            <span class="material-symbols-outlined">globe_location_pin</span>
            <div>
                <span class="informacao"><?php echo $estrangeiros; ?> <span class="informacao micro">(<?php echo $perc_estrangeiros; ?>)</span></span>
                <span style="font-size: 0.75rem; color: #64748b;">Estrangeiros</span>
            </div>
        </div>
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
    
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
        <h3 style="font-family: 'Kanit', sans-serif; color: #1e293b; font-size: 1.2rem; margin: 0;">Times na Liga</h3>
        <div>
            <?php include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php"); ?>
        </div>
    </div>

    <div style="overflow-x: auto; width: 100%;">
        <table id="tabelaElenco" class="table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Uniformes</th>
                    <th>Elenco</th>
                    <th>Média Idade</th>
                    <th>Estrangeiros</th>
                    <th>Nível (geral)</th>
                    <th>Nível (titulares)</th>
                    <th>Valor Mercado</th>
                    <th>Média / Jogador</th>
                    <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && $_SESSION['user_id'] === $idDonoPais): ?>
                        <th class="wide">Opções</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $time_stmt->fetch(PDO::FETCH_ASSOC)):
                    $idTime = $row['ID'];
                    $info = $time->readInfo($idTime);

                    $elencoPorTime = $info['jogadores'];
                    $mediaIdadePorTime = number_format($info['mediaIdade'],1);
                    $estrangeirosPorTime = $info['estrangeiros'];
                    $valorMercadoPorTime = "F$ ". number_format(($info['valorTotal']/1000000),2)."M";
                    $valorMedioJogador = "F$ ". number_format(($elencoPorTime > 0 ? ($info['valorTotal']/($elencoPorTime*1000000)) : 0),2)."M";
                    $escudos = $info['Escudo'];
                    $uniforme1 = $info['Uniforme1'];
                    $uniforme2 = $info['Uniforme2'];
                    $nivel_medio = number_format($info['mediaNivel'], 1);
                    $nivel_medio_onze = number_format($info['mediaNivelOnze'],1);
                ?>
                    <tr id="<?php echo $idTime; ?>" class="<?php echo $idLiga; ?>">
                        <td class="cell-clube">
                            <img class="logoliga" src="/images/escudos/<?php echo htmlspecialchars($escudos); ?>" height="30px"/>
                            <a href="<?php echo $baseLink; ?>.php?team=<?php echo $idTime; ?>" style="color: #0284c7; text-decoration: none; font-weight: 600;"><?php echo htmlspecialchars($row['Nome']); ?></a>
                        </td>
                        <td data-label="Uniformes">
                            <span class="cell-value">
                                <?php if(!empty($uniforme1)): ?>
                                    <img src="/images/uniformes/<?php echo htmlspecialchars($uniforme1); ?>" height="30px" style="margin-right: 5px;"/>
                                <?php endif; ?>
                                <?php if(!empty($uniforme2)): ?>
                                    <img src="/images/uniformes/<?php echo htmlspecialchars($uniforme2); ?>" height="30px"/>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td data-label="Elenco">
                            <span class="cell-value"><?php echo $elencoPorTime; ?></span>
                        </td>
                        <td data-label="Média Idade">
                            <span class="cell-value"><?php echo $mediaIdadePorTime; ?></span>
                        </td>
                        <td data-label="Estrangeiros">
                            <span class="cell-value"><?php echo $estrangeirosPorTime; ?></span>
                        </td>
                        <td data-label="Nível Geral">
                            <span class="cell-value"><?php echo $nivel_medio; ?></span>
                        </td>
                        <td data-label="Nível Titulares">
                            <span class="cell-value"><?php echo $nivel_medio_onze; ?></span>
                        </td>
                        <td data-label="Valor Mercado">
                            <span class="cell-value"><?php echo $valorMercadoPorTime; ?></span>
                        </td>
                        <td data-label="Valor Médio">
                            <span class="cell-value"><?php echo $valorMedioJogador; ?></span>
                        </td>
                        <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true): ?>
                            <td data-label="Opções">
                                <span class="cell-value">
                                    <a id="dow<?php echo $idTime; ?>" title="Baixar arquivo .ymt" class="clickable exportar" style="cursor: pointer; margin-right: 8px;">
                                        <span class="material-symbols-outlined inlineButton azul">download</span>
                                    </a>
                                    <?php if($_SESSION['user_id'] === $idDonoPais): ?>
                                        <a id="mov<?php echo $idTime; ?>" title="Mover" class="clickable mover" style="cursor: pointer; margin-right: 8px;">
                                            <span class="material-symbols-outlined inlineButton azul">swap_vert</span>
                                        </a>
                                        <select id="sel<?php echo $idTime; ?>" title="Selecionar liga" class="selecionar_liga" style="display: none; padding: 4px; border-radius: 4px; margin-right: 8px;">
                                            <?php for($i = 0; $i < count($listaLigas); $i++): ?>
                                                <option value="<?php echo $listaLigas[$i][0]; ?>"><?php echo htmlspecialchars($listaLigas[$i][1]); ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <a style="display: none; cursor: pointer; margin-right: 8px;" id="sal<?php echo $idTime; ?>" title="Salvar" class="clickable salvar">
                                            <span class="material-symbols-outlined inlineButton" style="color: #10b981;">check</span>
                                        </a>
                                        <a style="display: none; cursor: pointer;" id="can<?php echo $idTime; ?>" title="Cancelar" class="clickable cancelar">
                                            <span class="material-symbols-outlined inlineButton vermelho">close</span>
                                        </a>
                                    <?php endif; ?>
                                </span>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 30px;">
        <a href="paisstatus.php?country=<?php echo $idPais; ?>" style="display: inline-block; padding: 10px 20px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;"
           onmouseover="this.style.background='rgba(0, 0, 0, 0.06)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.03)'">
            ← Voltar para País
        </a>
    </div>
</div>
</main>

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
