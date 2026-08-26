<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Ranking de Seleções - Lista Completa";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'home_redesign';
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

<div class="ranking-container">
    <div class="ranking-card">
        <div class="ranking-card-header">
            <div>
                <h2 class="ranking-card-title">
                    <span class="material-symbols-outlined" style="color: #0284c7; font-size: 1.8rem;">flag</span>
                    Lista de Seleções
                </h2>
                <h3 class="ranking-card-date">Membros e seleções filiadas à CONFUSA</h3>
            </div>
            <span style="font-weight: 600; color: #64748b; font-size: 0.9rem;"><?php echo ($num > 0 ? ($num - 1) : 0); ?> Seleções</span>
        </div>
        
        <?php
        if($num > 0){
            echo "<div class='selecoes-grid'>";
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                extract($row);

                if ($id == 0) continue;
                
                echo "<a href='./teamstatus.php?team={$id}' class='selecao-card'>";
                    echo "<img src='/images/bandeiras/{$bandeira}' alt='{$nome}'>";
                    echo "<span>{$nome}</span>";
                echo "</a>";
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
