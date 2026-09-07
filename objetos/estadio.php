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
    public $foto;

    public function __construct($db){
        $this->conn = $db;
    }

    function create(){

        //escrever query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    Nome=:nome, Capacidade=:capacidade, Clima=:clima, Altitude=:altitude, Caldeirao=:caldeirao, Pais=:pais, foto=:foto";

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->nome = trim(html_entity_decode(strip_tags((string)($this->nome ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $this->capacidade = htmlspecialchars(strip_tags((string)($this->capacidade ?? '')));
        $this->clima = htmlspecialchars(strip_tags((string)($this->clima ?? '')));
        $this->altitude = htmlspecialchars(strip_tags((string)($this->altitude ?? '')));
        $this->caldeirao = htmlspecialchars(strip_tags((string)($this->caldeirao ?? '')));
        $this->pais = htmlspecialchars(strip_tags((string)($this->pais ?? '')));
        $this->foto = ($this->foto !== null && $this->foto !== '') ? htmlspecialchars(strip_tags((string)$this->foto)) : null;
        
        $altVal = strtolower(trim((string)$this->altitude));
        if ($altVal === '1' || $altVal === 'true' || $this->altitude === 1 || $this->altitude === true) {
            $this->altitude = 1;
        } else {
            $this->altitude = 0;
        }
        
        $caldVal = strtolower(trim((string)$this->caldeirao));
        if ($caldVal === '1' || $caldVal === 'true' || $this->caldeirao === 1 || $this->caldeirao === true) {
            $this->caldeirao = 1;
        } else {
            $this->caldeirao = 0;
        }


        // bind values
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":capacidade", $this->capacidade);
        $stmt->bindParam(":clima", $this->clima);
        $stmt->bindParam(":altitude", $this->altitude);
        $stmt->bindParam(":caldeirao", $this->caldeirao);
        $stmt->bindParam(":pais", $this->pais);
        $stmt->bindParam(":foto", $this->foto);

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

    //alterar estádio
    function alterar($idEstadio,$nomeEstadio,$capacidade,$pais,$altitude, $caldeirao, $clima, $foto = null){

        $idEstadio = htmlspecialchars(strip_tags((string)($idEstadio ?? '')));
        $nomeEstadio = trim(html_entity_decode(strip_tags((string)($nomeEstadio ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $capacidade = htmlspecialchars(strip_tags((string)($capacidade ?? '')));
        $pais = htmlspecialchars(strip_tags((string)($pais ?? '')));
        $altitude = htmlspecialchars(strip_tags((string)($altitude ?? '')));
		$caldeirao = htmlspecialchars(strip_tags((string)($caldeirao ?? '')));
		$clima = htmlspecialchars(strip_tags((string)($clima ?? '')));
		$foto = ($foto !== null && $foto !== '') ? htmlspecialchars(strip_tags((string)$foto)) : null;
		
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

        $idPais = $idPais !== null ? htmlspecialchars(strip_tags((string)$idPais)) : null;
        $idTime = $idTime !== null ? htmlspecialchars(strip_tags((string)$idTime)) : null;

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
            $nomeLimpo = trim(html_entity_decode(strip_tags((string)($this->nome ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $nomeHtml = htmlspecialchars($nomeLimpo, ENT_QUOTES, 'UTF-8');

            $query = "SELECT count(ID) as total FROM estadio WHERE (Nome = ? OR Nome = ?) AND Pais = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $nomeLimpo);
            $stmt->bindParam(2, $nomeHtml);
            $stmt->bindParam(3, $this->pais);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = $row['total'] ?? 0;

            return $total;
        }

        function codigoPorNomeEPais(){
            $nomeLimpo = trim(html_entity_decode(strip_tags((string)($this->nome ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $nomeHtml = htmlspecialchars($nomeLimpo, ENT_QUOTES, 'UTF-8');

            $query = "SELECT ID FROM estadio WHERE (Nome = ? OR Nome = ?) AND Pais = ? LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $nomeLimpo);
            $stmt->bindParam(2, $nomeHtml);
            $stmt->bindParam(3, $this->pais);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $ID = $row['ID'] ?? null;

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
	
	
	    function createSqlite(){

        //escrever query
        $query = "INSERT INTO
                    estadio(ID, Nome, Capacidade, Clima, Altitude, Caldeirao)
                VALUES 
                    (:id,:nome,:capacidade,:clima,:altitude,:caldeirao) 
				ON CONFLICT DO NOTHING";

        $stmt = $this->conn->prepare($query);

        // posted values
		$this->id=htmlspecialchars(strip_tags($this->id));
        $this->nome=trim(html_entity_decode(strip_tags((string)($this->nome ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $this->capacidade=htmlspecialchars(strip_tags($this->capacidade));
        $this->clima=htmlspecialchars(strip_tags($this->clima));
        $this->altitude=htmlspecialchars(strip_tags($this->altitude));
        $this->caldeirao=htmlspecialchars(strip_tags($this->caldeirao));
        
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
		$stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":capacidade", $this->capacidade);
        $stmt->bindParam(":clima", $this->clima);
        $stmt->bindParam(":altitude", $this->altitude);
        $stmt->bindParam(":caldeirao", $this->caldeirao);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }
	
	function carregarListaEstadiosSqlite(){
	
		
		$query = "SELECT ID, Nome FROM estadio";
        $stmt = $this->conn->prepare( $query );

        $stmt->execute();
		return $stmt;
		
	}

    function readAllAjax($item_pesquisado, $dono = null){
        $item_pesquisado_limpo = trim(html_entity_decode(strip_tags((string)$item_pesquisado), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $item_pesquisado_html = htmlspecialchars($item_pesquisado_limpo, ENT_QUOTES, 'UTF-8');
        $dono = htmlspecialchars(strip_tags((string)$dono));

        if($dono === null || $dono == 0){
            $sub_query_fim = " WHERE (a.Nome LIKE ? OR a.Nome LIKE ?) ORDER BY a.Capacidade DESC, a.Nome ASC LIMIT 150";
        } else {
            $sub_query_fim = " WHERE p.dono = ? AND (a.Nome LIKE ? OR a.Nome LIKE ?) ORDER BY a.Capacidade DESC, a.Nome ASC LIMIT 150";
        } 

        $query = "SELECT
                    a.ID, a.Nome, a.Capacidade, a.Clima, c.Nome as nomeClima, a.Caldeirao, a.Altitude, p.sigla as siglaPais, p.bandeira as bandeiraPais, p.id as idPais, p.dono as idDonoPais, a.foto 
                FROM
                    " . $this->table_name . " a
                LEFT JOIN paises p ON a.Pais = p.id
                LEFT JOIN clima c ON a.Clima = c.ID
                " . $sub_query_fim;

        $stmt = $this->conn->prepare( $query );
        $param1 = "%" . $item_pesquisado_limpo . "%";
        $param2 = "%" . $item_pesquisado_html . "%";
            
        if($dono === null || $dono == 0){
            $stmt->bindParam(1, $param1);
            $stmt->bindParam(2, $param2);
        } else {
            $stmt->bindParam(1, $dono);
            $stmt->bindParam(2, $param1);
            $stmt->bindParam(3, $param2);
        } 

        $stmt->execute();
        return $stmt;
    }

}
?>
