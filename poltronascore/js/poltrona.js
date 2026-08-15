// PoltronaScore JS - Core Application (Unified List Experience)
const DEFAULT_LOGO = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzk0YTNiOCI+PHBhdGggZD0iTTEyIDJMNCA1djYuMDljMCA1LjA1IDMuNDEgOS43NiA4IDEwLjkxIDQuNTktMS4xNSA4LTUuODYgOC0xMC45MVY1bC04LTN6bTAgMi4ybDYgMi4yNXY0LjY0YzAgMy43OS0yLjU2IDcuMzMtNiA4LjM1LTMuNDQtMS4wMi02LTQuNTYtNi04LjM1VjYuNDVsNi0yLjI1eiIvPjwvc3ZnPg==";

document.addEventListener('DOMContentLoaded', () => {
    let pollInterval = null;
    
    const getLogo = (logoUrl) => {
        if (!logoUrl || logoUrl.includes('id=-1') || logoUrl.includes('id=null')) {
            return DEFAULT_LOGO;
        }
        // Fallback for relative paths in production
        let absoluteUrl = logoUrl;
        if (!logoUrl.startsWith('http') && !logoUrl.startsWith('data:')) {
            const cleanPath = logoUrl.startsWith('/') ? logoUrl.substring(1) : logoUrl;
            absoluteUrl = 'http://52.203.150.214:8080/CONFUSALive/' + cleanPath;
        }
        
        // Proxy HTTP content to bypass HTTPS Mixed Content blocking
        if (absoluteUrl.startsWith('http://52.203.150.214')) {
            return '/api/poltronascore/proxy.php?url=' + encodeURIComponent(absoluteUrl);
        }
        return absoluteUrl;
    };
    
    const matchesList = document.getElementById('matches-list');
    const tabButtons = document.querySelectorAll('.tab-btn');
    const updateTimeText = document.getElementById('update-time');
    const statusDot = document.getElementById('status-dot');
    
    // Modal elements
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
    
    // Init loading
    loadMatches();
    
    // Setup polling (every 30s to keep it fresh)
    pollInterval = setInterval(loadMatches, 30000);
    
    // Tab scroll triggers
    tabButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const targetSec = btn.getAttribute('data-tab');
            const targetEl = document.getElementById(`sec-${targetSec}`);
            if (targetEl) {
                const headerOffset = 135; // Header + Nav height
                const elementPosition = targetEl.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
                
                // Set active class visually
                tabButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }
        });
    });
    
    // Modal Close
    modalClose.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) closeModal();
    });
    
    function loadMatches() {
        statusDot.className = 'status-dot loading';
        
        fetch('/api/poltronascore/jogos.php')
            .then(res => res.json())
            .then(res => {
                statusDot.className = res.last_update_success ? 'status-dot' : 'status-dot error';
                
                // Update time text
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
            .catch(err => {
                statusDot.className = 'status-dot error';
                updateTimeText.textContent = 'Erro de rede';
                console.error(err);
            });
    }
    
    function renderAllMatches(data) {
        let html = '';
        
        // 1. Live Matches Section
        const liveGroups = data.live || {};
        const liveCount = Object.values(liveGroups).reduce((acc, curr) => acc + curr.length, 0);
        
        html += `<div id="sec-live" class="section-container">`;
        if (liveCount > 0) {
            html += `<h2 class="section-title"><span class="badge-live" style="animation: pulse 1.2s infinite; font-size: 11px;">AO VIVO</span> Jogos em Andamento</h2>`;
            html += renderGroups(liveGroups, 'live');
            document.querySelector('.tab-btn[data-tab="live"]').style.display = 'flex';
        } else {
            // Hide live tab shortcut if no live matches
            document.querySelector('.tab-btn[data-tab="live"]').style.display = 'none';
        }
        html += `</div>`;
        
        // 2. Next Matches Section
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
        
        // 3. Previous Matches Section
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
        
        // Add click events to cards
        document.querySelectorAll('.match-card').forEach(card => {
            card.addEventListener('click', () => {
                const matchId = card.getAttribute('data-id');
                openMatchDetails(matchId);
            });
        });
    }
    
    function renderGroups(groupedMatches, type) {
        let html = '';
        for (const [championship, matches] of Object.entries(groupedMatches)) {
            html += `
                <div class="championship-group">
                    <div class="championship-header">${championship}</div>
            `;
            
            matches.forEach(match => {
                const homeScore = match.home_score !== null ? match.home_score : 0;
                const awayScore = match.away_score !== null ? match.away_score : 0;
                
                const scoreHtml = type === 'next' 
                    ? `<span class="score-display"><span class="score-divider">vs</span></span>`
                    : `<span class="score-display"><span>${homeScore}</span><span class="score-divider">-</span><span>${awayScore}</span></span>`;
                
                const timeStatusHtml = type === 'live'
                    ? `<span class="badge-live">AO VIVO</span>`
                    : `<span>${match.match_date} às ${match.match_time}</span>`;
                
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
                                <img class="team-logo" src="${getLogo(match.home_logo)}" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'" alt="${match.home_team}">
                            </div>
                            ${scoreHtml}
                            <div class="team-info away">
                                <img class="team-logo" src="${getLogo(match.away_logo)}" onerror="this.onerror=null; this.src='${DEFAULT_LOGO}'" alt="${match.away_team}">
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
    
    function openMatchDetails(matchId) {
        mEventsList.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
        modalOverlay.classList.add('active');
        
        fetch(`/api/poltronascore/jogo.php?id=${matchId}`)
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    mEventsList.innerHTML = `<div class="no-data">Erro: ${res.message}</div>`;
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
                
                // Events/Timeline
                renderEvents(res.events, match.home_team, match.away_team);
            })
            .catch(err => {
                mEventsList.innerHTML = '<div class="no-data">Falha ao obter detalhes.</div>';
                console.error(err);
            });
    }
    
    function renderEvents(events, homeTeam, awayTeam) {
        if (!events || events.length === 0) {
            mEventsList.innerHTML = '<div class="no-data">Nenhum evento registrado nesta partida.</div>';
            return;
        }
        
        let html = '<div class="event-list">';
        
        // Reverse array to show most recent events at the top
        events.slice().reverse().forEach(ev => {
            let iconClass = 'event-icon';
            if (ev.type) {
                iconClass += ' ' + ev.type;
            }
            
            let badgeHtml = '';
            if (ev.team_name) {
                badgeHtml = `<span class="event-team">${ev.team_name}</span>`;
            }
            
            let playerHtml = '';
            if (ev.player_name) {
                playerHtml = `<span class="event-player">${ev.player_name}</span>`;
            }
            
            html += `
                <div class="event-item">
                    <div class="${iconClass}"></div>
                    <div class="event-time-team">
                        <span class="event-minute">${ev.minute ? ev.minute + ' ' : ''}${ev.period || ''}</span>
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
});

// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/poltronascore/sw.js')
            .then(reg => {
                console.log('PoltronaScore Service Worker registered: ', reg.scope);
            })
            .catch(err => {
                console.error('Service Worker registration failed: ', err);
            });
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
    
    // Check if dismissed before
    const isDismissed = localStorage.getItem('pwa-dismissed');
    const dismissedTime = isDismissed ? parseInt(isDismissed, 10) : 0;
    const now = Date.now();
    
    // Show banner after 3 seconds if not dismissed in the last 3 days
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
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('PWA installation accepted.');
                }
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
