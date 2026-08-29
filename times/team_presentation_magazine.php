<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$records_per_page = 100;
$from_record_num = 0;

// Estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/federacoes.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$jogador = new Jogador($db);
$time = new Time($db);
$tecnico = new Tecnico($db);

$id = $_GET['team'] ?? 0;
$idTime = $id;

// Query times
$info = $time->readInfo($id);
$nome_time = $info['Nome'] ?? '';
$sigla_time = $info['TresLetras'] ?? '';
$estadio_time = $info['Estadio'] ?? '';
$estadio_capacidade = $info['Capacidade'] ?? 0;
$escudo_time = $info['Escudo'] ?? '';
$foto_estadio = $info['fotoEstadio'] ?? '';
$uniforme1_time = $info['Uniforme1'] ?? '';
$uniforme2_time = $info['Uniforme2'] ?? '';
$pais_time = $info['Pais'] ?? '';
$liga_time = $info['liga'] ?? '';
$donoPais = $info['donoPais'] ?? null;

$extra_info = $time->readExtraInfo($id);
$apelido_time = $extra_info['apelido'] ?? '';
$fundacao_time = $extra_info['fundacao'] ?? '';
$cidade_time = $extra_info['cidade'] ?? '';
$patrocinio_time = $extra_info['patrocinio'] ?? '';
$material_esportivo_time = $extra_info['material_esportivo'] ?? '';
$titulos_time = $extra_info['titulos'] ?? '';
$sobre_titulo = $extra_info['sobre_titulo'] ?? '';
$sobre_subtitulo = $extra_info['sobre_subtitulo'] ?? '';
$sobre_texto = $extra_info['sobre_texto'] ?? '';
$mascote_time = (!empty($extra_info['mascote']) && $extra_info['mascote'] != 'null') ? '/images/mascotes/' . $extra_info['mascote'] : '/images/mascotes/placeholder.png';
$foto_destaque = $extra_info['foto_destaque'] ?? '';

if(empty($foto_destaque)) {
    $foto_destaque = 'placeholder.png';
}

if(empty($titulos_time)){
	$titulos_time = "14x Campeonato Nacional Serie A, 2x Campeonato Nacional Serie B, 3x Taça Nacional, 1x Campeonato Continental";
}
if(empty($sobre_titulo)){
	$sobre_titulo = "MAL ACOSTUMADOS A PERDER";
}
if(empty($sobre_subtitulo)){
	$sobre_subtitulo = "Jejum que já dura 5 anos incomoda torcedores e diretoria.";
}
if(empty($sobre_texto)){
	$sobre_texto = "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum dapibus mauris eget tristique mattis. Nullam efficitur euismod bibendum. Mauris ultricies sed dui non gravida. Praesent non sem malesuada, tincidunt diam ut, interdum sem. Proin sit amet luctus lacus, ut pulvinar orci. Morbi auctor consequat eros sit amet feugiat. Maecenas vitae enim ac lorem viverra commodo.</p>
<p>Maecenas dolor leo, varius eget dignissim eu, maximus nec nisi. Nunc id odio vitae purus pellentesque congue mattis in ligula. Maecenas vulputate dolor in augue dignissim rutrum. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.</p>";
}

if(isset($_SESSION['user_id']) && $donoPais == $_SESSION["user_id"]){
    $donoLogado = true;
} else {
    $donoLogado = false;
}

$editable = $donoLogado ? "true" : "false";

$page_title = $nome_time . " - Apresentação Placar";
$css_filename = "team_presentation_magazine";
$css_login = 'login';
$aux_css = 'home_redesign';
$css_versao = date('h:i:s');

include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

function aplicarCapitular($html) {
    if (empty($html)) return $html;
    $trimmed = trim($html);
    if (strpos($trimmed, 'class="capitular"') !== false || strpos($trimmed, "class='capitular'") !== false) {
        return $html;
    }
    $pattern = '/^((?:<[^>]+>)*\s*)([\p{L}\p{N}])/u';
    $replacement = '$1<span class="capitular">$2</span>';
    return preg_replace($pattern, $replacement, $html, 1);
}

