<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Minhas propostas de técnicos - ".($_SESSION['nomereal'] ?? '');
$css_filename = "home_redesign";
$aux_css = "propostas_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");


if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>

<main class="propostas-container">
    <div id='errorbox'></div>

    <div class="propostas-card">
        <h2 class="propostas-title">Quadro de propostas de técnicos - <?php echo $_SESSION['nomereal']?></h2>

<?php

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 18;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$time = new Time($db);
$pais = new Pais($db);
$tecnico = new Tecnico($db);

//query
$stmt = $tecnico->lerPropostasPendentes($_SESSION['user_id'],$from_record_num,$records_per_page);

$num = $stmt->rowCount();

// the page where this paging is used
$page_url = "minhaspropostastecnicos.php?";

    // count all products in the database to calculate total pages
    $total_rows = $tecnico->contarPropostas($_SESSION['user_id']);


    // paging buttons here
    echo "<div style='clear:both;'></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");

echo "<hr>";

// display the products if there are any
if($num>0){
    echo "<div id='tabelaRecebidas'>";
    echo "<table id='tabelaPrincipal' class='table'>";
    echo "<thead>";
        echo "<tr>";
            echo "<th></th>";
            echo "<th>Técnico</th>";
            echo "<th>Nivel</th>";
            echo "<th>Clube Origem</th>";
            echo "<th>Clube Destino</th>";
            echo "<th>Data Prop.</th>";
            echo "<th>Data Concl.</th>";
            echo "<th>Mensagens</th>";
            echo "<th>Opções</th>";

        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            $formattedDataProp = date("d/m/Y H:i", strtotime($data));
            $formattedDataConcl = empty($dataConclusao) ? "" : date("d/m/Y H:i", strtotime($dataConclusao));

            $mensagensArr = [];
            if (!empty($mensagens)) {
                $decoded = json_decode($mensagens, true);
                if (is_array($decoded)) {
                    $mensagensArr = $decoded;
                }
            }
            $totalMensagens = count($mensagensArr);
            $mensagensJsonAttr = htmlspecialchars(json_encode($mensagensArr), ENT_QUOTES, 'UTF-8');

            echo "<tr id='".$idTransferencia."' class='tipo".$status_execucao."' data-tecnico='".htmlspecialchars($nomeJogador, ENT_QUOTES)."'>";
                echo "<td><img src='/images/icons/".$direcao.".png' width='30px' height='30px'/></td>";
                echo "<td data-label='Técnico'><a href='/ligas/coachstatus.php?coach={$idJogador}' style='color:#0284c7; text-decoration:none; font-weight:600;'><span class='nomeEditavel'>{$nomeJogador}</span></a></td>";
                echo "<td data-label='Nível'><span class='nomeEditavel'>{$nivelJogador}</span></td>";
                echo "<td data-label='Origem'><img class='thumb' src='/images/escudos/".$escudoOrigem . "' /><span class='nomeEditavel'>{$clubeOrigem}</span></td>";
                echo "<td data-label='Destino'><img class='thumb' src='/images/escudos/".$escudoDestino . "' /><span class='nomeEditavel'>{$clubeDestino}</span></td>";
                echo "<td data-label='Data Prop.'><span class='nomeEditavel'>{$formattedDataProp}</span></td>";
                echo "<td data-label='Data Concl.'><span class='nomeEditavel'>{$formattedDataConcl}</span></td>";
                
                // Coluna de Mensagens
                echo "<td data-label='Mensagens'>";
                if ($totalMensagens > 0) {
                    echo "<button type='button' class='btn-ver-mensagens' data-mensagens='".$mensagensJsonAttr."' data-tecnico='".htmlspecialchars($nomeJogador, ENT_QUOTES)."' title='Ver histórico de mensagens'>";
                    echo "<span class='material-symbols-outlined' style='font-size:18px;'>chat</span> <span>{$totalMensagens}</span>";
                    echo "</button>";
                } else {
                    echo "<span style='color:#94a3b8; font-size:0.85rem;'>-</span>";
                }
                echo "</td>";

                $optionsString = "<td class='wide' data-label='Opções'>";

                if($direcao == 'inbox' && $status_execucao == 0){
                    $optionsString .= "<a id='acc".$idJogador."' title='Aceitar' class='clickable aceitar'><span class='material-symbols-outlined inlineButton positivo'>check_circle</span></a>";
                    $optionsString .= "<a id='rec".$idJogador."' title='Recusar' class='clickable recusar'><span class='material-symbols-outlined inlineButton negativo'>cancel</span></a>";
                } else if($direcao == 'outbox' && $status_execucao == 0){
                    $optionsString .= "<a id='rec".$idJogador."' title='Cancelar proposta' class='clickable recusar'><span class='material-symbols-outlined inlineButton negativo'>cancel</span></a>";
                }
                    $optionsString .= "</td>";
                    echo $optionsString;


                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";
    echo "</div>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há propostas</div>";
}

