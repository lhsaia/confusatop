<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/login_info.php");

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: /login.php");
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/jogador.php");

$database = new Database();
$db = $database->getConnection();
$jogadorObj = new Jogador($db);

$userId = (int)$_SESSION['user_id'];
$mensagemSucesso = null;
$mensagemErro = null;

// Processar formulários POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $dados = [
            'percentual_salario' => (float)str_replace(',', '.', $_POST['percentual_salario'] ?? 0.50),
            'mult_idade_ate20' => (float)str_replace(',', '.', $_POST['mult_idade_ate20'] ?? 1.3),
            'mult_idade_21_22' => (float)str_replace(',', '.', $_POST['mult_idade_21_22'] ?? 1.15),
            'mult_idade_23_28' => (float)str_replace(',', '.', $_POST['mult_idade_23_28'] ?? 1.0),
            'mult_idade_29_30' => (float)str_replace(',', '.', $_POST['mult_idade_29_30'] ?? 0.9),
            'mult_idade_31_40' => (float)str_replace(',', '.', $_POST['mult_idade_31_40'] ?? 0.8),
            'mult_idade_41_mais' => (float)str_replace(',', '.', $_POST['mult_idade_41_mais'] ?? 0.5),
            'bonus_falta' => (float)str_replace(',', '.', $_POST['bonus_falta'] ?? 1.08),
            'fator_global' => (float)str_replace(',', '.', $_POST['fator_global'] ?? 1.0),
            'ajuste_goleiro' => (float)str_replace(',', '.', $_POST['ajuste_goleiro'] ?? 1.0),
            'ajuste_lateral' => (float)str_replace(',', '.', $_POST['ajuste_lateral'] ?? 1.0),
            'ajuste_zagueiro' => (float)str_replace(',', '.', $_POST['ajuste_zagueiro'] ?? 1.0),
            'ajuste_ala' => (float)str_replace(',', '.', $_POST['ajuste_ala'] ?? 1.0),
            'ajuste_volante' => (float)str_replace(',', '.', $_POST['ajuste_volante'] ?? 1.0),
            'ajuste_meia' => (float)str_replace(',', '.', $_POST['ajuste_meia'] ?? 1.0),
            'ajuste_atacante' => (float)str_replace(',', '.', $_POST['ajuste_atacante'] ?? 1.0),
            'bonus_polivalencia' => (float)str_replace(',', '.', $_POST['bonus_polivalencia'] ?? 1.0),
            'faixa1_a' => (float)str_replace(',', '.', $_POST['faixa1_a'] ?? 2),
            'faixa1_b' => (float)str_replace(',', '.', $_POST['faixa1_b'] ?? 10),
            'faixa2_a' => (float)str_replace(',', '.', $_POST['faixa2_a'] ?? 40),
            'faixa2_b' => (float)str_replace(',', '.', $_POST['faixa2_b'] ?? 100),
            'faixa3_a' => (float)str_replace(',', '.', $_POST['faixa3_a'] ?? 175),
            'faixa3_b' => (float)str_replace(',', '.', $_POST['faixa3_b'] ?? 675),
            'faixa4_a' => (float)str_replace(',', '.', $_POST['faixa4_a'] ?? 250),
            'faixa4_b' => (float)str_replace(',', '.', $_POST['faixa4_b'] ?? 4250),
            'faixa5_a' => (float)str_replace(',', '.', $_POST['faixa5_a'] ?? 350),
            'faixa5_b' => (float)str_replace(',', '.', $_POST['faixa5_b'] ?? 6850),
            'faixa6_a' => (float)str_replace(',', '.', $_POST['faixa6_a'] ?? 400),
            'faixa6_b' => (float)str_replace(',', '.', $_POST['faixa6_b'] ?? 9700),
        ];

        if ($jogadorObj->salvarParametrosValores($userId, $dados)) {
            $mensagemSucesso = "Parâmetros salvos com sucesso! Novos cálculos de passe nos seus clubes utilizarão esta base.";
        } else {
            $mensagemErro = "Ocorreu um erro ao salvar os parâmetros. Verifique os valores inseridos.";
        }
    } elseif ($acao === 'restaurar') {
        if ($jogadorObj->restaurarParametrosValoresPadrao($userId)) {
            $mensagemSucesso = "Parâmetros restaurados para o padrão original do sistema.";
        } else {
            $mensagemErro = "Ocorreu um erro ao restaurar os parâmetros padrões.";
        }
    } elseif ($acao === 'recalcular_massa') {
        $resultado = $jogadorObj->recalcularPassesEmMassa($userId);
        if ($resultado['success']) {
            $mensagemSucesso = "Recálculo em massa concluído com sucesso! Foram atualizados os valores e salários de <strong>" . (int)$resultado['total'] . "</strong> atletas de seus clubes.";
        } else {
            $mensagemErro = "Erro ao executar recálculo em massa: " . htmlspecialchars($resultado['error'] ?? 'Erro desconhecido');
        }
    }
}