function abreviarNomeClube($nome) {
    if (empty($nome)) return '';
    $substituicoes = [
        'Red Bull' => 'RB',
        'Esporte Clube' => 'EC',
        'Sport Club' => 'SC',
        'Futebol Clube' => 'FC',
        'Football Club' => 'FC',
        'Futebol e Regatas' => 'FR',
        'Associação Atlética' => 'AA',
        'Associação Atletica' => 'AA',
        'Club de Regatas' => 'CR',
        'Clube de Regatas' => 'CR',
        'Athletico Paranaense' => 'Athletico-PR',
        'Atlético Paranaense' => 'Atlético-PR',
        'Manchester United' => 'Man. United',
        'Manchester City' => 'Man. City',
        'Borussia Dortmund' => 'B. Dortmund',
        'Atlético de Madrid' => 'Atl. de Madrid',
        'Atletico de Madrid' => 'Atl. de Madrid',
        'Atlético Madrid' => 'Atl. Madrid',
        'Atletico Madrid' => 'Atl. Madrid',
        'Independiente' => 'Indep.',
        'Real Sociedad' => 'R. Sociedad',
    ];
    foreach ($substituicoes as $longo => $curto) {
        $nome = str_ireplace($longo, $curto, $nome);
    }
    if (mb_strlen($nome) > 18) {
        $nome = str_ireplace('Clube Atlético', 'Atlético', $nome);
        $nome = str_ireplace('Club Atlético', 'Atlético', $nome);
        $nome = str_ireplace('Deportivo', 'Dep.', $nome);
        $nome = str_ireplace('Universitario', 'Univ.', $nome);
    }
    return $nome;
}

function encurtarNomeClubeExtremo($nome, $limite = 14) {
    if (empty($nome)) return '';
    $nome = trim($nome);
    if (mb_strlen($nome) <= $limite) {
        return $nome;
    }
    $palavras = explode(' ', $nome);
    if (count($palavras) <= 1) {
        return mb_substr($nome, 0, $limite - 1) . '.';
    }
    while (count($palavras) > 1) {
        array_pop($palavras);
        $tentativa = implode(' ', $palavras);
        if (mb_strlen($tentativa) <= $limite - 1) {
            return $tentativa . '.';
        }
    }
    return mb_substr($palavras[0], 0, $limite - 1) . '.';
}

function rgb2hsl($r,$g,$b){
    $r = is_numeric($r) ? (float)$r : 0;
    $g = is_numeric($g) ? (float)$g : 0;
    $b = is_numeric($b) ? (float)$b : 0;
    $r/=255;$g/=255;$b/=255;$max=max($r,$g,$b);$min=min($r,$g,$b);$h = 0;$s = 0;$l=($max+$min)/2;$d=$max-$min;if($d!=0){$s=$d/(1-abs(2*$l-1));switch($max){case $r:$h=60*fmod((($g-$b)/$d),6);if($b>$g){$h+=360;}break;case $g:$h=60*(($b-$r)/$d+2);break;case $b:$h=60*(($r-$g)/$d+4);break;}}return[round($h,0),round($s*100,0),round($l*100,0)];
}

$uni1cor1 = (!empty($info["Uni1Cor1"]) && strlen($info["Uni1Cor1"]) === 9 && is_numeric($info["Uni1Cor1"])) ? $info["Uni1Cor1"] : "255255255";
$uni1cor2 = (!empty($info["Uni1Cor2"]) && strlen($info["Uni1Cor2"]) === 9 && is_numeric($info["Uni1Cor2"])) ? $info["Uni1Cor2"] : "000000000";

$pre_color1 = "rgb(".substr($uni1cor1,0,3).",".substr($uni1cor1,3,3).",".substr($uni1cor1,6,3).")";
$pre_color2 = "rgb(".substr($uni1cor2,0,3).",".substr($uni1cor2,3,3).",".substr($uni1cor2,6,3).")";

$lum_color1 = rgb2hsl(substr($uni1cor1,0,3),substr($uni1cor1,3,3),substr($uni1cor1,6,3))[2];
$lum_color2 = rgb2hsl(substr($uni1cor2,0,3),substr($uni1cor2,3,3),substr($uni1cor2,6,3))[2];

if($lum_color1 > $lum_color2){
	$color1 = $pre_color2;
	$color2 = $pre_color1;
} else {
	$color1 = $pre_color1;
	$color2 = $pre_color2;
}

$time_stmt = $jogador->selecionarElencoTime($id,$from_record_num,$records_per_page);

$lista_titulares = array();
$lista_reservas = array();
$lista_suplentes = array();

