<?php
class Pais{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "paises";

    // object properties
    public $id;
    public $nome;
    public $sigla;
    public $dono;
    public $pontos;
    public $posicao;
    public $bandeira;
    public $pontos_anteriores;
    public $federacao;


    public function __construct($db){
        $this->conn = $db;
    }

    // criar pais
    function inserir(){

        //escrever query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    nome=:nome, sigla=:sigla, dono=:dono, pontos=1000, posicao=0, bandeira=:bandeira, pontos_anteriores=1000, ranqueavel=:ranqueado, federacao=:federacao";

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->nome=htmlspecialchars(strip_tags($this->nome));
        $this->sigla=htmlspecialchars(strip_tags($this->sigla));
        $this->dono=htmlspecialchars(strip_tags($this->dono));
        $this->bandeira=htmlspecialchars(strip_tags($this->bandeira));
        $this->ranqueado=htmlspecialchars(strip_tags($this->ranqueado));
        $this->federacao=htmlspecialchars(strip_tags($this->federacao));

        // bind values
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":sigla", $this->sigla);
        $stmt->bindParam(":dono", $this->dono);
        $stmt->bindParam(":bandeira", $this->bandeira);
        $stmt->bindParam(":ranqueado", $this->ranqueado);
        $stmt->bindParam(":federacao", $this->federacao);

        if($stmt->execute()){
            return true;
        }else{
            return false;
        }

    }

    //ler todos os paises para o ranking
    function readAll($from_record_num, $records_per_page, $idDono = null){

    if($idDono != null){
        $subquery = " WHERE dono = ?";
    } else {
        $subquery = " WHERE ranqueavel = 0 ";
    }

    $query = "SELECT
                a.id, a.nome, pontos, bandeira, posicao, pontos_anteriores, ativo, sigla, f.nome as federacao, f.id as idFederacao, ranqueavel
            FROM
                " . $this->table_name . " a
            LEFT JOIN federacoes f ON f.id = a.federacao
             ".$subquery."
            ORDER BY
                ativo DESC, pontos DESC
            LIMIT
                {$from_record_num}, {$records_per_page}";

    $stmt = $this->conn->prepare( $query );

    if($idDono != null){
        $stmt->bindParam(1,$idDono);
    }

    $stmt->execute();

    return $stmt;
    }

