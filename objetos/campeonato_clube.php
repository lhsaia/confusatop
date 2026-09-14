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
	public $trofeu;
	public $genero;
	public $dono;
 
    public function __construct($db){
        $this->conn = $db;
        $this->checkAndMigrateSchema();
    }

    public function checkAndMigrateSchema()
    {
        if (!$this->conn) {
            return;
        }
        try {
            $resCol = $this->conn->query("SHOW COLUMNS FROM `campeonatos_clube` LIKE 'trofeu'");
            if ($resCol && $resCol->rowCount() == 0) {
                $this->conn->exec("ALTER TABLE `campeonatos_clube` ADD COLUMN `trofeu` VARCHAR(255) NULL");
            }
        } catch (\Throwable $e) {}
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
		if(!empty($this->trofeu)) $query .= ", trofeu=:trofeu";

        $stmt = $this->conn->prepare($query);
	
        // posted values
        $this->nome=htmlspecialchars(strip_tags((string)$this->nome));
		$this->dono=htmlspecialchars(strip_tags((string)$this->dono));

        // bind values
        $stmt->bindParam(":nome", $this->nome);
		$stmt->bindParam(":dono", $this->dono);
		
		if(!empty($this->federacao)) {
			$this->federacao=htmlspecialchars(strip_tags((string)$this->federacao));
			$stmt->bindParam(":federacao", $this->federacao);
		}
		if(!empty($this->sede)) {
			$this->sede=htmlspecialchars(strip_tags((string)$this->sede));
			$stmt->bindParam(":sede", $this->sede);
		}
		if(!empty($this->genero)) {
			$this->genero=htmlspecialchars(strip_tags((string)$this->genero));
			$stmt->bindParam(":genero", $this->genero);
		}
		if(!empty($this->logo)) {
			$this->logo=htmlspecialchars(strip_tags((string)$this->logo));
			$stmt->bindParam(":logo", $this->logo);
		}
		if(!empty($this->trofeu)) {
			$this->trofeu=htmlspecialchars(strip_tags((string)$this->trofeu));
			$stmt->bindParam(":trofeu", $this->trofeu);
		}

        if($stmt->execute()){
			return true;
        } else {
            return false;
        }

    }

    function alterar($id, $nome, $logo = null, $trofeu = null){
        $id = htmlspecialchars(strip_tags((string)$id));
        $nome = htmlspecialchars(strip_tags((string)$nome));
        $logo = ($logo !== null && $logo !== '') ? htmlspecialchars(strip_tags((string)$logo)) : null;
        $trofeu = ($trofeu !== null && $trofeu !== '') ? htmlspecialchars(strip_tags((string)$trofeu)) : null;

        $subquery = "";
        if($logo !== null){
            $subquery .= ", logo=:logo";
        }
        if($trofeu !== null){
            $subquery .= ", trofeu=:trofeu";
        }

        $query = "UPDATE " . $this->table_name . " SET nome=:nome " . $subquery . " WHERE id=:id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nome", $nome);
        if($logo !== null){
            $stmt->bindParam(":logo", $logo);
        }
        if($trofeu !== null){
            $stmt->bindParam(":trofeu", $trofeu);
        }
        $stmt->bindParam(":id", $id);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }
    }
}
?>