while ($row = $time_stmt->fetch(PDO::FETCH_ASSOC)){
    extract($row);
    $Nascimento = date("d-m-Y", strtotime($Nascimento));

    if($posicaoBase == 0){
        $posicaoBase = '';
    } else {
        $posicaoBase = $jogador->nomePosicaoPorCodigo($posicaoBase);
    }

    $stringPosicoes = $jogador->listaPosicoes($StringPosicoes);

    switch($titularidade){
        case 1:
            $titular = 'titular';
            break;
        case 0:
            $titular = 'reserva';
            break;
        case -1:
            $titular = 'suplente';
            break;
        default:
            $titular = 'suplente';
            break;
    }

    if($titular == 'titular'){
        $lista_titulares[] = [
            'nome' => $nomeJogador, 
            'nivel' => $Nivel, 
            'mod' => $ModificadorNivel, 
            'posicaoBase' => $posicaoBase, 
            'stringPosicoes' => $stringPosicoes, 
            'idJogador' => $idJogador, 
            'mentalidade' => $mentalidade, 
            'capitao' => $capitao, 
            'cobrancaPenalti' => $cobrancaPenalti, 
            'cobradorFalta' => $cobradorFalta, 
            'foto' => $foto, 
            'nascimento' => $Nascimento, 
            'nacionalidade' => $bandeiraPais
        ];
    } else if($titular == 'reserva'){
        $lista_reservas[] = [
            'nome' => $nomeJogador, 
            'nivel' => $Nivel, 
            'mod' => $ModificadorNivel, 
            'posicaoBase' => $posicaoBase, 
            'stringPosicoes' => $stringPosicoes, 
            'idJogador' => $idJogador
        ];
    } else {
        $lista_suplentes[] = [
            'nome' => $nomeJogador, 
            'nivel' => $Nivel, 
            'mod' => $ModificadorNivel, 
            'posicaoBase' => $posicaoBase, 
            'stringPosicoes' => $stringPosicoes, 
            'idJogador' => $idJogador
        ];
    }
}

?>

