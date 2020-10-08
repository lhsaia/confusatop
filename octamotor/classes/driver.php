<?php

require_once "db_name.php";

class Driver extends db_name implements \JsonSerializable{

  private $id;
  private $level;
  private $speed;
  private $pace;
  private $start_skills;
  private $technique;
  private $rain_skills;
  private $aggressiveness;
  private $name;
  private $nationality;
  private $birth_date;
  private $age;
  private $car_id;
  private $conn;
  private $number;
  private $stint;
  private $current_tire_set;
  private $pit_strategy;
  private $pit_count;
  private $remaining_pits;
  private $qualifying_best_time;
  private $qualifying_last_time;
  private $start_performance;
  private $race_lap_time;
  private $race_total_time;
  private $race_lap_count;
  private $overtake_count;
  private $overtake_penalty;
  private $status;
  private $issue_name;
  private $extra_pit;
  private $time_difference;
  private $threshold;
  private $best_lap;
  private $lap_penalty;
  private $permanent_penalty;
  private $abandoned_lap;
  private $pits_done;
  private $average_stint;
  private $average_speed;
  private $laps_behind;
  private $tv_name;

  public function adjustAttributes(){

    $total_points = $this->level * 0.55;
    $total_attributes = $this->speed + $this->pace + $this->aggressiveness + $this->start_skills + $this->rain_skills + $this->technique;

    $speed = ($this->speed / $total_attributes) * $total_points ;
    $pace = ($this->pace / $total_attributes) * $total_points;
    $aggressiveness = ($this->aggressiveness / $total_attributes) * $total_points;
    $start_skills = ($this->start_skills / $total_attributes) * $total_points;
    $rain_skills = ($this->rain_skills / $total_attributes) * $total_points;
    $technique = ($this->technique / $total_attributes) * $total_points;

    $attributeArray = array("start_skills" => $start_skills, "pace" => $pace, "aggressiveness" => $aggressiveness, "rain_skills" => $rain_skills, "speed" => $speed, "technique" => $technique );

    do {
      $remainder = 0.0;
      $dividend = 0.0;

      foreach($attributeArray as &$single_attribute){
        if($single_attribute > 10){
          $remainder = $remainder + $single_attribute - 10;
          $single_attribute = 10;
        } else if($single_attribute < 0.5){
          $dividend = $dividend - (0.5 - $single_attribute);
          $single_attribute = 0.5;
        } else {
          $dividend = $dividend + 10;
        }
      }
      unset($single_attribute);

      if($remainder > 0.0){
        $distribution = $remainder / $dividend;
        foreach($attributeArray as &$single_attribute){
          if($single_attribute < 10){
            $single_attribute = $single_attribute + $distribution * 10;
          }
        }
        unset($single_attribute);
      }

  } while ($remainder > 0.0);

    $this->speed = $attributeArray["speed"];
    $this->pace = $attributeArray["pace"];
    $this->technique = $attributeArray["technique"];
    $this->rain_skills = $attributeArray["rain_skills"];
    $this->start_skills = $attributeArray["start_skills"];
    $this->aggressiveness = $attributeArray["aggressiveness"];
  }

  public function getStatus(){
    return $this->status;
  }

  public function setStatus($status){
     $this->status = $status;
  }

  public function getIssueName(){
    return $this->issue_name;
  }

  public function getId(){
    return $this->id;
  }

  public function setIssueName($issue_name){
    $this->issue_name = $issue_name;
  }

  public function recordLapInfo($time_difference, $threshold, $laps_behind){
    $this->time_difference = $time_difference;
    $this->threshold = $threshold;

    $current_speed = 1/$this->race_lap_time;
    if(!$this->average_speed){
      $this->average_speed = $current_speed;
    } else {
      $this->average_speed = ($this->average_speed * $this->race_lap_count + $current_speed)/($this->race_lap_count + 1);
    }
    $this->race_lap_count++;
    $this->laps_behind = $laps_behind;
  }

  public function getAverageSpeed(){
    return $this->average_speed;
  }

  public function getThreshold(){
    return $this->threshold;
  }

  public function getTimeDifference(){
    return $this->time_difference;
  }

