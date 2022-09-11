<?php

require_once "db_name.php";

class Race extends db_name {

  private $race_type;
  private $drivers;
  private $conn;
  private $date;
  private $competition;
  private $track;
  private $race_list = array();
  private $highest_level;
  private $lap_results = array();
  private $event_counter = 0;
  private $event_combo = 0;
  private $safety_car_status = 0;
  private $safety_car_counter = 0;
  private $safety_car_penalty = 4;
  private $total_laps;
  private $base_timestamp;
  private $current_user;
  private $race_id;
  private $quali_end;
  private $current_lap;

  public function setCurrentUser($user){
    $this->current_user = $user;
  }

  public function recordRaceInfo($name, $season, $day_night, $race_id){
    $race_info["country_flag"] = $this->track->getCountryFlag();
    $race_info["country_name"] = $this->track->getCountryName();
    $race_info["base_timestamp"] = $this->base_timestamp;
    $race_info["name"] = $name;
    $race_info["track_name"] = $this->track->getName();
    $race_info["day_night"] = $day_night;
    $race_info["total_laps"] = $this->total_laps;
    $race_info["competition"] = $this->competition->getName();
    $race_info["season"] = $season;
    $race_info["quali_type"] = $this->competition->getQualifyingLaps();
    $race_info["drivers_excess"] = count($this->race_list) - $this->competition->getMaxDrivers();
	$race_info["race_type"] = $this->competition->getRaceType();
	$race_info["max_time"] = $this->competition->getMaxTime();
    $this->lap_results["INFO"] = $race_info;
    $this->race_id = $race_id;
  }

  public function setBaseTimestamp($timestamp){
    $this->base_timestamp = $timestamp;
  }

  public function setEvent(){
    $this->event_counter = $this->event_counter + 1;
    $this->event_combo = $this->event_combo + 1;
  }

  public function resetEventCombo(){
    $this->event_combo = 0;
  }

  public function resetEventCounter(){
    $this->event_counter = 0;
  }

  public function __construct($db, Competition $competition = null, Track $track = null){
      $this->conn = $db;
      if($competition != null && $track != null){
        $this->competition = $competition;
        $this->track = $track;
		$this->race_type = $this->competition->getRaceType();
      }
  }

  public function load_participants(Driver $driver, Car $car){
    $drivers_list = $driver->loadDrivers($this->competition->getId());
    $car_list = $car->loadCars($drivers_list);

    foreach($drivers_list as $single_driver){
      $driver_car_id = $single_driver->getCarId();
      $single_car = $car->searchList($car_list, $driver_car_id);
      $this->race_list[] = array("driver" => $single_driver, "car" => $single_car);

    }

    return true;
  }

  public function getRaceList(){
    return $this->race_list;
  }

  public function calculateHighestLevel($stage){
    $this->highest_level = 0;
    foreach($this->race_list as $racer){
      $level = $racer['driver']->highLevel($racer['car'],$this->track,$this->competition,$stage);
      if($level > $this->highest_level || !isset($this->highest_level)){
        $this->highest_level = $level;
      }
    }
  }

  public function pre_quali(){
    $this->recordResults("PQ-0", (($this->base_timestamp) - 172800 ));
    $max_drivers = $this->competition->getMaxDrivers();
    $this->track->setRain();

    if($this->track->isRaining()){
      foreach($this->race_list as &$racer){
        $racer["driver"]->putRainTire();
      }
      unset($racer);
    } else {
      foreach($this->race_list as &$racer){
        $racer["driver"]->putSoftTire();
      }
      unset($racer);
    }

      for($i = 1;$i<=12;$i++){
        foreach($this->race_list as &$racer){
          $racer["driver"]->setQualifyingTime($racer["driver"]->qualifying_lap_time($racer["car"],$this->track,$this->competition,$this->highest_level));
        }
        unset($racer);

        //define grid position array based on qualification time
        usort($this->race_list, function($a, $b) {
            return $a['driver']->getQualifyingTime() <=> $b['driver']->getQualifyingTime();
        });

        $this->recordResults("PQ-" . $i, (($this->base_timestamp) - 172800 + ($i) * 300 ));
      }

      while(count($this->race_list) > $max_drivers){
        array_pop($this->race_list);
      }

      $this->recordResults("PQ-G", (($this->base_timestamp) - 172800 + ($i + 1) * 300 ));




  }

  public function setTotalLaps(){
    $this->total_laps = min(ceil($this->competition->getTotalLength()/$this->track->getLength()), floor($this->competition->getMaxTime()/ $this->track->getFastestLap()));
  }

