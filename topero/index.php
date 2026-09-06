<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
$css_versao = date('h:i:s');

$is_logged = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$user_name = $_SESSION['nomereal'] ?? $_SESSION['username'] ?? 'Usuário';
$user_avatar = !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : '/images/default-user.png';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TOPERO • Simulador de Carreira CONFUSA</title>
    
    <!-- Meta tags para PWA Standalone -->
    <meta name="theme-color" content="#1A1469">
    <meta name="background-color" content="#090d16">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TOPERO">
    
    <link rel="apple-touch-icon" href="/topero/icons/icon-192.png">
    <link rel="icon" type="image/png" href="/topero/icons/favicon.png">
    <link rel="manifest" href="/topero/manifest.json">
    
    <!-- Custom styling -->
    <link rel="stylesheet" href="/css/topero.css?v=<?= $css_versao ?>">
    
    <!-- Fonts & Material Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body>

    <!-- Header bar standalone estilo Poltrona Score -->
    <header id="top-bar">
        <div class="header-container">
            <a href="/topero/" class="logo-link">
                <img src="/topero/icons/icon-192.png" alt="TOPERO Logo" class="logo-img">
                <span class="logo-text"><span style="color:#38bdf8;">TOP</span>ERO</span>
            </a>

            <div class="header-user-area">
                <?php if ($is_logged): ?>
                    <div class="user-badge-info" title="Você está conectado ao CONFUSA.top">
                        <img src="<?= htmlspecialchars($user_avatar) ?>" alt="Avatar" class="user-badge-avatar">
                        <span class="user-badge-name"><?= htmlspecialchars($user_name) ?></span>
                    </div>
                <?php else: ?>
                    <a href="/index.php" class="login-badge-btn" title="Fazer login para salvar suas carreiras">
                        <span class="material-symbols-outlined" style="font-size: 18px;">login</span>
                        <span>Entrar</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

