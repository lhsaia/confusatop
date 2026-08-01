<?php
class Dicionario{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "dicionario";
	private $language;

    public function __construct($db, $language){
        $this->conn = $db;
		$this->language = $language;
    }
	
	
	public function t($token){
	  $token = htmlspecialchars(strip_tags($token));
		
	  $query = "SELECT ".$this->language." FROM   " . $this->table_name . " WHERE token = :token";
	  
      $stmt = $this->conn->prepare($query);
	  $stmt->bindParam(":token", $token );
      $stmt->execute();
	  $result = $stmt->fetch();
	  
      return $result[0];
	}
	
	
}
?>
