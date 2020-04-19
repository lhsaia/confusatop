<?php

class Competition{

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

    $query = "SELECT point_system, extra_points, name, max_time, total_length, max_drivers, qualifying_style, event_factor, position_factor, random_factor, speed_factor, pace_factor, technique_factor, start_skills_factor, rain_skills_factor, aggressiveness_factor, car_factor, race_prop_factor, quali_prop_factor, time_random_factor FROM competition WHERE id = :id";
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
    $query = "SELECT competition.point_system, competition.extra_points, competition.logo, competition.name, max_time, total_length, about, qualifying_style, car_factor, speed_factor, technique_factor, pace_factor, random_factor, aggressiveness_factor, rain_skills_factor, start_skills_factor, quali_prop_factor, race_prop_factor, position_factor, event_factor, owner, max_drivers, p.nome as country_name, p.id as country_id, p.bandeira as country_flag, MIN(season.year) as first_year, COUNT(DISTINCT season.id) as total_seasons, COUNT(race.id) as total_races FROM competition LEFT JOIN lhsaia_confusa.paises p ON p.id = competition.country_id LEFT JOIN season ON season.competition_id = competition.id LEFT JOIN race ON season.id = race.season_id WHERE competition.id = :id";
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
    $query = "INSERT INTO competition (name, extra_points, point_system, tier, qualifying_style, car_factor, speed_factor, technique_factor, pace_factor, random_factor, aggressiveness_factor, rain_skills_factor, start_skills_factor, quali_prop_factor, race_prop_factor, time_random_factor, position_factor, event_factor, owner, country_id, max_drivers, about, max_time, total_length, logo) VALUES (?,?,?,3,?,?,?,?,?,?,?,?,?,?,?,1,?,?,?,?,?,?,?,?,?) ";
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

    $query = "UPDATE competition SET name=?, extra_points=?, point_system=?, qualifying_style=?, car_factor=?, speed_factor=?, technique_factor=?, pace_factor=?, random_factor=?, aggressiveness_factor=?, rain_skills_factor=?, start_skills_factor=?, quali_prop_factor=?, race_prop_factor = ?, position_factor=?, event_factor=?, owner=?, country_id=?, max_drivers=?, about=?, max_time =?, total_length=?,logo=? WHERE id = ? ";
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

    $query = "SELECT race.id, datetime, track_id, file, name, race.status, paises.bandeira as flag, paises.nome as country_name FROM race LEFT JOIN lhsaia_confusa.paises ON paises.id = race.country_id WHERE season_id = ? ORDER BY race.id DESC ";
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
    
    if($event_type == 1){ //race
      $query = "SELECT driver.name, car.team_name, position, points as total_points FROM race_position LEFT JOIN driver ON driver.id = race_position.driver LEFT JOIN car ON car.id = race_position.car WHERE race = ? AND timestamp < ? ORDER BY points DESC, position ASC";
    } else { //season
      $query = "SELECT driver.name, car.team_name, SUM(points) as total_points FROM race_position LEFT JOIN driver ON driver.id = race_position.driver LEFT JOIN car ON car.id = race_position.car LEFT JOIN race ON race_position.race = race.id WHERE race.season_id = ? AND timestamp < ? GROUP BY driver ORDER BY SUM(points) DESC, name DESC";
    }
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1,$event_id);
    $stmt->bindParam(2,$timestamp);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;

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




}


 ?>
