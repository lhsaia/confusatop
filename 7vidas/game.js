/* 7vidas - Game logic */

const FORMATIONS = {
    "3-4-2-1": [
        { id: 1, pos: "G", label: "Goleiro", x: 50, y: 90, idx: 0 },
        { id: 2, pos: "Z", label: "Zagueiro", x: 25, y: 75, idx: 3 },
        { id: 3, pos: "Z", label: "Zagueiro", x: 50, y: 78, idx: 3 },
        { id: 4, pos: "Z", label: "Zagueiro", x: 75, y: 75, idx: 3 },
        { id: 5, pos: "MD", label: "Meia Direito", x: 85, y: 55, idx: 7 },
        { id: 6, pos: "ME", label: "Meia Esquerdo", x: 15, y: 55, idx: 8 },
        { id: 7, pos: "MC", label: "Meia Central", x: 35, y: 56, idx: 9 },
        { id: 8, pos: "MC", label: "Meia Central", x: 65, y: 56, idx: 9 },
        { id: 9, pos: "Am", label: "Atacante Mov.", x: 33, y: 34, idx: 13 },
        { id: 10, pos: "Am", label: "Atacante Mov.", x: 67, y: 34, idx: 13 },
        { id: 11, pos: "Aa", label: "Centroavante", x: 50, y: 16, idx: 14 }
    ],
    "3-5-2": [
        { id: 1, pos: "G", label: "Goleiro", x: 50, y: 90, idx: 0 },
        { id: 2, pos: "Z", label: "Zagueiro", x: 25, y: 75, idx: 3 },
        { id: 3, pos: "Z", label: "Zagueiro", x: 50, y: 78, idx: 3 },
        { id: 4, pos: "Z", label: "Zagueiro", x: 75, y: 75, idx: 3 },
        { id: 5, pos: "V", label: "Volante", x: 50, y: 56, idx: 6 },
        { id: 6, pos: "MD", label: "Meia Direito", x: 85, y: 48, idx: 7 },
        { id: 7, pos: "ME", label: "Meia Esquerdo", x: 15, y: 48, idx: 8 },
        { id: 8, pos: "MC", label: "Meia Central", x: 50, y: 42, idx: 9 },
        { id: 9, pos: "MA", label: "Meia Atacante", x: 50, y: 30, idx: 12 },
        { id: 10, pos: "Am", label: "Atacante Mov.", x: 33, y: 18, idx: 13 },
        { id: 11, pos: "Aa", label: "Centroavante", x: 67, y: 18, idx: 14 }
    ],
    "3-6-1": [
        { id: 1, pos: "G", label: "Goleiro", x: 50, y: 90, idx: 0 },
        { id: 2, pos: "Z", label: "Zagueiro", x: 25, y: 75, idx: 3 },
        { id: 3, pos: "Z", label: "Zagueiro", x: 50, y: 78, idx: 3 },
        { id: 4, pos: "Z", label: "Zagueiro", x: 75, y: 75, idx: 3 },
        { id: 5, pos: "V", label: "Volante", x: 35, y: 58, idx: 6 },
        { id: 6, pos: "V", label: "Volante", x: 65, y: 58, idx: 6 },
        { id: 7, pos: "MD", label: "Meia Direito", x: 85, y: 48, idx: 7 },
        { id: 8, pos: "ME", label: "Meia Esquerdo", x: 15, y: 48, idx: 8 },
        { id: 9, pos: "MA", label: "Meia Atacante", x: 35, y: 35, idx: 12 },
        { id: 10, pos: "MA", label: "Meia Atacante", x: 65, y: 35, idx: 12 },
        { id: 11, pos: "Aa", label: "Centroavante", x: 50, y: 16, idx: 14 }
    ],
    "4-2-4": [
        { id: 1, pos: "G", label: "Goleiro", x: 50, y: 90, idx: 0 },
        { id: 2, pos: "LD", label: "Lat. Direito", x: 85, y: 72, idx: 1 },
        { id: 3, pos: "Z", label: "Zagueiro", x: 38, y: 75, idx: 3 },
        { id: 4, pos: "Z", label: "Zagueiro", x: 62, y: 75, idx: 3 },
        { id: 5, pos: "LE", label: "Lat. Esquerdo", x: 15, y: 72, idx: 2 },
        { id: 6, pos: "V", label: "Volante", x: 50, y: 56, idx: 6 },
        { id: 7, pos: "MA", label: "Meia Atacante", x: 50, y: 38, idx: 12 },
        { id: 8, pos: "PD", label: "Ponta Direito", x: 85, y: 24, idx: 10 },
        { id: 9, pos: "PE", label: "Ponta Esquerda", x: 15, y: 24, idx: 11 },
        { id: 10, pos: "Am", label: "Atacante Mov.", x: 35, y: 20, idx: 13 },
        { id: 11, pos: "Aa", label: "Centroavante", x: 65, y: 20, idx: 14 }
    ],
    "4-3-3 clássico": [
        { id: 1, pos: "G", label: "Goleiro", x: 50, y: 90, idx: 0 },
        { id: 2, pos: "LD", label: "Lat. Direito", x: 85, y: 72, idx: 1 },
        { id: 3, pos: "Z", label: "Zagueiro", x: 38, y: 75, idx: 3 },
        { id: 4, pos: "Z", label: "Zagueiro", x: 62, y: 75, idx: 3 },
        { id: 5, pos: "LE", label: "Lat. Esquerdo", x: 15, y: 72, idx: 2 },
        { id: 6, pos: "MD", label: "Meia Direito", x: 80, y: 50, idx: 7 },
        { id: 7, pos: "ME", label: "Meia Esquerdo", x: 20, y: 50, idx: 8 },
        { id: 8, pos: "MC", label: "Meia Central", x: 50, y: 44, idx: 9 },
        { id: 9, pos: "PD", label: "Ponta Direito", x: 80, y: 24, idx: 10 },
        { id: 10, pos: "PE", label: "Ponta Esquerda", x: 20, y: 24, idx: 11 },
        { id: 11, pos: "Aa", label: "Centroavante", x: 50, y: 16, idx: 14 }
    ],
    "4-3-3 equilibrado": [
        { id: 1, pos: "G", label: "Goleiro", x: 50, y: 90, idx: 0 },
        { id: 2, pos: "LD", label: "Lat. Direito", x: 85, y: 72, idx: 1 },
        { id: 3, pos: "Z", label: "Zagueiro", x: 38, y: 75, idx: 3 },
        { id: 4, pos: "Z", label: "Zagueiro", x: 62, y: 75, idx: 3 },
        { id: 5, pos: "LE", label: "Lat. Esquerdo", x: 15, y: 72, idx: 2 },
        { id: 6, pos: "V", label: "Volante", x: 50, y: 56, idx: 6 },
        { id: 7, pos: "MC", label: "Meia Central", x: 30, y: 44, idx: 9 },
        { id: 8, pos: "MA", label: "Meia Atacante", x: 70, y: 40, idx: 12 },
        { id: 9, pos: "PD", label: "Ponta Direito", x: 80, y: 24, idx: 10 },
        { id: 10, pos: "PE", label: "Ponta Esquerda", x: 20, y: 24, idx: 11 },
        { id: 11, pos: "Aa", label: "Centroavante", x: 50, y: 16, idx: 14 }
    ],
    "4-3-3 triangular": [
        { id: 1, pos: "G", label: "Goleiro", x: 50, y: 90, idx: 0 },
        { id: 2, pos: "LD", label: "Lat. Direito", x: 85, y: 72, idx: 1 },
        { id: 3, pos: "Z", label: "Zagueiro", x: 38, y: 75, idx: 3 },
        { id: 4, pos: "Z", label: "Zagueiro", x: 62, y: 75, idx: 3 },
        { id: 5, pos: "LE", label: "Lat. Esquerdo", x: 15, y: 72, idx: 2 },
        { id: 6, pos: "V", label: "Volante", x: 50, y: 56, idx: 6 },
        { id: 7, pos: "MA", label: "Meia Atacante", x: 35, y: 38, idx: 12 },
        { id: 8, pos: "MA", label: "Meia Atacante", x: 65, y: 38, idx: 12 },
        { id: 9, pos: "PD", label: "Ponta Direito", x: 80, y: 24, idx: 10 },
        { id: 10, pos: "PE", label: "Ponta Esquerda", x: 20, y: 24, idx: 11 },
        { id: 11, pos: "Aa", label: "Centroavante", x: 50, y: 16, idx: 14 }
    ],
    "4-4-2 clássico": [
        { id: 1, pos: "G", label: "Goleiro", x: 50, y: 90, idx: 0 },
        { id: 2, pos: "LD", label: "Lat. Direito", x: 85, y: 72, idx: 1 },
        { id: 3, pos: "Z", label: "Zagueiro", x: 38, y: 75, idx: 3 },
        { id: 4, pos: "Z", label: "Zagueiro", x: 62, y: 75, idx: 3 },
        { id: 5, pos: "LE", label: "Lat. Esquerdo", x: 15, y: 72, idx: 2 },
        { id: 6, pos: "MD", label: "Meia Direito", x: 80, y: 44, idx: 7 },
        { id: 7, pos: "ME", label: "Meia Esquerdo", x: 20, y: 44, idx: 8 },
        { id: 8, pos: "MC", label: "Meia Central", x: 35, y: 50, idx: 9 },
        { id: 9, pos: "MC", label: "Meia Central", x: 65, y: 50, idx: 9 },
        { id: 10, pos: "Aa", label: "Centroavante", x: 38, y: 18, idx: 14 },
        { id: 11, pos: "Aa", label: "Centroavante", x: 62, y: 18, idx: 14 }
    ],
    "4-4-2 equilibrado": [
        { id: 1, pos: "G", label: "Goleiro", x: 50, y: 90, idx: 0 },
        { id: 2, pos: "LD", label: "Lat. Direito", x: 85, y: 72, idx: 1 },
        { id: 3, pos: "Z", label: "Zagueiro", x: 38, y: 75, idx: 3 },
        { id: 4, pos: "Z", label: "Zagueiro", x: 62, y: 75, idx: 3 },
        { id: 5, pos: "LE", label: "Lat. Esquerdo", x: 15, y: 72, idx: 2 },
        { id: 6, pos: "V", label: "Volante", x: 50, y: 56, idx: 6 },
        { id: 7, pos: "MC", label: "Meia Central", x: 30, y: 44, idx: 9 },
        { id: 8, pos: "MC", label: "Meia Central", x: 70, y: 44, idx: 9 },
        { id: 9, pos: "MA", label: "Meia Atacante", x: 50, y: 34, idx: 12 },
        { id: 10, pos: "Am", label: "Atacante Mov.", x: 38, y: 20, idx: 13 },
        { id: 11, pos: "Aa", label: "Centroavante", x: 62, y: 18, idx: 14 }
    ],
    "4-5-1": [
        { id: 1, pos: "G", label: "Goleiro", x: 50, y: 90, idx: 0 },
        { id: 2, pos: "LD", label: "Lat. Direito", x: 85, y: 72, idx: 1 },
        { id: 3, pos: "Z", label: "Zagueiro", x: 38, y: 75, idx: 3 },
        { id: 4, pos: "Z", label: "Zagueiro", x: 62, y: 75, idx: 3 },
        { id: 5, pos: "LE", label: "Lat. Esquerdo", x: 15, y: 72, idx: 2 },
        { id: 6, pos: "V", label: "Volante", x: 50, y: 58, idx: 6 },
        { id: 7, pos: "MD", label: "Meia Direito", x: 80, y: 42, idx: 7 },
        { id: 8, pos: "ME", label: "Meia Esquerdo", x: 20, y: 42, idx: 8 },
        { id: 9, pos: "MC", label: "Meia Central", x: 35, y: 48, idx: 9 },
        { id: 10, pos: "MC", label: "Meia Central", x: 65, y: 48, idx: 9 },
        { id: 11, pos: "Aa", label: "Centroavante", x: 50, y: 16, idx: 14 }
    ],
    "5-2-2-1": [
        { id: 1, pos: "G", label: "Goleiro", x: 50, y: 90, idx: 0 },
        { id: 2, pos: "Z", label: "Zagueiro", x: 25, y: 76, idx: 3 },
        { id: 3, pos: "Z", label: "Zagueiro", x: 50, y: 78, idx: 3 },
        { id: 4, pos: "Z", label: "Zagueiro", x: 75, y: 76, idx: 3 },
        { id: 5, pos: "AD", label: "Ala Direito", x: 85, y: 68, idx: 4 },
        { id: 6, pos: "AE", label: "Ala Esquerdo", x: 15, y: 68, idx: 5 },
        { id: 7, pos: "MC", label: "Meia Central", x: 35, y: 52, idx: 9 },
        { id: 8, pos: "MC", label: "Meia Central", x: 65, y: 52, idx: 9 },
        { id: 9, pos: "Am", label: "Atacante Mov.", x: 35, y: 35, idx: 13 },
        { id: 10, pos: "Am", label: "Atacante Mov.", x: 65, y: 35, idx: 13 },
        { id: 11, pos: "Aa", label: "Centroavante", x: 50, y: 16, idx: 14 }
    ],
    "5-3-2": [
        { id: 1, pos: "G", label: "Goleiro", x: 50, y: 90, idx: 0 },
        { id: 2, pos: "Z", label: "Zagueiro", x: 25, y: 76, idx: 3 },
        { id: 3, pos: "Z", label: "Zagueiro", x: 50, y: 78, idx: 3 },
        { id: 4, pos: "Z", label: "Zagueiro", x: 75, y: 76, idx: 3 },
        { id: 5, pos: "AD", label: "Ala Direito", x: 85, y: 68, idx: 4 },
        { id: 6, pos: "AE", label: "Ala Esquerdo", x: 15, y: 68, idx: 5 },
        { id: 7, pos: "V", label: "Volante", x: 50, y: 54, idx: 6 },
        { id: 8, pos: "MC", label: "Meia Central", x: 30, y: 42, idx: 9 },
        { id: 9, pos: "MA", label: "Meia Atacante", x: 70, y: 42, idx: 12 },
        { id: 10, pos: "Am", label: "Atacante Mov.", x: 38, y: 20, idx: 13 },
        { id: 11, pos: "Aa", label: "Centroavante", x: 62, y: 20, idx: 14 }
    ],
    "WM": [
        { id: 1, pos: "G", label: "Goleiro", x: 50, y: 90, idx: 0 },
        { id: 2, pos: "Z", label: "Zagueiro", x: 25, y: 76, idx: 3 },
        { id: 3, pos: "Z", label: "Zagueiro", x: 50, y: 78, idx: 3 },
        { id: 4, pos: "Z", label: "Zagueiro", x: 75, y: 76, idx: 3 },
        { id: 5, pos: "MD", label: "Meia Direito", x: 80, y: 52, idx: 7 },
        { id: 6, pos: "ME", label: "Meia Esquerdo", x: 20, y: 52, idx: 8 },
        { id: 7, pos: "MC", label: "Meia Central", x: 35, y: 58, idx: 9 },
        { id: 8, pos: "MC", label: "Meia Central", x: 65, y: 58, idx: 9 },
        { id: 9, pos: "PD", label: "Ponta Direito", x: 85, y: 24, idx: 10 },
        { id: 10, pos: "PE", label: "Ponta Esquerda", x: 20, y: 24, idx: 11 },
        { id: 11, pos: "Aa", label: "Centroavante", x: 50, y: 16, idx: 14 }
    ]
};