  public function qualifying(){
    $qualifying_laps = $this->competition->getQualifyingLaps();
    $this->track->setRain();

    if($this->track->isRaining()){
      foreach($this->race_list as &$racer){
        $racer["driver"]->putRainTire();
      }
      unset($racer);
    } else {
      foreach($this->race_list as &$racer){
        $racer["driver"]->putSoftTire();
      }
      unset($racer);
    }

    if($qualifying_laps > 0){ // qualificação convencional
      $this->recordResults("QC-0", (($this->base_timestamp) - 86400));
      for($i = 1;$i<=$qualifying_laps;$i++){
        foreach($this->race_list as &$racer){
            $racer["driver"]->setQualifyingTime($racer["driver"]->qualifying_lap_time($racer["car"],$this->track,$this->competition,$this->highest_level));
        }
        unset($racer);

        //define grid position array based on qualification time
        usort($this->race_list, function($a, $b) {
            return $a['driver']->getQualifyingTime() <=> $b['driver']->getQualifyingTime();
        });

        $this->recordResults("QC-" . $i, (($this->base_timestamp) - 86400 + ($i) * 180 ));
      }
      $this->recordResults("G-0", (($this->base_timestamp) - 86400 + ($i+1) * 180 ));
      $this->quali_end = (($this->base_timestamp) - 86400 + ($i+1) * 180 );
      $this->recordGridPositions();

    } else { //qualificação em q1/q2/q3
      $quali_lap_counter = 0;
      $this->recordResults("QE-0", (($this->base_timestamp) - 86400));
      // q1
      for($i = 1;$i<=6;$i++){
        $quali_lap_counter++;
        foreach($this->race_list as &$racer){
            $racer["driver"]->setQualifyingTime($racer["driver"]->qualifying_lap_time($racer["car"],$this->track,$this->competition,$this->highest_level));
        }
        unset($racer);

        //define grid position array based on qualification time
        usort($this->race_list, function($a, $b) {
            return $a['driver']->getQualifyingTime() <=> $b['driver']->getQualifyingTime();
        });


        $this->recordResults("QE-" . $quali_lap_counter, (($this->base_timestamp) - 86400 + ($quali_lap_counter) * 180 ));

      }
      $q1_drivers = array_splice($this->race_list, 15, count($this->race_list));
      foreach($this->race_list as &$racer){
          $racer["driver"]->resetQualifyingTime();
      }
      unset($racer);


      for($i = 1;$i<=5;$i++){
        $quali_lap_counter++;
        foreach($this->race_list as &$racer){
            $racer["driver"]->setQualifyingTime($racer["driver"]->qualifying_lap_time($racer["car"],$this->track,$this->competition,$this->highest_level));
        }
        unset($racer);

        //define grid position array based on qualification time
        usort($this->race_list, function($a, $b) {
            return $a['driver']->getQualifyingTime() <=> $b['driver']->getQualifyingTime();
        });


        $this->recordResults("QE-" . $quali_lap_counter, (($this->base_timestamp) - 86400 + ($quali_lap_counter) * 180 ));

      }
      $q2_drivers = array_splice($this->race_list, 10, count($this->race_list));
      foreach($this->race_list as &$racer){
          $racer["driver"]->resetQualifyingTime();
      }
      unset($racer);
      for($i = 1;$i<=4;$i++){
        $quali_lap_counter++;
        foreach($this->race_list as &$racer){
            $racer["driver"]->setQualifyingTime($racer["driver"]->qualifying_lap_time($racer["car"],$this->track,$this->competition,$this->highest_level));
        }
        unset($racer);

        //define grid position array based on qualification time
        usort($this->race_list, function($a, $b) {
            return $a['driver']->getQualifyingTime() <=> $b['driver']->getQualifyingTime();
        });


        $this->recordResults("QE-" . $quali_lap_counter, (($this->base_timestamp) - 86400 + ($quali_lap_counter) * 180 ));
      }
      $this->race_list = array_merge($this->race_list, $q2_drivers, $q1_drivers);
      $this->recordResults("G-0", (($this->base_timestamp) - 86400 + ($quali_lap_counter+1) * 180 ));
      $this->quali_end = (($this->base_timestamp) - 86400 + ($quali_lap_counter+1) * 180 );
      $this->recordGridPositions();

    }


  }

  public function recordResults($stage, $timestamp){
    $new_race_list = array();
    $new_race_list[0] = array("timestamp" => $timestamp, "rain_status" => $this->track->isRaining(), "safety_car_status" => $this->safety_car_status, "track_temp" => $this->track->getTrackTemp(), "air_temp" => $this->track->getAirTemp());
    foreach($this->race_list as $pair){
      $driver_clone = clone $pair["driver"];
      $car_clone = clone $pair["car"];
      $new_race_list[] = array("driver" => $driver_clone, "car" => $car_clone);
    }
    $this->lap_results[$stage] = $new_race_list;
  }