<main class="topero-container">
  <div class="topero-card">

    <!-- Brand do Topo -->
    <div class="topero-brand">
      <h1 class="topero-title"><span class="top-highlight">TOP</span>ERO</h1>
      <p class="topero-subtitle">O Simulador de Carreira Profissional no Universo CONFUSA.top</p>
      
      <!-- Abas Principais de Navegação -->
      <div class="topero-nav-tabs">
        <button id="tab-btn-jogar" class="topero-tab-btn active">
          <span class="material-symbols-outlined">sports_soccer</span> Novo Jogo
        </button>
        <button id="tab-btn-minhas-carreiras" class="topero-tab-btn">
          <span class="material-symbols-outlined">badge</span> Minhas Carreiras
        </button>
        <button id="tab-btn-hall-fama" class="topero-tab-btn">
          <span class="material-symbols-outlined">trophy</span> Hall da Fama
        </button>
      </div>
    </div>

    <!-- Loading Inicial -->
    <div id="loading-overlay" style="text-align: center; padding: 2.5rem 0;">
      <div style="font-size: 1.1rem; color: #38bdf8; font-weight: 600;">Carregando federações, ligas e clubes...</div>
    </div>

    <!-- TELA 1: CRIAÇÃO DO ATLETA -->
    <section id="view-criacao" style="display: block;">
      <form id="form-novo-jogador">
        
        <div class="form-section-title">
          <span>👤</span> Identidade do Jogador
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="input-nome">Nome do Atleta</label>
            <input type="text" id="input-nome" class="form-control" placeholder="Ex: Lucas Gabriel" required maxlength="50">
          </div>
          <div class="form-group">
            <label for="input-numero">Número da Camisa</label>
            <input type="number" id="input-numero" class="form-control" value="10" min="1" max="99" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label>Gênero / Categoria</label>
            <div class="radio-chips-group">
              <label class="chip-radio">
                <input type="radio" name="genero" value="0" checked>
                <span class="chip-box">👨 Masculino</span>
              </label>
              <label class="chip-radio">
                <input type="radio" name="genero" value="1">
                <span class="chip-box">👩 Feminino</span>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label>Pé Preferido</label>
            <div class="radio-chips-group">
              <label class="chip-radio">
                <input type="radio" name="pe" value="Destro" checked>
                <span class="chip-box">Destro</span>
              </label>
              <label class="chip-radio">
                <input type="radio" name="pe" value="Canhoto">
                <span class="chip-box">Canhoto</span>
              </label>
              <label class="chip-radio">
                <input type="radio" name="pe" value="Ambidestro">
                <span class="chip-box">Ambidestro</span>
              </label>
            </div>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label for="select-posicao">Posição Principal</label>
            <select id="select-posicao" class="form-control" required>
              <option value="ST" selected>Atacante / Centroavante (ST)</option>
              <option value="LW">Ponta Esquerda (LW)</option>
              <option value="RW">Ponta Direita (RW)</option>
              <option value="CAM">Meia Ofensivo (CAM)</option>
              <option value="CM">Meio-Campista Central (CM)</option>
              <option value="CDM">Volante / Trinco (CDM)</option>
              <option value="LB">Lateral Esquerdo (LB)</option>
              <option value="RB">Lateral Direito (RB)</option>
              <option value="CB">Zagueiro Central (CB)</option>
              <option value="GK">Goleiro (GK)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="select-pais">Nacionalidade de Nascimento (Países Ativos)</label>
            <div style="display:flex; align-items:center; gap:8px;">
              <select id="select-pais" class="form-control" style="flex:1;" required>
                <option value="" disabled selected>Carregando países...</option>
              </select>
              <img id="bandeira-preview" src="" class="mini-bandeira" style="display:none; width:28px; height:20px;" alt="">
            </div>
            <div id="nota-geografica" style="display:none;"></div>
          </div>
        </div>

        <div class="form-section-title">
          <span>🌍</span> Clube de Estreia
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
          <label for="select-clube">Clube Inicial (Propostas da Categoria Selecionada)</label>
          <select id="select-clube" class="form-control" required>
            <option value="" disabled selected>Selecione um país primeiro...</option>
          </select>
        </div>

        <div class="form-section-title">
          <span>⏱️</span> Modo de Jogo & Cadência de Decisões
        </div>

        <div class="modes-grid">
          <label class="mode-card-radio">
            <input type="radio" name="cadencia" value="long">
            <div class="mode-card-box">
              <div class="mode-card-title">🔥 Intensa</div>
              <div class="mode-card-desc">1 decisão por temporada. Acompanhamento detalhado ano a ano.</div>
            </div>
          </label>

          <label class="mode-card-radio">
            <input type="radio" name="cadencia" value="normal" checked>
            <div class="mode-card-box">
              <div class="mode-card-title">⚡ Normal</div>
              <div class="mode-card-desc">Decisões a cada 2 temporadas. Equilíbrio ideal entre ritmo e escolhas.</div>
            </div>
          </label>

          <label class="mode-card-radio">
            <input type="radio" name="cadencia" value="express">
            <div class="mode-card-box">
              <div class="mode-card-title">🚀 Expresso</div>
              <div class="mode-card-desc">Decisões a cada 3 temporadas. Simulação super rápida até o topo.</div>
            </div>
          </label>
        </div>

        <div style="text-align: center; margin-top: 2.5rem;">
          <button type="submit" class="btn-primary" style="padding: 14px 36px; font-size: 1.15rem;">
            Começar Minha Carreira ➔
          </button>
        </div>

      </form>
    </section>

    <!-- TELA 2: PAINEL DE JOGO E CARREIRA -->
    <section id="view-jogo" style="display: none;">
      
      <!-- HUD do Atleta -->
      <div class="atleta-hud">
        <div class="hud-perfil">
          <div class="hud-avatar-circle" id="hud-camisa">#10</div>
          <div>
            <div class="hud-info-nome" id="hud-nome">Atleta</div>
            <div class="hud-meta-tags">
              <span class="hud-tag" id="hud-posicao">ST</span>
              <span class="hud-tag" id="hud-idade">17 anos</span>
              <span class="hud-tag">
                <img id="hud-pais-bandeira" src="" class="mini-bandeira" style="display:none;" alt="">
                <span id="hud-pais-nome">País</span>
              </span>
              <span class="hud-tag hud-tag-clube">
                <img id="hud-clube-escudo" src="" class="mini-escudo" style="display:none;" alt="">
                <span id="hud-clube-nome">Clube</span>
                <span style="color:#94a3b8; font-weight:normal;">(<span id="hud-clube-liga">Liga</span>)</span>
              </span>
            </div>
          </div>
        </div>

        <div class="hud-ovr-card">
          <div class="hud-ovr-rotulo">NÍVEL</div>
          <div class="hud-ovr-valor" id="hud-ovr">65</div>
          <div class="hud-ovr-progress-bg">
            <div id="hud-ovr-bar" class="hud-ovr-progress-fill" style="width: 40%;"></div>
          </div>
        </div>
      </div>

      <!-- Métricas Gerais Acumuladas -->
      <div class="stats-bar-grid">
        <div class="stat-box">
          <div class="stat-box-val" id="stat-jogos">0</div>
          <div class="stat-box-lbl" id="lbl-stat-jogos">Partidas</div>
        </div>
        <div class="stat-box">
          <div class="stat-box-val" id="stat-gols">0</div>
          <div class="stat-box-lbl" id="lbl-stat-gols">Gols Marcados</div>
        </div>
        <div class="stat-box">
          <div class="stat-box-val" id="stat-assists">0</div>
          <div class="stat-box-lbl" id="lbl-stat-assists">Assistências</div>
        </div>
        <div class="stat-box">
          <div class="stat-box-val" id="stat-titulos">0</div>
          <div class="stat-box-lbl" id="lbl-stat-titulos">Títulos Conquistados</div>
        </div>
      </div>

      <!-- Barra de Ação para Avançar -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem; flex-wrap:wrap; gap:10px;">
        <h3 style="font-family:'Outfit', sans-serif; margin:0; font-size:1.3rem; color:#f8fafc;">Histórico de Temporadas</h3>
        <button id="btn-avancar-temporada" class="btn-primary">
          Avançar Temporadas ➔
        </button>
      </div>

      <!-- Feed de Temporadas -->
      <div id="lista-temporadas"></div>

    </section>

    <!-- TELA 3: APOSENTADORIA / CARD FINAL -->
    <section id="view-aposentadoria" style="display: none;">
      
      <!-- Container do Card Oficial de Exportação -->
      <div id="card-carreira-exportable" class="card-carreira-exportable">
        <div class="final-header" style="margin-bottom: 1.25rem;">
          <div style="font-size:2.2rem; margin-bottom:4px;">🎖️</div>
          <h2 class="final-title" style="margin-bottom: 2px;">Fim da Trajetória Profissional</h2>
          <p style="color:#94a3b8; font-size:0.95rem; margin:0;">
            <strong id="final-nome" style="color:#f8fafc;">Atleta</strong> encerrou sua carreira lendária no CONFUSA.top!
          </p>
        </div>

        <!-- Card Resumo Dark -->
        <div class="atleta-hud" style="background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); margin-bottom: 1.25rem;">
          <div>
            <div class="hud-info-nome" style="font-size:1.6rem; color:#ffffff; margin-bottom: 4px;">Carreira Histórica</div>
            <div class="hud-meta-tags">
              <span class="hud-tag" id="final-numero-pos">#10 • ST</span>
              <span class="hud-tag">
                <img id="final-pais-bandeira" src="" class="mini-bandeira" style="display:none; width:20px; height:14px; object-fit:cover; margin-right:4px;" alt="">
                <span id="final-pais">País</span>
              </span>
              <span class="hud-tag" id="final-idade">40 anos</span>
            </div>
          </div>
          <div class="hud-ovr-card" style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(16, 185, 129, 0.3);">
            <div class="hud-ovr-rotulo" style="color:#6ee7b7;">PICO DE NÍVEL</div>
            <div class="hud-ovr-valor" id="final-ovr-pico" style="color:#10b981;">88</div>
          </div>
        </div>

        <div class="stats-bar-grid" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); margin-bottom: 1.5rem;">
          <div class="stat-box">
            <div class="stat-box-val" id="final-jogos">0</div>
            <div class="stat-box-lbl" id="lbl-final-jogos">Partidas</div>
          </div>
          <div class="stat-box">
            <div class="stat-box-val" id="final-gols">0</div>
            <div class="stat-box-lbl" id="lbl-final-gols">Gols</div>
          </div>
          <div class="stat-box">
            <div class="stat-box-val" id="final-assists">0</div>
            <div class="stat-box-lbl" id="lbl-final-assists">Assistências</div>
          </div>
          <div class="stat-box">
            <div class="stat-box-val" id="final-titulos">0</div>
            <div class="stat-box-lbl" id="lbl-final-titulos">Títulos</div>
          </div>
          <div class="stat-box">
            <div class="stat-box-val" id="final-bolas-ouro" style="color:#38bdf8;">0</div>
            <div class="stat-box-lbl" id="lbl-final-bolas-ouro">Bolas de Ouro</div>
          </div>
        </div>

        <!-- Troféus Acumulados -->
        <h4 style="font-family:'Outfit', sans-serif; font-size:1.15rem; color:#f8fafc; margin:1.25rem 0 0.5rem 0;">🏆 Galeria de Troféus Conquistados</h4>
        <div id="final-trofeus-lista" class="final-trofeus-grid" style="margin-bottom: 1.5rem;"></div>

        <!-- Clubes defendidos -->
        <h4 style="font-family:'Outfit', sans-serif; font-size:1.15rem; color:#f8fafc; margin:1.25rem 0 0.5rem 0;">🛡️ Clubes Onde Jogou</h4>
        <div id="final-clubes-lista" class="final-clubes-wrap" style="margin-bottom: 1rem;"></div>

        <div style="text-align:center; padding-top:10px; border-top:1px solid rgba(255,255,255,0.08); font-size:0.8rem; color:#64748b;">
          ⚽ TOPERO • CONFUSA.top • Carreira Histórica
        </div>
      </div>

      <!-- Ações Finais -->
      <div class="final-actions">
        <button id="btn-baixar-card" class="btn-primary">
          📥 Baixar Card da Carreira (PNG)
        </button>
        <button id="btn-salvar-carreira" class="btn-secondary">
          💾 Salvar Carreira no Meu Perfil
        </button>
        <button id="btn-jogar-novamente" class="btn-secondary">
          🔄 Jogar Novamente
        </button>
      </div>
    </section>

    <!-- TELA 4: MINHAS CARREIRAS & HALL DA FAMA -->
    <section id="view-hall-fama" style="display: none;">
      
      <div id="container-minhas-carreiras-wrap" style="display: none;">
        <h3 style="font-family:'Outfit', sans-serif; color:#f8fafc; margin-top:0; margin-bottom:12px; font-size:1.35rem; display:flex; align-items:center; gap:8px;">
          <span>🎖️</span> Minhas Carreiras Salvas
        </h3>
        <div id="minhas-carreiras-lista" class="carreiras-grid-cards" style="margin-bottom: 2.5rem;"></div>
      </div>

      <div>
        <h3 style="font-family:'Outfit', sans-serif; color:#f8fafc; margin-top:0; margin-bottom:12px; font-size:1.35rem; display:flex; align-items:center; gap:8px;">
          <span>🏆</span> Hall da Fama Global (Top 25 CONFUSA)
        </h3>
        <div id="ranking-global-lista" class="carreiras-grid-cards"></div>
      </div>

      <div style="text-align:center; margin-top:2.5rem;">
        <button id="btn-voltar-criacao" class="btn-primary">
          ⚽ Iniciar Nova Carreira
        </button>
      </div>
    </section>

  </div>