// Global Game State
let gameState = {
    stage: 'setup', 
    formation: '4-3-3 clássico',
    mode: 'classico', 
    draftedPlayers: {}, 
    currentRoll: null, 
    selectedPlayerForDraft: null, 
    opponents: [],
    myTeamName: "Meu Combinado",
    myTeamEscudo: "0.png",
    skipsRemaining: 3,
    skipsLeagueRemaining: 3,
    
    // Survival specific properties
    lives: 7,
    wins: 0,
    matchIndex: 0,
    history: [] 
};

// Load saved state on start
function initGame() {
    const saved = localStorage.getItem('7vidas_state');
    if (saved) {
        try {
            gameState = JSON.parse(saved);
            // Force neutral shield migration for legacy cached sessions
            if (gameState.myTeamEscudo !== '0.png') {
                gameState.myTeamEscudo = '0.png';
            }
            if (gameState.skipsLeagueRemaining === undefined) {
                gameState.skipsLeagueRemaining = 3;
            }
            // Force valid formation key fallback
            if (!FORMATIONS[gameState.formation]) {
                gameState.formation = '4-3-3 clássico';
            }
            renderFromState();
            loadGlobalRankings();
            return;
        } catch(e) {
            // console.error("Failed to load saved state", e);
        }
    }
    showSetup();
    loadGlobalRankings();
}

