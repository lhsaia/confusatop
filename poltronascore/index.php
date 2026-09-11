<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Poltrona Score - Placar e Classificação</title>
    
    <!-- Meta tags para PWA -->
    <meta name="theme-color" content="#1837E8">
    <meta name="background-color" content="#1837E8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Poltrona Score">
    
    <link rel="apple-touch-icon" href="/poltronascore/icons/icon-192.png">
    <link rel="icon" type="image/png" href="/poltronascore/icons/favicon.png">
    <link rel="manifest" href="/poltronascore/manifest.json">
    
    <!-- Custom styling -->
    <link rel="stylesheet" href="/poltronascore/css/poltrona.css?v=<?= time() ?>">
    
    <!-- Icons for material design -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body>

    <!-- Header bar -->
    <header id="top-bar">
        <div class="header-container">
            <a href="/poltronascore/" class="logo-link">
                <img src="/poltronascore/icons/icon-192.png" alt="Poltrona Score Logo" class="logo-img">
                <span class="logo-text">Poltrona Score</span>
            </a>
            
            <div class="status-indicator">
                <div id="status-dot" class="status-dot"></div>
                <span id="update-time">Carregando...</span>
            </div>
        </div>
    </header>

    <!-- Main Views Container -->
    <main class="main-content">
        
        <!-- VIEW 1: MATCHES (JOGOS) -->
        <section id="view-matches" class="app-view active">
            <!-- Navigation tabs for match filters -->
            <nav class="tabs-nav">
                <div class="tabs-container">
                    <button class="tab-btn" data-tab="previous">
                        <span class="material-symbols-outlined" style="font-size: 18px;">history</span>
                        Anteriores
                    </button>
                    <button class="tab-btn active" data-tab="live">
                        <span class="material-symbols-outlined" style="font-size: 18px;">sensors</span>
                        Ao Vivo
                    </button>
                    <button class="tab-btn" data-tab="next">
                        <span class="material-symbols-outlined" style="font-size: 18px;">calendar_month</span>
                        Próximos
                    </button>
                </div>
            </nav>

            <!-- Match list content -->
            <div id="matches-list">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                </div>
            </div>
        </section>

        <!-- VIEW 2: STANDINGS (TABELAS / CLASSIFICAÇÃO) -->
        <section id="view-standings" class="app-view">
            <!-- Competition Selector -->
            <div class="comp-selector-container">
                <label for="comp-select" class="comp-select-label">
                    <span class="material-symbols-outlined">emoji_events</span>
                    Competição:
                </label>
                <select id="comp-select" class="comp-select-dropdown">
                    <option value="">Carregando competições...</option>
                </select>
            </div>

            <!-- Standings Subtabs -->
            <div class="standings-subnav" id="standings-subnav">
                <button class="standings-tab-btn active" data-subtab="table" id="btn-subtab-table">
                    <span class="material-symbols-outlined">format_list_numbered</span>
                    Classificação
                </button>
                <button class="standings-tab-btn" data-subtab="bracket" id="btn-subtab-bracket">
                    <span class="material-symbols-outlined">account_tree</span>
                    Chaveamento
                </button>
                <button class="standings-tab-btn" data-subtab="rounds" id="btn-subtab-rounds">
                    <span class="material-symbols-outlined">event_note</span>
                    Rodadas & Jogos
                </button>
            </div>

            <!-- Standings & Rounds Content -->
            <div id="standings-content">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                </div>
            </div>
        </section>

        <!-- VIEW 3: COMPETITIONS (LIGAS) -->
        <section id="view-competitions" class="app-view">
            <div class="competitions-header">
                <h2>
                    <span class="material-symbols-outlined" style="color: var(--accent-cyan); vertical-align: middle;">sports_soccer</span>
                    Competições
                </h2>
                <span class="competitions-subtitle">Acompanhe as tabelas, rodadas e resultados</span>
            </div>
            
            <div id="competitions-list" class="competitions-grid">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                </div>
            </div>
        </section>

    </main>

    <!-- Slide-up Modal Drawer for match details -->
    <div id="modal-overlay" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title-container">
                    <span id="m-championship" class="modal-championship">POLUSCAO 2026</span>
                    <span id="m-info-row" class="modal-info-row">LIGA A - RODADA 21 • 08/08 • Arena Talheres</span>
                </div>
                <button id="modal-close" class="modal-close">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Scoreboard Card -->
                <div class="modal-board">
                    <div class="modal-board-teams">
                        <div class="modal-board-team">
                            <img id="m-home-logo" class="modal-board-logo" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzk0YTNiOCI+PHBhdGggZD0iTTEyIDJMNCA1djYuMDljMCA1LjA1IDMuNDEgOS43NiA4IDEwLjkxIDQuNTktMS4xNSA4LTUuODYgOC0xMC45MVY1bC04LTN6bTAgMi4ybDYgMi4yNXY0LjY0YzAgMy43OS0yLjU2IDcuMzMtNiA4LjM1LTMuNDQtMS4wMi02LTQuNTYtNi04LjM1VjYuNDVsNi0yLjI1eiIvPjwvc3ZnPg==" alt="Logo Casa">
                            <span id="m-home-name" class="modal-board-name">Time Mandante</span>
                        </div>
                        <div>
                            <div id="m-score" class="modal-board-score">0 - 0</div>
                            <div id="m-penalties" class="modal-board-penalties" style="display: none;">Pênaltis: (0) - (0)</div>
                        </div>
                        <div class="modal-board-team">
                            <img id="m-away-logo" class="modal-board-logo" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzk0YTNiOCI+PHBhdGggZD0iTTEyIDJMNCA1djYuMDljMCA1LjA1IDMuNDEgOS43NiA4IDEwLjkxIDQuNTktMS4xNSA4LTUuODYgOC0xMC45MVY1bC04LTN6bTAgMi4ybDYgMi4yNXY0LjY0YzAgMy43OS0yLjU2IDcuMzMtNiA4LjM1LTMuNDQtMS4wMi02LTQuNTYtNi04LjM1VjYuNDVsNi0yLjI1eiIvPjwvc3ZnPg==" alt="Logo Visitante">
                            <span id="m-away-name" class="modal-board-name">Time Visitante</span>
                        </div>
                    </div>
                    
                    <div class="modal-board-scorers">
                        <div id="m-home-scorers" style="text-align: right; color: var(--text-secondary);"></div>
                        <div id="m-away-scorers" style="text-align: left; color: var(--text-secondary);"></div>
                    </div>
                </div>
                
                <!-- Timeline Events -->
                <div class="timeline-section">
                    <h3 class="timeline-title">
                        <span class="material-symbols-outlined" style="color: var(--accent-cyan);">receipt_long</span>
                        LANCES DO JOGO
                    </h3>
                    <div id="m-events-list">
                        <!-- Events populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom PWA Install Banner -->
    <div id="pwa-install-banner" class="pwa-install-banner">
        <div class="pwa-install-content">
            <span class="material-symbols-outlined pwa-install-icon">download_for_offline</span>
            <div class="pwa-install-text">
                <strong>Instalar Poltrona Score</strong>
                <span>Adicione à sua tela inicial para acompanhar os jogos como um aplicativo.</span>
            </div>
        </div>
        <div class="pwa-install-actions">
            <button id="pwa-btn-cancel" class="pwa-btn-cancel">Agora não</button>
            <button id="pwa-btn-install" class="pwa-btn-install">Instalar</button>
        </div>
    </div>

    <!-- Sticky footer -->
    <footer id="bottom-bar">
        <div style="font-weight: 600; margin-bottom: 3px;">🛋️ Poltrona Score © 2026</div>
        <div>O placar da comunidade direto do seu sofá</div>
    </footer>

    <!-- Bottom Navigation Bar (SofaScore style) -->
    <nav id="bottom-nav" class="bottom-nav">
        <button class="nav-item active" data-view="matches">
            <span class="material-symbols-outlined nav-icon">sports_soccer</span>
            <span class="nav-label">Jogos</span>
        </button>
        <button class="nav-item" data-view="standings">
            <span class="material-symbols-outlined nav-icon">leaderboard</span>
            <span class="nav-label">Tabelas</span>
        </button>
        <button class="nav-item" data-view="competitions">
            <span class="material-symbols-outlined nav-icon">emoji_events</span>
            <span class="nav-label">Ligas</span>
        </button>
    </nav>

    <!-- App JavaScript -->
    <script src="/poltronascore/js/poltrona.js?v=<?= time() ?>"></script>
</body>
</html>
