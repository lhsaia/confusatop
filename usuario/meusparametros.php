<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus Parâmetros HYMT - ".($_SESSION['nomereal'] ?? '');
$css_filename = "home_redesign";
$aux_css = "home_redesign";
$extra_css = "meusparametros_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/parametros.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

    $database = new Database();
    $db = $database->getConnection();

    $parametro = new Parametro($db);
    $pais = new Pais($db);

    $opcoes = $parametro->coletarOpcoes($_SESSION['user_id']);

    if($opcoes->rowCount() <> 0 ){
        $opcoesResult = $opcoes->fetch(PDO::FETCH_ASSOC);
        $mostrarSumula = ($opcoesResult['mostrarSumula'] == 0 ? '' : 'checked');
        $VAR = ($opcoesResult['videoAr'] == 0 ? '' : 'checked');
        $limitarLesoes = ($opcoesResult['limitarLesoes'] == 0 ? '' : 'checked');
        $porTempo = $opcoesResult['porTempo'];
        $porData = $opcoesResult['porData'];
    } else {
        $mostrarSumula = 'checked';
        $VAR = 'checked';
        $limitarLesoes = '';
        $porTempo = 180;
        $porData = date('Y-m-d');
    }
?>

<main class="propostas-container">
    <div class="propostas-card">
        <div class="propostas-header">
            <h2 class="propostas-title">
                <span>⚙️ Parâmetros HYMT</span>
            </h2>
            <div class="header-actions-container">
                <a href="/usuario/criar_parametros.php" class="btn-header-action">
                    <span class="material-symbols-outlined" style="font-size: 18px;">add_circle</span> Criar Parâmetros
                </a>
                <a href="/usuario/index.php" class="btn-voltar">
                    <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span> Voltar
                </a>
            </div>
        </div>

        <div id="errorbox"></div>

        <!-- Opções Gerais -->
        <div class="opcoes-gerais-card">
            <h3 class="opcoes-gerais-title">
                <span class="material-symbols-outlined" style="font-size: 20px;">tune</span> Opções Gerais de Partida
            </h3>
            <div class="opcoes-grid">
                <div class="opcao-item">
                    <label class="opcao-checkbox-label">
                        <input id="checkboxSumulas" type="checkbox" <?php echo $mostrarSumula; ?> />
                        <span>Mostrar súmula</span>
                    </label>
                </div>
                <div class="opcao-item">
                    <label class="opcao-checkbox-label">
                        <input id="checkboxVAR" type="checkbox" <?php echo $VAR; ?> />
                        <span>Utilizar VAR</span>
                    </label>
                </div>
                <div class="opcao-item">
                    <label class="opcao-checkbox-label">
                        <input id="checkboxLesoes" type="checkbox" <?php echo $limitarLesoes; ?> />
                        <span>Limitar lesões</span>
                    </label>
                </div>
                <div class="opcao-item">
                    <label for="inputTempoLesao">Tempo (dias)</label>
                    <input min="1" max="365" value="<?php echo $porTempo; ?>" type="number" id="inputTempoLesao" />
                </div>
                <div class="opcao-item">
                    <label for="inputDataLesao">Data limite</label>
                    <input value="<?php echo $porData; ?>" type="date" id="inputDataLesao" />
                </div>
                <div class="opcao-item">
                    <button type="button" id="alterarOpcoes" class="btn-salvar-opcoes">
                        <span class="material-symbols-outlined" style="font-size: 18px;">save</span> Salvar Opções
                    </button>
                </div>
            </div>
        </div>

        <?php
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $records_per_page = 18;
        $from_record_num = ($records_per_page * $page) - $records_per_page;

        $stmtPais = $pais->read($_SESSION['user_id']);
        $listaPaises = array();
        while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
            extract($row_pais);
            $listaPaises[] = array($id, $nome);
        }

        $stmt = $parametro->readAll($from_record_num, $records_per_page, $_SESSION['user_id']);
        $num = $stmt->rowCount();
        $page_url = "meusparametros.php?";
        $total_rows = $parametro->countAll($_SESSION['user_id']);

        if($num > 0){
        ?>
            <div class="tbl_user_data">
                <table id="tabelaPrincipal" class="table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Nome</th>
                            <th style="width: 10%;">Gols</th>
                            <th style="width: 10%;">Faltas</th>
                            <th style="width: 10%;">Impedimentos</th>
                            <th style="width: 10%;">Cartões</th>
                            <th style="width: 14%;">Estilo</th>
                            <th style="width: 8%; text-align: center;">Padrão</th>
                            <th style="width: 12%;">País padrão</th>
                            <th style="width: 8%; text-align: center;">Bandeiras</th>
                            <th style="width: 80px; text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                            extract($row);

                            $faixaGols = $Gols * 5;
                            $faixaFaltas = $Faltas * 5;
                            $faixaImpedimentos = $Impedimentos * 10;
                            $faixaCartoes = $Cartoes * 10;
                            $faixaEstilo = ($Estilo - 3) * 50;

                            if($faixaEstilo > 0){
                                $faixaEstiloDir = $faixaEstilo;
                                $faixaEstiloEsq = 0;
                            } else if($faixaEstilo < 0){
                                $faixaEstiloEsq = ($faixaEstilo) * -1;
                                $faixaEstiloDir = 0;
                            } else {
                                $faixaEstiloEsq = 5;
                                $faixaEstiloDir = 5;
                            }

                            echo "<tr id='".$ID."'>";
                                echo "<td><span class='nomeEditavel' id='nom".$ID."'>{$Nome}</span></td>";
                                echo "<td><div class='meter'><span class='meter-bar' style='width: {$faixaGols}%'></span><span class='meter-value' id='gol".$ID."'>{$Gols}</span></div></td>";
                                echo "<td><div class='meter'><span class='meter-bar' style='width: {$faixaFaltas}%'></span><span class='meter-value' id='fal".$ID."'>{$Faltas}</span></div></td>";
                                echo "<td><div class='meter'><span class='meter-bar' style='width: {$faixaImpedimentos}%'></span><span class='meter-value' id='imp".$ID."'>{$Impedimentos}</span></div></td>";
                                echo "<td><div class='meter'><span class='meter-bar' style='width: {$faixaCartoes}%'></span><span class='meter-value' id='car".$ID."'>{$Cartoes}</span></div></td>";
                                echo "<td>
                                    <div class='meter geral-estilo'>
                                        <div class='div-chao'>
                                            <span class='meter-split-left' style='width: {$faixaEstiloEsq}%'></span>
                                            <span class='meter-left'>Chão</span>
                                        </div>
                                        <div class='div-alto'>
                                            <span class='meter-split-right' style='width: {$faixaEstiloDir}%'></span>
                                            <span class='meter-right'>Alto</span>
                                        </div>
                                    </div>
                                    <select class='comboEstilo editavel' id='{$Estilo}' hidden>
                                        <option value='1'>Pelo chão</option>
                                        <option value='2'>Mais pelo chão</option>
                                        <option value='3'>Intermediário</option>
                                        <option value='4'>Mais pelo alto</option>
                                        <option value='5'>Pelo alto</option>
                                    </select>
                                </td>";
                                echo "<td style='text-align: center;'><input class='checkboxSelecionado' type='checkbox' id='sel".$ID."' ". ($Selecionado == 1? 'checked disabled' : 'disabled')."/></td>";
                                echo "<td>";
                                if($PaisPadrao != 0){
                                    echo "<img src='/images/bandeiras/{$bandeira}' class='bandeira nomePais' id='ban".$ID."'> <span class='nomePais' id='pai".$ID."'>{$sigla}</span>";
                                }
                                echo "<select class='comboPais editavel' id='{$PaisPadrao}' hidden>";
                                for($i = 0; $i < count($listaPaises); $i++){
                                    $selected = ($listaPaises[$i][0] == $PaisPadrao ? "selected" : "");
                                    echo "<option value='{$listaPaises[$i][0]}' {$selected}>{$listaPaises[$i][1]}</option>";
                                }
                                echo "</select>";
                                echo "</td>";
                                echo "<td style='text-align: center;'><input class='checkboxBandeiras' type='checkbox' id='exi".$ID."' ". ($ExibirBandeiras == 1? 'checked disabled' : 'disabled')."/></td>";
                                echo "<td style='text-align: center; white-space: nowrap;'>";
                                    echo "<div style='display: flex; gap: 4px; justify-content: center;'>";
                                        echo "<button type='button' id='edi".$ID."' title='Editar' class='btn-action-icon editar'><span class='material-symbols-outlined' style='font-size: 18px;'>edit</span></button>";
                                        echo "<button type='button' hidden id='sal".$ID."' title='Salvar' class='btn-action-icon positive salvar'><span class='material-symbols-outlined' style='font-size: 18px;'>check</span></button>";
                                        echo "<button type='button' hidden id='can".$ID."' title='Cancelar' class='btn-action-icon negative cancelar'><span class='material-symbols-outlined' style='font-size: 18px;'>close</span></button>";
                                    echo "</div>";
                                echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <?php
            include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");
        } else {
            echo "<div class='alert alert-info'>Não há parâmetros personalizados cadastrados.</div>";
        }
        ?>
    </div>