function saveState() {
    localStorage.setItem('7vidas_state', JSON.stringify(gameState));
}

function resetGame(force = false) {
    if (!force && gameState.stage !== 'setup' && gameState.stage !== 'gameover') {
        const confirmReset = confirm("Deseja realmente reiniciar o jogo? Todo o progresso desta campanha será perdido!");
        if (!confirmReset) return;
    }
    localStorage.removeItem('7vidas_state');
    gameState = {
        stage: 'setup',
        formation: '4-3-3 clássico',
        mode: 'classico',
        draftedPlayers: {},
        currentRoll: null,
        selectedPlayerForDraft: null,
        opponents: [],
        myTeamName: "Meu Combinado",
        myTeamEscudo: "0.png",
        skipsRemaining: 3,
        skipsLeagueRemaining: 3,
        
        lives: 7,
        wins: 0,
        matchIndex: 0,
        history: []
    };
    showSetup();
    loadGlobalRankings();
}

function showSetup() {
    gameState.stage = 'setup';
    $('.game-logo-header').show();
    $('.alert-not-logged').show();
    $('#setup-panel').show();
    $('#ranking-box-setup').show();
    $('#pitch-container').hide();
    $('#drafting-controls').hide();
    $('#tournament-panel').hide();
    $('#reset-btn').hide();
    $('#mid-game-tactics').hide();
    $('#gameover-screen').hide();
    $('.game-grid').addClass('setup-mode').show();
    saveState();
}

