<?php
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Ranking de Seleções - Lista Completa";
$css_filename = "indexRanking";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once 'ranking_header.php';

// Estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);

// Ler todos os países (ranqueavel = 0 -> Confusa)
$stmt = $pais->read(null, true);
$num = $stmt->rowCount();

?>

<div id="ranking-container" style="height: auto; min-height: 100vh;">
    <div align="center" id="ranking">
        <h2>Lista de Seleções</h2>
        <hr>
        
        <?php
        if($num > 0){
            echo "<div style='display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; padding: 20px;'>";
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);

                if ($id == 0) continue;
                
                echo "<div class='selecao-item' style='width: 200px; text-align: center; margin-bottom: 20px;'>";
                echo "<a href='./teamstatus.php?team={$id}' style='text-decoration: none; color: black; font-weight: bold;'>";
                echo "<img src='/images/bandeiras/{$bandeira}' style='width: 100px; height: auto; border: 1px solid #ccc; box-shadow: 2px 2px 5px rgba(0,0,0,0.1); display: block; margin: 0 auto 10px auto;'>";
                echo "<span>{$nome}</span>";
                echo "</a>";
                echo "</div>";
            }
            
            echo "</div>";
        } else {
            echo "<div class='alert alert-info'>Nenhuma seleção encontrada.</div>";
        }
        ?>

    </div>
</div>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
