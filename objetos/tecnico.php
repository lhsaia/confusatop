<?php
class Tecnico{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "tecnico";

    // object properties
    public $id;
    public $nome;
    public $nascimento;
    public $nivel;
    public $mentalidade;
    public $estilo;
    public $pais;
    public $sexo;

    public function __construct($db){
        $this->conn = $db;
    }

    // criar time
    function create($fromScratch = null){

        //escrever query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    Nome=:nome, Nascimento=:nascimento, Nivel=:nivel, Mentalidade=:mentalidade, Estilo=:estilo, Pais=:pais, Sexo=:sexo ";

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->nome=htmlspecialchars(strip_tags($this->nome));
        $this->nascimento=htmlspecialchars(strip_tags($this->nascimento));
        $this->nivel=htmlspecialchars(strip_tags($this->nivel));
        $this->mentalidade=htmlspecialchars(strip_tags($this->mentalidade));
        $this->estilo=htmlspecialchars(strip_tags($this->estilo));
        $this->pais=htmlspecialchars(strip_tags($this->pais));
        $this->sexo=htmlspecialchars(strip_tags($this->sexo));

        // bind values
        $stmt->bindParam(":nome", $this->nome);
        if($fromScratch != null){
            $stmt->bindValue(":nascimento", $this->nascimento);
        } else {
            $stmt->bindValue(":nascimento", $this->aniversario_reverso($this->nascimento));
        }

        $stmt->bindParam(":nivel", $this->nivel);
        $stmt->bindParam(":mentalidade", $this->mentalidade);
        $stmt->bindParam(":estilo", $this->estilo);
        $stmt->bindParam(":pais", $this->pais);
        $stmt->bindParam(":sexo", $this->sexo);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }

    //ler todos os jogadores para o quadro
    function readAll($from_record_num, $records_per_page, $userID = null){

    if($userID != null){
        $subquery = " WHERE p.dono = {$userID} ";
    } else {
        $subquery = "";
    }

    $query = "SELECT
                a.ID, a.Nome, a.Nascimento,FLOOR((DATEDIFF(CURDATE(), a.Nascimento))/365) as idade, a.Mentalidade, a.Nivel, a.Estilo, p.sigla as siglaPais, p.bandeira as bandeiraPais, p.id as idPais, p.dono as idDonoPais, a.Sexo, q.dono as donoClubeVinculado, b.nome as clubeVinculado, b.escudo as escudoClubeVinculado, b.id as idClubeVinculado
            FROM
                " . $this->table_name . " a
            LEFT JOIN paises p ON a.pais = p.id
            LEFT JOIN contratos_tecnico c ON c.tecnico = a.id AND c.tipoContrato = 0
            LEFT JOIN clube b ON c.clube = b.id
            LEFT JOIN paises q ON b.pais = q.id
             " . $subquery . "
            ORDER BY
                a.Nome ASC
            LIMIT
                {$from_record_num}, {$records_per_page}";

    $stmt = $this->conn->prepare( $query );
    $stmt->execute();

    return $stmt;
    }