function startDrafting() {
    gameState.stage = 'drafting';
    gameState.formation = $('#formation-select').val();
    gameState.mode = $('#mode-select').val();
    gameState.myTeamName = $('#team-name-input').val().trim() || "Meu Combinado";
    gameState.draftedPlayers = {};
    gameState.currentRoll = null;
    gameState.selectedPlayerForDraft = null;
    gameState.skipsRemaining = 3;
    gameState.skipsLeagueRemaining = 3;
    
    gameState.lives = 7;
    gameState.wins = 0;
    gameState.matchIndex = 0;
    gameState.history = [];

    $('#setup-panel').hide();
    $('#ranking-box-setup').hide();
    $('.alert-not-logged').hide();
    $('.game-logo-header').hide();
    $('#pitch-container').show();
    $('#drafting-controls').show();
    $('#reset-btn').show();
    
    $('#formation-select-mid').val(gameState.formation);
    $('#mid-game-tactics').css('display', 'flex');
    $('.game-grid').removeClass('setup-mode').show();
    $('.rolled-team-card').hide();

    updateSkipButtonState();
    drawPitch();
    saveState();
}

function drawPitch() {
    const pitch = $('#pitch-container');
    pitch.find('.pitch-slot').remove();

    const slots = FORMATIONS[gameState.formation] || FORMATIONS['4-3-3 clássico'];
    slots.forEach(slot => {
        const drafted = gameState.draftedPlayers[slot.id];
        let content = '';

        if (drafted) {
            const hasPosition = drafted.stringPosicoes[slot.idx] === '1';
            const displayRating = gameState.mode === 'classico' ? drafted.nivel : '?';
            const penaltyClass = !hasPosition ? 'penalty' : '';
            const positionWarn = !hasPosition ? ' title="Fora de posição (-25% de penalidade)"' : '';

            content = `
                <div class="slot-circle filled" ${positionWarn}>
                    ${slot.pos}
                    <span class="slot-rating ${penaltyClass}">${displayRating}</span>
                </div>
                <div class="slot-label">${slot.label}</div>
                <div class="slot-player-name">${drafted.nome}</div>
            `;
        } else {
            content = `
                <div class="slot-circle">${slot.pos}</div>
                <div class="slot-label">${slot.label}</div>
            `;
        }

        const slotEl = $(`
            <div class="pitch-slot" id="slot-${slot.id}" style="left: ${slot.x}%; top: ${slot.y}%;" onclick="placePlayer(${slot.id})">
                ${content}
            </div>
        `);

        pitch.append(slotEl);
    });
}

function rollClub(sameLeagueId = 0) {
    $('#btn-roll-club').prop('disabled', true).text('Girando...');
    $('#btn-skip-club').prop('disabled', true);
    $('#btn-skip-league').prop('disabled', true);
    $('.rolled-team-card').slideUp(200);

    let url = '/api/7vidas.php?action=roll_club';
    if (sameLeagueId > 0) {
        url += '&same_league_id=' + sameLeagueId;
    }
    
    // Avoid drawing the same club consecutively by passing current ID as exclusion
    if (gameState.currentRoll && gameState.currentRoll.id) {
        url += '&exclude_club_id=' + gameState.currentRoll.id;
    }

    $.getJSON(url, function(data) {
        $('#btn-roll-club').text('Girar Roleta');
        if (data.error) {
            alert(data.error);
            $('#btn-roll-club').prop('disabled', false);
            return;
        }

        gameState.currentRoll = data;
        gameState.selectedPlayerForDraft = null;
        updateSkipButtonState();

        const shieldUrl = data.escudo ? '/images/escudos/' + data.escudo : '/images/escudos/0.png';
        const flagUrl = data.pais_bandeira ? '/images/bandeiras/' + data.pais_bandeira : '';

        $('#rolled-badge').attr('src', shieldUrl);
        $('#rolled-name').text(data.nome);
        
        let flagHtml = flagUrl ? `<img src="${flagUrl}" class="bandeira" alt="Bandeira">` : '';
        $('#rolled-country').html(`${flagHtml} ${data.pais_nome}`);

        const grid = $('#rolled-players');
        grid.empty();

        data.players.forEach(p => {
            const displayRating = gameState.mode === 'classico' ? p.nivel : '?';
            const positions = getReadablePositions(p.stringPosicoes);

            const row = $(`
                <div class="player-row" id="player-${p.id}" onclick="selectPlayerForDraft(${p.id})">
                    <div class="player-info-left">
                        <span class="player-name">${p.nome}</span>
                        <span class="player-positions">${positions}</span>
                    </div>
                    <span class="player-rating-badge">${displayRating}</span>
                </div>
            `);
            grid.append(row);
        });

        $('.rolled-team-card').slideDown(300);
        highlightNaturalSlots(null);
        saveState();
    });
}

function skipClub() {
    if (gameState.skipsRemaining <= 0) return;
    
    gameState.skipsRemaining--;
    updateSkipButtonState();
    rollClub(0);
}

function skipLeague() {
    if (gameState.skipsLeagueRemaining <= 0 || !gameState.currentRoll || !gameState.currentRoll.liga_id) return;
    
    const leagueId = gameState.currentRoll.liga_id;
    gameState.skipsLeagueRemaining--;
    updateSkipButtonState();
    rollClub(leagueId);
}

function updateSkipButtonState() {
    const skipBtn = $('#btn-skip-club');
    const skipLeagueBtn = $('#btn-skip-league');
    const rollBtn = $('#btn-roll-club');
    
    skipBtn.text(`Pular Geral (${gameState.skipsRemaining})`);
    skipLeagueBtn.text(`Mesma Liga (${gameState.skipsLeagueRemaining})`);
    
    // Disable "Girar Roleta" if a club has been rolled but not yet drafted or skipped
    if (gameState.currentRoll) {
        rollBtn.prop('disabled', true);
    } else {
        rollBtn.prop('disabled', false);
    }

    if (gameState.skipsRemaining <= 0 || !gameState.currentRoll) {
        skipBtn.prop('disabled', true);
    } else {
        skipBtn.prop('disabled', false);
    }

    if (gameState.skipsLeagueRemaining <= 0 || !gameState.currentRoll || !gameState.currentRoll.liga_id) {
        skipLeagueBtn.prop('disabled', true);
    } else {
        skipLeagueBtn.prop('disabled', false);
    }
}

function getReadablePositions(binStr) {
    const list = ['G', 'LD', 'LE', 'Z', 'AD', 'AE', 'V', 'MD', 'ME', 'MC', 'PD', 'PE', 'MA', 'Am', 'Aa'];
    let readable = [];
    for(let i = 0; i < binStr.length; i++) {
        if (binStr[i] === '1') {
            readable.push(list[i]);
        }
    }
    return readable.length > 0 ? readable.join(', ') : 'Nenhuma';
}

