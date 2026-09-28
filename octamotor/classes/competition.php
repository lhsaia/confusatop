<?php

require_once "db_name.php";

class Competition extends db_name{

  private $id;
  private $name;
  private $tier;
  private $qualifying_laps;
  private $random_factor;
  private $speed_factor;
  private $pace_factor;
  private $technique_factor;
  private $start_skills_factor;
  private $rain_skills_factor;
  private $aggressiveness_factor;
  private $car_factor;
  private $conn;
  private $race_prop_factor;
  private $quali_prop_factor;
  private $time_random_factor;
  private $position_factor;
  private $event_factor;
  private $max_drivers;
  private $max_time;
  private $total_length;
  private $point_system;
  private $extra_points;
  private $race_type;

  public function getName(){
    return $this->name;
  }

  public function __construct($db, $number = null){
      $this->conn = $db;

if($number != null){
  $this->id = $number;
  $this->loadFactors();
}

  }


  public function getMaxTime(){
    return ($this->max_time * 60);
  }

  public function getTotalLength(){
    return ($this->total_length * 1000);
  }

  public function getQualifyingLaps(){
    return $this->qualifying_laps;
  }

  public function getMaxDrivers(){
    return $this->max_drivers;
  }

  private function loadFactors(){

    $query = "SELECT point_system, extra_points, name, max_time, total_length, max_drivers, qualifying_style, event_factor, position_factor, random_factor, speed_factor, pace_factor, technique_factor, start_skills_factor, rain_skills_factor, aggressiveness_factor, car_factor, race_prop_factor, quali_prop_factor, time_random_factor, competition_type FROM competition WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $this->id);
    if($stmt->execute()){
      while ($results = $stmt->fetch(PDO::FETCH_ASSOC)){
        $this->name = $results['name'];
        $this->random_factor = $results['random_factor'];
        $this->pace_factor = $results['pace_factor'];
        $this->speed_factor = $results['speed_factor'];
        $this->technique_factor = $results['technique_factor'];
        $this->start_skills_factor = $results['start_skills_factor'];
        $this->rain_skills_factor = $results['rain_skills_factor'];
        $this->aggressiveness_factor = $results['aggressiveness_factor'];
        $this->car_factor = $results['car_factor'];
        $this->race_prop_factor = $results['race_prop_factor'];
        $this->quali_prop_factor = $results['quali_prop_factor'];
        $this->time_random_factor = $results['time_random_factor'];
        if($results['qualifying_style'] == 1){
          $this->qualifying_laps = 1;
        } else if ($results['qualifying_style'] == 2){
          $this->qualifying_laps = 3;
        } else {
          $this->qualifying_laps = 0;
        }
        $this->position_factor = $results['position_factor'];
        $this->event_factor = $results['event_factor'];
        $this->max_drivers = $results['max_drivers'];
        $this->max_time = $results["max_time"];
        $this->total_length = $results["total_length"];
        $this->point_system = $results['point_system'];
        $this->extra_points = $results['extra_points'];
		$this->race_type = $results["competition_type"];
      }
      return true;
    } else {
      return false;
    }
  }

  public function getSpeedFactor(){
    return $this->speed_factor;
  }

  public function getEventFactor(){
    return $this->event_factor * 0.5;
  }

  public function getPositionFactor(){
    return $this->position_factor;
  }


  public function getRandomFactor(){
    return $this->random_factor;
  }

  public function getPaceFactor(){
    return $this->pace_factor;
  }

  public function getAggressivenessFactor(){
    return $this->aggressiveness_factor;
  }

  public function getRainSkillsFactor(){
    return $this->rain_skills_factor;
  }

  public function getCarFactor(){
    return $this->car_factor;
  }

  public function getStartSkillsFactor(){
    return $this->start_skills_factor;
  }

  public function getTechniqueFactor(){
    return $this->technique_factor;
  }

