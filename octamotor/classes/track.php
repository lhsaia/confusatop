<?php

require_once "db_name.php";

class Track extends db_name {

  private $id;
  private $name;
  private $nationality;
  private $lap_best_time;
  private $total_laps;
  private $overtaking_threshold;
  private $rain_possibility;
  private $style;
  private $conn;
  private $is_raining;
  private $pit_best_length;
  private $length;
  private $avg_temp;
  private $country_name;
  private $country_flag;
  private $track_temp;
  private $air_temp;

  public function getName(){
    return $this->name;
  }

  public function getCountryFlag(){
    return $this->country_flag;
  }

  public function getCountryName(){
    return $this->country_name;
  }


  public function __construct($db, $number = null){
      $this->conn = $db;

      if($number != null){
        $this->id = $number;
        $this->loadFactors();
      }


  }

  public function loadFactors(){

        $query = "SELECT rain_possibility, lap_base_time, style, length, pit_lane_time, avg_temp, name, p.nome as country_name, p.bandeira as country_flag FROM track LEFT JOIN ".$this->db_name.".paises p ON p.id = track.country WHERE track.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        if($stmt->execute()){
          while ($results = $stmt->fetch(PDO::FETCH_ASSOC)){
            $this->rain_possibility = $results['rain_possibility'];
            $this->lap_best_time = $results['lap_base_time'];
            $this->overtaking_threshold = $results['style'] * 0.4;
            $this->pit_best_length = $results['pit_lane_time'];
            $this->length = $results['length'];
            $this->avg_temp = $results['avg_temp'];
            $this->name = $results['name'];
            $this->country_name = $results['country_name'];
            $this->country_flag = $results['country_flag'];
          }
          return true;
        } else {
          return false;
        }
  }

  public function getAirTemp(){
    return $this->air_temp;
  }

  public function getTrackTemp(){
    return $this->track_temp;
  }


  public function isRaining(){
    return $this->is_raining;
  }

  public function setRain(){

    $rain_possibility = $this->rain_possibility / 100;
    $rain_chance = mt_rand()/mt_getrandmax();
    if($rain_chance < $rain_possibility){
      $this->is_raining = 1;
      $this->air_temp = $this->avg_temp + mt_rand(0,5) - mt_rand(0,10);
      $this->track_temp = $this->air_temp + mt_rand(0,5) - mt_rand(0,10);
    } else {
      $this->is_raining = 0;
      $this->air_temp = $this->avg_temp + mt_rand(0,10) - mt_rand(0,10);
      $this->track_temp = $this->air_temp + mt_rand(0,20) - mt_rand(0,0);
    }
  }

  public function getFastestLap(){
    return $this->lap_best_time;
  }

  public function getOvertakingThreshold(){
    return $this->overtaking_threshold;
  }


  public function getLength(){
    return $this->length;
  }

  public function getPitBestLength(){
    return $this->pit_best_length;
  }

  public function getTracksList(){

    $query = "SELECT track.id, track.name, p.dono as owner FROM track LEFT JOIN ".$this->db_name.".paises p ON p.id = track.country";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
  }

  public function loadTrack($id){
    $id = htmlspecialchars(strip_tags($id));
    $query = "SELECT name, pit_lane_time, lap_base_time, style, rain_possibility, avg_temp, length, about, curves, first_used, image, p.nome as country_name, p.id as country_id, p.bandeira as country_flag FROM track LEFT JOIN ".$this->db_name.".paises p ON p.id = track.country WHERE track.id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id",$id);
    $stmt->execute();
    return $stmt;
  }

  public function insertTrack($track_data){
    foreach($track_data as &$data){
      $data = htmlspecialchars(strip_tags($data));
    }
    unset($data);
    $query = "INSERT INTO track (name, country, about, first_used, length, curves, lap_base_time, style, rain_possibility, avg_temp, pit_lane_time, image) VALUES (?,?,?,?,?,?,?,?,?,?,?,?) ";
    $stmt = $this->conn->prepare($query);
    $counter = 1;
    foreach($track_data as &$data){
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

  public function updateTrack($id, $track_data){
    foreach($track_data as &$single_data){
      $single_data = htmlspecialchars(strip_tags($single_data));
    }
    unset($single_data);
    $id = htmlspecialchars(strip_tags($id));

    //var_dump($track_data);

    $query = "UPDATE track SET name=?, country=?, about=?, first_used=?, length=?, curves=?, lap_base_time=?, style=?, rain_possibility=?, avg_temp = ?, pit_lane_time = ?, image=? WHERE id = ? ";
    $stmt = $this->conn->prepare($query);
    $counter = 1;
    foreach($track_data as &$single_data){
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



  public function isNotOwner($track_id, $user_id){
    $track_id = htmlspecialchars(strip_tags($track_id));
    $user_id = htmlspecialchars(strip_tags($user_id));

    $query = "SELECT p.dono FROM track LEFT JOIN ".$this->db_name.".paises p ON p.id = track.country WHERE track.id = ? ";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1,$track_id);
    $stmt->execute();
    $result = $stmt->fetchColumn();

    if($result == $user_id){
      return false;
    } else {
      return true;
    }

  }

  public function retrieveTracks(){

    $query = "SELECT track.id, track.name, p.id as country_id, p.bandeira as country_flag FROM track LEFT JOIN ".$this->db_name.".paises p ON p.id = track.country";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
  }




}

?>
