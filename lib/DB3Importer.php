<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/paises.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/jogador.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/estadio.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/clima.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/tecnico.php';

class DB3Importer {
    private $mysqlDb;
    private $paisObj;
    private $timeObj;
    private $jogadorObj;
    private $estadioObj;
    private $climaObj;
    private $tecnicoObj;

    public function __construct($mysqlDb) {
        $this->mysqlDb = $mysqlDb;
        $this->paisObj = new Pais($mysqlDb);
        $this->timeObj = new Time($mysqlDb);
        $this->jogadorObj = new Jogador($mysqlDb);
        $this->estadioObj = new Estadio($mysqlDb);
        $this->climaObj = new Clima($mysqlDb);
        $this->tecnicoObj = new Tecnico($mysqlDb);
    }

    /**
     * Helper para normalizar strings para busca case-insensitive e trim.
     */
    private function normalizeName($name) {
        $clean = trim(preg_replace('/\s*\[.*?\]\s*$/', '', (string)$name));
        return mb_strtolower($clean, 'UTF-8');
    }

    /**
     * Detecta se o clube do DB3 é uma Seleção Nacional (Principal, Sub-20, Sub-18, Olímpica/U21)
     * ou um Clube Regular (0).
     *
     * @param string $clubNome
     * @param string $paisNome
     * @return int 0: Clube Regular, 1: Seleção Principal, 2: Olímpica/U21/U23, 3: Sub-20, 4: Sub-18
     */
    public function detectSelecaoStatus($clubNome, $paisNome) {
        $cleanClub = mb_strtolower(trim((string)$clubNome), 'UTF-8');
        $cleanPais = mb_strtolower(trim((string)$paisNome), 'UTF-8');

        // Normalização de base
        $baseClub = trim(preg_replace('/\s*\[.*?\]\s*$/', '', $cleanClub));
        $baseClub = trim(preg_replace('/\s*\(.*?\)\s*$/', '', $baseClub));

        // 1. Sub-20 / U-20 / S-20
        if (preg_match('/\b(sub[- ]?20|u[- ]?20|s[- ]?20|sub20|u20|s20)\b/i', $cleanClub)) {
            return 3;
        }

        // 2. Sub-18 / U-18 / S-18 / Sub-17 / Sub-19
        if (preg_match('/\b(sub[- ]?18|u[- ]?18|s[- ]?18|sub18|u18|s18|sub[- ]?17|u[- ]?17|sub[- ]?19|u[- ]?19)\b/i', $cleanClub)) {
            return 4;
        }

        // 3. Olímpica / Sub-21 / Sub-23 / U-21 / U-23
        if (preg_match('/\b(ol[ií]mpica|sub[- ]?21|u[- ]?21|s[- ]?21|sub21|u21|sub[- ]?23|u[- ]?23|sub23|u23)\b/i', $cleanClub) || preg_match('/\[o\]/i', $cleanClub)) {
            return 2;
        }

        // 4. Seleção Principal
        if (
            $baseClub === $cleanPais ||
            $baseClub === 'seleção ' . $cleanPais ||
            $baseClub === 'selecao ' . $cleanPais ||
            $baseClub === $cleanPais . ' principal' ||
            $baseClub === 'república de ' . $cleanPais ||
            $baseClub === 'republica de ' . $cleanPais
        ) {
            return 1;
        }

        return 0;
    }

    /**
     * Retorna a descrição amigável do tipo/status do clube.
     */
    public function getSelecaoLabel($status) {
        switch ((int)$status) {
            case 1: return 'Seleção Principal';
            case 2: return 'Seleção Olímpica / Sub-21';
            case 3: return 'Seleção Sub-20';
            case 4: return 'Seleção Sub-18';
            default: return 'Clube Regular';
        }
    }