  public function setQualifyingTime($new_time){

    $this->qualifying_last_time = $new_time;
    if(!isset($this->qualifying_best_time) || $new_time < $this->qualifying_best_time){
      $this->qualifying_best_time = $new_time;
    }
  }

  public function resetQualifyingTime(){
    unset($this->qualifying_best_time);
    unset($this->qualifying_last_time);
  }

  public function getPitCount(){
    return $this->pit_count;
  }

  public function getPitStrategy(){
    return $this->pit_strategy;
  }

  public function getRemainingPits(){
    return $this->remaining_pits;
  }

  public function pitHappened($pit_time){
    $this->race_lap_time = $this->race_lap_time + $pit_time;
	$this->pits_done = $this->pits_done + 1;

  }



  public function setLapTime($lap_time){
    $this->race_lap_time = $lap_time;
  }

  public function increaseLapTime($extra_time){
    $this->race_lap_time = $this->race_lap_time + $extra_time;
  }

  public function getLapTime(){
    return $this->race_lap_time;
  }

  public function getQualifyingTime(){
    return $this->qualifying_best_time;
  }

  public function addOvertake(){
    $this->overtake_count = $this->overtake_count + 1;
  }

  public function setOvertakePenalty(){
    $this->overtake_penalty = 0.4;
  }

  public function addStartPenalty($penalty){
    $this->start_performance = $this->start_performance - $penalty;
  }

  public function addLapPenalty($penalty){
	  $this->lap_penalty = $penalty;
  }

    public function addPermanentPenalty($penalty){
	  $this->permanent_penalty = $penalty;
  }

  public function abandonRace($lap, $leader_speed){


    if($this->average_speed == 0){
      $speed_ratio = 1;
    } else {
      $speed_ratio = $leader_speed / $this->average_speed;
    }
    $this->status = -1;
    if($speed_ratio == 0){
      $speed_ratio = 1;
    }
    $this->abandoned_lap = floor($lap/$speed_ratio);

  }

  public function setExtraPit(){
    $this->extra_pit = true;
  }

  public function getOvertakes(){
    return $this->overtake_count;
  }

  // public function getOvertakePenalty(){
  //   $penalty = $this->overtake_penalty;
  //   $this->overtake_penalty = 0.0;
  //   return $penalty;
  // }

  public function __construct($db = null){
      $this->conn = $db;

      $this->stint = 1.0;

      $this->race_lap_time = 0.0;
      $this->race_total_time = 0.0;
      $this->overtake_penalty = 0.0;
      $this->overtake_count = 0;
      $this->status = 0;
	  $this->pits_done = 0;
  }

  public function getTotalTime(){
    return $this->race_total_time;
  }

  public function setTotalTime($race_total_time){
    $this->race_total_time = $race_total_time;
  }


  public function updateTotalTime(){
    $this->race_total_time = $this->race_total_time + $this->race_lap_time;
  }

  public function qualifying_lap_time(Car $car, Track $track, Competition $competition, $highest_level){

    //para teste de parametrização
    $car_factor = $competition->getCarFactor();
    $speed_factor = $competition->getSpeedFactor();
    $technique_factor = $competition->getTechniqueFactor();
    $random_factor = $competition->getRandomFactor();
    $rain_skills_factor = $competition->getRainSkillsFactor();

    $speed = $this->speed;
    $technique = $this->technique;
    $rain_skills = $this->rain_skills;
    $random = mt_rand(1,10);

    $engine = $car->getEngine();
    $chassis = $car->getChassis();
    //$tire_factor = $car->getTireFactor(1);
    $tire_factor = 0;

    $rain_status = $track->isRaining();

    $performance_factor = ($speed*$speed_factor + $technique*$technique_factor + $random*$random_factor + $rain_skills* $rain_skills_factor * $rain_status)*(($engine + $chassis)*$car_factor/2);

    $fastest_lap = $track->getFastestLap();
    $proportional_factor = $competition->getQualiPropFactor();

    $lap_time = ($fastest_lap * ($proportional_factor + $rain_status/3)) + (($fastest_lap/$performance_factor)*$highest_level) + mt_rand(10000000,50000000)/100000000 + $tire_factor;

    return $lap_time;
  }

