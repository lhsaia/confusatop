// PoltronaScore JS - Core Application (Unified Match & Standings Experience)
const DEFAULT_LOGO = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzk0YTNiOCI+PHBhdGggZD0iTTEyIDJMNCA1djYuMDljMCA1LjA1IDMuNDEgOS43NiA4IDEwLjkxIDQuNTktMS4xNSA4LTUuODYgOC0xMC45MVY1bC04LTN6bTAgMi4ybDYgMi4yNXY0LjY0YzAgMy43OS0yLjU2IDcuMzMtNiA4LjM1LTMuNDQtMS4wMi02LTQuNTYtNi04LjM1VjYuNDVsNi0yLjI1eiIvPjwvc3ZnPg==";

document.addEventListener('DOMContentLoaded', () => {
    let pollInterval = null;
    let currentCompId = 0;
    let currentSubTab = 'table';
    let cachedStandingsData = null;
    let cachedCompetitions = [];

    const getLogo = (logoUrl) => {
        if (!logoUrl || logoUrl.includes('id=-1') || logoUrl.includes('id=null')) {
            return DEFAULT_LOGO;
        }
        let absoluteUrl = logoUrl;
        if (!logoUrl.startsWith('http') && !logoUrl.startsWith('data:')) {
            const cleanPath = logoUrl.startsWith('/') ? logoUrl : '/' + logoUrl;
            absoluteUrl = window.location.origin + cleanPath;
        }
        if (absoluteUrl.startsWith('http://52.203.150.214')) {
            return '/api/poltronascore/proxy.php?url=' + encodeURIComponent(absoluteUrl);
        }
        return absoluteUrl;
    };

    // Favorites Manager (LocalStorage + Database Cloud Sync for Authenticated Users)
    const FavoritesManager = {
        KEY: 'poltronascore_favorites_v1',
        isLoggedIn: false,
        userId: 0,
        userName: '',

        get() {
            try {
                const raw = localStorage.getItem(this.KEY);
                return raw ? JSON.parse(raw) : { matches: [], clubs: [], competitions: [], players: [] };
            } catch (e) {
                return { matches: [], clubs: [], competitions: [], players: [] };
            }
        },
        save(data) {
            try {
                localStorage.setItem(this.KEY, JSON.stringify(data));
            } catch (e) {}
        },
        has(type, id) {
            const data = this.get();
            const list = data[type] || [];
            return list.some(item => (typeof item === 'object' ? item.id : item) == id);
        },
        toggle(type, item) {
            const data = this.get();
            if (!data[type]) data[type] = [];
            const id = typeof item === 'object' ? item.id : item;
            const idx = data[type].findIndex(it => (typeof it === 'object' ? it.id : it) == id);
            let added = false;
            if (idx >= 0) {
                data[type].splice(idx, 1);
            } else {
                data[type].push(item);
                added = true;
            }
            this.save(data);

            // Cloud sync in background if logged in
            if (this.isLoggedIn) {
                fetch('/api/poltronascore/favoritos_sync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'toggle',
                        tipo: type,
                        item_id: id
                    })
                }).catch(() => {});
            }

            return added;
        },
        remove(type, id) {
            const data = this.get();
            if (!data[type]) return;
            data[type] = data[type].filter(it => (typeof it === 'object' ? it.id : it) != id);
            this.save(data);

            // Cloud sync in background if logged in
            if (this.isLoggedIn) {
                fetch('/api/poltronascore/favoritos_sync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'remove',
                        tipo: type,
                        item_id: id
                    })
                }).catch(() => {});
            }
        },
        initSync() {
            fetch('/api/poltronascore/favoritos_sync.php')
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.logged_in) {
                        this.isLoggedIn = true;
                        this.userId = res.user_id;
                        this.userName = res.username;

                        // Merge local favorites to DB & load full list
                        const localData = this.get();
                        fetch('/api/poltronascore/favoritos_sync.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'sync',
                                favorites: localData
                            })
                        })
                        .then(r => r.json())
                        .then(syncRes => {
                            if (syncRes.success && syncRes.favorites) {
                                // Save consolidated database favorites into localStorage
                                this.save(syncRes.favorites);
                            }
                        })
                        .catch(() => {});
                    }
                })
                .catch(() => {});
        }
    };

    let currentFavSubtab = 'matches';

    // DOM Elements - Navigation & Views
    const navItems = document.querySelectorAll('.nav-item');
    const appViews = document.querySelectorAll('.app-view');
    const tabButtons = document.querySelectorAll('.tab-btn');
    const standingsTabBtns = document.querySelectorAll('.standings-tab-btn');
    const favTabBtns = document.querySelectorAll('#favorites-subnav .standings-tab-btn');
    const btnSubtabTable = document.getElementById('btn-subtab-table');
    const btnSubtabBracket = document.getElementById('btn-subtab-bracket');
    const btnSubtabRounds = document.getElementById('btn-subtab-rounds');
    const compSelect = document.getElementById('comp-select');

    // DOM Elements - Containers
    const matchesList = document.getElementById('matches-list');
    const standingsContent = document.getElementById('standings-content');
    const competitionsList = document.getElementById('competitions-list');
    const favoritesContent = document.getElementById('favorites-content');
    const updateTimeText = document.getElementById('update-time');
    const statusDot = document.getElementById('status-dot');

    // DOM Elements - Modal
    const modalOverlay = document.getElementById('modal-overlay');
    const modalClose = document.getElementById('modal-close');
    const modalFavBtn = document.getElementById('modal-fav-btn');
    const mChampionship = document.getElementById('m-championship');
    const mInfoRow = document.getElementById('m-info-row');
    const mHomeName = document.getElementById('m-home-name');
    const mHomeLogo = document.getElementById('m-home-logo');
    const mAwayName = document.getElementById('m-away-name');
    const mAwayLogo = document.getElementById('m-away-logo');
    const mScore = document.getElementById('m-score');
    const mPenalties = document.getElementById('m-penalties');
    const mHomeScorers = document.getElementById('m-home-scorers');
    const mAwayScorers = document.getElementById('m-away-scorers');
    const mEventsList = document.getElementById('m-events-list');

    // DOM Elements - Match Center Subtabs & Lineups (Fase 3)
    const btnMatchEvents = document.getElementById('btn-match-events');
    const btnMatchLineups = document.getElementById('btn-match-lineups');
    const matchTabEvents = document.getElementById('match-tab-events');
    const matchTabLineups = document.getElementById('match-tab-lineups');
    const mLineupsContent = document.getElementById('m-lineups-content');

    // DOM Elements - Player Modal (Fase 2)
    const playerModal = document.getElementById('player-modal');
    const playerModalClose = document.getElementById('player-modal-close');
    const playerModalFavBtn = document.getElementById('player-modal-fav-btn');
    const playerModalBody = document.getElementById('player-modal-body');

    // DOM Elements - Club Modal (Fase 4)
    const clubModal = document.getElementById('club-modal');
    const clubModalClose = document.getElementById('club-modal-close');
    const clubModalFavBtn = document.getElementById('club-modal-fav-btn');
    const clubModalBody = document.getElementById('club-modal-body');

    // Subtab Switching in Match Modal
    if (btnMatchEvents && btnMatchLineups) {
        btnMatchEvents.addEventListener('click', () => {
            btnMatchEvents.classList.add('active');
            btnMatchLineups.classList.remove('active');
            if (matchTabEvents) matchTabEvents.classList.add('active');
            if (matchTabLineups) matchTabLineups.classList.remove('active');
        });

        btnMatchLineups.addEventListener('click', () => {
            btnMatchLineups.classList.add('active');
            btnMatchEvents.classList.remove('active');
            if (matchTabLineups) matchTabLineups.classList.add('active');
            if (matchTabEvents) matchTabEvents.classList.remove('active');
        });
    }

    // Modal Close Events
    if (playerModalClose) {
        playerModalClose.addEventListener('click', closePlayerModal);
    }
    if (playerModal) {
        playerModal.addEventListener('click', (e) => {
            if (e.target === playerModal) closePlayerModal();
        });
    }

    if (clubModalClose) {
        clubModalClose.addEventListener('click', closeClubModal);
    }
    if (clubModal) {
        clubModal.addEventListener('click', (e) => {
            if (e.target === clubModal) closeClubModal();
        });
    }

    // Modal Z-Index Stacking Manager
    let topModalZIndex = 400;
    function bringModalToFront(modalEl) {
        if (!modalEl) return;
        topModalZIndex += 10;
        modalEl.style.zIndex = topModalZIndex;
    }

    function closePlayerModal() {
        if (playerModal) playerModal.classList.remove('active');
    }

    function closeClubModal() {
        if (clubModal) clubModal.classList.remove('active');
    }

    // =========================================================================
    // 1. NAVIGATION & ROUTING
    // =========================================================================
    
    function switchView(targetViewId) {
        navItems.forEach(item => {
            if (item.getAttribute('data-view') === targetViewId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        appViews.forEach(view => {
            if (view.id === `view-${targetViewId}`) {
                view.classList.add('active');
            } else {
                view.classList.remove('active');
            }
        });

        if (targetViewId === 'standings') {
            if (!cachedStandingsData && currentCompId > 0) {
                loadStandings(currentCompId);
            } else if (!cachedStandingsData && cachedCompetitions.length === 0) {
                loadCompetitions(true);
            }
        } else if (targetViewId === 'competitions') {
            if (cachedCompetitions.length === 0) {
                loadCompetitions(false);
            }
        } else if (targetViewId === 'favorites') {
            loadFavorites(currentFavSubtab);
        }
    }

    navItems.forEach(item => {
        item.addEventListener('click', () => {
            const view = item.getAttribute('data-view');
            switchView(view);
        });
    });

    // Sub-nav for matches (Anteriores / Ao Vivo / Próximos)
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetSec = btn.getAttribute('data-tab');
            const targetEl = document.getElementById(`sec-${targetSec}`);
            if (targetEl) {
                const headerOffset = 135;
                const elementPosition = targetEl.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });

                tabButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }
        });
    });

    // Sub-nav for standings (Classificação vs Chaveamento vs Rodadas)
    standingsTabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            currentSubTab = btn.getAttribute('data-subtab');
            standingsTabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            if (cachedStandingsData) {
                renderStandingsView(cachedStandingsData);
            }
        });
    });

    // Sub-nav for favorites (Jogos / Times / Ligas / Jogadores)
    favTabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            currentFavSubtab = btn.getAttribute('data-fav-tab') || 'matches';
            favTabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadFavorites(currentFavSubtab);
        });
    });

    // Dropdown change for competition
    if (compSelect) {
        compSelect.addEventListener('change', (e) => {
            currentCompId = parseInt(e.target.value, 10) || 0;
            if (currentCompId > 0) {
                currentSubTab = 'table';
                loadStandings(currentCompId);
            }
        });
    }

    // Modal Close
    modalClose.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) closeModal();
    });

    // =========================================================================
    // 2. DATA FETCHING - MATCHES
    // =========================================================================

    function loadMatches() {
        statusDot.className = 'status-dot loading';

        fetch('/api/poltronascore/jogos.php')
            .then(res => res.json())
            .then(res => {
                statusDot.className = res.last_update_success ? 'status-dot' : 'status-dot error';

                if (res.last_update) {
                    const lastDate = new Date(res.last_update.replace(/-/g, '/'));
                    const hours = String(lastDate.getHours()).padStart(2, '0');
                    const mins = String(lastDate.getMinutes()).padStart(2, '0');
                    updateTimeText.textContent = `Atualizado às ${hours}:${mins}`;
                } else {
                    updateTimeText.textContent = 'Sem atualizações';
                }

                if (!res.success) {
                    matchesList.innerHTML = `<div class="no-data">Erro ao carregar dados: ${res.message}</div>`;
                    return;
                }

                renderAllMatches(res.data);
            })
            .catch(() => {
                statusDot.className = 'status-dot error';
                updateTimeText.textContent = 'Erro de rede';
            });
    }

    function renderAllMatches(data) {
        let html = '';

        // Live Matches
        const liveGroups = data.live || {};
        const liveCount = Object.values(liveGroups).reduce((acc, curr) => acc + curr.length, 0);

        html += `<div id="sec-live" class="section-container">`;
        if (liveCount > 0) {
            html += `<h2 class="section-title"><span class="badge-live" style="animation: pulse 1.2s infinite; font-size: 11px;">AO VIVO</span> Jogos em Andamento</h2>`;
            html += renderGroups(liveGroups, 'live');
            const liveTab = document.querySelector('.tab-btn[data-tab="live"]');
            if (liveTab) liveTab.style.display = 'flex';
        } else {
            const liveTab = document.querySelector('.tab-btn[data-tab="live"]');
            if (liveTab) liveTab.style.display = 'none';
        }
        html += `</div>`;

        // Next Matches
        const nextGroups = data.next || {};
        const nextCount = Object.values(nextGroups).reduce((acc, curr) => acc + curr.length, 0);

        html += `<div id="sec-next" class="section-container">`;
        if (nextCount > 0) {
            html += `<h2 class="section-title"><span class="material-symbols-outlined" style="vertical-align: middle; color: var(--accent-amber);">calendar_month</span> Próximos Jogos</h2>`;
            html += renderGroups(nextGroups, 'next');
        } else {
            html += `<div class="no-data">Nenhum jogo agendado.</div>`;
        }
        html += `</div>`;

        // Previous Matches
        const prevGroups = data.previous || {};
        const prevCount = Object.values(prevGroups).reduce((acc, curr) => acc + curr.length, 0);

        html += `<div id="sec-previous" class="section-container">`;
        if (prevCount > 0) {
            html += `<h2 class="section-title"><span class="material-symbols-outlined" style="vertical-align: middle; color: var(--accent-emerald);">history</span> Resultados Anteriores</h2>`;
            html += renderGroups(prevGroups, 'previous');
        } else {
            html += `<div class="no-data">Nenhum resultado anterior encontrado.</div>`;
        }
        html += `</div>`;

        matchesList.innerHTML = html;
        bindMatchCardClicks();
    }

    function renderGroups(groupedMatches, type) {
        let html = '';
        for (const [groupKey, matches] of Object.entries(groupedMatches)) {
            const headerTitle = type === 'previous' ? `Jogos em ${groupKey}` : groupKey;

            html += `
                <div class="championship-group">
                    <div class="championship-header">${headerTitle}</div>
            `;

            matches.forEach(match => {
                const homeScore = match.home_score !== null ? match.home_score : 0;
                const awayScore = match.away_score !== null ? match.away_score : 0;

                const scoreHtml = type === 'next' 
                    ? `<span class="score-display"><span class="score-divider">vs</span></span>`
                    : `<span class="score-display"><span>${homeScore}</span><span class="score-divider">-</span><span>${awayScore}</span></span>`;

                const timeStatusHtml = type === 'live'
                    ? `<span class="badge-live">AO VIVO</span>`
                    : (type === 'previous'
                        ? `<span>${match.match_time}</span>`
                        : `<span>${match.match_date} às ${match.match_time}</span>`);

                const champLabel = match.championship
                    ? `<span style="color: var(--accent-cyan); font-weight: 600; text-transform: uppercase; font-size: 10px; margin-right: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;" title="${match.championship}${match.rodada ? ' - ' + match.rodada : ''}">${match.championship}${match.rodada ? ' - ' + match.rodada : ''}</span>`
                    : '';

                html += `
                    <div class="match-card" data-id="${match.id}">
                        <div class="match-top">
                            <div class="match-time-status">
                                ${champLabel}
                                ${timeStatusHtml}
                            </div>
                            <div class="match-stadium">${match.stadium || 'Local não informado'}</div>
                        </div>
                        <div class="match-teams-score">
                            <div class="team-info home">
                                <span class="team-name">${match.home_team}</span>
                                <img class="team-logo" src="${getLogo(match.home_logo)}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                            </div>
                            ${scoreHtml}
                            <div class="team-info away">
                                <img class="team-logo" src="${getLogo(match.away_logo)}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                                <span class="team-name">${match.away_team}</span>
                            </div>
                        </div>
                        ${(match.home_scorers || match.away_scorers) ? `
                        <div class="match-scorers">
                            <div class="scorers-list home" title="${match.home_scorers || ''}">${match.home_scorers || ''}</div>
                            <div></div>
                            <div class="scorers-list away" title="${match.away_scorers || ''}">${match.away_scorers || ''}</div>
                        </div>
                        ` : ''}
                    </div>
                `;
            });

            html += `</div>`;
        }
        return html;
    }

    // =========================================================================
    // 3. DATA FETCHING - COMPETITIONS & STANDINGS (CONFUSA.TOP SIMULATED)
    // =========================================================================

    function loadCompetitions(autoLoadFirstStandings = true) {
        fetch('/api/poltronascore/competicoes.php')
            .then(res => res.json())
            .then(res => {
                if (!res.success || !res.competitions || res.competitions.length === 0) {
                    if (compSelect) compSelect.innerHTML = '<option value="">Nenhuma competição cadastrada</option>';
                    if (competitionsList) competitionsList.innerHTML = '<div class="no-data">Nenhuma competição encontrada no sistema.</div>';
                    if (standingsContent) standingsContent.innerHTML = '<div class="no-data">Nenhuma competição encontrada no sistema.</div>';
                    return;
                }

                cachedCompetitions = res.competitions;

                // Populate Dropdown
                if (compSelect) {
                    compSelect.innerHTML = res.competitions.map(c => 
                        `<option value="${c.id}" ${c.id === currentCompId ? 'selected' : ''}>${c.name}</option>`
                    ).join('');

                    if (!currentCompId || currentCompId <= 0) {
                        currentCompId = res.competitions[0].id;
                        compSelect.value = currentCompId;
                    }
                }

                // Render Competitions Grid
                renderCompetitionsGrid(res.competitions);

                // Load Standings if needed
                if (autoLoadFirstStandings && currentCompId > 0) {
                    loadStandings(currentCompId);
                }
            })
            .catch(() => {
                if (compSelect) compSelect.innerHTML = '<option value="">Falha na conexão</option>';
                if (standingsContent) standingsContent.innerHTML = '<div class="no-data">Erro ao comunicar com o servidor.</div>';
            });
    }

    function renderCompetitionsGrid(competitions) {
        if (!competitionsList) return;

        if (competitions.length === 0) {
            competitionsList.innerHTML = '<div class="no-data">Nenhuma competição encontrada.</div>';
            return;
        }

        let html = '';
        competitions.forEach(c => {
            const sampleLogos = (c.sample_teams || []).map(t => 
                `<img class="comp-team-avatar" src="${getLogo(t.logo_url)}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">`
            ).join('');
            const isFav = FavoritesManager.has('competitions', c.id);

            html += `
                <div class="comp-card" data-comp-id="${c.id}">
                    <div class="comp-card-top">
                        <div class="comp-card-title">${c.name}</div>
                        <div class="comp-card-badges">
                            <button class="fav-star-btn ${isFav ? 'active' : ''}" data-fav-comp="${c.id}" title="Favoritar liga">
                                <span class="material-symbols-outlined">star</span>
                            </button>
                            <span class="comp-badge">${c.total_matches} jogos</span>
                        </div>
                    </div>
                    <div class="comp-card-teams">
                        ${sampleLogos}
                    </div>
                    <div class="comp-card-stats">
                        <span><strong style="color: var(--text-primary);">${c.finished_matches}</strong> encerrados</span>
                        <span>•</span>
                        <span><strong style="color: var(--text-primary);">${c.next_matches}</strong> pendentes</span>
                    </div>
                    <div class="comp-card-action">
                        <span class="material-symbols-outlined" style="font-size: 16px;">leaderboard</span>
                        Ver Detalhes
                    </div>
                </div>
            `;
        });

        competitionsList.innerHTML = html;

        // Fav star clicks on competition cards
        document.querySelectorAll('.fav-star-btn[data-fav-comp]').forEach(star => {
            star.addEventListener('click', (e) => {
                e.stopPropagation();
                const compId = parseInt(star.getAttribute('data-fav-comp'), 10);
                const isNowFav = FavoritesManager.toggle('competitions', { id: compId });
                star.classList.toggle('active', isNowFav);
            });
        });

        // Card clicks to open standings/bracket
        document.querySelectorAll('.comp-card').forEach(card => {
            card.addEventListener('click', () => {
                const compId = parseInt(card.getAttribute('data-comp-id'), 10);
                currentCompId = compId;
                if (compSelect) compSelect.value = compId;
                currentSubTab = 'table';
                switchView('standings');
                loadStandings(compId);
            });
        });
    }

    function loadStandings(compId) {
        if (!compId || compId <= 0) return;
        standingsContent.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';

        fetch(`/api/poltronascore/classificacao.php?id=${compId}`)
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    standingsContent.innerHTML = `<div class="no-data">Erro ao carregar dados: ${res.message}</div>`;
                    return;
                }
                cachedStandingsData = res;

                // Adjust subtabs visibility based on competition structure
                if (btnSubtabTable && btnSubtabBracket) {
                    if (res.has_groups) {
                        btnSubtabTable.innerHTML = '<span class="material-symbols-outlined">grid_view</span> Grupos';
                    } else {
                        btnSubtabTable.innerHTML = '<span class="material-symbols-outlined">leaderboard</span> Classificação';
                    }

                    if (!res.has_standings && res.has_bracket) {
                        // Mata-mata puro: oculta tabela, exibe chaveamento
                        btnSubtabTable.style.display = 'none';
                        btnSubtabBracket.style.display = 'flex';
                        currentSubTab = 'bracket';
                    } else if (res.has_standings && !res.has_bracket) {
                        // Pontos corridos puro: exibe tabela, oculta chaveamento
                        btnSubtabTable.style.display = 'flex';
                        btnSubtabBracket.style.display = 'none';
                        currentSubTab = 'table';
                    } else {
                        // Misto: exibe ambos
                        btnSubtabTable.style.display = 'flex';
                        btnSubtabBracket.style.display = 'flex';
                        if (currentSubTab !== 'bracket' && currentSubTab !== 'rounds') {
                            currentSubTab = 'table';
                        }
                    }

                    // Update active button visually
                    standingsTabBtns.forEach(b => {
                        if (b.getAttribute('data-subtab') === currentSubTab) {
                            b.classList.add('active');
                        } else {
                            b.classList.remove('active');
                        }
                    });
                }

                renderStandingsView(res);
            })
            .catch(() => {
                standingsContent.innerHTML = '<div class="no-data">Falha ao obter informações da competição.</div>';
            });
    }

    function renderStandingsView(data) {
        if (currentSubTab === 'table') {
            renderStandingsTable(data);
        } else if (currentSubTab === 'bracket') {
            renderStandingsBracket(data.bracket);
        } else {
            renderStandingsRounds(data.rounds);
        }
    }

    function renderStandingsTable(data) {
        if (!data.has_standings) {
            if (data.has_bracket) {
                renderStandingsBracket(data.bracket);
                return;
            }
            standingsContent.innerHTML = '<div class="no-data">Esta competição é disputada em formato de mata-mata.</div>';
            return;
        }

        if (data.groups && Object.keys(data.groups).length > 0) {
            let html = '';
            for (const [groupName, teamsList] of Object.entries(data.groups)) {
                html += `
                    <div class="championship-group">
                        <div class="championship-header">${groupName}</div>
                        ${renderSingleTableHtml(teamsList)}
                    </div>
                `;
            }
            standingsContent.innerHTML = html;
        } else {
            const standings = data.standings || [];
            if (standings.length === 0) {
                standingsContent.innerHTML = '<div class="no-data">Nenhum dado de classificação disponível para esta competição ainda.</div>';
                return;
            }
            standingsContent.innerHTML = renderSingleTableHtml(standings);
        }
    }

    function renderSingleTableHtml(standings) {
        const totalTeams = standings.length;
        let tableRows = '';

        standings.forEach(t => {
            let zoneClass = '';
            if (t.position <= 4) {
                zoneClass = 'zone-g4';
            } else if (totalTeams >= 8 && t.position > totalTeams - 4) {
                zoneClass = 'zone-relegation';
            }

            // Form badges (last 5)
            let formHtml = '';
            if (t.form && t.form.length > 0) {
                formHtml = t.form.map(f => {
                    let badgeType = 'draw';
                    if (f.result === 'V') badgeType = 'win';
                    if (f.result === 'D') badgeType = 'loss';
                    return `<span class="form-badge ${badgeType}" title="${f.result === 'V' ? 'Vitória' : (f.result === 'D' ? 'Derrota' : 'Empate')} vs ${f.opponent} (${f.score})">${f.result}</span>`;
                }).join('');
            } else {
                formHtml = '<span style="color: var(--text-secondary); font-size: 11px;">-</span>';
            }

            tableRows += `
                <tr class="${zoneClass}">
                    <td class="col-pos">
                        <span class="pos-indicator">${t.position}</span>
                    </td>
                    <td class="col-team">
                        <div class="team-cell">
                            <img class="team-cell-logo" src="${getLogo(t.logo)}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                            <span>${t.team}</span>
                        </div>
                    </td>
                    <td>${t.played}</td>
                    <td>${t.won}</td>
                    <td>${t.drawn}</td>
                    <td>${t.lost}</td>
                    <td>${t.goal_diff > 0 ? '+' + t.goal_diff : t.goal_diff}</td>
                    <td class="col-pts">${t.points}</td>
                    <td class="col-form">
                        <div class="form-badges-container">
                            ${formHtml}
                        </div>
                    </td>
                </tr>
            `;
        });

        return `
            <div class="standings-card">
                <div class="standings-table-wrapper">
                    <table class="standings-table">
                        <thead>
                            <tr>
                                <th class="col-pos">#</th>
                                <th class="col-team">Time</th>
                                <th title="Jogos Disputados">J</th>
                                <th title="Vitórias">V</th>
                                <th title="Empates">E</th>
                                <th title="Derrotas">D</th>
                                <th title="Saldo de Gols">SG</th>
                                <th class="col-pts" title="Pontos">PTS</th>
                                <th class="col-form" title="Últimos Jogos">Forma</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows}
                        </tbody>
                    </table>
                </div>
                <div class="standings-legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background: var(--accent-cyan);"></div>
                        <span>Zona de Classificação (G4)</span>
                    </div>
                    ${totalTeams >= 8 ? `
                    <div class="legend-item">
                        <div class="legend-color" style="background: var(--accent-red);"></div>
                        <span>Zona de Rebaixamento</span>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    function renderStandingsBracket(bracket) {
        if (!bracket || bracket.length === 0) {
            standingsContent.innerHTML = '<div class="no-data">Nenhum confronto de mata-mata registrado nesta competição.</div>';
            return;
        }

        let html = '<div class="bracket-phases-container">';

        bracket.forEach(phase => {
            html += `
                <div class="bracket-phase-card">
                    <div class="bracket-phase-header">${phase.phase_name}</div>
                    <div class="bracket-grid">
            `;

            phase.matches.forEach(m => {
                const homeScoreStr = m.home_score !== null ? m.home_score : '-';
                const awayScoreStr = m.away_score !== null ? m.away_score : '-';

                const homePenStr = (m.home_penalties !== null && (m.home_penalties > 0 || m.away_penalties > 0)) ? `<span class="bracket-penalties">(${m.home_penalties})</span>` : '';
                const awayPenStr = (m.away_penalties !== null && (m.home_penalties > 0 || m.away_penalties > 0)) ? `<span class="bracket-penalties">(${m.away_penalties})</span>` : '';

                const homeWinnerClass = (m.winner === 'home') ? 'winner' : (m.winner === 'away' ? 'loser' : '');
                const awayWinnerClass = (m.winner === 'away') ? 'winner' : (m.winner === 'home' ? 'loser' : '');

                html += `
                    <div class="bracket-match match-card" data-id="${m.id}">
                        <div class="bracket-team-line ${homeWinnerClass}">
                            <div class="bracket-team-info">
                                <img class="team-logo" src="${getLogo(m.home_logo)}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                                <span class="bracket-team-name">${m.home_team}</span>
                            </div>
                            <div class="bracket-score-badge">
                                ${homeScoreStr} ${homePenStr}
                            </div>
                        </div>
                        <div class="bracket-team-line ${awayWinnerClass}">
                            <div class="bracket-team-info">
                                <img class="team-logo" src="${getLogo(m.away_logo)}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                                <span class="bracket-team-name">${m.away_team}</span>
                            </div>
                            <div class="bracket-score-badge">
                                ${awayScoreStr} ${awayPenStr}
                            </div>
                        </div>
                        <div class="bracket-match-footer">
                            <span>${m.match_date} ${m.match_time ? 'às ' + m.match_time : ''}</span>
                            <span>${m.stadium || 'Estádio'}</span>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        });

        html += '</div>';
        standingsContent.innerHTML = html;
        bindMatchCardClicks();
    }

    function renderStandingsRounds(rounds) {
        if (!rounds || Object.keys(rounds).length === 0) {
            standingsContent.innerHTML = '<div class="no-data">Nenhuma rodada ou partida encontrada para esta competição.</div>';
            return;
        }

        let html = '';
        for (const [roundName, matches] of Object.entries(rounds)) {
            html += `
                <div class="championship-group">
                    <div class="championship-header">${roundName}</div>
            `;

            matches.forEach(match => {
                const homeScore = match.home_score !== null ? match.home_score : 0;
                const awayScore = match.away_score !== null ? match.away_score : 0;

                const scoreHtml = match.status === 'next' 
                    ? `<span class="score-display"><span class="score-divider">vs</span></span>`
                    : `<span class="score-display"><span>${homeScore}</span><span class="score-divider">-</span><span>${awayScore}</span></span>`;

                const timeStatusHtml = match.status === 'live'
                    ? `<span class="badge-live">AO VIVO</span>`
                    : `<span>${match.match_date} ${match.match_time ? 'às ' + match.match_time : ''}</span>`;

                html += `
                    <div class="match-card" data-id="${match.id}">
                        <div class="match-top">
                            <div class="match-time-status">
                                ${timeStatusHtml}
                            </div>
                            <div class="match-stadium">${match.stadium || 'Local não informado'}</div>
                        </div>
                        <div class="match-teams-score">
                            <div class="team-info home">
                                <span class="team-name">${match.home_team}</span>
                                <img class="team-logo" src="${getLogo(match.home_logo)}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                            </div>
                            ${scoreHtml}
                            <div class="team-info away">
                                <img class="team-logo" src="${getLogo(match.away_logo)}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                                <span class="team-name">${match.away_team}</span>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `</div>`;
        }

        standingsContent.innerHTML = html;
        bindMatchCardClicks();
    }

    // =========================================================================
    // 4. MATCH DETAILS MODAL & LINEUPS (FASE 3)
    // =========================================================================

    function bindMatchCardClicks() {
        document.querySelectorAll('.match-card').forEach(card => {
            card.addEventListener('click', (e) => {
                e.stopPropagation();
                const matchId = card.getAttribute('data-id');
                if (matchId) {
                    openMatchDetails(matchId);
                }
            });
        });

        // Clickable team names/logos on match cards
        document.querySelectorAll('.team-info').forEach(tInfo => {
            tInfo.classList.add('clickable-entity');
            tInfo.addEventListener('click', (e) => {
                e.stopPropagation();
                const nameEl = tInfo.querySelector('.team-name');
                if (nameEl && nameEl.textContent) {
                    openClubModal(0, nameEl.textContent.trim());
                }
            });
        });

        // Clickable team lines in bracket
        document.querySelectorAll('.bracket-team-info').forEach(bInfo => {
            bInfo.classList.add('clickable-entity');
            bInfo.addEventListener('click', (e) => {
                e.stopPropagation();
                const nameEl = bInfo.querySelector('.bracket-team-name');
                if (nameEl && nameEl.textContent) {
                    openClubModal(0, nameEl.textContent.trim());
                }
            });
        });

        // Clickable team cells in standings table
        document.querySelectorAll('.team-cell').forEach(tCell => {
            tCell.classList.add('clickable-entity');
            tCell.addEventListener('click', (e) => {
                e.stopPropagation();
                const nameEl = tCell.querySelector('span');
                if (nameEl && nameEl.textContent) {
                    openClubModal(0, nameEl.textContent.trim());
                }
            });
        });
    }

    function openMatchDetails(matchId) {
        // Reset to events subtab
        if (btnMatchEvents && btnMatchLineups) {
            btnMatchEvents.classList.add('active');
            btnMatchLineups.classList.remove('active');
            if (matchTabEvents) matchTabEvents.classList.add('active');
            if (matchTabLineups) matchTabLineups.classList.remove('active');
        }

        mEventsList.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
        if (mLineupsContent) {
            mLineupsContent.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
        }
        bringModalToFront(modalOverlay);
        modalOverlay.classList.add('active');

        fetch(`/api/poltronascore/jogo.php?id=${matchId}`)
            .then(res => res.json())
            .then(res => {
                if (!res.success || !res.match) {
                    mEventsList.innerHTML = `<div class="no-data">Detalhes não disponíveis para esta partida.</div>`;
                    if (mLineupsContent) mLineupsContent.innerHTML = `<div class="no-data">Escalações não disponíveis.</div>`;
                    return;
                }

                const match = res.match;

                // Header
                mChampionship.textContent = match.championship || 'Competição';
                mInfoRow.textContent = `${match.rodada ? match.rodada + ' • ' : ''}${match.match_date} • ${match.stadium || 'Local não informado'}`;

                // Teams
                mHomeName.textContent = match.home_team;
                mHomeLogo.onerror = () => { mHomeLogo.onerror = null; mHomeLogo.src = DEFAULT_LOGO; };
                mHomeLogo.src = getLogo(match.home_logo);

                mAwayName.textContent = match.away_team;
                mAwayLogo.onerror = () => { mAwayLogo.onerror = null; mAwayLogo.src = DEFAULT_LOGO; };
                mAwayLogo.src = getLogo(match.away_logo);

                // Make team boards clickable to open Club Profile
                const homeTeamBoard = mHomeName.closest('.modal-board-team');
                if (homeTeamBoard) {
                    homeTeamBoard.style.cursor = 'pointer';
                    homeTeamBoard.onclick = () => openClubModal(match.home_id || 0, match.home_team);
                }
                const awayTeamBoard = mAwayName.closest('.modal-board-team');
                if (awayTeamBoard) {
                    awayTeamBoard.style.cursor = 'pointer';
                    awayTeamBoard.onclick = () => openClubModal(match.away_id || 0, match.away_team);
                }

                // Score
                if (match.status === 'next') {
                    mScore.textContent = 'vs';
                } else {
                    mScore.textContent = `${match.home_score} - ${match.away_score}`;
                }

                // Penalties
                if (match.home_penalties !== null && match.away_penalties !== null && (match.home_penalties > 0 || match.away_penalties > 0)) {
                    mPenalties.textContent = `Pênaltis: (${match.home_penalties}) - (${match.away_penalties})`;
                    mPenalties.style.display = 'block';
                } else {
                    mPenalties.style.display = 'none';
                }

                // Scorers
                mHomeScorers.textContent = match.home_scorers || '';
                mAwayScorers.textContent = match.away_scorers || '';

                // Favorite Button
                if (modalFavBtn) {
                    const isFav = FavoritesManager.has('matches', match.id);
                    modalFavBtn.classList.toggle('active', isFav);
                    modalFavBtn.onclick = (e) => {
                        e.stopPropagation();
                        const nowFav = FavoritesManager.toggle('matches', { id: match.id, home: match.home_team, away: match.away_team });
                        modalFavBtn.classList.toggle('active', nowFav);
                        if (currentFavSubtab === 'matches' && document.getElementById('view-favorites').classList.contains('active')) {
                            loadFavorites('matches');
                        }
                    };
                }

                // Events
                renderEvents(res.events || [], match.home_team, match.away_team);

                // Lineups (Fase 3)
                renderLineups(res.lineups, match.home_team, match.away_team, match.home_logo, match.away_logo, match.home_id, match.away_id);
            })
            .catch(() => {
                mEventsList.innerHTML = '<div class="no-data">Falha ao obter detalhes da partida.</div>';
                if (mLineupsContent) mLineupsContent.innerHTML = '<div class="no-data">Falha ao carregar escalações.</div>';
            });
    }

    window.poltronaOpenMatchDetails = function(matchId) {
        openMatchDetails(matchId);
    };

    function renderEvents(events, homeTeamName, awayTeamName) {
        if (!events || events.length === 0) {
            mEventsList.innerHTML = '<div class="no-data">Nenhum evento registrado nesta partida.</div>';
            return;
        }

        let html = `
            <div class="timeline-header">
                <span class="th-home">${homeTeamName || 'Mandante'}</span>
                <span class="th-away">${awayTeamName || 'Visitante'}</span>
            </div>
            <div class="event-timeline">
        `;

        // Renderizar eventos na ordem cronológica decrescente fornecida pela API (mais recentes no topo, primeiros no fim)
        events.forEach(ev => {
            const isHome = (ev.side === 'home' || ev.is_home === true);
            const type = (ev.type || '').toLowerCase();
            const desc = ev.description || '';

            // Ícones e Cartões Preenchidos
            let iconHtml = '';
            if (type === 'goal' || type === 'lance-gol') {
                iconHtml = '<span class="material-symbols-outlined tl-icon-goal" title="Gol">sports_soccer</span>';
            } else if (type === 'yellow-card' || type === 'yellow_card' || type === 'cartao-amarelo' || (type === 'lance-cartao' && !desc.toLowerCase().includes('vermelho'))) {
                iconHtml = '<span class="card-rect yellow" title="Cartão Amarelo"></span>';
            } else if (type === 'red-card' || type === 'red_card' || type === 'cartao-vermelho' || (type === 'lance-cartao' && desc.toLowerCase().includes('vermelho'))) {
                iconHtml = '<span class="card-rect red" title="Cartão Vermelho"></span>';
            } else if (type === 'own-goal' || type === 'own_goal') {
                iconHtml = '<span class="material-symbols-outlined tl-icon-own-goal" title="Gol Contra">sports_soccer</span>';
            } else if (type === 'substitution' || type === 'lance-substituicao') {
                iconHtml = '<span class="material-symbols-outlined" style="font-size: 16px; color: var(--accent-emerald);" title="Substituição">sync_alt</span>';
            } else {
                iconHtml = '<span class="material-symbols-outlined" style="font-size: 16px; color: var(--accent-cyan);">info</span>';
            }

            const minuteBadge = `<span class="tl-minute-badge">${ev.minute || '-'}</span>`;
            const playerName = ev.player_name || ev.team_name || 'Lance';

            const contentBlock = `
                <div class="tl-text">
                    <span class="tl-player clickable-entity" onclick="window.poltronaOpenPlayerModal(0, '${playerName.replace(/'/g, "\\'")}')">${playerName}</span>
                    ${desc ? `<span class="tl-desc">${desc}</span>` : ''}
                </div>
            `;

            if (isHome) {
                html += `
                    <div class="tl-row tl-home">
                        <div class="tl-content tl-content-home">
                            ${contentBlock}
                            <div class="tl-icon-box">${iconHtml}</div>
                        </div>
                        <div class="tl-center">${minuteBadge}</div>
                        <div class="tl-content tl-content-away empty"></div>
                    </div>
                `;
            } else {
                html += `
                    <div class="tl-row tl-away">
                        <div class="tl-content tl-content-home empty"></div>
                        <div class="tl-center">${minuteBadge}</div>
                        <div class="tl-content tl-content-away">
                            <div class="tl-icon-box">${iconHtml}</div>
                            ${contentBlock}
                        </div>
                    </div>
                `;
            }
        });

        html += '</div>';
        mEventsList.innerHTML = html;
    }

    // =========================================================================
    // 5. 2D TACTICAL PITCH & LINEUPS (FASE 3)
    // =========================================================================

    function groupPlayersByLine(starters) {
        const gk = [];
        const def = [];
        const mid = [];
        const fwd = [];

        starters.forEach((p, idx) => {
            const pos = strtoupper(trim(p.position || ''));
            if (pos === 'G' || pos === 'GK' || pos === 'GOLEIRO' || idx === 0 && starters.length === 11) {
                gk.push(p);
            } else if (['Z', 'LD', 'LE', 'AD', 'AE', 'DF', 'CB', 'RB', 'LB', 'DEF'].includes(pos)) {
                def.push(p);
            } else if (['V', 'MC', 'MD', 'ME', 'MA', 'M', 'MF', 'DM', 'AM', 'CM', 'RM', 'LM', 'MEI'].includes(pos)) {
                mid.push(p);
            } else if (['CA', 'SA', 'PD', 'PE', 'A', 'FW', 'ST', 'CF', 'RW', 'LW', 'ATA'].includes(pos)) {
                fwd.push(p);
            } else {
                // Fallback: se não tiver posição definida, distribuir equilibrado
                if (idx < 1) gk.push(p);
                else if (idx <= 4) def.push(p);
                else if (idx <= 8) mid.push(p);
                else fwd.push(p);
            }
        });

        return { gk, def, mid, fwd };
    }

    function strtoupper(str) {
        return (str || '').toUpperCase();
    }

    function trim(str) {
        return (str || '').trim();
    }

    function getRatingClass(r) {
        const val = parseFloat(r);
        if (isNaN(val) || val <= 0) return '';
        if (val >= 7.0) return 'rating-high';
        if (val >= 6.0) return 'rating-med';
        return 'rating-low';
    }

    function renderLineups(lineups, homeTeam, awayTeam, homeLogo, awayLogo, homeId, awayId) {
        if (!mLineupsContent) return;

        if (!lineups || !lineups.has_lineups) {
            mLineupsContent.innerHTML = '<div class="no-data">Escalações não disponíveis para esta partida.</div>';
            return;
        }

        const homeStarters = lineups.home.starters || [];
        const awayStarters = lineups.away.starters || [];
        const homeBench = lineups.home.bench || [];
        const awayBench = lineups.away.bench || [];

        const homeGroup = groupPlayersByLine(homeStarters);
        const awayGroup = groupPlayersByLine(awayStarters);

        const renderPitchRow = (players, sideClass) => {
            if (!players || players.length === 0) return '';
            const nodesHtml = players.map(p => `
                <div class="pitch-player-node ${sideClass}" data-player-id="${p.id}" data-player-name="${p.name}" onclick="window.poltronaOpenPlayerModal(${p.id}, '${p.name.replace(/'/g, "\\'")}')">
                    <div class="pitch-player-circle">
                        <span>${p.number ? p.number : (p.position === 'G' ? '1' : '')}</span>
                        ${p.level > 0 ? `<span class="pitch-player-level">${p.level}</span>` : ''}
                    </div>
                    <span class="pitch-player-name" title="${p.name}">${p.name}</span>
                    <span class="pitch-player-pos">${p.position}</span>
                    ${p.rating !== null && p.rating !== undefined ? `<span class="pitch-player-rating ${getRatingClass(p.rating)}">${Number(p.rating).toFixed(1)}</span>` : ''}
                </div>
            `).join('');

            return `<div class="pitch-row">${nodesHtml}</div>`;
        };

        // Pitch HTML
        let html = `
            <div class="pitch-container">
                <div class="pitch-center-circle"></div>
                <div class="pitch-box-top"></div>
                <div class="pitch-box-bottom"></div>

                <!-- Home Team Half (Top) -->
                <div class="pitch-team-half">
                    <div class="pitch-team-header">
                        <span class="clickable-entity" onclick="window.poltronaOpenClubModal(${homeId || 0}, '${homeTeam.replace(/'/g, "\\'")}')">${homeTeam}</span>
                        <span class="pitch-formation-badge">${lineups.home.formation || '4-4-2'}</span>
                    </div>
                    ${renderPitchRow(homeGroup.gk, 'home')}
                    ${renderPitchRow(homeGroup.def, 'home')}
                    ${renderPitchRow(homeGroup.mid, 'home')}
                    ${renderPitchRow(homeGroup.fwd, 'home')}
                </div>

                <!-- Away Team Half (Bottom - Mirrored) -->
                <div class="pitch-team-half">
                    ${renderPitchRow(awayGroup.fwd, 'away')}
                    ${renderPitchRow(awayGroup.mid, 'away')}
                    ${renderPitchRow(awayGroup.def, 'away')}
                    ${renderPitchRow(awayGroup.gk, 'away')}
                    <div class="pitch-team-header" style="margin-top: 4px;">
                        <span class="clickable-entity" onclick="window.poltronaOpenClubModal(${awayId || 0}, '${awayTeam.replace(/'/g, "\\'")}')">${awayTeam}</span>
                        <span class="pitch-formation-badge">${lineups.away.formation || '4-4-2'}</span>
                    </div>
                </div>
            </div>
        `;

        // Starters Roster (Titulares)
        const renderPlayerRow = (p) => `
            <div class="lineup-player-row" onclick="window.poltronaOpenPlayerModal(${p.id}, '${p.name.replace(/'/g, "\\'")}')">
                <span class="lineup-p-num">${p.number || '-'}</span>
                <span class="lineup-p-pos">${p.position}</span>
                <span class="lineup-p-name">${p.name}</span>
                ${p.rating !== null && p.rating !== undefined ? `<span class="lineup-p-rating ${getRatingClass(p.rating)}">${Number(p.rating).toFixed(1)}</span>` : ''}
                ${p.level > 0 ? `<span class="lineup-p-lvl">${p.level}</span>` : ''}
            </div>
        `;

        html += `
            <div class="lineup-section-card">
                <div class="lineup-section-title">
                    <span class="material-symbols-outlined" style="font-size: 16px;">groups</span>
                    Titulares
                </div>
                <div class="lineup-grid-cols">
                    <div class="lineup-team-col">
                        <div class="lineup-team-title">${homeTeam}</div>
                        ${homeStarters.map(renderPlayerRow).join('')}
                    </div>
                    <div class="lineup-team-col">
                        <div class="lineup-team-title">${awayTeam}</div>
                        ${awayStarters.map(renderPlayerRow).join('')}
                    </div>
                </div>
            </div>
        `;

        // Bench Roster (Reservas)
        if (homeBench.length > 0 || awayBench.length > 0) {
            html += `
                <div class="lineup-section-card">
                    <div class="lineup-section-title">
                        <span class="material-symbols-outlined" style="font-size: 16px;">airline_seat_recline_normal</span>
                        Banco de Reservas
                    </div>
                    <div class="lineup-grid-cols">
                        <div class="lineup-team-col">
                            <div class="lineup-team-title">${homeTeam}</div>
                            ${homeBench.length > 0 ? homeBench.map(renderPlayerRow).join('') : '<span style="font-size: 11px; color: var(--text-secondary);">-</span>'}
                        </div>
                        <div class="lineup-team-col">
                            <div class="lineup-team-title">${awayTeam}</div>
                            ${awayBench.length > 0 ? awayBench.map(renderPlayerRow).join('') : '<span style="font-size: 11px; color: var(--text-secondary);">-</span>'}
                        </div>
                    </div>
                </div>
            `;
        }

        mLineupsContent.innerHTML = html;
    }

    // =========================================================================
    // 6. PLAYER PROFILE MODAL (FASE 2)
    // =========================================================================

    window.poltronaOpenPlayerModal = function(playerId, playerName) {
        openPlayerModal(playerId, playerName);
    };

    function openPlayerModal(playerId, playerName) {
        if (!playerModal || !playerModalBody) return;

        bringModalToFront(playerModal);
        playerModal.classList.add('active');
        playerModalBody.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';

        const url = `/api/poltronascore/jogador.php?id=${playerId || 0}&nome=${encodeURIComponent(playerName || '')}`;

        fetch(url)
            .then(res => res.json())
            .then(res => {
                if (!res.success || !res.player) {
                    playerModalBody.innerHTML = `<div class="no-data">Jogador não encontrado no banco de dados.</div>`;
                    return;
                }

                renderPlayerProfile(res.player);
            })
            .catch(() => {
                playerModalBody.innerHTML = `<div class="no-data">Falha ao obter informações do jogador.</div>`;
            });
    }

    function renderPlayerProfile(p) {
        const photoSrc = p.photo ? p.photo : DEFAULT_LOGO;
        const flagHtml = p.flag ? `<img src="${p.flag}" style="width: 16px; height: 11px; border-radius: 2px; object-fit: cover; vertical-align: middle;" alt="">` : '';

        // Favorite Button
        if (playerModalFavBtn) {
            const isFav = FavoritesManager.has('players', p.id);
            playerModalFavBtn.classList.toggle('active', isFav);
            playerModalFavBtn.onclick = (e) => {
                e.stopPropagation();
                const nowFav = FavoritesManager.toggle('players', { id: p.id, name: p.name });
                playerModalFavBtn.classList.toggle('active', nowFav);
                if (currentFavSubtab === 'players' && document.getElementById('view-favorites').classList.contains('active')) {
                    loadFavorites('players');
                }
            };
        }

        // Club chip
        let clubChipHtml = '';
        if (p.club) {
            const clubLogo = getLogo(p.club.logo);
            clubChipHtml = `
                <div class="player-club-chip" onclick="window.poltronaOpenClubModal(${p.club.id}, '${p.club.name.replace(/'/g, "\\'")}')">
                    <img class="player-club-logo" src="${clubLogo}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                    <span class="player-club-name">${p.club.name}</span>
                    ${p.club.jersey_number ? `<span style="font-size: 10px; color: var(--accent-cyan); font-weight: 700;">#${p.club.jersey_number}</span>` : ''}
                </div>
            `;
        }

        // Stats Summary
        const cs = p.career_stats || { matches: 0, goals: 0, yellow_cards: 0, red_cards: 0 };
        const rawAvg = p.average_rating !== null && p.average_rating !== undefined ? p.average_rating : (cs.average_rating || null);
        const avgRatingVal = rawAvg !== null ? Number(rawAvg).toFixed(1) : '-';
        const avgRatingClass = rawAvg !== null ? getRatingClass(rawAvg) : '';

        const statsHtml = `
            <div class="stats-summary-grid">
                <div class="stat-counter-card">
                    <div class="stat-counter-val">${cs.matches}</div>
                    <div class="stat-counter-label">Jogos</div>
                </div>
                <div class="stat-counter-card">
                    <div class="stat-counter-val" style="color: #22c55e;">${cs.goals}</div>
                    <div class="stat-counter-label">Gols</div>
                </div>
                <div class="stat-counter-card">
                    <div class="stat-counter-val ${avgRatingClass}" style="${avgRatingVal === '-' ? 'color: var(--text-secondary);' : ''}">
                        ${avgRatingVal !== '-' ? '★ ' + avgRatingVal : '-'}
                    </div>
                    <div class="stat-counter-label">Nota Média</div>
                </div>
                <div class="stat-counter-card">
                    <div class="stat-counter-val" style="color: #facc15;">${cs.yellow_cards}</div>
                    <div class="stat-counter-label">Amarelos</div>
                </div>
                <div class="stat-counter-card">
                    <div class="stat-counter-val" style="color: #ef4444;">${cs.red_cards}</div>
                    <div class="stat-counter-label">Vermelhos</div>
                </div>
            </div>
        `;

        // Helper for attribute bars (scale maxVal: 7 for technical, 10 for goalkeeper, 5 for speed & strength)
        const renderAttrBar = (label, val, maxVal = 7) => {
            const numVal = parseFloat(val) || 0;
            let pct = (numVal / maxVal) * 100;
            pct = Math.min(100, Math.max(0, pct));

            let barClass = 'med';
            if (pct >= 72) barClass = 'high';
            else if (pct <= 42) barClass = 'low';

            return `
                <div class="attr-row">
                    <div class="attr-header">
                        <span class="attr-label">${label}</span>
                        <span class="attr-val">${numVal} <span style="font-size: 9px; color: var(--text-secondary); font-weight: 500;">/ ${maxVal}</span></span>
                    </div>
                    <div class="attr-bar-track">
                        <div class="attr-bar-fill ${barClass}" style="width: ${pct}%;"></div>
                    </div>
                </div>
            `;
        };

        // Attribute groups
        const tech = p.attributes.technical || {};
        const phys = p.attributes.physical_mental || {};
        const gk = p.attributes.goalkeeper || {};

        let techHtml = '';
        if (!p.is_goalkeeper) {
            techHtml = `
                <div class="attributes-card">
                    <div class="attributes-group-title">
                        <span class="material-symbols-outlined" style="font-size: 16px;">fitness_center</span>
                        Atributos Técnicos
                    </div>
                    <div class="attribute-bars-grid">
                        ${renderAttrBar('Técnica', tech.tecnica, 7)}
                        ${renderAttrBar('Finalização', tech.finalizacao, 7)}
                        ${renderAttrBar('Controle de Bola', tech.controle_bola, 7)}
                        ${renderAttrBar('Faro de Gol', tech.faro_gol, 7)}
                        ${renderAttrBar('Visão de Jogo', tech.visao_jogo, 7)}
                        ${renderAttrBar('Cruzamentos', tech.cruzamentos, 7)}
                        ${renderAttrBar('Cabeceamento', tech.cabeceamento, 7)}
                        ${renderAttrBar('Desarme', tech.desarme, 7)}
                        ${renderAttrBar('Marcação', tech.marcacao, 7)}
                    </div>
                </div>
            `;
        }

        let physHtml = '';
        if (!p.is_goalkeeper) {
            physHtml = `
                <div class="attributes-card">
                    <div class="attributes-group-title">
                        <span class="material-symbols-outlined" style="font-size: 16px;">bolt</span>
                        Físico & Mental
                    </div>
                    <div class="attribute-bars-grid">
                        ${renderAttrBar('Velocidade', phys.velocidade, 5)}
                        ${renderAttrBar('Força', phys.forca, 5)}
                    </div>
                    <div class="player-mentality-row">
                        <span class="player-mentality-label">Mentalidade:</span>
                        <span class="player-mentality-pill">${p.mentality || 'Neutro'}</span>
                    </div>
                </div>
            `;
        } else {
            physHtml = `
                <div class="attributes-card">
                    <div class="attributes-group-title">
                        <span class="material-symbols-outlined" style="font-size: 16px;">psychology</span>
                        Mentalidade
                    </div>
                    <div class="player-mentality-row" style="margin-top: 0; padding-top: 0; border-top: none;">
                        <span class="player-mentality-label">Estilo / Perfil:</span>
                        <span class="player-mentality-pill">${p.mentality || 'Neutro'}</span>
                    </div>
                </div>
            `;
        }

        let gkHtml = '';
        if (p.is_goalkeeper) {
            gkHtml = `
                <div class="attributes-card">
                    <div class="attributes-group-title">
                        <span class="material-symbols-outlined" style="font-size: 16px;">sports_handball</span>
                        Atributos de Goleiro
                    </div>
                    <div class="attribute-bars-grid">
                        ${renderAttrBar('Reflexos', gk.reflexos, 10)}
                        ${renderAttrBar('Segurança', gk.seguranca, 10)}
                        ${renderAttrBar('Saídas do Gol', gk.saidas, 10)}
                        ${renderAttrBar('Jogo Aéreo', gk.jogo_aereo, 10)}
                        ${renderAttrBar('Lançamentos', gk.lancamentos, 10)}
                        ${renderAttrBar('Defesa de Pênaltis', gk.defesa_penaltis, 10)}
                    </div>
                </div>
            `;
        }

        // Recent Matches List
        let recentMatchesHtml = '';
        if (p.recent_matches && p.recent_matches.length > 0) {
            const matchesRows = p.recent_matches.map(rm => {
                let badgeClass = 'draw';
                if (rm.result === 'V') badgeClass = 'win';
                if (rm.result === 'D') badgeClass = 'loss';

                const ratingBadge = (rm.rating !== null && rm.rating !== undefined && rm.rating > 0)
                    ? `<span class="match-rating-badge ${getRatingClass(rm.rating)}">${Number(rm.rating).toFixed(1)}</span>`
                    : '';

                return `
                    <div class="bracket-match" style="padding: 8px 10px; margin-bottom: 6px;" onclick="openMatchDetails(${rm.match_id})">
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span class="form-badge ${badgeClass}">${rm.result}</span>
                                <span style="font-weight: 700; color: var(--text-primary);">vs ${rm.opponent}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                ${ratingBadge}
                                <div style="font-family: var(--font-title); font-weight: 800; color: var(--accent-cyan); font-size: 13px;">
                                    ${rm.score}
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 10px; color: var(--text-secondary); margin-top: 4px;">
                            <span>${rm.competition} • ${rm.date}</span>
                            <span>${rm.starter ? 'Titular (' + rm.position + ')' : 'Reserva'}</span>
                        </div>
                    </div>
                `;
            }).join('');

            recentMatchesHtml = `
                <div class="attributes-card">
                    <div class="attributes-group-title">
                        <span class="material-symbols-outlined" style="font-size: 16px;">history</span>
                        Últimas Partidas
                    </div>
                    <div>
                        ${matchesRows}
                    </div>
                </div>
            `;
        }

        playerModalBody.innerHTML = `
            <!-- Player Hero Card -->
            <div class="player-hero-card">
                <div class="player-hero-photo-box">
                    <img class="player-hero-photo" src="${photoSrc}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                    ${p.level > 0 ? `<div class="player-hero-level-badge">${p.level}</div>` : ''}
                </div>
                <div class="player-hero-info">
                    <div class="player-hero-name" title="${p.name}">${p.name}</div>
                    <div class="player-hero-tags">
                        <span class="player-pos-badge">${p.primary_position}</span>
                        ${p.positions && p.positions.length > 1 ? `<span style="font-size: 10px; color: var(--text-secondary);">(${p.positions.join(', ')})</span>` : ''}
                        <span class="player-meta-tag">${flagHtml} ${p.country}</span>
                        ${p.age ? `<span class="player-meta-tag">• ${p.age} anos</span>` : ''}
                        <span class="player-meta-tag"><span class="material-symbols-outlined" style="font-size: 13px;">psychology</span> ${p.mentality || 'Neutro'}</span>
                    </div>
                    ${clubChipHtml}
                </div>
            </div>

            <!-- Stats Summary -->
            ${statsHtml}

            <!-- Attributes Breakdown -->
            ${p.is_goalkeeper ? gkHtml : techHtml}
            ${physHtml}

            <!-- Recent Matches -->
            ${recentMatchesHtml}
        `;
    }

    // =========================================================================
    // 7. CLUB PROFILE MODAL (FASE 4)
    // =========================================================================

    window.poltronaOpenClubModal = function(clubId, clubName) {
        openClubModal(clubId, clubName);
    };

    function openClubModal(clubId, clubName) {
        if (!clubModal || !clubModalBody) return;

        bringModalToFront(clubModal);
        clubModal.classList.add('active');
        clubModalBody.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';

        const url = `/api/poltronascore/clube.php?id=${clubId || 0}&nome=${encodeURIComponent(clubName || '')}`;

        fetch(url)
            .then(res => res.json())
            .then(res => {
                if (!res.success || !res.club) {
                    clubModalBody.innerHTML = `<div class="no-data">Clube não encontrado no banco de dados.</div>`;
                    return;
                }

                renderClubProfile(res.club);
            })
            .catch(() => {
                clubModalBody.innerHTML = `<div class="no-data">Falha ao obter informações do clube.</div>`;
            });
    }

    function renderClubProfile(c) {
        const logoSrc = getLogo(c.logo);
        const flagHtml = c.flag ? `<img src="${c.flag}" style="width: 16px; height: 11px; border-radius: 2px; object-fit: cover; vertical-align: middle;" alt="">` : '';

        // Favorite Button
        if (clubModalFavBtn) {
            const isFav = FavoritesManager.has('clubs', c.id);
            clubModalFavBtn.classList.toggle('active', isFav);
            clubModalFavBtn.onclick = (e) => {
                e.stopPropagation();
                const nowFav = FavoritesManager.toggle('clubs', { id: c.id, name: c.name });
                clubModalFavBtn.classList.toggle('active', nowFav);
                if (currentFavSubtab === 'clubs' && document.getElementById('view-favorites').classList.contains('active')) {
                    loadFavorites('clubs');
                }
            };
        }

        // Stadium info
        const std = c.stadium || {};
        let stadiumHtml = '';
        if (std.name && std.name !== 'Estádio não informado') {
            stadiumHtml = `
                <div class="club-stadium-badge">
                    <span class="material-symbols-outlined" style="font-size: 14px;">stadium</span>
                    <span>${std.name} ${std.capacity > 0 ? '(' + std.capacity_formatted + ' lug.)' : ''}</span>
                </div>
            `;
        }

        // Stats Counters
        const stats = c.stats || {};
        const fullVal = stats.total_market_value_formatted || '-';
        const compactVal = stats.total_market_value_compact || fullVal;
        const statsGridHtml = `
            <div class="stats-summary-grid">
                <div class="stat-counter-card">
                    <div class="stat-counter-val">${stats.squad_count || 0}</div>
                    <div class="stat-counter-label">Elenco</div>
                </div>
                <div class="stat-counter-card">
                    <div class="stat-counter-val">${stats.average_age ? stats.average_age + 'a' : '-'}</div>
                    <div class="stat-counter-label">Média Idade</div>
                </div>
                <div class="stat-counter-card">
                    <div class="stat-counter-val" style="color: var(--accent-emerald);">${stats.average_level || 0}</div>
                    <div class="stat-counter-label">Nível Médio</div>
                </div>
                <div class="stat-counter-card">
                    <div class="stat-counter-val stat-counter-val-market" title="${fullVal}">
                        <span class="market-val-full">${fullVal}</span>
                        <span class="market-val-compact">${compactVal}</span>
                    </div>
                    <div class="stat-counter-label">Valor Total</div>
                </div>
            </div>
        `;

        // Categorized Squad
        const sq = c.squad || {};
        const categories = [
            { key: 'goleiros', title: 'Goleiros', icon: 'sports_handball' },
            { key: 'defensores', title: 'Defensores', icon: 'shield' },
            { key: 'meio_campistas', title: 'Meio-Campistas', icon: 'sync_alt' },
            { key: 'atacantes', title: 'Atacantes', icon: 'sports_soccer' }
        ];

        let squadHtml = '';
        categories.forEach(cat => {
            const players = sq[cat.key] || [];
            if (players.length > 0) {
                const playerGrid = players.map(p => {
                    const pPhoto = p.photo ? p.photo : DEFAULT_LOGO;
                    return `
                        <div class="squad-player-card" onclick="window.poltronaOpenPlayerModal(${p.id}, '${p.name.replace(/'/g, "\\'")}')">
                            <img class="squad-p-avatar" src="${pPhoto}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                            <div class="squad-p-info">
                                <span class="squad-p-name">${p.jersey_number ? '#' + p.jersey_number + ' ' : ''}${p.name}</span>
                                <div class="squad-p-meta">
                                    <span class="player-pos-badge" style="font-size: 9px; padding: 1px 4px;">${p.primary_position}</span>
                                    ${p.age ? `<span>${p.age}a</span>` : ''}
                                    ${p.level > 0 ? `<span style="color: var(--accent-emerald); font-weight: 800;">Nvl ${p.level}</span>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');

                squadHtml += `
                    <div class="squad-category-box">
                        <div class="squad-category-header">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 4px;">${cat.icon}</span>
                            ${cat.title} (${players.length})
                        </div>
                        <div class="squad-players-grid">
                            ${playerGrid}
                        </div>
                    </div>
                `;
            }
        });

        // Fixtures / Match Calendar
        const matchesObj = c.matches || { previous: [], next: [] };
        let calendarHtml = '';

        if (matchesObj.previous.length > 0 || matchesObj.next.length > 0) {
            let prevHtml = '';
            if (matchesObj.previous.length > 0) {
                prevHtml = matchesObj.previous.map(m => {
                    let badgeClass = 'draw';
                    if (m.result === 'V') badgeClass = 'win';
                    if (m.result === 'D') badgeClass = 'loss';

                    return `
                        <div class="bracket-match" style="padding: 8px 10px; margin-bottom: 6px;" onclick="openMatchDetails(${m.id})">
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span class="form-badge ${badgeClass}">${m.result}</span>
                                    <img src="${getLogo(m.opponent_logo)}" style="width: 16px; height: 16px; object-fit: contain;" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                                    <span style="font-weight: 700; color: var(--text-primary);">${m.home ? 'vs' : '@'} ${m.opponent}</span>
                                </div>
                                <div style="font-family: var(--font-title); font-weight: 800; color: var(--accent-cyan); font-size: 13px;">
                                    ${m.score}
                                </div>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 10px; color: var(--text-secondary); margin-top: 4px;">
                                <span>${m.competition} • ${m.date}</span>
                                <span>${m.stadium}</span>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            let nextHtml = '';
            if (matchesObj.next.length > 0) {
                nextHtml = matchesObj.next.map(m => `
                    <div class="bracket-match" style="padding: 8px 10px; margin-bottom: 6px;" onclick="openMatchDetails(${m.id})">
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <img src="${getLogo(m.opponent_logo)}" style="width: 16px; height: 16px; object-fit: contain;" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                                <span style="font-weight: 700; color: var(--text-primary);">${m.home ? 'vs' : '@'} ${m.opponent}</span>
                            </div>
                            <div style="font-family: var(--font-title); font-weight: 700; color: var(--accent-amber); font-size: 11px;">
                                ${m.time ? m.time : 'Agendado'}
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 10px; color: var(--text-secondary); margin-top: 4px;">
                            <span>${m.competition} • ${m.date}</span>
                            <span>${m.stadium}</span>
                        </div>
                    </div>
                `).join('');
            }

            calendarHtml = `
                <div class="attributes-card">
                    <div class="attributes-group-title">
                        <span class="material-symbols-outlined" style="font-size: 16px;">calendar_month</span>
                        Calendário de Partidas
                    </div>
                    ${nextHtml ? `<div style="margin-bottom: 12px;"><div style="font-size: 11px; font-weight: 700; color: var(--accent-amber); margin-bottom: 6px;">Próximos Confrontos</div>${nextHtml}</div>` : ''}
                    ${prevHtml ? `<div><div style="font-size: 11px; font-weight: 700; color: var(--accent-emerald); margin-bottom: 6px;">Resultados Recentes</div>${prevHtml}</div>` : ''}
                </div>
            `;
        }

        clubModalBody.innerHTML = `
            <!-- Club Hero Card -->
            <div class="club-hero-card">
                <img class="club-hero-logo" src="${logoSrc}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                <div class="club-hero-info">
                    <div class="club-hero-name">${c.name}</div>
                    <div class="club-hero-meta">
                        ${c.country ? `<span>${flagHtml} ${c.country}</span>` : ''}
                        ${c.city ? `<span>• ${c.city}</span>` : ''}
                        ${c.foundation ? `<span>• Fund. ${c.foundation}</span>` : ''}
                    </div>
                    ${stadiumHtml}
                </div>
            </div>

            <!-- Stats Overview -->
            ${statsGridHtml}

            <!-- Squad -->
            <div style="margin-bottom: 20px;">
                <div class="attributes-group-title" style="margin-bottom: 12px;">
                    <span class="material-symbols-outlined" style="font-size: 16px;">groups</span>
                    Elenco do Clube
                </div>
                ${squadHtml || '<div class="no-data">Nenhum jogador registrado no elenco deste clube.</div>'}
            </div>

            <!-- Calendar -->
            ${calendarHtml}
        `;
    }

    // =========================================================================
    // 8. FAVORITES VIEW & MANAGEMENT
    // =========================================================================

    function loadFavorites(subtab = 'matches') {
        if (!favoritesContent) return;
        favoritesContent.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';

        const favs = FavoritesManager.get();

        if (subtab === 'matches') {
            const matchIds = (favs.matches || []).map(m => typeof m === 'object' ? m.id : m).filter(id => id > 0);
            const clubIds = (favs.clubs || []).map(c => typeof c === 'object' ? c.id : c).filter(id => id > 0);

            if (matchIds.length === 0 && clubIds.length === 0) {
                favoritesContent.innerHTML = `
                    <div class="no-data">
                        <span class="material-symbols-outlined" style="font-size: 44px; color: var(--text-secondary); margin-bottom: 8px;">star_outline</span><br>
                        Nenhum jogo ou time favoritado ainda.<br>
                        <span style="font-size: 11px; opacity: 0.7;">Favorite partidas ou clubes (tocando na estrela ⭐) para acompanhar a agenda e resultados aqui.</span>
                    </div>
                `;
                return;
            }

            fetch(`/api/poltronascore/favoritos.php?matches=${matchIds.join(',')}&clubs=${clubIds.join(',')}`)
                .then(res => res.json())
                .then(res => {
                    if (!res.success || !res.matches || res.matches.length === 0) {
                        favoritesContent.innerHTML = `
                            <div class="no-data">
                                <span class="material-symbols-outlined" style="font-size: 40px; color: var(--text-secondary); margin-bottom: 8px;">event_busy</span><br>
                                Nenhuma partida recente ou agendada para os seus times e jogos favoritos.
                            </div>
                        `;
                        return;
                    }

                    renderFavoriteMatches(res.matches);
                })
                .catch(() => {
                    favoritesContent.innerHTML = '<div class="no-data">Erro ao carregar jogos favoritos.</div>';
                });

        } else if (subtab === 'clubs') {
            const clubIds = (favs.clubs || []).map(c => typeof c === 'object' ? c.id : c).filter(id => id > 0);
            if (clubIds.length === 0) {
                favoritesContent.innerHTML = `
                    <div class="no-data">
                        <span class="material-symbols-outlined" style="font-size: 44px; color: var(--text-secondary); margin-bottom: 8px;">shield</span><br>
                        Nenhum time favoritado.<br>
                        <span style="font-size: 11px; opacity: 0.7;">Abra o perfil de qualquer clube e clique na estrela ⭐ para acompanhá-lo.</span>
                    </div>
                `;
                return;
            }

            fetch(`/api/poltronascore/favoritos.php?clubs=${clubIds.join(',')}`)
                .then(res => res.json())
                .then(res => {
                    if (!res.success || !res.clubs || res.clubs.length === 0) {
                        favoritesContent.innerHTML = '<div class="no-data">Nenhum clube encontrado.</div>';
                        return;
                    }
                    renderFavoriteClubs(res.clubs);
                })
                .catch(() => {
                    favoritesContent.innerHTML = '<div class="no-data">Erro ao carregar times favoritos.</div>';
                });

        } else if (subtab === 'competitions') {
            const compIds = (favs.competitions || []).map(c => typeof c === 'object' ? c.id : c).filter(id => id > 0);
            if (compIds.length === 0) {
                favoritesContent.innerHTML = `
                    <div class="no-data">
                        <span class="material-symbols-outlined" style="font-size: 44px; color: var(--text-secondary); margin-bottom: 8px;">emoji_events</span><br>
                        Nenhuma liga favoritada.<br>
                        <span style="font-size: 11px; opacity: 0.7;">Na aba Competições, clique na estrela ⭐ de qualquer campeonato para fixá-lo aqui.</span>
                    </div>
                `;
                return;
            }

            fetch(`/api/poltronascore/favoritos.php?competitions=${compIds.join(',')}`)
                .then(res => res.json())
                .then(res => {
                    if (!res.success || !res.competitions || res.competitions.length === 0) {
                        favoritesContent.innerHTML = '<div class="no-data">Nenhuma liga encontrada.</div>';
                        return;
                    }
                    renderFavoriteCompetitions(res.competitions);
                })
                .catch(() => {
                    favoritesContent.innerHTML = '<div class="no-data">Erro ao carregar ligas favoritas.</div>';
                });

        } else if (subtab === 'players') {
            const playerIds = (favs.players || []).map(p => typeof p === 'object' ? p.id : p).filter(id => id > 0);
            if (playerIds.length === 0) {
                favoritesContent.innerHTML = `
                    <div class="no-data">
                        <span class="material-symbols-outlined" style="font-size: 44px; color: var(--text-secondary); margin-bottom: 8px;">person</span><br>
                        Nenhum jogador favoritado.<br>
                        <span style="font-size: 11px; opacity: 0.7;">Abra o perfil de qualquer jogador e clique na estrela ⭐ para acompanhá-lo aqui.</span>
                    </div>
                `;
                return;
            }

            fetch(`/api/poltronascore/favoritos.php?players=${playerIds.join(',')}`)
                .then(res => res.json())
                .then(res => {
                    if (!res.success || !res.players || res.players.length === 0) {
                        favoritesContent.innerHTML = '<div class="no-data">Nenhum jogador encontrado.</div>';
                        return;
                    }
                    renderFavoritePlayers(res.players);
                })
                .catch(() => {
                    favoritesContent.innerHTML = '<div class="no-data">Erro ao carregar jogadores favoritos.</div>';
                });
        }
    }

    function renderFavoriteMatches(matches) {
        let html = '<div class="championship-group" style="margin-top: 10px;">';
        html += '<div class="championship-header"><span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 4px; color: #fbbf24;">star</span> Partidas dos seus Favoritos</div>';

        matches.forEach(match => {
            const isLive = match.status === 'live';
            const isNext = match.status === 'next';

            const scoreHtml = isNext 
                ? `<span class="score-display"><span class="score-divider">vs</span></span>`
                : `<span class="score-display"><span>${match.home_score !== null ? match.home_score : 0}</span><span class="score-divider">-</span><span>${match.away_score !== null ? match.away_score : 0}</span></span>`;

            const timeStatusHtml = isLive
                ? `<span class="badge-live">AO VIVO</span>`
                : `<span>${match.date} ${match.time ? 'às ' + match.time : ''}</span>`;

            html += `
                <div class="match-card" data-id="${match.id}">
                    <div class="match-top">
                        <div class="match-time-status">
                            ${timeStatusHtml}
                        </div>
                        <div class="match-stadium">${match.competition} ${match.rodada ? '• ' + match.rodada : ''}</div>
                    </div>
                    <div class="match-teams-score">
                        <div class="team-info home clickable-entity" onclick="event.stopPropagation(); window.poltronaOpenClubModal(${match.home_id}, '${match.home_team.replace(/'/g, "\\'")}')">
                            <span class="team-name">${match.home_team}</span>
                            <img class="team-logo" src="${getLogo(match.home_logo)}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                        </div>
                        ${scoreHtml}
                        <div class="team-info away clickable-entity" onclick="event.stopPropagation(); window.poltronaOpenClubModal(${match.away_id}, '${match.away_team.replace(/'/g, "\\'")}')">
                            <img class="team-logo" src="${getLogo(match.away_logo)}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                            <span class="team-name">${match.away_team}</span>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        favoritesContent.innerHTML = html;

        favoritesContent.querySelectorAll('.match-card').forEach(card => {
            card.addEventListener('click', () => {
                const matchId = card.getAttribute('data-id');
                if (matchId) openMatchDetails(matchId);
            });
        });
    }

    function renderFavoriteClubs(clubs) {
        let html = '<div class="competitions-grid" style="margin-top: 10px;">';

        clubs.forEach(c => {
            const logoSrc = getLogo(c.logo);
            const flagHtml = c.flag ? `<img src="${c.flag}" style="width: 14px; height: 10px; border-radius: 2px; object-fit: cover; vertical-align: middle;" alt="">` : '';

            html += `
                <div class="comp-card" style="cursor: pointer;" onclick="window.poltronaOpenClubModal(${c.id}, '${c.name.replace(/'/g, "\\'")}')">
                    <div class="comp-card-top">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <img src="${logoSrc}" style="width: 28px; height: 28px; object-fit: contain;" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                            <div class="comp-card-title" style="margin: 0; font-size: 14px;">${c.name}</div>
                        </div>
                        <div class="comp-card-badges">
                            <button class="fav-star-btn active" data-fav-remove-club="${c.id}" title="Remover dos favoritos">
                                <span class="material-symbols-outlined">star</span>
                            </button>
                        </div>
                    </div>
                    <div class="comp-card-stats" style="margin-top: 8px;">
                        <span>${flagHtml} ${c.country}</span>
                    </div>
                    <div class="comp-card-action" style="margin-top: 10px;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">shield</span>
                        Ver Perfil do Clube
                    </div>
                </div>
            `;
        });

        html += '</div>';
        favoritesContent.innerHTML = html;

        favoritesContent.querySelectorAll('[data-fav-remove-club]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const clubId = parseInt(btn.getAttribute('data-fav-remove-club'), 10);
                FavoritesManager.remove('clubs', clubId);
                loadFavorites('clubs');
            });
        });
    }

    function renderFavoriteCompetitions(comps) {
        let html = '<div class="competitions-grid" style="margin-top: 10px;">';

        comps.forEach(c => {
            const logoSrc = getLogo(c.logo);
            const flagHtml = c.flag ? `<img src="${c.flag}" style="width: 14px; height: 10px; border-radius: 2px; object-fit: cover; vertical-align: middle;" alt="">` : '';

            html += `
                <div class="comp-card" data-comp-id="${c.id}">
                    <div class="comp-card-top">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <img src="${logoSrc}" style="width: 28px; height: 28px; object-fit: contain;" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                            <div class="comp-card-title" style="margin: 0; font-size: 14px;">${c.name}</div>
                        </div>
                        <div class="comp-card-badges">
                            <button class="fav-star-btn active" data-fav-remove-comp="${c.id}" title="Remover dos favoritos">
                                <span class="material-symbols-outlined">star</span>
                            </button>
                            <span class="comp-badge">${c.total_teams} times</span>
                        </div>
                    </div>
                    <div class="comp-card-stats" style="margin-top: 8px;">
                        <span>${flagHtml} ${c.country}</span>
                    </div>
                    <div class="comp-card-action" style="margin-top: 10px;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">leaderboard</span>
                        Ver Classificação & Jogos
                    </div>
                </div>
            `;
        });

        html += '</div>';
        favoritesContent.innerHTML = html;

        favoritesContent.querySelectorAll('[data-fav-remove-comp]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const compId = parseInt(btn.getAttribute('data-fav-remove-comp'), 10);
                FavoritesManager.remove('competitions', compId);
                loadFavorites('competitions');
            });
        });

        favoritesContent.querySelectorAll('.comp-card').forEach(card => {
            card.addEventListener('click', () => {
                const compId = parseInt(card.getAttribute('data-comp-id'), 10);
                if (compId > 0) {
                    currentCompId = compId;
                    if (compSelect) compSelect.value = compId;
                    currentSubTab = 'table';
                    switchView('standings');
                    loadStandings(compId);
                }
            });
        });
    }

    function renderFavoritePlayers(players) {
        let html = '<div class="competitions-grid" style="margin-top: 10px;">';

        players.forEach(p => {
            const photoSrc = p.photo ? p.photo : DEFAULT_LOGO;
            const clubLogo = getLogo(p.club_logo);
            const flagHtml = p.flag ? `<img src="${p.flag}" style="width: 14px; height: 10px; border-radius: 2px; object-fit: cover; vertical-align: middle;" alt="">` : '';

            html += `
                <div class="comp-card" style="cursor: pointer;" onclick="window.poltronaOpenPlayerModal(${p.id}, '${p.name.replace(/'/g, "\\'")}')">
                    <div class="comp-card-top">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="player-hero-photo-box" style="width: 44px; height: 44px; min-width: 44px; border-width: 2px;">
                                <img class="player-hero-photo" src="${photoSrc}" alt="" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                                ${p.level > 0 ? `<div class="player-hero-level-badge" style="font-size: 9px; padding: 1px 4px; bottom: -3px; right: -3px;">${p.level}</div>` : ''}
                            </div>
                            <div>
                                <div class="comp-card-title" style="margin: 0; font-size: 14px;">${p.name}</div>
                                <div style="display: flex; align-items: center; gap: 5px; margin-top: 2px;">
                                    <span class="player-pos-badge" style="font-size: 9px; padding: 1px 4px;">${p.position}</span>
                                    <span style="font-size: 11px; color: var(--text-secondary);">${flagHtml} ${p.country}</span>
                                </div>
                            </div>
                        </div>
                        <div class="comp-card-badges">
                            <button class="fav-star-btn active" data-fav-remove-player="${p.id}" title="Remover dos favoritos">
                                <span class="material-symbols-outlined">star</span>
                            </button>
                        </div>
                    </div>
                    ${p.club_name ? `
                    <div class="comp-card-stats" style="margin-top: 8px; display: flex; align-items: center; gap: 6px;">
                        <img src="${clubLogo}" style="width: 14px; height: 14px; object-fit: contain;" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'">
                        <span style="font-weight: 600; color: var(--text-primary);">${p.club_name}</span>
                    </div>
                    ` : ''}
                    <div class="comp-card-action" style="margin-top: 10px;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">person</span>
                        Ver Perfil do Atleta
                    </div>
                </div>
            `;
        });

        html += '</div>';
        favoritesContent.innerHTML = html;

        favoritesContent.querySelectorAll('[data-fav-remove-player]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const playerId = parseInt(btn.getAttribute('data-fav-remove-player'), 10);
                FavoritesManager.remove('players', playerId);
                loadFavorites('players');
            });
        });
    }

    function closeModal() {
        modalOverlay.classList.remove('active');
    }

    // Init loading
    FavoritesManager.initSync();
    loadMatches();
    loadCompetitions(true);
    pollInterval = setInterval(loadMatches, 30000);
});

// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/poltronascore/sw.js')
            .catch(() => {});
    });
}

// PWA Installation prompt handling
let deferredPrompt;
const installBanner = document.getElementById('pwa-install-banner');
const btnInstall = document.getElementById('pwa-btn-install');
const btnCancel = document.getElementById('pwa-btn-cancel');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;

    const isDismissed = localStorage.getItem('pwa-dismissed');
    const dismissedTime = isDismissed ? parseInt(isDismissed, 10) : 0;
    const now = Date.now();

    if (now - dismissedTime > 3 * 24 * 60 * 60 * 1000) {
        setTimeout(() => {
            if (installBanner) installBanner.classList.add('show');
        }, 3000);
    }
});

if (btnInstall) {
    btnInstall.addEventListener('click', () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(() => {
                deferredPrompt = null;
                installBanner.classList.remove('show');
            });
        }
    });
}

if (btnCancel) {
    btnCancel.addEventListener('click', () => {
        if (installBanner) installBanner.classList.remove('show');
        localStorage.setItem('pwa-dismissed', Date.now().toString());
    });
}