//ler todos os paises para o ranking, apenas de uma federacao
function readFromFederation($from_record_num, $records_per_page, $federation_index){

    $federation_index = htmlspecialchars(strip_tags($federation_index));

    $query = "SELECT
                id, nome, pontos, bandeira, posicao, pontos_anteriores, ativo
            FROM
                " . $this->table_name . "
            WHERE
                federacao = ".$federation_index ."
            ORDER BY
                ativo DESC, pontos DESC
            LIMIT
                {$from_record_num}, {$records_per_page}";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    return $stmt;
}


    //ler um pais para o ranking
    function readOne($id){

        $id = htmlspecialchars(strip_tags($id));

    $query = "SELECT
                pontos
            FROM
                " . $this->table_name . "
            WHERE
                id={$id}";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    return $stmt;
    }

    function readInfo($id){

        $id = htmlspecialchars(strip_tags($id));

    $query = "SELECT
                nome, sigla, pontos, bandeira, federacao, ativo, ranqueavel
            FROM
                " . $this->table_name . "
            WHERE
                id={$id}";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    return $stmt;

    }


    function readMoreInfo($idPais){
        $query = "SELECT avg(DATEDIFF(NOW(), j.Nascimento)/365) as mediaIdade, SUM(j.valor) as valorTotal, sum(case when j.Pais != b.Pais then 1 else 0 end) as estrangeiros, count(j.id) as jogadores, p.dono as idDonoPais, count(DISTINCT b.id) as clubes
        FROM contratos_jogador c
        LEFT JOIN jogador j ON c.jogador = j.id
        LEFT JOIN paises p ON j.Pais = p.id
        LEFT JOIN clube b ON c.clube = b.id
        WHERE b.Pais = {$idPais}";

        $stmt = $this->conn->prepare( $query );
        $stmt->execute();
        $info = $stmt->fetch(PDO::FETCH_ASSOC);

        return $info;
    }

    // atualizar ranking paises
    function atualizarPaisRanking($id, $pontos_anteriores, $pontos, $ultima_data){

        $id = htmlspecialchars(strip_tags($id));
        $pontos_anteriores = htmlspecialchars(strip_tags($pontos_anteriores));
        $pontos = htmlspecialchars(strip_tags($pontos));
        $ultima_data = htmlspecialchars(strip_tags($ultima_data));



    $query = "UPDATE
                " . $this->table_name . "
             SET
                pontos_anteriores={$pontos_anteriores}, pontos={$pontos}, ultimo_jogo='{$ultima_data}'
             WHERE
                id={$id}";


    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    return $stmt;
    }

    function atualizarAtividade(){

                 $query = "UPDATE
                " . $this->table_name . "
             SET
                ativo=0
             WHERE
                 ultimo_jogo = '00-00-0000'";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();


         $query = "UPDATE
                " . $this->table_name . "
             SET
                ativo=0
             WHERE
                 DATEDIFF(CURDATE(), ultimo_jogo)>=365";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();


        $query = "UPDATE
                " . $this->table_name . "
             SET
                ativo=1
             WHERE
                 DATEDIFF(CURDATE(), ultimo_jogo)<365";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    }


    // used for paging products
    public function countAll($federacao = null, $donoPais = null){

        $federacao = htmlspecialchars(strip_tags($federacao));

    if($federacao != null){

        $query = "SELECT id FROM " . $this->table_name . " WHERE ranqueavel = 0 AND federacao=".$federacao;



    } else if($donoPais != null){

        $query = "SELECT id FROM " . $this->table_name . " WHERE ranqueavel = 0 AND dono=" . $donoPais;

    }else {

        $query = "SELECT id FROM " . $this->table_name . " WHERE ranqueavel = 0 ";

    }

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    $num = $stmt->rowCount();

    return $num;
    }

    // used for paging products
    public function countAllActive(){

    $query = "SELECT id FROM  " . $this->table_name . " WHERE ativo =1  ";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    $num = $stmt->rowCount();

    return $num;
    }


    // used by select drop-down list
    function read($dono = null,$jogos = null, $incluirReais = null){

	if($incluirReais === false){
		$add_query = "WHERE dono != 0";
	} else if($incluirReais != null){
        $add_query = " OR dono = 0 ";
      } else {
		  $add_query = "";
	  }

        //ver se é por dono ou geral
        if($dono != null){
            $sub_query = "WHERE dono=? " . $add_query;
        } else if ($jogos != null){
            $sub_query = "WHERE ranqueavel=0";
        } else {
            $sub_query = $add_query;
        }
        //select all data
        $query = "SELECT
                    id, nome, sigla, bandeira, federacao
                FROM
                    " . $this->table_name . "
                ".$sub_query."
                ORDER BY
                    nome";

        $stmt = $this->conn->prepare( $query );
        if($dono === null){
        } else {
            $stmt->bindParam(1, $dono);
        }

        $stmt->execute();

        return $stmt;
    }

    // used to read category name by its ID
    public function readName(){

    $query = "SELECT nome FROM " . $this->table_name . " WHERE id = ? limit 0,1";

    $stmt = $this->conn->prepare( $query );
    $stmt->bindParam(1, $this->id);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $this->nome = $row['nome'];
    }
	
	public function setId($id){
		
		$id = htmlspecialchars(strip_tags($id));

    $this->id = $id;
    }
	
		public function getName(){
		
     return $this->nome;
    }

    //funcao para retornar id a partir da sigla
    function idPorSigla($siglaEnviada){

        $siglaEnviada = htmlspecialchars(strip_tags($siglaEnviada));

        $query = "SELECT id FROM ". $this->table_name ." WHERE sigla = ? limit 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $siglaEnviada);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row == false){
            $idObtida = "";
        } else {
            $idObtida = $row['id'];
        }


        return $idObtida;

    }

    function vincularUsuario($vincular, $novoIdUsuario){

        $vincular = htmlspecialchars(strip_tags($vincular));
        $novoIdUsuario = htmlspecialchars(strip_tags($novoIdUsuario));

        $query = "UPDATE " . $this->table_name . " SET dono=? WHERE id=?";
        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $novoIdUsuario);
        $stmt->bindParam(2, $vincular);
        $stmt->execute();

        return $stmt;
    }

    function bandeiraPorId($idEnviada){

        $idEnviada = htmlspecialchars(strip_tags($idEnviada));

        $query = "SELECT bandeira FROM ". $this->table_name ." WHERE id = ? limit 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $idEnviada);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row == false){
            $bandeira = "-";
        } else {
            $bandeira = $row['bandeira'];
        }


        return $bandeira;

    }

    function alterar($idPais,$nomePais,$siglaPais,$federacaoPais,$ranqueavel,$logo = null){

        $idPais = htmlspecialchars(strip_tags($idPais));
        $nomePais = htmlspecialchars(strip_tags($nomePais));
        $siglaPais = htmlspecialchars(strip_tags($siglaPais));
        $federacaoPais = htmlspecialchars(strip_tags($federacaoPais));
        $ranqueavel = htmlspecialchars(strip_tags($ranqueavel));
        $logo = htmlspecialchars(strip_tags($logo));

        if($logo != null && $logo != ''){
            $subquery = ", bandeira=:bandeira";
        } else {
            $subquery = "";
        }

        $query = "UPDATE " . $this->table_name . " SET nome=:nome, sigla=:sigla, federacao=:federacao, ranqueavel= (ranqueavel * :ranqueavel) ".$subquery." WHERE id=:id";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(":nome", $nomePais);
        $stmt->bindParam(":sigla", $siglaPais);
        $stmt->bindParam(":federacao", $federacaoPais);
        $stmt->bindParam(":ranqueavel", $ranqueavel);
        if($logo != null){
            $stmt->bindParam(":bandeira", $logo);
        }
        $stmt->bindParam(":id", $idPais);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }

    function janelasTransferencia($from_record_num, $records_per_page){
        $query = "SELECT p.id as idPais, p.bandeira, p.nome, p.dono as idDonoPais, s2.totalTransfer, k.padraoAbertura, CASE WHEN k.padraoAbertura is NULL THEN 1 ELSE SUBSTR(k.padraoAbertura, MONTH(NOW()), 1) END as statusAtual FROM paises p LEFT JOIN (SELECT s1.id as paisId, SUM(totalTransfer) as totalTransfer
        FROM
        (SELECT p.id, count(t.jogador) as totalTransfer FROM transferencias t LEFT JOIN clube c ON t.clubeOrigem = c.ID LEFT JOIN paises p ON c.Pais = p.id  LEFT JOIN clube d ON t.clubeDestino = d.ID LEFT JOIN paises q ON d.Pais = q.id LEFT JOIN janelas j ON j.pais = c.Pais WHERE d.Pais != c.Pais  AND t.status_execucao = 1 AND t.data >= DATE(NOW() - INTERVAL (CASE WHEN  j.padraoAbertura IS NOT NULL THEN (LOCATE (0,REVERSE(CONCAT(SUBSTR(j.padraoAbertura,MONTH(NOW())+1),SUBSTR(j.padraoAbertura,1,MONTH(NOW())))))-1) ELSE 12 END) MONTH)
        UNION ALL
        SELECT q.id, count(t.jogador) as totalTransfer FROM transferencias t  LEFT JOIN clube d ON t.clubeDestino = d.ID LEFT JOIN paises q ON d.Pais = q.id LEFT JOIN clube c ON t.clubeOrigem = c.ID LEFT JOIN paises p ON c.Pais = p.id LEFT JOIN janelas j ON j.pais = d.Pais WHERE d.Pais != c.Pais AND t.status_execucao = 1 AND t.data >= DATE(NOW() - INTERVAL  (CASE WHEN  j.padraoAbertura is NULL THEN 12 ELSE (LOCATE (0,REVERSE(CONCAT(SUBSTR(j.padraoAbertura,MONTH(NOW())+1),SUBSTR(j.padraoAbertura,1,MONTH(NOW())))))-1) END)  MONTH)) s1
        GROUP BY paisId HAVING paisId is not null) s2 ON s2.paisID = p.id LEFT JOIN janelas k ON p.id = k.pais WHERE p.ativo = 1
        ORDER BY statusAtual DESC, nome ASC
        LIMIT {$from_record_num},{$records_per_page}";
        $stmt = $this->conn->prepare( $query );
        $stmt->execute();
        return $stmt;
    }

    function alterarJanela($codeString){
        $codeString =htmlspecialchars(strip_tags($codeString));

        $query = "INSERT INTO janelas (pais,padraoAbertura) VALUES (?,?)
        ON DUPLICATE KEY UPDATE padraoAbertura = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$this->id);
        $stmt->bindParam(2,$codeString);
        $stmt->bindParam(3,$codeString);
        if($stmt->execute()){
            return true;
        } else {
            return false;
        }
    }


        //funcao para retornar id a partir da sigla
        function idPorBandeira($bandeiraEnviada){

            $bandeiraEnviada = htmlspecialchars(strip_tags($bandeiraEnviada));

            $bandeiraEnviada = $bandeiraEnviada . ".%";

            $query = "SELECT id FROM ". $this->table_name ." WHERE bandeira LIKE ? limit 0,1";

            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1, $bandeiraEnviada);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if($row == false){
                $idObtida = "";
            } else {
                $idObtida = $row['id'];
            }


            return $idObtida;

        }

        function atualizarBandeira($idPais, $bandeiraAtualizada){
            $idPais = htmlspecialchars(strip_tags($idPais));
            $bandeiraAtualizada = htmlspecialchars(strip_tags($bandeiraAtualizada));

            $query = "UPDATE paises SET bandeira = :bandeira WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":bandeira", $bandeiraAtualizada);
            $stmt->bindParam(":id", $idPais);
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
        }

        public function demografias(){
            $this->id = htmlspecialchars(strip_tags($this->id));

            $query = "SELECT nome*10 + sobrenome as nomeOuSobrenome, gen_origens.Origem as origem, fatorPercentual, gen_origens.ID as idOrigem, indiceMiscigenacao, ocorrenciaNomeDuplo FROM demografia LEFT JOIN gen_origens ON gen_origens.ID = demografia.origem WHERE pais = ? ORDER BY fatorPercentual DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$this->id);
            $stmt->execute();
            return $stmt;

        }

        public function novaDemografia($pais, $nome, $sobrenome, $origem, $fatorPercentual, $ocorrenciaNomeDuplo, $indiceMiscigenacao){

            $pais = htmlspecialchars(strip_tags($pais));
            $nome = htmlspecialchars(strip_tags($nome));
            $sobrenome = htmlspecialchars(strip_tags($sobrenome));
            $origem = htmlspecialchars(strip_tags($origem));
            $fatorPercentual = htmlspecialchars(strip_tags($fatorPercentual));
            $ocorrenciaNomeDuplo = htmlspecialchars(strip_tags($ocorrenciaNomeDuplo));
            $indiceMiscigenacao = htmlspecialchars(strip_tags($indiceMiscigenacao));

            $query = "INSERT INTO demografia (pais, origem, fatorPercentual, nome, sobrenome, ocorrenciaNomeDuplo, indiceMiscigenacao) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE fatorPercentual = VALUES(fatorPercentual), nome=VALUES(nome), sobrenome=VALUES(sobrenome), ocorrenciaNomeDuplo = VALUES(ocorrenciaNomeDuplo), indiceMiscigenacao = VALUES(indiceMiscigenacao)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$pais);
            $stmt->bindParam(2,$origem);
            $stmt->bindParam(3,$fatorPercentual);
            $stmt->bindParam(4,$nome);
            $stmt->bindParam(5,$sobrenome);
            $stmt->bindParam(6,$ocorrenciaNomeDuplo);
            $stmt->bindParam(7,$indiceMiscigenacao);
            $stmt->execute();
            return $stmt;

        }

        public function listaOrigens(){

            $query = "SELECT * FROM gen_origens";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;

        }

        function checarDono($idPais, $idDono){
            $idPais = htmlspecialchars(strip_tags($idPais));
            $idDono = htmlspecialchars(strip_tags($idDono));
            $query = "SELECT dono FROM paises WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idPais);
            $stmt->execute();
            $result = $stmt->fetchColumn();
            if($result == $idDono && $idDono != 0){
                return true;
            } else {
                return false;
            }
        }

        function sorteiaNacionalidade($idDono){
            $stmt = $this->read($idDono);
            $listaPaises = array();
            while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
                $listaPaises[] = $result['id'];
            }

            $sorteio = array_rand($listaPaises);

            return $listaPaises[$sorteio];
        }

        function verificarMiscigenacao($nacionalidade,$origemNomes){

            $query = "SELECT indiceMiscigenacao FROM demografia WHERE pais = ? AND origem = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$nacionalidade);
            $stmt->bindParam(2,$origemNomes);
            $stmt->execute();

            return $stmt->fetchColumn();
        }

        function verificarNomeDuplo($nacionalidade,$origemNomes){

            $query = "SELECT ocorrenciaNomeDuplo FROM demografia WHERE pais = ? AND origem = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$nacionalidade);
            $stmt->bindParam(2,$origemNomes);
            $stmt->execute();

            return $stmt->fetchColumn();
        }

        function sorteioDemografico($nacionalidade, $nomeSobrenome, $sexo){
            //verificar se tem demografia para nomes
            //0 para nome, 1 para sobrenome no segundo argumento
            if($sexo == 0){
              if($nomeSobrenome == 0){
                  $query = "SELECT demografia.origem, fatorPercentual, nomeM FROM demografia LEFT JOIN gen_origens ON demografia.origem = gen_origens.ID WHERE pais = ? AND nome = 1 AND nomeM > 0";
              } else {
                  $query = "SELECT demografia.origem, fatorPercentual, sobrenomeM FROM demografia LEFT JOIN gen_origens ON demografia.origem = gen_origens.ID WHERE pais = ? AND sobrenome = 1 AND sobrenomeM > 0";
              }
            } else {
              if($nomeSobrenome == 0){
                  $query = "SELECT demografia.origem, fatorPercentual, nomeF FROM demografia LEFT JOIN gen_origens ON demografia.origem = gen_origens.ID WHERE pais = ? AND nome = 1 AND nomeF > 0";
              } else {
                  $query = "SELECT demografia.origem, fatorPercentual, sobrenomeF FROM demografia LEFT JOIN gen_origens ON demografia.origem = gen_origens.ID WHERE pais = ? AND sobrenome = 1 AND sobrenomeF > 0";
              }
            }


            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$nacionalidade);
            $stmt->execute();

            $listaOrigens = array();

            while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
                $listaOrigens[] = [$result['origem'], $result['fatorPercentual']];
            }

            //se não, retornar 0
            $numeroOrigens = count($listaOrigens);
            if($numeroOrigens == 0){
                return 0;
            }

            //se sim, fazer sorteio probabilistico e retornar origem
            $random = mt_rand()/mt_getrandmax();

            $somaPercentual = 0;
            foreach($listaOrigens as $origem){
                $somaPercentual = $somaPercentual + $origem[1];
            }

            $limite = 0;

            foreach($listaOrigens as $origem){
                $origem[1] = $origem[1] / $somaPercentual;
                $limite = $limite + $origem[1];
                $origem[2] = $limite;
                if($random < $limite){
                    return $origem[0];
                }
            }

        }

        function apagarDemografia($idPais, $origem){
            $idPais = htmlspecialchars(strip_tags($idPais));
            $origem = htmlspecialchars(strip_tags($origem));
            $query = "DELETE FROM demografia WHERE pais = ? AND origem = ?";
            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1, $idPais);
            $stmt->bindParam(2, $origem);
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
        }

        //ignorar U21, U-21, S-21, S21, tudo entre parenteses, tudo entre colchetes, olimpico
        function removeModifiers($string) {
          return strtolower(trim(preg_replace('/\[(.*)\]|\((.*)\)|\S*\d+\S*|\W*((?i)sub(?-i))\W*|\W*((?i)olimpico(?-i))\W*|\W*((?i)olímpico(?-i))\W*/', '', $string)));
        }

        //ignorar acentos, case insensitive e trim
        function removeAccents($string) {
          return strtolower(trim(preg_replace('~[^0-9a-z]+~i', '-', preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'))), ' '));
        }


        function idPorNomeTratado($nomeTratado){
          $nomeTratado = $this->removeModifiers($nomeTratado);
          $nomeTratado = $this->removeAccents($nomeTratado);
          $nomeTratado = htmlspecialchars(strip_tags($nomeTratado));

          $query = "SELECT id, nome, bandeira FROM " . $this->table_name;
          $stmt = $this->conn->prepare( $query );
          $stmt->execute();
          while($results = $stmt->fetch(PDO::FETCH_ASSOC)){
            $treated_result = $this->removeAccents($results['nome']);

            if($treated_result == $nomeTratado){
              return $results['id'];
            }
          }
          return false;

        }
}
?>