    /**
     * Gera uma prévia completa de importação sem alterar o banco de dados MySQL.
     *
     * @param string $sqliteFilePath Caminho absoluto do arquivo .db3
     * @param int $idPais ID do país no MySQL
     * @param int $sexo 0 para Masculino, 1 para Feminino
     * @param int $userId ID do usuário administrador executando a ação
     * @return array Estrutura detalhada da prévia
     * @throws Exception Em caso de erro irrecuperável
     */
    public function previewFile($sqliteFilePath, $idPais, $sexo, $userId) {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        if (!file_exists($sqliteFilePath)) {
            throw new InvalidArgumentException("Arquivo DB3 não encontrado no caminho informado.");
        }

        // Conectar ao SQLite .db3
        try {
            $sqliteDb = new PDO('sqlite:' . $sqliteFilePath);
            $sqliteDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sqliteDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Não foi possível abrir o arquivo SQLite .db3: " . $e->getMessage());
        }

        // Verificar tabelas essenciais no SQLite
        $sqliteTables = $sqliteDb->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        $requiredTables = ['clube', 'jogador', 'elenco'];
        foreach ($requiredTables as $req) {
            if (!in_array($req, $sqliteTables, true)) {
                throw new RuntimeException("O arquivo .db3 não contém a tabela obrigatória '{$req}'.");
            }
        }

        // Carregar dados do país alvo
        $stmtPais = $this->mysqlDb->prepare("SELECT id, nome, sigla, bandeira, dono FROM paises WHERE id = ? LIMIT 1");
        $stmtPais->execute([$idPais]);
        $paisInfo = $stmtPais->fetch(PDO::FETCH_ASSOC);
        if (!$paisInfo) {
            throw new InvalidArgumentException("País selecionado (ID {$idPais}) não foi encontrado no sistema.");
        }

        // ==========================================
        // PRÉ-CARREGAMENTO EM MEMÓRIA (O(1) HASHMAPS)
        // ==========================================

        // 1. Estádios existentes no país
        $existingEstByName = [];
        $stmtEAll = $this->mysqlDb->prepare("SELECT ID, Nome, Capacidade FROM estadio WHERE Pais = ?");
        $stmtEAll->execute([$idPais]);
        while ($eRow = $stmtEAll->fetch(PDO::FETCH_ASSOC)) {
            $normE = $this->normalizeName($eRow['Nome']);
            $existingEstByName[$normE] = $eRow;
        }

        // 2. Técnicos existentes no país e gênero
        $existingTecByName = [];
        $stmtTAll = $this->mysqlDb->prepare("SELECT ID, Nome, Nascimento, Nivel FROM tecnico WHERE Pais = ? AND Sexo = ?");
        $stmtTAll->execute([$idPais, $sexo]);
        while ($tRow = $stmtTAll->fetch(PDO::FETCH_ASSOC)) {
            $normT = $this->normalizeName($tRow['Nome']);
            $existingTecByName[$normT] = $tRow;
        }

        // 3. Clubes existentes no país e gênero (mapeando também seleções por status)
        $existingClubByExtId = [];
        $existingClubByName = [];
        $existingSelecaoByStatus = [];
        $stmtCAll = $this->mysqlDb->prepare("SELECT ID, Nome, TresLetras, externalID, Estadio, Escudo, Uniforme1, Uniforme2, liga, status FROM clube WHERE Pais = ? AND Sexo = ?");
        $stmtCAll->execute([$idPais, $sexo]);
        while ($cRow = $stmtCAll->fetch(PDO::FETCH_ASSOC)) {
            $cStat = (int)($cRow['status'] ?? 0);
            if ($cStat > 0) {
                $existingSelecaoByStatus[$cStat] = $cRow;
            }
            if (!empty($cRow['externalID'])) {
                $existingClubByExtId[(int)$cRow['externalID']] = $cRow;
            }
            $normC = $this->normalizeName($cRow['Nome']);
            $existingClubByName[$normC] = $cRow;
        }

        // 4. Jogadores existentes vinculados ao país ou clubes do país no gênero
        $existingJogByExtId = [];
        $existingJogByName = [];
        $stmtJAll = $this->mysqlDb->prepare("
            SELECT j.ID, j.Nome, j.externalID, j.Pais as jogPais, j.valor as jogValor,
                   c.ID as clubeID, c.Nome as clubeNome, c.TresLetras as clubeSigla, c.Pais as clubePais,
                   p.dono as clubeDono
            FROM jogador j
            LEFT JOIN contratos_jogador cj ON j.ID = cj.jogador AND cj.tipoContrato = 0
            LEFT JOIN clube c ON cj.clube = c.ID
            LEFT JOIN paises p ON c.Pais = p.id
            WHERE j.Sexo = ? AND ( (c.ID IS NOT NULL AND c.Pais = ?) OR (j.Pais = ?) )
        ");
        $stmtJAll->execute([$sexo, $idPais, $idPais]);
        while ($jRow = $stmtJAll->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($jRow['externalID'])) {
                $existingJogByExtId[(int)$jRow['externalID']] = $jRow;
            }
            $normJ = $this->normalizeName($jRow['Nome']);
            $existingJogByName[$normJ] = $jRow;
        }

        $preview = [
            'success' => true,
            'pais' => $paisInfo,
            'sexo' => (int)$sexo,
            'stats' => [
                'total_clubes' => 0,
                'clubes_criados' => 0,
                'clubes_atualizados' => 0,
                'total_selecoes' => 0,
                'selecoes_criadas' => 0,
                'selecoes_atualizadas' => 0,
                'total_jogadores' => 0,
                'jogadores_criados' => 0,
                'jogadores_atualizados' => 0,
                'jogadores_propostas' => 0,
                'total_tecnicos' => 0,
                'tecnicos_criados' => 0,
                'tecnicos_atualizados' => 0,
                'total_estadios' => 0,
                'estadios_criados' => 0,
                'estadios_atualizados' => 0,
            ],
            'clubes' => [],
            'logs' => [],
        ];

        // 1. Mapear Estádios
        $estadiosDb3 = [];
        if (in_array('estadio', $sqliteTables, true)) {
            $stmtEst = $sqliteDb->query("SELECT * FROM estadio");
            while ($eRow = $stmtEst->fetch()) {
                $eId = (int)$eRow['ID'];
                $eNome = trim((string)$eRow['Nome']);
                $normE = $this->normalizeName($eNome);
                $foundE = $existingEstByName[$normE] ?? null;

                $eAction = $foundE ? 'matched' : 'created';
                if ($foundE) {
                    $preview['stats']['estadios_atualizados']++;
                } else {
                    $preview['stats']['estadios_criados']++;
                }
                $preview['stats']['total_estadios']++;

                $estadiosDb3[$eId] = [
                    'db3_id' => $eId,
                    'nome' => $eNome,
                    'capacidade' => (int)($eRow['Capacidade'] ?? 10000),
                    'action' => $eAction,
                    'matched_id' => $foundE ? (int)$foundE['ID'] : null,
                    'matched_nome' => $foundE ? $foundE['Nome'] : null,
                ];
            }
        }

        // 2. Mapear Técnicos
        $tecnicosDb3 = [];
        if (in_array('tecnico', $sqliteTables, true)) {
            $stmtTec = $sqliteDb->query("SELECT * FROM tecnico");
            while ($tRow = $stmtTec->fetch()) {
                $tId = (int)$tRow['ID'];
                $tNome = trim(preg_replace('/\s*\[.*?\]\s*$/', '', (string)$tRow['Nome']));
                $normT = $this->normalizeName($tNome);
                $foundT = $existingTecByName[$normT] ?? null;

                $tAction = $foundT ? 'matched' : 'created';
                if ($foundT) {
                    $preview['stats']['tecnicos_atualizados']++;
                } else {
                    $preview['stats']['tecnicos_criados']++;
                }
                $preview['stats']['total_tecnicos']++;

                $tecnicosDb3[$tId] = [
                    'db3_id' => $tId,
                    'nome' => $tNome,
                    'idade' => (int)($tRow['Idade'] ?? 45),
                    'nivel' => (int)($tRow['Nivel'] ?? 5),
                    'action' => $tAction,
                    'matched_id' => $foundT ? (int)$foundT['ID'] : null,
                    'matched_nome' => $foundT ? $foundT['Nome'] : null,
                ];
            }
        }

        // 3. Mapear Posições, Nacionalidades e Escalações dos Jogadores
        $posicoesDb3 = [];
        if (in_array('posicaojogador', $sqliteTables, true)) {
            $stmtPos = $sqliteDb->query("SELECT * FROM posicaojogador");
            while ($pRow = $stmtPos->fetch()) {
                $jId = (int)$pRow['Jogador'];
                $isG = ((int)($pRow['G'] ?? 0) === 1);
                $activePositions = [];
                if ($isG) {
                    $activePositions[] = 'G';
                }
                $posKeys = ['LD', 'LE', 'Z', 'AD', 'AE', 'V', 'MD', 'ME', 'MC', 'PD', 'PE', 'MA', 'Am', 'Aa'];
                foreach ($posKeys as $pk) {
                    if ((int)($pRow[$pk] ?? 0) === 1) {
                        $activePositions[] = $pk;
                    }
                }
                $posicoesDb3[$jId] = [
                    'is_goleiro' => $isG,
                    'positions' => $activePositions,
                ];
            }
        }

        $nacionalidadesDb3 = [];
        if (in_array('nacionalidades', $sqliteTables, true)) {
            $stmtNac = $sqliteDb->query("SELECT * FROM nacionalidades");
            while ($nRow = $stmtNac->fetch()) {
                $nacionalidadesDb3[(int)$nRow['ID_Jogador']] = trim((string)$nRow['Nacionalidade']);
            }
        }

        $escalacoesDb3 = [];
        if (in_array('escalacao', $sqliteTables, true)) {
            $stmtEsc = $sqliteDb->query("SELECT * FROM escalacao");
            while ($escRow = $stmtEsc->fetch()) {
                $cId = (int)$escRow['Clube'];
                $cap = (int)($escRow['Capitao'] ?? 0);
                $pen1 = (int)($escRow['Penalti1'] ?? 0);
                $pen2 = (int)($escRow['Penalti2'] ?? 0);
                $pen3 = (int)($escRow['Penalti3'] ?? 0);

                $titulares = [];
                for ($k = 1; $k <= 11; $k++) {
                    $tJogId = (int)($escRow["Jogador{$k}"] ?? 0);
                    $tPos = trim((string)($escRow["Pos{$k}"] ?? ''));
                    if ($tJogId > 0) {
                        $titulares[$tJogId] = $tPos;
                    }
                }

                $escalacoesDb3[$cId] = [
                    'capitao' => $cap,
                    'penaltis' => [1 => $pen1, 2 => $pen2, 3 => $pen3],
                    'titulares' => $titulares,
                ];
            }
        }

        // 4. Mapear Clubes do SQLite
        $stmtClubes = $sqliteDb->query("SELECT * FROM clube ORDER BY ID ASC");
        $clubesDb3 = $stmtClubes->fetchAll();

        foreach ($clubesDb3 as $cRow) {
            $db3ClubId = (int)$cRow['ID'];
            $clubNome = trim((string)$cRow['Nome']);
            $clubSigla = strtoupper(substr(trim((string)($cRow['TresLetras'] ?? 'XXX')), 0, 3));
            $db3EstId = (int)($cRow['Estadio'] ?? 0);
            $normC = $this->normalizeName($clubNome);

            $cStatus = $this->detectSelecaoStatus($clubNome, $paisInfo['nome']);
            $isSelecao = ($cStatus > 0);

            // Busca por Seleção (status) ou por externalID / Nome via HashMap O(1)
            if ($isSelecao) {
                $existingClub = $existingSelecaoByStatus[$cStatus] ?? ($existingClubByExtId[$db3ClubId] ?? ($existingClubByName[$normC] ?? null));
            } else {
                $existingClub = $existingClubByExtId[$db3ClubId] ?? ($existingClubByName[$normC] ?? null);
                if ($existingClub && (int)($existingClub['status'] ?? 0) > 0) {
                    $existingClub = null;
                }
            }

            $clubAction = $existingClub ? 'matched' : 'created';
            if ($isSelecao) {
                if ($existingClub) {
                    $preview['stats']['selecoes_atualizadas']++;
                } else {
                    $preview['stats']['selecoes_criadas']++;
                }
                $preview['stats']['total_selecoes']++;
            } else {
                if ($existingClub) {
                    $preview['stats']['clubes_atualizados']++;
                } else {
                    $preview['stats']['clubes_criados']++;
                }
                $preview['stats']['total_clubes']++;
            }

            $preview['clubes'][$db3ClubId] = [
                'db3_id' => $db3ClubId,
                'nome' => $clubNome,
                'sigla' => $clubSigla,
                'is_selecao' => $isSelecao,
                'status' => $cStatus,
                'tipo_label' => $this->getSelecaoLabel($cStatus),
                'max_torcedores' => (int)($cRow['MaxTorcedores'] ?? 5000),
                'fidelidade' => (int)($cRow['Fidelidade'] ?? 5),
                'cores' => [
                    'u1c1' => (string)($cRow['Uni1Cor1'] ?? '000000000'),
                    'u1c2' => (string)($cRow['Uni1Cor2'] ?? '255255255'),
                    'u1c3' => (string)($cRow['Uni1Cor3'] ?? '000000000'),
                    'u2c1' => (string)($cRow['Uni2Cor1'] ?? '255255255'),
                    'u2c2' => (string)($cRow['Uni2Cor2'] ?? '000000000'),
                    'u2c3' => (string)($cRow['Uni2Cor3'] ?? '255255255'),
                ],
                'estadio' => $estadiosDb3[$db3EstId] ?? null,
                'tecnico' => null,
                'action' => $clubAction,
                'matched_id' => $existingClub ? (int)$existingClub['ID'] : null,
                'matched_nome' => $existingClub ? $existingClub['Nome'] : null,
                'jogadores' => [],
                'stats_jogadores' => [
                    'total' => 0,
                    'criados' => 0,
                    'atualizados' => 0,
                    'convocados' => 0,
                    'propostas' => 0,
                ],
            ];
        }

        // 5. Pré-mapear jogadores de clubes regulares do SQLite para correlacionar com seleções
        $playerClubDb3Map = [];
        $stmtElencosPre = $sqliteDb->query("SELECT * FROM elenco");
        while ($elRow = $stmtElencosPre->fetch()) {
            $db3CId = (int)$elRow['Clube'];
            $cInf = $preview['clubes'][$db3CId] ?? null;
            if ($cInf && empty($cInf['is_selecao'])) {
                for ($k = 1; $k <= 23; $k++) {
                    $jId = (int)($elRow["Jogador{$k}"] ?? 0);
                    if ($jId > 0) {
                        $playerClubDb3Map[$jId] = [
                            'db3_clube_id' => $db3CId,
                            'nome_clube' => $cInf['nome'],
                            'sigla_clube' => $cInf['sigla'],
                        ];
                    }
                }
            }
        }

        // Carregar dados de todos os jogadores da base SQLite
        $stmtAllJog = $sqliteDb->query("SELECT * FROM jogador");
        $jogadoresDataDb3 = [];
        while ($jRow = $stmtAllJog->fetch()) {
            $jogadoresDataDb3[(int)$jRow['ID']] = $jRow;
        }

        // Mapear jogadores únicos presentes nos elencos da importação para estatísticas globais precisas
        $uniquePlayersInImport = [];
        $stmtElencosPreCount = $sqliteDb->query("SELECT * FROM elenco");
        while ($elRow = $stmtElencosPreCount->fetch()) {
            $db3CId = (int)$elRow['Clube'];
            if (isset($preview['clubes'][$db3CId])) {
                for ($k = 1; $k <= 23; $k++) {
                    $jId = (int)($elRow["Jogador{$k}"] ?? 0);
                    if ($jId > 0 && isset($jogadoresDataDb3[$jId])) {
                        $uniquePlayersInImport[$jId] = true;
                    }
                }
            }
        }

        foreach ($uniquePlayersInImport as $jId => $_) {
            $jData = $jogadoresDataDb3[$jId];
            $normJ = $this->normalizeName($jData['Nome']);
            $existingJog = $existingJogByExtId[$jId] ?? ($existingJogByName[$normJ] ?? null);
            if ($existingJog) {
                $preview['stats']['jogadores_atualizados']++;
            } else {
                $preview['stats']['jogadores_criados']++;
            }
            $preview['stats']['total_jogadores']++;
        }

        $stmtElencos = $sqliteDb->query("SELECT * FROM elenco");
        while ($elRow = $stmtElencos->fetch()) {
            $db3CId = (int)$elRow['Clube'];
            if (!isset($preview['clubes'][$db3CId])) {
                continue;
            }

            // Associar Técnico do elenco ao preview do clube
            $db3TId = (int)($elRow['Tecnico'] ?? 0);
            if ($db3TId > 0 && isset($tecnicosDb3[$db3TId])) {
                $preview['clubes'][$db3CId]['tecnico'] = $tecnicosDb3[$db3TId];
            }

            $escInfo = $escalacoesDb3[$db3CId] ?? null;
            $isSelecaoClub = !empty($preview['clubes'][$db3CId]['is_selecao']);

            for ($k = 1; $k <= 23; $k++) {
                $jId = (int)($elRow["Jogador{$k}"] ?? 0);
                if ($jId <= 0 || !isset($jogadoresDataDb3[$jId])) {
                    continue;
                }

                $jData = $jogadoresDataDb3[$jId];
                $pNome = trim((string)$jData['Nome']);
                $pIdade = (int)($jData['Idade'] ?? 22);
                $pNivel = (int)($jData['Nivel'] ?? 50);
                $normJ = $this->normalizeName($pNome);

                // Busca jogador existente no MySQL via HashMap O(1)
                $existingJog = $existingJogByExtId[$jId] ?? ($existingJogByName[$normJ] ?? null);

                $isConvocado = false;
                $origemClube = null;
                $origemSigla = null;

                if ($isSelecaoClub) {
                    // Para seleções: verificar se o atleta tem clube regular na DB3 ou no MySQL
                    $db3ClubOfPlayer = $playerClubDb3Map[$jId] ?? null;
                    $mysqlClubOfPlayer = (!empty($existingJog['clubeNome'])) ? $existingJog['clubeNome'] : null;

                    if ($db3ClubOfPlayer) {
                        $jogAction = 'convocado';
                        $isConvocado = true;
                        $origemClube = $db3ClubOfPlayer['nome_clube'];
                        $origemSigla = $db3ClubOfPlayer['sigla_clube'];
                        $preview['clubes'][$db3CId]['stats_jogadores']['convocados']++;
                    } elseif ($mysqlClubOfPlayer) {
                        $jogAction = 'convocado';
                        $isConvocado = true;
                        $origemClube = $mysqlClubOfPlayer;
                        $origemSigla = $existingJog['clubeSigla'] ?? '';
                        $preview['clubes'][$db3CId]['stats_jogadores']['convocados']++;
                    } else {
                        if ($existingJog) {
                            $jogAction = 'matched';
                            $preview['clubes'][$db3CId]['stats_jogadores']['atualizados']++;
                        } else {
                            $jogAction = 'created';
                            $preview['clubes'][$db3CId]['stats_jogadores']['criados']++;
                        }
                    }
                } else {
                    // Para clubes regulares
                    if ($existingJog) {
                        $clubeOrigemId = (int)($existingJog['clubeID'] ?? 0);
                        $clubeDono = (int)($existingJog['clubeDono'] ?? 0);
                        $clubePais = (int)($existingJog['clubePais'] ?? 0);
                        $isOutroUsuario = ($clubeOrigemId > 0 && $clubeDono !== (int)$userId && $clubePais !== (int)$idPais);

                        if ($isOutroUsuario) {
                            $jogAction = 'proposta';
                            $origemClube = $existingJog['clubeNome'] ?? "Clube #{$clubeOrigemId}";
                            $origemSigla = $existingJog['clubeSigla'] ?? '';
                            $preview['clubes'][$db3CId]['stats_jogadores']['propostas']++;
                            $preview['stats']['jogadores_propostas']++;
                        } else {
                            $jogAction = 'matched';
                            $preview['clubes'][$db3CId]['stats_jogadores']['atualizados']++;
                        }
                    } else {
                        $jogAction = 'created';
                        $preview['clubes'][$db3CId]['stats_jogadores']['criados']++;
                    }
                }

                $preview['clubes'][$db3CId]['stats_jogadores']['total']++;

                // Função no time (Titular / Capitão / Pênalti)
                $isTitular = false;
                $posBase = '';
                $isCap = false;
                $penNum = 0;

                if ($escInfo) {
                    if (isset($escInfo['titulares'][$jId])) {
                        $isTitular = true;
                        $posBase = $escInfo['titulares'][$jId];
                    }
                    if ($escInfo['capitao'] === $jId) {
                        $isCap = true;
                    }
                    foreach ($escInfo['penaltis'] as $pIdx => $pJog) {
                        if ($pJog === $jId) {
                            $penNum = $pIdx;
                            break;
                        }
                    }
                }

                $pPos = $posicoesDb3[$jId] ?? ['is_goleiro' => false, 'positions' => []];
                $pNac = $nacionalidadesDb3[$jId] ?? '-';

                $preview['clubes'][$db3CId]['jogadores'][] = [
                    'db3_id' => $jId,
                    'nome' => $pNome,
                    'idade' => $pIdade,
                    'nivel' => $pNivel,
                    'is_goleiro' => $pPos['is_goleiro'],
                    'posicoes' => $pPos['positions'],
                    'nacionalidade' => str_replace('.png', '', $pNac),
                    'titular' => $isTitular,
                    'posicao_base' => $posBase,
                    'capitao' => $isCap,
                    'penalti' => $penNum,
                    'action' => $jogAction,
                    'is_convocado' => $isConvocado,
                    'origem_clube' => $origemClube,
                    'origem_sigla' => $origemSigla,
                    'matched_id' => $existingJog ? (int)$existingJog['ID'] : null,
                    'matched_nome' => $existingJog ? $existingJog['Nome'] : null,
                ];
            }
        }

        return $preview;
    }