<div id="magazine-container">
    
    <!-- Toolbar de Ações Integrado na Página -->
    <div id="toolbar-card-container">
        <div class="toolbar-actions">
            <button id="irDetalhes" class="btn-action">
                <span class="material-symbols-outlined">assignment</span>
                <span>Detalhes</span>
            </button>
            <?php if($donoLogado): ?>
                <button id="salvarDados" class="btn-action btn-save">
                    <span class="material-symbols-outlined">save</span>
                    <span>Salvar</span>
                </button>
                <input type="file" id="fotoDestaqueInput" style="display:none;" accept="image/*">
            <?php endif; ?>
            <button id="tirarPrint" class="btn-action btn-print">
                <span class="material-symbols-outlined">print</span>
                <span>Print/Capturar</span>
            </button>
        </div>
    </div>

    <!-- Cabeçalho Estilo Capa Placar -->
    <header class="magazine-cover" style="background: linear-gradient(135deg, <?php echo $color1; ?> 0%, rgba(9,13,22,0.95) 100%);">
        <div class="cover-stripe" style="background-color: <?php echo $color2; ?>"></div>
        <div class="cover-grid">
            <div class="cover-escudo">
                <img src="/images/escudos/<?php echo $escudo_time; ?>" alt="Escudo">
            </div>
            <div class="cover-info">
                <h1 class="magazine-title" style="color: #ffffff; text-shadow: 2px 2px 0px <?php echo $color1; ?>;"><?php echo mb_strtoupper($nome_time); ?></h1>
                <p class="magazine-subtitle">
                    <span class="cidade-text" contenteditable="<?php echo $editable; ?>" id="cidadeText"><?php echo $cidade_time; ?></span> 
                    &middot; 
                    <span class="fundacao-text" contenteditable="<?php echo $editable; ?>" id="fundacaoText"><?php echo $fundacao_time; ?></span>
                </p>
                <div class="magazine-meta-badges">
                    <div class="badge-item">
                        <span class="badge-label">Apelido</span>
                        <span class="badge-val" contenteditable="<?php echo $editable; ?>" id="apelidoText"><?php echo $apelido_time; ?></span>
                    </div>
                    <div class="badge-item">
                        <span class="badge-label">Patrocínio</span>
                        <span class="badge-val" contenteditable="<?php echo $editable; ?>" id="patrocinioText"><?php echo $patrocinio_time; ?></span>
                    </div>
                    <div class="badge-item">
                        <span class="badge-label">Fornecedor</span>
                        <span class="badge-val" contenteditable="<?php echo $editable; ?>" id="materialText"><?php echo $material_esportivo_time; ?></span>
                    </div>
                </div>
            </div>
            <div class="cover-quick-stats">
                <div class="stadium-cover-card">
                    <?php if(!empty($foto_estadio)): ?>
                        <img src="/images/estadios/<?php echo htmlspecialchars($foto_estadio); ?>" class="stadium-cover-img" alt="Estádio">
                    <?php endif; ?>
                    <div class="stadium-cover-overlay">
                        <span class="stadium-cover-name"><?php echo $estadio_time; ?></span>
                        <span class="stadium-cover-cap"><?php echo number_format($estadio_capacidade, 0, ',', '.'); ?> torcedores</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="cover-trophies">
            <div class="trophy-icon-wrapper" style="color: <?php echo $color2; ?>">
                <span class="material-symbols-outlined">emoji_events</span>
            </div>
            <div class="trophy-text-wrapper">
                <span class="trophies-desc" contenteditable="<?php echo $editable; ?>" id="titulosText"><?php echo $titulos_time; ?></span>
            </div>
        </div>
    </header>

    <!-- Linha de Matéria Editorial & Destaque Portrait -->
    <section class="magazine-editorial-section">
        <div class="editorial-columns">
            <article class="editorial-article">
                <h2 class="editorial-headline" contenteditable="<?php echo $editable; ?>" id="sobreTitulo"><?php echo $sobre_titulo; ?></h2>
                <h3 class="editorial-deck" contenteditable="<?php echo $editable; ?>" id="sobreSubtitulo" style="color: <?php echo $color1; ?>"><?php echo $sobre_subtitulo; ?></h3>
                <div class="editorial-body text-columns" contenteditable="<?php echo $editable; ?>" id="aboutTeam">
                    <?php echo aplicarCapitular($sobre_texto); ?>
                </div>
            </article>
            
            <aside class="editorial-highlight-photo">
                <div class="highlight-frame">
                    <div class="highlight-label" style="background-color: <?php echo $color1; ?>; color: <?php echo $color2; ?>">DESTAQUE DO CLUBE</div>
                    <div class="highlight-img-container" id="destaqueImgWrapper">
                        <img id="destaquePhoto" src="/images/destaques/<?php echo $foto_destaque; ?>" alt="Destaque">
                        <?php if($donoLogado): ?>
                            <div class="upload-overlay">
                                <span class="material-symbols-outlined">photo_camera</span>
                                <span>Alterar Destaque</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <!-- Sessão Tática & Informações Extras -->
    <section class="magazine-tactical-section">
        <div class="tactical-grid">
            <div class="tactical-field-card">
                <div class="card-header-stripe" style="background-color: <?php echo $color1; ?>; color: <?php echo $color2; ?>">
                    <span>ESCALAÇÃO TÁTICA</span>
                </div>
                <div class="field-container">
                    <div id="soccerfield"></div>
                </div>
            </div>
            
            <div class="team-extra-graphics">
                <div class="graphics-card">
                    <div class="card-header-stripe" style="background-color: <?php echo $color1; ?>; color: <?php echo $color2; ?>">
                        <span>MASCOTE E UNIFORMES</span>
                    </div>
                    <div class="graphics-content">
                        <div class="graphic-item">
                            <span class="graphic-label">Mascote</span>
                            <img src="<?php echo $mascote_time; ?>" class="graphic-img mascot-img" alt="Mascote">
                        </div>
                        <div class="graphic-item">
                            <span class="graphic-label">Uniforme Oficial</span>
                            <div class="uniforms-row">
                                <img src="/images/uniformes/<?php echo $uniforme1_time; ?>" class="graphic-img-uniform" alt="Uniforme 1">
                                <img src="/images/uniformes/<?php echo $uniforme2_time; ?>" class="graphic-img-uniform" alt="Uniforme 2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Elenco Titular -->
    <section class="magazine-squad-section">
        <div class="section-title-editorial">
            <h2>TITULARES</h2>
            <div class="line-deco"></div>
        </div>
        <div class="players-grid">
            <?php foreach($lista_titulares as $ficha): 
                $posicao = $ficha['posicaoBase'];
                $mapa_abreviado = [
                    "Lateral-direito" => "Lat. Direito",
                    "Lateral-esquerdo" => "Lat. Esquerdo",
                    "Meia-atacante" => "Meia-Atac.",
                    "Meia central" => "Meia Central",
                    "Atacante de movimentação" => "Atacante",
                    "Atacante de área" => "Atacante",
                    "Goleiro" => "Goleiro",
                    "Zagueiro" => "Zagueiro",
                    "Volante" => "Volante",
                    "Meia direito" => "Meia Direito",
                    "Meia esquerdo" => "Meia Esquerdo"
                ];
                if (isset($mapa_abreviado[$posicao])) {
                    $posicao = $mapa_abreviado[$posicao];
                } else if(strpos($posicao, "Atacante") !== false){
                    $posicao = "Atacante";
                }
                
                $dadosTransferencia = $jogador->ultimaTransferencia($ficha['idJogador'], $idTime);
                $anoChegada = isset($dadosTransferencia["Data"]) ? substr($dadosTransferencia["Data"],-4) : date("Y");
                
                if(strlen($ficha['nome'])>16){
                    $temp_nome = explode(" ", $ficha["nome"]);
                    $sobrenome_jogador = end($temp_nome);
                    $primeira_letra = mb_substr($temp_nome[0], 0 ,1);
                    $nomeAbreviado = $primeira_letra . ". " . $sobrenome_jogador;
                } else {
                    $nomeAbreviado = $ficha['nome'];
                }
            ?>
                <div class="player-sticker-card">
                    <div class="sticker-header" style="background: <?php echo $color1; ?>; color: <?php echo $color2; ?>">
                        <span class="sticker-pos"><?php echo mb_strtoupper($posicao); ?></span>
                        <img class="sticker-flag" src="/images/bandeiras/<?php echo $ficha['nacionalidade']; ?>" alt="Nacionalidade">
                    </div>
                    <div class="sticker-photo-container">
                        <img src="/images/jogadores/<?php echo $ficha['foto']; ?>" alt="<?php echo $ficha['nome']; ?>">
                    </div>
                    <div class="sticker-footer">
                        <span class="sticker-name"><?php echo mb_strtoupper($nomeAbreviado); ?></span>
                        <div class="sticker-details">
                            <span>Nasc: <?php echo $ficha['nascimento']; ?></span>
                            <span>Desde: <?php echo $anoChegada; ?></span>
                        </div>
                        <div class="sticker-history">
                            <div class="history-title">Clubes anteriores</div>
                            <div class="history-items">
                                <?php 
                                $stmtTransf = $jogador->readTransferencias(0, 10, $ficha['idJogador']);
                                $transfList = [];
                                while ($rowTransf = $stmtTransf->fetch(PDO::FETCH_ASSOC)) {
                                    $transfList[] = $rowTransf;
                                }
                                
                                $prevClubsRanges = [];
                                for ($i = 0; $i < count($transfList); $i++) {
                                    $clubName = trim($transfList[$i]['nomeOrigem'] ?? '');
                                    if (empty($clubName) || strcasecmp($clubName, 'Sem clube') === 0 || strcasecmp($clubName, $nome_time) === 0) {
                                        continue;
                                    }
                                    if (isset($prevClubsRanges[$clubName])) {
                                        continue;
                                    }
                                    
                                    $exitYear = isset($transfList[$i]['data']) ? date("Y", strtotime($transfList[$i]['data'])) : '';
                                    $arrivalYear = null;
                                    
                                    for ($j = $i + 1; $j < count($transfList); $j++) {
                                        $destClub = trim($transfList[$j]['nomeDestino'] ?? '');
                                        if (strcasecmp($destClub, $clubName) === 0) {
                                            $arrivalYear = isset($transfList[$j]['data']) ? date("Y", strtotime($transfList[$j]['data'])) : null;
                                            break;
                                        }
                                    }
                                    
                                    if ($arrivalYear) {
                                        $rangeStr = $arrivalYear . "-" . $exitYear;
                                    } else {
                                        // If arrival is unknown, we can see if it's the oldest transfer in history and check if it came from Sem clube
                                        $rangeStr = $exitYear; // fallback to just the year if range start is unknown
                                    }
                                    
                                    $prevClubsRanges[$clubName] = $rangeStr;
                                    if (count($prevClubsRanges) >= 3) {
                                        break;
                                    }
                                }
                                
                                if (!empty($prevClubsRanges)) {
                                    foreach ($prevClubsRanges as $club => $range) {
                                        echo "<div class='history-line'>" . htmlspecialchars(encurtarNomeClubeExtremo(abreviarNomeClube($club), 14)) . " (" . $range . ")</div>";
                                    }
                                } else {
                                    echo "<div class='history-line'>-</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Rodapé: Reservas & Técnico -->
    <footer class="magazine-footer-section">
        <div class="footer-grid">
            <!-- Técnico -->
            <div class="coach-editorial-card">
                <div class="card-header-stripe" style="background-color: <?php echo $color1; ?>; color: <?php echo $color2; ?>">
                    <span>COMISSÃO TÉCNICA</span>
                </div>
                <?php 
                $stmtTec = $tecnico->infoTecnico($idTime);
                $rowTec = $stmtTec->fetch(PDO::FETCH_ASSOC);

                if ($rowTec && is_array($rowTec)):
                    $transferenciaTecnico = $tecnico->ultimaTransferencia($rowTec['ID'], $idTime);
                    $desdeTecnico = isset($transferenciaTecnico["Data"]) ? substr($transferenciaTecnico["Data"], -4) : date("Y");
                    
                    if(strlen($rowTec['Nome'])>16){
                        $temp_nome = explode(" ", $rowTec["Nome"]);
                        $sobrenome_tec = end($temp_nome);
                        $primeira_letra = mb_substr($temp_nome[0], 0,1);
                        $nomeAbreviadoTec = $primeira_letra . ". " . $sobrenome_tec;
                    } else {
                        $nomeAbreviadoTec = $rowTec['Nome'];
                    }
                ?>
                    <div class="coach-content">
                        <div class="coach-photo">
                            <a href="/ligas/coachstatus.php?coach=<?php echo $rowTec['ID']; ?>"><img src="/images/tecnicos/<?php echo $rowTec['foto']; ?>" alt="<?php echo $rowTec['Nome']; ?>"></a>
                        </div>
                        <div class="coach-info">
                            <span class="coach-role">Técnico Principal</span>
                            <span class="coach-name"><a href="/ligas/coachstatus.php?coach=<?php echo $rowTec['ID']; ?>" style="color: inherit; text-decoration: none;"><?php echo mb_strtoupper($nomeAbreviadoTec); ?></a></span>
                            <span class="coach-meta">Nascimento: <?php echo $rowTec['Nascimento']; ?></span>
                            <span class="coach-meta">No clube desde: <?php echo $desdeTecnico; ?></span>
                            <img class="coach-flag" src="/images/bandeiras/<?php echo $rowTec['bandeiraPais']; ?>" alt="País">
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Reservas -->
            <div class="reserves-editorial-card">
                <div class="card-header-stripe" style="background-color: <?php echo $color1; ?>; color: <?php echo $color2; ?>">
                    <span>BANCO DE RESERVAS</span>
                </div>
                <div class="reserves-list">
                    <?php foreach($lista_reservas as $ficha): 
                        if(strlen($ficha['nome'])>14 && strpos($ficha['nome'], ' ')){
                            $temp_nome = explode(" ", $ficha["nome"]);
                            $sobrenome_jogador = end($temp_nome);
                            $primeira_letra = mb_substr($temp_nome[0],0,1);
                            $nomeAbreviado = $primeira_letra . ". " . $sobrenome_jogador;
                        } else {
                            $nomeAbreviado = $ficha['nome'];
                        }
                        $pos = explode("-", $ficha['stringPosicoes'])[0];
                    ?>
                        <span class="reserve-item-tag">
                            <strong><?php echo $nomeAbreviado; ?></strong> (<?php echo $pos; ?>)
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </footer>

</div>

<script src="/js/dom-to-image.min.js"></script>
<script src="/js/FileSaver.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
$(document).ready(function(){
	$("#security-test").remove();
	
	var perguntarSaida = false;
	var donoLogado = <?php echo $donoLogado ? 1 : 0 ?>;
	
	$("#irDetalhes").on("click", function(){
		window.location = "/ligas/teamstatus.php?team=" + <?php echo $idTime ?>;
	});
	
	if(donoLogado){
        // Trigger upload
        $("#destaqueImgWrapper").on("click", function(e){
            $("#fotoDestaqueInput").click();
        });

        $("#fotoDestaqueInput").on("change", function(){
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e){
                    $("#destaquePhoto").attr("src", e.target.result);
                };
                reader.readAsDataURL(file);
                perguntarSaida = true;
            }
        });

		$("span, h2, h3, #aboutTeam").on('DOMSubtreeModified', function () {
			if(!perguntarSaida){
				window.addEventListener("beforeunload", function (e) {
                    var confirmationMessage = "\o/";
                    e.returnValue = confirmationMessage;
                    return confirmationMessage;
                });
				perguntarSaida = true;
			}
		});
		
		$('#salvarDados').click(function(){
			let cidade = $("#cidadeText").text();
			let fundacao = $("#fundacaoText").text();
			let apelido = $("#apelidoText").text();
			let patrocinio = $("#patrocinioText").text();
			let material_esportivo = $("#materialText").text();
			let titulos = $("#titulosText").text();
			let sobre_titulo = $("#sobreTitulo").text();
			let sobre_subtitulo = $("#sobreSubtitulo").text();
			let sobre_texto = $("#aboutTeam").html();
			let idTime = <?php echo $idTime ?>;

			var formData = new FormData();
			formData.append('id', idTime);
			formData.append('cidade', cidade);
			formData.append('fundacao', fundacao);
			formData.append('apelido', apelido);
			formData.append('patrocinio', patrocinio);
			formData.append('material_esportivo', material_esportivo);
			formData.append('titulos', titulos);
			formData.append('sobre_titulo', sobre_titulo);
			formData.append('sobre_subtitulo', sobre_subtitulo);
			formData.append('sobre_texto', sobre_texto);

            // Adiciona foto de destaque se houver alteração
            const fileInput = document.getElementById('fotoDestaqueInput');
            if (fileInput && fileInput.files.length > 0) {
                formData.append('foto_destaque', fileInput.files[0]);
            }

            $.ajax({
                url: 'alterar_sobre_time_magazine.php',
                processData: false,
                contentType: false,
                cache: false,
                type: "POST",
                dataType: 'json',
                data: formData,
                success: function(data) {
                    if(data.error != ''){
                        alert(data.error)
                    }
                    perguntarSaida = false;
                    window.location.reload();
                },
                error: function(data) {
                    alert("Erro, o procedimento não foi realizado, tente novamente.");
                }
            });
		});
	}

    $("#tirarPrint").on("click", function(){
        $("#top-bar").children().toggle();
        $("#bottom-bar").children().toggle();
        $("#toolbar-card-container").toggle();
        
        setTimeout(function() {
            html2canvas(document.getElementById("magazine-container"), {
                useCORS: true,
                scale: 2,
                backgroundColor: "#f1f5f9"
            }).then(function (canvas) {
                canvas.toBlob(function (blob) {
                    window.saveAs(blob, "Revista_<?php echo $nome_time ?>.png");
                    $("#top-bar").children().toggle();
                    $("#bottom-bar").children().toggle();
                    $("#toolbar-card-container").toggle();
                }, "image/png");
            }).catch(function (error) {
                console.error("Oops, html2canvas failed!", error);
                alert("Erro ao capturar a imagem da revista. Detalhe: " + error);
                $("#top-bar").children().toggle();
                $("#bottom-bar").children().toggle();
                $("#toolbar-card-container").toggle();
            });
        }, 100);
    });

	var soccerfieldData = [
	<?php 
	$zagueiro = 0; $volante = 0; $meia = 0; $armador = 0; $atacante = 0;
	$zagueiro_at = 1; $volante_at = 1; $meia_at = 1; $armador_at = 1; $atacante_at = 1;
	
	foreach($lista_titulares as $jogador_tabela){
		switch($jogador_tabela['posicaoBase']){
			case "Zagueiro": $zagueiro++; break;
			case "Volante": $volante++; break;
			case "Meia central": $meia++; break;
			case "Meia-atacante": $armador++; break;
			case "Atacante de movimentação": $atacante++; break;
			case "Atacante de área": $atacante++; break;
        }
	}
	
	foreach($lista_titulares as $jogador_tabela){
		$dicionario_posicoes = [
			"Goleiro" => "C_GK",
			"Lateral-direito" => "R_B",
			"Lateral-esquerdo" => "L_B",
			"Zagueiro" => "_B",
			"Ala esquerdo" => "L_DM",
			"Ala direito" => "R_DM",
			"Volante" => "_DM",
			"Meia esquerdo" => "L_M",
			"Meia direito" => "R_M",
			"Meia central" => "_M",
			"Meia-atacante" => "_AM",
			"Ponta direita" => "R_F",
			"Ponta esquerda" => "L_F",
			"Atacante de área" => "_F",
			"Atacante de movimentação" => "_F",
		];
		
		if(strlen($jogador_tabela["nome"]) > 12 && strpos($jogador_tabela["nome"], " ")){
			$temp_nome = explode(" ", $jogador_tabela["nome"]);
			$sobrenome_jogador = end($temp_nome);
			$primeira_letra = mb_substr($temp_nome[0],0,1);
			$nome_final = $primeira_letra . ". " . $sobrenome_jogador;
		} else {
			$nome_final = $jogador_tabela["nome"];
		}
		$posicao_base = $jogador_tabela['posicaoBase'] ?? '';
		$posicao_final = $dicionario_posicoes[$posicao_base] ?? '';
		$modificador = "";
		
		switch($posicao_final){
			case "_B":
				$modificador = $zagueiro == 1 ? "C" : $modificador;
				$modificador = ($zagueiro == 2 && $zagueiro_at == 1) ? "RC": $modificador;
				$modificador = ($zagueiro == 2 && $zagueiro_at == 2) ? "LC": $modificador;
				$modificador = ($zagueiro > 2 && $zagueiro_at == 1) ? "RC": $modificador;
				$modificador = ($zagueiro > 2 && $zagueiro_at == 2)? "C": $modificador;
				$modificador = ($zagueiro > 2 && $zagueiro_at == 3) ? "LC": $modificador;
				$zagueiro_at++;
				break;
			case "_DM":
				$modificador = $volante == 1 ? "C" : $modificador;
				$modificador = ($volante == 2 && $volante_at == 1) ? "RC": $modificador;
				$modificador = ($volante == 2 && $volante_at == 2) ? "LC": $modificador;
				$modificador = ($volante > 2 && $volante_at == 1) ? "RC": $modificador;
				$modificador = ($volante > 2 && $volante_at == 2)? "C": $modificador;
				$modificador = ($volante > 2 && $volante_at == 3) ? "LC": $modificador;
				$volante_at++;
				break;
			case "_M":
				$modificador = $meia == 1 ? "C" : $modificador;
				$modificador = ($meia == 2 && $meia_at == 1) ? "RC": $modificador;
				$modificador = ($meia == 2 && $meia_at == 2) ? "LC": $modificador;
				$modificador = ($meia > 2 && $meia_at == 1) ? "RC": $modificador;
				$modificador = ($meia > 2 && $meia_at == 2)? "C": $modificador;
				$modificador = ($meia > 2 && $meia_at == 3) ? "LC": $modificador;
				$meia_at++;				
				break;
			case "_AM":
				$modificador = $armador == 1 ? "C" : $modificador;
				$modificador = ($armador == 2 && $armador_at == 1) ? "RC": $modificador;
				$modificador = ($armador == 2 && $armador_at == 2) ? "LC": $modificador;
				$modificador = ($armador > 2 && $armador_at == 1) ? "RC": $modificador;
				$modificador = ($armador > 2 && $armador_at == 2)? "C": $modificador;
				$modificador = ($armador > 2 && $armador_at == 3) ? "LC": $modificador;
				$armador_at++;				
				break;
			case "_F":
				$modificador = $atacante == 1 ? "C" : $modificador;
				$modificador = ($atacante == 2 && $atacante_at == 1) ? "RC": $modificador;
				$modificador = ($atacante == 2 && $atacante_at == 2) ? "LC": $modificador;
				$modificador = ($atacante > 2 && $atacante_at == 1) ? "RC": $modificador;
				$modificador = ($atacante > 2 && $atacante_at == 2)? "C": $modificador;
				$modificador = ($atacante > 2 && $atacante_at == 3) ? "LC": $modificador;
				$atacante_at++;				
				break;
        }
		
		$posicao_final = $modificador . $posicao_final;
		$uniforme_final = ($posicao_final == "C_GK") ? $uniforme2_time : $uniforme1_time;
	
		echo '{name: "' . $nome_final . '", position: "' .$posicao_final . '", img: "/images/uniformes/' .$uniforme_final. '"},';
	}
	?>
    ];

    $("#soccerfield").soccerfield(soccerfieldData,{
      field: {
        width: "100%",
        height: "360px",
        img: '/images/fifa_soccer_field_1.png',
        startHidden: false,
        animate: true,
        fadeTime: 10,
        autoReveal:true
      },
      players: {
          img: '/images/soccer-player.png',
          font_size: 9,
          reveal: true,
          animate: true,
          sim: false,
          timeout: 1000,
          fadeTime: 1000
      }
    });
});
</script>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
