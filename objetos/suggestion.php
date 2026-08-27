<?php
class Suggestion{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "suggestions";

    // object properties
    public $id;
    public $title;
	public $description;
	public $type;
	public $status;
	public $originator;
	

    public function __construct($db){
        $this->conn = $db;
    }
	
	public function readSuggestions($search_term, $user){
	  $search_term = htmlspecialchars(strip_tags($search_term));
	  $user = htmlspecialchars(strip_tags($user));
	  $search_term = "%" . $search_term . "%";
		
	  $query = "SELECT a.*, count(b.suggestion) as vote_count, SUM(case when b.user = ? then 1 else 0 end) as voted_by_user FROM   " . $this->table_name . " a LEFT JOIN suggestions_votes b ON b.suggestion = a.id WHERE (title LIKE ? OR description LIKE ?) GROUP BY a.id ORDER BY (a.status = 0) DESC, vote_count DESC";
	  
      $stmt = $this->conn->prepare($query);
	  $stmt->bindParam(1, $user );
	  $stmt->bindParam(2, $search_term );
	  $stmt->bindParam(3, $search_term );
      $stmt->execute();

      return $stmt;
	}
	
	public function insertSuggestion($title, $description, $type, $originator){
      $title = htmlspecialchars(strip_tags($title));
      $description = htmlspecialchars(strip_tags($description));
	  $type = htmlspecialchars(strip_tags($type));
	  $originator = htmlspecialchars(strip_tags($originator));

      $query = "INSERT INTO " . $this->table_name . " (title, description, type, originator, status) VALUES (?,?,?,?,0)";
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(1,$title);
      $stmt->bindParam(2,$description);
      $stmt->bindParam(3,$type);
	  $stmt->bindParam(4,$originator);

      if($stmt->execute()){
        return true;
      } else {
        return false;
      }
	}
	
	public function toggleVote($user, $suggestion){
	  $user = htmlspecialchars(strip_tags($user));
	  $suggestion = htmlspecialchars(strip_tags($suggestion));
		
	  $checkQuery = "SELECT ID FROM suggestions_votes WHERE user = ? AND suggestion = ?";
	  $stmtCheck = $this->conn->prepare($checkQuery);
	  $stmtCheck->execute([$user, $suggestion]);
	  
	  if ($stmtCheck->fetchColumn()) {
	      $query = "DELETE FROM suggestions_votes WHERE user = ? AND suggestion = ?";
	      $stmt = $this->conn->prepare($query);
	      return $stmt->execute([$user, $suggestion]);
	  } else {
	      $query = "INSERT INTO suggestions_votes (user, suggestion) VALUES (?, ?)";
	      $stmt = $this->conn->prepare($query);
	      return $stmt->execute([$user, $suggestion]);
	  }
	}
	
	public function updateStatus($id, $status){
		$id = (int)$id;
		$status = (int)$status;
		
		$query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(":status", $status, PDO::PARAM_INT);
		$stmt->bindParam(":id", $id, PDO::PARAM_INT);
		return $stmt->execute();
	}
}
?>
