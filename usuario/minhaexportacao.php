<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Exportação de Database HYMT - ".($_SESSION['nomereal'] ?? '');
$css_filename = "home_redesign";
$aux_css = "home_redesign";
$extra_css = "minhaexportacao_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");

    $database = new Database();
    $db = $database->getConnection();

    $liga = new Liga($db);
    $pais = new Pais($db);

    $stmtPais = $pais->read($_SESSION['user_id']);
    $stmtLiga = $liga->read($_SESSION['user_id']);
?>

<main class="propostas-container">
    <div class="propostas-card">
        <div class="propostas-header">
            <h2 class="propostas-title">
                <span>💾 Exportação de Database HYMT</span>
            </h2>
            <div class="header-actions-container">
                <a href="/usuario/index.php" class="btn-voltar">
                    <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span> Voltar
                </a>
            </div>
        </div>

        <div id="errorbox"></div>

        <div class="export-grid">
            <!-- Box Países -->
            <div class="export-box">
                <div class="export-box-header">
                    <h3 class="export-box-title">
                        <span class="material-symbols-outlined" style="color: #0284c7; font-size: 20px;">flag</span> Países
                    </h3>
                    <label for="todosPaises" class="checkbox-toggle-label">
                        <input type="checkbox" id="todosPaises" name="todosPaises" value="todospaises"> Todos os países
                    </label>
                </div>
                <select multiple class="export-multiselect comboPaises" id="paises" name="comboPaises[]">
                    <?php
                    while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
                        extract($row_pais);
                        echo "<option value='{$id}'>{$nome}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Box Ligas -->
            <div class="export-box">
                <div class="export-box-header">
                    <h3 class="export-box-title">
                        <span class="material-symbols-outlined" style="color: #0284c7; font-size: 20px;">emoji_events</span> Ligas
                    </h3>
                    <label for="todasLigas" class="checkbox-toggle-label">
                        <input type="checkbox" id="todasLigas" name="todasLigas" value="todasligas"> Todas as ligas
                    </label>
                </div>
                <select multiple class="export-multiselect comboLigas" id="ligas" name="comboLigas[]">
                    <?php
                    while ($row_liga = $stmtLiga->fetch(PDO::FETCH_ASSOC)){
                        echo "<option pais-liga='{$row_liga['Pais']}' value='{$row_liga['id']}'>{$row_liga['nome']}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Opções do Pacote -->
        <div class="export-option-full">
            <label for="opcoes">Opções de Pacote</label>
            <select class="comboOpcoes form-control" id="opcoes" name="comboOpcoes[]">
                <option value="0">Pacote completo (Hexacolor 2.19.1) com Topdater</option>
                <option value="1">database.db3 e imagens</option>
                <option value="2">Apenas database.db3</option>
                <option value="3">Apenas Topdater</option>
            </select>
        </div>

        <!-- Botão Exportar -->
        <div class="export-actions">
            <button type="button" id="importar_time" class="btn-exportar">
                <span class="material-symbols-outlined" style="font-size: 22px;">download</span> Exportar Database
            </button>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    $('#todosPaises').prop("checked", true);
    $('#todasLigas').prop("checked", true);
    selectCountries(true, true);
    
    function selectCountries(isCheckedLeagues, isCheckedCountries){
        if(isCheckedCountries){
            $('#paises option').prop('selected', true);
            $('#paises').prop('disabled', true);
            $('#paises').trigger("change");
            selectLeagues(isCheckedLeagues, isCheckedCountries);
        } else {
            $('#paises option').prop('selected', false);
            $('#paises').prop('disabled', false);
            $('#paises').trigger("change");
            selectLeagues(isCheckedLeagues, isCheckedCountries);
        }
    }
    
    function selectLeagues(isCheckedLeagues, isCheckedCountries){
        if(isCheckedCountries && isCheckedLeagues){
            $('#ligas option').prop('selected', true);
            $('#ligas').prop('disabled', true);
        } else if(isCheckedLeagues) { 
            $('#ligas').prop('disabled', true);
            var selectedCountries = $('#paises').val() || [];
            $("#ligas > option").each(function() {
                var pais_liga = $(this).attr("pais-liga");
                if(selectedCountries.includes(pais_liga)){
                    $(this).prop('selected', true);
                } else {
                    $(this).prop('selected', false);
                }
            });
        } else {
            $('#ligas option').prop('selected', false);        
            $('#ligas').prop('disabled', false);
        }
    }

    $('#todosPaises').change(function() {
        var isCheckedCountries = this.checked;
        var isCheckedLeagues = $("#todasLigas").prop("checked");
        selectCountries(isCheckedLeagues, isCheckedCountries);
    });
    
    $('#todasLigas').change(function() {
        var isCheckedCountries = $("#todosPaises").prop("checked");
        var isCheckedLeagues = this.checked;
        selectLeagues(isCheckedLeagues, isCheckedCountries);
    });
    
    $('#paises').change(function() {
        var selectedCountries = $(this).val() || [];
        $("#ligas > option").each(function() {
            var pais_liga = $(this).attr("pais-liga");
            if(selectedCountries.includes(pais_liga)){
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        var isCheckedLeagues = $("#todasLigas").prop("checked");
        selectLeagues(isCheckedLeagues, false);
    });

    $(document)
        .ajaxStart(function () {
            $('html, body').css("cursor", "wait");
        })
        .ajaxStop(function () {
            $('html, body').css("cursor", "default");
        });
    
    $("#importar_time").on("click", function(){
        var paisesSelecionados = [];
        $('#paises option:selected').each(function() {
            paisesSelecionados.push($(this).val());
        });

        var ligasSelecionadas = [];
        $('#ligas option:selected').each(function() {
            ligasSelecionadas.push($(this).val());
        });

        var opcaoSelecionada = $('#opcoes').val();
        
        var ligaPais = [];
        $('#ligas option:selected').each(function() {
            var paisSelecionado = $(this).attr("pais-liga");
            var ligaSelecionada = $(this).val();
            ligaPais.push({pais: paisSelecionado, liga: ligaSelecionada});
        });
        
        ligaPais.sort(function (a, b) {
            if (a.pais > b.pais) return 1;
            if (a.pais < b.pais) return -1;
            return 0;
        });
        
        var groupBy = function(xs, key) {
            return xs.reduce(function(rv, x) {
                (rv[x[key]] = rv[x[key]] || []).push(x);
                return rv;
            }, {});
        };

        ligaPais = groupBy(ligaPais, 'pais');

        var formData = {
            'ligasSelecionadas' : ligasSelecionadas
        };
        
        if(paisesSelecionados.length === 0 || ligasSelecionadas.length === 0) {
            $("#errorbox").html("<div class='alert alert-danger'><span class='closebtn'>&times;</span>Nenhuma liga selecionada!</div>");
            return false;
        }
    
        if(opcaoSelecionada != 3){
            $.ajax({
                type: 'POST',
                url: 'verificar_exportacao.php',
                data: formData,
                dataType: 'json',
                encode: true
            }).done(function(response) {
                if(response.success){
                    $("#errorbox").html("<div class='alert alert-success'><span class='closebtn'>&times;</span>Banco de dados verificado, a exportação iniciará em instantes! Aguarde.</div>");
                    $('#importar_time').addClass('disabled');
                    $('html, body').css("cursor", "wait");
                    var optionString = opcaoSelecionada.toString();
                    var urlToOpen = 'exportar_database_imp3.php?data=' + encodeURIComponent(JSON.stringify(ligaPais)) + '&option=' + optionString;
                    if(urlToOpen.length < 2000){
                        window.location = urlToOpen;
                    } else {
                        $("#errorbox").html("<div class='alert alert-danger'><span class='closebtn'>&times;</span>Selecione um número menor de ligas para exportar de uma vez.</div>");
                    }
                } else {
                    $("#errorbox").html("<div class='alert alert-danger'><span class='closebtn'>&times;</span>Banco de dados não pode ser exportado pelos seguintes motivos:<br/>" + response.errors + "</div>");
                }
            }).fail(function() {
                $("#errorbox").html("<div class='alert alert-danger'><span class='closebtn'>&times;</span>Houve um erro não esperado na exportação dos dados, por favor contate o suporte.</div>");
            });
        } else {
            $("#errorbox").html("<div class='alert alert-success'><span class='closebtn'>&times;</span>A exportação iniciará em instantes! Aguarde.</div>");
            $('#importar_time').addClass('disabled');
            $('html, body').css("cursor", "wait");
            var optionString = opcaoSelecionada.toString();
            var urlToOpen = 'exportar_database_imp3.php?data=' + encodeURIComponent(JSON.stringify(ligaPais)) + '&option=' + optionString;
            window.location = urlToOpen;
        }
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