    // used for paging products
    public function countAll($dono){

    $dono = htmlspecialchars(strip_tags($dono));

    if($dono == null){

        $query = "SELECT id FROM " . $this->table_name . "";

    } else {

        $query =    "SELECT a.ID
                    FROM " . $this->table_name . " a
                     LEFT JOIN paises p ON a.pais = p.id
                      WHERE p.dono=".$dono;

    }

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
    function alterar($idRecebida,$nomeJogadorRec,$nomeAux1Rec,$nomeAux2Rec,$cobradorFaltaRec,$paisRec = 0){

        // $idRecebida = htmlspecialchars(strip_tags($idRecebida));
        // $nomeJogadorRec = htmlspecialchars(strip_tags($nomeJogadorRec));
        // $nomeAux1Rec = htmlspecialchars(strip_tags($nomeAux1Rec));
        // $nomeAux2Rec = htmlspecialchars(strip_tags($nomeAux2Rec));
        // $cobradorFaltaRec = htmlspecialchars(strip_tags($cobradorFaltaRec));
        // $paisRec = htmlspecialchars(strip_tags($paisRec));

        // $query = "UPDATE " . $this->table_name . " SET nomeJogador = ?, nascimento = ?, mentalidade = ?, cobradorFalta = ?, pais = ? WHERE id = ?";
        // $stmt = $this->conn->prepare( $query );

        // $stmt->bindParam(1, $nomeJogadorRec);
        // $stmt->bindParam(2, $nomeAux1Rec);
        // $stmt->bindParam(3, $nomeAux2Rec);
        // $stmt->bindParam(4, $cobradorFaltaRec);
        // $stmt->bindParam(5, $paisRec);
        // $stmt->bindParam(6, $idRecebida);

        // if($stmt->execute()){
        //     return true;
        // } else {
        //     return false;
        // }

    }

    public function aniversario_reverso( $idade ){
        $dias = mt_rand(1,350);
        return date('Y-m-d', strtotime($idade . ' years '.$dias.' days ago'));
    }

    //transferir tecnico
    function transferir($idTecnico,$idClubeDestino,$tipoTransferencia = 0,$prazo = 0){
        //escrever queries

        $error_count = 0;
        //verificar origem do jogador, se existir
        $query_origem = "SELECT clube FROM contratos_tecnico WHERE tecnico=:tecnico AND tipoContrato=:tipoContrato";

        $stmt = $this->conn->prepare( $query_origem );
        $stmt->bindParam(":tecnico", $idTecnico);
        $stmt->bindParam(":tipoContrato", $tipoTransferencia);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row == false){
            $origemJogador = 0;
        } else {
            $origemJogador = $row['clube'];
        }

        $query_contrato = "INSERT INTO
                    contratos_tecnico
                SET
                    tecnico=:tecnico, clube=:clube, prazo=:prazo, tipoContrato=:tipoContrato
                ON DUPLICATE KEY UPDATE
                    clube=:clubeNovo, prazo=:prazoNovo";
        $stmt = $this->conn->prepare( $query_contrato );
        $stmt->bindParam(":tecnico", $idTecnico);
        $stmt->bindParam(":tipoContrato", $tipoTransferencia);
        $stmt->bindParam(":clube", $idClubeDestino);
        $stmt->bindParam(":prazo", $prazo);
        $stmt->bindParam(":clubeNovo", $idClubeDestino);
        $stmt->bindParam(":prazoNovo", $prazo);
        if($stmt->execute()){

        } else {
            $error_count++;

        }

        $query_transferencia = "INSERT INTO transferencias_tecnico
        SET
            tecnico=:tecnico, clubeOrigem=:clubeOrigem, clubeDestino=:clube, tipoTransferencia=:tipoTransferencia, status_execucao=1";
        $stmt = $this->conn->prepare( $query_transferencia );
        $stmt->bindParam(":tecnico", $idTecnico);
        $stmt->bindParam(":tipoTransferencia", $tipoTransferencia);
        $stmt->bindParam(":clube", $idClubeDestino);
        $stmt->bindParam(":clubeOrigem", $origemJogador);
        if($stmt->execute()){

        } else {
            $error_count++;

        }

        if($error_count == 0){
            return true;
        } else {
            return false;
        }

    }


