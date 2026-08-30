<?php
header('Content-Type: text/html; charset=utf-8');
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");

$database = new Database();
$db = $database->getConnection();
$jogador = new Jogador($db);
$pais = new Pais($db);
$time = new Time($db);

//declaracoes de parametros
$page_title = "Importar jogador";
$css_filename = "home_redesign";
$aux_css = "arbitros_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
$_SESSION[ 'jogadorTime' ] = 1;

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

if($_SESSION['emTestes'] ?? false){
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
    echo '<main class="propostas-container narrow-container" style="padding-top: 80px; padding-bottom: 60px;"><div class="propostas-card"><div class="alert alert-warning" style="background: rgba(251, 191, 36, 0.15); color: #d97706; padding: 16px; border-radius: 8px;">Usuários em período de testes não possuem permissão para importar jogadores.</div></div></main>';
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
echo '<link rel="stylesheet" href="/css/importacao_moderna.css?v=' . $css_versao . '">';

echo '<main class="propostas-container narrow-container" style="padding-top: 80px; padding-bottom: 60px;">';
echo '<div class="propostas-card">';
echo '<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 1rem; width: 100%;">';
echo '    <h2 class="propostas-title" style="margin: 0; font-family: \'Outfit\', sans-serif; font-size: 1.6rem; color: #1e293b;">📥 Importar jogador</h2>';
echo '    <a href="/jogadores" style="display: inline-block; padding: 8px 16px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;" onmouseover="this.style.background=\'rgba(0, 0, 0, 0.06)\'" onmouseout="this.style.background=\'rgba(0, 0, 0, 0.03)\'">Voltar</a>';
echo '</div>';

$is_admin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == '1' && $_SESSION['impersonated'] == false);
$stmtLiga = $time->read($is_admin ? null : $_SESSION['user_id']);
$listaLigas = array();
while ($row_pais = $stmtLiga->fetch(PDO::FETCH_ASSOC)){
    $addArray = array($row_pais['id'], $row_pais['nome'], $row_pais['Sexo']);
    $listaLigas[] = $addArray;
}

$last_time = $_SESSION['last_import_time'] ?? '';
$last_sexo = $_SESSION['last_import_sexo'] ?? '0';

echo "<p class='selecaodeligas'>Seleção de time: <select id='selecaoTime' class=' selecaodeligas comboPais editavel '>'  ";
echo "<option value='' " . (empty($last_time) ? 'selected' : '') . ">Selecione time...</option>";
for($i = 0; $i < count($listaLigas);$i++){
    $selected = ($listaLigas[$i][0] == $last_time) ? 'selected' : '';
    echo "<option value='{$listaLigas[$i][0]}' data-sexo='{$listaLigas[$i][2]}' {$selected}>{$listaLigas[$i][1]}</option>";
}
echo "</select>";
echo "</p>";

echo "<p class='selecaodeligas'>Masculino / feminino: <select id='selecaosexo' class=' selecaodeligas comboPais editavel '>'  ";
echo "<option value='0' " . ($last_sexo == '0' ? 'selected' : '') . ">Masculino</option>";
echo "<option value='1' " . ($last_sexo == '1' ? 'selected' : '') . ">Feminino</option>";
echo "</select>";
echo "</p>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/import_box.php");

echo '</div>';
echo '</main>';

} else {
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
    echo '<main class="propostas-container narrow-container" style="padding-top: 80px; padding-bottom: 60px;">';
    echo '<div class="propostas-card">';
    echo "Usuário sem permissão para inserir jogadores, por favor faça o login.";
    echo '</div>';
    echo '</main>';
}
?>

<script>
$( document ).ready(function() {
    updateLigas();
    $('#selecaoTime').trigger('change');
});

$('#selecaoTime').on('change', function (e) {
    var optionSelected = $("option:selected", this);
    var valueSelected = this.value;
    $('input[name="timeselecionado"]').val(valueSelected);
});

$('#selecaosexo').on('change', function (e) {
    var optionSelected = $("option:selected", this);
    var valueSelected = this.value;
    $('input[name="sexo"]').val(valueSelected);
    updateLigas();
});

function updateLigas(){
    var sexo = $("#selecaosexo").val();
    $("#selecaoTime option").each(function(){
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
