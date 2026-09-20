<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/paises.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/jogador.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/estadio.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/clima.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/tecnico.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php';

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
     * Mapeia todas as tabelas do SQLite de forma case-insensitive.
     * Retorna array com chave em minúsculas => nome real no banco.
     */
    private function getSqliteTableMap(PDO $sqliteDb): array {
        $sqliteTables = $sqliteDb->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        $tableMap = [];
        foreach ($sqliteTables as $tblName) {
            $tableMap[mb_strtolower(trim((string)$tblName), 'UTF-8')] = trim((string)$tblName);
        }
        return $tableMap;
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

        // Verificar tabelas essenciais no SQLite (case-insensitive)
        $tableMap = $this->getSqliteTableMap($sqliteDb);
        $requiredTables = ['clube', 'jogador', 'elenco'];
        foreach ($requiredTables as $req) {
            if (!isset($tableMap[$req])) {
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
        if (isset($tableMap['estadio'])) {
            $tblEst = $tableMap['estadio'];
            $stmtEst = $sqliteDb->query("SELECT * FROM \"{$tblEst}\"");
            while ($eRow = $stmtEst->fetch()) {
                $eRow = array_change_key_case($eRow, CASE_LOWER);
                $eId = (int)($eRow['id'] ?? 0);
                $eNome = trim((string)($eRow['nome'] ?? ''));
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
                    'capacidade' => (int)($eRow['capacidade'] ?? 10000),
                    'action' => $eAction,
                    'matched_id' => $foundE ? (int)$foundE['ID'] : null,
                    'matched_nome' => $foundE ? $foundE['Nome'] : null,
                ];
            }
        }

        // 2. Mapear Técnicos
        $tecnicosDb3 = [];
        if (isset($tableMap['tecnico'])) {
            $tblTec = $tableMap['tecnico'];
            $stmtTec = $sqliteDb->query("SELECT * FROM \"{$tblTec}\"");
            while ($tRow = $stmtTec->fetch()) {
                $tRow = array_change_key_case($tRow, CASE_LOWER);
                $tId = (int)($tRow['id'] ?? 0);
                $tNome = trim(preg_replace('/\s*\[.*?\]\s*$/', '', (string)($tRow['nome'] ?? '')));
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
                    'idade' => (int)($tRow['idade'] ?? 45),
                    'nivel' => (int)($tRow['nivel'] ?? 5),
                    'action' => $tAction,
                    'matched_id' => $foundT ? (int)$foundT['ID'] : null,
                    'matched_nome' => $foundT ? $foundT['Nome'] : null,
                ];
            }
        }

        // 3. Mapear Posições, Nacionalidades e Escalações dos Jogadores
        $posicoesDb3 = [];
        if (isset($tableMap['posicaojogador'])) {
            $tblPos = $tableMap['posicaojogador'];
            $stmtPos = $sqliteDb->query("SELECT * FROM \"{$tblPos}\"");
            while ($pRow = $stmtPos->fetch()) {
                $pRow = array_change_key_case($pRow, CASE_LOWER);
                $jId = (int)($pRow['jogador'] ?? $pRow['id_jogador'] ?? $pRow['id'] ?? 0);
                if ($jId <= 0) continue;
                $isG = ((int)($pRow['g'] ?? 0) === 1);
                $activePositions = [];
                if ($isG) {
                    $activePositions[] = 'G';
                }
                $posMap = [
                    'ld' => 'LD', 'le' => 'LE', 'z' => 'Z', 'ad' => 'AD', 'ae' => 'AE',
                    'v' => 'V', 'md' => 'MD', 'me' => 'ME', 'mc' => 'MC', 'pd' => 'PD',
                    'pe' => 'PE', 'ma' => 'MA', 'am' => 'Am', 'aa' => 'Aa'
                ];
                foreach ($posMap as $pk => $lbl) {
                    if ((int)($pRow[$pk] ?? 0) === 1) {
                        $activePositions[] = $lbl;
                    }
                }
                $posicoesDb3[$jId] = [
                    'is_goleiro' => $isG,
                    'positions' => $activePositions,
                ];
            }
        }

        $nacionalidadesDb3 = [];
        if (isset($tableMap['nacionalidades'])) {
            $tblNac = $tableMap['nacionalidades'];
            $stmtNac = $sqliteDb->query("SELECT * FROM \"{$tblNac}\"");
            while ($nRow = $stmtNac->fetch()) {
                $nRow = array_change_key_case($nRow, CASE_LOWER);
                $jId = (int)($nRow['id_jogador'] ?? $nRow['jogador'] ?? $nRow['id'] ?? 0);
                if ($jId > 0) {
                    $nacionalidadesDb3[$jId] = trim((string)($nRow['nacionalidade'] ?? ''));
                }
            }
        }

        $escalacoesDb3 = [];
        if (isset($tableMap['escalacao'])) {
            $tblEsc = $tableMap['escalacao'];
            $stmtEsc = $sqliteDb->query("SELECT * FROM \"{$tblEsc}\"");
            while ($escRow = $stmtEsc->fetch()) {
                $escRow = array_change_key_case($escRow, CASE_LOWER);
                $cId = (int)($escRow['clube'] ?? 0);
                $cap = (int)($escRow['capitao'] ?? 0);
                $pen1 = (int)($escRow['penalti1'] ?? 0);
                $pen2 = (int)($escRow['penalti2'] ?? 0);
                $pen3 = (int)($escRow['penalti3'] ?? 0);

                $titulares = [];
                for ($k = 1; $k <= 11; $k++) {
                    $tJogId = (int)($escRow["jogador{$k}"] ?? 0);
                    $tPos = trim((string)($escRow["pos{$k}"] ?? ''));
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
        $tblClubes = $tableMap['clube'];
        $stmtClubes = $sqliteDb->query("SELECT * FROM \"{$tblClubes}\" ORDER BY ID ASC");
        $clubesDb3 = $stmtClubes->fetchAll();

        foreach ($clubesDb3 as $cRow) {
            $cRow = array_change_key_case($cRow, CASE_LOWER);
            $db3ClubId = (int)($cRow['id'] ?? 0);
            $clubNome = trim((string)($cRow['nome'] ?? ''));
            $clubSigla = strtoupper(substr(trim((string)($cRow['tresletras'] ?? 'XXX')), 0, 3));
            $db3EstId = (int)($cRow['estadio'] ?? 0);
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
                'max_torcedores' => (int)($cRow['maxtorcedores'] ?? 5000),
                'fidelidade' => (int)($cRow['fidelidade'] ?? 5),
                'cores' => [
                    'u1c1' => (string)($cRow['uni1cor1'] ?? '000000000'),
                    'u1c2' => (string)($cRow['uni1cor2'] ?? '255255255'),
                    'u1c3' => (string)($cRow['uni1cor3'] ?? '000000000'),
                    'u2c1' => (string)($cRow['uni2cor1'] ?? '255255255'),
                    'u2c2' => (string)($cRow['uni2cor2'] ?? '000000000'),
                    'u2c3' => (string)($cRow['uni2cor3'] ?? '255255255'),
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
        $tblElenco = $tableMap['elenco'];
        $stmtElencosPre = $sqliteDb->query("SELECT * FROM \"{$tblElenco}\"");
        while ($elRow = $stmtElencosPre->fetch()) {
            $elRow = array_change_key_case($elRow, CASE_LOWER);
            $db3CId = (int)($elRow['clube'] ?? 0);
            $cInf = $preview['clubes'][$db3CId] ?? null;
            if ($cInf && empty($cInf['is_selecao'])) {
                for ($k = 1; $k <= 23; $k++) {
                    $jId = (int)($elRow["jogador{$k}"] ?? 0);
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
        $tblJogador = $tableMap['jogador'];
        $stmtAllJog = $sqliteDb->query("SELECT * FROM \"{$tblJogador}\"");
        $jogadoresDataDb3 = [];
        while ($jRow = $stmtAllJog->fetch()) {
            $jRow = array_change_key_case($jRow, CASE_LOWER);
            $jId = (int)($jRow['id'] ?? 0);
            if ($jId > 0) {
                $jogadoresDataDb3[$jId] = $jRow;
            }
        }

        // Mapear jogadores únicos presentes nos elencos da importação para estatísticas globais precisas
        $uniquePlayersInImport = [];
        $stmtElencosPreCount = $sqliteDb->query("SELECT * FROM \"{$tblElenco}\"");
        while ($elRow = $stmtElencosPreCount->fetch()) {
            $elRow = array_change_key_case($elRow, CASE_LOWER);
            $db3CId = (int)($elRow['clube'] ?? 0);
            if (isset($preview['clubes'][$db3CId])) {
                for ($k = 1; $k <= 23; $k++) {
                    $jId = (int)($elRow["jogador{$k}"] ?? 0);
                    if ($jId > 0 && isset($jogadoresDataDb3[$jId])) {
                        $uniquePlayersInImport[$jId] = true;
                    }
                }
            }
        }

        foreach ($uniquePlayersInImport as $jId => $_) {
            $jData = $jogadoresDataDb3[$jId];
            $normJ = $this->normalizeName($jData['nome'] ?? '');
            $existingJog = $existingJogByExtId[$jId] ?? ($existingJogByName[$normJ] ?? null);
            if ($existingJog) {
                $preview['stats']['jogadores_atualizados']++;
            } else {
                $preview['stats']['jogadores_criados']++;
            }
            $preview['stats']['total_jogadores']++;
        }

        $stmtElencos = $sqliteDb->query("SELECT * FROM \"{$tblElenco}\"");
        while ($elRow = $stmtElencos->fetch()) {
            $elRow = array_change_key_case($elRow, CASE_LOWER);
            $db3CId = (int)($elRow['clube'] ?? 0);
            if (!isset($preview['clubes'][$db3CId])) {
                continue;
            }

            // Associar Técnico do elenco ao preview do clube
            $db3TId = (int)($elRow['tecnico'] ?? 0);
            if ($db3TId > 0 && isset($tecnicosDb3[$db3TId])) {
                $preview['clubes'][$db3CId]['tecnico'] = $tecnicosDb3[$db3TId];
            }

            $escInfo = $escalacoesDb3[$db3CId] ?? null;
            $isSelecaoClub = !empty($preview['clubes'][$db3CId]['is_selecao']);

            for ($k = 1; $k <= 23; $k++) {
                $jId = (int)($elRow["jogador{$k}"] ?? 0);
                if ($jId <= 0 || !isset($jogadoresDataDb3[$jId])) {
                    continue;
                }

                $jData = $jogadoresDataDb3[$jId];
                $pNome = trim((string)($jData['nome'] ?? ''));
                $pIdade = (int)($jData['idade'] ?? 22);
                $pNivel = (int)($jData['nivel'] ?? 50);
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

        // Verificar tabelas essenciais no SQLite (case-insensitive)
        $tableMap = $this->getSqliteTableMap($sqliteDb);
        $requiredTables = ['clube', 'jogador', 'elenco'];
        foreach ($requiredTables as $req) {
            if (!isset($tableMap[$req])) {
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
        if (isset($tableMap['clima'])) {
            $tblClima = $tableMap['clima'];
            $stmtClimas = $sqliteDb->query("SELECT * FROM \"{$tblClima}\"");
            while ($climaRow = $stmtClimas->fetch()) {
                $climaRow = array_change_key_case($climaRow, CASE_LOWER);
                $cId = (int)($climaRow['id'] ?? 0);
                $cNome = trim((string)($climaRow['nome'] ?? ''));
                $normClima = $this->normalizeName($cNome);
                
                $mysqlClimaId = $existingClimaByName[$normClima] ?? 0;

                if (!$mysqlClimaId) {
                    $this->climaObj->nome = $cNome;
                    $this->climaObj->tempVerao = (string)($climaRow['tempverao'] ?? 'Normal');
                    $this->climaObj->estiloVerao = (string)($climaRow['estiloverao'] ?? 'Normal');
                    $this->climaObj->tempOutono = (string)($climaRow['tempoutono'] ?? 'Normal');
                    $this->climaObj->estiloOutono = (string)($climaRow['estilooutono'] ?? 'Normal');
                    $this->climaObj->tempInverno = (string)($climaRow['tempinverno'] ?? 'Normal');
                    $this->climaObj->estiloInverno = (string)($climaRow['estiloinverno'] ?? 'Normal');
                    $this->climaObj->tempPrimavera = (string)($climaRow['tempprimavera'] ?? 'Normal');
                    $this->climaObj->estiloPrimavera = (string)($climaRow['estiloprimavera'] ?? 'Normal');
                    $this->climaObj->hemisferio = (string)($climaRow['hemisferio'] ?? '0');
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
        if (isset($tableMap['estadio'])) {
            $tblEst = $tableMap['estadio'];
            $stmtEstadios = $sqliteDb->query("SELECT * FROM \"{$tblEst}\"");
            while ($estRow = $stmtEstadios->fetch()) {
                $estRow = array_change_key_case($estRow, CASE_LOWER);
                $eId = (int)($estRow['id'] ?? 0);
                $eNome = trim((string)($estRow['nome'] ?? ''));
                $normE = $this->normalizeName($eNome);
                $eCap = (int)($estRow['capacidade'] ?? 10000);
                $eClimaDb3 = (int)($estRow['clima'] ?? 0);
                $mysqlClima = $mapClimaDb3ToMySQL[$eClimaDb3] ?? 0;
                $eAlt = (int)($estRow['altitude'] ?? 0);
                $eCald = (int)($estRow['caldeirao'] ?? 0);

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
        $tblClubes = $tableMap['clube'];
        $stmtClubes = $sqliteDb->query("SELECT * FROM \"{$tblClubes}\" ORDER BY ID ASC");
        $clubesDb3 = $stmtClubes->fetchAll();

        foreach ($clubesDb3 as $cRow) {
            $cRow = array_change_key_case($cRow, CASE_LOWER);
            $db3ClubId = (int)($cRow['id'] ?? 0);
            $clubNome = trim((string)($cRow['nome'] ?? ''));
            $normC = $this->normalizeName($clubNome);
            $clubSigla = strtoupper(substr(trim((string)($cRow['tresletras'] ?? 'XXX')), 0, 3));
            $maxTorcedores = (int)($cRow['maxtorcedores'] ?? 5000);
            $fidelidade = (int)($cRow['fidelidade'] ?? 5);

            $u1c1 = (string)($cRow['uni1cor1'] ?? '000000000');
            $u1c2 = (string)($cRow['uni1cor2'] ?? '255255255');
            $u1c3 = (string)($cRow['uni1cor3'] ?? '000000000');
            $u2c1 = (string)($cRow['uni2cor1'] ?? '255255255');
            $u2c2 = (string)($cRow['uni2cor2'] ?? '000000000');
            $u2c3 = (string)($cRow['uni2cor3'] ?? '255255255');

            $db3EstId = (int)($cRow['estadio'] ?? 0);
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
        if (isset($tableMap['tecnico'])) {
            $tblTec = $tableMap['tecnico'];
            $stmtTecDb3 = $sqliteDb->query("SELECT * FROM \"{$tblTec}\"");
            while ($tRow = $stmtTecDb3->fetch()) {
                $tRow = array_change_key_case($tRow, CASE_LOWER);
                $db3TId = (int)($tRow['id'] ?? 0);
                $tNome = trim(preg_replace('/\s*\[.*?\]\s*$/', '', (string)($tRow['nome'] ?? '')));
                $normT = $this->normalizeName($tNome);
                $tIdade = (int)($tRow['idade'] ?? 45);
                $tNivel = (int)($tRow['nivel'] ?? 5);
                $tMentalidade = (int)($tRow['mentalidade'] ?? 5);
                $tEstilo = (int)($tRow['estilo'] ?? 3);

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
            $tblElenco = $tableMap['elenco'];
            $stmtElencosTec = $sqliteDb->query("SELECT Clube, Tecnico FROM \"{$tblElenco}\" WHERE Tecnico > 0");
            while ($elRow = $stmtElencosTec->fetch()) {
                $elRow = array_change_key_case($elRow, CASE_LOWER);
                $db3CId = (int)($elRow['clube'] ?? 0);
                $db3TId = (int)($elRow['tecnico'] ?? 0);
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

        // 4. Carregar Mapeamentos do SQLite para Jogadores (case-insensitive)
        // Posições
        $posicoesPorJogadorDb3 = [];
        if (isset($tableMap['posicaojogador'])) {
            $tblPos = $tableMap['posicaojogador'];
            $stmtPos = $sqliteDb->query("SELECT * FROM \"{$tblPos}\"");
            while ($pRow = $stmtPos->fetch()) {
                $pRow = array_change_key_case($pRow, CASE_LOWER);
                $jId = (int)($pRow['jogador'] ?? $pRow['id_jogador'] ?? $pRow['id'] ?? 0);
                if ($jId <= 0) continue;
                $g = (int)($pRow['g'] ?? 0);
                $ld = (int)($pRow['ld'] ?? 0);
                $le = (int)($pRow['le'] ?? 0);
                $z = (int)($pRow['z'] ?? 0);
                $ad = (int)($pRow['ad'] ?? 0);
                $ae = (int)($pRow['ae'] ?? 0);
                $v = (int)($pRow['v'] ?? 0);
                $md = (int)($pRow['md'] ?? 0);
                $me = (int)($pRow['me'] ?? 0);
                $mc = (int)($pRow['mc'] ?? 0);
                $pd = (int)($pRow['pd'] ?? 0);
                $pe = (int)($pRow['pe'] ?? 0);
                $ma = (int)($pRow['ma'] ?? 0);
                $am = (int)($pRow['am'] ?? 0);
                $aa = (int)($pRow['aa'] ?? 0);

                $strPos = "{$g}{$ld}{$le}{$z}{$ad}{$ae}{$v}{$md}{$me}{$mc}{$pd}{$pe}{$ma}{$am}{$aa}";
                $posicoesPorJogadorDb3[$jId] = [
                    'isGoleiro' => ($g === 1),
                    'stringPosicoes' => $strPos,
                ];
            }
        }

        // Atributos de Linha
        $atributosJogadorDb3 = [];
        if (isset($tableMap['atributosjogador'])) {
            $tblAttr = $tableMap['atributosjogador'];
            $stmtAttr = $sqliteDb->query("SELECT * FROM \"{$tblAttr}\"");
            while ($aRow = $stmtAttr->fetch()) {
                $aRow = array_change_key_case($aRow, CASE_LOWER);
                $jId = (int)($aRow['jogador'] ?? $aRow['id_jogador'] ?? $aRow['id'] ?? 0);
                if ($jId > 0) {
                    $atributosJogadorDb3[$jId] = $aRow;
                }
            }
        }

        // Atributos de Goleiro
        $atributosGoleiroDb3 = [];
        if (isset($tableMap['atributosgoleiro'])) {
            $tblAttrG = $tableMap['atributosgoleiro'];
            $stmtAttrG = $sqliteDb->query("SELECT * FROM \"{$tblAttrG}\"");
            while ($agRow = $stmtAttrG->fetch()) {
                $agRow = array_change_key_case($agRow, CASE_LOWER);
                $gId = (int)($agRow['goleiro'] ?? $agRow['jogador'] ?? $agRow['id_jogador'] ?? $agRow['id'] ?? 0);
                if ($gId > 0) {
                    $atributosGoleiroDb3[$gId] = $agRow;
                }
            }
        }

        // Nacionalidades
        $nacionalidadesDb3 = [];
        if (isset($tableMap['nacionalidades'])) {
            $tblNac = $tableMap['nacionalidades'];
            $stmtNac = $sqliteDb->query("SELECT * FROM \"{$tblNac}\"");
            while ($nRow = $stmtNac->fetch()) {
                $nRow = array_change_key_case($nRow, CASE_LOWER);
                $jId = (int)($nRow['id_jogador'] ?? $nRow['jogador'] ?? $nRow['id'] ?? 0);
                if ($jId > 0) {
                    $nacionalidadesDb3[$jId] = trim((string)($nRow['nacionalidade'] ?? ''));
                }
            }
        }

        // Escalações (Capitão, Pênaltis, Titulares e Posições)
        $escalacoesDb3 = [];
        if (isset($tableMap['escalacao'])) {
            $tblEsc = $tableMap['escalacao'];
            $stmtEsc = $sqliteDb->query("SELECT * FROM \"{$tblEsc}\"");
            while ($escRow = $stmtEsc->fetch()) {
                $escRow = array_change_key_case($escRow, CASE_LOWER);
                $cId = (int)($escRow['clube'] ?? 0);
                $cap = (int)($escRow['capitao'] ?? 0);
                $pen1 = (int)($escRow['penalti1'] ?? 0);
                $pen2 = (int)($escRow['penalti2'] ?? 0);
                $pen3 = (int)($escRow['penalti3'] ?? 0);

                $titulares = [];
                for ($k = 1; $k <= 11; $k++) {
                    $tJogId = (int)($escRow["jogador{$k}"] ?? 0);
                    $tPos = trim((string)($escRow["pos{$k}"] ?? ''));
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
        $tblElenco = $tableMap['elenco'];
        $stmtElencos = $sqliteDb->query("SELECT * FROM \"{$tblElenco}\"");
        $uniquePlayersToImport = []; // db3_player_id => true
        $elencosPorClubeDb3 = [];

        while ($elRow = $stmtElencos->fetch()) {
            $elRow = array_change_key_case($elRow, CASE_LOWER);
            $db3CId = (int)($elRow['clube'] ?? 0);
            if (!isset($mapClubeDb3ToMySQL[$db3CId])) {
                continue; // Clube não pertence à importação
            }

            $elencosPorClubeDb3[$db3CId] = [];
            for ($k = 1; $k <= 23; $k++) {
                $jId = (int)($elRow["jogador{$k}"] ?? 0);
                if ($jId > 0) {
                    $uniquePlayersToImport[$jId] = true;
                    $elencosPorClubeDb3[$db3CId][] = $jId;
                }
            }
        }

        // Carregar dados de todos os jogadores no SQLite
        $tblJogador = $tableMap['jogador'];
        $stmtAllJog = $sqliteDb->query("SELECT * FROM \"{$tblJogador}\"");
        $jogadoresDataDb3 = [];
        while ($jRow = $stmtAllJog->fetch()) {
            $jRow = array_change_key_case($jRow, CASE_LOWER);
            $jId = (int)($jRow['id'] ?? 0);
            if ($jId > 0) {
                $jogadoresDataDb3[$jId] = $jRow;
            }
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
            $pNome = trim((string)($jData['nome'] ?? ''));
            $normJ = $this->normalizeName($pNome);
            $pIdade = (int)($jData['idade'] ?? 22);
            $pNivel = (int)($jData['nivel'] ?? 50);
            $pMentalidade = (int)($jData['mentalidade'] ?? 5);
            $pCobradorFalta = (int)($jData['cobradorfalta'] ?? ($jData['cobrador_falta'] ?? 0));

            // Posição do jogador
            $posInfo = $posicoesPorJogadorDb3[$db3PlayerId] ?? null;
            if ($posInfo) {
                $isGoleiro = (bool)$posInfo['isGoleiro'];
                $stringPosicoes = (string)$posInfo['stringPosicoes'];
            } else {
                // Fallback de determinação de posição
                $isGoleiro = isset($atributosGoleiroDb3[$db3PlayerId]);
                if ($isGoleiro) {
                    $stringPosicoes = '100000000000000';
                } else {
                    $stringPosicoes = '000000000100000'; // Meia Central padrão
                }
            }

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

            // Atributos com distribuição inteligente via adjustAttributes caso ausentes ou zerados
            if ($isGoleiro) {
                $ag = $atributosGoleiroDb3[$db3PlayerId] ?? [];
                $reflexos = isset($ag['reflexos']) ? (float)$ag['reflexos'] : 0.0;
                $seguranca = isset($ag['seguranca']) ? (float)$ag['seguranca'] : 0.0;
                $saidas = isset($ag['saidas']) ? (float)$ag['saidas'] : 0.0;
                $jogoAereo = isset($ag['jogoaereo']) ? (float)$ag['jogoaereo'] : 0.0;
                $lancamentos = isset($ag['lancamentos']) ? (float)$ag['lancamentos'] : 0.0;
                $defesaPenaltis = isset($ag['defesapenaltis']) ? (float)$ag['defesapenaltis'] : 0.0;
                $determinacao = isset($ag['determinacao']) ? (float)$ag['determinacao'] : 1.0;
                $determinacaoOriginal = isset($ag['determinacaooriginal']) ? (float)$ag['determinacaooriginal'] : (float)$determinacao;

                $sumG = $reflexos + $seguranca + $saidas + $jogoAereo + $lancamentos + $defesaPenaltis;
                if ($sumG <= 0.01) {
                    $adj = adjustAttributes(true, $pNivel, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
                    $reflexos = (float)($adj['reflexos'] ?? 1.0);
                    $seguranca = (float)($adj['seguranca'] ?? 1.0);
                    $saidas = (float)($adj['saidas'] ?? 1.0);
                    $jogoAereo = (float)($adj['jogoAereo'] ?? 1.0);
                    $lancamentos = (float)($adj['lancamentos'] ?? 1.0);
                    $defesaPenaltis = (float)($adj['defesaPenaltis'] ?? 1.0);
                }

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
            } else {
                $aj = $atributosJogadorDb3[$db3PlayerId] ?? [];
                $marcacao = isset($aj['marcacao']) ? (float)$aj['marcacao'] : 0.0;
                $desarme = isset($aj['desarme']) ? (float)$aj['desarme'] : 0.0;
                $visaoJogo = isset($aj['visaojogo']) ? (float)$aj['visaojogo'] : 0.0;
                $movimentacao = isset($aj['movimentacao']) ? (float)$aj['movimentacao'] : 0.0;
                $cruzamentos = isset($aj['cruzamentos']) ? (float)$aj['cruzamentos'] : 0.0;
                $cabeceamento = isset($aj['cabeceamento']) ? (float)$aj['cabeceamento'] : 0.0;
                $tecnica = isset($aj['tecnica']) ? (float)$aj['tecnica'] : 0.0;
                $controleBola = isset($aj['controlebola']) ? (float)$aj['controlebola'] : 0.0;
                $finalizacao = isset($aj['finalizacao']) ? (float)$aj['finalizacao'] : 0.0;
                $faroGol = isset($aj['farogol']) ? (float)$aj['farogol'] : 0.0;
                $velocidade = isset($aj['velocidade']) ? (float)$aj['velocidade'] : 0.0;
                $forca = isset($aj['forca']) ? (float)$aj['forca'] : 0.0;
                $determinacao = isset($aj['determinacao']) ? (float)$aj['determinacao'] : 1.0;
                $determinacaoOriginal = isset($aj['determinacaooriginal']) ? (float)$aj['determinacaooriginal'] : (float)$determinacao;

                $sumJ = $marcacao + $desarme + $visaoJogo + $movimentacao + $cruzamentos + $cabeceamento + $tecnica + $controleBola + $finalizacao + $faroGol + $velocidade + $forca;
                if ($sumJ <= 0.01) {
                    $adj = adjustAttributes(false, $pNivel, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
                    $marcacao = (float)($adj['marcacao'] ?? 1.0);
                    $desarme = (float)($adj['desarme'] ?? 1.0);
                    $visaoJogo = (float)($adj['visaoJogo'] ?? 1.0);
                    $movimentacao = (float)($adj['movimentacao'] ?? 1.0);
                    $cruzamentos = (float)($adj['cruzamentos'] ?? 1.0);
                    $cabeceamento = (float)($adj['cabeceamento'] ?? 1.0);
                    $tecnica = (float)($adj['tecnica'] ?? 1.0);
                    $controleBola = (float)($adj['controleBola'] ?? 1.0);
                    $finalizacao = (float)($adj['finalizacao'] ?? 1.0);
                    $faroGol = (float)($adj['faroGol'] ?? 1.0);
                    $velocidade = (float)($adj['velocidade'] ?? 1.0);
                    $forca = (float)($adj['forca'] ?? 1.0);
                }

                $reflexos = 0.0;
                $seguranca = 0.0;
                $saidas = 0.0;
                $jogoAereo = 0.0;
                $lancamentos = 0.0;
                $defesaPenaltis = 0.0;
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
                    $normJ = $this->normalizeName(trim((string)($jData['nome'] ?? '')));
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
                            $report['logs'][] = "Proposta automática enviada pelo atleta '{$jData['nome']}' (F$ " . number_format($valorPasse, 0, ',', '.') . ") ao clube '{$clOrigemNome}'.";
                        }
                    } else {
                        // Contrato de Clube Regular normal: tipoContrato = 0
                        $this->jogadorObj->transferir($mysqlPlayerId, $mysqlTargetClub, $isCapitao, $isPenalti, $titularidade, $posicaoBase, 0, 0, 0);
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