  public function resetStint(){
    $this->stint = 0.0;
  }

  public function getStint(){
    return $this->stint;
  }

  public function race_lap_time(Car $car, Track $track, Competition $competition, $highest_level, $lap, $safety_car_penalty){

    //para teste de parametrização
    $car_factor = $competition->getCarFactor();
    $pace_factor = $competition->getPaceFactor();
    $technique_factor = $competition->getTechniqueFactor();
    $random_factor = $competition->getRandomFactor();
    $aggressiveness_factor = $competition->getAggressivenessFactor();
    $time_random_factor = $competition->getTimeRandomFactor();
    $rain_skills_factor = $competition->getRainSkillsFactor();

    $pace = $this->pace;
    $technique = $this->technique;
    $aggressiveness = $this->aggressiveness;
    $rain_skills = $this->rain_skills;
    $random = mt_rand(1,10);

    $engine = $car->getEngine();
    $chassis = $car->getChassis();
    $tire_factor = $car->getTireFactor($this->stint, $this->current_tire_set);
    $fuel_factor = 0.037;
    //$fuel_factor = 0;

    $rain_status = $track->isRaining();

    $performance_factor = ($pace*$pace_factor + $technique*$technique_factor + $aggressiveness*$aggressiveness_factor + $random*$random_factor + $rain_skills*$rain_skills_factor * $rain_status)*(($engine + $chassis)*$car_factor/2);

    $fastest_lap = $track->getFastestLap();
    $proportional_factor = $competition->getRacePropFactor();

    $lap_time = (($fastest_lap * ($proportional_factor + $rain_status/3)) + (($fastest_lap/$performance_factor)*$highest_level) + mt_rand(100,500)*$time_random_factor/1000) - ($fuel_factor * $lap) + ($tire_factor);

    $this->stint = $this->stint + 1;

    if($lap == 1){
      $lap_time = $lap_time + 6;
    }

    //teste
    $this->overtake_penalty;
    $lap_time = $lap_time + $this->overtake_penalty + $safety_car_penalty;
    $this->overtake_penalty = 0.0;

	if(!isset($this->best_lap) || $this->best_lap > $lap_time){

		$this->best_lap = $lap_time;
	}

	if(isset($this->lap_penalty)){
		$lap_time = $lap_time + $this->lap_penalty;
		$this->lap_penalty = 0;
	}

	if(isset($this->permanent_penalty)){
		$lap_time = $lap_time + $this->permanent_penalty;
	}



    return $lap_time;
  }

  public function setRainSkills($level){
    $this->rain_skills = $level;
  }
  public function setTechnique($level){
    $this->technique = $level;
  }
  public function setSpeed($level){
    $this->speed = $level;
  }

  public function getDriversList(){

    $query = "SELECT driver.status, driver.id, driver.name, driver.car_id, p.dono as owner FROM driver LEFT JOIN ".$this->db_name.".paises p ON p.id = driver.country ORDER BY status DESC, driver.name";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
	  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
  }

  public function prepareDriver($results){
    $this->id = $results['id'];
    $this->level = $results['level'];
    $this->speed = $results['speed'];
    $this->pace = $results['pace'];
    $this->start_skills = $results['start_skills'];
    $this->technique = $results['technique'];
    $this->rain_skills = $results['rain_skills'];
    $this->aggressiveness = $results['aggressiveness'];
    $this->name = $results['name'];
    $this->tv_name = $results['tv_name'];
    $this->car_id = $results['car_id'];
    $this->number = $results['number'];
	$this->nationality = $results['country_flag'];

    $this->current_tire_set = 2;
    $this->race_lap_count = 0;

    $this->adjustAttributes();

    //$this->definePitStrategy();
  }

  public function getName(){
    return $this->name;
  }