    /**
     * Processa um arquivo SQLite .db3 completo para um país e gênero especificados.
     *
     * @param string $sqliteFilePath Caminho absoluto do arquivo .db3
     * @param int $idPais ID do país no MySQL
     * @param int $sexo 0 para Masculino, 1 para Feminino
     * @param int $userId ID do usuário administrador executando a ação
     * @return array Resumo completo da importação
     * @throws Exception Em caso de erro irrecuperável
     */
    public function processFile($sqliteFilePath, $idPais, $sexo, $userId) {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        if (!file_exists($sqliteFilePath)) {
            throw new InvalidArgumentException("Arquivo DB3 não encontrado no caminho informado.");
        }

        // Conectar ao SQLite .db3
        try {
            $sqliteDb = new PDO('sqlite:' . $sqliteFilePath);
            $sqliteDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sqliteDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Não foi possível abrir o arquivo SQLite .db3: " . $e->getMessage());
        }

        // Verificar tabelas essenciais no SQLite
        $sqliteTables = $sqliteDb->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        $requiredTables = ['clube', 'jogador', 'elenco'];
        foreach ($requiredTables as $req) {
            if (!in_array($req, $sqliteTables, true)) {
                throw new RuntimeException("O arquivo .db3 não contém a tabela obrigatória '{$req}'.");
            }
        }

        // Carregar dados do país alvo
        $stmtPais = $this->mysqlDb->prepare("SELECT id, nome, sigla, bandeira, dono FROM paises WHERE id = ? LIMIT 1");
        $stmtPais->execute([$idPais]);
        $paisInfo = $stmtPais->fetch(PDO::FETCH_ASSOC);
        if (!$paisInfo) {
            throw new InvalidArgumentException("País selecionado (ID {$idPais}) não foi encontrado no sistema.");
        }

        // ==========================================
        // PRÉ-CARREGAMENTO EM MEMÓRIA (O(1) HASHMAPS)
        // ==========================================

        // 1. Climas existentes no país
        $existingClimaByName = [];
        $stmtClimaAll = $this->mysqlDb->prepare("SELECT ID, Nome FROM clima WHERE Pais = ?");
        $stmtClimaAll->execute([$idPais]);
        while ($cRow = $stmtClimaAll->fetch(PDO::FETCH_ASSOC)) {
            $normClima = $this->normalizeName($cRow['Nome']);
            $existingClimaByName[$normClima] = (int)$cRow['ID'];
        }

        // 2. Estádios existentes no país
        $existingEstByName = [];
        $stmtEAll = $this->mysqlDb->prepare("SELECT ID, Nome, Capacidade FROM estadio WHERE Pais = ?");
        $stmtEAll->execute([$idPais]);
        while ($eRow = $stmtEAll->fetch(PDO::FETCH_ASSOC)) {
            $normE = $this->normalizeName($eRow['Nome']);
            $existingEstByName[$normE] = (int)$eRow['ID'];
        }

        // 3. Técnicos existentes no país e gênero
        $existingTecByName = [];
        $stmtTAll = $this->mysqlDb->prepare("SELECT ID, Nome, Nascimento, Nivel FROM tecnico WHERE Pais = ? AND Sexo = ?");
        $stmtTAll->execute([$idPais, $sexo]);
        while ($tRow = $stmtTAll->fetch(PDO::FETCH_ASSOC)) {
            $normT = $this->normalizeName($tRow['Nome']);
            $existingTecByName[$normT] = (int)$tRow['ID'];
        }

        // 4. Clubes existentes no país e gênero
        $existingClubByExtId = [];
        $existingClubByName = [];
        $existingSelecaoByStatus = [];
        $stmtCAll = $this->mysqlDb->prepare("SELECT ID, Nome, TresLetras, externalID, Estadio, Escudo, Uniforme1, Uniforme2, liga, status FROM clube WHERE Pais = ? AND Sexo = ?");
        $stmtCAll->execute([$idPais, $sexo]);
        while ($cRow = $stmtCAll->fetch(PDO::FETCH_ASSOC)) {
            $cStat = (int)($cRow['status'] ?? 0);
            if ($cStat > 0) {
                $existingSelecaoByStatus[$cStat] = $cRow;
            }
            if (!empty($cRow['externalID'])) {
                $existingClubByExtId[(int)$cRow['externalID']] = $cRow;
            }
            $normC = $this->normalizeName($cRow['Nome']);
            $existingClubByName[$normC] = $cRow;
        }

        // 5. Jogadores existentes vinculados ao país ou clubes do país no gênero
        $existingJogByExtId = [];
        $existingJogByName = [];
        $stmtJAll = $this->mysqlDb->prepare("
            SELECT j.ID, j.Nome, j.externalID, j.Pais as jogPais, j.valor as jogValor,
                   c.ID as clubeID, c.Nome as clubeNome, c.TresLetras as clubeSigla, c.Pais as clubePais,
                   p.dono as clubeDono
            FROM jogador j
            LEFT JOIN contratos_jogador cj ON j.ID = cj.jogador AND cj.tipoContrato = 0
            LEFT JOIN clube c ON cj.clube = c.ID
            LEFT JOIN paises p ON c.Pais = p.id
            WHERE j.Sexo = ? AND ( (c.ID IS NOT NULL AND c.Pais = ?) OR (j.Pais = ?) )
        ");
        $stmtJAll->execute([$sexo, $idPais, $idPais]);
        while ($jRow = $stmtJAll->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($jRow['externalID'])) {
                $existingJogByExtId[(int)$jRow['externalID']] = $jRow;
            }
            $normJ = $this->normalizeName($jRow['Nome']);
            $existingJogByName[$normJ] = $jRow;
        }

        // 6. Técnicos atualmente titulares nos clubes do país
        $existingClubTecTitular = [];
        $stmtClubTec = $this->mysqlDb->prepare("
            SELECT ct.clube, ct.tecnico, ct.tipoContrato
            FROM contratos_tecnico ct 
            INNER JOIN clube c ON ct.clube = c.ID 
            WHERE c.Pais = ? AND c.Sexo = ?
        ");
        $stmtClubTec->execute([$idPais, $sexo]);
        while ($ctRow = $stmtClubTec->fetch(PDO::FETCH_ASSOC)) {
            $existingClubTecTitular[(int)$ctRow['clube'] . '_' . (int)$ctRow['tipoContrato']] = (int)$ctRow['tecnico'];
        }

        $report = [
            'success' => true,
            'pais' => $paisInfo,
            'sexo' => $sexo,
            'stats' => [
                'total_clubes' => 0,
                'clubes_criados' => 0,
                'clubes_atualizados' => 0,
                'total_selecoes' => 0,
                'selecoes_criadas' => 0,
                'selecoes_atualizadas' => 0,
                'total_jogadores' => 0,
                'jogadores_criados' => 0,
                'jogadores_atualizados' => 0,
                'propostas_enviadas' => 0,
                'total_tecnicos' => 0,
                'tecnicos_criados' => 0,
                'tecnicos_atualizados' => 0,
                'total_estadios' => 0,
                'estadios_criados' => 0,
                'estadios_atualizados' => 0,
            ],
            'clubes_detalhes' => [],
            'jogadores_detalhes' => [],
            'logs' => [],
        ];

        // 1. Processar Climas e Estádios (se presentes no SQLite)
        $mapClimaDb3ToMySQL = [];
        if (in_array('clima', $sqliteTables, true)) {
            $stmtClimas = $sqliteDb->query("SELECT * FROM clima");
            while ($climaRow = $stmtClimas->fetch()) {
                $cId = (int)$climaRow['ID'];
                $cNome = trim((string)$climaRow['Nome']);
                $normClima = $this->normalizeName($cNome);
                
                $mysqlClimaId = $existingClimaByName[$normClima] ?? 0;

                if (!$mysqlClimaId) {
                    $this->climaObj->nome = $cNome;
                    $this->climaObj->tempVerao = (string)($climaRow['TempVerao'] ?? 'Normal');
                    $this->climaObj->estiloVerao = (string)($climaRow['EstiloVerao'] ?? 'Normal');
                    $this->climaObj->tempOutono = (string)($climaRow['TempOutono'] ?? 'Normal');
                    $this->climaObj->estiloOutono = (string)($climaRow['EstiloOutono'] ?? 'Normal');
                    $this->climaObj->tempInverno = (string)($climaRow['TempInverno'] ?? 'Normal');
                    $this->climaObj->estiloInverno = (string)($climaRow['EstiloInverno'] ?? 'Normal');
                    $this->climaObj->tempPrimavera = (string)($climaRow['TempPrimavera'] ?? 'Normal');
                    $this->climaObj->estiloPrimavera = (string)($climaRow['EstiloPrimavera'] ?? 'Normal');
                    $this->climaObj->hemisferio = (string)($climaRow['Hemisferio'] ?? '0');
                    $this->climaObj->pais = $idPais;
                    $this->climaObj->externalID = $cId;
                    if ($this->climaObj->create()) {
                        $mysqlClimaId = (int)$this->mysqlDb->lastInsertId();
                        $existingClimaByName[$normClima] = $mysqlClimaId;
                    }
                } else {
                    $this->mysqlDb->prepare("UPDATE clima SET externalID = ? WHERE ID = ?")->execute([$cId, $mysqlClimaId]);
                }
                if ($mysqlClimaId) {
                    $mapClimaDb3ToMySQL[$cId] = (int)$mysqlClimaId;
                }
            }
        }

        $mapEstadioDb3ToMySQL = [];
        if (in_array('estadio', $sqliteTables, true)) {
            $stmtEstadios = $sqliteDb->query("SELECT * FROM estadio");
            while ($estRow = $stmtEstadios->fetch()) {
                $eId = (int)$estRow['ID'];
                $eNome = trim((string)$estRow['Nome']);
                $normE = $this->normalizeName($eNome);
                $eCap = (int)($estRow['Capacidade'] ?? 10000);
                $eClimaDb3 = (int)($estRow['Clima'] ?? 0);
                $mysqlClima = $mapClimaDb3ToMySQL[$eClimaDb3] ?? 0;
                $eAlt = (int)($estRow['Altitude'] ?? 0);
                $eCald = (int)($estRow['Caldeirao'] ?? 0);

                $mysqlEstId = $existingEstByName[$normE] ?? 0;

                if (!$mysqlEstId) {
                    $this->estadioObj->nome = $eNome;
                    $this->estadioObj->capacidade = $eCap;
                    $this->estadioObj->clima = $mysqlClima;
                    $this->estadioObj->altitude = $eAlt;
                    $this->estadioObj->caldeirao = $eCald;
                    $this->estadioObj->pais = $idPais;
                    $this->estadioObj->foto = null;
                    $this->estadioObj->externalID = $eId;
                    if ($this->estadioObj->create()) {
                        $mysqlEstId = (int)$this->mysqlDb->lastInsertId();
                        $existingEstByName[$normE] = $mysqlEstId;
                        $report['stats']['estadios_criados']++;
                        $report['stats']['total_estadios']++;
                    }
                } else {
                    $this->estadioObj->alterar($mysqlEstId, $eNome, $eCap, $idPais, $eAlt, $eCald, $mysqlClima, null, $eId);
                    $report['stats']['estadios_atualizados']++;
                    $report['stats']['total_estadios']++;
                }
                if ($mysqlEstId) {
                    $mapEstadioDb3ToMySQL[$eId] = (int)$mysqlEstId;
                }
            }
        }

        // 2. Processar Clubes e Seleções
        $mapClubeDb3ToMySQL = [];
        $stmtClubes = $sqliteDb->query("SELECT * FROM clube ORDER BY ID ASC");
        $clubesDb3 = $stmtClubes->fetchAll();

        foreach ($clubesDb3 as $cRow) {
            $db3ClubId = (int)$cRow['ID'];
            $clubNome = trim((string)$cRow['Nome']);
            $normC = $this->normalizeName($clubNome);
            $clubSigla = strtoupper(substr(trim((string)($cRow['TresLetras'] ?? 'XXX')), 0, 3));
            $maxTorcedores = (int)($cRow['MaxTorcedores'] ?? 5000);
            $fidelidade = (int)($cRow['Fidelidade'] ?? 5);

            $u1c1 = (string)($cRow['Uni1Cor1'] ?? '000000000');
            $u1c2 = (string)($cRow['Uni1Cor2'] ?? '255255255');
            $u1c3 = (string)($cRow['Uni1Cor3'] ?? '000000000');
            $u2c1 = (string)($cRow['Uni2Cor1'] ?? '255255255');
            $u2c2 = (string)($cRow['Uni2Cor2'] ?? '000000000');
            $u2c3 = (string)($cRow['Uni2Cor3'] ?? '255255255');

            $db3EstId = (int)($cRow['Estadio'] ?? 0);
            $estadioId = $mapEstadioDb3ToMySQL[$db3EstId] ?? 0;

            $cStatus = $this->detectSelecaoStatus($clubNome, $paisInfo['nome']);
            $isSelecao = ($cStatus > 0);

            // Busca de clube ou seleção existente via HashMap O(1)
            if ($isSelecao) {
                $existingClub = $existingSelecaoByStatus[$cStatus] ?? ($existingClubByExtId[$db3ClubId] ?? ($existingClubByName[$normC] ?? null));
            } else {
                $existingClub = $existingClubByExtId[$db3ClubId] ?? ($existingClubByName[$normC] ?? null);
                if ($existingClub && (int)($existingClub['status'] ?? 0) > 0) {
                    $existingClub = null;
                }
            }

            $action = 'created';
            $mysqlClubId = 0;

            if ($existingClub) {
                $mysqlClubId = (int)$existingClub['ID'];
                $action = 'updated';

                $this->timeObj->id = $mysqlClubId;
                $this->timeObj->nome = $clubNome;
                $this->timeObj->sigla = $clubSigla;
                $this->timeObj->estadio = ($estadioId > 0) ? $estadioId : ($existingClub['Estadio'] ?: 0);
                $this->timeObj->uniforme1cor1 = $u1c1;
                $this->timeObj->uniforme1cor2 = $u1c2;
                $this->timeObj->uniforme1cor3 = $u1c3;
                $this->timeObj->uniforme2cor1 = $u2c1;
                $this->timeObj->uniforme2cor2 = $u2c2;
                $this->timeObj->uniforme2cor3 = $u2c3;
                $this->timeObj->maxTorcedores = $maxTorcedores;
                $this->timeObj->fidelidade = $fidelidade;
                $this->timeObj->pais = $idPais;
                $this->timeObj->liga = $isSelecao ? -1 : ($existingClub['liga'] ?? 0);
                $this->timeObj->sexo = $sexo;
                $this->timeObj->status = $cStatus;
                $this->timeObj->externalID = $db3ClubId;

                $this->timeObj->alterar();
                if ($isSelecao) {
                    $report['stats']['selecoes_atualizadas']++;
                } else {
                    $report['stats']['clubes_atualizados']++;
                }
                $existingSiglasInPais[$clubSigla] = $mysqlClubId;
            } else {
                $this->timeObj->nome = $clubNome;
                $this->timeObj->sigla = $clubSigla;
                $this->timeObj->estadio = $estadioId;
                $this->timeObj->escudo = $this->timeObj->escudoPadrao();
                $this->timeObj->uniforme1 = $this->timeObj->uniforme1Padrao();
                $this->timeObj->uniforme2 = $this->timeObj->uniforme2Padrao();
                $this->timeObj->uniforme1cor1 = $u1c1;
                $this->timeObj->uniforme1cor2 = $u1c2;
                $this->timeObj->uniforme1cor3 = $u1c3;
                $this->timeObj->uniforme2cor1 = $u2c1;
                $this->timeObj->uniforme2cor2 = $u2c2;
                $this->timeObj->uniforme2cor3 = $u2c3;
                $this->timeObj->maxTorcedores = $maxTorcedores;
                $this->timeObj->fidelidade = $fidelidade;
                $this->timeObj->pais = $idPais;
                $this->timeObj->liga = $isSelecao ? -1 : 0;
                $this->timeObj->sexo = $sexo;
                $this->timeObj->status = $cStatus;
                $this->timeObj->externalID = $db3ClubId;

                if ($this->timeObj->create()) {
                    $mysqlClubId = (int)$this->mysqlDb->lastInsertId();
                    if ($isSelecao) {
                        $report['stats']['selecoes_criadas']++;
                    } else {
                        $report['stats']['clubes_criados']++;
                    }

                    // Atualiza cache em memória
                    $newClubData = [
                        'ID' => $mysqlClubId,
                        'Nome' => $clubNome,
                        'TresLetras' => $this->timeObj->sigla,
                        'externalID' => $db3ClubId,
                        'Estadio' => $estadioId,
                        'Escudo' => $this->timeObj->escudo,
                        'Uniforme1' => $this->timeObj->uniforme1,
                        'Uniforme2' => $this->timeObj->uniforme2,
                        'liga' => $isSelecao ? -1 : 0,
                        'status' => $cStatus,
                    ];
                    $existingClubByExtId[$db3ClubId] = $newClubData;
                    $existingClubByName[$normC] = $newClubData;
                    if ($isSelecao) {
                        $existingSelecaoByStatus[$cStatus] = $newClubData;
                    }
                    $existingSiglasInPais[$this->timeObj->sigla] = $mysqlClubId;
                    $clubSigla = $this->timeObj->sigla;
                } else {
                    $errDesc = !empty($this->timeObj->ultimo_erro) ? " ({$this->timeObj->ultimo_erro})" : "";
                    $report['logs'][] = "Aviso: Falha ao inserir clube '{$clubNome}' (db3 ID: {$db3ClubId}){$errDesc}.";
                }
            }

            if ($mysqlClubId > 0) {
                $mapClubeDb3ToMySQL[$db3ClubId] = [
                    'id' => $mysqlClubId,
                    'status' => $cStatus,
                    'is_selecao' => $isSelecao,
                    'nome' => $clubNome,
                ];
                if ($isSelecao) {
                    $report['stats']['total_selecoes']++;
                } else {
                    $report['stats']['total_clubes']++;
                }
                $report['clubes_detalhes'][$db3ClubId] = [
                    'db3_id' => $db3ClubId,
                    'mysql_id' => $mysqlClubId,
                    'nome' => $clubNome,
                    'sigla' => $clubSigla,
                    'is_selecao' => $isSelecao,
                    'status' => $cStatus,
                    'tipo_label' => $this->getSelecaoLabel($cStatus),
                    'action' => $action,
                    'jogadores_count' => 0,
                ];
            }
        }

        // 3. Processar Técnicos (se presentes no SQLite)
        $mapTecnicoDb3ToMySQL = [];
        if (in_array('tecnico', $sqliteTables, true)) {
            $stmtTecDb3 = $sqliteDb->query("SELECT * FROM tecnico");
            while ($tRow = $stmtTecDb3->fetch()) {
                $db3TId = (int)$tRow['ID'];
                $tNome = trim(preg_replace('/\s*\[.*?\]\s*$/', '', (string)$tRow['Nome']));
                $normT = $this->normalizeName($tNome);
                $tIdade = (int)($tRow['Idade'] ?? 45);
                $tNivel = (int)($tRow['Nivel'] ?? 5);
                $tMentalidade = (int)($tRow['Mentalidade'] ?? 5);
                $tEstilo = (int)($tRow['Estilo'] ?? 3);

                $foundTec = $existingTecByName[$normT] ?? 0;
                $mysqlTecId = 0;

                if ($foundTec > 0) {
                    $mysqlTecId = (int)$foundTec;
                    $this->tecnicoObj->id = $mysqlTecId;
                    $this->tecnicoObj->nome = $tNome;
                    $this->tecnicoObj->nascimento = $tIdade;
                    $this->tecnicoObj->nivel = $tNivel;
                    $this->tecnicoObj->mentalidade = $tMentalidade;
                    $this->tecnicoObj->estilo = $tEstilo;
                    $this->tecnicoObj->pais = $idPais;
                    $this->tecnicoObj->sexo = $sexo;
                    $this->tecnicoObj->externalID = $db3TId;
                    $this->tecnicoObj->alterar($mysqlTecId, $tNome, $tIdade, $tNivel, $tMentalidade, $tEstilo, $idPais, $sexo, $db3TId);
                    $report['stats']['tecnicos_atualizados']++;
                    $report['stats']['total_tecnicos']++;
                } else {
                    $this->tecnicoObj->nome = $tNome;
                    $this->tecnicoObj->nascimento = $tIdade;
                    $this->tecnicoObj->nivel = $tNivel;
                    $this->tecnicoObj->mentalidade = $tMentalidade;
                    $this->tecnicoObj->estilo = $tEstilo;
                    $this->tecnicoObj->pais = $idPais;
                    $this->tecnicoObj->sexo = $sexo;
                    $this->tecnicoObj->externalID = $db3TId;
                    if ($this->tecnicoObj->create()) {
                        $mysqlTecId = (int)$this->tecnicoObj->id;
                        $existingTecByName[$normT] = $mysqlTecId;
                        $report['stats']['tecnicos_criados']++;
                        $report['stats']['total_tecnicos']++;
                    }
                }

                if ($mysqlTecId > 0) {
                    $mapTecnicoDb3ToMySQL[$db3TId] = $mysqlTecId;
                }
            }

            // Vincular técnicos aos seus respectivos clubes em elencos
            $stmtElencosTec = $sqliteDb->query("SELECT Clube, Tecnico FROM elenco WHERE Tecnico > 0");
            while ($elRow = $stmtElencosTec->fetch()) {
                $db3CId = (int)$elRow['Clube'];
                $db3TId = (int)$elRow['Tecnico'];
                $targetClubInfo = $mapClubeDb3ToMySQL[$db3CId] ?? null;
                if (!$targetClubInfo) continue;

                $mysqlClubId = (int)$targetClubInfo['id'];
                $targetClubStatus = (int)$targetClubInfo['status'];
                $mysqlTecId = $mapTecnicoDb3ToMySQL[$db3TId] ?? 0;

                if ($mysqlClubId > 0 && $mysqlTecId > 0) {
                    if ($targetClubStatus > 0) {
                        // Contrato de Seleção (tipoContrato = status)
                        $stmtTecSel = $this->mysqlDb->prepare("
                            INSERT INTO contratos_tecnico (tecnico, clube, modificadorNivel, prazo, tipoContrato, salario)
                            VALUES (?, ?, 0, 0, ?, 0)
                            ON DUPLICATE KEY UPDATE clube = VALUES(clube)
                        ");
                        $stmtTecSel->execute([$mysqlTecId, $mysqlClubId, $targetClubStatus]);
                    } else {
                        // Contrato de Clube Regular (tipoContrato = 0)
                        $this->tecnicoObj->transferir($mysqlTecId, $mysqlClubId);
                    }
                    $existingClubTecTitular[$mysqlClubId . '_' . $targetClubStatus] = $mysqlTecId;
                }
            }
        }

        // 4. Carregar Mapeamentos do SQLite para Jogadores
        // Posições
        $posicoesPorJogadorDb3 = [];
        if (in_array('posicaojogador', $sqliteTables, true)) {
            $stmtPos = $sqliteDb->query("SELECT * FROM posicaojogador");
            while ($pRow = $stmtPos->fetch()) {
                $jId = (int)$pRow['Jogador'];
                $g = (int)($pRow['G'] ?? 0);
                $ld = (int)($pRow['LD'] ?? 0);
                $le = (int)($pRow['LE'] ?? 0);
                $z = (int)($pRow['Z'] ?? 0);
                $ad = (int)($pRow['AD'] ?? 0);
                $ae = (int)($pRow['AE'] ?? 0);
                $v = (int)($pRow['V'] ?? 0);
                $md = (int)($pRow['MD'] ?? 0);
                $me = (int)($pRow['ME'] ?? 0);
                $mc = (int)($pRow['MC'] ?? 0);
                $pd = (int)($pRow['PD'] ?? 0);
                $pe = (int)($pRow['PE'] ?? 0);
                $ma = (int)($pRow['MA'] ?? 0);
                $am = (int)($pRow['Am'] ?? 0);
                $aa = (int)($pRow['Aa'] ?? 0);

                $strPos = "{$g}{$ld}{$le}{$z}{$ad}{$ae}{$v}{$md}{$me}{$mc}{$pd}{$pe}{$ma}{$am}{$aa}";
                $posicoesPorJogadorDb3[$jId] = [
                    'isGoleiro' => ($g === 1),
                    'stringPosicoes' => $strPos,
                ];
            }
        }

        // Atributos de Linha
        $atributosJogadorDb3 = [];
        if (in_array('atributosjogador', $sqliteTables, true)) {
            $stmtAttr = $sqliteDb->query("SELECT * FROM atributosjogador");
            while ($aRow = $stmtAttr->fetch()) {
                $atributosJogadorDb3[(int)$aRow['Jogador']] = $aRow;
            }
        }

        // Atributos de Goleiro
        $atributosGoleiroDb3 = [];
        if (in_array('atributosgoleiro', $sqliteTables, true)) {
            $stmtAttrG = $sqliteDb->query("SELECT * FROM atributosgoleiro");
            while ($agRow = $stmtAttrG->fetch()) {
                $atributosGoleiroDb3[(int)$agRow['Goleiro']] = $agRow;
            }
        }

        // Nacionalidades
        $nacionalidadesDb3 = [];
        if (in_array('nacionalidades', $sqliteTables, true)) {
            $stmtNac = $sqliteDb->query("SELECT * FROM nacionalidades");
            while ($nRow = $stmtNac->fetch()) {
                $nacionalidadesDb3[(int)$nRow['ID_Jogador']] = trim((string)$nRow['Nacionalidade']);
            }
        }

        // Escalações (Capitão, Pênaltis, Titulares e Posições)
        $escalacoesDb3 = [];
        if (in_array('escalacao', $sqliteTables, true)) {
            $stmtEsc = $sqliteDb->query("SELECT * FROM escalacao");
            while ($escRow = $stmtEsc->fetch()) {
                $cId = (int)$escRow['Clube'];
                $cap = (int)($escRow['Capitao'] ?? 0);
                $pen1 = (int)($escRow['Penalti1'] ?? 0);
                $pen2 = (int)($escRow['Penalti2'] ?? 0);
                $pen3 = (int)($escRow['Penalti3'] ?? 0);

                $titulares = [];
                for ($k = 1; $k <= 11; $k++) {
                    $tJogId = (int)($escRow["Jogador{$k}"] ?? 0);
                    $tPos = trim((string)($escRow["Pos{$k}"] ?? ''));
                    if ($tJogId > 0) {
                        $titulares[$tJogId] = $tPos;
                    }
                }

                $escalacoesDb3[$cId] = [
                    'capitao' => $cap,
                    'penaltis' => [1 => $pen1, 2 => $pen2, 3 => $pen3],
                    'titulares' => $titulares,
                ];
            }
        }

        // Elencos: Mapear jogadores aos clubes do país
        $stmtElencos = $sqliteDb->query("SELECT * FROM elenco");
        $uniquePlayersToImport = []; // db3_player_id => true
        $elencosPorClubeDb3 = [];

        while ($elRow = $stmtElencos->fetch()) {
            $db3CId = (int)$elRow['Clube'];
            if (!isset($mapClubeDb3ToMySQL[$db3CId])) {
                continue; // Clube não pertence à importação
            }

            $elencosPorClubeDb3[$db3CId] = [];
            for ($k = 1; $k <= 23; $k++) {
                $jId = (int)($elRow["Jogador{$k}"] ?? 0);
                if ($jId > 0) {
                    $uniquePlayersToImport[$jId] = true;
                    $elencosPorClubeDb3[$db3CId][] = $jId;
                }
            }
        }

        // Carregar dados de todos os jogadores no SQLite
        $stmtAllJog = $sqliteDb->query("SELECT * FROM jogador");
        $jogadoresDataDb3 = [];
        while ($jRow = $stmtAllJog->fetch()) {
            $jogadoresDataDb3[(int)$jRow['ID']] = $jRow;
        }

        // Rastreamento dos jogadores importados por clube MySQL (para limpar desfalques/excedentes)
        $importedPlayersByMySQLClub = [];
        foreach ($mapClubeDb3ToMySQL as $db3CId => $clubInfo) {
            $mClubId = (int)$clubInfo['id'];
            $importedPlayersByMySQLClub[$mClubId] = [
                'status' => (int)$clubInfo['status'],
                'players' => [],
            ];
        }

        // 5. Processar Jogadores Únicos (Tabela jogador)
        $mapPlayerDb3ToMySQL = [];

        foreach (array_keys($uniquePlayersToImport) as $db3PlayerId) {
            if (!isset($jogadoresDataDb3[$db3PlayerId])) {
                continue;
            }

            $jData = $jogadoresDataDb3[$db3PlayerId];
            $pNome = trim((string)$jData['Nome']);
            $normJ = $this->normalizeName($pNome);
            $pIdade = (int)($jData['Idade'] ?? 22);
            $pNivel = (int)($jData['Nivel'] ?? 50);
            $pMentalidade = (int)($jData['Mentalidade'] ?? 5);
            $pCobradorFalta = (int)($jData['CobradorFalta'] ?? 0);

            $posInfo = $posicoesPorJogadorDb3[$db3PlayerId] ?? ['isGoleiro' => false, 'stringPosicoes' => '000000000000000'];
            $isGoleiro = $posInfo['isGoleiro'];
            $stringPosicoes = $posInfo['stringPosicoes'];

            // Nacionalidade
            $nacStr = $nacionalidadesDb3[$db3PlayerId] ?? '';
            $idNacJogador = $idPais; // padrão: país da importação
            if (!empty($nacStr) && $nacStr !== '-') {
                $cleanNac = str_replace('.png', '', $nacStr);
                $foundPaisNac = $this->paisObj->idPorBandeira($cleanNac);
                if (!$foundPaisNac) {
                    $foundPaisNac = $this->paisObj->idPorSigla($cleanNac);
                }
                if ($foundPaisNac) {
                    $idNacJogador = (int)$foundPaisNac;
                }
            }

            // Atributos
            if ($isGoleiro) {
                $ag = $atributosGoleiroDb3[$db3PlayerId] ?? [];
                $marcacao = 0.0;
                $desarme = 0.0;
                $visaoJogo = 0.0;
                $movimentacao = 0.0;
                $cruzamentos = 0.0;
                $cabeceamento = 0.0;
                $tecnica = 0.0;
                $controleBola = 0.0;
                $finalizacao = 0.0;
                $faroGol = 0.0;
                $velocidade = 0.0;
                $forca = 0.0;

                $reflexos = (float)($ag['Reflexos'] ?? 1.0);
                $seguranca = (float)($ag['Seguranca'] ?? 1.0);
                $saidas = (float)($ag['Saidas'] ?? 1.0);
                $jogoAereo = (float)($ag['JogoAereo'] ?? 1.0);
                $lancamentos = (float)($ag['Lancamentos'] ?? 1.0);
                $defesaPenaltis = (float)($ag['DefesaPenaltis'] ?? 1.0);
                $determinacao = (float)($ag['Determinacao'] ?? 1.0);
                $determinacaoOriginal = (float)($ag['determinacaoOriginal'] ?? 1);
            } else {
                $aj = $atributosJogadorDb3[$db3PlayerId] ?? [];
                $marcacao = (float)($aj['Marcacao'] ?? 1.0);
                $desarme = (float)($aj['Desarme'] ?? 1.0);
                $visaoJogo = (float)($aj['VisaoJogo'] ?? 1.0);
                $movimentacao = (float)($aj['Movimentacao'] ?? 1.0);
                $cruzamentos = (float)($aj['Cruzamentos'] ?? 1.0);
                $cabeceamento = (float)($aj['Cabeceamento'] ?? 1.0);
                $tecnica = (float)($aj['Tecnica'] ?? 1.0);
                $controleBola = (float)($aj['ControleBola'] ?? 1.0);
                $finalizacao = (float)($aj['Finalizacao'] ?? 1.0);
                $faroGol = (float)($aj['FaroGol'] ?? 1.0);
                $velocidade = (float)($aj['Velocidade'] ?? 1.0);
                $forca = (float)($aj['Forca'] ?? 1.0);

                $reflexos = 0.0;
                $seguranca = 0.0;
                $saidas = 0.0;
                $jogoAereo = 0.0;
                $lancamentos = 0.0;
                $defesaPenaltis = 0.0;
                $determinacao = (float)($aj['Determinacao'] ?? 1.0);
                $determinacaoOriginal = (float)($aj['determinacaoOriginal'] ?? 1);
            }

            // Preparar objeto jogador
            $this->jogadorObj->nomeJogador = $pNome;
            $this->jogadorObj->nascimento = $pIdade;
            $this->jogadorObj->nivel = $pNivel;
            $this->jogadorObj->mentalidade = $pMentalidade;
            $this->jogadorObj->cobradorFalta = $pCobradorFalta;
            $this->jogadorObj->condicao = "true";
            $this->jogadorObj->pais = $idNacJogador;
            $this->jogadorObj->stringPosicoes = $stringPosicoes;
            $this->jogadorObj->sexo = $sexo;
            $this->jogadorObj->externalID = $db3PlayerId;

            $this->jogadorObj->marcacao = $marcacao;
            $this->jogadorObj->desarme = $desarme;
            $this->jogadorObj->visaoJogo = $visaoJogo;
            $this->jogadorObj->movimentacao = $movimentacao;
            $this->jogadorObj->cruzamentos = $cruzamentos;
            $this->jogadorObj->cabeceamento = $cabeceamento;
            $this->jogadorObj->tecnica = $tecnica;
            $this->jogadorObj->controleBola = $controleBola;
            $this->jogadorObj->finalizacao = $finalizacao;
            $this->jogadorObj->faroGol = $faroGol;
            $this->jogadorObj->velocidade = $velocidade;
            $this->jogadorObj->forca = $forca;
            $this->jogadorObj->reflexos = $reflexos;
            $this->jogadorObj->seguranca = $seguranca;
            $this->jogadorObj->saidas = $saidas;
            $this->jogadorObj->jogoAereo = $jogoAereo;
            $this->jogadorObj->lancamentos = $lancamentos;
            $this->jogadorObj->defesaPenaltis = $defesaPenaltis;
            $this->jogadorObj->determinacao = $determinacao;
            $this->jogadorObj->determinacaoOriginal = $determinacaoOriginal;
            $this->jogadorObj->valor = $this->jogadorObj->calcularPasse();

            // Busca de jogador existente via HashMap O(1)
            $existingJog = $existingJogByExtId[$db3PlayerId] ?? ($existingJogByName[$normJ] ?? null);

            $mysqlPlayerId = 0;
            $jogAction = 'created';

            if ($existingJog) {
                $mysqlPlayerId = (int)$existingJog['ID'];
                $jogAction = 'updated';
                $this->jogadorObj->updateImported($mysqlPlayerId);
                $report['stats']['jogadores_atualizados']++;
            } else {
                if ($this->jogadorObj->create()) {
                    $mysqlPlayerId = (int)$this->jogadorObj->id;
                    $report['stats']['jogadores_criados']++;

                    // Atualiza cache em memória
                    $newJogData = [
                        'ID' => $mysqlPlayerId,
                        'Nome' => $pNome,
                        'externalID' => $db3PlayerId,
                    ];
                    $existingJogByExtId[$db3PlayerId] = $newJogData;
                    $existingJogByName[$normJ] = $newJogData;
                } else {
                    $report['logs'][] = "Aviso: Erro ao inserir jogador '{$pNome}' (db3 ID: {$db3PlayerId}).";
                }
            }

            if ($mysqlPlayerId > 0) {
                $mapPlayerDb3ToMySQL[$db3PlayerId] = $mysqlPlayerId;
                $report['stats']['total_jogadores']++;

                if (count($report['jogadores_detalhes']) < 100) {
                    $report['jogadores_detalhes'][] = [
                        'db3_id' => $db3PlayerId,
                        'mysql_id' => $mysqlPlayerId,
                        'nome' => $pNome,
                        'nivel' => $pNivel,
                        'action' => $jogAction,
                    ];
                }
            }
        }

        // 5.1 Vincular Atletas aos Elencos (Clubes e Seleções)
        foreach ($elencosPorClubeDb3 as $db3CId => $playerIdsInClub) {
            $targetClubInfo = $mapClubeDb3ToMySQL[$db3CId] ?? null;
            if (!$targetClubInfo) continue;

            $mysqlTargetClub = (int)$targetClubInfo['id'];
            $targetClubStatus = (int)$targetClubInfo['status'];
            $escClub = $escalacoesDb3[$db3CId] ?? null;

            foreach ($playerIdsInClub as $db3PlayerId) {
                $mysqlPlayerId = $mapPlayerDb3ToMySQL[$db3PlayerId] ?? 0;
                if ($mysqlPlayerId <= 0) continue;

                // Determinar titularidade, posição base, capitão e batedor de pênalti
                $isCapitao = 0;
                $isPenalti = 0;
                $titularidade = 0;
                $posicaoBase = 0;

                if ($escClub) {
                    if ($escClub['capitao'] === $db3PlayerId) {
                        $isCapitao = 1;
                    }
                    foreach ($escClub['penaltis'] as $order => $penJogId) {
                        if ($penJogId === $db3PlayerId) {
                            $isPenalti = $order;
                            break;
                        }
                    }
                    if (isset($escClub['titulares'][$db3PlayerId])) {
                        $titularidade = 1;
                        $siglaPos = $escClub['titulares'][$db3PlayerId];
                        $posicaoBase = (int)$this->jogadorObj->posicaoPorSigla($siglaPos);
                    }
                }

                if ($targetClubStatus > 0) {
                    // Contrato de Seleção: tipoContrato = targetClubStatus (preserva contrato profissional de clube tipoContrato = 0)
                    $stmtJogSel = $this->mysqlDb->prepare("
                        INSERT INTO contratos_jogador (jogador, clube, posicaoBase, titularidade, capitao, cobrancaPenalti, ModificadorNivel, encerramento, salario, tipoContrato, clubeVinculado)
                        VALUES (?, ?, ?, ?, ?, ?, 0, '0000-00-00', 0, ?, 0)
                        ON DUPLICATE KEY UPDATE
                            clube = VALUES(clube),
                            posicaoBase = VALUES(posicaoBase),
                            titularidade = VALUES(titularidade),
                            capitao = VALUES(capitao),
                            cobrancaPenalti = VALUES(cobrancaPenalti)
                    ");
                    $stmtJogSel->execute([
                        $mysqlPlayerId,
                        $mysqlTargetClub,
                        $posicaoBase,
                        $titularidade,
                        $isCapitao,
                        $isPenalti,
                        $targetClubStatus
                    ]);
                    $importedPlayersByMySQLClub[$mysqlTargetClub]['players'][] = $mysqlPlayerId;
                    if (isset($report['clubes_detalhes'][$db3CId])) {
                        $report['clubes_detalhes'][$db3CId]['jogadores_count']++;
                    }
                } else {
                    // Contrato de Clube Regular: verificar se atleta está em clube de outro usuário
                    $jData = $jogadoresDataDb3[$db3PlayerId] ?? [];
                    $normJ = $this->normalizeName(trim((string)($jData['Nome'] ?? '')));
                    $existingJogInfo = $existingJogByExtId[$db3PlayerId] ?? ($existingJogByName[$normJ] ?? null);

                    $clubeOrigemId = (int)($existingJogInfo['clubeID'] ?? 0);
                    $clubeDono = (int)($existingJogInfo['clubeDono'] ?? 0);
                    $clubePais = (int)($existingJogInfo['clubePais'] ?? 0);
                    $isOutroUsuario = ($clubeOrigemId > 0 && $clubeDono !== (int)$userId && $clubePais !== (int)$idPais);

                    if ($isOutroUsuario) {
                        // Jogador em clube de outro usuário: criar proposta automática em vez de transferir direto
                        $stmtCheckProp = $this->mysqlDb->prepare("
                            SELECT ID FROM transferencias 
                            WHERE jogador = ? AND clubeDestino = ? AND status_execucao = 0
                        ");
                        $stmtCheckProp->execute([$mysqlPlayerId, $mysqlTargetClub]);
                        $hasPendingProp = (bool)$stmtCheckProp->fetchColumn();

                        if (!$hasPendingProp) {
                            $valorPasse = (float)($existingJogInfo['jogValor'] ?? 0);
                            if ($valorPasse <= 0) {
                                $valorPasse = $this->jogadorObj->calcularPasse($mysqlPlayerId);
                            }
                            $this->jogadorObj->proporTransferencia(
                                $mysqlPlayerId,
                                $clubeOrigemId,
                                $mysqlTargetClub,
                                $valorPasse,
                                0,
                                0,
                                0,
                                '-- proposta automatica --',
                                'Importador DB3'
                            );
                            $report['stats']['propostas_enviadas']++;
                            $clOrigemNome = $existingJogInfo['clubeNome'] ?? "Clube #{$clubeOrigemId}";
                            $report['logs'][] = "Proposta automática enviada pelo atleta '{$jData['Nome']}' (F$ " . number_format($valorPasse, 0, ',', '.') . ") ao clube '{$clOrigemNome}'.";
                        }
                    } else {
                        // Contrato de Clube Regular normal: tipoContrato = 0
                        $this->jogadorObj->transferir($mysqlPlayerId, $mysqlTargetClub, $isCapitao, $isPenalti, $titularidade, $posicaoBase);
                        $importedPlayersByMySQLClub[$mysqlTargetClub]['players'][] = $mysqlPlayerId;
                        if (isset($report['clubes_detalhes'][$db3CId])) {
                            $report['clubes_detalhes'][$db3CId]['jogadores_count']++;
                        }
                    }
                }
            }
        }

        // 6. Demover atletas sobressalentes que não estavam no novo elenco importado
        foreach ($importedPlayersByMySQLClub as $mClubId => $clubGroup) {
            if ($mClubId <= 0) continue;

            $cTargetStatus = (int)$clubGroup['status'];
            $importedList = $clubGroup['players'];

            $stmtCur = $this->mysqlDb->prepare("SELECT jogador FROM contratos_jogador WHERE clube = ? AND tipoContrato = ?");
            $stmtCur->execute([$mClubId, $cTargetStatus]);
            $curIds = $stmtCur->fetchAll(PDO::FETCH_COLUMN);

            $leftovers = array_diff($curIds, $importedList);
            if (!empty($leftovers)) {
                $inClause = implode(',', array_fill(0, count($leftovers), '?'));
                $stmtDemote = $this->mysqlDb->prepare("
                    UPDATE contratos_jogador 
                    SET titularidade = -1, posicaoBase = 0, capitao = 0, cobrancaPenalti = 0 
                    WHERE clube = ? AND jogador IN ({$inClause}) AND tipoContrato = ?
                ");
                $stmtDemote->execute(array_merge([$mClubId], array_values($leftovers), [$cTargetStatus]));
            }
        }

        return $report;
    }
}