echo('</div>');
echo('</main>');

?>

<!-- Modal de Histórico de Mensagens / Chat -->
<div id="modalChatMensagens" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.6); overflow-y:auto; padding-top:60px;">
  <div class="modal-content animate larger" style="background-color:#ffffff; border: 1px solid rgba(0, 0, 0, 0.1); color:#0f172a; border-radius:14px; padding:24px; max-width:560px; margin:0 auto; box-shadow:0 12px 32px rgba(0,0,0,0.15);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">
      <h3 id="modalChatTitulo" style="margin:0; font-family:'Outfit',sans-serif; color:#0284c7; font-size:1.2rem; display:flex; align-items:center; gap:6px;">
        <span class="material-symbols-outlined">forum</span> Mensagens da Transferência
      </h3>
      <button type="button" onclick="$('#modalChatMensagens').hide();" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer;">&times;</button>
    </div>
    
    <div id="modalChatCorpo" class="chat-timeline-container">
      <!-- Inserido dinamicamente via JS -->
    </div>
    
    <div style="margin-top:20px; text-align:right;">
      <button type="button" onclick="$('#modalChatMensagens').hide();" style="padding:8px 18px; border-radius:8px; border:1px solid #cbd5e1; background:#f8fafc; color:#334155; font-weight:600; cursor:pointer;">Fechar</button>
    </div>
  </div>
</div>

<!-- Modal de Recusa com Mensagem -->
<div id="modalRecusa" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.6); overflow-y:auto; padding-top:60px;">
  <form id="formRecusa" class="modal-content animate larger" style="background-color:#ffffff; border: 1px solid rgba(0, 0, 0, 0.1); color:#0f172a; border-radius:14px; padding:24px; max-width:500px; margin:0 auto; box-shadow:0 12px 32px rgba(0,0,0,0.15);">
    <input type="hidden" id="recusaIdTransferencia" name="idTransferencia" value="">
    <h3 style="margin-top:0; font-family:'Outfit',sans-serif; color:#ef4444; font-size:1.2rem; display:flex; align-items:center; gap:6px;">
      <span class="material-symbols-outlined">cancel</span> Recusar / Cancelar Proposta
    </h3>
    <p id="recusaDescricao" style="font-size:0.9rem; color:#64748b; margin-bottom:15px;">Tem certeza que deseja recusar esta proposta?</p>
    
    <div style="margin-bottom:20px;">
      <label for="recusaMensagem" style="display:block; margin-bottom:6px; font-weight:bold; font-size:0.85rem; color:#334155;">Motivo / Mensagem ao proponente (opcional):</label>
      <textarea id="recusaMensagem" name="mensagem" class="form-control" rows="3" maxlength="500" placeholder="Ex: O técnico possui contrato em andamento e faz parte do nosso planejamento..." style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1; box-sizing:border-box;"></textarea>
    </div>
    
    <div style="display:flex; justify-content:flex-end; gap:10px;">
      <button type="button" onclick="$('#modalRecusa').hide();" style="padding:8px 16px; border-radius:8px; border:1px solid #cbd5e1; background:#f8fafc; color:#334155; font-weight:600; cursor:pointer;">Voltar</button>
      <button type="submit" style="padding:8px 18px; border-radius:8px; border:none; background:#ef4444; color:#ffffff; font-weight:600; cursor:pointer;">Confirmar Recusa</button>
    </div>
  </form>
