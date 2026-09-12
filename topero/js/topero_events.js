/**
 * topero_events.js
 * Banco de eventos, dilemas, propostas e probabilidades para o TOPERO (CONFUSA.top)
 * Inspirado nas decisões do Simulador de Carrera da Copero e ambientado nas federações e clubes do CONFUSA.
 */

window.TOPERO_EVENTS = [
  {
    id: "training_extra",
    title: "Treino Extra de Pré-Temporada",
    description: "A comissão técnica oferece uma rotina pesada em dois turnos antes da estreia. Você pode acelerar sua evolução ou sofrer uma lesão precoce.",
    weight: 100,
    options: [
      {
        id: "accept",
        label: "Encarar a rotina pesada",
        positiveOutcome: { probability: 70, ovrDelta: 3, minutosMult: 1.1, description: "+3 Nível por evolução atlética e foco! Ganhou mais ritmo e minutos em campo." },
        negativeOutcome: { probability: 30, ovrDelta: -2, minutosMult: 0.75, description: "Sentiu uma fisgada muscular grave na pré-temporada: -2 Nível e perdeu 25% dos jogos tratando a lesão." }
      },
      {
        id: "reject",
        label: "Priorizar o descanso e a prevenção",
        outcome: { ovrDelta: 0, minutosMult: 1.0, description: "Você manteve a carga padrão sem riscos adicionais." }
      }
    ]
  },
  {
    id: "personal_coach",
    title: "Preparador Pessoal e Fisiologia",
    description: "Um especialista renomado propõe reajustar totalmente sua mecânica de chute e plano alimentar.",
    weight: 90,
    options: [
      {
        id: "accept",
        label: "Mudar a técnica e nutrição",
        positiveOutcome: { probability: 60, ovrDelta: 2, minutosMult: 1.05, description: "+2 Nível com finalização mais precisa e excelente resistência física!" },
        negativeOutcome: { probability: 40, ovrDelta: -2, minutosMult: 0.85, description: "Você demorou a se adaptar ao novo estilo: -2 Nível e perdeu minutos no time titular." }
      },
      {
        id: "reject",
        label: "Manter seu estilo natural",
        outcome: { ovrDelta: 0, minutosMult: 1.0, description: "Decidiu continuar com sua mecânica habitual." }
      }
    ]
  },
  {
    id: "mysterious_substance",
    title: "Suplemento Experimental",
    description: "Um preparador do clube sugere um composto importado de alta performance. Melhora o fôlego drasticamente, mas se cair no antidoping, a suspensão é certa.",
    weight: 40,
    options: [
      {
        id: "consume",
        label: "Consumir o suplemento",
        positiveOutcome: { probability: 75, ovrDelta: 5, minutosMult: 1.15, description: "+5 Nível! Rendimento absurdo em campo, titular absoluto e arrancadas imparáveis!" },
        negativeOutcome: { probability: 25, ovrDelta: -4, minutosMult: 0.25, suspenso: true, description: "Flagrado no antidoping! Suspensão de vários meses: disputou apenas uma fração das partidas do ano e -4 Nível." }
      },
      {
        id: "reject",
        label: "Recusar e manter jogo limpo",
        outcome: { ovrDelta: 0, minutosMult: 1.0, description: "Você optou pela integridade e não se arriscou." }
      }
    ]
  },
  {
    id: "season_load",
    title: "Carga da Nova Temporada",
    description: "Após um calendário desgastante, a nova época exigirá ainda mais. Você pede para acelerar na titularidade ou preservar o corpo?",
    weight: 80,
    options: [
      {
        id: "high_load",
        label: "Carga máxima (buscar protagonismo)",
        positiveOutcome: { probability: 70, ovrDelta: 2, minutosMult: 1.1, description: "Líder técnico do time e titular absoluto em todos os jogos!" },
        negativeOutcome: { probability: 30, ovrDelta: -3, minutosMult: 0.6, description: "Exaustão física e lesão articular severa: -3 Nível e quase metade da temporada no estaleiro." }
      },
      {
        id: "preserve",
        label: "Moderar os minutos em campo",
        outcome: { ovrDelta: 0, minutosMult: 0.85, description: "Menos minutos na liga (rotação de elenco), mas corpo 100% preservado." }
      }
    ]
  },
  {
    id: "rival_offer",
    title: "Proposta do Maior Rival",
    description: "Um dos grandes rivais diretos da sua liga fez uma proposta milionária para contratar você e montar um 'super-time'.",
    weight: 70,
    options: [
      {
        id: "accept",
        label: "Aceitar a transferência polêmica",
        trocaRival: true,
        positiveOutcome: { probability: 65, ovrDelta: 3, minutosMult: 1.05, description: "+3 Nível e elenco estelar! Mais chances de títulos na temporada." },
        negativeOutcome: { probability: 35, ovrDelta: -1, minutosMult: 0.75, description: "Pressão hostil da torcida rival e você amargou o banco em várias rodadas cruciais." }
      },
      {
        id: "reject",
        label: "Permanecer fiel ao seu clube",
        outcome: { ovrDelta: 1, minutosMult: 1.05, description: "+1 Nível de moral e idolatria eterna com a torcida local!" }
      }
    ]
  },
  {
    id: "injury_at_peak",
    title: "Lesão no Auge da Competição",
    description: "Você sentiu o joelho às vésperas dos confrontos decisivos da temporada. Forçar para jogar no sacrifício ou parar para tratar?",
    weight: 60,
    options: [
      {
        id: "play_injured",
        label: "Jogar no sacrifício com infiltração",
        positiveOutcome: { probability: 55, ovrDelta: 2, minutosMult: 1.0, description: "Herói da conquista! Título garantido com atuação épica mesmo mancando." },
        negativeOutcome: { probability: 45, ovrDelta: -4, minutosMult: 0.35, lesao: true, description: "O esforço rompeu os ligamentos! Cirurgia e 7 meses fora: quase não entrou em campo no ano (-4 Nível)." }
      },
      {
        id: "recover",
        label: "Priorizar a recuperação clínica",
        outcome: { ovrDelta: -1, minutosMult: 0.8, description: "Ficou fora das finais para se recuperar, perdendo algumas partidas mas evitando lesão crônica." }
      }
    ]
  },
  {
    id: "decisive_penalty",
    title: "Pênalti Decisivo na Final",
    description: "Último lance da grande final continental! A bola da sua carreira está na marca da cal. Para onde você vai cobrar?",
    weight: 50,
    options: [
      {
        id: "left",
        label: "Bater forte no canto esquerdo",
        positiveOutcome: { probability: 60, ovrDelta: 3, minutosMult: 1.05, description: "GOL HISTÓRICO! Bola na gaveta e título comemorado aos prantos!" },
        negativeOutcome: { probability: 40, ovrDelta: -2, minutosMult: 0.9, description: "O goleiro espalmou no cantinho... Desilusão amarga e abalo de confiança." }
      },
      {
        id: "right",
        label: "Deslocar o goleiro no canto direito",
        positiveOutcome: { probability: 60, ovrDelta: 3, minutosMult: 1.05, description: "GOL DA GLÓRIA! Goleiro foi para um lado, bola para o outro!" },
        negativeOutcome: { probability: 40, ovrDelta: -2, minutosMult: 0.9, description: "Cobrança saiu raspando a trave... Tristeza e perda de prestígio." }
      }
    ]
  },
  {
    id: "fan_backlash",
    title: "Cobrança Pesada da Torcida",
    description: "Após uma sequência ruim, organizadas cercam o treino e exigem sua saída imediata do time titular.",
    weight: 55,
    options: [
      {
        id: "stay_and_fight",
        label: "Assumir a bronca e dar a volta por cima",
        positiveOutcome: { probability: 65, ovrDelta: 2, minutosMult: 1.05, description: "+2 Nível com raça, gols decisivos e aplausos de pé da torcida!" },
        negativeOutcome: { probability: 35, ovrDelta: -2, minutosMult: 0.7, description: "O clima pesou ainda mais: vaias a cada toque e você acabou afastado pelo técnico (-2 Nível)." }
      },
      {
        id: "search_exit",
        label: "Pedir para seu empresário negociar saída",
        outcome: { ovrDelta: 0, minutosMult: 0.8, description: "Você forçou a rescisão e abriu caminho para novas ofertas, jogando menos até a transferência." }
      }
    ]
  },
  {
    id: "honesty_test",
    title: "Abordagem Obscura",
    description: "Um grupo suspeito oferece uma quantia astronômica em dinheiro se você cometer um erro defensivo ou forçar um cartão amarelo no clássico.",
    weight: 35,
    options: [
      {
        id: "accept",
        label: "Aceitar a mala preta",
        positiveOutcome: { probability: 40, ovrDelta: 1, minutosMult: 1.0, description: "O esquema passou despercebido e você embolsou a fortuna." },
        negativeOutcome: { probability: 60, ovrDelta: -6, minutosMult: 0.15, suspenso: true, description: "Esquema descoberto! Banimento pelo tribunal desportivo: temporada arruinada com quase zero jogos e -6 Nível." }
      },
      {
        id: "reject",
        label: "Denunciar e recusar categoricamente",
        outcome: { ovrDelta: 2, minutosMult: 1.05, description: "+2 Nível de respeito da federação e liderança moral inabalável no vestiário!" }
      }
    ]
  },
  {
    id: "giant_tattoo",
    title: "Festa e Tatuagem Extravagante",
    description: "Durante a folga de fim de ano, amigos te desafiam a tatuar uma águia gigante no peito e fechar a noite na balada.",
    weight: 45,
    options: [
      {
        id: "accept",
        label: "Fazer a tatuagem e curtir a festa",
        positiveOutcome: { probability: 70, ovrDelta: 1, minutosMult: 1.0, description: "+1 Nível de moral e autoestima no topo!" },
        negativeOutcome: { probability: 30, ovrDelta: -2, minutosMult: 0.8, description: "A tatuagem inflamou feio: você perdeu boa parte das rodadas iniciais (-2 Nível)." }
      },
      {
        id: "reject",
        label: "Dormir cedo e manter o foco atlético",
        outcome: { ovrDelta: 0, minutosMult: 1.0, description: "Nenhuma distração extracampo. Foco total nos treinos e condicionamento pleno." }
      }
    ]
  },
  {
    id: "foreign_grandfather",
    title: "Cidadania e Troca de Seleção",
    description: "Descobriram registros comprovando que seus avós vieram de outra nação do CONFUSA. Você recebeu o convite formal para mudar sua nacionalidade esportiva!",
    weight: 40,
    options: [
      {
        id: "switch",
        label: "Aceitar a naturalização esportiva",
        trocaPais: true,
        positiveOutcome: { probability: 80, ovrDelta: 2, minutosMult: 1.05, description: "Naturalização aceita! Você passou a defender a nova seleção nacional (+2 Nível) com grande destaque!" },
        negativeOutcome: { probability: 20, ovrDelta: -1, minutosMult: 0.95, description: "Naturalização aceita em meio a fortes críticas públicas no seu país de origem (-1 Nível)." }
      },
      {
        id: "keep",
        label: "Defender apenas a sua pátria de berço",
        outcome: { ovrDelta: 1, minutosMult: 1.0, description: "+1 Nível por orgulho patriótico inegociável." }
      }
    ]
  },
  {
    id: "club_national_conflict",
    title: "Impasse Clube x Seleção",
    description: "A diretoria do seu clube tenta vetar sua apresentação para a Copa Continental, ameaçando deixá-lo no banco se viajar.",
    weight: 50,
    options: [
      {
        id: "go_anyway",
        label: "Viajar e defender seu país a qualquer custo",
        positiveOutcome: { probability: 70, ovrDelta: 3, minutosMult: 0.9, description: "+3 Nível com atuações de gala no torneio de seleções, mesmo com retaliação do clube!" },
        negativeOutcome: { probability: 30, ovrDelta: -2, minutosMult: 0.5, description: "Relações rompidas com a diretoria do clube: você foi afastado por meses e jogou metade da temporada (-2 Nível)." }
      },
      {
        id: "comply",
        label: "Ceder às exigências do clube",
        outcome: { ovrDelta: 0, minutosMult: 1.0, description: "Você manteve a titularidade no clube sem conflitos internos." }
      }
    ]
  }
];

