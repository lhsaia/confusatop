/**
 * topero_engine.js
 * Motor de simulação de carreira do TOPERO (CONFUSA.top)
 * Calcula progressão de idade, Nível (OVR), simulação de partidas, gols, assistências,
 * títulos locais/continentais, ofertas de transferência e aposentadoria.
 */

window.ToperoEngine = class ToperoEngine {
  constructor(dadosJogador, modoCadencia, dadosMundo) {
    this.jogador = {
      nome: dadosJogador.nome || 'Jogador',
      numero: parseInt(dadosJogador.numero, 10) || 10,
      sexo: dadosJogador.sexo !== undefined ? parseInt(dadosJogador.sexo, 10) : 0, // 0 = Masc, 1 = Fem
      pe: dadosJogador.pe || 'Destro',
      posicao: dadosJogador.posicao || 'ST',
      pais: dadosJogador.pais, // { id, nome, sigla, bandeira, federacao, forcaSelecaoMasc, forcaSelecaoFem }
      clubeAtual: dadosJogador.clubeInicial, // { id, nome, sigla, escudo, tierLiga, nomeLiga, idPais, nomePais }
      idade: 17,
      nivel: Math.floor(62 + Math.random() * 6), // 62 a 67 no início
      ovrMaximo: 62,
      estatisticasTotais: {
        jogos: 0,
        gols: 0,
        assists: 0,
        golsSofridos: 0,
        jogosSemSofrerGol: 0,
        titulos: 0,
        bolasOuro: 0,
        jogosSelecao: 0,
        golsSelecao: 0,
        titulosSelecao: 0,
        titulosDetalhados: []
      },
      historicoClubes: [
        {
          clube: dadosJogador.clubeInicial,
          anoInicio: 1,
          anoFim: 1
        }
      ],
      temporadas: []
    };

    this.jogador.ovrMaximo = this.jogador.nivel;
    this.modo = modoCadencia || 'normal'; // 'long' (1 ano), 'normal' (2 anos), 'express' (3 anos)
    this.intervaloDecisao = this.modo === 'long' ? 1 : (this.modo === 'express' ? 3 : 2);
    this.mundo = dadosMundo; // { paises, ligas, clubes, mapeamentoVizinho }
    this.temporadaAtual = 0;
    this.aposentado = false;

    // Modificadores ativos que impactam a temporada seguinte (ex: suspensão, lesão grave, bônus)
    this.modTemporada = {
      minutosMult: 1.0,
      suspenso: false,
      lesao: false,
      rotuloStatus: null
    };

    // Agendamento de eventos narrativos (dilemas de vida):
    // Dilemas pessoais acontecem ~1 a cada 4-5 anos ao longo da carreira até os 40 anos.
    this.eventosConcluidos = [];
    this.idadesEventosAgendados = this.agendarIdadesEventos();
  }

  // Define de 3 a 4 idades em que haverá um dilema narrativo raro ao longo da carreira
  agendarIdadesEventos() {
    // Carreira vai dos 17 aos 40 anos.
    // Intervalos: jovem (20-22), ápice (25-27), maturidade (30-33), veterano (35-38)
    const faixas = [
      [20, 22],
      [25, 27],
      [30, 33],
      [35, 38]
    ];
    const idades = [];
    faixas.forEach(f => {
      const sorteada = Math.floor(Math.random() * (f[1] - f[0] + 1)) + f[0];
      idades.push(sorteada);
    });
    return idades;
  }

  // Obtém o nome da competição continental de seleções com base na federação do país
  obterCompeticaoSelecao(idFederacao) {
    const fed = parseInt(idFederacao, 10);
    switch (fed) {
      case 1:
        return { nome: 'FEASCOPA', federacaoNome: 'FEASCO', icone: 'trofeu_continental_selecao' };
      case 2:
        return { nome: 'Copa dos Três Mares', federacaoNome: 'FEMIFUS', icone: 'trofeu_continental_selecao' };
      case 3:
        return { nome: 'Taça dos Hemisférios', federacaoNome: 'COMPACTA', icone: 'trofeu_continental_selecao' };
      default:
        return { nome: 'Copa Continental de Seleções', federacaoNome: 'CONFUSA', icone: 'trofeu_continental_selecao' };
    }
  }

  // Multiplicadores estatísticos por posição
  obterPesosPosicao(pos) {
    switch (pos) {
      case 'GK':
        return { jogos: [34, 46], gols: [0, 0], assists: [0, 1], pesoTrofeu: 0.9 };
      case 'CB':
      case 'LB':
      case 'RB':
        return { jogos: [30, 48], gols: [1, 6], assists: [2, 9], pesoTrofeu: 0.95 };
      case 'CDM':
      case 'CM':
        return { jogos: [32, 50], gols: [3, 12], assists: [6, 18], pesoTrofeu: 1.0 };
      case 'CAM':
      case 'LM':
      case 'RM':
      case 'RW':
      case 'LW':
        return { jogos: [32, 52], gols: [8, 24], assists: [8, 22], pesoTrofeu: 1.05 };
      case 'ST':
      default:
        return { jogos: [32, 54], gols: [14, 38], assists: [4, 15], pesoTrofeu: 1.1 };
    }
  }

  // Simula 1 temporada individual
  simularTemporadaIndividual() {
    if (this.aposentado) return null;

    this.temporadaAtual++;
    this.jogador.idade++;

    // 1. Curva natural de evolução por idade
    let deltaNatural = 0;
    const idade = this.jogador.idade;
    if (idade <= 21) {
      deltaNatural = Math.floor(Math.random() * 4) + 1; // +1 a +4
    } else if (idade <= 25) {
      deltaNatural = Math.floor(Math.random() * 3) + 1; // +1 a +3
    } else if (idade <= 29) {
      deltaNatural = Math.floor(Math.random() * 3) - 1; // -1 a +1
    } else if (idade <= 33) {
      deltaNatural = -(Math.floor(Math.random() * 2) + 1); // -1 a -2
    } else {
      deltaNatural = -(Math.floor(Math.random() * 3) + 1); // -1 a -3
    }

    this.jogador.nivel = Math.max(45, Math.min(99, this.jogador.nivel + deltaNatural));
    if (this.jogador.nivel > this.jogador.ovrMaximo) {
      this.jogador.ovrMaximo = this.jogador.nivel;
    }

    // 2. Estatísticas em campo no clube
    const pesos = this.obterPesosPosicao(this.jogador.posicao);
    const fatorNivel = (this.jogador.nivel - 50) / 45; // 0.2 a 1.05

    // Aplica multiplicador de minutos por consequências de eventos (ex: suspensão, lesão, rotação)
    const multMinutos = this.modTemporada.minutosMult || 1.0;
    const statusAtivo = this.modTemporada.rotuloStatus || (this.modTemporada.suspenso ? 'Suspenso' : (this.modTemporada.lesao ? 'Lesionado' : null));

    // Jogos disputados no ano no clube
    const minJogos = pesos.jogos[0];
    const maxJogos = pesos.jogos[1];
    let jogosBase = Math.round(minJogos + (maxJogos - minJogos) * Math.min(1.0, fatorNivel * (0.7 + Math.random() * 0.5)));
    let jogos = Math.max(0, Math.round(jogosBase * multMinutos));

    // Gols e assistências para jogadores de linha, ou gols sofridos e jogos sem sofrer gol para goleiros (GK)
    let gols = 0;
    let assists = 0;
    let golsSofridos = 0;
    let jogosSemSofrerGol = 0;

    if (this.jogador.posicao === 'GK') {
      const taxaGolsSofridos = Math.max(0.55, 1.45 - (this.jogador.nivel - 50) * 0.015);
      golsSofridos = jogos === 0 ? 0 : Math.max(0, Math.round(jogos * taxaGolsSofridos * (0.85 + Math.random() * 0.3)));
      const taxaCleanSheets = Math.min(0.52, Math.max(0.12, 0.18 + (this.jogador.nivel - 50) * 0.007));
      jogosSemSofrerGol = jogos === 0 ? 0 : Math.max(0, Math.round(jogos * taxaCleanSheets * (0.85 + Math.random() * 0.3)));
    } else {
      const minGols = pesos.gols[0];
      const maxGols = pesos.gols[1];
      let golsBase = Math.round(minGols + (maxGols - minGols) * Math.max(0.05, fatorNivel * (0.6 + Math.random() * 0.7)));
      gols = jogos === 0 ? 0 : Math.max(0, Math.round(golsBase * multMinutos));

      const minAss = pesos.assists[0];
      const maxAss = pesos.assists[1];
      let assistsBase = Math.round(minAss + (maxAss - minAss) * Math.max(0.05, fatorNivel * (0.6 + Math.random() * 0.7)));
      assists = jogos === 0 ? 0 : Math.max(0, Math.round(assistsBase * multMinutos));
    }

    // 3. Simulação de Seleção Nacional (Convocação e Partidas Internacionais)
    const sexo = this.jogador.sexo || 0;
    const forcaPaisBase = (sexo === 1 ? this.jogador.pais.forcaSelecaoFem : this.jogador.pais.forcaSelecaoMasc) || 64;
    
    // Jogador é convocado se tiver OVR suficiente em relação ao nível da seleção do seu país
    // (ex: seleções menores convocam com OVR mais baixo, seleções de ponta exigem OVR maior)
    const limiteConvocacao = Math.max(65, forcaPaisBase - 4);
    let convocadoSelecao = false;
    let jogosSelecaoAno = 0;
    let golsSelecaoAno = 0;

    if (this.jogador.nivel >= limiteConvocacao && jogos >= 15 && !this.modTemporada.suspenso) {
      convocadoSelecao = true;
      jogosSelecaoAno = Math.floor(Math.random() * 5) + 4; // 4 a 8 jogos pela seleção no ano
      if (this.jogador.posicao !== 'GK') {
        const taxaGolSelecao = this.jogador.posicao === 'ST' ? 0.45 : (['RW','LW','CAM'].includes(this.jogador.posicao) ? 0.28 : 0.12);
        golsSelecaoAno = Math.round(jogosSelecaoAno * taxaGolSelecao * (this.jogador.nivel / 80) * (0.6 + Math.random() * 0.8));
      }
    }

    // 4. Conquista de Troféus na Temporada
    const titulosAno = [];
    const tierClube = this.jogador.clubeAtual.tierLiga || 1;
    const fatorParticipacao = Math.min(1.0, multMinutos);

    // Liga Nacional
    if (tierClube === 1) {
      // 1ª Divisão
      const probLiga = (this.jogador.nivel / 100) * 0.32 * fatorParticipacao;
      if (Math.random() < probLiga) {
        titulosAno.push({
          tipo: 'liga',
          nome: `${this.jogador.clubeAtual.nomeLiga || 'Liga Nacional'}`,
          categoria: 'Liga Nacional',
          icone: 'trofeu_ouro'
        });
      }
    } else {
      // 2ª ou 3ª Divisão (Título de Acesso)
      const probLigaAcesso = (this.jogador.nivel / 90) * 0.38 * fatorParticipacao;
      if (Math.random() < probLigaAcesso) {
        titulosAno.push({
          tipo: 'liga_acesso',
          nome: `${this.jogador.clubeAtual.nomeLiga || 'Segunda Divisão'} (Acesso)`,
          categoria: 'Divisão de Acesso',
          icone: 'trofeu_bronze'
        });
      }
    }

    // Copa Nacional (Taça do País)
    // Times de Tier 1 têm probabilidade normal. Times de Tier 2/3 têm chance residual de "zebra histórica" (3% a 5%)
    let probCopa = 0;
    if (tierClube === 1) {
      probCopa = 0.20 * (this.jogador.nivel / 80) * fatorParticipacao;
    } else {
      probCopa = 0.04 * (this.jogador.nivel / 85) * fatorParticipacao; // Zebra rara
    }

    if (Math.random() < probCopa) {
      const prefixoZebra = tierClube > 1 ? ' [Zebra Épica]' : '';
      titulosAno.push({
        tipo: 'copa',
        nome: `Copa de ${this.jogador.clubeAtual.nomePais || 'País'}${prefixoZebra}`,
        categoria: 'Copa Nacional',
        icone: 'trofeu_prata'
      });
    }

    // Copa do Mundo CONFUSA de Clubes (apenas Tier 1 com OVR alto >= 80 e time de ponta)
    if (tierClube === 1 && this.jogador.nivel >= 80 && Math.random() < 0.14 * fatorParticipacao) {
      titulosAno.push({
        tipo: 'mundial_clubes',
        nome: `Copa do Mundo CONFUSA de Clubes`,
        categoria: 'Mundial de Clubes',
        icone: 'trofeu_continental'
      });
    }

    // Torneios de Seleções Nacionais (se convocado)
    if (convocadoSelecao) {
      const forcaTotalSelecao = (forcaPaisBase * 0.65) + (this.jogador.nivel * 0.35);

      // Torneio Continental de Seleções (ocorre a cada ciclo bienal de anos pares)
      if (this.temporadaAtual % 2 === 0 && (this.temporadaAtual % 4 !== 0)) {
        const torneioFed = this.obterCompeticaoSelecao(this.jogador.pais.federacao);
        const probContinentalSelecao = (forcaTotalSelecao / 100) * 0.24 * fatorParticipacao;
        if (Math.random() < probContinentalSelecao) {
          titulosAno.push({
            tipo: 'torneio_selecao',
            nome: `${torneioFed.nome} (${this.jogador.pais.nome})`,
            categoria: `Seleções • ${torneioFed.federacaoNome}`,
            icone: 'trofeu_selecao'
          });
          this.jogador.estatisticasTotais.titulosSelecao++;
        }
      }

      // Copa do Mundo de Seleções CONFUSA (ocorre a cada 4 anos)
      if (this.temporadaAtual % 4 === 0) {
        const probMundialSelecao = Math.pow(forcaTotalSelecao / 100, 2) * 0.20 * fatorParticipacao;
        if (Math.random() < probMundialSelecao) {
          titulosAno.push({
            tipo: 'copa_mundo_selecao',
            nome: `Copa do Mundo de Seleções (${this.jogador.pais.nome})`,
            categoria: 'Seleções • Mundial',
            icone: 'trofeu_copa_mundo'
          });
          this.jogador.estatisticasTotais.titulosSelecao++;
        }
      }
    }

    // Bola de Ouro CONFUSA (Melhor Atleta do Mundo):
    // REGRA ESTRITA: O atleta DEVE estar na 1ª Divisão (tierClube === 1), OVR >= 88 e ter feito temporada dominante
    let bolaDeOuro = false;
    const destaqueGK = this.jogador.posicao === 'GK' && jogosSemSofrerGol >= 20;
    const destaqueLinha = (gols + assists >= 35 || this.jogador.posicao === 'CB');
    if (tierClube === 1 && jogos >= 25 && this.jogador.nivel >= 88 && (destaqueGK || destaqueLinha) && Math.random() < (this.jogador.nivel - 86) * 0.08) {
      bolaDeOuro = true;
      titulosAno.push({
        tipo: 'bola_ouro',
        nome: 'Melhor Jogador do Ano CONFUSA (Bola de Ouro)',
        categoria: 'Individual',
        icone: 'bola_ouro'
      });
      this.jogador.estatisticasTotais.bolasOuro++;
    }

    // Atualiza totais
    this.jogador.estatisticasTotais.jogos += jogos;
    this.jogador.estatisticasTotais.gols += gols;
    this.jogador.estatisticasTotais.assists += assists;
    this.jogador.estatisticasTotais.golsSofridos += golsSofridos;
    this.jogador.estatisticasTotais.jogosSemSofrerGol += jogosSemSofrerGol;
    this.jogador.estatisticasTotais.jogosSelecao += jogosSelecaoAno;
    this.jogador.estatisticasTotais.golsSelecao += golsSelecaoAno;
    this.jogador.estatisticasTotais.titulos += titulosAno.length;
    titulosAno.forEach(t => this.jogador.estatisticasTotais.titulosDetalhados.push(t));

    const registroTemporada = {
      ano: this.temporadaAtual,
      idade: this.jogador.idade,
      clube: { ...this.jogador.clubeAtual },
      nivel: this.jogador.nivel,
      jogos,
      gols,
      assists,
      golsSofridos,
      jogosSemSofrerGol,
      convocadoSelecao,
      jogosSelecao: jogosSelecaoAno,
      golsSelecao: golsSelecaoAno,
      titulos: titulosAno,
      bolaDeOuro,
      status: statusAtivo
    };

    // Reseta o modificador temporário após aplicar à temporada
    this.modTemporada = {
      minutosMult: 1.0,
      suspenso: false,
      lesao: false,
      rotuloStatus: null
    };

    this.jogador.temporadas.push(registroTemporada);

    // Checar aposentadoria natural: encerramento oficial aos 40 anos de idade
    if (this.jogador.idade >= 40) {
      this.aposentado = true;
    }

    return registroTemporada;
  }

  // Executa o bloco de temporadas até a próxima tomada de decisão
  simularBlocoDecisao() {
    const temporadasSimuladas = [];
    for (let i = 0; i < this.intervaloDecisao; i++) {
      if (this.aposentado) break;
      const t = this.simularTemporadaIndividual();
      if (t) temporadasSimuladas.push(t);
    }
    return temporadasSimuladas;
  }

  // Gera 2 ou 3 ofertas de clubes para o jogador escolher se quer se transferir
  // e define se o clube atual ofereceu renovação de contrato com critérios realistas
  gerarOfertasTransferencia() {
    if (!this.mundo || !this.mundo.clubes || this.mundo.clubes.length === 0) {
      return { ofertas: [], podeRenovar: true };
    }
    const nivel = this.jogador.nivel;
    const idade = this.jogador.idade;
    const sexoJogador = this.jogador.sexo !== undefined ? this.jogador.sexo : 0;
    const clubeAtual = this.jogador.clubeAtual;
    const clubeAtualId = clubeAtual ? clubeAtual.id : null;
    const tierClubeAtual = (clubeAtual && clubeAtual.tierLiga) ? clubeAtual.tierLiga : 1;

    // Filtra clubes disponíveis do mesmo sexo e diferentes do atual
    const potenciais = this.mundo.clubes.filter(c => {
      if (clubeAtualId && c.id === clubeAtualId) return false;
      const sexoClube = c.sexo !== undefined ? parseInt(c.sexo, 10) : 0;
      return sexoClube === sexoJogador;
    });

    const tier1Clubes = potenciais.filter(c => (c.tierLiga || 1) === 1);
    const tier2Clubes = potenciais.filter(c => (c.tierLiga || 1) >= 2);

    const selecionados = [];

    // Lógica de mercado estruturada por patamar do atleta e idade:
    // A) Atleta Veterano (35+ anos):
    //    Não recebe propostas de superclubes de Tier 1 no auge, a não ser propostas ocasionais de Tier 1 médio,
    //    priorizando clubes de tier 2, ligas alternativas ou seu país de origem.
    if (idade >= 35) {
      const embaralhadosT2 = [...tier2Clubes].sort(() => Math.random() - 0.5);
      const embaralhadosT1 = [...tier1Clubes].sort(() => Math.random() - 0.5);
      
      // Proposta 1: Clube de Tier 2 (experiência para buscar acesso)
      if (embaralhadosT2.length > 0) selecionados.push(embaralhadosT2[0]);
      // Proposta 2: Clube do mesmo país ou alternativo
      const clubeMesmoPais = potenciais.find(c => c.idPais === this.jogador.pais.id && !selecionados.some(s => s.id === c.id));
      if (clubeMesmoPais) selecionados.push(clubeMesmoPais);
      else if (embaralhadosT2.length > 1) selecionados.push(embaralhadosT2[1]);
      // Proposta 3: Tier 1 modesto se OVR ainda for razoável (>= 72), ou outro Tier 2
      if (nivel >= 72 && embaralhadosT1.length > 0) selecionados.push(embaralhadosT1[0]);
      else if (embaralhadosT2.length > 2) selecionados.push(embaralhadosT2[2]);
    }
    // B) Atleta no Auge ou Grande Promessa com Alto Nível (OVR >= 78 e idade < 35):
    //    Recebe propostas de elite de Tier 1 de diversos países competitivos.
    else if (nivel >= 78) {
      const embaralhadosT1 = [...tier1Clubes].sort(() => Math.random() - 0.5);
      // Pega os primeiros 3 clubes de Tier 1 disponíveis
      for (const c of embaralhadosT1) {
        if (selecionados.length >= 3) break;
        selecionados.push(c);
      }
    }
    // C) Atleta em Desenvolvimento / Nível Médio (OVR 68 a 77):
    //    Mix balanceado entre clubes de Tier 1 médios e clubes de ponta de Tier 2
    else if (nivel >= 68) {
      const embaralhadosT1 = [...tier1Clubes].sort(() => Math.random() - 0.5);
      const embaralhadosT2 = [...tier2Clubes].sort(() => Math.random() - 0.5);
      if (embaralhadosT1.length > 0) selecionados.push(embaralhadosT1[0]);
      if (embaralhadosT1.length > 1) selecionados.push(embaralhadosT1[1]);
      if (embaralhadosT2.length > 0) selecionados.push(embaralhadosT2[0]);
    }
    // D) Atleta em Início / Nível Baixo (OVR < 68):
    //    Clubes de Tier 2/3 para buscar rodagem e desenvolvimento
    else {
      const embaralhadosT2 = [...tier2Clubes].sort(() => Math.random() - 0.5);
      for (const c of embaralhadosT2) {
        if (selecionados.length >= 3) break;
        selecionados.push(c);
      }
    }

    // Fallback garantido se ainda faltar alguma proposta
    const todosEmbaralhados = [...potenciais].sort(() => Math.random() - 0.5);
    while (selecionados.length < Math.min(3, potenciais.length)) {
      const extra = todosEmbaralhados.find(c => !selecionados.some(s => s.id === c.id));
      if (extra) selecionados.push(extra);
      else break;
    }

    // LÓGICA DE RENOVAÇÃO CONTRATUAL CONSCIENTE:
    // Nunca demitir jovens promessas ou atletas que acabaram de ser campeões/titulares importantes.
    let podeRenovar = true;
    const ultimasTemporadas = this.jogador.temporadas.slice(-this.intervaloDecisao);
    const teveTitulosRecentes = ultimasTemporadas.some(t => t.titulos && t.titulos.length > 0);
    const mediaJogosRecente = ultimasTemporadas.length > 0 
      ? ultimasTemporadas.reduce((acc, t) => acc + (t.jogos || 0), 0) / ultimasTemporadas.length 
      : 30;

    if (idade < 22) {
      // Jovem em formação: clube sempre oferece renovação
      podeRenovar = true;
    } else if (teveTitulosRecentes && mediaJogosRecente >= 20) {
      // Campeão e peça atuante do elenco: clube quer manter a qualquer custo
      podeRenovar = true;
    } else if (tierClubeAtual === 1 && nivel < 66) {
      // Nível muito baixo para a primeira divisão: dispensa técnica real
      podeRenovar = false;
    } else if (idade >= 36 && mediaJogosRecente < 18 && Math.random() < 0.35) {
      // Veterano em fim de ciclo com pouca minutagem: não renovação por renovação de elenco
      podeRenovar = false;
    } else {
      podeRenovar = true;
    }

    return {
      ofertas: selecionados,
      podeRenovar
    };
  }

  // Transfere o jogador para um novo clube
  transferirPara(novoClube) {
    if (!novoClube) return;
    this.jogador.clubeAtual = novoClube;
    const hist = this.jogador.historicoClubes;
    if (hist.length > 0) {
      hist[hist.length - 1].anoFim = this.temporadaAtual;
    }
    hist.push({
      clube: novoClube,
      anoInicio: this.temporadaAtual + 1,
      anoFim: this.temporadaAtual + 1
    });
  }

  // Checa se deve disparar um dilema pessoal raro nesta idade
  deveDispararDilema() {
    const idade = this.jogador.idade;
    // Verifica se a idade atual está na lista de agendados e ainda não teve evento disparado nessa faixa
    const slotIdx = this.idadesEventosAgendados.findIndex(agendada => Math.abs(agendada - idade) <= (this.intervaloDecisao - 1));
    if (slotIdx !== -1) {
      // Remove o slot agendado para não disparar repetidamente no mesmo ciclo
      this.idadesEventosAgendados.splice(slotIdx, 1);
      return true;
    }
    return false;
  }

  // Sorteia um evento narrativo da lista de eventos, priorizando eventos ainda não vistos
  sortearEvento() {
    const lista = window.TOPERO_EVENTS || [];
    if (lista.length === 0) return null;

    // Filtra eventos que ainda não foram concluídos nesta carreira
    const naoVistos = lista.filter(ev => !this.eventosConcluidos.includes(ev.id));
    const candidatos = naoVistos.length > 0 ? naoVistos : lista;

    const indice = Math.floor(Math.random() * candidatos.length);
    const escolhido = candidatos[indice];
    this.eventosConcluidos.push(escolhido.id);
    return escolhido;
  }

  // Aplica a escolha de um evento
  aplicarEscolhaEvento(evento, opcaoId) {
    const opcao = evento.options.find(o => o.id === opcaoId);
    if (!opcao) return null;

    let resultado = {
      sucesso: true,
      ovrDelta: 0,
      minutosMult: 1.0,
      suspenso: false,
      lesao: false,
      descricao: ''
    };

    let outcomeObj = null;

    if (opcao.positiveOutcome && opcao.negativeOutcome) {
      const roll = Math.random() * 100;
      if (roll <= opcao.positiveOutcome.probability) {
        outcomeObj = opcao.positiveOutcome;
        resultado.sucesso = true;
      } else {
        outcomeObj = opcao.negativeOutcome;
        resultado.sucesso = false;
      }
    } else if (opcao.outcome) {
      outcomeObj = opcao.outcome;
    } else if (opcao.positiveOutcome) {
      outcomeObj = opcao.positiveOutcome;
    } else if (opcao.negativeOutcome) {
      outcomeObj = opcao.negativeOutcome;
      resultado.sucesso = false;
    }

    if (outcomeObj) {
      resultado.ovrDelta = outcomeObj.ovrDelta || 0;
      resultado.descricao = outcomeObj.description;
      resultado.minutosMult = outcomeObj.minutosMult !== undefined ? outcomeObj.minutosMult : 1.0;
      resultado.suspenso = !!outcomeObj.suspenso;
      resultado.lesao = !!outcomeObj.lesao;
    }

    // Aplica os efeitos para a próxima temporada/bloco
    this.modTemporada = {
      minutosMult: resultado.minutosMult,
      suspenso: resultado.suspenso,
      lesao: resultado.lesao,
      rotuloStatus: resultado.suspenso ? 'Suspenso' : (resultado.lesao ? 'Lesionado' : null)
    };

    // Se o evento e opcao envolvem troca de clube para o maior rival da mesma liga
    if (opcao.trocaRival && this.mundo && this.mundo.clubes && this.jogador.clubeAtual) {
      const clubeAtual = this.jogador.clubeAtual;
      const sexoJogador = this.jogador.sexo !== undefined ? this.jogador.sexo : 0;
      
      // Prioridade 1: Clubes da mesmíssima liga (rival direto)
      let rivais = this.mundo.clubes.filter(c => {
        if (c.id === clubeAtual.id) return false;
        const sexoClube = c.sexo !== undefined ? parseInt(c.sexo, 10) : 0;
        if (sexoClube !== sexoJogador) return false;
        return c.idLiga && clubeAtual.idLiga && c.idLiga === clubeAtual.idLiga;
      });

      // Prioridade 2 (Fallback): Clubes do mesmo país caso a liga só tenha 1 time cadastrado
      if (rivais.length === 0) {
        rivais = this.mundo.clubes.filter(c => {
          if (c.id === clubeAtual.id) return false;
          const sexoClube = c.sexo !== undefined ? parseInt(c.sexo, 10) : 0;
          if (sexoClube !== sexoJogador) return false;
          return c.idPais && clubeAtual.idPais && c.idPais === clubeAtual.idPais;
        });
      }

      // Prioridade 3 (Fallback global): Qualquer outro clube ativo do mesmo gênero
      if (rivais.length === 0) {
        rivais = this.mundo.clubes.filter(c => {
          if (c.id === clubeAtual.id) return false;
          const sexoClube = c.sexo !== undefined ? parseInt(c.sexo, 10) : 0;
          return sexoClube === sexoJogador;
        });
      }

      if (rivais.length > 0) {
        const rivalSorteado = rivais[Math.floor(Math.random() * rivais.length)];
        const clubeAntigoNome = clubeAtual.nome;
        this.transferirPara(rivalSorteado);
        resultado.novoClube = rivalSorteado;
        resultado.descricao = `Transferência bombástica concretizada! Você deixou o ${clubeAntigoNome} e agora veste a camisa do ${rivalSorteado.nome}! ${resultado.descricao}`;
      }
    }

    // Se o evento e opcao envolvem troca de nacionalidade (naturalizacao)
    if (opcao.trocaPais && this.mundo && this.mundo.paises) {
      const outrosPaises = this.mundo.paises.filter(p => p.id !== this.jogador.pais.id);
      if (outrosPaises.length > 0) {
        const sorteado = outrosPaises[Math.floor(Math.random() * outrosPaises.length)];
        const paisAntigo = this.jogador.pais.nome;
        this.jogador.pais = sorteado;
        resultado.novoPais = sorteado;
        resultado.descricao = `Naturalizado com sucesso! Você abriu mão da seleção de ${paisAntigo} e agora defende oficialmente ${sorteado.nome}!`;
      }
    }

    this.jogador.nivel = Math.max(45, Math.min(99, this.jogador.nivel + resultado.ovrDelta));
    if (this.jogador.nivel > this.jogador.ovrMaximo) {
      this.jogador.ovrMaximo = this.jogador.nivel;
    }

    return resultado;
  }
};
