<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Apenas administradores logados
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)($_SESSION['admin_status'] ?? 0) !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas administradores podem acessar esta API.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

/**
 * Atualiza automaticamente os contadores em gen_origens com base nos registros reais.
 */
function sincronizarContadoresOrigem(PDO $db, int $idOrigem): void {
    if ($idOrigem <= 0) {
        return;
    }

    $stmtNomes = $db->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN M = 1 THEN 1 ELSE 0 END), 0) AS totalNomeM,
            COALESCE(SUM(CASE WHEN F = 1 THEN 1 ELSE 0 END), 0) AS totalNomeF
        FROM gen_nomes 
        WHERE Origem = ?
    ");
    $stmtNomes->execute([$idOrigem]);
    $countsNomes = $stmtNomes->fetch(PDO::FETCH_ASSOC);

    $stmtSobrenomes = $db->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN M = 1 THEN 1 ELSE 0 END), 0) AS totalSobrenomeM,
            COALESCE(SUM(CASE WHEN F = 1 THEN 1 ELSE 0 END), 0) AS totalSobrenomeF
        FROM gen_sobrenomes 
        WHERE Origem = ?
    ");
    $stmtSobrenomes->execute([$idOrigem]);
    $countsSobrenomes = $stmtSobrenomes->fetch(PDO::FETCH_ASSOC);

    $stmtUpdate = $db->prepare("
        UPDATE gen_origens 
        SET 
            nomeM = :nomeM,
            nomeF = :nomeF,
            sobrenomeM = :sobrenomeM,
            sobrenomeF = :sobrenomeF
        WHERE ID = :id
    ");

    $stmtUpdate->execute([
        ':nomeM' => (int)($countsNomes['totalNomeM'] ?? 0),
        ':nomeF' => (int)($countsNomes['totalNomeF'] ?? 0),
        ':sobrenomeM' => (int)($countsSobrenomes['totalSobrenomeM'] ?? 0),
        ':sobrenomeF' => (int)($countsSobrenomes['totalSobrenomeF'] ?? 0),
        ':id' => $idOrigem
    ]);
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

try {
    switch ($action) {

        // ==========================================
        // 1. CRIAR NOVA ORIGEM
        // ==========================================
        case 'criar_origem':
            $nomeOrigem = trim((string)($_POST['origem'] ?? ''));
            if ($nomeOrigem === '') {
                throw new InvalidArgumentException('O nome da origem não pode estar vazio.');
            }

            // Verificar duplicidade
            $stmtCheck = $db->prepare("SELECT ID FROM gen_origens WHERE LOWER(Origem) = LOWER(?) LIMIT 1");
            $stmtCheck->execute([$nomeOrigem]);
            if ($stmtCheck->fetch()) {
                throw new InvalidArgumentException('Já existe uma origem cadastrada com este nome.');
            }

            $stmtInsert = $db->prepare("INSERT INTO gen_origens (Origem, nomeM, nomeF, sobrenomeM, sobrenomeF) VALUES (?, 0, 0, 0, 0)");
            $stmtInsert->execute([$nomeOrigem]);
            $novoId = (int)$db->lastInsertId();

            echo json_encode([
                'success' => true,
                'id' => $novoId,
                'origem' => $nomeOrigem,
                'message' => 'Origem criada com sucesso!'
            ]);
            break;

        // ==========================================
        // 2. RENOMEAR ORIGEM
        // ==========================================
        case 'renomear_origem':
            $idOrigem = (int)($_POST['id'] ?? 0);
            $novoNome = trim((string)($_POST['origem'] ?? ''));

            if ($idOrigem <= 0 || $novoNome === '') {
                throw new InvalidArgumentException('ID e novo nome da origem são obrigatórios.');
            }

            // Verificar duplicidade com outra origem
            $stmtCheck = $db->prepare("SELECT ID FROM gen_origens WHERE LOWER(Origem) = LOWER(?) AND ID != ? LIMIT 1");
            $stmtCheck->execute([$novoNome, $idOrigem]);
            if ($stmtCheck->fetch()) {
                throw new InvalidArgumentException('Já existe outra origem com este nome.');
            }

            $stmtUpdate = $db->prepare("UPDATE gen_origens SET Origem = ? WHERE ID = ?");
            $stmtUpdate->execute([$novoNome, $idOrigem]);

            echo json_encode([
                'success' => true,
                'origem' => $novoNome,
                'message' => 'Nome da origem atualizado com sucesso!'
            ]);
            break;

        // ==========================================
        // 3. EXCLUIR ORIGEM
        // ==========================================
        case 'excluir_origem':
            $idOrigem = (int)($_POST['id'] ?? 0);
            if ($idOrigem <= 0) {
                throw new InvalidArgumentException('ID de origem inválido.');
            }

            // Verificar se países usam essa origem
            $stmtDem = $db->prepare("
                SELECT d.pais, p.nome 
                FROM demografia d 
                LEFT JOIN paises p ON p.id = d.pais 
                WHERE d.origem = ?
            ");
            $stmtDem->execute([$idOrigem]);
            $paisesVinculados = $stmtDem->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($paisesVinculados)) {
                $nomesPaises = array_map(function($p) { return $p['nome'] ?? ('ID ' . $p['pais']); }, $paisesVinculados);
                throw new InvalidArgumentException('Não é possível excluir esta origem porque ela está em uso nos seguintes países: ' . implode(', ', $nomesPaises) . '. Remova a fatia demográfica desses países primeiro.');
            }

            // Excluir nomes, sobrenomes e a própria origem
            $db->beginTransaction();
            $stmtDelNomes = $db->prepare("DELETE FROM gen_nomes WHERE Origem = ?");
            $stmtDelNomes->execute([$idOrigem]);

            $stmtDelSobrenomes = $db->prepare("DELETE FROM gen_sobrenomes WHERE Origem = ?");
            $stmtDelSobrenomes->execute([$idOrigem]);

            $stmtDelOrigem = $db->prepare("DELETE FROM gen_origens WHERE ID = ?");
            $stmtDelOrigem->execute([$idOrigem]);
            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Origem e todo o seu acervo foram excluídos com sucesso.'
            ]);
            break;

        // ==========================================
        // 4. LISTAR NOMES DE UMA ORIGEM
        // ==========================================
        case 'listar_nomes':
            $idOrigem = (int)($_GET['origem'] ?? 0);
            $busca = trim((string)($_GET['busca'] ?? ''));
            $genero = (string)($_GET['genero'] ?? 'todos');
            $pagina = max(1, (int)($_GET['pagina'] ?? 1));
            $limite = 50;
            $offset = ($pagina - 1) * $limite;

            $where = ['Origem = :origem'];
            $params = [':origem' => $idOrigem];

            if ($busca !== '') {
                $where[] = 'Nome LIKE :busca';
                $params[':busca'] = '%' . $busca . '%';
            }

            if ($genero === 'm') {
                $where[] = 'M = 1';
            } elseif ($genero === 'f') {
                $where[] = 'F = 1';
            } elseif ($genero === 'ambos') {
                $where[] = 'M = 1 AND F = 1';
            } elseif ($genero === 'somente_m') {
                $where[] = 'M = 1 AND F = 0';
            } elseif ($genero === 'somente_f') {
                $where[] = 'M = 0 AND F = 1';
            }

            $whereSql = implode(' AND ', $where);

            // Total
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM gen_nomes WHERE {$whereSql}");
            $stmtCount->execute($params);
            $totalRegistros = (int)$stmtCount->fetchColumn();

            // Lista paginada
            $stmt = $db->prepare("
                SELECT ID, Nome, Origem, M, F 
                FROM gen_nomes 
                WHERE {$whereSql} 
                ORDER BY Nome ASC 
                LIMIT {$limite} OFFSET {$offset}
            ");
            $stmt->execute($params);
            $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'total' => $totalRegistros,
                'pagina' => $pagina,
                'total_paginas' => (int)ceil($totalRegistros / $limite),
                'itens' => $itens
            ]);
            break;

        // ==========================================
        // 5. ADICIONAR NOME
        // ==========================================
        case 'adicionar_nome':
            $idOrigem = (int)($_POST['origem'] ?? 0);
            $nome = trim((string)($_POST['nome'] ?? ''));
            $m = (int)($_POST['m'] ?? 0) === 1 ? 1 : 0;
            $f = (int)($_POST['f'] ?? 0) === 1 ? 1 : 0;

            if ($idOrigem <= 0 || $nome === '') {
                throw new InvalidArgumentException('Origem e Nome são obrigatórios.');
            }
            if ($m === 0 && $f === 0) {
                throw new InvalidArgumentException('O nome deve ser válido para ao menos um gênero (Masculino ou Feminino).');
            }

            // Verificar duplicata na mesma origem
            $stmtCheck = $db->prepare("SELECT ID FROM gen_nomes WHERE LOWER(Nome) = LOWER(?) AND Origem = ? LIMIT 1");
            $stmtCheck->execute([$nome, $idOrigem]);
            if ($stmtCheck->fetch()) {
                throw new InvalidArgumentException('Este nome já está cadastrado nesta origem.');
            }

            $stmtInsert = $db->prepare("INSERT INTO gen_nomes (Nome, Origem, M, F) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$nome, $idOrigem, $m, $f]);
            $novoId = (int)$db->lastInsertId();

            sincronizarContadoresOrigem($db, $idOrigem);

            echo json_encode([
                'success' => true,
                'id' => $novoId,
                'nome' => $nome,
                'm' => $m,
                'f' => $f,
                'message' => 'Nome cadastrado com sucesso!'
            ]);
            break;

        // ==========================================
        // 6. EDITAR NOME
        // ==========================================
        case 'editar_nome':
            $id = (int)($_POST['id'] ?? 0);
            $idOrigem = (int)($_POST['origem'] ?? 0);
            $nome = trim((string)($_POST['nome'] ?? ''));
            $m = (int)($_POST['m'] ?? 0) === 1 ? 1 : 0;
            $f = (int)($_POST['f'] ?? 0) === 1 ? 1 : 0;

            if ($id <= 0 || $idOrigem <= 0 || $nome === '') {
                throw new InvalidArgumentException('Parâmetros inválidos para edição.');
            }
            if ($m === 0 && $f === 0) {
                throw new InvalidArgumentException('O nome deve ser válido para ao menos um gênero.');
            }

            // Verificar duplicidade com outro ID
            $stmtCheck = $db->prepare("SELECT ID FROM gen_nomes WHERE LOWER(Nome) = LOWER(?) AND Origem = ? AND ID != ? LIMIT 1");
            $stmtCheck->execute([$nome, $idOrigem, $id]);
            if ($stmtCheck->fetch()) {
                throw new InvalidArgumentException('Outro registro já utiliza este nome nesta origem.');
            }

            $stmtUpdate = $db->prepare("UPDATE gen_nomes SET Nome = ?, M = ?, F = ? WHERE ID = ? AND Origem = ?");
            $stmtUpdate->execute([$nome, $m, $f, $id, $idOrigem]);

            sincronizarContadoresOrigem($db, $idOrigem);

            echo json_encode([
                'success' => true,
                'message' => 'Nome atualizado com sucesso!'
            ]);
            break;

        // ==========================================
        // 7. EXCLUIR NOME
        // ==========================================
        case 'excluir_nome':
            $id = (int)($_POST['id'] ?? 0);
            $idOrigem = (int)($_POST['origem'] ?? 0);

            if ($id <= 0 || $idOrigem <= 0) {
                throw new InvalidArgumentException('ID inválido para exclusão.');
            }

            $stmtDel = $db->prepare("DELETE FROM gen_nomes WHERE ID = ? AND Origem = ?");
            $stmtDel->execute([$id, $idOrigem]);

            sincronizarContadoresOrigem($db, $idOrigem);

            echo json_encode([
                'success' => true,
                'message' => 'Nome excluído com sucesso.'
            ]);
            break;

        // ==========================================
        // 8. LISTAR SOBRENOMES DE UMA ORIGEM
        // ==========================================
        case 'listar_sobrenomes':
            $idOrigem = (int)($_GET['origem'] ?? 0);
            $busca = trim((string)($_GET['busca'] ?? ''));
            $genero = (string)($_GET['genero'] ?? 'todos');
            $pagina = max(1, (int)($_GET['pagina'] ?? 1));
            $limite = 50;
            $offset = ($pagina - 1) * $limite;

            $where = ['Origem = :origem'];
            $params = [':origem' => $idOrigem];

            if ($busca !== '') {
                $where[] = 'Sobrenome LIKE :busca';
                $params[':busca'] = '%' . $busca . '%';
            }

            if ($genero === 'm') {
                $where[] = 'M = 1';
            } elseif ($genero === 'f') {
                $where[] = 'F = 1';
            } elseif ($genero === 'ambos') {
                $where[] = 'M = 1 AND F = 1';
            } elseif ($genero === 'somente_m') {
                $where[] = 'M = 1 AND F = 0';
            } elseif ($genero === 'somente_f') {
                $where[] = 'M = 0 AND F = 1';
            }

            $whereSql = implode(' AND ', $where);

            // Total
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM gen_sobrenomes WHERE {$whereSql}");
            $stmtCount->execute($params);
            $totalRegistros = (int)$stmtCount->fetchColumn();

            // Lista paginada
            $stmt = $db->prepare("
                SELECT ID, Sobrenome, Origem, M, F 
                FROM gen_sobrenomes 
                WHERE {$whereSql} 
                ORDER BY Sobrenome ASC 
                LIMIT {$limite} OFFSET {$offset}
            ");
            $stmt->execute($params);
            $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'total' => $totalRegistros,
                'pagina' => $pagina,
                'total_paginas' => (int)ceil($totalRegistros / $limite),
                'itens' => $itens
            ]);
            break;

        // ==========================================
        // 9. ADICIONAR SOBRENOME
        // ==========================================
        case 'adicionar_sobrenome':
            $idOrigem = (int)($_POST['origem'] ?? 0);
            $sobrenome = trim((string)($_POST['sobrenome'] ?? ''));
            $m = (int)($_POST['m'] ?? 0) === 1 ? 1 : 0;
            $f = (int)($_POST['f'] ?? 0) === 1 ? 1 : 0;

            if ($idOrigem <= 0 || $sobrenome === '') {
                throw new InvalidArgumentException('Origem e Sobrenome são obrigatórios.');
            }
            if ($m === 0 && $f === 0) {
                throw new InvalidArgumentException('O sobrenome deve ser válido para ao menos um gênero.');
            }

            // Verificar duplicata
            $stmtCheck = $db->prepare("SELECT ID FROM gen_sobrenomes WHERE LOWER(Sobrenome) = LOWER(?) AND Origem = ? LIMIT 1");
            $stmtCheck->execute([$sobrenome, $idOrigem]);
            if ($stmtCheck->fetch()) {
                throw new InvalidArgumentException('Este sobrenome já está cadastrado nesta origem.');
            }

            $stmtInsert = $db->prepare("INSERT INTO gen_sobrenomes (Sobrenome, Origem, M, F) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$sobrenome, $idOrigem, $m, $f]);
            $novoId = (int)$db->lastInsertId();

            sincronizarContadoresOrigem($db, $idOrigem);

            echo json_encode([
                'success' => true,
                'id' => $novoId,
                'sobrenome' => $sobrenome,
                'm' => $m,
                'f' => $f,
                'message' => 'Sobrenome cadastrado com sucesso!'
            ]);
            break;

        // ==========================================
        // 10. EDITAR SOBRENOME
        // ==========================================
        case 'editar_sobrenome':
            $id = (int)($_POST['id'] ?? 0);
            $idOrigem = (int)($_POST['origem'] ?? 0);
            $sobrenome = trim((string)($_POST['sobrenome'] ?? ''));
            $m = (int)($_POST['m'] ?? 0) === 1 ? 1 : 0;
            $f = (int)($_POST['f'] ?? 0) === 1 ? 1 : 0;

            if ($id <= 0 || $idOrigem <= 0 || $sobrenome === '') {
                throw new InvalidArgumentException('Parâmetros inválidos para edição.');
            }
            if ($m === 0 && $f === 0) {
                throw new InvalidArgumentException('O sobrenome deve ser válido para ao menos um gênero.');
            }

            $stmtCheck = $db->prepare("SELECT ID FROM gen_sobrenomes WHERE LOWER(Sobrenome) = LOWER(?) AND Origem = ? AND ID != ? LIMIT 1");
            $stmtCheck->execute([$sobrenome, $idOrigem, $id]);
            if ($stmtCheck->fetch()) {
                throw new InvalidArgumentException('Outro registro já utiliza este sobrenome nesta origem.');
            }

            $stmtUpdate = $db->prepare("UPDATE gen_sobrenomes SET Sobrenome = ?, M = ?, F = ? WHERE ID = ? AND Origem = ?");
            $stmtUpdate->execute([$sobrenome, $m, $f, $id, $idOrigem]);

            sincronizarContadoresOrigem($db, $idOrigem);

            echo json_encode([
                'success' => true,
                'message' => 'Sobrenome atualizado com sucesso!'
            ]);
            break;

        // ==========================================
        // 11. EXCLUIR SOBRENOME
        // ==========================================
        case 'excluir_sobrenome':
            $id = (int)($_POST['id'] ?? 0);
            $idOrigem = (int)($_POST['origem'] ?? 0);

            if ($id <= 0 || $idOrigem <= 0) {
                throw new InvalidArgumentException('ID inválido para exclusão.');
            }

            $stmtDel = $db->prepare("DELETE FROM gen_sobrenomes WHERE ID = ? AND Origem = ?");
            $stmtDel->execute([$id, $idOrigem]);

            sincronizarContadoresOrigem($db, $idOrigem);

            echo json_encode([
                'success' => true,
                'message' => 'Sobrenome excluído com sucesso.'
            ]);
            break;

        // ==========================================
        // 12. IMPORTAÇÃO EM LOTE (BULK IMPORT)
        // ==========================================
        case 'importar_lote':
            $idOrigem = (int)($_POST['origem'] ?? 0);
            $tipo = (string)($_POST['tipo'] ?? 'nome'); // 'nome' ou 'sobrenome'
            $texto = (string)($_POST['texto'] ?? '');
            $m = (int)($_POST['m'] ?? 0) === 1 ? 1 : 0;
            $f = (int)($_POST['f'] ?? 0) === 1 ? 1 : 0;
            $autoEslavo = (int)($_POST['auto_eslavo'] ?? 0) === 1;

            if ($idOrigem <= 0) {
                throw new InvalidArgumentException('Origem inválida.');
            }
            if (!$autoEslavo && $m === 0 && $f === 0) {
                throw new InvalidArgumentException('Selecione ao menos um gênero (Masculino e/ou Feminino).');
            }

            // Função local de detecção de sufixos eslavos/russos
            $fnDetectarGeneroEslavo = function(string $termo, int $defaultM, int $defaultF): array {
                $t = mb_strtolower(trim($termo), 'UTF-8');

                // Sufixos Femininos Eslavos: -ova, -eva, -ina, -yna, -skaya, -skaia, -skaja, -tskaya, -tskaia, -aya, -aia, -aja, -ovna, -evna, -ová
                if (preg_match('/(ova|eva|ina|yna|skaya|skaia|skaja|tskaya|tskaia|aya|aia|aja|ovna|evna|ová)$/u', $t)) {
                    return ['m' => 0, 'f' => 1];
                }

                // Sufixos Masculinos Eslavos: -ov, -ev, -in, -yn, -sky, -skiy, -skii, -ski, -tsky, -tskiy, -tskii, -tsk, -ovs, -evs, -ins, -oy, -yj, -iy
                if (preg_match('/(ov|ev|in|yn|sky|skiy|skii|ski|tsky|tskiy|tskii|tsk|ovs|evs|ins|oy|yj|iy)$/u', $t)) {
                    return ['m' => 1, 'f' => 0];
                }

                // Invariáveis / Neutros ou outros: se não detectou sufixo flexionado, usa unissex ou o padrão
                $mFinal = ($defaultM === 0 && $defaultF === 0) ? 1 : $defaultM;
                $fFinal = ($defaultM === 0 && $defaultF === 0) ? 1 : $defaultF;
                return ['m' => $mFinal, 'f' => $fFinal];
            };

            // Normalizar quebras de linha e separadores (vírgula, ponto e vírgula, newline)
            $linhas = preg_split('/[\r\n,;]+/', $texto);
            $itensProcessar = [];

            foreach ($linhas as $linha) {
                $limpo = trim($linha);
                // Remove marcadores de lista, traços, bullets, numerações iniciais (ex: "- Nome", "• Nome", "* Nome", "1. Nome", "1 - Nome")
                $limpo = preg_replace('/^(\s*[-–—*•·+>]|\s*\d+[\.\)\-:\s])+\s*/u', '', $limpo);
                // Remove aspas ou caracteres estranhos nas pontas
                $limpo = preg_replace('/^["\']+|["\']+$/u', '', $limpo);
                $limpo = trim($limpo);
                if ($limpo !== '' && !in_array($limpo, $itensProcessar, true)) {
                    $itensProcessar[] = $limpo;
                }
            }

            if (empty($itensProcessar)) {
                throw new InvalidArgumentException('Nenhum termo válido foi encontrado no texto fornecido.');
            }

            $inseridos = 0;
            $duplicados = 0;

            $db->beginTransaction();

            if ($tipo === 'sobrenome') {
                $stmtCheck = $db->prepare("SELECT ID FROM gen_sobrenomes WHERE LOWER(Sobrenome) = LOWER(?) AND Origem = ? LIMIT 1");
                $stmtInsert = $db->prepare("INSERT INTO gen_sobrenomes (Sobrenome, Origem, M, F) VALUES (?, ?, ?, ?)");

                foreach ($itensProcessar as $item) {
                    $stmtCheck->execute([$item, $idOrigem]);
                    if ($stmtCheck->fetch()) {
                        $duplicados++;
                        continue;
                    }

                    $genItem = $autoEslavo ? $fnDetectarGeneroEslavo($item, $m, $f) : ['m' => $m, 'f' => $f];
                    $stmtInsert->execute([$item, $idOrigem, $genItem['m'], $genItem['f']]);
                    $inseridos++;
                }
            } else {
                $stmtCheck = $db->prepare("SELECT ID FROM gen_nomes WHERE LOWER(Nome) = LOWER(?) AND Origem = ? LIMIT 1");
                $stmtInsert = $db->prepare("INSERT INTO gen_nomes (Nome, Origem, M, F) VALUES (?, ?, ?, ?)");

                foreach ($itensProcessar as $item) {
                    $stmtCheck->execute([$item, $idOrigem]);
                    if ($stmtCheck->fetch()) {
                        $duplicados++;
                        continue;
                    }

                    $genItem = $autoEslavo ? $fnDetectarGeneroEslavo($item, $m, $f) : ['m' => $m, 'f' => $f];
                    $stmtInsert->execute([$item, $idOrigem, $genItem['m'], $genItem['f']]);
                    $inseridos++;
                }
            }

            $db->commit();

            sincronizarContadoresOrigem($db, $idOrigem);

            echo json_encode([
                'success' => true,
                'inseridos' => $inseridos,
                'duplicados' => $duplicados,
                'total_enviados' => count($itensProcessar),
                'message' => "Importação concluída! {$inseridos} registros inseridos com sucesso." . ($duplicados > 0 ? " ({$duplicados} ignorados por já existirem)." : "")
            ]);
            break;

        // ==========================================
        // 13. RECALCULAR TUDO (Manutenção em massa)
        // ==========================================
        case 'sincronizar_todas':
            $stmt = $db->query("SELECT ID FROM gen_origens");
            $origens = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($origens as $idO) {
                sincronizarContadoresOrigem($db, (int)$idO);
            }
            echo json_encode([
                'success' => true,
                'message' => 'Todos os contadores de origens foram sincronizados com sucesso!'
            ]);
            break;

        default:
            throw new InvalidArgumentException('Ação não reconhecida.');
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