</div>

<script>
// Visualizar histórico de mensagens
$(document).on("click", ".btn-ver-mensagens", function(){
    var msgsRaw = $(this).attr("data-mensagens");
    var tecnicoNome = $(this).attr("data-tecnico");
    var msgs = [];
    try {
        msgs = JSON.parse(msgsRaw);
    } catch(e) {
        msgs = [];
    }

    $("#modalChatTitulo").html("<span class='material-symbols-outlined'>forum</span> Histórico: " + tecnicoNome);
    var html = "";
    if (msgs.length === 0) {
        html = "<p style='color:#64748b; text-align:center;'>Nenhuma mensagem registrada.</p>";
    } else {
        $.each(msgs, function(index, msg){
            var tipoClass = msg.tipo || 'proposta';
            var tipoNome = (tipoClass === 'contrapropor' ? 'Contraproposta' : (tipoClass === 'recusar' ? 'Recusa' : (tipoClass === 'aceitar' ? 'Aceite' : 'Proposta')));
            var dataFmt = msg.data || '';
            
            html += "<div class='chat-bubble " + tipoClass + "'>";
            html += "  <div class='chat-header'>";
            html += "    <span class='chat-author'>" + (msg.remetente || 'Usuário') + "</span>";
            html += "    <span><span class='chat-badge " + tipoClass + "'>" + tipoNome + "</span> <small style='margin-left:6px;'>" + dataFmt + "</small></span>";
            html += "  </div>";
            html += "  <div class='chat-text'>" + (msg.texto || '') + "</div>";
            html += "</div>";
        });
    }
    $("#modalChatCorpo").html(html);
    $("#modalChatMensagens").show();
});

// Modal de Recusa
$(".recusar").click(function(){
    var row = $(this).closest('tr');
    var idTransferencia = row.attr("id");
    var tecnico = row.attr("data-tecnico");
    
    $("#recusaIdTransferencia").val(idTransferencia);
    $("#recusaDescricao").text("Deseja confirmar a recusa/cancelamento da proposta para o técnico " + tecnico + "?");
    $("#recusaMensagem").val("");
    $("#modalRecusa").show();
});

$("#formRecusa").submit(function(e){
    e.preventDefault();
    var formData = {
        "idTransferencia": $("#recusaIdTransferencia").val(),
        "acao": 'recusar',
        "mensagem": $("#recusaMensagem").val()
    };
    
    $.ajax({
        type: "POST",
        url: '/ligas/avaliar_proposta_tecnico.php',
        data: formData,
        dataType: 'json',
        success: function(data) {
            $("#modalRecusa").hide();
            if (data.success) {
                location.reload();
            } else {
                alert("Erro ao recusar: " + (data.error || "Tente novamente."));
            }
        },
        error: function() {
            alert("Erro de comunicação ao recusar proposta.");
        }
    });
});

// Aceitar
$(".aceitar").click(function(){
    var idTransferencia = $(this).closest('tr').attr("id");
    var r = confirm("Você tem certeza que deseja aceitar essa transferência?");
    var formData = {
        "idTransferencia" : idTransferencia,
        "acao" : 'aceitar'
    };
    if (r) {
        $.ajax({
            type: "POST",
            url: '/ligas/avaliar_proposta_tecnico.php',
            data: formData,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert("Erro ao aceitar: " + (data.error || "Tente novamente."));
                }
            },
            error: function() {
                alert("Erro, o procedimento não foi realizado, tente novamente.");
            }
        });
    }
});



</script>



<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
