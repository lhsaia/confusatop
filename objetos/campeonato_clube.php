<?php
class Campeonato_clube{
 
    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "campeonatos_clube";
 
    // object properties
    public $id;
    public $nome;
    public $federacao;
	public $sede;
	public $logo;
	public $genero;
	public $dono;
 
    public function __construct($db){
        $this->conn = $db;
    }
 
    function inserir(){

        //escrever query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    nome=:nome, dono=:dono";
					
		// Add optional fields if they exist in the object and are not null
		if(!empty($this->federacao)) $query .= ", federacao=:federacao";
		if(!empty($this->sede)) $query .= ", sede=:sede";
		if(!empty($this->genero)) $query .= ", genero=:genero";
		if(!empty($this->logo)) $query .= ", logo=:logo";

        $stmt = $this->conn->prepare($query);
	
        // posted values
        $this->nome=htmlspecialchars(strip_tags($this->nome));
		$this->dono=htmlspecialchars(strip_tags($this->dono));

        // bind values
        $stmt->bindParam(":nome", $this->nome);
		$stmt->bindParam(":dono", $this->dono);
		
		if(!empty($this->federacao)) {
			$this->federacao=htmlspecialchars(strip_tags($this->federacao));
			$stmt->bindParam(":federacao", $this->federacao);
		}
		if(!empty($this->sede)) {
			$this->sede=htmlspecialchars(strip_tags($this->sede));
			$stmt->bindParam(":sede", $this->sede);
		}
		if(!empty($this->genero)) {
			$this->genero=htmlspecialchars(strip_tags($this->genero));
			$stmt->bindParam(":genero", $this->genero);
		}
		if(!empty($this->logo)) {
			$this->logo=htmlspecialchars(strip_tags($this->logo));
			$stmt->bindParam(":logo", $this->logo);
		}

        if($stmt->execute()){
			return true;
        } else {
            return false;
        }

    }
}
?>
