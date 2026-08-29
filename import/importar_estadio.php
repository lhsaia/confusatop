<?php

header('Content-Type: text/html; charset=utf-8');
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");

$database = new Database();
$db = $database->getConnection();
$estadio = new Estadio($db);
$pais = new Pais($db);
$time = new Time($db);

// declaracoes de parametros
$page_title = "Importar estádio";
$css_filename = "home_redesign";
$aux_css = "arbitros_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
$_SESSION['jogadorTime'] = 6;

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
    echo '<link rel="stylesheet" href="/css/importacao_moderna.css?v=' . $css_versao . '">';

    echo '<main class="propostas-container narrow-container" style="padding-top: 80px; padding-bottom: 60px;">';
    echo '<div class="propostas-card">';
    echo '<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 1rem; width: 100%;">';
    echo '    <h2 class="propostas-title" style="margin: 0; font-family: \'Outfit\', sans-serif; font-size: 1.6rem; color: #1e293b;">📥 Importar estádio</h2>';
    echo '    <a href="/usuario/meusestadios.php" style="display: inline-block; padding: 8px 16px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;" onmouseover="this.style.background=\'rgba(0, 0, 0, 0.06)\'" onmouseout="this.style.background=\'rgba(0, 0, 0, 0.03)\'">Voltar</a>';
    echo '</div>';

    // query caixa de seleção times desse dono
    $stmtLiga = $time->read($_SESSION['user_id']);
    $listaLigas = array();
    while ($row_liga = $stmtLiga->fetch(PDO::FETCH_ASSOC)){
        $addArray = array($row_liga['id'], $row_liga['nome'], $row_liga['Sexo']);
        $listaLigas[] = $addArray;
    }

    // query caixa de seleção paises desse dono
    $stmtPais = $pais->read($_SESSION['user_id']);
    $listaPaises = array();
    while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
        $addArray = array($row_pais['id'], $row_pais['nome']);
        $listaPaises[] = $addArray;
    }

    echo "<p class='selecaodeligas'>Seleção de time: <select id='selecaoTime' class=' selecaodeligas comboPais editavel '>";
    echo "<option value=''>Selecione time...</option>";
    for($i = 0; $i < count($listaLigas);$i++){
        echo "<option value='{$listaLigas[$i][0]}' data-sexo='{$listaLigas[$i][2]}'>{$listaLigas[$i][1]}</option>";
    }
    echo "</select></p>";

    echo "<p class='selecaodeligas'>Masculino / feminino: <select id='selecaosexo' class=' selecaodeligas comboPais editavel '>";
    echo "<option selected value='0'>Masculino</option>";
    echo "<option value='1'>Feminino</option>";
    echo "</select></p>";

    echo "<p class='selecaodeligas'>País: <select id='selecaonacionalidade' class=' selecaodeligas comboPais editavel '>";
    echo "<option value=''>Selecione país...</option>";
    for($i = 0; $i < count($listaPaises);$i++){
        echo "<option value='{$listaPaises[$i][0]}'>{$listaPaises[$i][1]}</option>";
    }
    echo "</select></p>";

    include_once($_SERVER['DOCUMENT_ROOT']."/elements/import_box.php");

    echo '</div>';
    echo '</main>';

} else {
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
    echo '<main class="propostas-container narrow-container" style="padding-top: 80px; padding-bottom: 60px;">';
    echo '<div class="propostas-card">';
    echo "<p>Usuário sem permissão para inserir estádios, por favor faça o login.</p>";
    echo '</div>';
    echo '</main>';
}
?>

<script>
$(document).ready(function() {
    updateLigas();
});

$('#selecaoTime').on('change', function (e) {
    var valueSelected = this.value;
    $('input[name="timeselecionado"]').val(valueSelected);
});

$('#selecaosexo').on('change', function (e) {
    var valueSelected = this.value;
    $('input[name="sexo"]').val(valueSelected);
    updateLigas();
});

$('#selecaonacionalidade').on('change', function (e) {
    var valueSelected = this.value;
    $('input[name="nacionalidade"]').val(valueSelected);
});

function updateLigas(){
    var sexo = $("#selecaosexo").val();
    $("#selecaoTime option").each(function(){
        var sexoLiga = $(this).attr("data-sexo");
        if(typeof sexoLiga !== 'undefined'){
            if (sexoLiga != sexo){
                $(this).hide();
            } else {
                $(this).show();
            }
        }
    });
}
</script>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
