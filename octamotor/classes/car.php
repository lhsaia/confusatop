<?php

class Car implements \JsonSerializable{

  private $id;
  private $chassis;
  private $engine;
  private $reliability;
  private $pit_stop_skills;
  private $strategy;
  private $nationality;
  private $team_name;
  private $conn;
  private $car_picture;
  private $tv_name;

  public function getReliability(){
    return $this->reliability;
  }


  public function __construct($db = null){
      $this->conn = $db;
  }

  public function getEngine(){
    return $this->engine;
  }

  public function getChassis(){
    return $this->chassis;
  }

  public function setEngine($level){
    $this->engine = $level;
  }

  public function setChassis($level){
    $this->chassis = $level;
  }

  public function getPitstopSkills(){
	  return $this->pit_stop_skills;
  }

  private function setParameters($results){
    $this->id = $results['id'];
    $this->engine = $results['engine'];
    $this->chassis = $results['chassis'];
    $this->reliability = $results['reliability'];
    $this->pit_stop_skills = $results['pit_stop_skills'];
    $this->strategy = $results['strategy'];
    $this->team_name = $results['team_name'];
    $this->car_picture = $results['car_picture'];
    $this->tv_name = $results['tv_name'];


  }

  public function getStrategy(){
    return $this->strategy;
  }

  public function loadCars(array $drivers){

      $aux_query = " WHERE car.id = ";
      $car_counter = 0;
      $parameter_list = array();
      foreach($drivers as $single_driver){
        if($car_counter > 0){
          $aux_query .= " OR ";
        }
        $aux_query .= " ? ";
        $parameter_list[] = $single_driver->getCarId();
        $car_counter++;
      }

      $query = "SELECT car.tv_name, car.engine, car.chassis, car.id, car.team_name, car.pit_stop_skills, car.reliability, car.strategy, car.picture as car_picture FROM car " . $aux_query;
      $stmt = $this->conn->prepare($query);
      for($i = 1;$i <= $car_counter; $i++){
        $stmt->bindParam($i,$parameter_list[$i-1]);
      }
      $stmt->execute();

      $car_list = array();

      while ($results = $stmt->fetch(PDO::FETCH_ASSOC)){
        $new_car = new Car();
        $new_car->setParameters($results);
        $car_list[] = $new_car;
      }


      return $car_list;

      //return $query;

  }

  public function searchList(array $list, $id){
    foreach($list as $car_element){
      if($car_element->getId() == $id){
        return $car_element;
      }
    }

  }

  public function getId(){
    return $this->id;
  }

  public function getName(){
    return $this->team_name;
  }

  public function getTireFactor($lap, $type){

    if($type == 1){
      $a = 0.0005;
      $b = 0.01;
      $c = 0;
    } else if($type == 2){
      $a = 0.002;
      $b = 0.02;
      $c = -0.7;
    } else if($type == 3){
      $a = 0.0005;
      $b = 0.01;
      $c = 0;
    }

    $wear = $a * ($lap ** 2) + $b * $lap + $c;

    return $wear;
  }


  public function pit_stop_length($best_pit){

    $total_pit = $best_pit + 2 + (mt_rand(1,20))/($this->pit_stop_skills);

    return $total_pit;

  }

  	public function jsonSerialize()
	{
	$vars = get_object_vars($this);

	return $vars;
	}

  public function getCarsList(){

    $query = "SELECT car.id, car.team_name, competition.name as comp_name, p.dono as owner FROM car LEFT JOIN lhsaia_confusa.paises p ON p.id = car.country LEFT JOIN competition ON car.competition_id = competition.id";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
	  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
  }

  public function loadCar($id){
    $id = htmlspecialchars(strip_tags($id));
    $query = "SELECT suit as car_suit, color, tv_name, team_name as name, engine, chassis, car.logo, pit_stop_skills, strategy, reliability, team_chief, tech_chief, base, chassis_name, engine_name, picture as car_picture, competition.name as competition, competition_id, p.nome as country_name, p.id as country_id, p.bandeira as country_flag FROM car LEFT JOIN competition ON car.competition_id = competition.id LEFT JOIN lhsaia_confusa.paises p ON p.id = car.country WHERE car.id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id",$id);
    $stmt->execute();
    return $stmt;
  }


  public function insertCar($car_data){
    foreach($car_data as &$data){
      $data = htmlspecialchars(strip_tags($data));
    }
    unset($data);
    $query = "INSERT INTO car (team_name, tv_name, color, country, engine, chassis, pit_stop_skills, strategy, reliability, competition_id, team_chief, tech_chief, base, chassis_name, engine_name, logo, picture, suit) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ";
    $stmt = $this->conn->prepare($query);
    $counter = 1;
    foreach($car_data as &$data){
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

  public function updateCar($id, $car_data){
    foreach($car_data as &$single_data){
      $single_data = htmlspecialchars(strip_tags($single_data));
    }
    unset($single_data);
    $id = htmlspecialchars(strip_tags($id));

    //var_dump($driver_data);

    $query = "UPDATE car SET team_name=?, tv_name=?, color=?, country=?, engine=?, chassis=?, pit_stop_skills=?, strategy=?, reliability=?, competition_id=?, team_chief=?, tech_chief=?, base=?, chassis_name=?, engine_name=?, logo=?, picture=?, suit=? WHERE id = ? ";
    $stmt = $this->conn->prepare($query);
    $counter = 1;
    foreach($car_data as &$single_data){
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

  public function isNotOwner($car_id, $user_id){
    $car_id = htmlspecialchars(strip_tags($car_id));
    $user_id = htmlspecialchars(strip_tags($user_id));

    $query = "SELECT p.dono FROM car LEFT JOIN lhsaia_confusa.paises p ON p.id = car.country WHERE car.id = ? ";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1,$car_id);
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
