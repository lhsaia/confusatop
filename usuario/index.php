<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Tela inicial - ".($_SESSION['nomereal'] ?? 'Visitante');
$css_filename = "home_redesign";
$aux_css = "usuario_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");

$database = new Database();
$db = $database->getConnection();

$jogador = new Jogador($db);
$tecnico = new Tecnico($db);
$usuario = new Usuario($db);

// Consulta direta e precisa para contar propostas de jogadores que exigem ação (inbox pendente + outbox contraproposta)
$idUsuario = $_SESSION['user_id'];
$query_count_jogadores = "
    SELECT COUNT(t.id) as total
    FROM transferencias t
    LEFT JOIN clube c ON t.clubeOrigem = c.id
    LEFT JOIN paises p ON c.Pais = p.id
    LEFT JOIN jogador j ON t.jogador = j.id
    LEFT JOIN paises q ON j.Pais = q.id
    LEFT JOIN clube d ON t.clubeDestino = d.id
    LEFT JOIN paises z ON d.Pais = z.id
    WHERE (
        ((p.dono = ? AND t.clubeOrigem <> 0 AND t.status_execucao = 0) OR (t.clubeOrigem = 0 AND q.dono = ? AND t.status_execucao = 0))
        OR
        (z.dono = ? AND t.status_execucao = 2)
    )";
$stmt_count_jogadores = $db->prepare($query_count_jogadores);
$stmt_count_jogadores->execute([$idUsuario, $idUsuario, $idUsuario]);
$res_jogadores = $stmt_count_jogadores->fetch(PDO::FETCH_ASSOC);
$propostasPendentes = (int)($res_jogadores['total'] ?? 0);

// Consulta direta e precisa para contar propostas de técnicos que exigem ação (inbox pendente + outbox contraproposta)
$query_count_tecnicos = "
    SELECT COUNT(t.id) as total
    FROM transferencias_tecnico t
    LEFT JOIN clube c ON t.clubeOrigem = c.id
    LEFT JOIN paises p ON c.Pais = p.id
    LEFT JOIN clube d ON t.clubeDestino = d.id
    LEFT JOIN paises z ON d.Pais = z.id
    WHERE (
        (p.dono = ? AND t.status_execucao = 0)
        OR
        (z.dono = ? AND t.status_execucao = 2)
    )";
$stmt_count_tecnicos = $db->prepare($query_count_tecnicos);
$stmt_count_tecnicos->execute([$idUsuario, $idUsuario]);
$res_tecnicos = $stmt_count_tecnicos->fetch(PDO::FETCH_ASSOC);
$propostasPendentesTecnico = (int)($res_tecnicos['total'] ?? 0);

$tempoDesatualizado = $usuario->alteracoesPosteriores($_SESSION['user_id']);
$horas = round($tempoDesatualizado/3600,1);

?>

<main class="redesign-container">
    <div id='errorbox'></div>

    <h2 class="hub-section-title">Minha Área</h2>

    <!-- Seção: Propostas -->
    <h3 class="hub-subsection-title">Propostas</h3>
    <section class="redesign-grid">
        <!-- Propostas de Jogadores -->
        <a href='minhaspropostas.php' id="propostas" class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/proposta_jogador.jpg" alt="Propostas de Jogadores" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Propostas de Jogadores</span>
                    <?php if ($propostasPendentes > 0): ?>
                        <span class="badge-status counter"><?php echo $propostasPendentes; ?></span>
                    <?php else: ?>
                        <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                    <?php endif; ?>
                </h3>
                <p class="hub-card-desc">Visualize e responda a propostas de transferências de atletas.</p>
            </div>
        </a>

        <!-- Propostas de Técnicos -->
        <a href='minhaspropostastecnicos.php' id="propostasTecnicos" class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/proposta_tecnico.jpg" alt="Propostas de Técnicos" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Propostas de Técnicos</span>
                    <?php if ($propostasPendentesTecnico > 0): ?>
                        <span class="badge-status counter"><?php echo $propostasPendentesTecnico; ?></span>
                    <?php else: ?>
                        <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                    <?php endif; ?>
                </h3>
                <p class="hub-card-desc">Gerencie propostas recebidas por treinadores.</p>
            </div>
        </a>
    </section>

    <!-- Seção: Bases de Dados -->
    <h3 class="hub-subsection-title">Bases de Dados</h3>
    <section class="redesign-grid">
        <!-- Países -->
        <a href='meuspaises.php' class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/paises.jpg" alt="Países" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Países</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Gerencie as federações e seleções nacionais.</p>
            </div>
        </a>

        <!-- Ligas -->
        <a href="minhasligas.php" class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/ligas.jpg" alt="Ligas" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Ligas</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Configure e visualize as ligas nacionais.</p>
            </div>
        </a>

        <!-- Times -->
        <a href='meustimes.php' class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/times.jpeg" alt="Times" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Times</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Edite informações e gerencie os clubes cadastrados.</p>
            </div>
        </a>

        <!-- Jogadores -->
        <a href='meusjogadores.php' class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/jogadores.jpg" alt="Jogadores" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Jogadores</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Administre a base de dados de jogadores.</p>
            </div>
        </a>

        <!-- Técnicos -->
        <a href='meustecnicos.php' class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/tecnicos.png" alt="Técnicos" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Técnicos</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Gerencie a lista de técnicos e treinadores.</p>
            </div>
        </a>

        <!-- Estádios -->
        <a href='meusestadios.php' class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/estadios.jpg" alt="Estádios" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Estádios</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Cadastre e edite praças esportivas.</p>
            </div>
        </a>

        <!-- Climas -->
        <a href='meusclimas.php' class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/clima.jpg" alt="Climas" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Climas</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Gerencie as variações climáticas locais.</p>
            </div>
        </a>

        <!-- Jogadores no exterior -->
        <a href='jogadores_exterior.php' class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/exterior.jpeg" alt="Jogadores no exterior" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Jogadores no Exterior</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Consulte a base de atletas atuando fora do país.</p>
            </div>
        </a>
    </section>

    <!-- Seção: Gerenciamento Hexacolor YMT -->
    <h3 class="hub-subsection-title">Gerenciamento Hexacolor YMT</h3>
    <section class="redesign-grid">
        <!-- Exportar para HYMT -->
        <a href='minhaexportacao.php' id='quadro-exportar' title='<?php echo ($tempoDesatualizado > 0 ? "Alterações feitas ".$horas ." horas após o último download" :"Banco de dados atualizado") ?>' class='hub-card exportar <?php echo ($tempoDesatualizado > 0 ? "export_pending":"") ?>'>
            <div class="hub-card-hero-image">
                <img src="/images/hymt.jpg" alt="Exportar" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Exportar para HYMT</span>
                    <?php if ($tempoDesatualizado > 0): ?>
                        <span class="badge-status pending">Pendente</span>
                    <?php else: ?>
                        <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                    <?php endif; ?>
                </h3>
                <p class="hub-card-desc">Baixe a base de dados atualizada para uso offline.</p>
            </div>
        </a>

        <!-- Parâmetros HYMT -->
        <a href='meusparametros.php' class='hub-card'>
            <div class="hub-card-hero-image">
                <img src="/images/parametro.jpg" alt="Parâmetros HYMT" />
            </div>
            <div class="hub-card-body">
                <h3 class="hub-card-title">
                    <span>Parâmetros HYMT</span>
                    <span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
                </h3>
                <p class="hub-card-desc">Configure as opções de simulação do HYMT.</p>
            </div>
        </a>
    </section>
</main>

<?php

} else {
    echo "<main class='redesign-container'><p>Usuário, por favor refaça o login.</p></main>";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