function selectPlayerForDraft(playerId) {
    if (!gameState.currentRoll) return;
    const player = gameState.currentRoll.players.find(p => p.id == playerId);
    if (!player) return;

    gameState.selectedPlayerForDraft = player;
    $('.player-row').removeClass('selected');
    $(`#player-${playerId}`).addClass('selected');

    highlightNaturalSlots(player.stringPosicoes);
}

function highlightNaturalSlots(binStr) {
    $('.slot-circle').removeClass('highlight');
    if (!binStr) return;

    const slots = FORMATIONS[gameState.formation];
    slots.forEach(slot => {
        const isOccupied = !!gameState.draftedPlayers[slot.id];
        if (binStr[slot.idx] === '1' && !isOccupied) {
            $(`#slot-${slot.id} .slot-circle`).addClass('highlight');
        }
    });
}

function placePlayer(slotId) {
    if (!gameState.selectedPlayerForDraft) {
        alert("Gire a roleta e selecione um jogador primeiro!");
        return;
    }

    if (gameState.draftedPlayers[slotId]) {
        alert("Esta posição já está ocupada! Escolha um slot vazio.");
        return;
    }

    gameState.draftedPlayers[slotId] = {
        id: gameState.selectedPlayerForDraft.id,
        nome: gameState.selectedPlayerForDraft.nome,
        nivel: parseInt(gameState.selectedPlayerForDraft.nivel),
        stringPosicoes: gameState.selectedPlayerForDraft.stringPosicoes,
        clubName: gameState.currentRoll.nome,
        clubEscudo: gameState.currentRoll.escudo
    };


    gameState.selectedPlayerForDraft = null;
    gameState.currentRoll = null;
    $('.rolled-team-card').slideUp(200);
    highlightNaturalSlots(null);
    updateSkipButtonState();

    drawPitch();

    if (Object.keys(gameState.draftedPlayers).length === 11) {
        setupSurvivalRun();
    } else {
        saveState();
    }
}

function setupSurvivalRun() {
    $('#btn-roll-club').prop('disabled', true).text('Agendando partidas...');

    $.getJSON('/api/7vidas.php?action=get_opponents', function(opponents) {
        if (opponents.error || opponents.length < 15) {
            alert("Erro ao carregar oponentes: " + (opponents.error || "Poucos times ativos."));
            $('#btn-roll-club').prop('disabled', false).text('Girar Roleta');
            return;
        }

        gameState.opponents = opponents;
        gameState.stage = 'tournament';
        gameState.lives = 7;
        gameState.wins = 0;
        gameState.matchIndex = 0;
        gameState.history = [];

        renderTournamentPanel();
        saveState();
    });
}

function getPlayerTeamRating() {
    const slots = FORMATIONS[gameState.formation];
    let total = 0;
    let correctPositions = 0;
    slots.forEach(slot => {
        const p = gameState.draftedPlayers[slot.id];
        if (p) {
            let rating = p.nivel;
            const hasPosition = p.stringPosicoes[slot.idx] === '1';
            if (!hasPosition) {
                rating = Math.round(rating * 0.75); // 25% penalty
            } else {
                correctPositions++;
            }
            total += rating;
        } else {
            total += 40;
        }
    });
    
    let finalRating = Math.round(total / 11);
    
    // Positional Harmony Bonus: +5 overall rating if all 11 play in natural positions
    if (correctPositions === 11) {
        finalRating += 5;
    } else if (correctPositions <= 5) {
        // Penalty if less than half play in natural positions
        finalRating -= 3;
    }
    
    return finalRating;
}

function renderTournamentPanel() {
    $('#drafting-controls').hide();
    $('#tournament-panel').show();

    // Show current survival statistics
    $('#tournament-status-title').text(`Partida ${gameState.wins + 1}`);

    // Render Hearts
    let hearts = '';
    for (let i = 0; i < gameState.lives; i++) {
        hearts += '💖';
    }
    $('#lives-display').html(hearts || '💔');
    $('#wins-counter').text(gameState.wins);

    // Calculate & render Tactic Style & Chemistry
    const formationStyles = {
        "4-2-4": "Ofensivo",
        "4-3-3 triangular": "Ofensivo",
        "3-4-2-1": "Ofensivo",
        "5-3-2": "Defensivo",
        "5-2-2-1": "Defensivo",
        "3-6-1": "Defensivo"
    };
    const style = formationStyles[gameState.formation] || "Equilibrado";
    $('#tactic-style-display').text(style);

    const slots = FORMATIONS[gameState.formation];
    let correctPositions = 0;
    slots.forEach(slot => {
        const p = gameState.draftedPlayers[slot.id];
        if (p && p.stringPosicoes[slot.idx] === '1') {
            correctPositions++;
        }
    });
    const chemistry = Math.round((correctPositions / 11) * 100);
    $('#chemistry-display').text(`${chemistry}%`);

    // Render current opponent details
    const opponent = getCurrentOpponent();
    if (opponent) {
        const badgeUrl = opponent.escudo ? `/images/escudos/` + opponent.escudo : '/images/escudos/0.png';
        const flagUrl = opponent.pais_bandeira ? `/images/bandeiras/` + opponent.pais_bandeira : '';

        $('#next-opp-badge').attr('src', badgeUrl);
        $('#next-opp-name').text(opponent.nome);
        $('#next-opp-rating').text(gameState.mode === 'classico' ? opponent.top11_rating : '?');
        
        let flagHtml = flagUrl ? `<img src="${flagUrl}" class="bandeira" alt="Bandeira">` : '';
        $('#next-opp-country').html(`${flagHtml} ${opponent.pais_nome}`);
    }

    renderMatchHistory();
    switchTab('survival-match');
}

function getCurrentOpponent() {
    if (gameState.opponents.length === 0) return null;
    const idx = gameState.matchIndex % gameState.opponents.length;
    return gameState.opponents[idx];
}

function switchTab(tabId) {
    $('.tab-btn').removeClass('active');
    $(`.tab-btn[onclick="switchTab('${tabId}')"]`).addClass('active');
    $('.tab-content').removeClass('active');
    $(`#tab-${tabId}`).addClass('active');
    
    if (tabId === 'global-ranking') {
        renderGlobalRankingsTab();
    }
}