  public function runRace($stressTest = null){

    // rain calculation

    $this->track->setRain();

    if($this->track->isRaining()){
      foreach($this->race_list as &$racer){
        $racer["driver"]->putRainTire();
      }
      unset($racer);
    } else {
      foreach($this->race_list as &$racer){
        $racer["driver"]->putSoftTire();
      }
      unset($racer);
    }


    // end rain

    // strategy calculation
    $best_pit = $this->track->getPitBestLength() ;

    $best_strategy_list = array_keys($this->calculateBestStrategy());

    //var_dump($best_strategy_list);

      foreach($this->race_list as $position => &$racer){
        $racer["driver"]->definePitStrategy($best_strategy_list, $racer["car"]->getStrategy(), $this->total_laps);
        if($this->track->isRaining()){
          $racer["driver"]->putRainTire();
        } else {
          $racer["driver"]->setTire();
        }

      }
      unset($racer);

    //var_dump($best_strategy_list);

    // strategy end

    //create lap 1 (race start logic + 6s penalty) - no overtake threshold
    foreach($this->race_list as $position => &$racer){
        $racer['driver']->setStartPerformance($racer["car"],$this->track,$this->competition,$this->highest_level, $position);
        if($start_issue = $this->checkStartMistake($racer['driver'])){
          $this->setEvent();
          $racer['driver']->setIssueName($start_issue['name']);
          if($start_issue["abandon"] == true){
            $racer['driver']->abandonRace(0,0);
          } else {
            $racer['driver']->addStartPenalty($start_issue["time_penalty"]);
            if($start_issue["pit"]){
				$racer['driver']->setExtraPit();
				$racer['driver']->setStatus(1);
			}
          }
        } else {
          $this->resetEventCombo();
        }
    }
    unset($racer);



		//eliminate players with -1
	$in_race = array();
	$out_race = array();
	while(count($this->race_list) > 0){
		$element = array_shift($this->race_list);
		if ($element["driver"]->getStatus() < 0){
			$out_race[] = $element;
		} else {
			$in_race[] = $element;
		}
	}

	//define grid position array based on qualification time
    usort($in_race, function($a, $b) {
        return $b['driver']->getStartPerformance() <=> $a['driver']->getStartPerformance();
    });

    $lap_zero_time = 0;
    foreach($in_race as $position => &$racer ){
      $racer["driver"]->setTotalTime($lap_zero_time);
      $racer["driver"]->setLapTime($lap_zero_time);
      $lap_zero_time = $lap_zero_time + (mt_rand (0.2*1000, 0.3*1000) / 1000);

    }
    unset($racer);

	$this->race_list = array_merge($in_race, $out_race);

    $this->recordResults("R-0", $this->base_timestamp);

    $overtaking_threshold = $this->track->getOvertakingThreshold();

    // all laps

    for($lap = 1;$lap <= $this->total_laps; $lap++){
		
	  $this->current_lap = $lap;
 
      $next_total_time = 0;
      $next_lap_time = 0;
      $time_difference = 0;
      $threshold = 0;
	  if($this->safety_car_status == 0 && $this->event_counter > 0){
		  $this->safety_car_status = $this->checkSafetyCar($this->event_counter);
	  } else if($this->safety_car_status == 1){
		  if($this->safety_car_counter > 0){
          $this->safety_car_counter = $this->safety_car_counter - 1;
      } else {
          $this->safety_car_status = 0;
          $this->safety_car_penalty = 0;
      }
	  }
	  $this->resetEventCounter();
      $this->resetEventCombo();

      foreach($this->race_list as $key => &$racer){



        if($racer['driver']->getStatus() < 0){
          continue;
        } else {
			$racer['driver']->setIssueName("");
		}

        $leader_average_speed = 0;

        $racer["driver"]->setLapTime($racer["driver"]->race_lap_time($racer["car"],$this->track,$this->competition,$this->highest_level, $lap, $this->safety_car_penalty));

        $remaining_laps = $this->total_laps - $lap;
        if(($remaining_laps > 5 && $this->safety_car_status == 0 && $racer["driver"]->hasToPit($racer['car']->getStrategy()))){
          $racer["driver"]->pitHappened($racer["car"]->pit_stop_length($best_pit));

		  //pit failures
		  if($start_issue = $this->checkPitFailure($racer['car'])){
          $racer['driver']->setIssueName($start_issue['name']);
          if($start_issue["abandon"] == true){
            $racer['driver']->abandonRace($lap, $leader_average_speed);
          } else {
            $racer['driver']->addLapPenalty($start_issue["time_penalty"]);
            if($start_issue["pit"]){
				$racer['driver']->setExtraPit();
				$racer['driver']->setStatus(1);
			}
          }
        }


        } else {

		$temp_event = 0;
		//driver mistakes and collisions
		  if($start_issue = $this->checkLapMistake($racer['driver'])){
          $temp_event++;
          $racer['driver']->setIssueName($start_issue['name']);
          if($start_issue["abandon"] == true){
            $racer['driver']->abandonRace($lap, $leader_average_speed);
          } else {
            $racer['driver']->addLapPenalty($start_issue["time_penalty"]);
            if($start_issue["pit"]){
      				$racer['driver']->setExtraPit();
      				$racer['driver']->setStatus(1);
      			}
          }
        }

		//car failures
		if($start_issue = $this->checkCarFailure($racer['car'])){
          $temp_event++;
          $racer['driver']->setIssueName($start_issue['name']);
          if($start_issue["abandon"] == true){
            $racer['driver']->abandonRace($lap, $leader_average_speed);
          } else {
            $racer['driver']->addPermanentPenalty($start_issue["time_penalty"]);
            if($start_issue["pit"]){
				//$racer['driver']->setExtraPit();
				$racer['driver']->setStatus(1);
			}
          }
        }

		if($temp_event > 0){
			$this->setEvent();
		} else {
			$this->resetEventCombo();
		}

		}

      // leader verification
        if($key != 0) {


          // Start effects and distances determination
          if($lap == 1){
            //$time_difference = ($racer['driver']->getLapTime()  - $next_lap_time) + (0.25);
            $time_difference = ($racer['driver']->getLapTime()  - $next_lap_time) + ($racer['driver']->getTotalTime() - $next_total_time);
          } else {
            $time_difference = ($racer['driver']->getLapTime()  - $next_lap_time) + ($racer['driver']->getTotalTime() - $next_total_time);
          }

            // DRS allowed only after lap 3
           if($lap > 2 && ($racer["driver"]->getTotalTime() - $next_total_time) < 1 && $this->competition->getRaceType() == 0){
             $threshold = - $overtaking_threshold + 0.4;
           } else {
             $threshold = - $overtaking_threshold;
           }

           // overtake verification
          if($time_difference < $threshold && $this->safety_car_status == 0){
            $racer['driver']->addOvertake();
            $this->race_list[$key - 1]["driver"]->setOvertakePenalty();

			// overtake accidents
			      if($start_issue = $this->checkOvertakeMistake($racer['driver'], $this->race_list[$key - 1]["driver"])){
              $this->setEvent();
		          if(isset($start_issue[0])){
                $racer['driver']->setIssueName($start_issue[0]['name']);
                if($start_issue[0]["abandon"] == true){
                  $racer['driver']->abandonRace($lap, $leader_average_speed);
                } else {
                  $racer['driver']->addLapPenalty($start_issue[0]["time_penalty"]);
                  if($start_issue[0] ["pit"]){
				            $racer['driver']->setExtraPit();
				            $racer['driver']->setStatus(1);
			            }
                }
		          }
		          if(isset($start_issue[1])){
		            if($start_issue[1]["abandon"] == true){
                  $racer['driver']->abandonRace($lap, $leader_average_speed);
                } else {
                  $this->race_list[$key - 1]["driver"]->addLapPenalty($start_issue[1]["time_penalty"]);
                  if($start_issue[1] ["pit"]){
				            $this->race_list[$key - 1]["driver"]->setExtraPit();
				            $this->race_list[$key - 1]["driver"]->setStatus(1);
			            }
                }
		          }
            } else {
              $this->resetEventCombo();
            }

          } else if ($time_difference > $threshold && $time_difference < 0.2){
             $racer['driver']->increaseLapTime(0.2 - $time_difference);

           } else if ($time_difference < $threshold && $this->safety_car_status == 1){
			   $racer['driver']->increaseLapTime(0.2 - $time_difference);
		   }
         }

         if($key == 0){
           $leader_average_speed = $racer['driver']->getAverageSpeed();
            $leader_time = $racer['driver']->getTotalTime();
         }

         $distance_to_leader = $racer['driver']->getTotalTime() - $leader_time;
         $laps_behind = floor($distance_to_leader / $this->track->getFastestLap());
         $racer['driver']->recordLapInfo($time_difference, $threshold, $laps_behind);
         $next_lap_time = $racer['driver']->getLapTime();
         $next_total_time = $racer['driver']->getTotalTime();
         $racer['driver']->updateTotalTime();




      }
    unset($racer);


//eliminate players with -1
	$in_race = array();
	$out_race = array();
	while(count($this->race_list) > 0){
		$element = array_shift($this->race_list);
		if ($element["driver"]->getStatus() < 0){
			$out_race[] = $element;
		} else {
			$in_race[] = $element;
		}
	}

//define grid position array based on qualification time
    usort($in_race, function($a, $b) {
        return $a['driver']->getTotalTime() <=> $b['driver']->getTotalTime();
    });

	$this->race_list = array_merge($in_race, $out_race);

    $this->recordResults("R-" . $lap, ($this->base_timestamp) + ($this->race_list[0]["driver"]->getTotalTime()));

	if($this->race_list[0]["driver"]->getTotalTime() > $this->competition->getMaxTime()){
		break;
	}
	
    }


  

  if($stressTest == null){
	  
	 $this->recordRacePositions();
	 
	if($this->saveToJson($this->filename)){
	return true;
	} else {
	return false;
	}
  }


  }

