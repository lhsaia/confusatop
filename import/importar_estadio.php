<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
$database = new Database();
$db = $database->getConnection();
$estadio = new Estadio($db);
$pais = new Pais($db);
$time = new Time($db);

//declaracoes de parametros
$page_title = "Importar estádio";
$css_filename = "indexRanking";
$aux_css = "main";
$css_login = 'login';
$css_versao = date('h:i:s');
$_SESSION[ 'jogadorTime' ] = 6;


if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

include_once($_SERVER['DOCUMENT_ROOT']."/elements/import_box.php");

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

echo "<p class='selecaodeligas'>Seleção de time: <select id='selecaoTime' class=' selecaodeligas comboPais editavel '>'  ";
echo "<option value='' >Selecione time...</option>";
for($i = 0; $i < count($listaLigas);$i++){
    echo "<option value='{$listaLigas[$i][0]}' data-sexo='{$listaLigas[$i][2]}'>{$listaLigas[$i][1]}</option>";
}
echo "</select>";
echo "</p>";

echo "<p class='selecaodeligas'>Masculino / feminino: <select id='selecaosexo' class=' selecaodeligas comboPais editavel '>'  ";
echo "<option selected value='0' >Masculino</option>";
echo "<option value='1' >Feminino</option>";
echo "</select>";
echo "</p>";

echo "<p class='selecaodeligas'>País: <select id='selecaonacionalidade' class=' selecaodeligas comboPais editavel '>'  ";
echo "<option value='' >Selecione país...</option>";
for($i = 0; $i < count($listaPaises);$i++){
    echo "<option value='{$listaPaises[$i][0]}'>{$listaPaises[$i][1]}</option>";
}
echo "</select>";
echo "</p>";

} else {
    echo "Usuário sem permissão para inserir estádios, por favor faça o login.";
}

?>

<script>

$( document ).ready(function() {
    updateLigas();
});

$('#selecaoTime').on('change', function (e) {
    var optionSelected = $("option:selected", this);
    var valueSelected = this.value;
    $('input[name="timeselecionado"]').val(valueSelected);
    console.log(valueSelected);
});

$('#selecaosexo').on('change', function (e) {
    var optionSelected = $("option:selected", this);
    var valueSelected = this.value;
    $('input[name="sexo"]').val(valueSelected);
    updateLigas();
});

$('#selecaonacionalidade').on('change', function (e) {
    var optionSelected = $("option:selected", this);
    var valueSelected = this.value;
    $('input[name="nacionalidade"]').val(valueSelected);
});

function updateLigas(){
    var sexo = $("#selecaosexo").val();
    $("#selecaoTime option").each(function(){

        var sexoLiga = $(this).attr("data-sexo");
        //console.log(sexoLiga);
        //console.log(sexo);
        if (sexoLiga != sexo){
            $(this).hide();
        } else {
            $(this).show();
        }

});


}

</script>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