function renderMatchHistory() {
    const tbody = $('#history-body');
    tbody.empty();

    if (gameState.history.length === 0) {
        tbody.append(`<tr><td colspan="4" style="text-align: center; color: var(--text-muted);">Nenhuma partida jogada ainda.</td></tr>`);
        return;
    }

    gameState.history.forEach((h, idx) => {
        const resColor = h.result === 'V' ? 'var(--success)' : (h.result === 'E' ? 'var(--warning)' : 'var(--danger)');
        const impactText = h.result === 'V' ? '+0 💖' : (h.result === 'E' ? '-1 💖' : '-2 💖');
        
        tbody.append(`
            <tr>
                <td>${idx + 1}</td>
                <td>vs <strong>${h.opponent}</strong></td>
                <td>${h.score}</td>
                <td style="font-weight: bold; color: ${resColor};">${h.result} (${impactText})</td>
            </tr>
        `);
    });
}

function playRound() {
    const opponent = getCurrentOpponent();
    if (!opponent) return;

    // Simulate match interactively
    startInteractiveMatch(opponent);
}

function startInteractiveMatch(opp) {
    $('#sim-overlay').css('display', 'flex');
    $('#sim-ticker').empty();
    $('#btn-close-sim').hide();

    const homeName = gameState.myTeamName;
    const awayName = opp.nome;
    const homeBadge = gameState.myTeamEscudo ? `/images/escudos/` + gameState.myTeamEscudo : '/images/escudos/0.png';
    const awayBadge = opp.escudo ? `/images/escudos/` + opp.escudo : '/images/escudos/0.png';

    $('#sim-home-name').text(homeName);
    $('#sim-home-badge').attr('src', homeBadge);
    $('#sim-away-name').text(awayName);
    $('#sim-away-badge').attr('src', awayBadge);

    $('#sim-home-score').text(0);
    $('#sim-away-score').text(0);

    let scoreH = 0;
    let scoreA = 0;
    let minute = 0;

    // Kickoff message
    addTickerEvent(0, "Começa a partida! Bola rolando no gramado.");

    const rateH = getPlayerTeamRating();
    const rateA = opp.top11_rating || 55;

    // Apply tactical style modifier on match pace
    let paceModifier = 1.0;
    const formationStyles = {
        "4-2-4": "Ofensivo",
        "4-3-3 triangular": "Ofensivo",
        "3-4-2-1": "Ofensivo",
        "5-3-2": "Defensivo",
        "5-2-2-1": "Defensivo",
        "3-6-1": "Defensivo"
    };
    const style = formationStyles[gameState.formation] || "Equilibrado";
    if (style === "Ofensivo") paceModifier = 1.25;
    if (style === "Defensivo") paceModifier = 0.75;

    const goalProbH = (0.05 + (rateH - rateA) / 400) * paceModifier;
    const goalProbA = (0.05 + (rateA - rateH) / 400) * paceModifier;

    const matchInterval = setInterval(() => {
        minute += Math.floor(Math.random() * 8) + 3;
        if (minute > 90) minute = 90;

        const eventRoll = Math.random();
        if (eventRoll < goalProbH) {
            scoreH++;
            $('#sim-home-score').text(scoreH);
            addTickerEvent(minute, `⚽ GOL do ${homeName}! Belíssima finalização para abrir o placar!`, 'goal');
        } else if (eventRoll < goalProbH + goalProbA) {
            scoreA++;
            $('#sim-away-score').text(scoreA);
            addTickerEvent(minute, `⚽ GOL do ${awayName}! Finalização certeira no cantinho!`, 'goal');
        } else if (Math.random() < 0.25) {
            const isHomePlay = Math.random() < 0.5;
            const activeTeam = isHomePlay ? homeName : awayName;
            const passiveTeam = isHomePlay ? awayName : homeName;
            const templates = [
                `O ${activeTeam} troca passes rápidos no campo de ataque tentando furar o bloqueio do ${passiveTeam}.`,
                `Grande jogada de ataque do ${activeTeam}, mas a zaga do ${passiveTeam} faz o desarme perfeito.`,
                `Cruzamento perigoso do ${activeTeam} na área, o goleiro do ${passiveTeam} sai de soco e afasta!`,
                `Chute forte de longe do ${activeTeam}, mas o goleiro do ${passiveTeam} defende sem rebote.`,
                `🟨 Cartão amarelo! Falta dura cometida por um jogador do ${activeTeam} para parar o contra-ataque do ${passiveTeam}.`,
                `Contra-ataque veloz armado pelo ${activeTeam}! O ${passiveTeam} se recompõe a tempo de afastar o perigo.`,
                `Cobrança de falta perigosa para o ${activeTeam}, a bola passa raspando a trave do ${passiveTeam}!`,
                `Pressão intensa do ${activeTeam} encurralando o ${passiveTeam} em sua própria grande área.`
            ];
            const randomComm = templates[Math.floor(Math.random() * templates.length)];
            const isCard = randomComm.includes('🟨');
            addTickerEvent(minute, randomComm, isCard ? 'card' : '');
        }

        if (minute === 90) {
            clearInterval(matchInterval);
            addTickerEvent(90, `Fim de jogo! Apita o árbitro encerrando o tempo regulamentar.`);
            finishSurvivalMatch(opp, scoreH, scoreA);
        }
    }, 180);
}

function addTickerEvent(time, desc, type = '') {
    const ev = $(`
        <div class="ticker-event ${type}">
            <span class="ticker-time">${time}'</span>
            <span class="ticker-desc">${desc}</span>
        </div>
    `);
    const container = $('#sim-ticker');
    container.append(ev);
    $('.sim-modal-body').scrollTop(container[0].scrollHeight);
}

function finishSurvivalMatch(opp, scoreH, scoreA) {
    $('#btn-close-sim').show();

    const playerWon = (scoreH > scoreA);
    const playerDrew = (scoreH === scoreA);
    const resultChar = playerWon ? 'V' : (playerDrew ? 'E' : 'D');

    // Update life state
    if (playerDrew) {
        gameState.lives -= 1;
    } else if (!playerWon) { // Loss
        gameState.lives -= 2;
    }

    if (playerWon) {
        gameState.wins++;
    }

    if (gameState.lives < 0) {
        gameState.lives = 0;
    }

    gameState.history.push({
        opponent: opp.nome,
        opponentCountry: opp.pais_nome || '',
        score: `${scoreH} - ${scoreA}`,
        result: resultChar
    });

    gameState.matchIndex++;
}