  public function getRaceResults(){
    return $this->lap_results;
  }

  public function setFilename($filename = null){
    if($filename == null){
      $this->filename = "teste";
    } else {
      $this->filename = $filename;
    }
  }

// events

    public function checkPitFailure(Car $car){
    $types_of_failure = array(
      //array("failure_code" => 1, "failure_name" => "Stall", "severity" => 1, "time_penalty"  => 0),
      array("failure_code" => 1, "failure_name" => "Loose Wheel", "severity" => 1, "time_penalty"  => 0),
      array("failure_code" => 2, "failure_name" => "Tool malfunction", "severity" => 0, "time_penalty" => mt_rand(1,5))
    );

    $reliability = $car->getReliability();
	$pit_stop_skills = $car->getPitstopSkills();

    $failure_threshold = 0.0015 * (20 - $reliability - $pit_stop_skills) * $this->competition->getEventFactor();

    $failure_occurrence = mt_rand() / mt_getrandmax();

    if($failure_occurrence <= $failure_threshold){
      $failure = $types_of_failure[array_rand($types_of_failure)];

      $abandon_race = mt_rand() / mt_getrandmax();

      if($abandon_race < $failure["severity"] ){
        return array("name" => $failure["failure_name"], "abandon" => true, "time_penalty" => 0, "pit" => false);
      } else {
        return array("name" => $failure["failure_name"], "abandon" => false, "time_penalty" => $failure["time_penalty"], "pit" => true);
      }
    } else {
      return false;
    }

  }