        //transpor para tabela de exportação
        function exportacao($idPais = null, $idTime = null, $idLiga = null){

            $idPais = htmlspecialchars(strip_tags($idPais));
            $idTime = htmlspecialchars(strip_tags($idTime));
			$idLiga = htmlspecialchars(strip_tags($idLiga));

            if($idPais != null){
              $subquery = " b.Pais=:pais ";
            } else if($idTime != null){
              $subquery = " b.ID=:clube ";
            } else if($idLiga != null) {
			  $subquery = " b.liga=:liga ";
			}

            $query = "SELECT DISTINCT a.ID, CONCAT(a.Nome,' [',p.sigla,']' ) as Nome, IFNULL(FLOOR((DATEDIFF(CURDATE(), a.Nascimento))/365),0) as Idade, a.Nivel, a.Mentalidade, a.Estilo FROM contratos_tecnico c LEFT JOIN tecnico a ON c.tecnico = a.ID LEFT JOIN paises p ON a.Pais = p.id LEFT JOIN clube b ON c.clube = b.ID WHERE " . $subquery;
            $stmt = $this->conn->prepare( $query );
            if($idPais != null){
              $stmt->bindParam(":pais", $idPais);
            } else if($idTime != null){
              $stmt->bindParam(":clube", $idTime);
            } else if($idLiga != null){
              $stmt->bindParam(":liga", $idLiga);
            }

            $stmt->execute();

            return $stmt;

        }

        function infoTecnico($idTime){

            $idTime = htmlspecialchars(strip_tags($idTime));

            $query = "SELECT t.Nome, t.ID, FLOOR((DATEDIFF(CURDATE(), t.Nascimento))/365) as idade, t.Nascimento, t.Nivel, t.Mentalidade, t.Estilo, p.id as idPais, p.bandeira as bandeiraPais, p.sigla as siglaPais, c.modificadorNivel, c.prazo as encerramento, p.dono as donoTecnico, t.Sexo
            FROM contratos_tecnico c
            LEFT JOIN tecnico t ON t.ID = c.tecnico
            LEFT JOIN paises p ON t.Pais = p.ID
            WHERE c.clube = :clube
            LIMIT 0,1";
            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(":clube", $idTime);
            $stmt->execute();

            return $stmt;
        }