  public function getId(){
    return $this->id;
  }

  public function getQualiPropFactor(){
    return $this->quali_prop_factor;
  }

  public function getRacePropFactor(){
    return $this->race_prop_factor;
  }

  public function getTimeRandomFactor(){
    return $this->time_random_factor;
  }

  public function getCompetitionList(){
    $query = "SELECT id, name, owner FROM competition";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
  }

  public function loadCompetition($id){
    $id = htmlspecialchars(strip_tags($id));
    $query = "SELECT competition.point_system, competition.extra_points, competition.logo, competition.name, max_time, total_length, about, qualifying_style, car_factor, speed_factor, technique_factor, pace_factor, random_factor, aggressiveness_factor, rain_skills_factor, start_skills_factor, quali_prop_factor, race_prop_factor, position_factor, event_factor, owner, max_drivers, p.nome as country_name, p.id as country_id, p.bandeira as country_flag, MIN(season.year) as first_year, COUNT(DISTINCT season.id) as total_seasons, COUNT(race.id) as total_races, competition.competition_type FROM competition LEFT JOIN ".$this->db_name.".paises p ON p.id = competition.country_id LEFT JOIN season ON season.competition_id = competition.id LEFT JOIN race ON season.id = race.season_id WHERE competition.id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id",$id);
    $stmt->execute();
    return $stmt;
  }

  public function insertCompetition($competition_data){
    foreach($competition_data as &$data){
      $data = htmlspecialchars(strip_tags($data));
    }
    unset($data);
    $query = "INSERT INTO competition (name, extra_points, point_system, tier, qualifying_style, car_factor, speed_factor, technique_factor, pace_factor, random_factor, aggressiveness_factor, rain_skills_factor, start_skills_factor, quali_prop_factor, race_prop_factor, time_random_factor, position_factor, event_factor, owner, country_id, max_drivers, about, max_time, total_length, competition_type, logo) VALUES (?,?,?,3,?,?,?,?,?,?,?,?,?,?,?,1,?,?,?,?,?,?,?,?,?,?) ";
    $stmt = $this->conn->prepare($query);
    $counter = 1;
    foreach($competition_data as &$data){
      $stmt->bindParam($counter,$data);
      $counter++;
    }
    unset($data);
    if($stmt->execute()){
      return true;
    } else {
      return false;
    }

  }

  public function updateCompetition($id, $competition_data){
    foreach($competition_data as &$single_data){
      $single_data = htmlspecialchars(strip_tags($single_data));
    }
    unset($single_data);
    $id = htmlspecialchars(strip_tags($id));

    //var_dump($track_data);

    $query = "UPDATE competition SET name=?, extra_points=?, point_system=?, qualifying_style=?, car_factor=?, speed_factor=?, technique_factor=?, pace_factor=?, random_factor=?, aggressiveness_factor=?, rain_skills_factor=?, start_skills_factor=?, quali_prop_factor=?, race_prop_factor = ?, position_factor=?, event_factor=?, owner=?, country_id=?, max_drivers=?, about=?, max_time =?, total_length=?, competition_type = ?, logo=? WHERE id = ? ";
    $stmt = $this->conn->prepare($query);
    $counter = 1;
    foreach($competition_data as &$single_data){
      $stmt->bindParam($counter, $single_data);
      $counter++;
    }
    unset($single_data);
    $stmt->bindParam($counter, $id);

    if($stmt->execute()){


      return true;
    } else {
      return false;
    }

  }



  public function isNotOwner($competition_id, $user_id){
    $competition_id = htmlspecialchars(strip_tags($competition_id));
    $user_id = htmlspecialchars(strip_tags($user_id));

    $query = "SELECT owner FROM competition WHERE id = ? ";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1,$competition_id);
    $stmt->execute();
    $result = $stmt->fetchColumn();

    if($result == $user_id){
      return false;
    } else {
      return true;
    }

  }

  public function retrieveSeasons($competition_id){
    $competition_id = htmlspecialchars(strip_tags($competition_id));

    $query = "SELECT id, year FROM season WHERE competition_id = ? ORDER BY year DESC ";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1,$competition_id);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;

  }

  public function retrieveRaces($season_id){
    $season_id = htmlspecialchars(strip_tags($season_id));

    $query = "SELECT race.id, datetime, track_id, file, name, race.status, paises.bandeira as flag, paises.nome as country_name FROM race LEFT JOIN ".$this->db_name.".paises ON paises.id = race.country_id WHERE season_id = ? ORDER BY race.id DESC ";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1,$season_id);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;

  }

  public function retrieveStandings($event_id, $event_type){
    $event_id = htmlspecialchars(strip_tags($event_id));
    $event_type = htmlspecialchars(strip_tags($event_type));
    $timestamp = time();
    
    if($event_type == 1){ // race
      // Drivers
      $query_drivers = "SELECT driver.name, car.team_name, position, points as total_points, car.picture as car_picture, car.logo as team_logo FROM race_position LEFT JOIN driver ON driver.id = race_position.driver LEFT JOIN car ON car.id = race_position.car WHERE race = ? AND timestamp < ? ORDER BY (CASE WHEN position > 0 THEN position WHEN position = 0 THEN 998 WHEN position = -1 THEN 999 ELSE 1000 END) ASC, points DESC, driver.name ASC";
      $stmt_d = $this->conn->prepare($query_drivers);
      $stmt_d->bindParam(1,$event_id);
      $stmt_d->bindParam(2,$timestamp);
      $stmt_d->execute();
      $drivers_results = $stmt_d->fetchAll(PDO::FETCH_ASSOC);

      // Teams
      $query_teams = "SELECT car.id as car_id, car.team_name, car.picture as car_picture, car.logo as team_logo, SUM(points) as total_points, GROUP_CONCAT(driver.name SEPARATOR ', ') as driver_names FROM race_position LEFT JOIN car ON car.id = race_position.car LEFT JOIN driver ON driver.id = race_position.driver WHERE race = ? AND timestamp < ? GROUP BY car.id ORDER BY SUM(points) DESC, car.team_name ASC";
      $stmt_t = $this->conn->prepare($query_teams);
      $stmt_t->bindParam(1,$event_id);
      $stmt_t->bindParam(2,$timestamp);
      $stmt_t->execute();
      $teams_results = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

      return [
        "drivers" => $drivers_results,
        "teams" => $teams_results
      ];
    } else { // season
      // Drivers
      $query_drivers = "SELECT driver.name, car.team_name, SUM(points) as total_points, car.picture as car_picture, car.logo as team_logo, COUNT(DISTINCT race_position.race) as total_races, SUM(CASE WHEN race_position.position = 1 THEN 1 ELSE 0 END) as wins, SUM(CASE WHEN race_position.position >= 1 AND race_position.position <= 3 THEN 1 ELSE 0 END) as podiums FROM race_position LEFT JOIN driver ON driver.id = race_position.driver LEFT JOIN car ON car.id = race_position.car LEFT JOIN race ON race_position.race = race.id WHERE race.season_id = ? AND timestamp < ? GROUP BY driver.id ORDER BY SUM(points) DESC, wins DESC, name ASC";
      $stmt_d = $this->conn->prepare($query_drivers);
      $stmt_d->bindParam(1,$event_id);
      $stmt_d->bindParam(2,$timestamp);
      $stmt_d->execute();
      $drivers_results = $stmt_d->fetchAll(PDO::FETCH_ASSOC);

      // Teams
      $query_teams = "SELECT car.id as car_id, car.team_name, car.picture as car_picture, car.logo as team_logo, SUM(points) as total_points, SUM(CASE WHEN race_position.position = 1 THEN 1 ELSE 0 END) as wins, SUM(CASE WHEN race_position.position >= 1 AND race_position.position <= 3 THEN 1 ELSE 0 END) as podiums, GROUP_CONCAT(DISTINCT driver.name SEPARATOR ', ') as driver_names FROM race_position LEFT JOIN car ON car.id = race_position.car LEFT JOIN driver ON driver.id = race_position.driver LEFT JOIN race ON race_position.race = race.id WHERE race.season_id = ? AND timestamp < ? GROUP BY car.id ORDER BY SUM(points) DESC, wins DESC, car.team_name ASC";
      $stmt_t = $this->conn->prepare($query_teams);
      $stmt_t->bindParam(1,$event_id);
      $stmt_t->bindParam(2,$timestamp);
      $stmt_t->execute();
      $teams_results = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

      return [
        "drivers" => $drivers_results,
        "teams" => $teams_results
      ];
    }
  }

  public function getFullCompetitionList(){
    $query = "SELECT id, name, owner, tier, competition_type, logo FROM competition ORDER BY tier ASC, name ASC";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getStatsDriversWins($competition_id = null){
    $filter = "";
    if($competition_id && $competition_id !== 'all'){
      $filter = " AND race_position.competition_id = " . intval($competition_id);
    }
    $query = "SELECT driver.id as driver_id, driver.name, driver.photo, p.nome as country_name, p.bandeira as country_flag, GROUP_CONCAT(DISTINCT car.team_name ORDER BY car.team_name ASC SEPARATOR ' / ') as team_name, comp.name as competition_name, COUNT(race_position.race) as total_wins FROM race_position LEFT JOIN driver ON driver.id = race_position.driver LEFT JOIN car ON car.id = race_position.car LEFT JOIN competition comp ON comp.id = race_position.competition_id LEFT JOIN ".$this->db_name.".paises p ON p.id = driver.country WHERE race_position.position = 1 {$filter} GROUP BY driver.id ORDER BY total_wins DESC, driver.name ASC LIMIT 50";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getStatsDriversPodiums($competition_id = null){
    $filter = "";
    if($competition_id && $competition_id !== 'all'){
      $filter = " AND race_position.competition_id = " . intval($competition_id);
    }
    $query = "SELECT driver.id as driver_id, driver.name, driver.photo, p.nome as country_name, p.bandeira as country_flag, GROUP_CONCAT(DISTINCT car.team_name ORDER BY car.team_name ASC SEPARATOR ' / ') as team_name, comp.name as competition_name, COUNT(race_position.race) as total_podiums, SUM(CASE WHEN race_position.position = 1 THEN 1 ELSE 0 END) as p1, SUM(CASE WHEN race_position.position = 2 THEN 1 ELSE 0 END) as p2, SUM(CASE WHEN race_position.position = 3 THEN 1 ELSE 0 END) as p3 FROM race_position LEFT JOIN driver ON driver.id = race_position.driver LEFT JOIN car ON car.id = race_position.car LEFT JOIN competition comp ON comp.id = race_position.competition_id LEFT JOIN ".$this->db_name.".paises p ON p.id = driver.country WHERE race_position.position >= 1 AND race_position.position <= 3 {$filter} GROUP BY driver.id ORDER BY total_podiums DESC, p1 DESC, p2 DESC, p3 DESC LIMIT 50";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getStatsDriversPoints($competition_id = null){
    $filter = "";
    if($competition_id && $competition_id !== 'all'){
      $filter = " AND race_position.competition_id = " . intval($competition_id);
    }
    $query = "SELECT driver.id as driver_id, driver.name, driver.photo, p.nome as country_name, p.bandeira as country_flag, GROUP_CONCAT(DISTINCT car.team_name ORDER BY car.team_name ASC SEPARATOR ' / ') as team_name, comp.name as competition_name, COUNT(DISTINCT race_position.race) as total_races, SUM(race_position.points) as total_points, SUM(CASE WHEN race_position.position = 1 THEN 1 ELSE 0 END) as total_wins FROM race_position LEFT JOIN driver ON driver.id = race_position.driver LEFT JOIN car ON car.id = race_position.car LEFT JOIN competition comp ON comp.id = race_position.competition_id LEFT JOIN ".$this->db_name.".paises p ON p.id = driver.country WHERE 1=1 {$filter} GROUP BY driver.id ORDER BY total_points DESC, total_wins DESC LIMIT 50";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getStatsDriversReliability($competition_id = null){
    $filter = "";
    if($competition_id && $competition_id !== 'all'){
      $filter = " AND race_position.competition_id = " . intval($competition_id);
    }
    $query = "SELECT driver.id as driver_id, driver.name, driver.photo, p.nome as country_name, p.bandeira as country_flag, GROUP_CONCAT(DISTINCT car.team_name ORDER BY car.team_name ASC SEPARATOR ' / ') as team_name, comp.name as competition_name, COUNT(race_position.race) as total_races, SUM(CASE WHEN race_position.position = -1 THEN 1 ELSE 0 END) as total_dnf, (COUNT(race_position.race) - SUM(CASE WHEN race_position.position = -1 THEN 1 ELSE 0 END)) as total_finished, ROUND((SUM(CASE WHEN race_position.position = -1 THEN 1 ELSE 0 END) / COUNT(race_position.race)) * 100, 1) as abandon_rate, ROUND(((COUNT(race_position.race) - SUM(CASE WHEN race_position.position = -1 THEN 1 ELSE 0 END)) / COUNT(race_position.race)) * 100, 1) as finish_rate FROM race_position LEFT JOIN driver ON driver.id = race_position.driver LEFT JOIN car ON car.id = race_position.car LEFT JOIN competition comp ON comp.id = race_position.competition_id LEFT JOIN ".$this->db_name.".paises p ON p.id = driver.country WHERE 1=1 {$filter} GROUP BY driver.id HAVING total_races >= 5 ORDER BY abandon_rate ASC, total_races DESC LIMIT 50";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getStatsTeamsWins($competition_id = null){
    $filter = "";
    if($competition_id && $competition_id !== 'all'){
      $filter = " AND race_position.competition_id = " . intval($competition_id);
    }
    $query = "SELECT car.id as car_id, car.team_name, car.picture as car_picture, car.logo as team_logo, comp.name as competition_name, COUNT(race_position.race) as total_wins FROM race_position LEFT JOIN car ON car.id = race_position.car LEFT JOIN competition comp ON comp.id = race_position.competition_id WHERE race_position.position = 1 {$filter} GROUP BY car.id ORDER BY total_wins DESC, car.team_name ASC LIMIT 50";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getStatsTeamsPoints($competition_id = null){
    $filter = "";
    if($competition_id && $competition_id !== 'all'){
      $filter = " AND race_position.competition_id = " . intval($competition_id);
    }
    $query = "SELECT car.id as car_id, car.team_name, car.picture as car_picture, car.logo as team_logo, comp.name as competition_name, COUNT(DISTINCT race_position.race) as total_races, SUM(race_position.points) as total_points, SUM(CASE WHEN race_position.position = 1 THEN 1 ELSE 0 END) as total_wins, SUM(CASE WHEN race_position.position >= 1 AND race_position.position <= 3 THEN 1 ELSE 0 END) as total_podiums FROM race_position LEFT JOIN car ON car.id = race_position.car LEFT JOIN competition comp ON comp.id = race_position.competition_id WHERE 1=1 {$filter} GROUP BY car.id ORDER BY total_points DESC, total_wins DESC LIMIT 50";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getStatsChampions($competition_id = null){
    $comp_filter = "";
    if($competition_id && $competition_id !== 'all'){
      $comp_filter = " WHERE s.competition_id = " . intval($competition_id);
    }
    
    // Buscar temporadas com corridas
    $query_seasons = "SELECT DISTINCT s.id as season_id, s.year, s.competition_id, c.name as competition_name FROM season s LEFT JOIN competition c ON c.id = s.competition_id LEFT JOIN race r ON r.season_id = s.id {$comp_filter} ORDER BY s.year DESC, s.competition_id ASC";
    $stmt_s = $this->conn->prepare($query_seasons);
    $stmt_s->execute();
    $seasons = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

    $driver_titles = [];
    $team_titles = [];
    $season_history = [];

    $timestamp = time();

    foreach($seasons as $s){
      $s_id = $s['season_id'];
      
      // Campeão de Pilotos
      $q_d = "SELECT driver.id as driver_id, driver.name as driver_name, driver.photo, p.nome as country_name, p.bandeira as country_flag, car.team_name, SUM(points) as total_points FROM race_position LEFT JOIN driver ON driver.id = race_position.driver LEFT JOIN car ON car.id = race_position.car LEFT JOIN race ON race_position.race = race.id LEFT JOIN ".$this->db_name.".paises p ON p.id = driver.country WHERE race.season_id = ? AND timestamp < ? GROUP BY driver.id ORDER BY SUM(points) DESC, SUM(CASE WHEN race_position.position = 1 THEN 1 ELSE 0 END) DESC LIMIT 1";
      $st_d = $this->conn->prepare($q_d);
      $st_d->bindParam(1, $s_id);
      $st_d->bindParam(2, $timestamp);
      $st_d->execute();
      $champ_d = $st_d->fetch(PDO::FETCH_ASSOC);

      // Campeão de Equipes
      $q_t = "SELECT car.id as car_id, car.team_name, car.picture as car_picture, car.logo as team_logo, SUM(points) as total_points FROM race_position LEFT JOIN car ON car.id = race_position.car LEFT JOIN race ON race_position.race = race.id WHERE race.season_id = ? AND timestamp < ? GROUP BY car.id ORDER BY SUM(points) DESC, SUM(CASE WHEN race_position.position = 1 THEN 1 ELSE 0 END) DESC LIMIT 1";
      $st_t = $this->conn->prepare($q_t);
      $st_t->bindParam(1, $s_id);
      $st_t->bindParam(2, $timestamp);
      $st_t->execute();
      $champ_t = $st_t->fetch(PDO::FETCH_ASSOC);

      if($champ_d && $champ_d['total_points'] > 0){
        $d_id = $champ_d['driver_id'];
        if(!isset($driver_titles[$d_id])){
          $driver_titles[$d_id] = [
            'driver_id' => $d_id,
            'name' => $champ_d['driver_name'],
            'photo' => $champ_d['photo'],
            'country_name' => $champ_d['country_name'],
            'country_flag' => $champ_d['country_flag'],
            'teams' => [],
            'team_name' => '',
            'titles_count' => 0,
            'years' => []
          ];
        }
        $driver_titles[$d_id]['titles_count']++;
        if(!empty($champ_d['team_name'])){
          $driver_titles[$d_id]['teams'][] = $champ_d['team_name'];
        }
        $driver_titles[$d_id]['team_name'] = implode(' / ', array_unique($driver_titles[$d_id]['teams']));
        $driver_titles[$d_id]['years'][] = $s['year'] . " (" . $s['competition_name'] . ")";

        if($champ_t && $champ_t['total_points'] > 0){
          $t_id = $champ_t['car_id'];
          if(!isset($team_titles[$t_id])){
            $team_titles[$t_id] = [
              'car_id' => $t_id,
              'team_name' => $champ_t['team_name'],
              'car_picture' => $champ_t['car_picture'],
              'team_logo' => $champ_t['team_logo'],
              'titles_count' => 0,
              'years' => []
            ];
          }
          $team_titles[$t_id]['titles_count']++;
          $team_titles[$t_id]['years'][] = $s['year'] . " (" . $s['competition_name'] . ")";
        }

        $season_history[] = [
          'year' => $s['year'],
          'competition_id' => $s['competition_id'],
          'competition_name' => $s['competition_name'],
          'driver_champion' => $champ_d['driver_name'],
          'driver_country_flag' => $champ_d['country_flag'],
          'driver_points' => $champ_d['total_points'],
          'team_champion' => $champ_t['team_name'] ?? '-',
          'team_points' => $champ_t['total_points'] ?? 0
        ];
      }
    }

    usort($driver_titles, function($a, $b){
      return $b['titles_count'] - $a['titles_count'];
    });

    usort($team_titles, function($a, $b){
      return $b['titles_count'] - $a['titles_count'];
    });

    return [
      'driver_titles' => $driver_titles,
      'team_titles' => $team_titles,
      'season_history' => $season_history
    ];
  }

  public function getStatsTrackRecords($competition_id = null){
    $filter = "";
    if($competition_id && $competition_id !== 'all'){
      $filter = " AND race_position.competition_id = " . intval($competition_id);
    }
    $query = "SELECT t.id as track_id, t.name as track_name, t.length, t.image as track_image, p.nome as country_name, p.bandeira as country_flag, MIN(race_position.best_time) as record_time, d.name as driver_name, c.team_name, r.name as race_name, comp.name as competition_name, s.year as season_year FROM race_position LEFT JOIN race r ON r.id = race_position.race LEFT JOIN track t ON t.id = r.track_id LEFT JOIN season s ON s.id = r.season_id LEFT JOIN competition comp ON comp.id = s.competition_id LEFT JOIN driver d ON d.id = race_position.driver LEFT JOIN car c ON c.id = race_position.car LEFT JOIN ".$this->db_name.".paises p ON p.id = t.country WHERE race_position.best_time > 0 AND t.id IS NOT NULL AND t.name IS NOT NULL AND t.name != '' {$filter} GROUP BY t.id ORDER BY t.name ASC";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function createSeason($season_data){
    foreach($season_data as &$data){
      $data = htmlspecialchars(strip_tags($data));
    }
    unset($data);
    $query = "INSERT INTO season (competition_id, year) VALUES (?,?) ";
    $stmt = $this->conn->prepare($query);
    $counter = 1;
    foreach($season_data as &$data){
      $stmt->bindParam($counter,$data);
      $counter++;
    }
    unset($data);
    if($stmt->execute()){
      return true;
    } else {
      return false;
    }

  }

  public function getPointSystem(){
    return $this->point_system;
  }

  public function getExtraPoints(){
    return $this->extra_points;
  }

  public function getRaceType(){
    return $this->race_type;
  }

  
  public function getLogoFromName($name){
	  
	$name = htmlspecialchars(strip_tags($name));
	
	$name = "%" . $name . "%";
	  
	$query = "SELECT logo FROM competition WHERE name LIKE ?";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1, $name);
    $stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	
    return $result["logo"];
	
  }
  
  public function getCompetitionLockedStatusByDriver($driver_id){
	  	$driver_id = htmlspecialchars(strip_tags($driver_id));

		$query = "SELECT c.locked FROM driver d LEFT JOIN car r ON d.car_id = r.id LEFT JOIN competition c ON c.id = r.competition_id WHERE d.id = :driver_id ";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(":driver_id",$driver_id);
		$stmt->execute();
		$result = $stmt->fetchColumn();
		
		return $result;
  }
  
    public function getCompetitionLockedStatusByCar($car_id){
	  	$car_id = htmlspecialchars(strip_tags($car_id));

		$query = "SELECT c.locked FROM car r LEFT JOIN competition c ON c.id = r.competition_id WHERE r.id = :car_id ";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(":car_id",$car_id);
		$stmt->execute();
		$result = $stmt->fetchColumn();
		
		return $result;
  }

}


 ?>