  public function checkCarFailure(Car $car){
    $types_of_failure = array(
      array("failure_code" => 0, "failure_name" => "Engine", "severity" => 0.7, "time_penalty"  => (float)mt_rand(1,6)),
      array("failure_code" => 1, "failure_name" => "Suspension", "severity" => 1, "time_penalty"  => 0),
      array("failure_code" => 2, "failure_name" => "Electronics", "severity" => 1, "time_penalty"  => 0),
      array("failure_code" => 3, "failure_name" => "Gearbox", "severity" => 0.7, "time_penalty"  => (float)mt_rand(1,6)),
      array("failure_code" => 4, "failure_name" => "Hydraulics", "severity" => 1, "time_penalty"  => 0),
      array("failure_code" => 5, "failure_name" => "Nose damage", "severity" => 0.2, "time_penalty"  => 1),
      array("failure_code" => 6, "failure_name" => "Steering", "severity" => 1, "time_penalty"  => 0),
	  array("failure_code" => 7, "failure_name" => "Puncture", "severity" => 1, "time_penalty"  => 0)
    );
	
	  if($this->competition->getRaceType() == 0){
		array_push($types_of_failure, array("failure_code" => 8, "failure_name" => "DRS", "severity" => 1, "time_penalty"  => 0));
	  }

    $reliability = $car->getReliability();

    $failure_threshold = 0.0015 * (20 - 2*$reliability) * $this->competition->getEventFactor();

    $failure_occurrence = mt_rand() / mt_getrandmax();

    if($failure_occurrence <= $failure_threshold){
      $failure = $types_of_failure[array_rand($types_of_failure)];

      $abandon_race = mt_rand() / mt_getrandmax();

      if($abandon_race < $failure["severity"]){
        return array("name" => $failure["failure_name"], "abandon" => true, "time_penalty" => 0, "pit" => false);
      } else {
        return array("name" => $failure["failure_name"], "abandon" => false, "time_penalty" => $failure["time_penalty"], "pit" => false);
      }
    } else {
      return false;
    }

  }

  public function checkLapMistake(Driver $driver){
    $types_of_mistake = array(
      array("mistake_code" => 1, "mistake_name" => "Spin", "severity" => 0.3, "time_penalty"  => 5.0),
	  array("mistake_code" => 2, "mistake_name" => "Minor contact", "severity" => 0.0, "time_penalty"  => 2.0),
      array("mistake_code" => 3, "mistake_name" => "Contact", "severity" => 0.2, "time_penalty"  => 2.0),
      array("mistake_code" => 4, "mistake_name" => "Collision", "severity" => 1.0, "time_penalty"  => 0.0),

    );
    $aggressiveness = $driver->getAggressiveness();

    if($this->event_combo){
      $mistake_threshold = 0.1 * ($this->event_combo ** 2) * $this->competition->getEventFactor();
    } else {
      $mistake_threshold = 0.0015 * $aggressiveness * $this->competition->getEventFactor();
    }


    $rain_status = $this->track->isRaining();
    $mistake_threshold = $mistake_threshold * ($rain_status + 1);

    $mistake_occurrence = mt_rand() / mt_getrandmax();

    if($mistake_occurrence <= $mistake_threshold){
      $mistake = $types_of_mistake[array_rand($types_of_mistake)];

            $abandon_race = mt_rand() / mt_getrandmax();

            if($abandon_race < $mistake["severity"]){
              return array("name" => $mistake["mistake_name"], "abandon" => true, "time_penalty" => 0.0, "pit" => false);
            } else {
				if($mistake["severity"] == 0){
					$pit = false;
				} else {
					$pit = true;
				}

              return array("name" => $mistake["mistake_name"], "abandon" => false, "time_penalty" => $mistake["time_penalty"], "pit" => $pit);
            }
    } else {
      return false;
    }

  }

