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

    // DOM Elements - Navigation & Views
    const navItems = document.querySelectorAll('.nav-item');
    const appViews = document.querySelectorAll('.app-view');
    const tabButtons = document.querySelectorAll('.tab-btn');
    const standingsTabBtns = document.querySelectorAll('.standings-tab-btn');
    const btnSubtabTable = document.getElementById('btn-subtab-table');
    const btnSubtabBracket = document.getElementById('btn-subtab-bracket');
    const btnSubtabRounds = document.getElementById('btn-subtab-rounds');
    const compSelect = document.getElementById('comp-select');

    // DOM Elements - Containers
    const matchesList = document.getElementById('matches-list');
    const standingsContent = document.getElementById('standings-content');
    const competitionsList = document.getElementById('competitions-list');
    const updateTimeText = document.getElementById('update-time');
    const statusDot = document.getElementById('status-dot');

    // DOM Elements - Modal
    const modalOverlay = document.getElementById('modal-overlay');
    const modalClose = document.getElementById('modal-close');
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

    // Dropdown change for competition
    if (compSelect) {
        compSelect.addEventListener('change', (e) => {
            currentCompId = parseInt(e.target.value, 10) || 0;
            if (currentCompId > 0) {
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

            html += `
                <div class="comp-card" data-comp-id="${c.id}">
                    <div class="comp-card-top">
                        <div class="comp-card-title">${c.name}</div>
                        <div class="comp-card-badges">
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

        // Card clicks to open standings/bracket
        document.querySelectorAll('.comp-card').forEach(card => {
            card.addEventListener('click', () => {
                const compId = parseInt(card.getAttribute('data-comp-id'), 10);
                currentCompId = compId;
                if (compSelect) compSelect.value = compId;
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
                    if (!res.has_standings && res.has_bracket) {
                        // Mata-mata puro: oculta tabela, exibe chaveamento
                        btnSubtabTable.style.display = 'none';
                        btnSubtabBracket.style.display = 'flex';
                        if (currentSubTab === 'table') {
                            currentSubTab = 'bracket';
                        }
                    } else if (res.has_standings && !res.has_bracket) {
                        // Pontos corridos puro: exibe tabela, oculta chaveamento
                        btnSubtabTable.style.display = 'flex';
                        btnSubtabBracket.style.display = 'none';
                        if (currentSubTab === 'bracket') {
                            currentSubTab = 'table';
                        }
                    } else {
                        // Misto: exibe ambos
                        btnSubtabTable.style.display = 'flex';
                        btnSubtabBracket.style.display = 'flex';
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

        if (data.has_groups && data.groups && Object.keys(data.groups).length > 0) {
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
    // 4. MATCH DETAILS MODAL
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
    }

    function openMatchDetails(matchId) {
        mEventsList.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
        modalOverlay.classList.add('active');

        fetch(`/api/poltronascore/jogo.php?id=${matchId}`)
            .then(res => res.json())
            .then(res => {
                if (!res.success || !res.match) {
                    mEventsList.innerHTML = `<div class="no-data">Detalhes não disponíveis para esta partida.</div>`;
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

                // Events
                renderEvents(res.events || []);
            })
            .catch(() => {
                mEventsList.innerHTML = '<div class="no-data">Falha ao obter detalhes da partida.</div>';
            });
    }

    function renderEvents(events) {
        if (!events || events.length === 0) {
            mEventsList.innerHTML = '<div class="no-data">Nenhum evento registrado nesta partida.</div>';
            return;
        }

        let html = '<div class="event-list">';
        events.slice().reverse().forEach(ev => {
            let iconClass = 'event-icon';
            if (ev.type) iconClass += ' ' + ev.type;

            let badgeHtml = ev.team_name ? `<span class="event-team">${ev.team_name}</span>` : '';
            let playerHtml = ev.player_name ? `<span class="event-player">${ev.player_name}</span>` : '';

            html += `
                <div class="event-item">
                    <div class="${iconClass}"></div>
                    <div class="event-time-team">
                        <span class="event-minute">${ev.minute ? ev.minute + ' ' : ''}</span>
                        ${badgeHtml}
                    </div>
                    ${playerHtml}
                    <div class="event-desc">${ev.description}</div>
                </div>
            `;
        });
        html += '</div>';
        mEventsList.innerHTML = html;
    }

    function closeModal() {
        modalOverlay.classList.remove('active');
    }

    // Init loading
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