// Obter parâmetros atuais
$params = $jogadorObj->obterParametrosValores($userId);

// Obter estatísticas dos clubes e jogadores do usuário
$totalClubes = 0;
$totalJogadores = 0;
$valorTotalElencos = 0;
try {
    $stmtStats = $db->prepare("
        SELECT 
            COUNT(DISTINCT c.id) as total_clubes,
            COUNT(DISTINCT j.id) as total_jogadores,
            SUM(j.valor) as valor_total
        FROM contratos_jogador cj
        INNER JOIN clube c ON cj.clube = c.id
        INNER JOIN paises p ON c.Pais = p.id
        INNER JOIN jogador j ON cj.jogador = j.id
        WHERE p.dono = ? AND cj.tipoContrato = 0
    ");
    $stmtStats->execute([$userId]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    if ($stats) {
        $totalClubes = (int)($stats['total_clubes'] ?? 0);
        $totalJogadores = (int)($stats['total_jogadores'] ?? 0);
        $valorTotalElencos = (float)($stats['valor_total'] ?? 0);
    }
} catch (Exception $e) {
    error_log("Erro ao buscar estatisticas de valores: " . $e->getMessage());
}

$page_title = "Parâmetros de Valores - " . ($_SESSION['nomereal'] ?? 'CONFUSA.top');
$css_filename = "home_redesign";
$aux_css = "parametros_valores";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/header.php");

?>

<div style="clear:both;"></div>
<div class="propostas-container">
    <div class="propostas-card">
        
        <div class="header-actions-container">
            <h2 class="propostas-title">
                Parâmetros de Valores - <?php echo htmlspecialchars($_SESSION['nomereal'] ?? ''); ?>
            </h2>
            <div class="header-buttons-wrapper">
                <a href="/usuario/index.php" class="btn-action-primary">
                    <span class="material-symbols-outlined">arrow_back</span>
                    <span>Voltar à Minha Área</span>
                </a>
            </div>
        </div>

    <!-- Notificações -->
    <?php if ($mensagemSucesso): ?>
        <div class="alert-box alert-success">
            <span class="material-symbols-outlined">check_circle</span>
            <div><?php echo $mensagemSucesso; ?></div>
        </div>
    <?php endif; ?>

    <?php if ($mensagemErro): ?>
        <div class="alert-box alert-error">
            <span class="material-symbols-outlined">error</span>
            <div><?php echo $mensagemErro; ?></div>
        </div>
    <?php endif; ?>

    <!-- Estatísticas Rápidas -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <span class="material-symbols-outlined">shield</span>
            </div>
            <div class="stat-info">
                <div class="stat-label">Seus Clubes</div>
                <div class="stat-value"><?php echo number_format($totalClubes, 0, ',', '.'); ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <span class="material-symbols-outlined">groups</span>
            </div>
            <div class="stat-info">
                <div class="stat-label">Atletas sob Contrato</div>
                <div class="stat-value"><?php echo number_format($totalJogadores, 0, ',', '.'); ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon amber">
                <span class="material-symbols-outlined">monetization_on</span>
            </div>
            <div class="stat-info">
                <div class="stat-label">Avaliação Total dos Elencos</div>
                <div class="stat-value">$ <?php echo number_format($valorTotalElencos, 0, ',', '.'); ?></div>
            </div>
        </div>
    </section>

    <!-- Banner de Recálculo em Massa -->
    <div class="recalculo-banner">
        <div class="recalculo-info">
            <h3>
                <span class="material-symbols-outlined" style="color: #38bdf8;">sync</span>
                Recálculo em Massa de Passes & Salários
            </h3>
            <p>Aplica instantaneamente seus parâmetros atuais a todos os <strong><?php echo $totalJogadores; ?> atletas</strong> pertencentes aos seus <strong><?php echo $totalClubes; ?> clubes</strong>, recalculando valores de passe de mercado e os salários pelo percentual configurado (<strong><?php echo number_format((float)($params['percentual_salario'] ?? 0.5), 2, ',', '.'); ?>%</strong> do passe).</p>
        </div>
        <form method="POST" action="" onsubmit="return confirm('Deseja realmente recalcular o passe e salário de todos os atletas dos seus clubes agora? Esta operação atualizará o banco de dados.');">
            <input type="hidden" name="acao" value="recalcular_massa">
            <button type="submit" class="btn-emerald" style="padding: 14px 24px; font-size: 1rem;">
                <span class="material-symbols-outlined">auto_fix_high</span>
                Recalcular em Massa
            </button>
        </form>
    </div>

    <!-- Formulário Principal de Configuração -->
    <form method="POST" action="" id="formParametros" style="margin-top: 24px;">
        <input type="hidden" name="acao" value="salvar">

        <!-- Card: Multiplicadores Gerais, Salário & Idade -->
        <div class="config-card">
            <div class="card-section-header">
                <h2>
                    <span class="material-symbols-outlined" style="color: #0284c7;">hourglass_empty</span>
                    1. Fatores Globais, Percentual de Salário & Idade
                </h2>
            </div>
            <p class="card-section-desc">Ajuste o percentual de salário mensal, coeficientes aplicados sobre o valor base do atleta e faixas etárias.</p>

            <div class="form-grid-4">
                <div class="form-group">
                    <label for="percentual_salario">
                        % Salário / Passe
                        <span class="field-hint">Padrão: 0.50%</span>
                    </label>
                    <input type="number" step="0.01" min="0.01" max="100.0" class="form-input param-trigger" id="percentual_salario" name="percentual_salario" value="<?php echo htmlspecialchars($params['percentual_salario'] ?? '0.50'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="fator_global">
                        Fator de Escala Global
                        <span class="field-hint">Padrão: 1.00</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="10" class="form-input param-trigger" id="fator_global" name="fator_global" value="<?php echo htmlspecialchars($params['fator_global']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="bonus_falta">
                        Bônus Cobrador de Falta
                        <span class="field-hint">Padrão: 1.08</span>
                    </label>
                    <input type="number" step="0.01" min="1.0" max="3.0" class="form-input param-trigger" id="bonus_falta" name="bonus_falta" value="<?php echo htmlspecialchars($params['bonus_falta']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="bonus_polivalencia">
                        Multiplicador de Polivalência
                        <span class="field-hint">Padrão: 1.00</span>
                    </label>
                    <input type="number" step="0.01" min="0.5" max="3.0" class="form-input param-trigger" id="bonus_polivalencia" name="bonus_polivalencia" value="<?php echo htmlspecialchars($params['bonus_polivalencia']); ?>" required>
                </div>
            </div>

            <div class="form-grid-3" style="margin-top: 14px;">
                <div class="form-group">
                    <label for="mult_idade_ate20">
                        Até 20 anos
                        <span class="field-hint">Padrão: 1.30</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="5.0" class="form-input param-trigger" id="mult_idade_ate20" name="mult_idade_ate20" value="<?php echo htmlspecialchars($params['mult_idade_ate20']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="mult_idade_21_22">
                        21 a 22 anos
                        <span class="field-hint">Padrão: 1.15</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="5.0" class="form-input param-trigger" id="mult_idade_21_22" name="mult_idade_21_22" value="<?php echo htmlspecialchars($params['mult_idade_21_22']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="mult_idade_23_28">
                        23 a 28 anos (Auge)
                        <span class="field-hint">Padrão: 1.00</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="5.0" class="form-input param-trigger" id="mult_idade_23_28" name="mult_idade_23_28" value="<?php echo htmlspecialchars($params['mult_idade_23_28']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="mult_idade_29_30">
                        29 a 30 anos
                        <span class="field-hint">Padrão: 0.90</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="5.0" class="form-input param-trigger" id="mult_idade_29_30" name="mult_idade_29_30" value="<?php echo htmlspecialchars($params['mult_idade_29_30']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="mult_idade_31_40">
                        31 a 40 anos
                        <span class="field-hint">Padrão: 0.80</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="5.0" class="form-input param-trigger" id="mult_idade_31_40" name="mult_idade_31_40" value="<?php echo htmlspecialchars($params['mult_idade_31_40']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="mult_idade_41_mais">
                        41 anos ou mais
                        <span class="field-hint">Padrão: 0.50</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="5.0" class="form-input param-trigger" id="mult_idade_41_mais" name="mult_idade_41_mais" value="<?php echo htmlspecialchars($params['mult_idade_41_mais']); ?>" required>
                </div>
            </div>
        </div>

        <!-- Card: Ajustes por Posição em Campo -->
        <div class="config-card">
            <div class="card-section-header">
                <h2>
                    <span class="material-symbols-outlined" style="color: #0284c7;">sports_soccer</span>
                    2. Ajustes e Pesos por Setor / Posição
                </h2>
            </div>
            <p class="card-section-desc">Defina a valorização ou desvalorização relativa de acordo com o setor tático do jogador.</p>

            <div class="form-grid-4">
                <div class="form-group">
                    <label for="ajuste_goleiro">
                        Goleiro (G)
                        <span class="field-hint">Padrão: 1.00</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="5.0" class="form-input param-trigger" id="ajuste_goleiro" name="ajuste_goleiro" value="<?php echo htmlspecialchars($params['ajuste_goleiro']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="ajuste_lateral">
                        Laterais (LD/LE)
                        <span class="field-hint">Padrão: 1.00</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="5.0" class="form-input param-trigger" id="ajuste_lateral" name="ajuste_lateral" value="<?php echo htmlspecialchars($params['ajuste_lateral']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="ajuste_zagueiro">
                        Zagueiros (Z)
                        <span class="field-hint">Padrão: 1.00</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="5.0" class="form-input param-trigger" id="ajuste_zagueiro" name="ajuste_zagueiro" value="<?php echo htmlspecialchars($params['ajuste_zagueiro']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="ajuste_ala">
                        Alas (AD/AE)
                        <span class="field-hint">Padrão: 1.00</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="5.0" class="form-input param-trigger" id="ajuste_ala" name="ajuste_ala" value="<?php echo htmlspecialchars($params['ajuste_ala']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="ajuste_volante">
                        Volantes (V)
                        <span class="field-hint">Padrão: 1.00</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="5.0" class="form-input param-trigger" id="ajuste_volante" name="ajuste_volante" value="<?php echo htmlspecialchars($params['ajuste_volante']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="ajuste_meia">
                        Meias (MD/ME/MC/MA/MEC)
                        <span class="field-hint">Padrão: 1.00</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="5.0" class="form-input param-trigger" id="ajuste_meia" name="ajuste_meia" value="<?php echo htmlspecialchars($params['ajuste_meia']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="ajuste_atacante">
                        Atacantes (PD/PE/CA)
                        <span class="field-hint">Padrão: 1.00</span>
                    </label>
                    <input type="number" step="0.01" min="0.1" max="5.0" class="form-input param-trigger" id="ajuste_atacante" name="ajuste_atacante" value="<?php echo htmlspecialchars($params['ajuste_atacante']); ?>" required>
                </div>
            </div>
        </div>

        <!-- Card: Curva de Faixas de Nível (Overall) -->
        <div class="config-card">
            <div class="card-section-header">
                <h2>
                    <span class="material-symbols-outlined" style="color: #0284c7;">trending_up</span>
                    3. Curva de Nível (Parâmetros Base A e B por Faixa)
                </h2>
            </div>
            <p class="card-section-desc">Fórmula de cada faixa: <code>Valor Base = (Parâmetro A * (Nível - Nível Mínimo)) + Parâmetro B</code></p>

            <div class="faixas-table-container">
                <table class="faixas-table">
                    <thead>
                        <tr>
                            <th>Faixa de Nível</th>
                            <th>Nível Mín - Máx</th>
                            <th>Parâmetro A (Inclinação)</th>
                            <th>Parâmetro B (Base Inicial)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Faixa 1</strong> (Iniciantes)</td>
                            <td>10 a 30</td>
                            <td><input type="number" step="0.1" class="form-input param-trigger" name="faixa1_a" id="faixa1_a" value="<?php echo htmlspecialchars($params['faixa1_a']); ?>" required></td>
                            <td><input type="number" step="0.1" class="form-input param-trigger" name="faixa1_b" id="faixa1_b" value="<?php echo htmlspecialchars($params['faixa1_b']); ?>" required></td>
                        </tr>
                        <tr>
                            <td><strong>Faixa 2</strong> (Em Desenvolvimento)</td>
                            <td>31 a 50</td>
                            <td><input type="number" step="0.1" class="form-input param-trigger" name="faixa2_a" id="faixa2_a" value="<?php echo htmlspecialchars($params['faixa2_a']); ?>" required></td>
                            <td><input type="number" step="0.1" class="form-input param-trigger" name="faixa2_b" id="faixa2_b" value="<?php echo htmlspecialchars($params['faixa2_b']); ?>" required></td>
                        </tr>
                        <tr>
                            <td><strong>Faixa 3</strong> (Intermediários)</td>
                            <td>51 a 70</td>
                            <td><input type="number" step="0.1" class="form-input param-trigger" name="faixa3_a" id="faixa3_a" value="<?php echo htmlspecialchars($params['faixa3_a']); ?>" required></td>
                            <td><input type="number" step="0.1" class="form-input param-trigger" name="faixa3_b" id="faixa3_b" value="<?php echo htmlspecialchars($params['faixa3_b']); ?>" required></td>
                        </tr>
                        <tr>
                            <td><strong>Faixa 4</strong> (Avançados)</td>
                            <td>71 a 80</td>
                            <td><input type="number" step="0.1" class="form-input param-trigger" name="faixa4_a" id="faixa4_a" value="<?php echo htmlspecialchars($params['faixa4_a']); ?>" required></td>
                            <td><input type="number" step="0.1" class="form-input param-trigger" name="faixa4_b" id="faixa4_b" value="<?php echo htmlspecialchars($params['faixa4_b']); ?>" required></td>
                        </tr>
                        <tr>
                            <td><strong>Faixa 5</strong> (Estrelas)</td>
                            <td>81 a 88</td>
                            <td><input type="number" step="0.1" class="form-input param-trigger" name="faixa5_a" id="faixa5_a" value="<?php echo htmlspecialchars($params['faixa5_a']); ?>" required></td>
                            <td><input type="number" step="0.1" class="form-input param-trigger" name="faixa5_b" id="faixa5_b" value="<?php echo htmlspecialchars($params['faixa5_b']); ?>" required></td>
                        </tr>
                        <tr>
                            <td><strong>Faixa 6</strong> (Lendas / Elite)</td>
                            <td>89 a 97</td>
                            <td><input type="number" step="0.1" class="form-input param-trigger" name="faixa6_a" id="faixa6_a" value="<?php echo htmlspecialchars($params['faixa6_a']); ?>" required></td>
                            <td><input type="number" step="0.1" class="form-input param-trigger" name="faixa6_b" id="faixa6_b" value="<?php echo htmlspecialchars($params['faixa6_b']); ?>" required></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Card: Testador / Simulador Interativo em Tempo Real -->
        <div class="config-card">
            <div class="card-section-header">
                <h2>
                    <span class="material-symbols-outlined" style="color: #0284c7;">calculate</span>
                    Simulador Interativo em Tempo Real
                </h2>
            </div>
            <p class="card-section-desc">Teste como a sua configuração afeta um atleta de exemplo antes de salvar ou recalcular.</p>

            <div class="simulator-box">
                <div class="form-grid-4">
                    <div class="form-group">
                        <label for="sim_nivel">Nível (Overall)</label>
                        <input type="number" id="sim_nivel" class="form-input" min="10" max="97" value="75">
                    </div>
                    <div class="form-group">
                        <label for="sim_idade">Idade (Anos)</label>
                        <input type="number" id="sim_idade" class="form-input" min="15" max="50" value="24">
                    </div>
                    <div class="form-group">
                        <label for="sim_posicao">Posição Principal</label>
                        <select id="sim_posicao" class="form-input">
                            <option value="0">Goleiro (G)</option>
                            <option value="1">Lateral Direito (LD)</option>
                            <option value="2">Lateral Esquerdo (LE)</option>
                            <option value="3">Zagueiro (Z)</option>
                            <option value="4">Ala Direito (AD)</option>
                            <option value="5">Ala Esquerdo (AE)</option>
                            <option value="6">Volante (V)</option>
                            <option value="7">Meia Direita (MD)</option>
                            <option value="8">Meia Esquerda (ME)</option>
                            <option value="9">Meia Central (MC)</option>
                            <option value="10">Meia Atacante (MA)</option>
                            <option value="11">Meio-Campo (MEC)</option>
                            <option value="12">Ponta Direita (PD)</option>
                            <option value="13">Ponta Esquerda (PE)</option>
                            <option value="14" selected>Centroavante (CA)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sim_falta">Cobrador de Falta?</label>
                        <select id="sim_falta" class="form-input">
                            <option value="0">Não</option>
                            <option value="1" selected>Sim</option>
                        </select>
                    </div>
                </div>

                <div class="simulator-result-box">
                    <div>
                        <div class="simulator-result-title">Valor de Passe Estimado</div>
                        <div class="simulator-result-value" id="sim_resultado_passe">$ 0</div>
                    </div>
                    <div>
                        <div class="simulator-result-title">Salário Mensal Estimado (0.5%)</div>
                        <div class="simulator-result-salario" id="sim_resultado_salario">$ 0 / mês</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="form-actions-bar">
            <button type="submit" class="btn-primary">
                <span class="material-symbols-outlined">save</span>
                Salvar Parâmetros
            </button>
        </div>
    </form>

    <!-- Formulário Restaurar Padrões -->
    <form method="POST" action="" id="formRestaurar" style="margin-top: 14px; text-align: right;" onsubmit="return confirm('Deseja realmente restaurar todos os parâmetros para os valores padrão do CONFUSA?');">
        <input type="hidden" name="acao" value="restaurar">
        <button type="submit" class="btn-danger">
            <span class="material-symbols-outlined">restart_alt</span>
            Restaurar Valores Padrão
        </button>
    </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    function calcularSimulacao() {
        const nivel = parseInt(document.getElementById('sim_nivel').value) || 75;
        const idade = parseInt(document.getElementById('sim_idade').value) || 24;
        const posIdx = parseInt(document.getElementById('sim_posicao').value) || 14;
        const cobradorFalta = parseInt(document.getElementById('sim_falta').value) || 0;

        const fatorGlobal = parseFloat(document.getElementById('fator_global').value) || 1.0;
        const bonusFalta = parseFloat(document.getElementById('bonus_falta').value) || 1.08;
        const bonusPoliv = parseFloat(document.getElementById('bonus_polivalencia').value) || 1.0;

        // Idades
        let multIdade = 1.0;
        if (idade <= 20) multIdade = parseFloat(document.getElementById('mult_idade_ate20').value) || 1.3;
        else if (idade <= 22) multIdade = parseFloat(document.getElementById('mult_idade_21_22').value) || 1.15;
        else if (idade <= 28) multIdade = parseFloat(document.getElementById('mult_idade_23_28').value) || 1.0;
        else if (idade <= 30) multIdade = parseFloat(document.getElementById('mult_idade_29_30').value) || 0.9;
        else if (idade <= 40) multIdade = parseFloat(document.getElementById('mult_idade_31_40').value) || 0.8;
        else multIdade = parseFloat(document.getElementById('mult_idade_41_mais').value) || 0.5;

        // Ajuste Posicao
        let ajustePos = 1.0;
        if (posIdx === 0) ajustePos = parseFloat(document.getElementById('ajuste_goleiro').value) || 1.0;
        else if (posIdx === 1 || posIdx === 2) ajustePos = parseFloat(document.getElementById('ajuste_lateral').value) || 1.0;
        else if (posIdx === 3) ajustePos = parseFloat(document.getElementById('ajuste_zagueiro').value) || 1.0;
        else if (posIdx === 4 || posIdx === 5) ajustePos = parseFloat(document.getElementById('ajuste_ala').value) || 1.0;
        else if (posIdx === 6) ajustePos = parseFloat(document.getElementById('ajuste_volante').value) || 1.0;
        else if (posIdx >= 7 && posIdx <= 11) ajustePos = parseFloat(document.getElementById('ajuste_meia').value) || 1.0;
        else ajustePos = parseFloat(document.getElementById('ajuste_atacante').value) || 1.0;

        // Faixas de nivel
        const faixas = [
            { min: 10, max: 30, a: parseFloat(document.getElementById('faixa1_a').value) || 2, b: parseFloat(document.getElementById('faixa1_b').value) || 10 },
            { min: 31, max: 50, a: parseFloat(document.getElementById('faixa2_a').value) || 40, b: parseFloat(document.getElementById('faixa2_b').value) || 100 },
            { min: 51, max: 70, a: parseFloat(document.getElementById('faixa3_a').value) || 175, b: parseFloat(document.getElementById('faixa3_b').value) || 675 },
            { min: 71, max: 80, a: parseFloat(document.getElementById('faixa4_a').value) || 250, b: parseFloat(document.getElementById('faixa4_b').value) || 4250 },
            { min: 81, max: 88, a: parseFloat(document.getElementById('faixa5_a').value) || 350, b: parseFloat(document.getElementById('faixa5_b').value) || 6850 },
            { min: 89, max: 97, a: parseFloat(document.getElementById('faixa6_a').value) || 400, b: parseFloat(document.getElementById('faixa6_b').value) || 9700 }
        ];

        let faixa = faixas[0];
        for (let i = 0; i < faixas.length; i++) {
            if (nivel <= faixas[i].max) {
                faixa = faixas[i];
                break;
            }
            faixa = faixas[i];
        }

        let base = (faixa.a * (nivel - faixa.min)) + faixa.b;
        let passe = base;

        if (cobradorFalta > 0) {
            passe = passe * bonusFalta;
        }

        passe = passe * multIdade;
        passe = 1000 * passe * ajustePos * bonusPoliv * fatorGlobal;

        if (passe < 0) passe = 0;
        const percSalario = parseFloat(document.getElementById('percentual_salario').value) || 0.5;
        const salario = passe * (percSalario / 100.0);

        document.getElementById('sim_resultado_passe').textContent = '$ ' + Math.round(passe).toLocaleString('pt-BR');
        document.getElementById('sim_resultado_salario').textContent = '$ ' + Math.round(salario).toLocaleString('pt-BR') + ' / mês (' + percSalario.toFixed(2) + '%)';
    }

    // Eventos nos inputs
    document.querySelectorAll('.param-trigger, #sim_nivel, #sim_idade, #sim_posicao, #sim_falta').forEach(function(el) {
        el.addEventListener('input', calcularSimulacao);
        el.addEventListener('change', calcularSimulacao);
    });

    calcularSimulacao();
});
</script>

<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/footer.php");
?>
