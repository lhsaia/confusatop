<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 15;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

$idPais = $_GET['country'] ?? 0;

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/federacoes.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$jogador = new Jogador($db);
$time = new Time($db);
$liga = new Liga($db);
$federacao = new Federacao($db);

// query paises
$stmt = $pais->readInfo($idPais);
$info = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
$moreInfo = $pais->readMoreInfo($idPais);
$nome_selecao = $info['nome'] ?? '';
$federacao_id = $info['federacao'] ?? 0;
$pontos = $info['pontos'] ?? 0;
$bandeira = $info['bandeira'] ?? '';
$ativo = (!empty($info['ativo'])) ? 'ativo' : 'inativo';

//query federacao
$stmt = $federacao->selFederacao($federacao_id);
$info = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
$federacao_selecao = $info['nome'] ?? '';

//outras informações para infoblock
$mediaIdade = number_format($moreInfo['mediaIdade'] ?? 0, 1);
$estrangeiros = $moreInfo['estrangeiros'] ?? 0;
$valor_total_clube = number_format(($moreInfo['valorTotal'] ?? 0)/1000000000, 2) . "B";
$jogadores = $moreInfo['jogadores'] ?? 0;

$page_title = "Ligas - ".$nome_selecao;
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'ligas_status_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
?>

<iframe id="results_sheet" hidden></iframe>

<main class="propostas-container" style="padding-top: 80px; padding-bottom: 60px;">
<div class="propostas-card">
    <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
        <img id="bandeiraGrande" src="/images/bandeiras/<?php echo htmlspecialchars($bandeira); ?>" style="height: 60px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <div>
            <h2 class="propostas-title" style="margin: 0; text-align: left;"><?php echo htmlspecialchars($nome_selecao); ?></h2>
            <h3 style="margin: 4px 0 0 0; font-size: 1rem;"><a href="geral.php?fed=g<?php echo $federacao_id; ?>" style="color: #0284c7; text-decoration: none; font-weight: 600;"><?php echo htmlspecialchars($federacao_selecao); ?></a></h3>
        </div>
    </div>
    
    <hr style="border: none; border-bottom: 1px solid rgba(0,0,0,0.08); margin: 20px 0;">

    <?php
    //query ligas
    $liga_stmt = $liga->readAll($from_record_num,$records_per_page,null,null,$idPais);

    // the page where this paging is used
    $page_url = "leaguestatus.php?country=" . $idPais . "&";

    // count all products in the database to calculate total pages
    $total_rows = $liga->countAll(null,null,$idPais);

    $perc_estrangeiros = $jogadores > 0 ? number_format(($estrangeiros / $jogadores)*100,1)."%" : "0%";
    ?>

    <div id="info-jogos">
        <div class="infoblock" title="Quantidade de ligas">
            <span class="material-symbols-outlined">emoji_events</span>
            <div>
                <span class="informacao"><?php echo $total_rows; ?></span>
                <span style="font-size: 0.75rem; color: #64748b;">Ligas</span>
            </div>
        </div>
        <div class="infoblock" title="Quantidade de times">
            <span class="material-symbols-outlined">shield</span>
            <div>
                <span class="informacao"><?php echo ($moreInfo['clubes'] ?? 0); ?></span>
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
            <span class="material-symbols-outlined">person</span>
            <div>
                <span class="informacao"><?php echo $mediaIdade; ?></span>
                <span style="font-size: 0.75rem; color: #64748b;">Média Idade</span>
            </div>
        </div>
        <div class="infoblock" title="Estrangeiros">
            <span class="material-symbols-outlined">public</span>
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
    </div>

    <hr style="border: none; border-bottom: 1px solid rgba(0,0,0,0.08); margin: 20px 0;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
        <h3 style="font-family: 'Kanit', sans-serif; color: #1e293b; font-size: 1.2rem; margin: 0;">Ligas Disponíveis</h3>
        <div>
            <?php include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php"); ?>
        </div>
    </div>

    <div style="overflow-x: auto; width: 100%;">
        <table id="tabelaElenco" class="table">
            <thead>
                <tr>
                    <th>Liga</th>
                    <th>Nº Jogadores</th>
                    <th>Média Idade</th>
                    <th>Estrangeiros</th>
                    <th>Valor Mercado</th>
                    <th>Média / Jogador</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $liga_stmt->fetch(PDO::FETCH_ASSOC)):
                    extract($row);
                    $idLiga = $row['id'];
                    $info = $liga->readInfo($idLiga);

                    $elencoPorTime = $info['jogadores'];
                    $mediaIdadePorTime = number_format($info['mediaIdade'],1);
                    $estrangeirosPorTime = $info['estrangeiros'];
                    $valorMercadoPorTime = "F$ ". number_format(($info['valorTotal']/1000000),2)."M";
                    $valorMedioJogador = "F$ ". number_format(($info['valorTotal']/($elencoPorTime*1000000 + 0.0000000001)),2)."M";
                ?>
                    <tr>
                        <td class="cell-liga">
                            <img class="logoliga" src="/images/ligas/<?php echo htmlspecialchars($logo); ?>" height="30px"/>
                            <a href="leaguestatus.php?league=<?php echo $idLiga; ?>" style="color: #0284c7; text-decoration: none; font-weight: 600;"><?php echo htmlspecialchars($row['nome']); ?></a>
                        </td>
                        <td data-label="Nº Jogadores">
                            <span class="cell-value"><?php echo $elencoPorTime; ?></span>
                        </td>
                        <td data-label="Média Idade">
                            <span class="cell-value"><?php echo $mediaIdadePorTime; ?></span>
                        </td>
                        <td data-label="Estrangeiros">
                            <span class="cell-value"><?php echo $estrangeirosPorTime; ?></span>
                        </td>
                        <td data-label="Valor Mercado">
                            <span class="cell-value"><?php echo $valorMercadoPorTime; ?></span>
                        </td>
                        <td data-label="Média / Jogador">
                            <span class="cell-value"><?php echo $valorMedioJogador; ?></span>
                        </td>
                        <td data-label="Ações">
                            <span class="cell-value">
                                <a title="Baixar times para Kitbasher" id="ktb<?php echo $idLiga; ?>" class="clickable exportarKitbasher" style="cursor: pointer;">
                                    <span class="material-symbols-outlined inlineButton azul">checkroom</span>
                                </a>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 30px;">
        <a href="index.php" style="display: inline-block; padding: 10px 20px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;"
           onmouseover="this.style.background='rgba(0, 0, 0, 0.06)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.03)'">
            ← Voltar para Federações
        </a>
    </div>