  public function definePitStrategy($strategy_array = null, $strategy_level = null, $total_laps = null){

    //var_dump($strategy_array);

    $probability_array = array();
    $probability_array[0] = (-79/9)*$strategy_level+88.77777;
    $probability_array[1] = (15-$strategy_level) + $probability_array[0];
    $probability_array[2] = ($strategy_level + 4) + $probability_array[1];
    $probability_array[3] = ((79/9)*$strategy_level-7.77778) + $probability_array[2];

    $sort_strategy = (mt_rand() / mt_getrandmax())*100;

    if($sort_strategy < $probability_array[0]){
      $real_strategy = $strategy_array[3];
    } else if($sort_strategy < $probability_array[1]){
      $real_strategy = $strategy_array[2];
    } else if($sort_strategy < $probability_array[2]){
      $real_strategy = $strategy_array[1];
    } else {
      $real_strategy = $strategy_array[0];
    }

    $this->pit_strategy = str_shuffle($real_strategy);
    $this->pit_strategy = str_replace("S","2",$this->pit_strategy);
    $this->pit_strategy = str_replace("M","1",$this->pit_strategy);

  //  echo $this->pit_strategy;

    $this->pit_count = strlen($this->pit_strategy) - 1;
    $this->remaining_pits = $this->pit_count;

    $count_soft = substr_count($real_strategy, "S");
    $count_medium = substr_count($real_strategy, "M");

    $this->average_stint = $total_laps / ($count_soft + 2*$count_medium);

    // if($this->current_tire_set == 1){
    //   $this->pit_strategy = "12";
    // } else {
    //   $this->pit_strategy = "21";
    // }
    // $this->pit_strategy = $this->pit_strategy . mt_rand(0,2) . mt_rand(0,2);
    // $this->pit_strategy = str_replace("0", "", $this->pit_strategy);
  }

  public function loadDrivers($competition){

    $query = "SELECT driver.tv_name, driver.id, driver.name, driver.level, driver.speed, driver.pace, driver.start_skills, driver.technique, driver.rain_skills, driver.aggressiveness, car.picture as car_picture, car_id, car.team_name as team_name, number, p.nome as country_name, p.bandeira as country_flag FROM driver LEFT JOIN car ON car.id = driver.car_id LEFT JOIN ".$this->db_name.".paises p ON p.id = driver.country LEFT JOIN competition ON competition.id = car.competition_id WHERE competition_id = :competition ORDER BY driver.name";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":competition",$competition);
    $stmt->execute();
    $driver_list = array();

    while ($results = $stmt->fetch(PDO::FETCH_ASSOC)){
      $new_driver = new Driver();
      $new_driver->prepareDriver($results);
      $driver_list[] = $new_driver;
    }