window.TOPERO_EASTER_EGGS = [
  {
    id: "rei_do_khemed",
    title: "👑 A Coroa Perdida do Khemed",
    description: "Uma frota de limusines folheadas a ouro e escoltada por cavalaria do deserto cerca o centro de treinamento. Um emissário imperial trajando seda e turbante desembarca com um pergaminho milenar selado com o brasão da dinastia Kalish: exames genealógicos comprovam que você é o herdeiro legítimo do Trono do Khemed! O reino de Wadesdah exige que você abandone imediatamente as chuteiras para assumir o trono e governar os poços de petróleo.",
    easterEgg: true,
    options: [
      {
        id: "aceitar_trono",
        label: "👑 Abdicar do futebol e ser coroado Rei do Khemed!",
        outcome: {
          aposentar: true,
          ovrDelta: 0,
          motivoAposentadoria: "Abdicou dos gramados para ser coroado Sua Majestade Real, Soberano do Khemed!",
          trofeuEspecial: {
            tipo: "trofeu_rei",
            nome: "Coroa Imperial do Khemed",
            categoria: "Realeza & Império",
            icone: "trofeu_rei"
          },
          description: "VIDA LONGA AO REI! 👑 Você pendurou as chuteiras imediatamente e embarcou no jato real rumo a Wadesdah. Agora você comanda um reino com 40 palácios de mármore, 500 camelos de corrida e poços infinitos de petróleo! Sua carreira no futebol acabou de forma apoteótica e lendária."
        }
      },
      {
        id: "recusar_trono",
        label: "⚽ 'Meu reino são as quatro linhas!' (Recusar a coroa)",
        outcome: {
          ovrDelta: 3,
          minutosMult: 1.1,
          description: "O emissário ficou chocado com sua devoção ao futebol, mas em respeito te presenteou com uma mala de barras de ouro puro e o título de Príncipe Honorário (+3 Nível e moral nas alturas)!"
        }
      }
    ]
  },
  {
    id: "copa_andromeda",
    title: "🛸 O Chamado do Planeta Cúbico de Mali (COMETA)",
    description: "Uma nave em formato de cubo perfeito pousa silenciosamente no gramado durante o treino noturno. Uma delegação espacial desembarca vestindo uniformes com o lendário brasão da COMETA — a antiga confederação da CONFUSA que todos julgavam extinta! Eles revelam que a COMETA nunca acabou, apenas se transferiu para o espaço profundo, e agora o Planeta Cúbico de Mali precisa do seu futebol para disputar a Taça Interplanetária!",
    easterEgg: true,
    options: [
      {
        id: "embarcar_nave",
        label: "🛸 Embarcar na nave cúbica e defender Mali na COMETA!",
        outcome: {
          aposentar: true,
          ovrDelta: 0,
          motivoAposentadoria: "Abduzido para defender o Planeta Cúbico de Mali na renascida confederação COMETA!",
          trofeuEspecial: {
            tipo: "trofeu_alien",
            nome: "Taça Interplanetária da COMETA (Planeta Mali)",
            categoria: "COMETA • Interplanetário",
            icone: "trofeu_alien"
          },
          description: "GLÓRIA CÚBICA NO ESPAÇO! 🛸 Você embarcou rumo ao Planeta Cúbico de Mali! A COMETA está mais viva do que nunca além das estrelas. Dizem que você dominou a física dos chutes de efeito com gravidade cúbica e virou lenda eterna no universo!"
        }
      },
      {
        id: "ficar_na_terra",
        label: "🌍 'Agradeço à COMETA, mas tenho compromisso na CONFUSA!'",
        outcome: {
          ovrDelta: 4,
          minutosMult: 1.15,
          description: "A delegação de Mali admirou sua fidelidade à CONFUSA terrestre e te presenteou com um propulsor antigravitacional cúbico para as suas chuteiras (+4 Nível)!"
        }
      }
    ]
  },
  {
    id: "bilionario_topero_coin",
    title: "💎 A Fortuna Secreta do ToperoCoin",
    description: "Uma criptomoeda que você comprou por 10 reais na adolescência ('ToperoCoin') valorizou 900.000.000% esta madrugada. Você acordou sendo oficialmente o homem mais rico do planeta, com patrimônio superior a vários países da CONFUSA juntos!",
    easterEgg: true,
    options: [
      {
        id: "comprar_ilha",
        label: "🏝️ Comprar uma ilha de um famoso ator americano e viver de sombra e água fresca!",
        outcome: {
          aposentar: true,
          ovrDelta: 0,
          motivoAposentadoria: "Encerrou a carreira precocemente para viver como magnata em seu arquipélago oceânico!",
          trofeuEspecial: {
            tipo: "trofeu_ilha",
            nome: "Escritura do Arquipélago Paradisíaco",
            categoria: "Magnata Global",
            icone: "trofeu_ilha"
          },
          description: "VIDA DE MAGNATA! 🏝️ Você comprou o arquipélago inteiro, um iate de 15 andares com campinho de futebol society na proa e contratou seus antigos companheiros de time como consultores de churrasco!"
        }
      },
      {
        id: "jogar_por_amor",
        label: "⚽ Jogar puramente por amor à camisa (Doar fortunas)",
        outcome: {
          ovrDelta: 2,
          minutosMult: 1.05,
          description: "Você financiou novos estádios para todos os times da liga e passou a jogar 100% livre de pressão financeira (+2 Nível de serenidade)!"
        }
      }
    ]
  }
];
