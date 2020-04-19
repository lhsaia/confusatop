<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
header('Content-Type: text/html; charset=utf-8');
session_start();
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");

$database = new Database();
$db = $database->getConnection();
$jogador = new Jogador($db);
$pais = new Pais($db);
$time = new Time($db);
$estadio = new Estadio($db);
$clima = new Clima($db);
$tecnico = new Tecnico($db);
$liga = new Liga($db);

//declaracoes de parametros
$page_title = "Importar time";
$css_filename = "indexRanking";
$aux_css = "main";
$css_login = 'login'; 
$css_versao = date('h:i:s');

$_SESSION[ 'jogadorTime' ] = 2;


if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

include_once($_SERVER['DOCUMENT_ROOT']."/elements/import_box.php");

    // query caixa de seleção ligas desse dono

    $stmtLiga = $liga->read($_SESSION['user_id']);
    $listaLigas = array();
    while ($row_pais = $stmtLiga->fetch(PDO::FETCH_ASSOC)){
        $addArray = array($row_pais['id'], $row_pais['nome'], $row_pais['Sexo'], $row_pais['Pais']);
        $listaLigas[] = $addArray;
    }

    echo "<p class='selecaodeligas'>Seleção de liga: <select id='selecaoligas' class=' selecaodeligas comboPais editavel '>'  ";
    echo "<option value='' >Selecione liga...</option>";
    for($i = 0; $i < count($listaLigas);$i++){
        echo "<option value='{$listaLigas[$i][0]}' data-sexo='{$listaLigas[$i][2]}' data-pais='{$listaLigas[$i][3]}'>{$listaLigas[$i][1]}</option>";
    }
    echo "</select>";
    echo "</p>";

    echo "<p class='selecaodeligas'>Masculino / feminino: <select id='selecaosexo' class=' selecaodeligas comboPais editavel '>'  ";
    echo "<option selected value='0' >Masculino</option>";
    echo "<option value='1' >Feminino</option>";
    echo "</select>";
    echo "</p>";

} else {
    echo "Usuário sem permissão para inserir times, por favor faça o login.";
}

?>

<script>

$( document ).ready(function() {
    updateLigas();
});


$('#selecaoligas').on('change', function (e) {
    var optionSelected = $("option:selected", this);
    var valueSelected = this.value;
    var valuePaisLiga = $('option:selected', this).attr('data-pais');
    $('input[name="ligaselecionada"]').val(valueSelected);
    $('input[name="paisligaselecionada"]').val(valuePaisLiga);
});

$('#selecaosexo').on('change', function (e) {
    var optionSelected = $("option:selected", this);
    var valueSelected = this.value;
    $('input[name="sexo"]').val(valueSelected);
    updateLigas();
});

function updateLigas(){
    var sexo = $("#selecaosexo").val();
    $("#selecaoligas option").each(function(){

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

// $('#importForm').submit(function(event){

//     var myform = $(this);
//     var fd = new FormData(myform);
//     $.ajax({
//         url: "/elements/import_ajax.php",
//         data: fd,
//         cache: false,
//         processData: false,
//         contentType: false,
//         type: 'POST',
//         success: function (dataofconfirm) {
//             // do something with the result
//             if(dataofconfirm.success){
//                 $('.box__error').
//             } else {

//             }
//         }
//     });

// event.preventDefault();
// });

</script>

<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
