<?php
class TrioArbitragem{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "arbitros";

    // object properties
    public $id;
    public $nomeArbitro;
    public $nomeAuxiliarUm;
    public $nomeAuxiliarDois;
    public $estilo;
    public $pais;
	public $nivel;
	public $nascimento;

    public function __construct($db){
        $this->conn = $db;
    }

    // criar trio de arbitragem
    function create(){

        //escrever query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    nomeArbitro=:nomeArbitro, nomeAuxiliarUm=:nomeAuxiliarUm, nomeAuxiliarDois=:nomeAuxiliarDois, estilo=:estilo, pais=:pais, nivel=:nivel, nascimento=:nascimento ";

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->nome=htmlspecialchars(strip_tags($this->nomeArbitro));
        $this->sigla=htmlspecialchars(strip_tags($this->nomeAuxiliarUm));
        $this->usuario=htmlspecialchars(strip_tags($this->nomeAuxiliarDois));
        $this->pontos=htmlspecialchars(strip_tags($this->estilo));
        $this->posicao=htmlspecialchars(strip_tags($this->pais));
		$this->nivel=htmlspecialchars(strip_tags($this->nivel));
		$this->nascimento=htmlspecialchars(strip_tags($this->nascimento));

         //verificar se árbitro já existe
         $tag_comparacao = (string)$this->nomeArbitro;

         $query_comparacao = "SELECT nomeArbitro FROM ". $this->table_name . " WHERE nomeArbitro = ?";
         $stmt_comparacao = $this->conn->prepare($query_comparacao);
         //$stmt_comparacao->bindParam(1, $this->nomeArbitro);
         $stmt_comparacao->bindParam(1, $this->nomeArbitro);
         $stmt_comparacao->execute();
         $result_comp = $stmt_comparacao->fetch(PDO::FETCH_ASSOC);
		 if(!isset($result_comp['nomeArbitro'])){
			 $tag_atual = "";
		 } else {
			 $tag_atual = (string)$result_comp['nomeArbitro'];
		 }
         

        // bind values
        $stmt->bindParam(":nomeArbitro", $this->nomeArbitro);
        $stmt->bindParam(":nomeAuxiliarUm", $this->nomeAuxiliarUm);
        $stmt->bindParam(":nomeAuxiliarDois", $this->nomeAuxiliarDois);
        $stmt->bindParam(":estilo", $this->estilo);
        $stmt->bindParam(":pais", $this->pais);
		$stmt->bindParam(":nivel", $this->nivel);
		$stmt->bindParam(":nascimento", $this->nascimento);


        if(trim($tag_atual) != trim($tag_comparacao)){
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    //ler todos os arbitros para o quadro
    function readAll($from_record_num, $records_per_page){

    $query = "SELECT
                a.id, a.nomeArbitro, a.nomeAuxiliarUm, a.nomeAuxiliarDois, a.estilo, p.sigla as siglaPais, p.bandeira as bandeiraPais, p.id as idPais, p.dono as idDonoPais, a.nivel, a.nascimento,FLOOR((DATEDIFF(CURDATE(), a.nascimento))/365) as idade, a.ativo 
            FROM
                " . $this->table_name . " a
            LEFT JOIN paises p ON a.pais = p.id
            ORDER BY
                a.nomeArbitro ASC
            LIMIT
                {$from_record_num}, {$records_per_page}";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    return $stmt;
    }

//ler todos os arbitros para o quadro, apenas de uma federacao
function readFromFederation($from_record_num, $records_per_page, $federation_index){

    $federation_index = htmlspecialchars(strip_tags($federation_index));

    $query = "SELECT
                a.id, a.nomeArbitro, a.nomeAuxiliarUm, a.nomeAuxiliarDois, a.estilo, p.sigla as siglaPais, p.bandeira as bandeiraPais, p.federacao, p.id as idPais, p.dono as idDonoPais, a.nivel, a.nascimento,FLOOR((DATEDIFF(CURDATE(), a.nascimento))/365) as idade, a.ativo 
            FROM
                " . $this->table_name . " a
            LEFT JOIN paises p ON a.pais = p.id
            WHERE
                p.federacao = ".$federation_index ."
            ORDER BY
                a.nomeArbitro ASC
            LIMIT
                {$from_record_num}, {$records_per_page}";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    return $stmt;
}


    // used for paging products
    public function countAll($federacao){

	if($federacao != null){
		$federacao = htmlspecialchars(strip_tags($federacao));
	}
    

    if($federacao == null || $federacao == 0 || $federacao == "0"){

        $query = "SELECT id FROM " . $this->table_name;

    } else {

        $query =    "SELECT a.id
                    FROM " . $this->table_name . " a
                     LEFT JOIN paises p ON a.pais = p.id
                      WHERE p.federacao=".$federacao;

    }

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    $num = $stmt->rowCount();

    return $num;
    }

    //apagar árbitro
    function apagar($idApagar){
        $idApagar = htmlspecialchars(strip_tags($idApagar));
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $idApagar);
        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }

    //alterar árbitro
    function alterar($idRecebida,$nomeArbitroRec,$nomeAux1Rec,$nomeAux2Rec,$estiloRec,$paisRec, $nivel, $status, $nascimento){

        $idRecebida = htmlspecialchars(strip_tags($idRecebida));
        $nomeArbitroRec = htmlspecialchars(strip_tags($nomeArbitroRec));
        $nomeAux1Rec = htmlspecialchars(strip_tags($nomeAux1Rec));
        $nomeAux2Rec = htmlspecialchars(strip_tags($nomeAux2Rec));
        $estiloRec = htmlspecialchars(strip_tags($estiloRec));
        $paisRec = htmlspecialchars(strip_tags($paisRec));
		$nivel = htmlspecialchars(strip_tags($nivel));
		$status = htmlspecialchars(strip_tags($status));
		$nascimento = htmlspecialchars(strip_tags($nascimento));

        $query = "UPDATE " . $this->table_name . " SET nomeArbitro = :nome, nomeAuxiliarUm = :aux1, nomeAuxiliarDois = :aux2, estilo = :estilo, pais = :pais, nivel = :nivel, ativo = :status, nascimento = :nascimento WHERE id = :id";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(":nome", $nomeArbitroRec);
        $stmt->bindParam(":aux1", $nomeAux1Rec);
        $stmt->bindParam(":aux2", $nomeAux2Rec);
        $stmt->bindParam(":estilo", $estiloRec);
        $stmt->bindParam(":pais", $paisRec);
		$stmt->bindParam(":nivel", $nivel);
		$stmt->bindParam(":status", $status);
		$stmt->bindParam(":nascimento", $nascimento);
        $stmt->bindParam(":id", $idRecebida);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }

    //transpor para tabela de exportação
    function exportacao($idPais = null, $idFederacao = null){

        $idPais = htmlspecialchars(strip_tags($idPais));
        $idFederacao = htmlspecialchars(strip_tags($idFederacao));



        if($idPais != null){
          $subquery = " a.pais=:pais ";
        } else if($idFederacao != 0) {
          $subquery = " p.federacao=:federacao ";
        } else {
          $subquery = " 1=1 ";
        }



        $query = "SELECT a.id, CONCAT(a.nomeArbitro,' [',p.sigla,']' ) as nomeArbitro, CONCAT(a.nomeAuxiliarUm,' [',p.sigla,']' ) as nomeAuxiliarUm, CONCAT(a.nomeAuxiliarDois,' [',p.sigla,']' ) as nomeAuxiliarDois, a.estilo FROM arbitros a LEFT JOIN paises p ON a.pais = p.id WHERE " . $subquery;
        $stmt = $this->conn->prepare( $query );
        if($idPais != null){
          $stmt->bindParam(":pais", $idPais);
        } else {
          $stmt->bindParam(":federacao", $idFederacao);
        }
        $stmt->execute();

        return $stmt;

    }
	
	public function aniversario_reverso( $idade ){
        $dias = mt_rand(1,350);
        return date('Y-m-d', strtotime($idade . ' years '.$dias.' days ago'));
    }

    function randomTrio($nacionalidade, $origemNomeArbitro, $origemSobrenomeArbitro, $ocorrenciaNomeDuploArbitro, $indiceMiscigenacaoArbitro, $origemNomeAux1, $origemSobrenomeAux1, $ocorrenciaNomeDuploAux1, $indiceMiscigenacaoAux1, $origemNomeAux2, $origemSobrenomeAux2, $ocorrenciaNomeDuploAux2, $indiceMiscigenacaoAux2, $sexo){

		$idade = $this->sorteia(20,40,25);
		$this->nascimento = $this->aniversario_reverso($idade);
		
        $this->estilo = $this->sorteia(1,5);
        $this->pais = $nacionalidade;

        if($sexo != 2){
            $sexoArbitro = $sexo;
            $sexoAux1 = $sexo;
            $sexoAux2 = $sexo;
        } else {
            $sexoArbitro = $this->sorteia(0,1);
            $sexoAux1 = $this->sorteia(0,1);
            $sexoAux2 = $this->sorteia(0,1);
        }

        //nome e sobrenome

        //arbitro
        //chance de nome duplo
        $chanceNomeDuploArbitro = mt_rand(1,101);
        if($chanceNomeDuploArbitro <= $ocorrenciaNomeDuploArbitro){
            $nomeDuploArbitro = true;
        } else {
            $nomeDuploArbitro = false;
        }

        //chance de miscigenacao
        $chanceMiscigenacaoArbitro = mt_rand(0,101);
        if($chanceMiscigenacaoArbitro <= $indiceMiscigenacaoArbitro){
            $this->nomeArbitro = $this->geranomes($origemNomeArbitro, $origemSobrenomeArbitro, $nomeDuploArbitro, $sexoArbitro);
        } else {
            $this->nomeArbitro = $this->geranomes($origemNomeArbitro, $origemNomeArbitro, $nomeDuploArbitro, $sexoArbitro);
        }

        //auxiliar 1
        //chance de nome duplo
        $chanceNomeDuploAux1 = mt_rand(1,101);
        if($chanceNomeDuploAux1 <= $ocorrenciaNomeDuploAux1){
            $nomeDuploAux1 = true;
        } else {
            $nomeDuploAux1 = false;
        }

        //chance de miscigenacao
        $chanceMiscigenacaoAux1 = mt_rand(0,101);
        if($chanceMiscigenacaoAux1 <= $indiceMiscigenacaoAux1){
            $this->nomeAuxiliarUm = $this->geranomes($origemNomeAux1, $origemSobrenomeAux1, $nomeDuploAux1, $sexoAux1);
        } else {
            $this->nomeAuxiliarUm = $this->geranomes($origemNomeAux1, $origemNomeAux1, $nomeDuploAux1, $sexoAux1);
        }

        //auxiliar 2
        //chance de nome duplo
        $chanceNomeDuploAux2 = mt_rand(1,101);
        if($chanceNomeDuploAux2 <= $ocorrenciaNomeDuploAux2){
            $nomeDuploAux2 = true;
        } else {
            $nomeDuploAux2 = false;
        }

        //chance de miscigenacao
        $chanceMiscigenacaoAux2 = mt_rand(0,101);
        if($chanceMiscigenacaoAux2 <= $indiceMiscigenacaoAux2){
            $this->nomeAuxiliarDois = $this->geranomes($origemNomeAux2, $origemSobrenomeAux2, $nomeDuploAux2, $sexoAux2);
        } else {
            $this->nomeAuxiliarDois = $this->geranomes($origemNomeAux2, $origemNomeAux2, $nomeDuploAux2, $sexoAux2);
        }
    }

    function sorteia($inferior, $superior, $media = null){

        if($media == null){

            return (float)mt_rand($inferior, $superior);

        }

        $desvPadInf = ($media - $inferior) / 3.0;
        $desvPadSup = ($superior - $media) / 3.0;

        do {
            $rand1 = (float)mt_rand()/(float)mt_getrandmax();
            $rand2 = (float)mt_rand()/(float)mt_getrandmax();
            $gaussian_number = sqrt(-2 * log($rand1)) * cos(2 * M_PI * $rand2);
            if($media == $inferior){
                $gaussian_number = abs($gaussian_number);
            }
            if($media == $superior){
                $gaussian_number = - abs($gaussian_number);
            }
            if ($gaussian_number < 0) {
                $numeroSorteado = round($media + $desvPadInf*$gaussian_number);
            } else {
                $numeroSorteado = round($media + $desvPadSup*$gaussian_number);
            }
        } while ($numeroSorteado < $inferior || $numeroSorteado > $superior);

    return $numeroSorteado;

    }

    function geranomes($origemNomes, $origemSobrenomes,$nomeDuplo, $sexo){

        $listaNomes = array();

        $busca = ($sexo == 1 ? 'F' : 'M');
        if($origemNomes == 0){
            $query = "SELECT Nome FROM gen_nomes WHERE {$busca} = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
                $listaNomes[] = $result['Nome'];
            }
        } else {
            $query = "SELECT Nome FROM gen_nomes WHERE Origem = ? AND {$busca} = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$origemNomes);
            $stmt->execute();
            while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
                $listaNomes[] = $result['Nome'];
            }
        }

