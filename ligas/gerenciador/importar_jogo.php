<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
header('Content-Type: text/html; charset=utf-8');
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos_clube.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");

$database = new Database();
$db = $database->getConnection();
$jogo = new Jogo($db);
$pais = new Pais($db);
$time = new Time($db);
$liga = new Liga($db);
$competicao = new Competicao($db);

//declaracoes de parametros
$page_title = "Importar jogo (clubes)";
$css_filename = "indexRanking";
$aux_css = "main";
$css_login = 'login';
$css_versao = date('h:i:s');
$_SESSION['jogadorTime'] = 7;


if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

// Direct query to campeonatos_clube as requested
// Fetch User's Leagues (Private)
$stmtLigas = $liga->read($_SESSION['user_id']); // Using read($dono) from Liga class

// Fetch Cups (Public [0] or User's [user_id])
$queryCups = "SELECT id, nome FROM campeonatos_clube WHERE dono = 0 OR dono = :dono ORDER BY nome";
$stmtCups = $db->prepare($queryCups);
$stmtCups->bindParam(":dono", $_SESSION['user_id']);
$stmtCups->execute();

echo "<p class='selecaodeligas'>Tipo de Competição: <select id='selecaotipo' class='selecaodeligas comboPais editavel'>";
echo "<option value='1' selected>Copa</option>";
echo "<option value='0'>Liga</option>";
echo "</select></p>";

echo "<div id='container_liga' style='display:none;'>";
echo "<p class='selecaodeligas'>Liga: <select id='selecaoliga' class='selecaodeligas comboPais editavel'>";
if($stmtLigas->rowCount() > 0){
    while ($row_liga = $stmtLigas->fetch(PDO::FETCH_ASSOC)){
        echo "<option value='{$row_liga['id']}'>{$row_liga['nome']}</option>";
    }
} else {
    echo "<option value='0'>Nenhuma liga encontrada</option>";
}
echo "</select></p>";
echo "</div>";

echo "<div id='container_copa'>";
echo "<p class='selecaodeligas'>Copa: <select id='selecaocopa' class='selecaodeligas comboPais editavel'>";
while ($row_comp = $stmtCups->fetch(PDO::FETCH_ASSOC)){
    echo "<option value='{$row_comp['id']}'>{$row_comp['nome']}</option>";
}
echo "</select></p>";
echo "</div>";

// Hidden inputs removed here because they are now in import_box.php or handled by JS


echo "<p class='selecaodeligas'>Fase: <select id='selecaofase' class=' selecaodeligas comboPais editavel '>'  ";
echo "<option value='0'>N/A</option>";
echo "<option value='1'>Fase pré</option>";
echo "<option value='2'>Fase de grupos</option>";
echo "<option value='3'>Oitavas-de-final</option>";
echo "<option value='4'>Quartas-de-final</option>";
echo "<option value='5'>Semi-final</option>";
echo "<option value='6'>Disputa de terceiro lugar</option>";
echo "<option value='7'>Repescagem</option>";
echo "<option value='8'>Final</option>";
echo "</select>";
echo "</p>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/import_box.php");

// Moved dropdowns to top

// echo "<p class='selecaodeligas'>Masculino / feminino: <select id='selecaosexo' class=' selecaodeligas comboPais editavel '>'  ";
// echo "<option selected value='0' >Masculino</option>";
// echo "<option value='1' >Feminino</option>";
// echo "</select>";
// echo "</p>";

} else {
    echo "Usuário sem permissão para inserir jogos, por favor faça o login.";
}

?>

<script>

//
// $('#selecaosexo').on('change', function (e) {
//     var optionSelected = $("option:selected", this);
//     var valueSelected = this.value;
//     $('input[name="sexo"]').val(valueSelected);
// });

// Sync values on page load
$(document).ready(function() {
    updateCompeticaoValues();
});

$('#selecaotipo').on('change', function (e) {
    var tipo = $(this).val();
    $('#competicao_tipo').val(tipo);
    
    if(tipo == '0'){ // Liga
        $('#container_liga').show();
        $('#container_copa').hide();
    } else { // Copa
        $('#container_liga').hide();
        $('#container_copa').show();
    }
    updateCompeticaoValues();
});

$('#selecaoliga').on('change', function (e) {
    updateCompeticaoValues();
});

$('#selecaocopa').on('change', function (e) {
    updateCompeticaoValues();
});

$('#selecaofase').on('change', function (e) {
    var valueSelected = this.value;
    $('input[name="fase_jogo_import"]').val(valueSelected);
});

function updateCompeticaoValues(){
    var tipo = $('#selecaotipo').val();
    $('#competicao_tipo').val(tipo);
    $('input[name="fase_jogo_import"]').val($('#selecaofase').val());
    
    if(tipo == '0'){
        $('input[name="campeonato_jogo_import"]').val($('#selecaoliga').val());
    } else {
        $('input[name="campeonato_jogo_import"]').val($('#selecaocopa').val());
    }
}

// Double check before upload interaction
$('.box__file').on('click', function() {
    updateCompeticaoValues();
});


</script>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
