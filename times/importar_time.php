<?php
header('Content-Type: text/html; charset=utf-8');
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
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
$css_filename = "home_redesign";
$aux_css = "arbitros_redesign";
$css_login = 'login'; 
$css_versao = date('h:i:s');

$_SESSION[ 'jogadorTime' ] = 2;

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
echo '<link rel="stylesheet" href="/css/importacao_moderna.css?v=' . $css_versao . '">';

echo '<main class="propostas-container narrow-container" style="padding-top: 80px; padding-bottom: 60px;">';
echo '<div class="propostas-card">';
echo '<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 1rem; width: 100%;">';
echo '    <h2 class="propostas-title" style="margin: 0; font-family: \'Kanit\', sans-serif; font-size: 1.6rem; color: #1e293b;">📥 Importar time</h2>';
echo '    <a href="/times" style="display: inline-block; padding: 8px 16px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;" onmouseover="this.style.background=\'rgba(0, 0, 0, 0.06)\'" onmouseout="this.style.background=\'rgba(0, 0, 0, 0.03)\'">Voltar</a>';
echo '</div>';

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

include_once($_SERVER['DOCUMENT_ROOT']."/elements/import_box.php");

echo '</div>';
echo '</main>';

} else {
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
    echo '<main class="propostas-container narrow-container" style="padding-top: 80px; padding-bottom: 60px;">';
    echo '<div class="propostas-card">';
    echo "Usuário sem permissão para inserir times, por favor faça o login.";
    echo '</div>';
    echo '</main>';
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
