/**
 * topero_ui.js
 * Interface interativa, manipulação do DOM e fluxos de tela do TOPERO
 */

(function () {
  let mundo = null;
  let motor = null;
  let eventoAtual = null;
  let ofertasAtuais = [];

  // Elementos da interface
  const viewCriacao = document.getElementById('view-criacao');
  const viewJogo = document.getElementById('view-jogo');
  const viewAposentadoria = document.getElementById('view-aposentadoria');

  const selectPais = document.getElementById('select-pais');
  const selectClube = document.getElementById('select-clube');
  const notaGeografica = document.getElementById('nota-geografica');
  const bandeiraPreview = document.getElementById('bandeira-preview');

  // Inicialização
  document.addEventListener('DOMContentLoaded', init);

  async function init() {
    try {
      const res = await fetch('/topero/api.php?action=bootstrap');
      const data = await res.json();
      if (data.success) {
        mundo = data;
        popularPaises();
        configurarEventosFormulario();
        document.getElementById('loading-overlay').style.display = 'none';
      } else {
        alert('Não foi possível carregar os dados das federações do CONFUSA.');
      }
    } catch (e) {
      console.error('Erro ao carregar dados:', e);
      document.getElementById('loading-overlay').style.display = 'none';
    }
  }

  // Popula o combo de países (qualquer país do CONFUSA)
  function popularPaises() {
    selectPais.innerHTML = '<option value="" disabled selected>Selecione sua nacionalidade...</option>';
    mundo.paises.forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = `${p.nome} (${p.sigla})`;
      selectPais.appendChild(opt);
    });
  }

  // Atualiza clubes disponíveis para o país selecionado (com resolução de proximidade se não tiver liga)
  function atualizarClubesParaPais(idPais) {
    if (!idPais) return;
    idPais = parseInt(idPais, 10);
    const generoInput = document.querySelector('input[name="genero"]:checked');
    const sexo = generoInput ? parseInt(generoInput.value, 10) : 0;

    // Seleciona o mapa de vizinhança conforme o gênero (masculino ou feminino)
    const mapaVizinhos = sexo === 1 ? mundo.mapeamentoVizinhoFem : mundo.mapeamentoVizinhoMasc;
    const viz = (mapaVizinhos && mapaVizinhos[idPais]) ? mapaVizinhos[idPais] : mundo.mapeamentoVizinho[idPais];
    selectClube.innerHTML = '';

    if (!viz) {
      selectClube.innerHTML = '<option value="" disabled>Nenhum clube disponível</option>';
      return;
    }

    let idPaisComLiga = idPais;
    const catLabel = sexo === 1 ? 'feminina' : 'masculina';
    if (!viz.temLiga) {
      idPaisComLiga = viz.idPaisMaisProximo;
      const km = viz.distanciaKm ? ` (~${viz.distanciaKm} km)` : '';
      notaGeografica.innerHTML = `
        <div class="geo-badge">
          📍 Seu país natal não possui liga <strong>${catLabel}</strong> ativa. Por proximidade geográfica no mapa, você iniciará sua formação nos clubes de <strong>${viz.nomePaisMaisProximo}</strong>${km}!
        </div>
      `;
      notaGeografica.style.display = 'block';
    } else {
      notaGeografica.innerHTML = `
        <div class="geo-badge geo-badge-direct">
          ⭐ Seu país possui liga <strong>${catLabel}</strong> cadastrada no CONFUSA.top! Escolha seu clube de formação:
        </div>
      `;
      notaGeografica.style.display = 'block';
    }

    // Filtra clubes compatíveis estritamente com o sexo escolhido e país de destino
    let clubesDisponiveis = mundo.clubes.filter(c => {
      const sexoClube = c.sexo !== undefined ? parseInt(c.sexo, 10) : 0;
      return c.idPais === idPaisComLiga && sexoClube === sexo;
    });

    // Se o país de destino não tiver clubes cadastrados daquele sexo, busca clubes do país mais próximo geral
    if (clubesDisponiveis.length === 0 && mundo.mapeamentoVizinho && mundo.mapeamentoVizinho[idPais]) {
      const fallbackPaisId = mundo.mapeamentoVizinho[idPais].idPaisMaisProximo;
      clubesDisponiveis = mundo.clubes.filter(c => {
        const sexoClube = c.sexo !== undefined ? parseInt(c.sexo, 10) : 0;
        return c.idPais === fallbackPaisId && sexoClube === sexo;
      });
    }

    // Regra: pro início da carreira, só devem haver 3 opções aleatórias dentro do país
    const clubesSorteados = [...clubesDisponiveis]
      .sort(() => Math.random() - 0.5)
      .slice(0, 3);

    selectClube.innerHTML = '<option value="" disabled selected>Escolha uma das 3 propostas de base...</option>';
    clubesSorteados.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.id;
      opt.textContent = `${c.nome} (${c.nomeLiga || 'Liga Nacional'})`;
      selectClube.appendChild(opt);
    });
  }

  function configurarEventosFormulario() {
    selectPais.addEventListener('change', function () {
      const idPais = this.value;
      const p = mundo.paises.find(item => item.id == idPais);
      if (p && p.bandeira) {
        bandeiraPreview.src = `/images/bandeiras/${p.bandeira}`;
        bandeiraPreview.style.display = 'inline-block';
      } else {
        bandeiraPreview.style.display = 'none';
      }
      atualizarClubesParaPais(idPais);
    });

    // Ao alternar gênero (Masculino / Feminino), atualiza as opções de clube de estreia
    const radiosGenero = document.querySelectorAll('input[name="genero"]');
    radiosGenero.forEach(r => {
      r.addEventListener('change', function () {
        if (selectPais.value) {
          atualizarClubesParaPais(selectPais.value);
        }
      });
    });

    document.getElementById('form-novo-jogador').addEventListener('submit', function (e) {
      e.preventDefault();
      const nome = document.getElementById('input-nome').value.trim() || 'Atleta';
      const numero = document.getElementById('input-numero').value || 10;
      const generoInput = document.querySelector('input[name="genero"]:checked');
      const sexo = generoInput ? parseInt(generoInput.value, 10) : 0;
      const pe = document.querySelector('input[name="pe"]:checked').value;
      const posicao = document.getElementById('select-posicao').value;
      const modo = document.querySelector('input[name="cadencia"]:checked').value;
      const idPais = selectPais.value;
      const idClube = selectClube.value;

      if (!idPais || !idClube) {
        alert('Por favor, selecione sua nacionalidade e clube de estreia.');
        return;
      }

      const pObj = mundo.paises.find(p => p.id == idPais);
      const cObj = mundo.clubes.find(c => c.id == idClube);

      iniciarJogo({
        nome,
        numero,
        sexo,
        pe,
        posicao,
        pais: pObj,
        clubeInicial: cObj
      }, modo);
    });

    // Ações de jogo
    document.getElementById('btn-avancar-temporada').addEventListener('click', avancarCiclo);
    document.getElementById('btn-jogar-novamente').addEventListener('click', reiniciarJogo);
    document.getElementById('btn-salvar-carreira').addEventListener('click', salvarCarreiraNoPortal);
    document.getElementById('btn-baixar-card').addEventListener('click', baixarCardCarreira);
  }

  // Inicia uma nova carreira
  function iniciarJogo(dadosJogador, modo) {
    motor = new ToperoEngine(dadosJogador, modo, mundo);
    viewCriacao.style.display = 'none';
    viewAposentadoria.style.display = 'none';
    viewJogo.style.display = 'block';

    atualizarPainelAtleta();
    document.getElementById('lista-temporadas').innerHTML = '';
    
    // Rola suave para o topo do painel
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // Roda o primeiro bloco de temporadas
    avancarCiclo();
  }

  // Avança temporadas até a próxima tomada de decisão
  function avancarCiclo() {
    if (!motor || motor.aposentado) return;

    // Desabilita botão durante processamento
    const btnAvancar = document.getElementById('btn-avancar-temporada');
    btnAvancar.disabled = true;

    const novasTemporadas = motor.simularBlocoDecisao();

    // Renderiza novas temporadas no feed
    novasTemporadas.forEach(t => renderizarLinhaTemporada(t));
    atualizarPainelAtleta();

    // Verifica se aposentou
    if (motor.aposentado) {
      finalizarCarreira();
      return;
    }

    // Se não aposentou, dispara a tomada de decisão (Evento ou Transferência)
    abrirMomentoDecisao();
  }

  // Atualiza cabeçalho e métricas do atleta
  function atualizarPainelAtleta() {
    const jog = motor.jogador;
    document.getElementById('hud-nome').textContent = jog.nome;
    document.getElementById('hud-camisa').textContent = `#${jog.numero}`;
    document.getElementById('hud-posicao').textContent = jog.posicao;
    document.getElementById('hud-idade').textContent = `${jog.idade} anos`;
    document.getElementById('hud-ovr').textContent = jog.nivel;

    // Barra de OVR
    const barra = document.getElementById('hud-ovr-bar');
    if (barra) {
      barra.style.width = `${Math.min(100, Math.max(0, (jog.nivel - 40) * 1.66))}%`;
    }

    // Clube atual
    document.getElementById('hud-clube-nome').textContent = jog.clubeAtual.nome;
    document.getElementById('hud-clube-liga').textContent = jog.clubeAtual.nomeLiga || 'Liga';
    const escudoImg = document.getElementById('hud-clube-escudo');
    if (jog.clubeAtual.escudo) {
      escudoImg.src = `/images/escudos/${jog.clubeAtual.escudo}`;
      escudoImg.style.display = 'inline-block';
    } else {
      escudoImg.style.display = 'none';
    }

    // País
    document.getElementById('hud-pais-nome').textContent = jog.pais.nome;
    const bandImg = document.getElementById('hud-pais-bandeira');
    if (jog.pais.bandeira) {
      bandImg.src = `/images/bandeiras/${jog.pais.bandeira}`;
      bandImg.style.display = 'inline-block';
    }

    // Estatísticas Acumuladas
    const isGK = jog.posicao === 'GK';
    document.getElementById('stat-jogos').textContent = jog.estatisticasTotais.jogos;

    if (isGK) {
      document.getElementById('lbl-stat-gols').textContent = 'Gols Sofridos';
      document.getElementById('stat-gols').textContent = jog.estatisticasTotais.golsSofridos;
      document.getElementById('lbl-stat-assists').textContent = 'Jogos s/ Sofrer Gol';
      document.getElementById('stat-assists').textContent = jog.estatisticasTotais.jogosSemSofrerGol;
    } else {
      document.getElementById('lbl-stat-gols').textContent = 'Gols Marcados';
      document.getElementById('stat-gols').textContent = jog.estatisticasTotais.gols;
      document.getElementById('lbl-stat-assists').textContent = 'Assistências';
      document.getElementById('stat-assists').textContent = jog.estatisticasTotais.assists;
    }

    document.getElementById('stat-titulos').textContent = jog.estatisticasTotais.titulos;
  }

  // Renderiza um card da temporada no feed
  function renderizarLinhaTemporada(t) {
    const feed = document.getElementById('lista-temporadas');
    const card = document.createElement('div');
    card.className = 'temporada-card animate-fade-in';

    let htmlTrofeus = '';
    if (t.titulos.length > 0) {
      htmlTrofeus = '<div class="trofeus-temporada">';
      t.titulos.forEach(trof => {
        htmlTrofeus += `<span class="trofeu-badge" title="${trof.nome}">${obterSvgTrofeu(trof.icone)} ${trof.nome}</span>`;
      });
      htmlTrofeus += '</div>';
    }

    let htmlEscudo = t.clube.escudo ? `<img src="/images/escudos/${t.clube.escudo}" class="mini-escudo" alt="">` : '';
    let htmlStatus = t.status ? `<span class="badge-status-alerta" title="Impacto na temporada">${t.status}</span>` : '';

    const isGK = motor.jogador.posicao === 'GK';
    const stat2Html = isGK
      ? `<div class="stat-pill"><strong>${t.golsSofridos || 0}</strong> Gols Sofridos</div>`
      : `<div class="stat-pill"><strong>${t.gols}</strong> Gols</div>`;
    const stat3Html = isGK
      ? `<div class="stat-pill"><strong>${t.jogosSemSofrerGol || 0}</strong> Sem Levar Gol</div>`
      : `<div class="stat-pill"><strong>${t.assists}</strong> Assists</div>`;

    card.innerHTML = `
      <div class="temp-header">
        <div class="temp-ano-badge">Ano ${t.ano} (${t.idade} anos)</div>
        <div class="temp-clube-info">${htmlEscudo} <strong>${t.clube.nome}</strong> (${t.clube.nomeLiga || 'Liga'}) ${htmlStatus}</div>
        <div class="temp-ovr-badge">OVR ${t.nivel}</div>
      </div>
      <div class="temp-stats">
        <div class="stat-pill"><strong>${t.jogos}</strong> Jogos</div>
        ${stat2Html}
        ${stat3Html}
      </div>
      ${htmlTrofeus}
    `;

    // Insere no topo do histórico
    feed.insertBefore(card, feed.firstChild);
  }

  // Gera SVG para os troféus
  function obterSvgTrofeu(tipo) {
    switch (tipo) {
      case 'trofeu_ouro':
        return `<svg class="svg-trophy gold" viewBox="0 0 24 24"><path fill="#f59e0b" d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0 0 11 15.9V19H8v2h8v-2h-3v-3.1c1.86-.41 3.28-1.89 3.61-3.96C19.08 11.63 21 9.55 21 7V5c0-1.1-.9-2-2-2m-14 3V7h2v3.82C5.84 10.4 5 9.3 5 8m14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>`;
      case 'trofeu_prata':
        return `<svg class="svg-trophy silver" viewBox="0 0 24 24"><path fill="#94a3b8" d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0 0 11 15.9V19H8v2h8v-2h-3v-3.1c1.86-.41 3.28-1.89 3.61-3.96C19.08 11.63 21 9.55 21 7V5c0-1.1-.9-2-2-2m-14 3V7h2v3.82C5.84 10.4 5 9.3 5 8m14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>`;
      case 'trofeu_continental':
        return `<svg class="svg-trophy cyan" viewBox="0 0 24 24"><path fill="#0284c7" d="M12 2L9 7h6l-3-5zm0 18l-5-4h10l-5 4zM2 9l3 2 1-3H3c-.55 0-1 .45-1 1zm19-1h-3l1 3 3-2c0-.55-.45-1-1-1zm-9-1c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5z"/></svg>`;
      case 'bola_ouro':
        return `<svg class="svg-trophy gold" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#eab308"/><path fill="#ca8a04" d="M12 2a10 10 0 0 0-10 10c0 5.52 4.48 10 10 10 5.52 0 10-4.48 10-10A10 10 0 0 0 12 2zm1 17.93V17l2-2-1-2 2-2-2-2 1-2-2-2V4.07c3.84.47 6.9 3.53 7.37 7.37.06.56.06 1.12 0 1.68-.47 3.84-3.53 6.9-7.37 7.37z"/></svg>`;
      default:
        return `🏆`;
    }
  }

  // Abre a janela modal com Decisão (Janela de Transferências ou Dilema Raro de Carreira)
  function abrirMomentoDecisao() {
    const modal = document.getElementById('modal-decisao');

    // 1. Dilemas narrativos são raros (apenas ~3 a 4 em toda a carreira, checado por deveDispararDilema)
    // Regra: Nos anos em que tem dilema, NÃO tem mudança de time (a decisão daquele período é exclusivamente o dilema)
    if (motor.deveDispararDilema()) {
      eventoAtual = motor.sortearEvento();
      if (eventoAtual) {
        renderizarModalEvento(eventoAtual);
        modal.style.display = 'flex';
        return;
      }
    }

    // 2. Nos anos em que NÃO tem dilema, abre a janela de transferências com ofertas do CONFUSA
    ofertasAtuais = motor.gerarOfertasTransferencia();
    if (ofertasAtuais && ofertasAtuais.ofertas && ofertasAtuais.ofertas.length > 0) {
      renderizarModalTransferencia(ofertasAtuais);
      modal.style.display = 'flex';
      return;
    }

    // Se nenhuma janela ou evento abriu, libera o botão para avançar
    document.getElementById('btn-avancar-temporada').disabled = false;
  }

  function renderizarModalEvento(ev) {
    const container = document.getElementById('modal-decisao-conteudo');
    let botoes = '';

    ev.options.forEach(opt => {
      let descProb = '';
      if (opt.positiveOutcome && opt.negativeOutcome) {
        descProb = `<span class="prob-tag">${opt.positiveOutcome.probability}% de sucesso</span>`;
      }
      botoes += `
        <button class="btn-decisao-opcao" data-id="${opt.id}">
          <div class="decisao-label">${opt.label}</div>
          ${descProb}
        </button>
      `;
    });

    container.innerHTML = `
      <div class="decisao-tag">Momento Decisivo</div>
      <h3 class="decisao-titulo">${ev.title}</h3>
      <p class="decisao-desc">${ev.description}</p>
      <div class="decisao-opcoes-grid">
        ${botoes}
      </div>
      <div id="decisao-feedback" style="display:none;"></div>
    `;

    container.querySelectorAll('.btn-decisao-opcao').forEach(btn => {
      btn.addEventListener('click', function () {
        const idOpcao = this.getAttribute('data-id');
        processarEscolhaEvento(idOpcao);
      });
    });
  }

  function processarEscolhaEvento(idOpcao) {
    const resultado = motor.aplicarEscolhaEvento(eventoAtual, idOpcao);
    const feedback = document.getElementById('decisao-feedback');
    const grid = document.querySelector('.decisao-opcoes-grid');
    grid.style.display = 'none';

    let classe = resultado.sucesso ? 'resultado-sucesso' : 'resultado-falha';
    let sinal = resultado.ovrDelta > 0 ? `+${resultado.ovrDelta}` : `${resultado.ovrDelta}`;
    let ovrTxt = resultado.ovrDelta !== 0 ? `<strong>${sinal} Nível</strong>` : 'Sem alteração de Nível';

    feedback.className = `decisao-feedback-box ${classe}`;
    feedback.innerHTML = `
      <div class="feedback-desc">${resultado.descricao}</div>
      <div class="feedback-ovr">${ovrTxt}</div>
      <button id="btn-continuar-decisao" class="btn-primary" style="margin-top:15px;">Continuar Carreira</button>
    `;
    feedback.style.display = 'block';

    atualizarPainelAtleta();

    document.getElementById('btn-continuar-decisao').addEventListener('click', function () {
      document.getElementById('modal-decisao').style.display = 'none';
      document.getElementById('btn-avancar-temporada').disabled = false;
    });
  }

  function renderizarModalTransferencia(dadosTransferencia) {
    const container = document.getElementById('modal-decisao-conteudo');
    const ofertas = dadosTransferencia.ofertas || [];
    const podeRenovar = dadosTransferencia.podeRenovar !== false;

    let listaOfertas = '';

    ofertas.forEach(c => {
      let escudo = c.escudo ? `<img src="/images/escudos/${c.escudo}" class="transfer-escudo" alt="">` : '';
      listaOfertas += `
        <div class="card-oferta">
          <div class="oferta-info">
            ${escudo}
            <div>
              <div class="oferta-nome">${c.nome}</div>
              <div class="oferta-liga">${c.nomeLiga || 'Liga'} (${c.nomePais || 'País'})</div>
            </div>
          </div>
          <button class="btn-aceitar-transferencia btn-outline-cyan" data-clube="${c.id}">Assinar Contrato</button>
        </div>
      `;
    });

    let htmlAcaoPermanecer = '';
    let subtituloJanela = '';

    if (podeRenovar) {
      subtituloJanela = 'Seu desempenho atraiu o interesse de outros clubes do CONFUSA. Você pode aceitar uma proposta ou continuar no seu time atual.';
      htmlAcaoPermanecer = `
        <div style="margin-top: 20px; text-align: center;">
          <button id="btn-recusar-ofertas" class="btn-secondary">Permanecer no ${motor.jogador.clubeAtual.nome} (Renovação)</button>
        </div>
      `;
    } else {
      subtituloJanela = `<strong>Fim de ciclo:</strong> O ${motor.jogador.clubeAtual.nome} decidiu não renovar seu contrato. Você precisa assinar com um novo clube para seguir sua carreira!`;
      htmlAcaoPermanecer = `
        <div style="margin-top: 20px; text-align: center;">
          <div class="badge-status-alerta" style="display:inline-block; padding:6px 14px; font-size:0.85rem;">
            ⚠️ Contrato encerrado: escolha um dos clubes acima para continuar.
          </div>
        </div>
      `;
    }

    container.innerHTML = `
      <div class="decisao-tag">Mercado da Bola</div>
      <h3 class="decisao-titulo">Janela de Transferências Aberta!</h3>
      <p class="decisao-desc">${subtituloJanela}</p>
      <div class="ofertas-grid">
        ${listaOfertas}
      </div>
      ${htmlAcaoPermanecer}
    `;

    container.querySelectorAll('.btn-aceitar-transferencia').forEach(btn => {
      btn.addEventListener('click', function () {
        const idClube = parseInt(this.getAttribute('data-clube'), 10);
        const clubeEscolhido = mundo.clubes.find(c => c.id === idClube);
        motor.transferirPara(clubeEscolhido);
        atualizarPainelAtleta();
        document.getElementById('modal-decisao').style.display = 'none';
        document.getElementById('btn-avancar-temporada').disabled = false;
      });
    });

    const btnRecusar = document.getElementById('btn-recusar-ofertas');
    if (btnRecusar) {
      btnRecusar.addEventListener('click', function () {
        document.getElementById('modal-decisao').style.display = 'none';
        document.getElementById('btn-avancar-temporada').disabled = false;
      });
    }
  }

  // Tela Final de Aposentadoria
  function finalizarCarreira() {
    viewJogo.style.display = 'none';
    viewAposentadoria.style.display = 'block';

    const jog = motor.jogador;
    const tot = jog.estatisticasTotais;

    document.getElementById('final-nome').textContent = jog.nome;
    document.getElementById('final-numero-pos').textContent = `#${jog.numero} • ${jog.posicao}`;
    document.getElementById('final-pais').textContent = jog.pais.nome;
    document.getElementById('final-idade').textContent = `${jog.idade} anos`;
    document.getElementById('final-ovr-pico').textContent = jog.ovrMaximo;

    document.getElementById('final-jogos').textContent = tot.jogos;

    const isGK = jog.posicao === 'GK';
    if (isGK) {
      document.getElementById('lbl-final-gols').textContent = 'Gols Sofridos';
      document.getElementById('final-gols').textContent = tot.golsSofridos;
      document.getElementById('lbl-final-assists').textContent = 'Jogos s/ Sofrer Gol';
      document.getElementById('final-assists').textContent = tot.jogosSemSofrerGol;
    } else {
      document.getElementById('lbl-final-gols').textContent = 'Gols na Carreira';
      document.getElementById('final-gols').textContent = tot.gols;
      document.getElementById('lbl-final-assists').textContent = 'Assistências';
      document.getElementById('final-assists').textContent = tot.assists;
    }

    document.getElementById('final-titulos').textContent = tot.titulos;

    const elBolasOuro = document.getElementById('final-bolas-ouro');
    if (elBolasOuro) {
      elBolasOuro.textContent = tot.bolasOuro || 0;
    }

    const bandFinal = document.getElementById('final-pais-bandeira');
    if (bandFinal && jog.pais && jog.pais.bandeira) {
      bandFinal.src = `/images/bandeiras/${jog.pais.bandeira}`;
      bandFinal.style.display = 'inline-block';
    }

    // Resumo de troféus agrupados
    const containerTrofeus = document.getElementById('final-trofeus-lista');
    containerTrofeus.innerHTML = '';

    const contagem = {};
    tot.titulosDetalhados.forEach(t => {
      contagem[t.nome] = (contagem[t.nome] || 0) + 1;
    });

    const nomesTrofeus = Object.keys(contagem);
    if (nomesTrofeus.length === 0) {
      containerTrofeus.innerHTML = '<p class="text-muted" style="color:#94a3b8; font-size:0.9rem;">Nenhum título expressivo conquistado. Mas honrou a camisa até o fim!</p>';
    } else {
      nomesTrofeus.forEach(nome => {
        const item = tot.titulosDetalhados.find(t => t.nome === nome);
        const qtd = contagem[nome];
        const badge = document.createElement('div');
        badge.className = 'trofeu-final-card';
        badge.innerHTML = `
          ${obterSvgTrofeu(item.icone)}
          <div class="trofeu-final-qtd">x${qtd}</div>
          <div class="trofeu-final-nome">${nome}</div>
        `;
        containerTrofeus.appendChild(badge);
      });
    }

    // Clubes defendidos
    const containerClubes = document.getElementById('final-clubes-lista');
    containerClubes.innerHTML = '';
    jog.historicoClubes.forEach(h => {
      const el = document.createElement('div');
      el.className = 'clube-hist-badge';
      const escudo = h.clube.escudo ? `<img src="/images/escudos/${h.clube.escudo}" class="mini-escudo" alt="">` : '⚽ ';
      const anos = h.anoFim ? (h.anoInicio === h.anoFim ? `Ano ${h.anoInicio}` : `Anos ${h.anoInicio}-${h.anoFim}`) : `Ano ${h.anoInicio}+`;
      el.innerHTML = `${escudo} <strong>${h.clube.nome}</strong> (${anos})`;
      containerClubes.appendChild(el);
    });

    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  // Salvar Carreira no Banco de Dados do Portal
  async function salvarCarreiraNoPortal() {
    const btn = document.getElementById('btn-salvar-carreira');
    btn.disabled = true;
    btn.textContent = 'Salvando carreira...';

    const jog = motor.jogador;
    const payload = {
      nome_jogador: jog.nome,
      numero: jog.numero,
      sexo: jog.sexo !== undefined ? jog.sexo : 0,
      posicao: jog.posicao,
      id_pais_origem: jog.pais.id,
      id_ultimo_clube: jog.clubeAtual.id,
      idade_final: jog.idade,
      ovr_maximo: jog.ovrMaximo,
      partidas_totais: jog.estatisticasTotais.jogos,
      gols_totais: jog.estatisticasTotais.gols,
      assistencias_totais: jog.estatisticasTotais.assists,
      gols_sofridos: jog.estatisticasTotais.golsSofridos,
      clean_sheets: jog.estatisticasTotais.jogosSemSofrerGol,
      titulos_totais: jog.estatisticasTotais.titulos,
      bolas_ouro: jog.estatisticasTotais.bolasOuro,
      detalhes: {
        temporadas: jog.temporadas,
        historicoClubes: jog.historicoClubes
      }
    };

    try {
      const res = await fetch('/topero/api.php?action=salvar_carreira', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        btn.textContent = '✓ Carreira Salva no Hall da Fama!';
        btn.classList.add('btn-sucesso');
      } else {
        alert(data.message || 'Faça login no CONFUSA.top para salvar sua carreira.');
        btn.disabled = false;
        btn.textContent = 'Salvar no Meu Perfil';
      }
    } catch (e) {
      alert('Erro de conexão ao salvar carreira.');
      btn.disabled = false;
      btn.textContent = 'Salvar no Meu Perfil';
    }
  }

  // Baixar Card de Compartilhamento (via html2canvas aguardando 100% do carregamento)
  async function baixarCardCarreira() {
    const btn = document.getElementById('btn-baixar-card');
    const txtOriginal = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⏳ Preparando e renderizando card em alta definição...';

    const cardElement = document.getElementById('card-carreira-exportable');

    try {
      // 1. Aguarda fontes Google carregarem
      if (document.fonts && document.fonts.ready) {
        await document.fonts.ready;
      }

      // 2. Aguarda TODAS as imagens dentro do card carregarem e decodificarem por completo
      const imgs = Array.from(cardElement.querySelectorAll('img'));
      await Promise.all(imgs.map(img => {
        if (img.complete && img.naturalWidth > 0) {
          if (img.decode) return img.decode().catch(() => {});
          return Promise.resolve();
        }
        return new Promise(resolve => {
          img.onload = async () => {
            if (img.decode) {
              try { await img.decode(); } catch(e) {}
            }
            resolve();
          };
          img.onerror = resolve;
          setTimeout(resolve, 3000);
        });
      }));

      // 3. Pausa de sincronização de layout no navegador
      await new Promise(r => setTimeout(r, 250));

      // 4. Captura com html2canvas de alta resolução (2x retina)
      if (typeof html2canvas === 'function') {
        const canvas = await html2canvas(cardElement, {
          scale: 2,
          useCORS: true,
          allowTaint: true,
          backgroundColor: '#0f172a',
          logging: false
        });

        const jog = motor.jogador;
        const link = document.createElement('a');
        link.download = `topero-${jog.nome.toLowerCase().replace(/\s+/g, '-')}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
      } else {
        alert('Aguarde o carregamento do recurso de captura e tente novamente.');
      }

    } catch (err) {
      console.error('Erro ao gerar card html2canvas:', err);
      alert('Houve um erro ao processar o card. Tente novamente.');
    } finally {
      btn.disabled = false;
      btn.innerHTML = txtOriginal;
    }
  }

  function reiniciarJogo() {
    viewAposentadoria.style.display = 'none';
    viewJogo.style.display = 'none';
    viewCriacao.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
})();

// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/topero/sw.js')
      .then(reg => {
        // console.log('TOPERO Service Worker registrado com sucesso: ', reg.scope);
      })
      .catch(err => {
        // console.error('Erro ao registrar Service Worker do TOPERO: ', err);
      });
  });
}

// PWA Installation prompt handling
let deferredPromptTopero;
const installBannerTopero = document.getElementById('pwa-install-banner');
const btnInstallTopero = document.getElementById('pwa-btn-install');
const btnCancelTopero = document.getElementById('pwa-btn-cancel');

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPromptTopero = e;

  const isDismissed = localStorage.getItem('topero-pwa-dismissed');
  const dismissedTime = isDismissed ? parseInt(isDismissed, 10) : 0;
  const now = Date.now();

  // Exibe o banner após 3 segundos se não foi dispensado nos últimos 3 dias
  if (now - dismissedTime > 3 * 24 * 60 * 60 * 1000) {
    setTimeout(() => {
      if (installBannerTopero) installBannerTopero.classList.add('show');
    }, 3000);
  }
});

if (btnInstallTopero) {
  btnInstallTopero.addEventListener('click', () => {
    if (deferredPromptTopero) {
      deferredPromptTopero.prompt();
      deferredPromptTopero.userChoice.then((choiceResult) => {
        deferredPromptTopero = null;
        if (installBannerTopero) installBannerTopero.classList.remove('show');
      });
    }
  });
}

if (btnCancelTopero) {
  btnCancelTopero.addEventListener('click', () => {
    if (installBannerTopero) installBannerTopero.classList.remove('show');
    localStorage.setItem('topero-pwa-dismissed', Date.now().toString());
  });
}

