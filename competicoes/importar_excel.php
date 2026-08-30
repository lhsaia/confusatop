<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
header('Content-Type: application/json');

if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    die(json_encode(['success' => false, 'error' => 'Acesso negado.']));
}

$idCompeticao = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$idCompeticao) {
    die(json_encode(['success' => false, 'error' => 'ID da competição não fornecido.']));
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/lib/simplexlsx/SimpleXLSX.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/config/database.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/objetos/competicao_clube.php";

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

$compInfo = $competicaoObj->readInfo($idCompeticao);
if (!$compInfo) {
    die(json_encode(['success' => false, 'error' => 'Competição não encontrada.']));
}

// Check permission
$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);
$dono = isset($compInfo['dono']) ? (int)$compInfo['dono'] : 0;
if (!$isAdmin && $_SESSION['user_id'] != $dono) {
    die(json_encode(['success' => false, 'error' => 'Você não tem permissão para importar dados nesta competição.']));
}

use Shuchkin\SimpleXLSX;

if (isset($_FILES['planilha_excel']) && !empty($_FILES['planilha_excel'])) {
    $filePath = $_FILES['planilha_excel']['tmp_name'];
    $fileType = $_FILES['planilha_excel']['type'];
    
    $correct_extensions = [
        "application/vnd.ms-excel",
        "application/octet-stream",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    ];

    if ($filePath != "" && ($_FILES['planilha_excel']['size'] > 0)) {
        try {
            $xlsx = SimpleXLSX::parse($filePath);
            if (!$xlsx) {
                throw new Exception(SimpleXLSX::parseError());
            }
            $rawRows = $xlsx->rows();
            
            // Reindex to 1-based row and column letters A, B, C...
            $sheetData = [];
            $colLetters = range('A', 'Z');
            foreach ($rawRows as $rIdx => $rowValues) {
                $rowNum = $rIdx + 1;
                $sheetData[$rowNum] = [];
                foreach ($rowValues as $cIdx => $val) {
                    if (isset($colLetters[$cIdx])) {
                        $sheetData[$rowNum][$colLetters[$cIdx]] = $val;
                    }
                }
            }
            
            $db->beginTransaction();
            
            $currentRow = 9; // Data starts at row 9
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            while (true) {
                // If there's no data in row, stop
                $timeAId = isset($sheetData[$currentRow]['B']) ? trim($sheetData[$currentRow]['B']) : '';
                $timeBId = isset($sheetData[$currentRow]['E']) ? trim($sheetData[$currentRow]['E']) : '';
                if ($timeAId === '' && $timeBId === '') {
                    break;
                }
                
                $matchId = isset($sheetData[$currentRow]['A']) ? trim($sheetData[$currentRow]['A']) : '';
                $golsA = isset($sheetData[$currentRow]['D']) && trim($sheetData[$currentRow]['D']) !== '' ? intval($sheetData[$currentRow]['D']) : null;
                $golsB = isset($sheetData[$currentRow]['G']) && trim($sheetData[$currentRow]['G']) !== '' ? intval($sheetData[$currentRow]['G']) : null;
                $dataHora = isset($sheetData[$currentRow]['H']) ? trim($sheetData[$currentRow]['H']) : '';
                $fase = isset($sheetData[$currentRow]['I']) ? intval($sheetData[$currentRow]['I']) : 1;
                $grupo = isset($sheetData[$currentRow]['J']) && trim($sheetData[$currentRow]['J']) !== '' ? trim($sheetData[$currentRow]['J']) : null;
                $estadio = isset($sheetData[$currentRow]['K']) ? intval($sheetData[$currentRow]['K']) : 0;
                $arbitro = isset($sheetData[$currentRow]['L']) ? intval($sheetData[$currentRow]['L']) : 0;
                $neutro = isset($sheetData[$currentRow]['M']) ? intval($sheetData[$currentRow]['M']) : 0;
                $status = isset($sheetData[$currentRow]['N']) ? intval($sheetData[$currentRow]['N']) : 0;
                
                $timeA_id = intval($timeAId);
                $timeB_id = intval($timeBId);
                
                if ($timeA_id <= 0 || $timeB_id <= 0) {
                    $errors[] = "Linha {$currentRow}: IDs de time inválidos.";
                    $errorCount++;
                    $currentRow++;
                    continue;
                }
                
                if (empty($dataHora)) {
                    $errors[] = "Linha {$currentRow}: Data e hora vazias.";
                    $errorCount++;
                    $currentRow++;
                    continue;
                }
                
                $timeA_portal = ($timeA_id > 0) ? 1 : 0;
                $timeB_portal = ($timeB_id > 0) ? 1 : 0;
                
                if (!empty($matchId)) {
                    // UPDATE
                    $matchId = intval($matchId);
                    // Check if match belongs to this competition
                    $stmtCheck = $db->prepare("SELECT competicao_id FROM jogos_clube WHERE id = ?");
                    $stmtCheck->execute([$matchId]);
                    $dbCompId = $stmtCheck->fetchColumn();
                    
                    if ($dbCompId != $idCompeticao) {
                        $errors[] = "Linha {$currentRow}: O jogo ID {$matchId} não pertence a esta competição.";
                        $errorCount++;
                        $currentRow++;
                        continue;
                    }
                    
                    $query = "UPDATE jogos_clube 
                              SET timeA_id = :timeA, timeB_id = :timeB, timeA_gols = :golsA, timeB_gols = :golsB,
                                  data = :data, estadio_id = :estadio,
                                  neutro = :neutro, arbitro_id = :arbitro, fase = :fase, grupo = :grupo, status = :status
                              WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':timeA', $timeA_id);
                    $stmt->bindParam(':timeB', $timeB_id);
                    $stmt->bindValue(':golsA', $golsA, $golsA === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmt->bindValue(':golsB', $golsB, $golsB === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmt->bindParam(':data', $dataHora);
                    $stmt->bindParam(':estadio', $estadio);
                    $stmt->bindParam(':neutro', $neutro);
                    $stmt->bindParam(':arbitro', $arbitro);
                    $stmt->bindParam(':fase', $fase);
                    $stmt->bindValue(':grupo', $grupo, $grupo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':id', $matchId);
                    $stmt->execute();
                    $successCount++;
                } else {
                    // INSERT
                    $query = "INSERT INTO jogos_clube 
                              (timeA_id, timeB_id, timeA_gols, timeB_gols, data, competicao_id, estadio_id, neutro, arbitro_id, fase, grupo, status, competicao_tipo, simulador_interno, dono)
                              VALUES 
                              (:timeA, :timeB, :golsA, :golsB, :data, :competicao, :estadio, :neutro, :arbitro, :fase, :grupo, :status, 1, 1, :dono)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':timeA', $timeA_id);
                    $stmt->bindParam(':timeB', $timeB_id);
                    $stmt->bindValue(':golsA', $golsA, $golsA === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmt->bindValue(':golsB', $golsB, $golsB === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmt->bindParam(':data', $dataHora);
                    $stmt->bindParam(':competicao', $idCompeticao);
                    $stmt->bindParam(':estadio', $estadio);
                    $stmt->bindParam(':neutro', $neutro);
                    $stmt->bindParam(':arbitro', $arbitro);
                    $stmt->bindParam(':fase', $fase);
                    $stmt->bindValue(':grupo', $grupo, $grupo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':dono', $dono);
                    $stmt->execute();
                    $successCount++;
                }
                
                $currentRow++;
            }
            
            if ($errorCount > 0) {
                $db->rollBack();
                echo json_encode(['success' => false, 'error' => "Erro na validação da planilha:\n" . implode("\n", $errors)]);
            } else {
                $db->commit();
                echo json_encode(['success' => true, 'message' => "Tabela importada com sucesso! {$successCount} partidas processadas."]);
            }
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode(['success' => false, 'error' => 'Erro ao processar arquivo: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Arquivo inválido ou vazio.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Nenhum arquivo enviado.']);
}