</div>
</main>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>

<script>

     $(document).ready(function() {

        $('.exportarFootscore').click(function(){
            var idLiga = $(this).attr("id").replace(/\D/g,'');
            //window.location.href = "exportar_planilha.php?idPais="+ idPais;

            var formData = new FormData();
            formData.append('codigo_liga', idLiga);

            $.ajax({
                url: '/export/export_footscore.php',
                processData: false,
               contentType: false,
               cache: false,
               type: "POST",
               dataType: 'json',
                data: formData,
                     success: function(data) {
                         document.getElementById("results_sheet").src = data.filename;
                         //location.reload();
                     },
                     error: function(data) {
                         successmessage = 'Error';
                         alert("Erro na execução da solicitação");
                         //location.reload();
                     }
                 }).fail(function(jqXHR, textStatus, errorThrown ){
                     console.log("Erro");
                     console.log(jqXHR);
                     console.log(textStatus);
                     console.log(errorThrown);
                 });
        });
		
		
		        $('.exportarKitbasher').click(function(){
            var idLiga = $(this).attr("id").replace(/\D/g,'');
            //window.location.href = "exportar_planilha.php?idPais="+ idPais;

            var formData = new FormData();
            formData.append('codigo_liga', idLiga);
			
			console.log(idLiga);

            $.ajax({
                url: '/export/export_kitbasher.php',
                processData: false,
               contentType: false,
               cache: false,
               type: "POST",
               dataType: 'json',
                data: formData,
                     success: function(data) {
                         document.getElementById("results_sheet").src = data.filename;
                         //location.reload();
                     },
                     error: function(data) {
                         successmessage = 'Error';
                         alert("Erro na execução da solicitação");
                         //location.reload();
                     }
                 }).fail(function(jqXHR, textStatus, errorThrown ){
                     console.log("Erro");
                     console.log(jqXHR);
                     console.log(textStatus);
                     console.log(errorThrown);
                 });
        });
        
     });
    
    
</script>
