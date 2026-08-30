<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
header('Content-Type: text/html; charset=utf-8');
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
$database = new Database();
$db = $database->getConnection();
$trioArbitragem = new TrioArbitragem($db);
$pais = new Pais($db);

//declaracoes de parametros
$page_title = "Importar árbitro";
$css_filename = "home_redesign";
$aux_css = "arbitros_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
$_SESSION['jogadorTime'] = 3;
//$importing_reference_page = "arbitros/importar_arbitro";


if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
echo '<link rel="stylesheet" href="/css/importacao_moderna.css?v=' . $css_versao . '">';

echo '<main class="propostas-container narrow-container" style="padding-top: 80px; padding-bottom: 60px;">';
echo '<div class="propostas-card">';
echo '<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 1rem; width: 100%;">';
echo '    <h2 class="propostas-title" style="margin: 0; font-family: \'Outfit\', sans-serif; font-size: 1.6rem; color: #1e293b;">📥 Importar árbitro</h2>';
echo '    <a href="/arbitros" style="display: inline-block; padding: 8px 16px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;" onmouseover="this.style.background=\'rgba(0, 0, 0, 0.06)\'" onmouseout="this.style.background=\'rgba(0, 0, 0, 0.03)\'">Voltar</a>';
echo '</div>';

    $isAdmin = isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == '1';
    $stmtPais = $pais->read($isAdmin ? null : $_SESSION['user_id']);
    $listaPaises = array();
    while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
        $listaPaises[] = array($row_pais['id'], $row_pais['nome']);
    }

    echo "<p class='selecaodeligas'>Nacionalidade / País: <select id='selecaonacionalidade' class=' selecaodeligas comboPais editavel '>";
    echo "<option value=''>Detectar do arquivo / Selecione país...</option>";
    for($i = 0; $i < count($listaPaises); $i++){
        echo "<option value='{$listaPaises[$i][0]}'>{$listaPaises[$i][1]}</option>";
    }
    echo "</select></p>";

    include_once($_SERVER['DOCUMENT_ROOT']."/elements/import_box.php");

    echo '</div>';
    echo '</main>';
    ?>

    <script>
    $('#selecaonacionalidade').on('change', function (e) {
        var valueSelected = this.value;
        $('input[name="nacionalidade"]').val(valueSelected);
    });
    </script>
    <?php
} else {
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
    echo '<main class="propostas-container narrow-container" style="padding-top: 80px; padding-bottom: 60px;">';
    echo '<div class="propostas-card">';
    echo "Usuário sem permissão para inserir árbitros, por favor faça o login.";
    echo '</div>';
    echo '</main>';
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
