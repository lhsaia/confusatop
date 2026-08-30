<?php
// gerenciar_competicoes.php (Focused on Cups/Campeonatos)

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
header('Content-Type: text/html; charset=utf-8');
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/campeonato_clube.php");

$database = new Database();
$db = $database->getConnection();
$competicao = new Campeonato_clube($db);

// Parameters and Header
$page_title = "Gerenciar Competições";
$css_filename = "home_redesign";
$aux_css = "home_redesign";
$extra_css = "ligas_redesign";
$css_versao = date('h:i:s');
?>

<?php
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

// Handle Form Submission
if(isset($_POST['create_competition'])){
    $nome = $_POST['nome'];
    $federacao = 0; // Default or could be input
    $sede = 0;      // Default or could be input
    $genero = 'M';  // Default or could be input
    $dono = $_SESSION['user_id'];
    
    // Assign values to object
    $competicao->nome = $nome;
    $competicao->federacao = $federacao;
    $competicao->sede = $sede;
    $competicao->genero = $genero;
    $competicao->dono = $dono;
    $competicao->logo = ''; // Optional
    
    if($competicao->inserir()){
        echo "<div class='alert alert-success'>Competição criada com sucesso!</div>";
    } else {
        echo "<div class='alert alert-danger'>Erro ao criar competição.</div>";
    }
}

// Fetch User's Cups
$queryUserCups = "SELECT * FROM campeonatos_clube WHERE dono = :dono ORDER BY nome DESC";
$stmtUserCups = $db->prepare($queryUserCups);
$stmtUserCups->bindParam(":dono", $_SESSION['user_id']);
$stmtUserCups->execute();

?>

<main class="propostas-container">
    <div class="propostas-card mb-4">
        <div class="header-actions-container">
            <h2 class="propostas-title">Gerenciar Meus Campeonatos</h2>
            <div class="d-flex" style="gap: 10px;">
                <a href="/usuario/minhasligas.php" class="btn-action-primary"><span class="material-symbols-outlined">list</span> Minhas Ligas</a>
            </div>
        </div>
        
        <div class="propostas-card-subtitle">
            <span class="material-symbols-outlined">add_circle</span> Criar Nova Copa
        </div>
        <form method="post" action="">
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <label for="nome">Nome da Copa:</label>
                <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: Super Copa dos Campeões" required>
                <button type="submit" name="create_competition" class="btn-primary">Criar Copa</button>
            </div>
        </form>
    </div>

    <div class="propostas-card">
        <div class="propostas-card-subtitle">
            <span class="material-symbols-outlined">list</span> Minhas Copas Existentes
        </div>
        
        <?php if($stmtUserCups->rowCount() > 0): ?>
            <div class="tbl_user_data">
                <table id="tabelaPrincipal" class="table">
                    <thead>
                        <tr>
                            <th width="15%">ID</th>
                            <th>Nome da Competição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmtUserCups->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark">#<?php echo $row['id']; ?></span></td>
                                <td class="fw-bold"><?php echo $row['nome']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 2.5rem 1.5rem; background: rgba(2, 132, 199, 0.03); border: 1px dashed rgba(2, 132, 199, 0.25); border-radius: 14px; margin-top: 1rem;">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(2, 132, 199, 0.1); color: #0284c7; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <span class="material-symbols-outlined" style="font-size: 32px;">emoji_events</span>
                </div>
                <h3 style="font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 600; color: #1e293b; margin: 0 0 6px 0;">Nenhuma copa encontrada</h3>
                <p style="font-family: 'Montserrat', sans-serif; font-size: 0.9rem; color: #64748b; margin: 0;">Você ainda não possui copas cadastradas para gerenciar. Utilize o formulário acima para criar sua primeira copa.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php

} else {
    echo "Usuário sem permissão, por favor faça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