  public function checkStartMistake(Driver $driver){

  //verify events
    if($this->event_combo){
      $mistake_threshold = 0.1 * ($this->event_combo ** 2) * $this->competition->getEventFactor();

      $types_of_mistake = array(
		array("mistake_code" => 1, "mistake_name" => "Minor contact", "severity" => 0.0, "time_penalty"  => 2.0),
        array("mistake_code" => 2, "mistake_name" => "Contact", "severity" => 0.2, "time_penalty"  => 2.0),
        array("mistake_code" => 3, "mistake_name" => "Collision", "severity" => 1.0, "time_penalty"  => 0.0),
      );

    } else {

	//get parameters and calculate threshold
      $aggressiveness = $driver->getAggressiveness();
      $start_skills = $driver->getStartSkills();
      $mistake_threshold = 0.03 * ($aggressiveness + (10 - $start_skills)) * $this->competition->getEventFactor();

      $types_of_mistake = array(
        array("mistake_code" => 1, "mistake_name" => "Did not start", "severity" => 1.0, "time_penalty"  => 0.0),
        array("mistake_code" => 2, "mistake_name" => "Delayed start", "severity" => 0.0, "time_penalty"  => 3.0),
		array("mistake_code" => 3, "mistake_name" => "Minor contact", "severity" => 0.0, "time_penalty"  => 2.0),
        array("mistake_code" => 3, "mistake_name" => "Contact", "severity" => 0.2, "time_penalty"  => 2.0),
        array("mistake_code" => 4, "mistake_name" => "Collision", "severity" => 1.0, "time_penalty"  => 0.0),
      );

    }

	// rain influence
    $rain_status = $this->track->isRaining();
    $mistake_threshold = $mistake_threshold * ($rain_status + 1);

	// event ocurring
    $mistake_occurrence = mt_rand() / mt_getrandmax();

    if($mistake_occurrence <= $mistake_threshold){
        $mistake = $types_of_mistake[array_rand($types_of_mistake)];

            $abandon_race = mt_rand() / mt_getrandmax();

            if($abandon_race < $mistake["severity"]){
              return array("name" => $mistake["mistake_name"], "abandon" => true, "time_penalty" => 0, "pit" => false);
            } else {
				if($mistake["severity"] == 0){
					$pit = false;
				} else {
					$pit = true;
				}

              return array("name" => $mistake["mistake_name"], "abandon" => false, "time_penalty" => $mistake["time_penalty"], "pit" => $pit);
            }
    } else {
      return false;
    }

  }

    public function checkOvertakeMistake(Driver $driver){
    $types_of_mistake = array(
      array("mistake_code" => 1, "mistake_name" => "Spin", "severity" => 0.3, "time_penalty"  => 5.0),
	  array("mistake_code" => 2, "mistake_name" => "Minor contact", "severity" => 0.0, "time_penalty"  => 2.0),
      array("mistake_code" => 3, "mistake_name" => "Contact", "severity" => 0.2, "time_penalty"  => 2.0),
      array("mistake_code" => 4, "mistake_name" => "Collision", "severity" => 1.0, "time_penalty"  => 0.0),

    );
    $aggressiveness = $driver->getAggressiveness();

    if($this->event_combo){
      $mistake_threshold = 0.1 * ($this->event_combo ** 2) * $this->competition->getEventFactor();
    } else {
      $mistake_threshold = 0.001 * $aggressiveness * $this->competition->getEventFactor();
    }


    $rain_status = $this->track->isRaining();
    $mistake_threshold = $mistake_threshold * ($rain_status + 1);

    $mistake_occurrence[0] = mt_rand() / mt_getrandmax();
	$mistake_occurrence[1] = mt_rand() / mt_getrandmax();

    if($mistake_occurrence[0] <= $mistake_threshold){
      $mistake = $types_of_mistake[array_rand($types_of_mistake)];

            $abandon_race = mt_rand() / mt_getrandmax();

            if($abandon_race < $mistake["severity"]){
              $result_array[0] = array("name" => $mistake["mistake_name"], "abandon" => true, "time_penalty" => 0, "pit" => false);
            } else {
				if($mistake["severity"] == 0){
					$pit = false;
				} else {
					$pit = true;
				}

              $result_array[0] = array("name" => $mistake["mistake_name"], "abandon" => false, "time_penalty" => $mistake["time_penalty"], "pit" => $pit);
            }
    }

	    if($mistake_occurrence[1] <= $mistake_threshold){
      $mistake = $types_of_mistake[array_rand($types_of_mistake)];

            $abandon_race = mt_rand() / mt_getrandmax();

            if($abandon_race < $mistake["severity"]){
              $result_array[1] = array("name" => $mistake["mistake_name"], "abandon" => true, "time_penalty" => 0, "pit" => false);
            } else {
				if($mistake["severity"] == 0){
					$pit = false;
				} else {
					$pit = true;
				}

              $result_array[1] = array("name" => $mistake["mistake_name"], "abandon" => false, "time_penalty" => $mistake["time_penalty"], "pit" => $pit);
            }
    }

	if(isset($result_array)){
		return $result_array;
	} else {
		return false;
	}

  }

