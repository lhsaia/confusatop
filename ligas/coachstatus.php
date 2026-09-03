<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// ID do técnico
$id_tecnico = isset($_GET['coach']) ? (int)$_GET['coach'] : (isset($_GET['tecnico']) ? (int)$_GET['tecnico'] : 0);

if ($id_tecnico <= 0) {
    header("Location: /ranking/index.php");
    exit;
}

// Estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");

$database = new Database();
$db = $database->getConnection();

$tecnicoObj = new Tecnico($db);
$paisObj = new Pais($db);
$timeObj = new Time($db);
$ligaObj = new Liga($db);

$info = $tecnicoObj->readInfo($id_tecnico);

if (empty($info['nome'])) {
    header("Location: /ranking/index.php");
    exit;
}

$nome_tecnico = $info['nome'];
$foto_tecnico = !empty($info['foto']) ? $info['foto'] : 'manager.png';
$id_pais = (int)$info['idPais'];
$nome_pais = $info['Pais'];
$bandeira_pais = $info['bandeiraPais'];
$sigla_pais = $info['siglaPais'];
$dono_pais = (int)$info['donoPais'];
$idade_tecnico = (int)$info['idade'];
$nascimento_tecnico = $info['nascimento'];
$nivel_tecnico = (int)$info['nivel'];
$mentalidade_idx = (int)$info['mentalidade'];
$estilo_idx = (int)$info['estilo'];
$sexo = (int)$info['sexo'];
$disponibilidade_tecnico = $info['disponibilidade'] ?? 1;
$data_falecimento = $info['data_falecimento'] ?? null;
$isFalecido = ((int)$disponibilidade_tecnico === -3 || !empty($data_falecimento));

// Dados do clube
$id_time = (int)$info['idTime'];
$nome_time = $info['time'];
$escudo_time = $info['escudoTime'];
$id_liga = (int)$info['idLiga'];
$nome_liga = $info['liga'];
$logo_liga = $info['logoLiga'];
$tier_liga = $info['tier'];
$pais_time = (int)$info['paisTime'];
$nome_pais_time = $info['nomePaisTime'];
$bandeira_pais_time = $info['bandeiraPaisTime'];
$dono_clube = (int)$info['donoClube'];
$desde_quando = $info['inicioContrato'];
$ate_quando = $info['fimContrato'];
$salario = (int)$info['salario'];
$mod_nivel = (int)$info['modificadorNivel'];

// Mapeamentos de Mentalidade e Estilo
$array_mentalidades = [
    0 => "Muito Defensiva",
    1 => "Defensiva",
    2 => "Normal",
    3 => "Ofensiva",
    4 => "Muito Ofensiva"
];

$array_estilos = [
    0 => "Equilibrado",
    1 => "Posse de Bola",
    2 => "Contra-Ataque",
    3 => "Pressão Alta",
    4 => "Linha Defensiva",
    5 => "Bolas Longas",
    6 => "Laterais Ofensivos"
];

$mentalidade_texto = $array_mentalidades[$mentalidade_idx] ?? "Equilibrada";
$estilo_texto = $array_estilos[$estilo_idx] ?? "Equilibrado";

$donoLogado = false;
if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $dono_pais || $_SESSION['user_id'] == $dono_clube)) {
    $donoLogado = true;
}

$page_title = $nome_tecnico . " - CONFUSA.top";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'coachstatus_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

// Formatar data de nascimento
if (!empty($nascimento_tecnico) && strpos($nascimento_tecnico, '-') !== false) {
    $nasc_parts = explode("-", $nascimento_tecnico);
    $nascimento_formatado = (count($nasc_parts) === 3) 
        ? $nasc_parts[2] . "/" . $nasc_parts[1] . "/" . $nasc_parts[0] 
        : "-";
} else {
    $nascimento_formatado = "-";
}

// Formatar início de contrato
if (!empty($desde_quando)) {
    $desde_parts = explode(" ", $desde_quando)[0];
    $desde_explode = explode("-", $desde_parts);
    $desde_formatado = (count($desde_explode) === 3) 
        ? $desde_explode[2] . "/" . $desde_explode[1] . "/" . $desde_explode[0] 
        : $desde_quando;
} else {
    $desde_formatado = "Indeterminado";
}