        function ultimaTransferencia($id_jogador, $id_time){

            $id_jogador = htmlspecialchars(strip_tags($id_jogador));
            $id_time = htmlspecialchars(strip_tags($id_time));


        $query = "SELECT c.Nome, t.data FROM transferencias_tecnico t
        LEFT JOIN clube c ON t.clubeOrigem = c.ID
        WHERE t.tecnico=:tecnico AND t.clubeDestino=:clubeDestino ORDER BY t.data DESC LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(":clubeDestino",$id_time);
        $stmt->bindParam(":tecnico",$id_jogador);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $row['data'] = date("d-m-Y", strtotime($row['data']));
        if($row['Nome']==""){
            $row['Nome']= "Sem clube";
        }

        $results = array("Clube" => $row['Nome'], "Data" => $row['data']);

        return $results;
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

        function randomTecnico($nacionalidade, $origemNomes, $origemSobrenomes,$idadeMin,$idadeMax,$nivelMin,$nivelMax,$nivelMed,$idadeMed,$ocorrenciaNomeDuplo, $indiceMiscigenacao, $sexo){

            if($idadeMin == 0){
                $idadeMin = 18;
            }

            if($idadeMax == 0){
                $idadeMax = 80;
            }

            if($nivelMin == 0){
                $nivelMin = 1;
            }

            if($nivelMax == 0){
                $nivelMax = 10;
            }

            if($nivelMax > 99){
                $nivelMax = 99;
            }

            if($nivelMin < 1){
                $nivelMin = 1;
            }

            if($nivelMed == 0){
                $nivelMed = round((($nivelMin + $nivelMax)/2),0);
            }

            if($idadeMed == 0){
                $idadeMed = round((($idadeMin + $idadeMax)/2),0);
            }


            $this->nivel = $this->sorteia($nivelMin,$nivelMax,$nivelMed);
            $idade = $this->sorteia($idadeMin,$idadeMax,$idadeMed);
            $this->mentalidade = $this->sorteia(1,5);
            $this->estilo = $this->sorteia(1,5);
            $this->nascimento = $this->aniversario_reverso($idade);

            $this->pais = $nacionalidade;

            //nome e sobrenome
            //chance de nome duplo
            $chanceNomeDuplo = mt_rand(1,101);
            if($chanceNomeDuplo <= $ocorrenciaNomeDuplo){
                $nomeDuplo = true;
            } else {
                $nomeDuplo = false;
            }

            //chance de miscigenacao
            $chanceMiscigenacao = mt_rand(0,101);
            if($chanceMiscigenacao <= $indiceMiscigenacao){
                $this->nome = $this->geranomes($origemNomes, $origemSobrenomes, $nomeDuplo, $sexo);
            } else {
                $this->nome = $this->geranomes($origemNomes, $origemNomes, $nomeDuplo, $sexo);
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


        function proporTransferencia($idTecnico, $clubeOrigem, $clubeDestino, $tipoTransferencia = 0){

            $query_transferencia = "INSERT INTO transferencias_tecnico
            SET
                tecnico=:tecnico, clubeOrigem=:clubeOrigem, clubeDestino=:clube, tipoTransferencia=:tipoTransferencia, status_execucao=0";
            $stmt = $this->conn->prepare( $query_transferencia );
            $stmt->bindParam(":tecnico", $idTecnico);
            $stmt->bindParam(":tipoTransferencia", $tipoTransferencia);
            $stmt->bindParam(":clube", $clubeDestino);
            $stmt->bindParam(":clubeOrigem", $clubeOrigem);

            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
        }

        public function avaliarProposta($idTransferencia, $acao){

            $idTransferencia = htmlspecialchars(strip_tags($idTransferencia));
            $acao = htmlspecialchars(strip_tags($acao));

            if($acao == 'recusar'){
                $query = "UPDATE transferencias_tecnico SET status_execucao = 3 WHERE ID = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1,$idTransferencia);

            }

            if($acao == 'aceitar'){



                $infoquery = "SELECT tecnico, clubeOrigem, clubeDestino FROM transferencias_tecnico WHERE ID = ?";
                $stmt = $this->conn->prepare($infoquery);
                $stmt->bindParam(1,$idTransferencia);
                if($stmt->execute()){
                } else {
                    return false;
                }
                $row = $stmt->fetch(PDO::FETCH_ASSOC);


                //verificar se o time tem técnico e demitir
                $queryVerificacao = "SELECT tecnico FROM contratos_tecnico WHERE clube=:clube";
                $stmt = $this->conn->prepare($queryVerificacao);
                $stmt->bindParam(":clube", $row['clubeDestino']);
                $stmt->execute();
                $tecnicoAntigo = $stmt->fetchColumn();
                if($tecnicoAntigo != null && $tecnicoAntigo !== 0){
                    $this->demitir($tecnicoAntigo, $row['clubeDestino']);
                }

                if($row['clubeOrigem'] == 0){
                    $prequery = "INSERT INTO contratos_tecnico SET
                    clube=:clube, prazo=0, tipoContrato=0, tecnico=:tecnico";
                    $stmt = $this->conn->prepare($prequery);
                } else {
                    $prequery = "UPDATE contratos_tecnico
                    SET
                        clube=:clube, prazo=0, tipoContrato=0
                        WHERE tecnico=:tecnico AND clube=:clubeOrigem";
                    $stmt = $this->conn->prepare($prequery);
                    $stmt->bindParam(':clubeOrigem',$row['clubeOrigem']);
                }
                $stmt->bindParam(':clube',$row['clubeDestino']);
                $stmt->bindParam(':tecnico',$row['tecnico']);

                if($stmt->execute()){
                } else {
                    return false;
                }



                $newquery = "UPDATE transferencias_tecnico SET status_execucao = 3 WHERE ID <> ? AND tecnico = ? AND status_execucao <> 1";
                $stmt = $this->conn->prepare($newquery);
                $stmt->bindParam(1,$idTransferencia);
                $stmt->bindParam(2,$row['tecnico']);
                //$stmt->bindParam(3,$row['clubeDestino']);
                $stmt->execute();

                $query = "UPDATE transferencias_tecnico SET status_execucao = 1 WHERE ID = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1,$idTransferencia);


            }

            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
        }

        function demitir($idTecnico,$idClube){

            //verificar tipo transferencia
                    $query_origem = "SELECT tipoContrato FROM contratos_tecnico WHERE tecnico=:tecnico AND clube=:clube";

                    $stmt = $this->conn->prepare( $query_origem );
                    $stmt->bindParam(":tecnico", $idTecnico);
                    $stmt->bindParam(":clube", $idClube);
                    $stmt->execute();
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if($row == false){
                        $tipoContrato = 0;
                    } else {
                        $tipoContrato = $row['tipoContrato'];
                    }

                    $error_count = 0;

              $query_contrato = "DELETE FROM
                            contratos_tecnico
                        WHERE
                            tecnico=:tecnico AND clube=:clube";
                $stmt = $this->conn->prepare( $query_contrato );
                $stmt->bindParam(":tecnico", $idTecnico);
                $stmt->bindParam(":clube", $idClube);
                if($stmt->execute()){
                } else {
                    $error_count++;
                }

                if($tipoContrato == 0){
                            $query_transferencia = "INSERT INTO transferencias_tecnico
                            SET
                                tecnico=:tecnico, clubeOrigem=:clubeOrigem, clubeDestino=0, tipoTransferencia=:tipoTransferencia, status_execucao=1";
            $stmt = $this->conn->prepare( $query_transferencia );
            $stmt->bindParam(":tecnico", $idTecnico);
            $stmt->bindParam(":tipoTransferencia", $tipoContrato);
            $stmt->bindParam(":clubeOrigem", $idClube);
            if($stmt->execute()){
            } else {
            $error_count++;
            }

                }


                if($error_count == 0){
                    return true;
                } else {
                    return false;
                }

            }

            function pesquisaAvancada($nivelMin, $nivelMax, $nome, $nacionalidade, $mentalidade, $estilo, $semclube, $sexo, $apenasConfusa, $usuarioLogado){

            $nivelMin = htmlspecialchars(strip_tags($nivelMin));
            $nivelMax = htmlspecialchars(strip_tags($nivelMax));
            $nome = htmlspecialchars(strip_tags($nome));
            $nacionalidade = htmlspecialchars(strip_tags($nacionalidade));
            $mentalidade = htmlspecialchars(strip_tags($mentalidade));
            $estilo = htmlspecialchars(strip_tags($estilo));
            $semclube = htmlspecialchars(strip_tags($semclube));
            $sexo = htmlspecialchars(strip_tags($sexo));
            $apenasConfusa = htmlspecialchars(strip_tags($apenasConfusa));

            $subquery = '';
            if($estilo != null){
                $subquery .= ' AND estiloIndex = :estilo ';
            }

            if($nome != null){
                $subquery .= ' AND nomeJogador LIKE :nome ';
            }

            if($nacionalidade != null){
                $subquery .= ' AND nacionalidade = :nacionalidade ';
            }

            if($mentalidade != null){
                $subquery .= ' AND mentalidadeIndex = :mentalidade ';
            }

            if($semclube != null){
                $subquery .= ' AND idClube = 0  ';
            }

            if($apenasConfusa == null){
                $subquery .= ' AND ranqueavel = 0 ';
            }


            $query = "SELECT t1.*, d.Nome as nomeClube, d.Escudo as escudoClube  FROM (SELECT t.ID as idJogador, t.Nome as nomeJogador, FLOOR(DATEDIFF(NOW(),t.Nascimento)/365) as idadeJogador, m.Nome as mentalidade, e.Nome as estilo, '-' as cobrancaFalta, t.Sexo as sexoJogador, t.Pais as nacionalidade, 'T' as stringPosicoes,
            '-' as valor, t.Nivel as nivel, '-' as disponibilidade, p.bandeira, q.bandeira as bandeiraClube, q.ID as paisClube, CASE WHEN b.ID is not NULL THEN b.ID ELSE 0 END as idClube, b.liga as idLiga, l.Nome as ligaClube, '-' as posicaoBaseJogador, t.Mentalidade as mentalidadeIndex, t.Estilo as estiloIndex, p.ranqueavel, CASE WHEN p.dono <> :usuarioLogado THEN 0 ELSE 1 END as donoJogador, c.tipoContrato
            FROM tecnico t
            LEFT JOIN paises p ON t.Pais = p.id
            LEFT JOIN contratos_tecnico c ON t.ID = c.tecnico
            LEFT JOIN clube b ON b.ID = c.clube
            LEFT JOIN paises q ON b.Pais = q.ID
            LEFT JOIN mentalidade_tecnico m ON t.Mentalidade = m.ID
            LEFT JOIN estilo_tecnico e ON t.Estilo = e.ID
            LEFT JOIN liga l ON l.ID = b.liga) t1
            LEFT JOIN clube d ON d.ID = t1.idClube
            WHERE (tipoContrato = 0 OR tipoContrato is NULL) AND nivel >= :nivelMin AND nivel <= :nivelMax AND
                 sexoJogador = :sexo " . $subquery;


            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nivelMin',$nivelMin);
            $stmt->bindParam(':nivelMax',$nivelMax);
            $stmt->bindParam(':usuarioLogado',$usuarioLogado);
            $stmt->bindParam(':sexo',$sexo);
            if($mentalidade != null){
                $stmt->bindParam(':mentalidade',$mentalidade);
            }
            if($estilo != null){
                $stmt->bindParam(':estilo',$estilo);
            }
            if($nome != null){
                $nome = "%".$nome."%";
                $stmt->bindParam(':nome',$nome);
            }
            if($nacionalidade != null){
                $stmt->bindParam(':nacionalidade',$nacionalidade);
            }
            $stmt->execute();
            return $stmt;
        }

        function convocar($idTecnico, $selecaoDestino,$tipoContrato){
            $idTecnico = htmlspecialchars(strip_tags($idTecnico));
            $selecaoDestino = htmlspecialchars(strip_tags($selecaoDestino));
            $tipoContrato = htmlspecialchars(strip_tags($tipoContrato));

            if($tipoContrato == 0){
                $tipoContrato = 1;
            }

            $query = "INSERT INTO contratos_tecnico (tecnico, clube, modificadorNivel, prazo, tipoContrato) VALUES (?, ?, 0, 0, ?) ON DUPLICATE KEY UPDATE tecnico = tecnico";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $idTecnico);
            $stmt->bindParam(2, $selecaoDestino);
            $stmt->bindParam(3, $tipoContrato);
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }

        }