  public function checkSafetyCar($number_of_cars){

    $safety_car_threshold = 0.05 * ($number_of_cars ** 2);
    $safety_car_chance = mt_rand()/mt_getrandmax();

    if($safety_car_threshold > 2){
      return -1; // red flag
    } else if($safety_car_chance < $safety_car_threshold){

     $this->safety_car_status = 1;	 // safety car in
  	 $this->safety_car_counter = rand ( 1 , 6 );
  	 $this->safety_car_penalty = 4;
     return 1;

    } else {
      $this->safety_car_penalty = 0;
      return 0; // nothing happens
    }

  }

  private function saveToJson($filename){
	  $file_path = "/octamotor/races/";
    if($filename == "teste"){
      $filename = $this->current_user . "test.json";
    }
    if(!isset($_SERVER['DOCUMENT_ROOT'])){
      $_SERVER['DOCUMENT_ROOT'] = "/home/lhsaia/confusa.top/";
    }
	  if($file = fopen($_SERVER['DOCUMENT_ROOT'].$file_path . $filename, 'w')){
      fwrite($file, json_encode($this->lap_results));
      fclose($file);
      return true;
    } else {
      return false;
    }


  }

  private function calculateSoftWear($stint_lap){
    $soft_wear = 0.002 * ($stint_lap ** 2) + 0.02 * $stint_lap - 0.7;

    return $soft_wear;
  }

  private function calculateMediumWear($stint_lap){
    $medium_wear = 0.0005 * ($stint_lap ** 2) + 0.01 * $stint_lap;

    return $medium_wear;
  }

  private function calculateTotalLostTime($div_soft,$amount_soft, $amount_med, $amount_pits){
    $pit_time = $this->track->getPitBestLength();
    $race_laps = $this->total_laps;
    $div_med = $div_soft/2;

    $accumulated_soft_wear = 0;
    $accumulated_medium_wear = 0;

    for($i = 1; $i <= round($race_laps/$div_soft); $i++){
      $accumulated_soft_wear = $accumulated_soft_wear + $this->calculateSoftWear($i);
    }
    for($i = 1; $i <= round($race_laps/$div_med); $i++){
      $accumulated_medium_wear = $accumulated_medium_wear + $this->calculateMediumWear($i);
    }

    $total_lost_time = $amount_soft * $accumulated_soft_wear + $amount_med * $accumulated_medium_wear + $amount_pits * $pit_time;

    return $total_lost_time;

  }

  public function calculateBestStrategy(){

    $strategy_array = array();


    $strategy_array["SM"] = $this->calculateTotalLostTime(3,1,1,1);
    $strategy_array["SMM"] = $this->calculateTotalLostTime(5,1,2,2);
    $strategy_array["SSM"] = $this->calculateTotalLostTime(4,2,1,2);
    $strategy_array["SMMM"] = $this->calculateTotalLostTime(7,1,3,3);
    $strategy_array["SSMM"] = $this->calculateTotalLostTime(6,2,2,3);
    $strategy_array["SSSM"] = $this->calculateTotalLostTime(5,3,1,3);

    uasort($strategy_array, function($a, $b) {
        return $a - $b;
    });

    return array_slice($strategy_array, 0, 4);

  }