function closeSim() {
    $('#sim-overlay').hide();

    if (gameState.lives <= 0) {
        gameState.stage = 'gameover';
        saveResultsToDB();
        showGameOver(false);
    } else {
        renderTournamentPanel();
        saveState();
    }
}

function saveResultsToDB() {
    const avgRating = getPlayerTeamRating();

    $.post('/api/7vidas.php?action=save_result', {
        modo: gameState.mode === 'classico' ? 'Clássico' : 'Almanaque',
        nivel_medio: avgRating,
        vitorias: gameState.wins,
        resultado_final: `${gameState.wins} Vitórias`
    }, function(res) {
        loadGlobalRankings();
    });
}

function loadGlobalRankings() {
    $.getJSON('/api/7vidas.php?action=get_rankings', function(data) {
        if (data && !data.error) {
            renderRankingsTable(data, '#rankings-body-setup');
            renderRankingsTable(data, '#rankings-body-tournament');
        }
    });
}

function renderRankingsTable(data, targetSelector) {
    const tbody = $(targetSelector);
    tbody.empty();

    if (data.length === 0) {
        tbody.append(`<tr><td colspan="5" style="text-align: center; color: var(--text-muted);">Nenhum resultado registrado ainda. Seja o primeiro!</td></tr>`);
        return;
    }

    data.forEach((r, idx) => {
        tbody.append(`
            <tr>
                <td>${idx + 1}</td>
                <td><strong>${r.usuario_nome}</strong></td>
                <td>${r.modo}</td>
                <td>${r.nivel_medio}</td>
                <td>${r.vitorias} Vitórias</td>
            </tr>
        `);
    });
}

function renderGlobalRankingsTab() {
    loadGlobalRankings();
}

function showGameOver(won = false) {
    $('.game-logo-header').hide();
    $('.alert-not-logged').hide();
    $('#tournament-panel').hide();
    $('#setup-panel').hide();
    $('#pitch-container').hide();
    $('#drafting-controls').hide();
    $('#mid-game-tactics').hide();
    $('#reset-btn').hide();
    $('.game-grid').hide();

    let screen = $('#gameover-screen');
    if (screen.length === 0) {
        screen = $(`
            <div id="gameover-screen" class="roulette-container" style="max-width: 600px; margin: 0 auto; text-align: center;">
                <h2 id="gameover-title" style="font-size: 2.2rem; margin-bottom: 15px;"></h2>
                <p id="gameover-desc" style="color: var(--text-muted); margin-bottom: 25px;"></p>
                <div id="history-box" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; margin-bottom: 20px; text-align: left;">
                    <h3 style="margin-top: 0; margin-bottom: 10px;">Histórico da Campanha:</h3>
                    <ul id="history-list" style="list-style: none; padding: 0; margin: 0; max-height: 250px; overflow-y: auto;"></ul>
                </div>
                <div class="gameover-buttons">
                    <button class="btn-roll" onclick="resetGame()">Jogar Novamente</button>
                    <button class="btn-roll" style="background: var(--primary); border-color: var(--primary); color: #fff;" onclick="exportCampaignImage()">Exportar Imagem</button>
                </div>
            </div>
        `);
        $('.game-container').append(screen);
    }

    screen.show();

    $('#gameover-title').text("💔 Fim de Jogo!").css('color', 'var(--danger)');
    $('#gameover-desc').text(`Suas vidas acabaram! Você conseguiu acumular um total de ${gameState.wins} vitórias com seu time.`);

    const hList = $('#history-list');
    hList.empty();
    
    gameState.history.forEach((h, idx) => {
        const resColor = h.result === 'V' ? 'var(--success)' : (h.result === 'E' ? 'var(--warning)' : 'var(--danger)');
        hList.append(`
            <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding: 6px 0;">
                <span>Partida ${idx + 1}: vs <strong>${h.opponent}</strong></span>
                <span style="font-weight: bold; color: ${resColor};">${h.score} (${h.result})</span>
            </li>
        `);
    });

    saveState();
}

function renderFromState() {
    $('#gameover-screen').hide();
    
    if (gameState.stage === 'setup') {
        $('.game-logo-header').show();
        $('.alert-not-logged').show();
        showSetup();
    } else if (gameState.stage === 'drafting') {
        $('.game-logo-header').hide();
        $('.alert-not-logged').hide();
        $('#setup-panel').hide();
        $('#ranking-box-setup').hide();
        $('#pitch-container').show();
        $('#drafting-controls').show();
        $('#reset-btn').show();
        
        $('#formation-select-mid').val(gameState.formation);
        $('#mid-game-tactics').css('display', 'flex');
        $('.game-grid').removeClass('setup-mode').show();
        
        drawPitch();
        updateSkipButtonState();

        if (gameState.currentRoll) {
            const data = gameState.currentRoll;
            const shieldUrl = data.escudo ? '/images/escudos/' + data.escudo : '/images/escudos/0.png';
            const flagUrl = data.pais_bandeira ? '/images/bandeiras/' + data.pais_bandeira : '';

            $('#rolled-badge').attr('src', shieldUrl);
            $('#rolled-name').text(data.nome);
            
            let flagHtml = flagUrl ? `<img src="${flagUrl}" class="bandeira" alt="Bandeira">` : '';
            $('#rolled-country').html(`${flagHtml} ${data.pais_nome}`);

            const grid = $('#rolled-players');
            grid.empty();

            data.players.forEach(p => {
                const displayRating = gameState.mode === 'classico' ? p.nivel : '?';
                const positions = getReadablePositions(p.stringPosicoes);

                const row = $(`
                    <div class="player-row" id="player-${p.id}" onclick="selectPlayerForDraft(${p.id})">
                        <div class="player-info-left">
                            <span class="player-name">${p.nome}</span>
                            <span class="player-positions">${positions}</span>
                        </div>
                        <span class="player-rating-badge">${displayRating}</span>
                    </div>
                `);
                grid.append(row);
            });

            $('.rolled-team-card').show();
        }
    } else if (gameState.stage === 'tournament') {
        $('.game-logo-header').hide();
        $('.alert-not-logged').hide();
        $('#setup-panel').hide();
        $('#ranking-box-setup').hide();
        $('#pitch-container').show();
        $('#reset-btn').show();
        
        $('#formation-select-mid').val(gameState.formation);
        $('#mid-game-tactics').css('display', 'flex');
        $('.game-grid').removeClass('setup-mode').show();
        
        drawPitch();
        renderTournamentPanel();
    } else if (gameState.stage === 'gameover') {
        $('.game-logo-header').hide();
        $('.alert-not-logged').hide();
        $('#mid-game-tactics').hide();
        $('.game-grid').hide();
        showGameOver(false);
    }
}