        public function contarPropostas($idUsuario, $somenteRecebidas = null){

            $idUsuario = htmlspecialchars(strip_tags($idUsuario));

            if($somenteRecebidas == null){
            $subQuery = "UNION
            SELECT t.id FROM transferencias_tecnico t
            LEFT JOIN clube d ON t.clubeDestino = d.id
            LEFT JOIN paises p ON d.Pais = p.id
            WHERE p.dono = ? ";
            } else {
                $subQuery = "";
            }

        $query = "SELECT t.id FROM transferencias_tecnico t
        LEFT JOIN tecnico j ON t.tecnico = j.id
        LEFT JOIN paises p ON j.Pais = p.id
        WHERE p.dono = ? AND t.status_execucao = 0 ".$subQuery;

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1,$idUsuario);
        if($somenteRecebidas == null){
            $stmt->bindParam(2,$idUsuario);
        }
        $stmt->execute();

        $num = $stmt->rowCount();

        return $num;
        }

        function lerPropostasPendentes($idUsuario,$from_record_num,$records_per_page){
            $idUsuario = htmlspecialchars(strip_tags($idUsuario));

            $query = "SELECT j.Nome as nomeJogador, c.Nome as clubeOrigem, d.Nome as clubeDestino, c.Escudo as escudoOrigem, d.Escudo as escudoDestino, j.id as idJogador, 'inbox' as direcao, t.data as data, j.Nivel as nivelJogador, t.status_execucao as status_execucao, t.id as idTransferencia, (case when t.status_execucao = 0 then 1 when t.status_execucao = 3 then 2 end) as precedencia
            FROM transferencias_tecnico t
            LEFT JOIN clube c ON t.clubeOrigem = c.id
            LEFT JOIN tecnico j ON t.tecnico = j.id
            LEFT JOIN paises p ON c.Pais = p.id
            LEFT JOIN clube d ON t.clubeDestino = d.id
            LEFT JOIN paises q ON j.Pais = q.id
            WHERE ((p.dono = ? AND (t.status_execucao = 0 OR t.status_execucao = 3)) OR (c.id = 0 AND q.dono = ? AND (t.status_execucao = 0 OR t.status_execucao = 3)))
            UNION
            SELECT j.Nome as nomeJogador, c.Nome as clubeOrigem, d.Nome as clubeDestino, c.Escudo as escudoOrigem, d.Escudo as escudoDestino, j.id as idJogador, 'outbox' as direcao, t.data as data, j.Nivel as nivelJogador, t.status_execucao, t.id as idTransferencia, (case when t.status_execucao = 2 then 1 else 2 end) as precedencia
            FROM transferencias_tecnico t
            LEFT JOIN clube d ON t.clubeDestino = d.id
            LEFT JOIN tecnico j ON t.tecnico = j.id
            LEFT JOIN paises p ON d.Pais = p.id
            LEFT JOIN clube c ON t.clubeOrigem = c.id
            WHERE p.dono = ?
            ORDER BY precedencia ASC, data DESC
                    LIMIT
                        {$from_record_num}, {$records_per_page}";

            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1,$idUsuario);
            $stmt->bindParam(2,$idUsuario);
            $stmt->bindParam(3,$idUsuario);
            $stmt->execute();

            return $stmt;

        }


        function verificarDonoTimeVinculado($idJogador){
            $idJogador = htmlspecialchars(strip_tags($idJogador));

            $query = "SELECT p.dono FROM tecnico t LEFT JOIN contratos_tecnico c ON c.tecnico = t.ID AND c.tipoContrato = 0 LEFT JOIN clube b ON b.ID = c.clube LEFT JOIN paises p ON b.Pais = p.id WHERE t.ID = ? LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idJogador);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $idDono = $row['dono'];

            return $idDono;

        }

        function verificarDono($idJogador){
            $idJogador = htmlspecialchars(strip_tags($idJogador));

            $query = "SELECT p.dono FROM tecnico t LEFT JOIN paises p ON t.Pais = p.id WHERE t.ID = ? LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idJogador);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $idDono = $row['dono'];
            return $idDono;

        }


        function editar($idTecnico,$idTime = null,$nomeTecnico,$nacionalidadeTecnico,$nascimentoTecnico,$nivelTecnico,$isDono,$mentalidadeTecnico = null, $estiloTecnico = null){

            $idTecnico = htmlspecialchars(strip_tags($idTecnico));
            $idTime = htmlspecialchars(strip_tags($idTime));
            $nomeTecnico = htmlspecialchars(strip_tags($nomeTecnico));
            $nacionalidadeTecnico = htmlspecialchars(strip_tags($nacionalidadeTecnico));
            $nascimentoTecnico = htmlspecialchars(strip_tags($nascimentoTecnico));
            $nivelTecnico = htmlspecialchars(strip_tags($nivelTecnico));
            $mentalidadeTecnico = htmlspecialchars(strip_tags($mentalidadeTecnico));
            $estiloTecnico = htmlspecialchars(strip_tags($estiloTecnico));

            if($nivelTecnico > 10){
              $nivelTecnico = 10;
            }

            if($nivelTecnico < 1){
              $nivelTecnico = 1;
            }

            if($isDono){
              $nome = $nomeTecnico;
              $nacionalidade = $nacionalidadeTecnico;
              $nascimento = $nascimentoTecnico;

              if($mentalidadeTecnico != null){
                $mentalidade = $mentalidadeTecnico;
                $queryMentalidade = " Mentalidade=:mentalidade, ";
              } else {
                $queryMentalidade = "";
              }

              if($estiloTecnico != null){
                $estilo = $estiloTecnico;
                $queryEstilo = " Estilo=:estilo, ";
              } else {
                $queryEstilo = "";
              }

              $query = "UPDATE tecnico SET Nome=:nome, Nascimento=:nascimento, Pais=:nacionalidade, ".$queryMentalidade ." " .$queryEstilo .  " Nivel=:nivel WHERE ID = :id";
            } else {
              $query = "UPDATE tecnico SET Nivel=:nivel WHERE ID = :id";
            }

            $nivel = $nivelTecnico;

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":id", $idTecnico);
            $stmt->bindParam(":nivel",$nivel);

            if($isDono){
              $stmt->bindParam(":nome", $nome);
              $stmt->bindParam(":nascimento", $nascimento);
              $stmt->bindParam(":nacionalidade",$nacionalidade);

              if(isset($mentalidade)){
                $stmt->bindParam(":mentalidade", $mentalidade);
              }

              if(isset($estilo)){
                  $stmt->bindParam(":estilo", $estilo);
              }
            }

            if($stmt->execute()){
                return true;
            } else {
                return false;
            }

        }

        public function coletarTecnicoTime($idTime){
          $idTime = htmlspecialchars(strip_tags($idTime));

          $query = "SELECT a.ID as id, CONCAT(a.Nome,' [',p.sigla,']' ) as Nome, FLOOR((DATEDIFF(CURDATE(), a.Nascimento))/365) as Idade, a.Nivel, a.Mentalidade, a.Estilo FROM tecnico a LEFT JOIN contratos_tecnico c ON c.tecnico = a.ID LEFT JOIN paises p ON a.Pais = p.ID WHERE c.clube = ?";
          $stmt = $this->conn->prepare( $query );
          $stmt->bindParam(1, $idTime);
          $stmt->execute();

          return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }


        public function enviarEmailProposta($idTecnico, $clubeOrigem){
           $idTecnico = htmlspecialchars(strip_tags($idTecnico));
           $clubeOrigem = htmlspecialchars(strip_tags($clubeOrigem));

           if($clubeOrigem){
             $query = "SELECT usuarios.email FROM clube LEFT JOIN paises ON paises.id = clube.pais LEFT JOIN usuarios ON usuarios.id = paises.dono WHERE clube.id = ?";
           } else{
             $query = "SELECT usuarios.email FROM tecnico LEFT JOIN paises ON paises.id = tecnico.Pais LEFT JOIN usuarios ON usuarios.id = paises.dono WHERE tecnico.id = ?";
           }
           $stmt = $this->conn->prepare($query);
           if($clubeOrigem){
             $stmt->bindParam(1,$clubeOrigem);
           } else{
             $stmt->bindParam(1,$idTecnico);
           }
           $stmt->execute();
           $result = $stmt->fetch(PDO::FETCH_ASSOC);

           //return $result;

           $to = $result['email'] . ", lhsaia@gmail.com";
           $from = "no-reply@confusa.top";

           $headers = "From: " . $from . "\r\n";

           $subject = "Você recebeu uma proposta de transferência no CONFUSA.TOP ";
           $body = "Foi feita uma nova proposta de transferência para um técnico sob seu controle, acesse o portal para negociar.";

           if(mail($to, $subject, $body, $headers, "-f " . $from)){
             return true;
           } else {
             return false;
           }

        }

}
?>
