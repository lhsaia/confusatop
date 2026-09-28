<?php
ini_set('display_errors', false);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once "config/database.php";
require_once "classes/competition.php";

$competition_id = isset($_POST["competition_id"]) ? $_POST["competition_id"] : (isset($_GET["competition_id"]) ? $_GET["competition_id"] : "1");

$database = new OctamotorDatabase();
$db = $database->getConnection();

$competition = new Competition($db);

$competitions_list = $competition->getFullCompetitionList();
$drivers_wins = $competition->getStatsDriversWins($competition_id);
$drivers_podiums = $competition->getStatsDriversPodiums($competition_id);
$drivers_points = $competition->getStatsDriversPoints($competition_id);
$drivers_reliability = $competition->getStatsDriversReliability($competition_id);
$teams_wins = $competition->getStatsTeamsWins($competition_id);
$teams_points = $competition->getStatsTeamsPoints($competition_id);
$champions = $competition->getStatsChampions($competition_id);
$track_records = $competition->getStatsTrackRecords($competition_id);

echo json_encode([
    "success" => true,
    "competition_id" => $competition_id,
    "competitions_list" => $competitions_list,
    "drivers_wins" => $drivers_wins,
    "drivers_podiums" => $drivers_podiums,
    "drivers_points" => $drivers_points,
    "drivers_reliability" => $drivers_reliability,
    "teams_wins" => $teams_wins,
    "teams_points" => $teams_points,
    "champions" => $champions,
    "track_records" => $track_records
]);
exit;