$(document).ready(function() {
    initGame();

    // Listen to tactical scheme changes mid-game
    $('#formation-select-mid').change(function() {
        const val = $(this).val();
        gameState.formation = val;
        $('#formation-select').val(val);
        
        drawPitch();
        if (gameState.stage === 'tournament') {
            renderTournamentPanel();
        }
        saveState();
    });
});

function openHelpModal() {
    $('#help-modal').css('display', 'flex');
}

function closeHelpModal() {
    $('#help-modal').hide();
    localStorage.setItem('7vidas_help_seen', 'true');
}

function exportCampaignImage() {
    const historyCount = gameState.history.length;
    const canvasHeight = 740 + (historyCount * 32) + 70;
    
    const canvas = document.createElement('canvas');
    canvas.width = 800;
    canvas.height = Math.max(1000, canvasHeight);
    const ctx = canvas.getContext('2d');

    // Draw background (Sleek dark gradient matching page aesthetics)
    const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
    gradient.addColorStop(0, '#0f172a');
    gradient.addColorStop(1, '#020617');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Draw thick outer card border
    ctx.strokeStyle = '#1e293b';
    ctx.lineWidth = 12;
    ctx.strokeRect(6, 6, canvas.width - 12, canvas.height - 12);
    
    // Draw thin gold neon border accent
    ctx.strokeStyle = '#eab308';
    ctx.lineWidth = 2;
    ctx.strokeRect(15, 15, canvas.width - 30, canvas.height - 30);

    // Draw Game title
    ctx.fillStyle = '#eab308'; // Gold
    ctx.font = 'bold 36px sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText("7VIDAS - MUNDIAL DE CLUBES", canvas.width / 2, 75);

    // Draw Team Name and Wins
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 28px sans-serif';
    ctx.fillText(gameState.myTeamName, canvas.width / 2, 125);

    ctx.fillStyle = '#22c55e'; // Success green
    ctx.font = 'bold 24px sans-serif';
    ctx.fillText(`Campanha Final: ${gameState.wins} Vitórias`, canvas.width / 2, 165);

    // Top separator
    ctx.strokeStyle = '#334155';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(50, 195);
    ctx.lineTo(750, 195);
    ctx.stroke();

    // Roster Header
    ctx.fillStyle = '#94a3b8';
    ctx.font = 'bold 20px sans-serif';
    ctx.textAlign = 'left';
    ctx.fillText("ELENCO FINAL ESCALADO", 70, 235);

    // Render Players
    ctx.font = '16px sans-serif';
    const slots = FORMATIONS[gameState.formation] || FORMATIONS['4-3-3 clássico'];
    let yPos = 275;
    
    slots.forEach((slot, index) => {
        const p = gameState.draftedPlayers[slot.id];
        const pName = p ? p.nome : 'Posição Vazia';
        const pRating = p ? p.nivel : '-';
        const pClub = p ? p.clubName : '';
        const pPos = slot.pos;

        // Draw position abbreviation badge background
        ctx.fillStyle = '#1e293b';
        ctx.fillRect(70, yPos - 18, 55, 24);
        
        ctx.fillStyle = '#eab308';
        ctx.font = 'bold 13px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(pPos, 97, yPos - 1);

        // Draw player name with a fixed layout to prevent overlaps
        ctx.textAlign = 'left';
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 15px sans-serif';
        ctx.fillText(pName, 140, yPos - 1);
        
        // Draw club name in a dedicated column to prevent overlapping issues
        if (pClub) {
            ctx.fillStyle = '#64748b';
            ctx.font = '13px sans-serif';
            ctx.fillText(`(${pClub})`, 380, yPos - 1);
        }

        // Draw rating
        ctx.textAlign = 'right';
        ctx.fillStyle = '#3b82f6';
        ctx.font = 'bold 16px sans-serif';
        ctx.fillText(pRating !== '-' ? `Nível ${pRating}` : '-', 730, yPos - 1);

        yPos += 35;
    });

    // Middle separator
    ctx.strokeStyle = '#334155';
    ctx.beginPath();
    ctx.moveTo(50, 680);
    ctx.lineTo(750, 680);
    ctx.stroke();

    // History Header
    ctx.fillStyle = '#94a3b8';
    ctx.font = 'bold 20px sans-serif';
    ctx.textAlign = 'left';
    ctx.fillText("HISTÓRICO COMPLETO DA CAMPANHA", 70, 720);

    // Render matches (all games from history now display!)
    yPos = 765;
    gameState.history.forEach((h, idx) => {
        const resColor = h.result === 'V' ? '#22c55e' : (h.result === 'E' ? '#eab308' : '#ef4444');
        const countryLabel = h.opponentCountry ? ` (${h.opponentCountry})` : '';

        ctx.textAlign = 'left';
        ctx.fillStyle = '#ffffff';
        ctx.font = '15px sans-serif';
        ctx.fillText(`Jogo ${idx + 1}: vs ${h.opponent}${countryLabel}`, 70, yPos);

        ctx.textAlign = 'right';
        ctx.fillStyle = resColor;
        ctx.font = 'bold 15px sans-serif';
        ctx.fillText(`${h.score} (${h.result})`, 730, yPos);

        yPos += 32;
    });

    // Draw Footer brand info
    ctx.fillStyle = '#475569';
    ctx.font = '13px sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText("confusa.top", canvas.width / 2, canvas.height - 35);

    // Trigger PNG Download
    const dataUrl = canvas.toDataURL('image/png');
    const link = document.createElement('a');
    link.download = `${gameState.myTeamName.replace(/\s+/g, '_')}_7vidas_resultado.png`;
    link.href = dataUrl;
    link.click();
}
