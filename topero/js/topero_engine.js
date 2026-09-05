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
      pais: dadosJogador.pais, // { id, nome, sigla, bandeira, federacao }
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

    // 2. Estatísticas em campo
    const pesos = this.obterPesosPosicao(this.jogador.posicao);
    const fatorNivel = (this.jogador.nivel - 50) / 45; // 0.2 a 1.05

    // Aplica multiplicador de minutos por consequências de eventos (ex: suspensão, lesão, rotação)
    const multMinutos = this.modTemporada.minutosMult || 1.0;
    const statusAtivo = this.modTemporada.rotuloStatus || (this.modTemporada.suspenso ? 'Suspenso' : (this.modTemporada.lesao ? 'Lesionado' : null));

    // Jogos disputados no ano
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
      // Para goleiro:
      // Gols sofridos: menor quanto maior o nível do goleiro e do time
      // Média de 0.7 a 1.4 gols por jogo
      const taxaGolsSofridos = Math.max(0.55, 1.45 - (this.jogador.nivel - 50) * 0.015);
      golsSofridos = jogos === 0 ? 0 : Math.max(0, Math.round(jogos * taxaGolsSofridos * (0.85 + Math.random() * 0.3)));

      // Jogos sem sofrer gol (Clean Sheets): 20% a 48% das partidas conforme nível
      const taxaCleanSheets = Math.min(0.52, Math.max(0.12, 0.18 + (this.jogador.nivel - 50) * 0.007));
      jogosSemSofrerGol = jogos === 0 ? 0 : Math.max(0, Math.round(jogos * taxaCleanSheets * (0.85 + Math.random() * 0.3)));
    } else {
      // Gols proporcionais ao número de partidas disputadas
      const minGols = pesos.gols[0];
      const maxGols = pesos.gols[1];
      let golsBase = Math.round(minGols + (maxGols - minGols) * Math.max(0.05, fatorNivel * (0.6 + Math.random() * 0.7)));
      gols = jogos === 0 ? 0 : Math.max(0, Math.round(golsBase * multMinutos));

      // Assistências proporcionais
      const minAss = pesos.assists[0];
      const maxAss = pesos.assists[1];
      let assistsBase = Math.round(minAss + (maxAss - minAss) * Math.max(0.05, fatorNivel * (0.6 + Math.random() * 0.7)));
      assists = jogos === 0 ? 0 : Math.max(0, Math.round(assistsBase * multMinutos));
    }

    // 3. Conquista de Troféus na Temporada
    const titulosAno = [];
    const tierClube = this.jogador.clubeAtual.tierLiga || 1;
    // Se suspenso ou com quase nenhum jogo, chance de títulos é penalizada
    const fatorParticipacao = Math.min(1.0, multMinutos);

    // Probabilidade de título da liga (clubes de tier 1 e alto nível têm mais chance)
    const probLiga = (this.jogador.nivel / 100) * (tierClube === 1 ? 0.35 : 0.20) * fatorParticipacao;
    if (Math.random() < probLiga) {
      titulosAno.push({
        tipo: 'liga',
        nome: `${this.jogador.clubeAtual.nomeLiga || 'Liga Nacional'}`,
        categoria: 'Liga Nacional',
        icone: 'trofeu_ouro'
      });
    }

    // Probabilidade de Copa Nacional
    if (Math.random() < 0.22 * (this.jogador.nivel / 80) * fatorParticipacao) {
      titulosAno.push({
        tipo: 'copa',
        nome: `Copa de ${this.jogador.clubeAtual.nomePais || 'País'}`,
        categoria: 'Copa Nacional',
        icone: 'trofeu_prata'
      });
    }

    // Se estiver em time forte de tier 1 e nível alto: chance de Torneio Continental
    if (tierClube === 1 && this.jogador.nivel >= 76 && Math.random() < 0.16 * fatorParticipacao) {
      titulosAno.push({
        tipo: 'continental',
        nome: `Copa dos Campeões CONFUSA`,
        categoria: 'Continental de Clubes',
        icone: 'trofeu_continental'
      });
    }

    // Bola de Ouro CONFUSA (Melhor Atleta do Mundo): se nível >= 88 e teve grande temporada e jogou regularmente
    let bolaDeOuro = false;
    const destaqueGK = this.jogador.posicao === 'GK' && jogosSemSofrerGol >= 20;
    const destaqueLinha = (gols + assists >= 35 || this.jogador.posicao === 'CB');
    if (jogos >= 25 && this.jogador.nivel >= 88 && (destaqueGK || destaqueLinha) && Math.random() < (this.jogador.nivel - 85) * 0.08) {
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
  // e define se o clube atual ofereceu renovação de contrato
  gerarOfertasTransferencia() {
    if (!this.mundo || !this.mundo.clubes || this.mundo.clubes.length === 0) {
      return { ofertas: [], podeRenovar: true };
    }
    const nivel = this.jogador.nivel;
    const sexoJogador = this.jogador.sexo !== undefined ? this.jogador.sexo : 0;
    const clubeAtualId = this.jogador.clubeAtual ? this.jogador.clubeAtual.id : null;

    // Filtra clubes disponíveis do mesmo sexo (masculino vs feminino) e diferentes do atual
    const potenciais = this.mundo.clubes.filter(c => {
      if (clubeAtualId && c.id === clubeAtualId) return false;
      const sexoClube = c.sexo !== undefined ? parseInt(c.sexo, 10) : 0;
      return sexoClube === sexoJogador;
    });

    // Ordena ou pontua clubes com base na adequação ao nível do atleta
    // Nível alto (>= 80): clubes de tier 1
    // Nível médio (68-79): clubes de tier 1 e tier 2
    // Nível baixo (< 68): clubes de tier 2 ou tier 3
    const embaralhados = [...potenciais].sort(() => Math.random() - 0.5);
    const selecionados = [];

    for (const c of embaralhados) {
      if (selecionados.length >= 3) break;
      const tier = c.tierLiga || 1;
      if (nivel >= 82 && tier <= 1) {
        selecionados.push(c);
      } else if (nivel >= 72 && tier <= 2) {
        selecionados.push(c);
      } else if (nivel < 72) {
        selecionados.push(c);
      }
    }

    // Fallback se não preencheu 3
    while (selecionados.length < Math.min(3, potenciais.length)) {
      const extra = embaralhados.find(c => !selecionados.some(s => s.id === c.id));
      if (extra) selecionados.push(extra);
      else break;
    }

    // Regra: nem sempre deve haver opção de continuar no próprio time (renovação negada pelo clube)
    // No início da carreira (abaixo de 21 anos), os clubes têm paciência para maturação da promessa e sempre aceitam renovar/manter.
    // Acima disso: se o jogador estiver com nível baixo em clube forte de tier 1, veterano, ou por decisão da diretoria (~28% de chance)
    let podeRenovar = true;
    const idade = this.jogador.idade;
    const tierClubeAtual = this.jogador.clubeAtual.tierLiga || 1;

    if (idade < 21) {
      // Jogador jovem em formação: clube mantém o atleta para desenvolvimento
      podeRenovar = true;
    } else if (tierClubeAtual === 1 && nivel < 68) {
      // Dispensado por baixo rendimento
      podeRenovar = false;
    } else if (idade >= 34 && Math.random() < 0.45) {
      // Diretoria optou por rejuvenescimento do elenco
      podeRenovar = false;
    } else if (Math.random() < 0.28) {
      // Fim de contrato sem acordo de renovação
      podeRenovar = false;
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
