<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao.php");
$database = new Database();
$db = $database->getConnection();
$jogo = new Jogo($db);
$pais = new Pais($db);
$time = new Time($db);
$competicao = new Competicao($db);

//declaracoes de parametros
$page_title = "Importar jogo";
$css_filename = "indexRanking";
$aux_css = "main";
$css_login = 'login';
$css_versao = date('h:i:s');
$_SESSION['jogadorTime'] = 4;


if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

include_once($_SERVER['DOCUMENT_ROOT']."/elements/import_box.php");

echo "<p class='selecaodeligas'>Campeonato: <select id='selecaocampeonato' class=' selecaodeligas comboPais editavel '>'  ";
$stmtComp = $competicao->read();
while ($row_comp = $stmtComp->fetch(PDO::FETCH_ASSOC)){
    echo "<option value='{$row_comp['id']}'>{$row_comp['nome']}</option>";
}
echo "</select>";
echo "</p>";

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

$('#selecaofase').on('change', function (e) {
    var optionSelected = $("option:selected", this);
    var valueSelected = this.value;
    $('input[name="fase_jogo_import"]').val(valueSelected);
});

$('#selecaocampeonato').on('change', function (e) {
    var optionSelected = $("option:selected", this);
    var valueSelected = this.value;
    $('input[name="campeonato_jogo_import"]').val(valueSelected);
});


</script>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