  public function getWeeklyRaces(){
    $current_time = time() - 7 * 60 * 60;
    $final_time = $current_time + (14 * 24 * 60 * 60);
    $current_year = date("Y");

    $query = "SELECT competition.logo, race.name, race.id, race.file, track.image, season.competition_id, season.year, race.datetime, p.nome as country_name FROM race LEFT JOIN season ON race.season_id = season.id LEFT JOIN track ON race.track_id = track.id LEFT JOIN competition ON competition.id = season.competition_id LEFT JOIN ".$this->db_name.".paises p ON p.id = race.country_id WHERE season.year = ?  AND race.datetime > ? AND (season.competition_id < 3 OR race.datetime < ?) ORDER BY race.datetime DESC";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1, $current_year);
    $stmt->bindParam(2, $current_time);
    $stmt->bindParam(3, $final_time);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $results;

  }

  public function createRace($race_data){
    foreach($race_data as &$data){
      $data = htmlspecialchars(strip_tags($data));
    }
    unset($data);

    $preQuery = "SELECT country FROM track WHERE id = ?";
    $preStmt = $this->conn->prepare($preQuery);
    $preStmt->bindParam(1, $race_data["race_track"]);
    $preStmt->execute();
    $track_id = $preStmt->fetchColumn();
    $query = "INSERT INTO race (name, season_id, track_id, datetime, country_id, status) VALUES (?,?,?,?,?,0) ";
    $stmt = $this->conn->prepare($query);
    $counter = 1;
    foreach($race_data as &$data){
      $stmt->bindParam($counter,$data);
      $counter++;
    }
    unset($data);
    $stmt->bindParam($counter, $track_id);
    if($stmt->execute()){
      return true;
    } else {
      return false;
    }
  }

  public function editRace($race_data){
    foreach($race_data as &$data){
      $data = htmlspecialchars(strip_tags($data));
    }
    unset($data);

    $preQuery = "SELECT country FROM track WHERE id = ?";
    $preStmt = $this->conn->prepare($preQuery);
    $preStmt->bindParam(1, $race_data["race_track"]);
    $preStmt->execute();
    $track_id = $preStmt->fetchColumn();
    $query = "UPDATE race SET name = ?, season_id = ?, track_id = ?, datetime = ?, country_id = ? WHERE id = ? AND status = 0 ";
    $stmt = $this->conn->prepare($query);
    $counter = 1;
    foreach($race_data as &$data){
      $stmt->bindParam($counter,$data);
      $counter++;
      if($counter == 5){
        $stmt->bindParam($counter, $track_id);
        $counter++;
      }
    }
    unset($data);

    if($stmt->execute()){
      return true;
    } else {
      return false;
    }
  }

  public function isNotOwner($season_id, $user_id){
    $season_id = htmlspecialchars(strip_tags($season_id));
    $user_id = htmlspecialchars(strip_tags($user_id));

    $query = "SELECT competition.owner FROM season LEFT JOIN competition ON competition.id = season.competition_id WHERE season.id = ? ";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1,$season_id);
    $stmt->execute();
    $result = $stmt->fetchColumn();

    if($result == $user_id){
      return false;
    } else {
      return true;
    }

  }


  public function supplySimulator($current_time, $time_span){
    $final_time = $current_time + $time_span;

    $query = "SELECT race.id, datetime, season_id, season.year as season_year, season.competition_id, track_id, name, status FROM race LEFT JOIN season ON season.id = race.season_id WHERE race.datetime < ? ORDER BY race.datetime";
    $stmt = $this->conn->prepare($query);
    //$stmt->bindParam(1, $current_time);
    $stmt->bindParam(1, $final_time);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $results;

  }

  public function complete($id, $filename){

        $query = "UPDATE race SET status = 1, file = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $filename);
        $stmt->bindParam(2, $id);
        if($stmt->execute()){
          return true;
        } else {
          return false;
        }

  }

  private function recordGridPositions(){
    $competition_id = $this->competition->getId();
    $max_drivers = $this->competition->getMaxDrivers();
    $quali_end = $this->quali_end;

    //foreach driver
    foreach($this->race_list as $key => $single_driver){
      $driver_id = $single_driver['driver']->getId();
      $car_id = $single_driver['car']->getId();
      $best_lap = $single_driver['driver']->getQualifyingTime();

      if($key >= $max_drivers){
        $position = "-1";
      } else {
        $position = $key + 1;
      }
      $query = "INSERT IGNORE INTO grid_position (race, driver, car, position, best_time, competition_id, timestamp) VALUES (?,?,?,?,?,?,?) ";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(1,$this->race_id);
      $stmt->bindParam(2,$driver_id);
      $stmt->bindParam(3,$car_id);
      $stmt->bindParam(4,$position);
      $stmt->bindParam(5,$best_lap);
      $stmt->bindParam(6,$competition_id);
      $stmt->bindParam(7,$quali_end);
      $stmt->execute();
    }

  }

  private function recordRacePositions(){

    $point_system_array = explode("-",$this->competition->getPointSystem());
    $competition_id = $this->competition->getId();
    $race_end = $this->base_timestamp + $this->race_list[0]["driver"]->getTotalTime();
	$race_last_lap = $this->current_lap;
	
	if(($race_last_lap / $this->total_laps) > 0.7){
		$total_points = true;
	} else {
		$total_points = false;
	}
	

    //foreach driver
    foreach($this->race_list as $key => $single_driver){
      $driver_id = $single_driver['driver']->getId();
      $car_id = $single_driver['car']->getId();
      $best_lap = $single_driver['driver']->getBestLapTime();
      if(isset($point_system_array[$key])){
		  if($total_points){
			$current_points = $point_system_array[$key];
		  } else {
			$current_points = $point_system_array[$key] / 2;
		  }
        
      } else {
        $current_points = 0;
      }
      if($single_driver['driver']->getStatus() < 0){
        $position = "-1";
		$current_points = 0;
      } else {
        $position = $key + 1;
      }
      $query = "INSERT IGNORE INTO race_position (race, driver, car, position, best_time, points, competition_id, timestamp) VALUES (?,?,?,?,?,?,?,?) ";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(1,$this->race_id);
      $stmt->bindParam(2,$driver_id);
      $stmt->bindParam(3,$car_id);
      $stmt->bindParam(4,$position);
      $stmt->bindParam(5,$best_lap);
      $stmt->bindParam(6,$current_points);
      $stmt->bindParam(7,$competition_id);
      $stmt->bindParam(8,$race_end);
      $stmt->execute();
    }


  }
  
  public function getLapResults(){
	  return $this->lap_results;
  }

}

// proximas atualizações

// penalização fixa se houver retardatários -> calculada sobre distancia e tempo base
// corrigir safety car -> em vez de penalização fixa, criar teto de tempo (ver se está funcionando)
// colocar safety car pra 1 piloto crash as vezes

 ?>