// Histórico de transferências
$transf_stmt = $tecnicoObj->readAllTransferencias($id_tecnico, 0, 100);
$fotoClass = $isFalecido ? 'margin-left foto-falecido' : 'margin-left';
?>

<main class='propostas-container' style='padding-top: 80px; padding-bottom: 60px;'>
    <div class='propostas-card'>
        <div id='quadro-container'>
            <div id='quadro-superior'>
                <div id='quadro-nomes'>
                    <h2>
                        <?php 
                        echo htmlspecialchars($nome_tecnico);
                        if ($isFalecido) {
                            $dataFalecFmt = !empty($data_falecimento) ? date('d/m/Y', strtotime($data_falecimento)) : "";
                            $labelFalec = $dataFalecFmt ? "Falecido em " . $dataFalecFmt : "Falecido";
                            echo " <span class='stamp stamp-falecido' title='{$labelFalec}'><span class='cruz-morte'>†</span> <svg class='luto-icon' viewBox='0 0 24 24' width='14' height='14' fill='currentColor'><path d='M12 2C8.69 2 6 4.69 6 8c0 2.21 1.2 4.15 3 5.19V22l3-2 3 2v-8.81c1.8-1.04 3-2.98 3-5.19 0-3.31-2.69-6-6-6zm0 2c2.21 0 4 1.79 4 4 0 1.25-.58 2.37-1.49 3.1-.47.38-.51 1.07-.09 1.5.42.43 1.12.43 1.58.01C17.26 11.45 18 9.82 18 8c0-3.31-2.69-6-6-6s-6 2.69-6 6c0 1.82.74 3.45 1.99 4.61.47.42 1.16.42 1.59-.01.42-.43.38-1.12-.09-1.5C8.58 10.37 8 9.25 8 8c0-2.21 1.79-4 4-4z'/></svg> " . $labelFalec . "</span>";
                        }
                        ?>
                    </h2>
                    <?php if ($id_time <= 0): ?>
                        <h3><span style="color:#64748b;"><?php echo $isFalecido ? 'Falecido' : 'Sem Clube (Disponível no Mercado)'; ?></span></h3>
                    <?php else: ?>
                        <h3>
                            <a href='paisstatus.php?country=<?php echo $pais_time; ?>'>
                                <img class='smallthumb' src='/images/bandeiras/<?php echo $bandeira_pais_time; ?>'>&nbsp;<?php echo htmlspecialchars($nome_pais_time); ?>
                            </a>
                            <a href='leaguestatus.php?league=<?php echo $id_liga; ?>'>
                                - <img class='smallthumb' src='/images/ligas/<?php echo $logo_liga; ?>'>&nbsp;<?php echo htmlspecialchars($nome_liga); ?> (Tier <?php echo $tier_liga; ?>)
                            </a>
                            <a href='teamstatus.php?team=<?php echo $id_time; ?>'>
                                - <img class='smallthumb' src='/images/escudos/<?php echo $escudo_time; ?>'>&nbsp;<?php echo htmlspecialchars($nome_time); ?>
                            </a>
                        </h3>
                    <?php endif; ?>
                </div>
                <div id='quadro-foto'>
                    <img id='bandeiraGrande' class='<?php echo $fotoClass; ?>' src='/images/tecnicos/<?php echo htmlspecialchars($foto_tecnico); ?>' onerror="this.src='/images/jogadores/avatar.png';" height='100px'>
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid rgba(0,0,0,0.08); margin: 20px 0;">

            <div id='info_geral'>
                <!-- Bloco de Informações do Treinador -->
                <div id='info-jogos'>
                    <?php
                    if ($isFalecido && !empty($data_falecimento)) {
                        $dataFalecFmt = date('d/m/Y', strtotime($data_falecimento));
                        $nascimento_display_full = "{$nascimento_formatado} &bull; &dagger; {$dataFalecFmt} ({$idade_tecnico} anos)";
                        echo "<div id='idade' class='infoblock large' title='Nascimento e Falecimento'><span class='material-symbols-outlined'>church</span><div><span class='informacao' style='font-size:0.85rem;'>{$nascimento_display_full}</span><span style='font-size: 0.75rem; color: #64748b;'>Nascimento &bull; Falecimento</span></div></div>";
                    } else {
                        echo "<div id='idade' class='infoblock' title='Idade e Data de Nascimento'><span class='material-symbols-outlined'>cake</span><div><span class='tituloinformacao'>Idade</span><span class='informacao'>{$idade_tecnico} anos ({$nascimento_formatado})</span></div></div>";
                    }
                    ?>

                    <div id='nacionalidade' class='infoblock' title='Nacionalidade'>
                        <span class="material-symbols-outlined">flag</span>
                        <div>
                            <span class='tituloinformacao'>Nacionalidade</span>
                            <span class='informacao'>
                                <a href='paisstatus.php?country=<?php echo $id_pais; ?>' style="color:#0284c7; text-decoration:none; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                                    <img src='/images/bandeiras/<?php echo $bandeira_pais; ?>' class='smallthumb'> <?php echo htmlspecialchars($nome_pais); ?>
                                </a>
                            </span>
                        </div>
                    </div>

                    <div id='nivel' class='infoblock' title='Nível Geral de Habilidade'>
                        <span class="material-symbols-outlined">military_tech</span>
                        <div>
                            <span class='tituloinformacao'>Nível do Técnico</span>
                            <span class='informacao' style="font-size: 1.1rem; color: #0284c7;">
                                <?php echo $nivel_tecnico; ?> / 10
                                <?php if ($mod_nivel != 0): ?>
                                    <small style="color: <?php echo ($mod_nivel > 0 ? '#16a34a' : '#dc2626'); ?>; font-weight:bold;">
                                        (<?php echo ($mod_nivel > 0 ? '+' : '') . $mod_nivel; ?>)
                                    </small>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <div id='mentalidade' class='infoblock' title='Mentalidade Tática'>
                        <span class="material-symbols-outlined">psychology</span>
                        <div>
                            <span class='tituloinformacao'>Mentalidade</span>
                            <span class='informacao'><?php echo htmlspecialchars($mentalidade_texto); ?></span>
                        </div>
                    </div>

                    <div id='estilo' class='infoblock' title='Estilo de Jogo Predominante'>
                        <span class="material-symbols-outlined">sports</span>
                        <div>
                            <span class='tituloinformacao'>Estilo de Jogo</span>
                            <span class='informacao'><?php echo htmlspecialchars($estilo_texto); ?></span>
                        </div>
                    </div>

                    <div id='contrato' class='infoblock' title='Vínculo com o Clube Atual'>
                        <span class="material-symbols-outlined">contract</span>
                        <div>
                            <span class='tituloinformacao'>Início do Contrato</span>
                            <span class='informacao'><?php echo $desde_formatado; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Bloco Lateral: Clube Atual -->
                <div id='info-clube'>
                    <div class='infoblock' style="flex-direction: column; align-items: flex-start; gap: 12px;">
                        <span class='tituloinformacao' style="font-size: 1rem; color: #0f172a; font-weight: 700;">Situação Atual</span>
                        <?php if ($isFalecido): ?>
                            <p style="color: #64748b; font-size: 0.9rem; margin: 0; display:flex; align-items:center; gap:6px;">
                                <span class='cruz-morte'>†</span> Treinador falecido.
                            </p>
                        <?php elseif ($id_time > 0): ?>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src='/images/escudos/<?php echo $escudo_time; ?>' style="width: 48px; height: 48px; object-fit: contain;">
                                <div>
                                    <a href='teamstatus.php?team=<?php echo $id_time; ?>' style="font-size: 1.1rem; font-weight: 700; color: #0284c7; text-decoration: none;">
                                        <?php echo htmlspecialchars($nome_time); ?>
                                    </a>
                                    <span style="font-size: 0.85rem; color: #64748b;"><?php echo htmlspecialchars($nome_liga); ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <p style="color: #64748b; font-size: 0.9rem; margin: 0;">Treinador sem clube no momento. Disponível para contratação e propostas.</p>
                        <?php endif; ?>

                        <?php if ($donoLogado): ?>
                            <div style="margin-top: 10px; width: 100%;">
                                <a href="/usuario/meustecnicos.php" class="btn" style="display: block; text-align: center; background: #0284c7; color: #fff; padding: 10px; border-radius: 8px; font-weight: 600; text-decoration: none; font-family: 'Outfit', sans-serif;">
                                    ⚙️ Gerenciar Técnico
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Seção de Histórico de Clubes / Transferências -->
            <h3 class="secao-titulo">
                <span class="material-symbols-outlined">history</span> Histórico de Clubes e Contratos
            </h3>

            <div class="tbl_user_data" style="margin-top: 15px;">
                <table id='tabelaElenco' class='table'>
                    <thead>
                        <tr>
                            <th style="width: 25%;">Data</th>
                            <th style="width: 37%;">Saiu de</th>
                            <th style="width: 38%;">Foi para</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $hasTransf = false;
                        if ($transf_stmt) {
                            while ($row = $transf_stmt->fetch(PDO::FETCH_ASSOC)) {
                                $hasTransf = true;
                                $data_raw = explode(" ", $row['data'])[0];
                                $d_parts = explode("-", $data_raw);
                                $data_fmt = (count($d_parts) === 3) ? $d_parts[2] . "/" . $d_parts[1] . "/" . $d_parts[0] : $data_raw;

                                $stamp = "<span class='stamp stamp-definitiva'>Contratado</span>";
                                if ($row['idDestino'] == 0) {
                                    $stamp = "<span class='stamp stamp-fim'>Demitido / Fim</span>";
                                }

                                echo "<tr>";
                                echo "<td class='nopadding'>{$data_fmt} {$stamp}</td>";

                                // Origem
                                echo "<td class='nopadding'>";
                                if (!empty($row['idOrigem']) && $row['idOrigem'] > 0) {
                                    echo "<a href='/ligas/teamstatus.php?team={$row['idOrigem']}' style='display:inline-flex; align-items:center; gap:6px; color:#0284c7; text-decoration:none; font-weight:600;'>";
                                    echo "<img src='/images/escudos/{$row['escudoOrigem']}' class='minithumb' onerror=\"this.src='/images/escudos/shield.png';\" /> " . htmlspecialchars($row['nomeOrigem']);
                                    echo "</a>";
                                    if (!empty($row['idLigaOrigem'])) {
                                        echo "<br/><a class='posicao' href='/ligas/leaguestatus.php?league={$row['idLigaOrigem']}' style='font-size:0.8rem; color:#64748b; text-decoration:none;'>";
                                        echo "<img src='/images/bandeiras/{$row['bandeiraOrigem']}' class='minithumb' /> " . htmlspecialchars($row['nomeLigaOrigem']);
                                        echo "</a>";
                                    }
                                } else {
                                    echo "<span style='color:#94a3b8;'>Sem Clube</span>";
                                }
                                echo "</td>";

                                // Destino
                                echo "<td class='nopadding'>";
                                if (!empty($row['idDestino']) && $row['idDestino'] > 0) {
                                    echo "<a href='/ligas/teamstatus.php?team={$row['idDestino']}' style='display:inline-flex; align-items:center; gap:6px; color:#0284c7; text-decoration:none; font-weight:600;'>";
                                    echo "<img src='/images/escudos/{$row['escudoDestino']}' class='minithumb' onerror=\"this.src='/images/escudos/shield.png';\" /> " . htmlspecialchars($row['nomeDestino']);
                                    echo "</a>";
                                    if (!empty($row['idLigaDestino'])) {
                                        echo "<br/><a class='posicao' href='/ligas/leaguestatus.php?league={$row['idLigaDestino']}' style='font-size:0.8rem; color:#64748b; text-decoration:none;'>";
                                        echo "<img src='/images/bandeiras/{$row['bandeiraDestino']}' class='minithumb' /> " . htmlspecialchars($row['nomeLigaDestino']);
                                        echo "</a>";
                                    }
                                } else {
                                    echo "<span style='color:#94a3b8;'>Sem Clube (Disponível)</span>";
                                }
                                echo "</td>";

                                echo "</tr>";
                            }
                        }

                        if (!$hasTransf) {
                            echo "<tr><td colspan='3' style='text-align: center; color: #94a3b8; padding: 2rem;'>Nenhum histórico de transferências registrado para este treinador.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</main>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
