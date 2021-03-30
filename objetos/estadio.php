<?php
class Estadio{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "estadio";

    // object properties
    public $id;
    public $nome;
    public $capacidade;
    public $clima;
    public $altitude;
    public $caldeirao;
    public $pais;

    public function __construct($db){
        $this->conn = $db;
    }

    // criar time
    function create(){

        //escrever query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    Nome=:nome, Capacidade=:capacidade, Clima=:clima, Altitude=:altitude, Caldeirao=:caldeirao, Pais=:pais";

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->nome=htmlspecialchars(strip_tags($this->nome));
        $this->capacidade=htmlspecialchars(strip_tags($this->capacidade));
        $this->clima=htmlspecialchars(strip_tags($this->clima));
        $this->altitude=htmlspecialchars(strip_tags($this->altitude));
        $this->caldeirao=htmlspecialchars(strip_tags($this->caldeirao));
        $this->pais=htmlspecialchars(strip_tags($this->pais));
        
        if($this->altitude == "false"){
            $this->altitude = 0;
        } else if ($this->altitude == "true"){
            $this->altitude = 1;
        }
        
        if($this->caldeirao == "false"){
            $this->caldeirao = 0;
        } else if ($this->caldeirao == "true"){
            $this->caldeirao = 1;
        }


        // bind values
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":capacidade", $this->capacidade);
        $stmt->bindParam(":clima", $this->clima);
        $stmt->bindParam(":altitude", $this->altitude);
        $stmt->bindParam(":caldeirao", $this->caldeirao);
        $stmt->bindParam(":pais", $this->pais);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }

    //ler todos os jogadores para o quadro
    function readAll($from_record_num, $records_per_page, $dono){

        $dono = htmlspecialchars(strip_tags($dono));

    $query = "SELECT
                a.ID, a.Nome, a.Capacidade, a.Clima, c.Nome as nomeClima, a.Caldeirao, a.Altitude, p.sigla as siglaPais, p.bandeira as bandeiraPais, p.id as idPais, p.dono as idDonoPais, a.foto 
            FROM
                " . $this->table_name . " a
            LEFT JOIN paises p ON a.Pais = p.id
            LEFT JOIN clima c ON a.Clima = c.ID
            WHERE p.dono = ?
            ORDER BY
                a.Capacidade DESC

            LIMIT
                {$from_record_num}, {$records_per_page}";

    $stmt = $this->conn->prepare( $query );
    $stmt->bindParam(1, $dono);
    $stmt->execute();

    return $stmt;
    }

    // used for paging products
    public function countAll($dono){

    $dono = htmlspecialchars(strip_tags($dono));



        $query =    "SELECT a.id
                    FROM " . $this->table_name . " a
                     LEFT JOIN paises p ON a.pais = p.id
                      WHERE p.dono =".$dono;


    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    $num = $stmt->rowCount();

    return $num;
    }

    //apagar jogador
    // - se o jogador tiver em time ou transferencias, não apaga
    function apagar($idApagar){
        // $idApagar = htmlspecialchars(strip_tags($idApagar));
        // $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        // $stmt = $this->conn->prepare( $query );
        // $stmt->bindParam(1, $idApagar);
        // if($stmt->execute()){
        //     return true;
        // } else {
        //     return false;
        // }

    }

    //alterar jogador
    function alterar($idEstadio,$nomeEstadio,$capacidade,$pais,$altitude, $caldeirao, $clima, $foto = null){

        $idEstadio = htmlspecialchars(strip_tags($idEstadio));
        $nomeEstadio = htmlspecialchars(strip_tags($nomeEstadio));
        $capacidade = htmlspecialchars(strip_tags($capacidade));
        $pais = htmlspecialchars(strip_tags($pais));
        $altitude = htmlspecialchars(strip_tags($altitude));
		$caldeirao = htmlspecialchars(strip_tags($caldeirao));
		$clima = htmlspecialchars(strip_tags($clima));
		$foto = htmlspecialchars(strip_tags($foto));
		
		$altitude = ($altitude == 'true') ? 1 : 0;
		$caldeirao = ($caldeirao == 'true') ? 1 : 0;
		
		if($foto != "" && $foto != null){
			$query_foto = ", foto=:foto";
		} else {
			$query_foto = "";
		}
		

        $query = "UPDATE " . $this->table_name . " SET Nome=:nome, Capacidade=:capacidade, Clima=:clima, Altitude=:altitude, Caldeirao=:caldeirao, Pais=:pais ".$query_foto." WHERE ID=:id";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(":nome", $nomeEstadio);
        $stmt->bindParam(":capacidade", $capacidade);
		$stmt->bindParam(":clima", $clima);
		$stmt->bindParam(":altitude", $altitude);
		$stmt->bindParam(":caldeirao", $caldeirao);
        $stmt->bindParam(":pais", $pais);
        $stmt->bindParam(":id", $idEstadio);
		if($foto != "" && $foto != null){
			$stmt->bindParam(":foto", $foto);
		} 

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }

    function exportacao($idPais = null, $idTime = null){

        $idPais = htmlspecialchars(strip_tags($idPais));
        $idTime = htmlspecialchars(strip_tags($idTime));

        if($idPais != null){
          $query = "SELECT e.ID, e.Nome, e.Capacidade, e.Clima, e.Altitude, e.Caldeirao FROM estadio e WHERE e.Pais=:pais";
        } else {
          $query = "SELECT DISTINCT e.ID, e.Nome, e.Capacidade, e.Clima, e.Altitude, e.Caldeirao FROM clube b LEFT JOIN estadio e ON e.ID = b.Estadio WHERE b.ID=:clube";
        }
        $stmt = $this->conn->prepare( $query );
        if($idPais != null){
          $stmt->bindParam(":pais", $idPais);
        } else {
          $stmt->bindParam(":clube", $idTime);
        }
        $stmt->execute();

        return $stmt;

    }

        //verificar se já existe
        function verificar(){

            $query = "SELECT count(ID) as total FROM estadio WHERE Nome = ? AND Pais = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$this->nome);
            $stmt->bindParam(2,$this->pais);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = $row['total'];

            return $total;
        }

        function codigoPorNomeEPais(){

            $query = "SELECT ID FROM estadio WHERE Nome = ? AND Pais = ? LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$this->nome);
            $stmt->bindParam(2,$this->pais);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $ID = $row['ID'];

            return $ID;
        }

        // used by select drop-down list
     function read($dono){

        //select all data
        $query = "SELECT
                    a.id, a.nome, a.capacidade, a.Pais
                FROM
                    " . $this->table_name . " a
                LEFT JOIN
                    paises p ON a.Pais = p.id
                WHERE p.dono = ?
                ORDER BY
                    nome";

        $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1, $dono);

        $stmt->execute();

        return $stmt;
    }

    public function coletarEstadioTime($idTime){
      $idTime = htmlspecialchars(strip_tags($idTime));

      $query = "SELECT a.id, a.Nome, a.Capacidade, a.Clima, a.Caldeirao, a.Altitude FROM " . $this->table_name . " a LEFT JOIN clube c ON c.Estadio = a.ID WHERE c.ID = ?";
      $stmt = $this->conn->prepare( $query );
      $stmt->bindParam(1, $idTime);
      $stmt->execute();

      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



}
?>