    return $driver_list;

    }

    public function getCarId(){
      return $this->car_id;
    }

    public function setStartPerformance(Car $car, Track $track, Competition $competition, $highest_level, $position){

      //para teste de parametrização
      $car_factor = $competition->getCarFactor();
      $start_skills_factor = $competition->getStartSkillsFactor();
      $random_factor = $competition->getRandomFactor();
      $rain_skills_factor = $competition->getRainSkillsFactor();
      $position_factor = $competition->getPositionFactor();

      $start_skills = $this->start_skills;
      $rain_skills = $this->rain_skills;
      $random = mt_rand(1,10);

      $engine = $car->getEngine();

      $rain_status = $track->isRaining();

      $this->start_performance = ($start_skills*$start_skills_factor + $random*$random_factor + $rain_skills*$rain_skills_factor * $rain_status +$engine*$car_factor -$position* $position_factor);

      return true;
    }

    public function getStartPerformance(){
      return $this->start_performance;
    }

    public function highLevel(Car $car, Track $track, Competition $competition, $stage){
      //para teste de parametrização
      $car_factor = $competition->getCarFactor();
      $pace_factor = $competition->getPaceFactor();
      $speed_factor = $competition->getSpeedFactor();
      $random_factor = $competition->getRandomFactor();
      $time_random_factor = $competition->getTimeRandomFactor();
      $rain_skills_factor = $competition->getRainSkillsFactor();
      $technique_factor = $competition->getTechniqueFactor();
      $aggressiveness_factor = $competition->getAggressivenessFactor();

      $pace = $this->pace;
      $speed = $this->speed;
      $technique = $this->technique;
      $aggressiveness = $this->aggressiveness;
      $rain_skills = $this->rain_skills;
      $random = 10;

      $engine = $car->getEngine();
      $chassis = $car->getChassis();

      $rain_status = $track->isRaining();

      if($stage = 0){ //qualifying
        $performance_factor = ($speed*$speed_factor + $random*$random_factor + $technique*$technique_factor + $rain_skills*$rain_skills_factor * $rain_status)*(($engine + $chassis)*$car_factor/2);
      } else { // race
        $performance_factor = ($pace*$pace_factor + $random*$random_factor + $technique*$technique_factor + $aggressiveness*$aggressiveness_factor + $rain_skills*$rain_skills_factor * $rain_status)*(($engine + $chassis)*$car_factor/2);
      }

      return $performance_factor / 20;

    }

    public function setTire(){
      if($this->current_tire_set != 3){
        if(strlen($this->pit_strategy) > 0){
          $this->current_tire_set = $this->pit_strategy[0];
          $this->pit_strategy = substr($this->pit_strategy, 1);;
        }
      }
    }

    public function putRainTire(){
      $this->current_tire_set = 3;
    }

    public function putSoftTire(){
      $this->current_tire_set = 2;
    }

    public function getTire(){
      return $this->current_tire_set;
    }


    public function hasToPit($strategy){

      if($this->extra_pit == true){
        if(($this->stint >= (2*$this->average_stint/3) && $this->current_tire_set == 2) || ($this->stint >= (4*$this->average_stint/3) && $this->current_tire_set == 1) || ($this->stint >= (4*$this->average_stint/3) && $this->current_tire_set == 3)){
          $this->remaining_pits = $this->remaining_pits - 1;
          $this->setTire();
        }
        $this->extra_pit = false;
        $this->status = 0;
        $this->resetStint();
        return true;
      }

      if(($this->stint >= $this->average_stint && $this->current_tire_set == 2) || ($this->stint >= ($this->average_stint * 2) && $this->current_tire_set == 1) || ($this->stint >= ($this->average_stint * 2) && $this->current_tire_set == 3)){

        $correct_strategy = ($strategy) / 10;
        $should_pit = mt_rand() / mt_getrandmax();

        if(($should_pit < $correct_strategy) || ($this->stint > (2.3 * $this->average_stint))){
          $this->remaining_pits = $this->remaining_pits - 1;
          $this->setTire();
          $this->resetStint();
          return true;
        } else {
          return false;
        }

      } else {
        return false;
      }


    }

    public function getAggressiveness(){
      return $this->aggressiveness;
    }

    public function getStartSkills(){
      return $this->start_skills;
    }

	public function jsonSerialize()
	{
	$vars = get_object_vars($this);

	return $vars;
	}

  public function loadDriver($id){
    $timestamp = time();
    $id = htmlspecialchars(strip_tags($id));
    $query = "SELECT driver.genre, driver.tv_name, driver.status, hicomp.name as highest_comp, SUM(d.points) as points, COUNT(d.driver) as gps, SUM(case when (d.position > 0 && d.position < 4) then 1 else 0 end) as podiums ,SUM(case when d.position = -1 then 1 else 0 end) as abandon, MIN(NULLIF(ABS(d.position), -d.position)) as best_position, SUM(case when d.position = (SELECT MIN(NULLIF(ABS(position), -position)) FROM race_position WHERE driver = :id1 ) then 1 else 0 end) as best_position_times, driver.name, driver.photo, competition.name as competition, driver.bio, driver.helmet, driver.level, driver.speed, driver.pace, driver.start_skills, driver.technique, driver.rain_skills, driver.aggressiveness, car.picture as car_picture, driver.car_id, car.team_name as team_name, driver.number, p.nome as country_name, p.id as country_id, p.bandeira as country_flag, driver.birth_date, driver.birth_place FROM driver LEFT JOIN car ON car.id = driver.car_id LEFT JOIN competition ON car.competition_id = competition.id LEFT JOIN competition hicomp ON driver.highest_comp = hicomp.id LEFT JOIN ".$this->db_name.".paises p ON p.id = driver.country  LEFT JOIN race_position d ON driver.id = d.driver WHERE driver.id = :id2 AND (case when driver.car_id <> 0 then (car.competition_id = d.competition_id) else (d.competition_id = driver.highest_comp) end) AND d.timestamp < :timestamp AND d.race <> 9999";

	//	$query = "SELECT driver.name, driver.photo, competition.name as competition, bio, driver.helmet, driver.level, driver.speed, driver.pace, driver.start_skills, driver.technique, driver.rain_skills, driver.aggressiveness, car.picture as car_picture, car_id, car.team_name as team_name, number, p.nome as country_name, p.id as country_id, p.bandeira as country_flag, birth_date, birth_place FROM driver LEFT JOIN car ON car.id = driver.car_id LEFT JOIN competition ON car.competition_id = competition.id LEFT JOIN lhsaia_confusa.paises p ON p.id = driver.country WHERE driver.id = ?";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(":id1",$id);
    $stmt->bindParam(":id2",$id);
    $stmt->bindParam(":timestamp",$timestamp);
		$stmt->execute();
		return $stmt;
	}

  public function insertDriver($driver_data){
    foreach($driver_data as &$data){
      $data = htmlspecialchars(strip_tags($data));
    }
    unset($data);
    $query = "INSERT INTO driver (name, tv_name, genre, bio, level, speed, pace, start_skills, technique, rain_skills, aggressiveness, car_id, number, country, birth_date, birth_place, status, photo, helmet) VALUES (?,?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?) ";
    $stmt = $this->conn->prepare($query);
    $counter = 1;
    foreach($driver_data as &$data){
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

  public function updateDriver($id, $driver_data){
    foreach($driver_data as &$single_data){
      $single_data = htmlspecialchars(strip_tags($single_data));
    }
    unset($single_data);
    $id = htmlspecialchars(strip_tags($id));

    //var_dump($driver_data);

    $query = "UPDATE driver SET name=?, tv_name=?, genre=?, bio=?, level=?, speed=?, pace=?, start_skills=?, technique=?, rain_skills=?, aggressiveness=?, number=?, country=?, birth_date=?, birth_place=?, status=?, photo=?, helmet=? WHERE id = ? ";
    $stmt = $this->conn->prepare($query);
    $counter = 1;
    foreach($driver_data as &$single_data){
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

  public function getDriversByTeam($teamId){
    $teamId = htmlspecialchars(strip_tags($teamId));
    $query = "SELECT driver.id, driver.name, driver.photo FROM driver WHERE car_id = :id ORDER BY team_position LIMIT 0,2";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $teamId);
    $stmt->execute();
    return $stmt;
  }

  public function getBestLapTime(){
    return $this->best_lap;
  }

  public function updateDriverTeam($driver_id, $team_id, $position){
    $team_id = htmlspecialchars(strip_tags($team_id));
    $driver_id = htmlspecialchars(strip_tags($driver_id));

    $this->fireDriver($team_id, $position);

    $query = "UPDATE driver SET car_id = :car_id, team_position = :team_position WHERE id = :id AND (car_id IS NULL OR LENGTH(car_id)=0 OR car_id = 0 );";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $driver_id);
    $stmt->bindParam(":car_id", $team_id);
    $stmt->bindParam(":team_position", $position);
    $stmt->execute();
    return $stmt;
  }

  public function fireDriver($car_id, $position){
    $car_id = htmlspecialchars(strip_tags($car_id));
    $position = htmlspecialchars(strip_tags($position));
    $clean_query = "UPDATE driver SET car_id = 0 WHERE car_id = :car_id AND team_position = :team_position";
    $stmt = $this->conn->prepare($clean_query);
    $stmt->bindParam(":car_id", $car_id);
    $stmt->bindParam(":team_position", $position);
    $stmt->execute();
    return $stmt;
  }

  public function isNotOwner($driver_id, $user_id){
    $driver_id = htmlspecialchars(strip_tags($driver_id));
    $user_id = htmlspecialchars(strip_tags($user_id));

    $query = "SELECT p.dono FROM driver LEFT JOIN ".$this->db_name.".paises p ON p.id = driver.country WHERE driver.id = ? ";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1,$driver_id);
    $stmt->execute();
    $result = $stmt->fetchColumn();

    if($result == $user_id){
      return false;
    } else {
      return true;
    }

  }


}

?>