</main>

<!-- MODAL DE DECISÃO -->
<div id="modal-decisao" class="modal-overlay" style="display: none;">
  <div class="modal-content-card" id="modal-decisao-conteudo"></div>
</div>

<!-- Custom PWA Install Banner -->
<div id="pwa-install-banner" class="pwa-install-banner">
    <div class="pwa-install-content">
        <span class="material-symbols-outlined pwa-install-icon">download_for_offline</span>
        <div class="pwa-install-text">
            <strong>Instalar TOPERO</strong>
            <span>Adicione à sua tela inicial para simular carreiras direto do seu app.</span>
        </div>
    </div>
    <div class="pwa-install-actions">
        <button id="pwa-btn-cancel" class="pwa-btn-cancel">Agora não</button>
        <button id="pwa-btn-install" class="pwa-btn-install">Instalar</button>
    </div>
</div>

<!-- Sticky footer standalone -->
<footer id="bottom-bar">
    <div style="font-weight: 600; margin-bottom: 5px; color:#cbd5e1;">⚽ TOPERO © 2026 • CONFUSA.top</div>
    <div>Simulador de carreira profissional independente</div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="/topero/js/topero_events.js?v=<?= $css_versao ?>"></script>
<script src="/topero/js/topero_engine.js?v=<?= $css_versao ?>"></script>
<script src="/topero/js/topero_ui.js?v=<?= $css_versao ?>"></script>

</body>
</html>
