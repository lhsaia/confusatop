<?php
class EscudosPop{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "escudos_pop";

    // object properties
    public $id;
    public $nome_array;

    public function __construct($db){
        $this->conn = $db;
    }

    public function loadTeams(){
      $query = "SELECT * FROM   " . $this->table_name;
      $stmt = $this->conn->prepare($query);
      $stmt->execute();

      $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

      return $results;
    }

    public function loadTeamIds(){
      $query = "SELECT DISTINCT team_id FROM   " . $this->table_name;
      $stmt = $this->conn->prepare($query);
      $stmt->execute();

      $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

      return $results;
    }

    public function gravarPalpite($user_id, $selected_id, $team_name){
      $user_id = htmlspecialchars(strip_tags($user_id));
      $selected_id = htmlspecialchars(strip_tags($selected_id));
      $team_name = htmlspecialchars(strip_tags($team_name));

      $query = "INSERT IGNORE INTO escudos_pop_palpites (user_id, team_id, team_name) VALUES (?,?,?)";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(1,$user_id);
      $stmt->bindParam(2,$selected_id);
      $stmt->bindParam(3,$team_name);

      if($stmt->execute()){
        return true;
      } else {
        return false;
      }

    }

    public function lerPalpites($user_id){
      $user_id = htmlspecialchars(strip_tags($user_id));

      $query = "SELECT team_id, team_name FROM escudos_pop_palpites WHERE user_id = ?";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(1,$user_id);
      $stmt->execute();
      $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $result;

    }



}
?>
