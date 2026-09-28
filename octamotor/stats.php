<?php
if (isset($_SERVER['DOCUMENT_ROOT'])) {
    $dir = __DIR__;
    while ($dir !== dirname($dir)) {
        if (file_exists($dir . '/config/session.php')) {
            $_SERVER['DOCUMENT_ROOT'] = $dir;
            break;
        }
        $dir = dirname($dir);
    }
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
?>
<!DOCTYPE html>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "OctaMotor - Estatísticas";
$css_filename = "home_redesign";
$aux_css = "driver_info";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/competition.php");

$octa_database = new OctamotorDatabase();
$odb = $octa_database->getConnection();
$competition = new Competition($odb);

$competitions_list = $competition->getFullCompetitionList();
?>

<div id='loadingDiv' style="display:none;"><img src='/octamotor/images/lights.gif'/></div>

<div id="container-home-octamotor">
  <div class="stats-page-container">
    <!-- Header / Controls -->
    <div class="stats-header-bar">
      <div class="stats-title-section">
        <span class="material-symbols-outlined stats-icon">query_stats</span>
        <div>
          <h1 class="stats-main-title">Estatísticas do Automobilismo</h1>
          <p class="stats-sub-title">Hall da Fama, recordes históricos, confiabilidade e hegemonia</p>
        </div>
      </div>
      
      <div class="stats-controls-section">
        <label for="select-stats-competition" class="stats-select-label">Competição / Categoria:</label>
        <select id="select-stats-competition" class="modern-stats-select">
          <optgroup label="Visão Global">
            <option value="all">🌐 Todas as Competições (Geral)</option>
          </optgroup>
          <optgroup label="Categorias Principais">
            <?php
            foreach($competitions_list as $comp){
              if($comp['id'] == 1 || $comp['id'] == 2 || $comp['tier'] <= 2){
                $tier_label = ($comp['id'] == 1 || $comp['tier'] == 1) ? " (Tier 1 / F1)" : " (Tier 2 / F2)";
                $selected = ($comp['id'] == 1) ? "selected" : "";
                echo "<option value='{$comp['id']}' {$selected}>🏎️ {$comp['name']}{$tier_label}</option>";
              }
            }
            ?>
          </optgroup>
          <optgroup label="Demais Competições & Regionais">
            <?php
            foreach($competitions_list as $comp){
              if($comp['id'] != 1 && $comp['id'] != 2 && $comp['tier'] > 2){
                echo "<option value='{$comp['id']}'>🏁 {$comp['name']}</option>";
              }
            }
            ?>
          </optgroup>
        </select>
      </div>
    </div>

    <!-- Hall of Fame Hero Cards -->
    <div class="stats-hero-grid">
      <div class="stats-hero-card" id="card-hero-wins">
        <div class="stats-hero-icon-wrapper gold-glow">
          <span class="material-symbols-outlined">emoji_events</span>
        </div>
        <div class="stats-hero-content">
          <span class="stats-hero-label">Maior Vencedor</span>
          <h3 class="stats-hero-value" id="hero-winner-name">-</h3>
          <p class="stats-hero-meta" id="hero-winner-meta">0 vitórias</p>
        </div>
      </div>

      <div class="stats-hero-card" id="card-hero-champ">
        <div class="stats-hero-icon-wrapper purple-glow">
          <span class="material-symbols-outlined">workspace_premium</span>
        </div>
        <div class="stats-hero-content">
          <span class="stats-hero-label">Maior Campeão</span>
          <h3 class="stats-hero-value" id="hero-champ-name">-</h3>
          <p class="stats-hero-meta" id="hero-champ-meta">0 títulos</p>
        </div>
      </div>

      <div class="stats-hero-card" id="card-hero-team">
        <div class="stats-hero-icon-wrapper silver-glow">
          <span class="material-symbols-outlined">shield</span>
        </div>
        <div class="stats-hero-content">
          <span class="stats-hero-label">Equipe Mais Vitoriosa</span>
          <h3 class="stats-hero-value" id="hero-team-name">-</h3>
          <p class="stats-hero-meta" id="hero-team-meta">0 vitórias</p>
        </div>
      </div>

      <div class="stats-hero-card" id="card-hero-reliability">
        <div class="stats-hero-icon-wrapper emerald-glow">
          <span class="material-symbols-outlined">verified</span>
        </div>
        <div class="stats-hero-content">
          <span class="stats-hero-label">Sr. Confiabilidade</span>
          <h3 class="stats-hero-value" id="hero-reliability-name">-</h3>
          <p class="stats-hero-meta" id="hero-reliability-meta">100% término</p>
        </div>
      </div>
    </div>

    <!-- Main Navigation Tabs -->
    <div class="stats-tabs-bar">
      <button type="button" class="stats-nav-btn active" data-tab="drivers_wins">
        <span class="material-symbols-outlined">military_tech</span>
        <span>Vitórias de Pilotos</span>
      </button>
      <button type="button" class="stats-nav-btn" data-tab="drivers_podiums">
        <span class="material-symbols-outlined">hotel_class</span>
        <span>Pódios de Pilotos</span>
      </button>
      <button type="button" class="stats-nav-btn" data-tab="champions">
        <span class="material-symbols-outlined">trophy</span>
        <span>Títulos & Campeões</span>
      </button>
      <button type="button" class="stats-nav-btn" data-tab="teams_wins">
        <span class="material-symbols-outlined">directions_car</span>
        <span>Equipes / Construtores</span>
      </button>
      <button type="button" class="stats-nav-btn" data-tab="drivers_points">
        <span class="material-symbols-outlined">leaderboard</span>
        <span>Pontos Históricos</span>
      </button>
      <button type="button" class="stats-nav-btn" data-tab="reliability">
        <span class="material-symbols-outlined">handyman</span>
        <span>Confiabilidade & DNF</span>
      </button>
      <button type="button" class="stats-nav-btn" data-tab="tracks">
        <span class="material-symbols-outlined">timer</span>
        <span>Recordes de Pista</span>
      </button>
    </div>

    <!-- Tables Display Area -->
    <div class="stats-content-area" id="stats-table-container">
      <!-- Populated via JS -->
    </div>
  </div>
</div>

<script>
var statsData = null;
var currentTab = 'drivers_wins';

function formatLapTime(seconds) {
  if (!seconds || seconds <= 0) return "-";
  var secNum = parseFloat(seconds);
  var minutes = Math.floor(secNum / 60);
  var remSeconds = (secNum % 60).toFixed(3);
  if (minutes > 0) {
    if (remSeconds < 10) remSeconds = "0" + remSeconds;
    return minutes + ":" + remSeconds;
  }
  return remSeconds + "s";
}

function loadStats() {
  var compId = $("#select-stats-competition").val();
  $("#loadingDiv").show();

  $.ajax({
    url: 'retrieve_stats.php',
    type: 'POST',
    dataType: 'json',
    data: { competition_id: compId }
  })
  .done(function(data) {
    $("#loadingDiv").hide();
    if (data && data.success) {
      statsData = data;
      renderHeroCards();
      renderActiveTab();
    } else {
      $("#stats-table-container").html("<div class='stats-empty-state'>Erro ao carregar estatísticas.</div>");
    }
  })
  .fail(function() {
    $("#loadingDiv").hide();
    $("#stats-table-container").html("<div class='stats-empty-state'>Erro de conexão com o servidor.</div>");
  });
}

function renderHeroCards() {
  if (!statsData) return;

  // Top Winner
  if (statsData.drivers_wins && statsData.drivers_wins.length > 0) {
    var topWinner = statsData.drivers_wins[0];
    $("#hero-winner-name").text(topWinner.name);
    $("#hero-winner-meta").text(topWinner.total_wins + " vitórias (" + (topWinner.team_name || "Várias") + ")");
  } else {
    $("#hero-winner-name").text("-");
    $("#hero-winner-meta").text("0 vitórias");
  }

  // Top Champion
  if (statsData.champions && statsData.champions.driver_titles && statsData.champions.driver_titles.length > 0) {
    var topChamp = statsData.champions.driver_titles[0];
    $("#hero-champ-name").text(topChamp.name);
    $("#hero-champ-meta").text(topChamp.titles_count + (topChamp.titles_count > 1 ? " títulos" : " título"));
  } else {
    $("#hero-champ-name").text("-");
    $("#hero-champ-meta").text("0 títulos");
  }

  // Top Team
  if (statsData.teams_wins && statsData.teams_wins.length > 0) {
    var topTeam = statsData.teams_wins[0];
    $("#hero-team-name").text(topTeam.team_name);
    $("#hero-team-meta").text(topTeam.total_wins + " vitórias");
  } else {
    $("#hero-team-name").text("-");
    $("#hero-team-meta").text("0 vitórias");
  }

  // Reliability
  if (statsData.drivers_reliability && statsData.drivers_reliability.length > 0) {
    var topRel = statsData.drivers_reliability[0];
    $("#hero-reliability-name").text(topRel.name);
    $("#hero-reliability-meta").text(topRel.finish_rate + "% de término (" + topRel.total_races + " GPs)");
  } else {
    $("#hero-reliability-name").text("-");
    $("#hero-reliability-meta").text("Sem dados");
  }
}

function renderActiveTab() {
  if (!statsData) return;

  var html = "";

  switch (currentTab) {
    case 'drivers_wins':
      html = renderDriversWinsTable(statsData.drivers_wins || []);
      break;
    case 'drivers_podiums':
      html = renderDriversPodiumsTable(statsData.drivers_podiums || []);
      break;
    case 'champions':
      html = renderChampionsSection(statsData.champions || {});
      break;
    case 'teams_wins':
      html = renderTeamsWinsTable(statsData.teams_wins || [], statsData.teams_points || []);
      break;
    case 'drivers_points':
      html = renderDriversPointsTable(statsData.drivers_points || []);
      break;
    case 'reliability':
      html = renderReliabilityTable(statsData.drivers_reliability || []);
      break;
    case 'tracks':
      html = renderTrackRecordsTable(statsData.track_records || []);
      break;
    default:
      html = "<div class='stats-empty-state'>Selecione uma categoria acima.</div>";
  }

  $("#stats-table-container").html(html);
}

function renderDriversWinsTable(list) {
  if (list.length === 0) return "<div class='stats-empty-state'>Nenhum registro de vitória encontrado nesta categoria.</div>";

  var html = "<div class='stats-table-wrapper'>";
  html += "<table class='stats-data-table'>";
  html += "<thead><tr>";
  html += "<th style='width:50px; text-align:center;'>#</th>";
  html += "<th>Piloto</th>";
  html += "<th>Nacionalidade</th>";
  html += "<th>Equipe</th>";
  html += "<th>Competição</th>";
  html += "<th style='text-align:right;'>Vitórias</th>";
  html += "</tr></thead><tbody>";

  list.forEach(function(item, idx) {
    var pos = idx + 1;
    var badgeClass = pos <= 3 ? "pos-" + pos : "";
    var flag = item.country_flag ? "<img src='/images/bandeiras/" + item.country_flag + "' class='stats-flag-icon' /> " : "";

    html += "<tr>";
    html += "<td class='pos-cell'><span class='driver-position " + badgeClass + "'>" + pos + "</span></td>";
    html += "<td class='stats-col-name font-bold'><a href='driver_info.php?driver=" + item.driver_id + "' class='stats-driver-link'>" + item.name + "</a></td>";
    html += "<td>" + flag + (item.country_name || "-") + "</td>";
    html += "<td>" + (item.team_name || "-") + "</td>";
    html += "<td><span class='stats-comp-badge'>" + (item.competition_name || "Geral") + "</span></td>";
    html += "<td class='stats-col-highlight' style='text-align:right; font-weight:bold; color:#fbbf24; font-size:1.1em;'>" + item.total_wins + "</td>";
    html += "</tr>";
  });

  html += "</tbody></table></div>";
  return html;
}

function renderDriversPodiumsTable(list) {
  if (list.length === 0) return "<div class='stats-empty-state'>Nenhum registro de pódio encontrado nesta categoria.</div>";

  var html = "<div class='stats-table-wrapper'>";
  html += "<table class='stats-data-table'>";
  html += "<thead><tr>";
  html += "<th style='width:50px; text-align:center;'>#</th>";
  html += "<th>Piloto</th>";
  html += "<th>Nacionalidade</th>";
  html += "<th>Equipe</th>";
  html += "<th style='text-align:center; color:#ffd700;' title='1º Lugar'>1º (Ouro)</th>";
  html += "<th style='text-align:center; color:#e2e8f0;' title='2º Lugar'>2º (Prata)</th>";
  html += "<th style='text-align:center; color:#d97706;' title='3º Lugar'>3º (Bronze)</th>";
  html += "<th style='text-align:right;'>Total Pódios</th>";
  html += "</tr></thead><tbody>";

  list.forEach(function(item, idx) {
    var pos = idx + 1;
    var badgeClass = pos <= 3 ? "pos-" + pos : "";
    var flag = item.country_flag ? "<img src='/images/bandeiras/" + item.country_flag + "' class='stats-flag-icon' /> " : "";

    html += "<tr>";
    html += "<td class='pos-cell'><span class='driver-position " + badgeClass + "'>" + pos + "</span></td>";
    html += "<td class='stats-col-name font-bold'><a href='driver_info.php?driver=" + item.driver_id + "' class='stats-driver-link'>" + item.name + "</a></td>";
    html += "<td>" + flag + (item.country_name || "-") + "</td>";
    html += "<td>" + (item.team_name || "-") + "</td>";
    html += "<td style='text-align:center; font-weight:bold; color:#ffd700;'>" + item.p1 + "</td>";
    html += "<td style='text-align:center; font-weight:bold; color:#e2e8f0;'>" + item.p2 + "</td>";
    html += "<td style='text-align:center; font-weight:bold; color:#d97706;'>" + item.p3 + "</td>";
    html += "<td class='stats-col-highlight' style='text-align:right; font-weight:bold; color:#fbbf24; font-size:1.1em;'>" + item.total_podiums + "</td>";
    html += "</tr>";
  });

  html += "</tbody></table></div>";
  return html;
}

function renderChampionsSection(champions) {
  var driverTitles = champions.driver_titles || [];
  var teamTitles = champions.team_titles || [];
  var history = champions.season_history || [];

  if (driverTitles.length === 0 && history.length === 0) {
    return "<div class='stats-empty-state'>Nenhuma temporada finalizada com títulos registrada nesta categoria.</div>";
  }

  var html = "<div class='champions-split-container'>";
  
  // Left: Drivers titles
  html += "<div class='champions-card'>";
  html += "<h3 class='champions-card-title'><span class='material-symbols-outlined' style='color:#fbbf24;vertical-align:middle;margin-right:6px;'>military_tech</span> Títulos por Piloto</h3>";
  html += "<table class='stats-data-table mini-table'>";
  html += "<thead><tr><th style='width:50px; text-align:center;'>#</th><th>Piloto</th><th>Equipe</th><th style='text-align:right;'>Títulos</th></tr></thead><tbody>";
  driverTitles.forEach(function(item, idx){
    var pos = idx + 1;
    var badgeClass = pos <= 3 ? "pos-" + pos : "";
    html += "<tr>";
    html += "<td class='pos-cell'><span class='driver-position " + badgeClass + "'>" + pos + "</span></td>";
    html += "<td class='font-bold'><a href='driver_info.php?driver=" + item.driver_id + "' class='stats-driver-link'>" + item.name + "</a></td>";
    html += "<td style='font-size:0.9em;color:#a3a3a3;'>" + (item.team_name || "-") + "</td>";
    html += "<td style='text-align:right;font-weight:bold;color:#fbbf24;font-size:1.05em;'>" + item.titles_count + " 🏆</td>";
    html += "</tr>";
  });
  html += "</tbody></table></div>";

  // Right: Constructors titles
  html += "<div class='champions-card'>";
  html += "<h3 class='champions-card-title'><span class='material-symbols-outlined' style='color:#e5e5e5;vertical-align:middle;margin-right:6px;'>shield</span> Títulos por Construtor</h3>";
  html += "<table class='stats-data-table mini-table'>";
  html += "<thead><tr><th style='width:50px; text-align:center;'>#</th><th>Equipe</th><th style='text-align:right;'>Títulos</th></tr></thead><tbody>";
  teamTitles.forEach(function(item, idx){
    var pos = idx + 1;
    var badgeClass = pos <= 3 ? "pos-" + pos : "";
    html += "<tr>";
    html += "<td class='pos-cell'><span class='driver-position " + badgeClass + "'>" + pos + "</span></td>";
    html += "<td class='font-bold' style='color:#f5f5f5;'>" + item.team_name + "</td>";
    html += "<td style='text-align:right;font-weight:bold;color:#fbbf24;font-size:1.05em;'>" + item.titles_count + " 🏆</td>";
    html += "</tr>";
  });
  html += "</tbody></table></div>";

  html += "</div>";

  // Bottom: Season by Season History
  html += "<div class='champions-history-container' style='margin-top:24px;'>";
  html += "<h3 class='champions-card-title'><span class='material-symbols-outlined' style='color:#a855f7;vertical-align:middle;margin-right:6px;'>history_edu</span> Histórico de Temporadas</h3>";
  html += "<div class='stats-table-wrapper'><table class='stats-data-table'>";
  html += "<thead><tr>";
  html += "<th>Ano</th>";
  html += "<th>Competição</th>";
  html += "<th>Piloto Campeão</th>";
  html += "<th style='text-align:center;'>Pts Piloto</th>";
  html += "<th>Equipe Campeã</th>";
  html += "<th style='text-align:right;'>Pts Equipe</th>";
  html += "</tr></thead><tbody>";

  history.forEach(function(s){
    var flag = s.driver_country_flag ? "<img src='/images/bandeiras/" + s.driver_country_flag + "' class='stats-flag-icon' /> " : "";
    html += "<tr>";
    html += "<td class='font-bold' style='color:#fbbf24;'>" + s.year + "</td>";
    html += "<td><span class='stats-comp-badge'>" + s.competition_name + "</span></td>";
    html += "<td class='font-bold'>" + flag + s.driver_champion + "</td>";
    html += "<td style='text-align:center; color:#fbbf24; font-weight:bold;'>" + s.driver_points + "</td>";
    html += "<td class='font-bold' style='color:#f5f5f5;'>" + s.team_champion + "</td>";
    html += "<td style='text-align:right; color:#fbbf24; font-weight:bold;'>" + s.team_points + "</td>";
    html += "</tr>";
  });

  html += "</tbody></table></div></div>";

  return html;
}

function renderTeamsWinsTable(winsList, pointsList) {
  if (winsList.length === 0 && pointsList.length === 0) return "<div class='stats-empty-state'>Nenhum dado de equipe encontrado nesta categoria.</div>";

  var html = "<div class='stats-table-wrapper'>";
  html += "<table class='stats-data-table'>";
  html += "<thead><tr>";
  html += "<th style='width:50px; text-align:center;'>#</th>";
  html += "<th>Equipe / Construtor</th>";
  html += "<th>Competição</th>";
  html += "<th style='text-align:center;'>GPs Disputados</th>";
  html += "<th style='text-align:center; color:#ffd700;'>Vitórias</th>";
  html += "<th style='text-align:center; color:#e2e8f0;'>Pódios</th>";
  html += "<th style='text-align:right;'>Pontos Totais</th>";
  html += "</tr></thead><tbody>";

  pointsList.forEach(function(item, idx) {
    var pos = idx + 1;
    var badgeClass = pos <= 3 ? "pos-" + pos : "";

    html += "<tr>";
    html += "<td class='pos-cell'><span class='driver-position " + badgeClass + "'>" + pos + "</span></td>";
    html += "<td class='stats-col-name font-bold' style='color:#f5f5f5;'><a href='car_info.php?car=" + item.car_id + "' class='stats-driver-link'>" + item.team_name + "</a></td>";
    html += "<td><span class='stats-comp-badge'>" + (item.competition_name || "Geral") + "</span></td>";
    html += "<td style='text-align:center; color:#a3a3a3;'>" + (item.total_races || 0) + "</td>";
    html += "<td style='text-align:center; font-weight:bold; color:#ffd700;'>" + (item.total_wins || 0) + "</td>";
    html += "<td style='text-align:center; font-weight:bold; color:#e2e8f0;'>" + (item.total_podiums || 0) + "</td>";
    html += "<td class='stats-col-highlight' style='text-align:right; font-weight:bold; color:#fbbf24; font-size:1.1em;'>" + (item.total_points || 0) + "</td>";
    html += "</tr>";
  });

  html += "</tbody></table></div>";
  return html;
}

function renderDriversPointsTable(list) {
  if (list.length === 0) return "<div class='stats-empty-state'>Nenhum piloto com pontos registrado nesta categoria.</div>";

  var html = "<div class='stats-table-wrapper'>";
  html += "<table class='stats-data-table'>";
  html += "<thead><tr>";
  html += "<th style='width:50px; text-align:center;'>#</th>";
  html += "<th>Piloto</th>";
  html += "<th>Nacionalidade</th>";
  html += "<th>Equipe</th>";
  html += "<th style='text-align:center;'>GPs Disputados</th>";
  html += "<th style='text-align:center; color:#ffd700;'>Vitórias</th>";
  html += "<th style='text-align:right;'>Pontos Totais</th>";
  html += "</tr></thead><tbody>";

  list.forEach(function(item, idx) {
    var pos = idx + 1;
    var badgeClass = pos <= 3 ? "pos-" + pos : "";
    var flag = item.country_flag ? "<img src='/images/bandeiras/" + item.country_flag + "' class='stats-flag-icon' /> " : "";

    html += "<tr>";
    html += "<td class='pos-cell'><span class='driver-position " + badgeClass + "'>" + pos + "</span></td>";
    html += "<td class='stats-col-name font-bold'><a href='driver_info.php?driver=" + item.driver_id + "' class='stats-driver-link'>" + item.name + "</a></td>";
    html += "<td>" + flag + (item.country_name || "-") + "</td>";
    html += "<td>" + (item.team_name || "-") + "</td>";
    html += "<td style='text-align:center; color:#a3a3a3;'>" + item.total_races + "</td>";
    html += "<td style='text-align:center; font-weight:bold; color:#ffd700;'>" + item.total_wins + "</td>";
    html += "<td class='stats-col-highlight' style='text-align:right; font-weight:bold; color:#fbbf24; font-size:1.1em;'>" + item.total_points + "</td>";
    html += "</tr>";
  });

  html += "</tbody></table></div>";
  return html;
}

function renderReliabilityTable(list) {
  if (list.length === 0) return "<div class='stats-empty-state'>Nenhum dado de confiabilidade com no mínimo 5 corridas nesta categoria.</div>";

  var html = "<div class='stats-table-wrapper'>";
  html += "<table class='stats-data-table'>";
  html += "<thead><tr>";
  html += "<th style='width:50px; text-align:center;'>#</th>";
  html += "<th>Piloto</th>";
  html += "<th>Nacionalidade</th>";
  html += "<th>Equipe</th>";
  html += "<th style='text-align:center;'>GPs</th>";
  html += "<th style='text-align:center; color:#4ade80;'>Concluídos</th>";
  html += "<th style='text-align:center; color:#f87171;'>Abandonos (DNF)</th>";
  html += "<th style='text-align:right;'>Taxa de Término</th>";
  html += "</tr></thead><tbody>";

  list.forEach(function(item, idx) {
    var pos = idx + 1;
    var badgeClass = pos <= 3 ? "pos-" + pos : "";
    var flag = item.country_flag ? "<img src='/images/bandeiras/" + item.country_flag + "' class='stats-flag-icon' /> " : "";

    html += "<tr>";
    html += "<td class='pos-cell'><span class='driver-position " + badgeClass + "'>" + pos + "</span></td>";
    html += "<td class='stats-col-name font-bold'><a href='driver_info.php?driver=" + item.driver_id + "' class='stats-driver-link'>" + item.name + "</a></td>";
    html += "<td>" + flag + (item.country_name || "-") + "</td>";
    html += "<td>" + (item.team_name || "-") + "</td>";
    html += "<td style='text-align:center; color:#a3a3a3;'>" + item.total_races + "</td>";
    html += "<td style='text-align:center; color:#4ade80; font-weight:bold;'>" + item.total_finished + "</td>";
    html += "<td style='text-align:center; color:#f87171; font-weight:bold;'>" + item.total_dnf + "</td>";
    html += "<td class='stats-col-highlight' style='text-align:right; font-weight:bold; color:#4ade80; font-size:1.1em;'>" + item.finish_rate + "%</td>";
    html += "</tr>";
  });

  html += "</tbody></table></div>";
  return html;
}

function renderTrackRecordsTable(list) {
  var validList = (list || []).filter(function(item) {
    return item && item.track_id && item.track_name && item.track_name !== 'null' && String(item.track_name).trim() !== '';
  });

  if (validList.length === 0) return "<div class='stats-empty-state'>Nenhum recorde de volta mais rápida registrado nesta categoria.</div>";

  var html = "<div class='stats-table-wrapper'>";
  html += "<table class='stats-data-table'>";
  html += "<thead><tr>";
  html += "<th>Circuito</th>";
  html += "<th>País</th>";
  html += "<th>Extensão</th>";
  html += "<th>Detentor do Recorde</th>";
  html += "<th>Equipe</th>";
  html += "<th>Temporada / GP</th>";
  html += "<th style='text-align:right;'>Melhor Volta</th>";
  html += "</tr></thead><tbody>";

  validList.forEach(function(item) {
    var flag = item.country_flag ? "<img src='/images/bandeiras/" + item.country_flag + "' class='stats-flag-icon' /> " : "";
    var formattedTime = formatLapTime(item.record_time);

    html += "<tr>";
    html += "<td class='stats-col-name font-bold' style='color:#f5f5f5;'><a href='track_info.php?track=" + item.track_id + "' class='stats-driver-link'>" + item.track_name + "</a></td>";
    html += "<td>" + flag + (item.country_name || "-") + "</td>";
    html += "<td style='color:#a3a3a3;'>" + (item.length ? item.length + " km" : "-") + "</td>";
    html += "<td class='font-bold' style='color:#fbbf24;'>" + (item.driver_name || "-") + "</td>";
    html += "<td style='color:#d4d4d4;'>" + (item.team_name || "-") + "</td>";
    html += "<td><span class='stats-comp-badge'>" + (item.season_year ? item.season_year + " - " : "") + (item.race_name || item.competition_name || "") + "</span></td>";
    html += "<td class='stats-col-highlight' style='text-align:right; font-weight:bold; color:#a855f7; font-family:monospace; font-size:1.15em;'>" + formattedTime + "</td>";
    html += "</tr>";
  });

  html += "</tbody></table></div>";
  return html;
}

$(document).ready(function() {
  $("#select-stats-competition").on("change", function() {
    loadStats();
  });

  $(".stats-nav-btn").on("click", function() {
    $(".stats-nav-btn").removeClass("active");
    $(this).addClass("active");
    currentTab = $(this).attr("data-tab");
    renderActiveTab();
  });

  loadStats();
});
</script>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