</main>

<script>
$(document).ready(function() {
    $('#checkboxLesoes').on('change', function(){
        var val = this.checked;
        $("#inputTempoLesao").prop("disabled", !val);
        $("#inputDataLesao").prop("disabled", !val);
    });

    if($('#checkboxLesoes').is(':checked')){
        $("#inputTempoLesao").prop("disabled", false);
        $("#inputDataLesao").prop("disabled", false);
    } else {
        $("#inputTempoLesao").prop("disabled", true);
        $("#inputDataLesao").prop("disabled", true);
    }

    $("#alterarOpcoes").on("click", function(e){
        e.preventDefault();
        var sumulas = $("#checkboxSumulas").is(':checked') ? 1 : 0;
        var VAR = $("#checkboxVAR").is(':checked') ? 1 : 0;
        var lesoes = $("#checkboxLesoes").is(':checked') ? 1 : 0;
        var porTempo = $("#inputTempoLesao").val();
        var porData = $("#inputDataLesao").val();

        var formData = {
            'sumulas' : sumulas,
            'lesoes' : lesoes,
            'porTempo' : porTempo,
            'porData' : porData,
            'VAR' : VAR
        };

        $.ajax({
            type: 'POST',
            url: '/usuario/alterar_opcoes.php',
            data: formData,
            dataType: 'json',
            encode: true
        }).done(function(data) {
            if (!data.success) {
                $('#errorbox').html('<div class="alert alert-danger"><span class="closebtn">&times;</span>Não foi possível salvar opções: ' + data.error + '</div>');
            } else {
                $('#errorbox').html('<div class="alert alert-success"><span class="closebtn">&times;</span>Opções salvas com sucesso!</div>');
            }
        }).fail(function() {
            $('#errorbox').html('<div class="alert alert-danger"><span class="closebtn">&times;</span>Erro de comunicação ao salvar opções.</div>');
        });
    });

    // Inline edit handlers
    $('.editar').click(function(){
        var tbl_row = $(this).closest('tr');
        tbl_row.find('span.meter-value, span.nomeEditavel').each(function(){
            $(this).attr('original_entry', $(this).html());
        });

        var checkSelecionado = tbl_row.find('.checkboxSelecionado');
        var checkBandeiras = tbl_row.find('.checkboxBandeiras');
        checkSelecionado.attr('original_entry', checkSelecionado.is(':checked'));
        checkBandeiras.attr('original_entry', checkBandeiras.is(':checked'));

        tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
        tbl_row.find('.salvar').show();
        tbl_row.find('.cancelar').show();
        tbl_row.find('.editar').hide();
        tbl_row.find('.nomePais').hide();
        tbl_row.find('.geral-estilo').hide();
        tbl_row.find('.checkboxSelecionado').removeAttr("disabled");
        tbl_row.find('.checkboxBandeiras').removeAttr("disabled");
        tbl_row.find('.meter-value').each(function(){
            $(this).attr('contenteditable', 'true').addClass('editavel');
        });

        var paisId = tbl_row.find('.comboPais').attr('id');
        tbl_row.find('.comboPais').show().val(paisId);
        var estiloId = tbl_row.find('.comboEstilo').attr('id');
        tbl_row.find('.comboEstilo').show().val(estiloId);
    });

    $('.cancelar').click(function(){
        var tbl_row = $(this).closest('tr');
        tbl_row.find('.checkboxSelecionado').attr("disabled", true);
        tbl_row.find('.checkboxBandeiras').attr("disabled", true);
        tbl_row.find('.meter-value').each(function(){
            $(this).attr('contenteditable', 'false').removeClass('editavel');
        });
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.comboEstilo').hide();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.geral-estilo').show();
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
        
        tbl_row.find('span.meter-value, span.nomeEditavel').each(function(){
            $(this).html($(this).attr('original_entry'));
        });

        tbl_row.find('.checkboxSelecionado').prop("checked", tbl_row.find('.checkboxSelecionado').attr('original_entry') === 'true');
        tbl_row.find('.checkboxBandeiras').prop("checked", tbl_row.find('.checkboxBandeiras').attr('original_entry') === 'true');
    });

    $('.salvar').click(function(){
        var tbl_row = $(this).closest('tr');
        var id = tbl_row.attr('id');
        var nome = tbl_row.find('#nom'+id).text().trim();
        var gols = tbl_row.find('#gol'+id).text().trim();
        var faltas = tbl_row.find('#fal'+id).text().trim();
        var impedimentos = tbl_row.find('#imp'+id).text().trim();
        var cartoes = tbl_row.find('#car'+id).text().trim();
        var estilo = tbl_row.find('.comboEstilo').val();
        var pais = tbl_row.find('.comboPais').val();
        var selecionado = tbl_row.find('.checkboxSelecionado').is(':checked') ? 1 : 0;
        var bandeiras = tbl_row.find('.checkboxBandeiras').is(':checked') ? 1 : 0;

        var formData = {
            'id' : id,
            'nome' : nome,
            'gols' : gols,
            'faltas' : faltas,
            'impedimentos' : impedimentos,
            'cartoes' : cartoes,
            'estilo' : estilo,
            'pais' : pais,
            'selecionado' : selecionado,
            'bandeiras' : bandeiras
        };

        $.ajax({
            type: 'POST',
            url: '/usuario/alterar_parametros.php',
            data: formData,
            dataType: 'json',
            encode: true
        }).done(function(data) {
            if (!data.success) {
                $('#errorbox').html('<div class="alert alert-danger"><span class="closebtn">&times;</span>Houve um erro ao alterar os parâmetros: ' + data.error + '</div>');
            } else {
                location.reload();
            }
        }).fail(function() {
            $('#errorbox').html('<div class="alert alert-danger"><span class="closebtn">&times;</span>Erro de comunicação ao salvar parâmetros.</div>');
        });
    });

    $(document).on('click', '.closebtn', function(){
        $(this).parent().fadeOut(300, function(){ $(this).remove(); });
    });
});
</script>

<?php
} else {
    echo "<main class='propostas-container'><div class='propostas-card'><p>Usuário sem permissão, por favor faça o login.</p></div></main>";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
