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
$page_title = "Gerenciar Competicoes";
$css_filename = "indexRanking";
$aux_css = "main";
$css_login = 'login';
$css_versao = date('h:i:s');
?>

<style>
    #ranking-container {
        padding: 40px 20px;
        background: #f4f7f6;
        min-height: 100vh;
        overflow-x: hidden;
    }

    #quadroTimes {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    h2 {
        font-family: 'Outfit', 'Inter', sans-serif;
        font-weight: 700 !important;
        color: #1a1469 !important;
        margin-bottom: 30px;
        position: relative;
        padding-bottom: 15px;
        letter-spacing: -0.5px;
    }
    
    h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 4px;
        background: linear-gradient(90deg, #1a1469, #4c41d1);
        border-radius: 2px;
    }
    
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 30px;
    }
    
    .card-header {
        background: #fff !important;
        border-bottom: 1px solid #edf2f7;
        padding: 18px 25px;
        font-weight: 700 !important;
        color: #1a1469 !important;
        font-size: 1.2em;
        display: flex;
        align-items: center;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .card-header i {
        margin-right: 12px;
        color: #4c41d1;
        font-size: 1.1em;
    }
    
    .form-label {
        font-weight: 600;
        color: #4a5568;
    }
    
    .btn-primary {
        background: #1a1469;
        border: none;
        padding: 10px 25px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background: #2a228a;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(26, 20, 105, 0.3);
    }
    
    .table thead th {
        background: #f8fafc;
        border-bottom: 2px solid #edf2f7;
        color: #718096;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85em;
        letter-spacing: 0.5px;
    }
    
    .table td {
        vertical-align: middle;
        color: #2d3748;
    }

    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .btn-action {
        padding: 8px 20px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none !important;
    }

    .btn-nav {
        background: #1a1469;
        color: #fff !important;
    }

    .btn-nav:hover {
        background: #2a228a;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(26, 20, 105, 0.3);
    }
</style>

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

<div id="ranking-container">
    <div id="quadroTimes">
        <div class="header-actions">
            <h2>Gerenciar Meus Campeonatos</h2>
            <div class="d-flex" style="gap: 10px;">
                <a href="/usuario/minhasligas.php" class="btn-action btn-nav"><span class="material-symbols-outlined">list</span> Minhas Ligas</a>
            </div>
        </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <span class="material-symbols-outlined">add_circle</span> Criar Nova Copa
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="form-group row mb-0">
                    <label for="nome" class="col-sm-2 col-form-label font-weight-bold">Nome da Copa:</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: Super Copa dos Campeões" required>
                    </div>
                    <div class="col-sm-2 text-end">
                        <button type="submit" name="create_competition" class="btn btn-primary w-100">Criar Copa</button>
                    </div>
                </div>
                <!-- Add more fields if necessary based on Competicao_clube::inserir() parameters -->
                

            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="material-symbols-outlined">list</span> Minhas Copas Existentes
        </div>
        <div class="card-body">
            <?php if($stmtUserCups->rowCount() > 0): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th width="15%">ID</th>
                            <th>Nome da Competição</th>
                            <!-- <th>Ações</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmtUserCups->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark">#<?php echo $row['id']; ?></span></td>
                                <td class="fw-bold"><?php echo $row['nome']; ?></td>
                                <!-- <td><a href="#" class="btn btn-sm btn-secondary">Editar</a></td> -->
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhuma competição encontrada.</p>
            <?php endif; ?>
        </div>
    </div>
    </div>
</div>

<?php

} else {
    echo "Usuário sem permissão, por favor faça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