        $listaSobrenomes = array();
        if($origemSobrenomes == 0){
            $query = "SELECT Sobrenome FROM gen_sobrenomes WHERE {$busca} = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
                $listaSobrenomes[] = $result['Sobrenome'];
            }

        } else {
            $query = "SELECT Sobrenome FROM gen_sobrenomes WHERE Origem = ? AND {$busca} = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$origemSobrenomes);
            $stmt->execute();
            while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
                $listaSobrenomes[] = $result['Sobrenome'];
            }
        }

        //$nome = array_rand($listaNomes);

        $nome = random_int(0,count($listaNomes) - 1);
        $nome = $listaNomes[$nome];
        if($nomeDuplo === true){
            //$segundoNome = array_rand($listaNomes);
            $segundoNome = random_int(0,count($listaNomes) - 1);
            $segundoNome = $listaNomes[$segundoNome];
            $nome = $nome . " " . $segundoNome;
        }
        $sobrenome = random_int(0,count($listaSobrenomes) - 1);
        //$sobrenome = array_rand($listaSobrenomes);
        $sobrenome = $listaSobrenomes[$sobrenome];

        return $nome . " " . $sobrenome;
        //return $origemNomes . "- " . $origemSobrenomes . "- " . $nomeDuplo;
    }
}
?>
