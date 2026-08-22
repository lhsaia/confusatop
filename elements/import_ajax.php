<?php
ob_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

if (isset($_POST['ajax'])) {

    include($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
    include($_SERVER['DOCUMENT_ROOT'] . "/objetos/jogador.php");
    include($_SERVER['DOCUMENT_ROOT'] . "/objetos/paises.php");
    include($_SERVER['DOCUMENT_ROOT'] . "/objetos/time.php");
    include($_SERVER['DOCUMENT_ROOT'] . "/objetos/estadio.php");
    include($_SERVER['DOCUMENT_ROOT'] . "/objetos/clima.php");
    include($_SERVER['DOCUMENT_ROOT'] . "/objetos/tecnico.php");
    include($_SERVER['DOCUMENT_ROOT'] . "/objetos/liga.php");
    include($_SERVER['DOCUMENT_ROOT'] . "/objetos/usuarios.php");
    if (isset($_SESSION['jogadorTime']) && $_SESSION['jogadorTime'] == 7) {
        include($_SERVER['DOCUMENT_ROOT'] . "/objetos/jogos_clube.php");
    } else {
        include($_SERVER['DOCUMENT_ROOT'] . "/objetos/jogos.php");
    }
    include($_SERVER['DOCUMENT_ROOT'] . "/objetos/arbitros.php");

    $database = new Database();
    $db = $database->getConnection();
    $jogador = new Jogador($db);
    $pais = new Pais($db);
    $time = new Time($db);
    $estadio = new Estadio($db);
    $clima = new Clima($db);
    $tecnico = new Tecnico($db);
    $liga = new Liga($db);
    $usuario = new Usuario($db);
    $jogo = new Jogo($db);
    $trioArbitragem = new TrioArbitragem($db);


    $sexo = $_POST['sexo'];
    $campeonato_jogo_import = $_POST['campeonato_jogo_import'];
    $fase_jogo_import = $_POST['fase_jogo_import'];
    $correct_extension = '';
    $max_file_size = 0;
    $arquivo_tratamento = '';

    if (isset($_SESSION['jogadorTime'])) {
        if ($_SESSION['jogadorTime'] == 2) {
            $correct_extension = 'ymt';
            $max_file_size = 400000;
            $arquivo_tratamento = "/times/tratamento_time.php";
        } else if ($_SESSION['jogadorTime'] == 1) {
            $correct_extension = 'jog';
            $max_file_size = 2400;
            $arquivo_tratamento = "/jogadores/tratamento_jogador.php";
        } else if ($_SESSION['jogadorTime'] == 3) {
            $correct_extension = 'tda';
            $max_file_size = 400;
            $arquivo_tratamento = "/arbitros/tratamento_arbitro.php";
        } else if ($_SESSION['jogadorTime'] == 4) {
            $arquivo_tratamento = "/ranking/tratamento_jogo.php";
            $correct_extension = 'hyl';
            $max_file_size = 400000;
        } else if ($_SESSION['jogadorTime'] == 5) {
            $arquivo_tratamento = "/import/tratamento_tecnico.php";
            $correct_extension = 'tec';
            $max_file_size = 2400;
        } else if ($_SESSION['jogadorTime'] == 6) {
            $arquivo_tratamento = "/import/tratamento_estadio.php";
            $correct_extension = 'est';
            $correct_extension = 'est';
            $max_file_size = 2000;
        } else if ($_SESSION['jogadorTime'] == 7) {
            $arquivo_tratamento = "/ligas/gerenciador/tratamento_jogo.php";
            $correct_extension = 'hyl';
            $max_file_size = 400000;
        }
    }

    $upload_success = null;
    $upload_error = '';
    $is_success = false;
    if (isset($_POST['ligaselecionada'])) {
        $ligaSelecionada = $_POST['ligaselecionada'];
        $paisLigaSelecionada = $_POST['paisligaselecionada'];
    } else {
        $ligaSelecionada = null;
    }

    if (isset($_POST['timeselecionado'])) {
        $timeSelecionado = $_POST['timeselecionado'];
    } else {
        $timeSelecionado = null;
    }

    if (isset($_POST['nacionalidade'])) {
        $nacionalidadeSelecionada = $_POST['nacionalidade'];
    } else {
        $nacionalidadeSelecionada = null;
    }



    if (!empty($_FILES['files'])) {

        $filesToUpload = array();
        $fileExt = [];
        $forbiddenFile = [];
        $fileSizeCheck = [];

        $j = 0;
        foreach ($_FILES['files']['name'] as $fileName) {
            $fileName = (string) $fileName;
            $fileExt = strtolower(substr($fileName, -3));
            $countOfDots = (int) substr_count($fileName, ".");

            if ($countOfDots > 01) {
                $forbiddenFile = 1;
            } else {
                $forbiddenFile = 0;
            }
            $filesToUpload[$j][1] = $fileExt;
            $filesToUpload[$j][2] = $forbiddenFile;
            $j++;
        }

        $j = 0;
        foreach ($_FILES['files']['size'] as $fileSize) {
            $filesToUpload[$j][3] = $fileSize;
            $j++;
        }

        $j = 0;
        foreach ($_FILES['files']['tmp_name'] as $tempName) {
            $filesToUpload[$j][0] = $tempName;
            $j++;
        }

        $j = 0;
        foreach ($_FILES['files']['name'] as $originalName) {
            $filesToUpload[$j][4] = $originalName;
            $j++;
        }

        //libxml_use_internal_errors(true);

        if (isset($_SESSION['jogadorTime']) && $_SESSION['jogadorTime'] == 4) {
            $parsedGames = [];
            $countriesList = [];

            // Buscar todos os países do sistema para o Select2 (sem filtro de ranqueável)
            $stmtCountries = $pais->read(null, null, false);
            while ($row = $stmtCountries->fetch(PDO::FETCH_ASSOC)) {
                $countriesList[] = [
                    'id' => (int) $row['id'],
                    'nome' => $row['nome'],
                    'sigla' => $row['sigla'],
                    'bandeira' => $row['bandeira']
                ];
            }

            for ($i = 0; $i <= count($filesToUpload) - 1; $i++) {
                $filePath = $filesToUpload[$i][0];
                $forbidden = $filesToUpload[$i][2];
                $importExt = $filesToUpload[$i][1];
                $importSize = $filesToUpload[$i][3];
                $originalName = $filesToUpload[$i][4];

                if ($filePath != "" && $forbidden == 0 && $importExt == $correct_extension && $importSize <= $max_file_size) {
                    $xml = json_decode(file_get_contents($filePath));
                    if ($xml === null) {
                        $parsedGames[] = [
                            'filename' => $originalName,
                            'error' => 'Arquivo JSON inválido'
                        ];
                        continue;
                    }

                    $nome_pais_A = isset($xml->time1) ? (string) $xml->time1 : '';
                    $nome_pais_B = isset($xml->time2) ? (string) $xml->time2 : '';

                    $timeA_id = $pais->idPorNomeTratado($nome_pais_A);
                    $timeB_id = $pais->idPorNomeTratado($nome_pais_B);

                    $timeA_bandeira = $timeA_id ? $pais->bandeiraPorId($timeA_id) : '-';
                    $timeB_bandeira = $timeB_id ? $pais->bandeiraPorId($timeB_id) : '-';

                    // Extração de data do arquivo
                    $tempName = substr($originalName, 10);
                    $explodedName = explode(".", $tempName);
                    $game_date = date("Y-m-d", strtotime($explodedName[0]));
                    if ($game_date == '1970-01-01' || !$game_date) {
                        $explodedName = explode(".", $originalName);
                        $game_date = date("Y-m-d", strtotime($explodedName[0]));
                    }

                    $is_duplicate = false;
                    $existing_id = null;
                    if ($timeA_id && $timeB_id) {
                        $jogo->timeA_id = $timeA_id;
                        $jogo->timeB_id = $timeB_id;
                        $jogo->data = $game_date;
                        $existing_id = $jogo->getMatchId();
                        if ($existing_id) {
                            $is_duplicate = true;
                        }
                    }

                    $escalacaoTime1 = [];
                    if (isset($xml->escalacaoTime1) && is_array($xml->escalacaoTime1)) {
                        foreach ($xml->escalacaoTime1 as $p) {
                            if (isset($p->id) && isset($p->nome)) {
                                $escalacaoTime1[] = ['id' => (int) $p->id, 'nome' => (string) $p->nome];
                            }
                        }
                    }
                    $escalacaoTime2 = [];
                    if (isset($xml->escalacaoTime2) && is_array($xml->escalacaoTime2)) {
                        foreach ($xml->escalacaoTime2 as $p) {
                            if (isset($p->id) && isset($p->nome)) {
                                $escalacaoTime2[] = ['id' => (int) $p->id, 'nome' => (string) $p->nome];
                            }
                        }
                    }

                    $eventos = [];
                    if (isset($xml->eventos) && is_array($xml->eventos)) {
                        foreach ($xml->eventos as $ev) {
                            if (isset($ev->tempo) && isset($ev->minutos) && isset($ev->tipoEvento) && isset($ev->idJogador)) {
                                $eventos[] = [
                                    'tempo' => (string) $ev->tempo,
                                    'minutos' => (int) $ev->minutos,
                                    'tipoEvento' => (string) $ev->tipoEvento,
                                    'idJogador' => (int) $ev->idJogador
                                ];
                            }
                        }
                    }

                    $parsedGames[] = [
                        'filename' => $originalName,
                        'data' => $game_date,
                        'estadio' => isset($xml->estadio) ? (string) $xml->estadio : '',
                        'campeonato' => (int) $campeonato_jogo_import,
                        'fase' => (int) $fase_jogo_import,
                        'time1_raw' => $nome_pais_A,
                        'time2_raw' => $nome_pais_B,
                        'timeA_id' => $timeA_id ? (int) $timeA_id : null,
                        'timeA_bandeira' => $timeA_bandeira,
                        'timeB_id' => $timeB_id ? (int) $timeB_id : null,
                        'timeB_bandeira' => $timeB_bandeira,
                        'placarTime1' => isset($xml->placarTime1) ? (int) $xml->placarTime1 : 0,
                        'placarTime2' => isset($xml->placarTime2) ? (int) $xml->placarTime2 : 0,
                        'placarProrrogacaoTime1' => isset($xml->placarProrrogacaoTime1) ? (int) $xml->placarProrrogacaoTime1 : -1,
                        'placarProrrogacaoTime2' => isset($xml->placarProrrogacaoTime2) ? (int) $xml->placarProrrogacaoTime2 : -1,
                        'placarPenaltisTime1' => isset($xml->placarPenaltisTime1) ? (int) $xml->placarPenaltisTime1 : -1,
                        'placarPenaltisTime2' => isset($xml->placarPenaltisTime2) ? (int) $xml->placarPenaltisTime2 : -1,
                        'escalacaoTime1' => $escalacaoTime1,
                        'escalacaoTime2' => $escalacaoTime2,
                        'eventos' => $eventos,
                        'is_duplicate' => $is_duplicate,
                        'existing_id' => $existing_id
                    ];
                } else {
                    $parsedGames[] = [
                        'filename' => $originalName,
                        'error' => 'Arquivo inválido ou excedeu o tamanho máximo.'
                    ];
                }
            }

            $php_output = ob_get_clean();
            die(json_encode([
                'success' => true,
                'phase' => 1,
                'games' => $parsedGames,
                'countries' => $countriesList,
                'php_output' => $php_output
            ]));
        }

        $filePath = "";
        for ($i = 0; $i <= count($filesToUpload) - 1; $i++) {
            $filePath = $filesToUpload[$i][0];
            $forbidden = $filesToUpload[$i][2];
            $importExt = $filesToUpload[$i][1];
            $importSize = $filesToUpload[$i][3];
            $originalName = $filesToUpload[$i][4];
            $error_msg = '';

            if ($filePath != "" && $forbidden == 0 && $importExt == $correct_extension && $importSize <= $max_file_size) {

                if ($_SESSION['jogadorTime'] == 4 || $_SESSION['jogadorTime'] == 7) {
                    $xml = json_decode(file_get_contents($filePath));
                } else {
                    if (simplexml_load_string(file_get_contents($filePath)) == false) {
                        $xml = simplexml_load_string(utf8_encode(file_get_contents($filePath)));

                    } else {
                        $xml = simplexml_load_string(file_get_contents($filePath));
                    }
                    $usuario->atualizarAlteracao($_SESSION['user_id']);
                }

                $is_admin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == '1' && $_SESSION['impersonated'] == false);
                if ($is_admin && ($_SESSION['jogadorTime'] == 1 || $_SESSION['jogadorTime'] == 2)) {
                    // Parse players and build matching list
                    $players_to_match = [];
                    
                    // Determine players list based on import type
                    $imported_players = [];
                    if ($_SESSION['jogadorTime'] == 1) {
                        $imported_players[] = [
                            'xml_index' => 0,
                            'nome' => (string)$xml->jogador->Nome,
                            'idade' => (int)$xml->jogador->Idade,
                            'nivel' => (int)$xml->jogador->Nivel
                        ];
                    } else {
                        $total_de_jogadores = $xml->elenco->Jogador->int->count();
                        for ($j = 0; $j < $total_de_jogadores; $j++) {
                            $imported_players[] = [
                                'xml_index' => $j,
                                'nome' => (string)$xml->jogadores->jogador[$j]->Nome,
                                'idade' => (int)$xml->jogadores->jogador[$j]->Idade,
                                'nivel' => (int)$xml->jogadores->jogador[$j]->Nivel
                            ];
                        }
                    }

                    // Get country_id
                    $country_id = null;
                    if ($_SESSION['jogadorTime'] == 1) {
                        if ($timeSelecionado) {
                            $stmt_time = $db->prepare("SELECT Pais FROM clube WHERE ID = ?");
                            $stmt_time->execute([$timeSelecionado]);
                            $country_id = $stmt_time->fetchColumn();
                        }
                    } else {
                        if (isset($paisLigaSelecionada) && $paisLigaSelecionada != 0) {
                            $country_id = $paisLigaSelecionada;
                        } else {
                            $teste_pais = array();
                            if (isset($xml->nacionalidades->string)) {
                                foreach($xml->nacionalidades->string as $pais_provavel){
                                    $teste_pais[] = (string)$pais_provavel;
                                }
                            }
                            if (!empty($teste_pais)) {
                                $pais_recorrente = array_count_values($teste_pais);
                                arsort($pais_recorrente);
                                $pais_real = array_slice(array_keys($pais_recorrente),0,1,true);
                                if (isset($pais_real[0])) {
                                    $bandeiraImport = explode(".",$pais_real[0])[0];
                                    if($bandeiraImport <> '-'){
                                        $country_id = $pais->idPorBandeira($bandeiraImport);
                                    }
                                }
                            }
                        }
                    }

                    // Find potential matches for each player
                    foreach ($imported_players as $ip) {
                        $words = explode(' ', $ip['nome']);
                        $clauses = [];
                        $params = [];
                        
                        if ($country_id) {
                            $clauses[] = "Pais = ?";
                            $params[] = $country_id;
                        }
                        
                        $name_clauses = [];
                        $params_similar = [];
                        foreach ($words as $w) {
                            $w = trim($w);
                            if (strlen($w) > 2) {
                                $name_clauses[] = "Nome LIKE ?";
                                $params_similar[] = '%' . $w . '%';
                            }
                        }
                        
                        $matches = [];
                        
                        // 1. Search for exact name match
                        $sql_exact = "SELECT j.ID, j.Nome, j.Nivel, j.Nascimento, p.Bandeira, p.Nome AS NomePais FROM jogador j LEFT JOIN paises p ON j.Pais = p.id WHERE j.Nome = ?";
                        $params_exact = [$ip['nome']];
                        $stmt_exact = $db->prepare($sql_exact);
                        $stmt_exact->execute($params_exact);
                        $exact_matches = $stmt_exact->fetchAll(PDO::FETCH_ASSOC);
                        
                        // 2. Search for similar names (fuzzy LIKE)
                        $similar_matches = [];
                        if (!empty($name_clauses)) {
                            // Map "Nome LIKE ?" to "j.Nome LIKE ?"
                            $j_name_clauses = array_map(function($clause) {
                                return "j." . $clause;
                            }, $name_clauses);
                            $sql_similar = "SELECT j.ID, j.Nome, j.Nivel, j.Nascimento, p.Bandeira, p.Nome AS NomePais FROM jogador j LEFT JOIN paises p ON j.Pais = p.id WHERE " . implode(' OR ', $j_name_clauses) . " LIMIT 10";
                            $stmt_similar = $db->prepare($sql_similar);
                            $stmt_similar->execute($params_similar);
                            $similar_matches = $stmt_similar->fetchAll(PDO::FETCH_ASSOC);
                        }
                        
                        // Merge and keep unique players (exact first)
                        $merged = array_merge($exact_matches, $similar_matches);
                        $seen_ids = [];
                        foreach ($merged as $m) {
                            if (!in_array($m['ID'], $seen_ids)) {
                                $seen_ids[] = $m['ID'];
                                $matches[] = $m;
                            }
                        }
                        
                        // Limit to top 5
                        $matches = array_slice($matches, 0, 5);
                        
                        $players_to_match[] = [
                            'xml_index' => $ip['xml_index'],
                            'nome' => $ip['nome'],
                            'idade' => $ip['idade'],
                            'nivel' => $ip['nivel'],
                            'matches' => $matches
                        ];
                    }

                    // Find potential matches for team, coach, and stadium (only for team imports, type == 2)
                    $team_matches_data = null;
                    if ($_SESSION['jogadorTime'] == 2) {
                        // Team matches
                        $t_name = (string)$xml->clube->Nome;
                        $t_sigla = (string)$xml->clube->TresLetras;
                        $stmt_t = $db->prepare("SELECT ID, Nome FROM clube WHERE Nome = ? OR TresLetras = ?");
                        $stmt_t->execute([$t_name, $t_sigla]);
                        $t_matches = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

                        // Coach matches
                        $c_name = trim(preg_replace('/\s*\[.*?\]\s*$/', '', (string)$xml->tecnico->Nome));
                        $stmt_c_exact = $db->prepare("SELECT ID, Nome FROM tecnico WHERE Nome = ?");
                        $stmt_c_exact->execute([$c_name]);
                        $c_exact = $stmt_c_exact->fetchAll(PDO::FETCH_ASSOC);
                        $stmt_c_fuzzy = $db->prepare("SELECT ID, Nome FROM tecnico WHERE Nome LIKE ?");
                        $stmt_c_fuzzy->execute(['%' . $c_name . '%']);
                        $c_fuzzy = $stmt_c_fuzzy->fetchAll(PDO::FETCH_ASSOC);
                        $c_matches = array_slice(array_unique(array_merge($c_exact, $c_fuzzy), SORT_REGULAR), 0, 5);

                        // Stadium matches
                        $s_name = (string)$xml->estadio->Nome;
                        $stmt_s_exact = $db->prepare("SELECT ID, Nome FROM estadio WHERE Nome = ?");
                        $stmt_s_exact->execute([$s_name]);
                        $s_exact = $stmt_s_exact->fetchAll(PDO::FETCH_ASSOC);
                        $stmt_s_fuzzy = $db->prepare("SELECT ID, Nome FROM estadio WHERE Nome LIKE ?");
                        $stmt_s_fuzzy->execute(['%' . $s_name . '%']);
                        $s_fuzzy = $stmt_s_fuzzy->fetchAll(PDO::FETCH_ASSOC);
                        $s_matches = array_slice(array_unique(array_merge($s_exact, $s_fuzzy), SORT_REGULAR), 0, 5);

                        $team_matches_data = [
                            'clube' => ['nome' => $t_name, 'matches' => $t_matches],
                            'tecnico' => ['nome' => $c_name, 'matches' => $c_matches],
                            'estadio' => ['nome' => $s_name, 'matches' => $s_matches]
                        ];
                    }

                    // Save to session
                    $_SESSION['pending_import'] = [
                        'xml_content' => file_get_contents($filePath),
                        'type' => $_SESSION['jogadorTime'],
                        'liga' => $ligaSelecionada,
                        'time' => $timeSelecionado,
                        'sexo' => $sexo,
                        'nacionalidade' => $nacionalidadeSelecionada,
                        'pais_liga_selecionada' => isset($paisLigaSelecionada) ? $paisLigaSelecionada : null,
                        'players' => $players_to_match,
                        'team_matches' => $team_matches_data
                    ];

                    $is_success = true;
                    $php_output = ob_get_clean();
                    die(json_encode([
                        'success' => true,
                        'require_association' => true,
                        'redirect' => '/jogadores/associar_importados.php',
                        'php_output' => $php_output
                    ]));
                } else {
                    include($_SERVER['DOCUMENT_ROOT'] . $arquivo_tratamento);
                }

                if ($xml === false) {
                    foreach (libxml_get_errors() as $error) {
                        echo "\t", $error->message;
                    }
                }
            } else {
                if ($filePath == "") {
                    $error_msg .= "Nome " . $filePath . " inválido. ";
                }
                if ($forbidden == 1) {
                    $error_msg .= "Nome com muitos pontos. ";
                }
                if ($importExt != $correct_extension) {
                    $error_msg .= "Extensão " . $importExt . " incorreta. ";
                }
                if ($importSize > $max_file_size) {
                    $error_msg .= "Arquivo muito grande. ";
                }

            }


        }

        $php_output = ob_get_clean();
        die(json_encode(['success' => $is_success, 'error' => $error_msg, 'php_output' => $php_output]));

    }
}



?>