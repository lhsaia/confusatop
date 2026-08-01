<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    die("Acesso negado.");
}

$idCompeticao = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$idCompeticao) {
    die("ID da competição não fornecido.");
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/config/database.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/objetos/competicao_clube.php";

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

$compInfo = $competicaoObj->readInfo($idCompeticao);
if (!$compInfo) {
    die("Competição não encontrada.");
}

// Check permission
$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);
$dono = isset($compInfo['dono']) ? (int)$compInfo['dono'] : 0;
if (!$isAdmin && $_SESSION['user_id'] != $dono) {
    die("Você não tem permissão para exportar esta planilha.");
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Style configurations
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1469']],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
];

// Instructions block
$sheet->setCellValue('A1', "INSTRUÇÕES DE PREENCHIMENTO:");
$sheet->mergeCells('A1:N1');
$sheet->setCellValue('A2', "1. Coluna 'ID Partida': NÃO altere o ID de partidas existentes. Deixe em branco se quiser CADASTRAR um novo jogo.");
$sheet->mergeCells('A2:N2');
$sheet->setCellValue('A3', "2. Colunas 'Time A ID' e 'Time B ID': Use IDs de times válidos da competição. Os nomes são apenas informativos.");
$sheet->mergeCells('A3:N3');
$sheet->setCellValue('A4', "3. 'Data/Hora': Use o formato YYYY-MM-DD HH:MM:SS (Ex: 2026-08-05 15:30:00).");
$sheet->mergeCells('A4:N4');
$sheet->setCellValue('A5', "4. 'Neutro': Insira 0 para jogo com mando de campo do Time A, ou 1 para campo neutro.");
$sheet->mergeCells('A5:N5');
$sheet->setCellValue('A6', "5. Para salvar as alterações, salve o arquivo e faça o upload através do botão 'Importar Excel'.");
$sheet->mergeCells('A6:N6');

// Headers
$headers = [
    'ID Partida', 'Time A ID', 'Time A Nome', 'Gols A',
    'Time B ID', 'Time B Nome', 'Gols B',
    'Data/Hora', 'Fase ID', 'Grupo', 'Estádio ID', 'Árbitro ID', 'Neutro (0/1)', 'Status (0/1)'
];

$row = 8;
$colChar = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($colChar . $row, $h);
    $sheet->getColumnDimension($colChar)->setAutoSize(true);
    $colChar++;
}
$sheet->getStyle('A8:N8')->applyFromArray($headerStyle);

// Load matches
$stmt = $competicaoObj->carregarListaJogos($idCompeticao);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dataRow = 9;
foreach ($matches as $m) {
    $stmtTeamA = $db->prepare("SELECT Nome FROM clube WHERE ID = ?");
    $stmtTeamA->execute([$m['timeA_id']]);
    $tAName = $stmtTeamA->fetchColumn() ?: "Time #".$m['timeA_id'];

    $stmtTeamB = $db->prepare("SELECT Nome FROM clube WHERE ID = ?");
    $stmtTeamB->execute([$m['timeB_id']]);
    $tBName = $stmtTeamB->fetchColumn() ?: "Time #".$m['timeB_id'];

    $sheet->setCellValue('A' . $dataRow, $m['id']);
    $sheet->setCellValue('B' . $dataRow, $m['timeA_id']);
    $sheet->setCellValue('C' . $dataRow, $tAName);
    $sheet->setCellValue('D' . $dataRow, $m['timeA_gols']);
    $sheet->setCellValue('E' . $dataRow, $m['timeB_id']);
    $sheet->setCellValue('F' . $dataRow, $tBName);
    $sheet->setCellValue('G' . $dataRow, $m['timeB_gols']);
    $sheet->setCellValue('H' . $dataRow, $m['data']);
    $sheet->setCellValue('I' . $dataRow, $m['fase']);
    $sheet->setCellValue('J' . $dataRow, $m['grupo']);
    $sheet->setCellValue('K' . $dataRow, $m['estadio']);
    $sheet->setCellValue('L' . $dataRow, $m['arbitro']);
    $sheet->setCellValue('M' . $dataRow, $m['neutro']);
    $sheet->setCellValue('N' . $dataRow, $m['status']);
    
    // Style read-only columns (C, F)
    $sheet->getStyle('C' . $dataRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
    $sheet->getStyle('F' . $dataRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
    
    $dataRow++;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="tabela_competicao_' . $idCompeticao . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
