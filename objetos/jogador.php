<?php
class Jogador{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "jogador";

    // object properties
    public $id;
    public $nomeJogador;
    public $nascimento;
    public $mentalidade;
    public $cobradorFalta;
    public $pais;
    public $condicao;
    public $stringPosicoes;
    public $valor;
    public $nivel;
    public $marcacao;
    public $desarme;
    public $visaoJogo;
    public $movimentacao;
    public $cruzamentos;
    public $cabeceamento;
    public $tecnica;
    public $controleBola;
    public $finalizacao;
    public $faroGol;
    public $velocidade;
    public $forca;
    public $reflexos;
    public $seguranca;
    public $saidas;
    public $jogoAereo;
    public $lancamentos;
    public $defesaPenaltis;
    public $determinacao;
    public $determinacaoOriginal;
    public $sexo;
    public $progressao;

    public function __construct($db){
        $this->conn = $db;
    }

    // criar trio de arbitragem
    function create($fromScratch = null){


        $subquery = ", Valor=:valor";
        $this->valor=htmlspecialchars(strip_tags($this->valor));

        $this->progressao = $this->randomProgressao();

        $this->condicao = "true";

        //escrever query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    Nome=:nomeJogador, Nascimento=:nascimento, Mentalidade=:mentalidade, CobradorFalta=:cobradorFalta, Pais=:pais, Condicao=:condicao, StringPosicoes=:stringPosicoes, Nivel=:nivel, Marcacao=:marcacao, Desarme=:desarme, VisaoJogo=:visaoJogo, Movimentacao=:movimentacao, Cruzamentos=:cruzamentos, Cabeceamento=:cabeceamento, Tecnica=:tecnica, ControleBola=:controleBola, Finalizacao=:finalizacao, FaroGol=:faroGol, Velocidade=:velocidade, Forca=:forca, Reflexos=:reflexos, Seguranca=:seguranca, Saidas=:saidas, JogoAereo=:jogoAereo, Lancamentos=:lancamentos, DefesaPenaltis=:defesaPenaltis, Determinacao=:determinacao, DeterminacaoOriginal=:determinacaoOriginal, Sexo=:sexo, Progressao=:progressao " . $subquery;

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->nomeJogador=htmlspecialchars(strip_tags($this->nomeJogador));
        $this->nascimento=htmlspecialchars(strip_tags($this->nascimento));
        $this->mentalidade=htmlspecialchars(strip_tags($this->mentalidade));
        $this->cobradorFalta=htmlspecialchars(strip_tags($this->cobradorFalta));
        $this->pais=htmlspecialchars(strip_tags($this->pais));
        $this->condicao=htmlspecialchars(strip_tags($this->condicao));
        $this->stringPosicoes=htmlspecialchars(strip_tags($this->stringPosicoes));
        $this->nivel=htmlspecialchars(strip_tags($this->nivel));
        $this->sexo=htmlspecialchars(strip_tags($this->sexo));

        //atributos de jogador de linha
        $this->marcacao=htmlspecialchars(strip_tags($this->marcacao));
        $this->desarme=htmlspecialchars(strip_tags($this->desarme));
        $this->visaoJogo=htmlspecialchars(strip_tags($this->visaoJogo));
        $this->movimentacao=htmlspecialchars(strip_tags($this->movimentacao));
        $this->cruzamentos=htmlspecialchars(strip_tags($this->cruzamentos));
        $this->cabeceamento=htmlspecialchars(strip_tags($this->cabeceamento));
        $this->tecnica=htmlspecialchars(strip_tags($this->tecnica));
        $this->controleBola=htmlspecialchars(strip_tags($this->controleBola));
        $this->finalizacao=htmlspecialchars(strip_tags($this->finalizacao));
        $this->faroGol=htmlspecialchars(strip_tags($this->faroGol));
        $this->velocidade=htmlspecialchars(strip_tags($this->velocidade));
        $this->forca=htmlspecialchars(strip_tags($this->forca));

        //atributos de goleiro
        $this->reflexos=htmlspecialchars(strip_tags($this->reflexos));
        $this->seguranca=htmlspecialchars(strip_tags($this->seguranca));
        $this->saidas=htmlspecialchars(strip_tags($this->saidas));
        $this->jogoAereo=htmlspecialchars(strip_tags($this->jogoAereo));
        $this->lancamentos=htmlspecialchars(strip_tags($this->lancamentos));
        $this->defesaPenaltis=htmlspecialchars(strip_tags($this->defesaPenaltis));

        //atributos mistos
        $this->determinacao=htmlspecialchars(strip_tags($this->determinacao));
        $this->determinacaoOriginal=htmlspecialchars(strip_tags($this->determinacaoOriginal));

        if($this->valor == 0){
            $stmt->bindValue(":valor", $this->calcularPasse());
        } else {
            $stmt->bindValue(":valor", $this->valor);
        }

        // bind values
        $stmt->bindParam(":nomeJogador", $this->nomeJogador);
        if($fromScratch != null){
            $stmt->bindValue(":nascimento", $this->nascimento);
        } else {
            $stmt->bindValue(":nascimento", $this->aniversario_reverso($this->nascimento));
        }

        $stmt->bindParam(":mentalidade", $this->mentalidade);
        $stmt->bindParam(":cobradorFalta", $this->cobradorFalta);
        $stmt->bindParam(":pais", $this->pais);
        $stmt->bindParam(":condicao", $this->condicao);
        $stmt->bindParam(":stringPosicoes", $this->stringPosicoes);
        $stmt->bindParam(":nivel", $this->nivel);
        $stmt->bindParam(":marcacao", $this->marcacao);
        $stmt->bindParam(":desarme", $this->desarme);
        $stmt->bindParam(":visaoJogo", $this->visaoJogo);
        $stmt->bindParam(":movimentacao", $this->movimentacao);
        $stmt->bindParam(":cruzamentos", $this->cruzamentos);
        $stmt->bindParam(":cabeceamento", $this->cabeceamento);
        $stmt->bindParam(":tecnica", $this->tecnica);
        $stmt->bindParam(":controleBola", $this->controleBola);
        $stmt->bindParam(":finalizacao", $this->finalizacao);
        $stmt->bindParam(":faroGol", $this->faroGol);
        $stmt->bindParam(":velocidade", $this->velocidade);
        $stmt->bindParam(":forca", $this->forca);
        $stmt->bindParam(":reflexos", $this->reflexos);
        $stmt->bindParam(":seguranca", $this->seguranca);
        $stmt->bindParam(":saidas", $this->saidas);
        $stmt->bindParam(":jogoAereo", $this->jogoAereo);
        $stmt->bindParam(":lancamentos", $this->lancamentos);
        $stmt->bindParam(":defesaPenaltis", $this->defesaPenaltis);
        $stmt->bindParam(":determinacao", $this->determinacao);
        $stmt->bindParam(":determinacaoOriginal", $this->determinacaoOriginal);
        $stmt->bindParam(":sexo", $this->sexo);
        $stmt->bindParam(":progressao", $this->progressao);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }

    //ler todos os jogadores para o quadro
function readAll($from_record_num, $records_per_page, $dono = null){

    //ver se é por dono ou geral
    if($dono === null){
        $sub_query_inicio = "";
        $sub_query_fim = "";
    } else {
        $sub_query_inicio = "SELECT * FROM (";
        $sub_query_fim = ") t1 WHERE idDonoPais = ? ORDER BY Nome ASC";

    }

$query = $sub_query_inicio. "SELECT tf.ID, tf.Nome, tf.Nascimento, tf.Mentalidade, tf.CobradorFalta, tf.StringPosicoes, tf.valor, tf.Nivel, tf.disponibilidade, tf.idPais, tf.idDonoPais, tf.siglaPais, tf.bandeiraPais, tf.posicaoBase as posicaoBase, tf.titularidade, b.Nome as clubeVinculado, d.Nome as clubeEmprestimo, f.Nome as clubeSelecao, tf.determinacaoOriginal, tf.sexo, b.Escudo as escudoClubeVinculado, b.ID as idClubeVinculado, tf.Idade, q.dono as donoClubeVinculado FROM ( SELECT
            a.ID, a.Nome, a.Nascimento, m.Nome as Mentalidade, r.Nome as CobradorFalta, a.StringPosicoes, a.valor, a.Nivel, a.disponibilidade, p.id as idPais, p.dono as idDonoPais, p.sigla as siglaPais, p.bandeira as bandeiraPais, c.clube as clubeVinculado, e.clube as clubeEmprestimo, s.clube as clubeSelecao, c.posicaoBase as posicaoBase, c.titularidade, a.Sexo as sexo, a.determinacaoOriginal, FLOOR((DATEDIFF(CURDATE(), a.Nascimento))/365) as Idade
        FROM
            " . $this->table_name . " a
        LEFT JOIN paises p ON a.Pais = p.id
        LEFT JOIN contratos_jogador c ON a.ID = c.jogador AND c.tipoContrato = 0
        LEFT JOIN contratos_jogador e ON a.ID = e.jogador AND e.tipoContrato = 1
        LEFT JOIN contratos_jogador s ON a.ID = s.jogador AND s.tipoContrato = 2
        LEFT JOIN mentalidade m ON a.Mentalidade = m.ID
        LEFT JOIN cobrador r ON a.CobradorFalta = r.ID
          ) tf
        LEFT JOIN clube b ON tf.clubeVinculado = b.id
        LEFT JOIN clube d ON tf.clubeEmprestimo = d.id
        LEFT JOIN clube f ON tf.clubeSelecao = f.id
        LEFT JOIN paises q ON b.Pais = q.id
        ORDER BY
            tf.Nome ASC ".$sub_query_fim."
        LIMIT
            {$from_record_num}, {$records_per_page}";



$stmt = $this->conn->prepare( $query );
if($dono === null){
} else {
    $stmt->bindParam(1, $dono);
}
$stmt->execute();

return $stmt;
}

       // used for paging products
       public function countAll($dono = null){

        $dono = htmlspecialchars(strip_tags($dono));

        if($dono == null){

           $query = "SELECT id FROM " . $this->table_name . "";

        } else {

            $query =    "SELECT a.id
                        FROM " . $this->table_name . " a
                         LEFT JOIN paises p ON a.pais = p.id
                          WHERE p.dono = ".$dono;

        }

        $stmt = $this->conn->prepare( $query );
        $stmt->execute();

        $num = $stmt->rowCount();

        return $num;
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

    //transferir jogador
    function transferir($idJogador,$idClubeDestino,$isCapitao = 0,$isPenalti = 0,$titularidade = 0, $posicaoBase = 0, $tipoTransferencia = 0,$prazo = 0,$valor = null){

        //sistema F+
        if($valor === null){
            $preco = 0;
            $passeJogador = $this->calcularPasse();
            $salario = $this->calcularSalario($passeJogador);
            $alterarPasse = true;
            $querySalario = "salario=:salarioNovo,";
            $querySalario2 = "salario=:salario,";
        } else if($valor === 0){
            $preco = 0;
            $alterarPasse = false;
            $querySalario = "";
            $querySalario2 = "";
        } else {
            $preco = $valor;
            $passeJogador = $valor;
            $salario = $this->calcularSalario($passeJogador);
            $alterarPasse = true;
            $querySalario = "salario=:salarioNovo,";
            $querySalario2 = "salario=:salario,";
        }

        $error_count = 0;



        //verificar origem do jogador, se existir
        $query_origem = "SELECT clube FROM contratos_jogador WHERE jogador=:jogador AND tipoContrato=:tipoContrato";

        $stmt = $this->conn->prepare( $query_origem );
        $stmt->bindParam(":jogador", $idJogador);
        $stmt->bindParam(":tipoContrato", $tipoTransferencia);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row == false){
            $origemJogador = 0;
        } else {
            $origemJogador = $row['clube'];
        }



        $query_contrato = "INSERT INTO
                    contratos_jogador
                SET
                    jogador=:jogador, clube=:clube, encerramento=:encerramento, tipoContrato=:tipoContrato, ".$querySalario2." capitao=:capitao, cobrancaPenalti=:cobrancaPenalti, titularidade=:titularidade, posicaoBase=:posicaoBase
                ON DUPLICATE KEY UPDATE
                    ".$querySalario." clube=:clubeNovo, encerramento=:encerramentoNovo, capitao=:capitaoNovo, cobrancaPenalti=:cobrancaPenaltiNovo, titularidade=:titularidadeNovo, posicaoBase=:posicaoBaseNovo";
        $stmt = $this->conn->prepare( $query_contrato );
        $stmt->bindParam(":jogador", $idJogador);
        $stmt->bindParam(":tipoContrato", $tipoTransferencia);
        $stmt->bindParam(":clube", $idClubeDestino);
        $stmt->bindParam(":encerramento", $prazo);
        $stmt->bindParam(":clubeNovo", $idClubeDestino);
        $stmt->bindParam(":encerramentoNovo", $prazo);
        if($valor !== 0){
            $stmt->bindParam(":salario",$salario);
            $stmt->bindParam(":salarioNovo",$salario);
        }
        $stmt->bindParam(":capitao", $isCapitao);
        $stmt->bindParam(":cobrancaPenalti", $isPenalti);
        $stmt->bindParam(":titularidade", $titularidade);
        $stmt->bindParam(":titularidadeNovo", $titularidade);
        $stmt->bindParam(":posicaoBase", $posicaoBase);
        $stmt->bindParam(":posicaoBaseNovo", $posicaoBase);
        $stmt->bindParam(":capitaoNovo", $isCapitao);
        $stmt->bindParam(":cobrancaPenaltiNovo", $isPenalti);
        if($stmt->execute()){

        } else {
            $error_count++;
        }

        $query_transferencia = "INSERT INTO transferencias
                            SET
                                jogador=:jogador, clubeOrigem=:clubeOrigem, clubeDestino=:clube, valor=:valor, tipoTransferencia=:tipoTransferencia, status_execucao=1";
        $stmt = $this->conn->prepare( $query_transferencia );
        $stmt->bindParam(":jogador", $idJogador);
        $stmt->bindParam(":tipoTransferencia", $tipoTransferencia);
        $stmt->bindParam(":clube", $idClubeDestino);
        $stmt->bindParam(":valor", $preco);
        $stmt->bindParam(":clubeOrigem", $origemJogador);
        if($stmt->execute()){

        } else {
            $error_count++;
        }

        if($alterarPasse){
            $query_valor = "UPDATE jogador SET valor = ?, disponibilidade = 0 WHERE id = ?";
            $stmt = $this->conn->prepare($query_valor);
            $stmt->bindParam(1,$passeJogador);
            $stmt->bindParam(2,$idJogador);
            if($stmt->execute()){

            } else {
                $error_count++;
            }
        } else {
			$query_valor = "UPDATE jogador SET disponibilidade = 0 WHERE id = ?";
            $stmt = $this->conn->prepare($query_valor);
            $stmt->bindParam(1,$idJogador);
            if($stmt->execute()){

            } else {
                $error_count++;
            }
		}

        if($error_count > 0){
            return false;
        } else {
            return true;
        }

    }

    //propor transferencia
    function proporTransferencia($idJogador, $clubeOrigem, $clubeDestino, $valor, $tipoTransferencia = 0, $tipoTransacao = 0, $fimContrato = 0){
        
        //verificar se jogador é emprestado
        $query_check_vinculo = "SELECT (tipoContrato + clubeVinculado) as aptoProposta FROM contratos_jogador WHERE jogador = :jogador AND clube = :clube";
        $stmt_check_vinculo = $this->conn->prepare($query_check_vinculo);
        $stmt_check_vinculo->bindParam(":jogador", $idJogador);
        $stmt_check_vinculo->bindParam(":clube", $clubeOrigem);
        $stmt_check_vinculo->execute();
        $result_check_vinculo = $stmt_check_vinculo->fetch(PDO::FETCH_ASSOC);
        if($result_check_vinculo["aptoProposta"] != 0){
            return false;
        }
        
        if($tipoTransacao == 2){
            $emprestimo = 1;
        } else {
            $emprestimo = 0;
        }

        $query_transferencia = "INSERT INTO transferencias
        SET
            jogador=:jogador, clubeOrigem=:clubeOrigem, clubeDestino=:clube, valor=:valor, tipoTransferencia=:tipoTransferencia, status_execucao=0, encerramento=:encerramento, emprestimo=:emprestimo";
        $stmt = $this->conn->prepare( $query_transferencia );
        $stmt->bindParam(":jogador", $idJogador);
        $stmt->bindParam(":tipoTransferencia", $tipoTransferencia);
        $stmt->bindParam(":clube", $clubeDestino);
        $stmt->bindParam(":valor", $valor);
        $stmt->bindParam(":clubeOrigem", $clubeOrigem);
        $stmt->bindParam(":encerramento", $fimContrato);
        $stmt->bindParam(":emprestimo", $emprestimo);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }
    }

    //encerrar contrato
    function encerrarContrato($idJogador, $idClube){

    }

    //alterar titularidade
    function alterarTitularidade($idJogador, $idClube){

    }

    public function aniversario_reverso( $idade ){
        $dias = mt_rand(1,350);
        return date('Y-m-d', strtotime($idade . ' years '.$dias.' days ago'));
    }

    function calcularSalario($passe){
        $multiplicador = 0.005;
        $salario = $passe * $multiplicador;
        return $salario;
    }

    function readOne($idJogador){
        $idJogador = htmlspecialchars(strip_tags($idJogador));

        $query = "SELECT Nivel as nivel, FLOOR((DATEDIFF(CURDATE(), Nascimento))/365) as idade, CobradorFalta as cobradorFalta, StringPosicoes as stringPosicoes FROM jogador WHERE ID = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$idJogador);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    function calcularPasse($novaId = null){
        if($novaId == null){

            $nivel = (int)$this->nivel;

            if ($this->nascimento < 100) {
                $idade = (int)$this->nascimento;
            } else {

                $anoAtual = date("Y");
                $idade = $anoAtual - (int)$this->nascimento;

            }

            $cobrancaFalta = $this->cobradorFalta;
            $stringPosicoes = $this->stringPosicoes;

        } else {
            $result = $this->readOne($novaId);
            $nivel = $result['nivel'];
            $idade = $result['idade'];
            $cobrancaFalta = $result['cobradorFalta'];
            $stringPosicoes = $result['stringPosicoes'];
        }

        $ajustePorPosicao = array(1,1,1,1,1,1,1,1,1,1,1,1,1,1,1);
        $bonusPolivalencia = array(1=>1,2=>1,3=>1,4=>1,5=>1,6=>1,7=>1);
        $bonusCobrancaFalta = 1.08;
        $idadeMax = array(1=>20,2=>22,3=>28,4=>30,5=>40,6=>45);
        $idadeMult = array(1=>1.3,2=>1.15,3=>1,4=>0.9,5=>0.8,6=>0.5);
        $parametroA = array(0=>2, 1=>40,2=>175,3=>250,4=>350,5=>400);
        $parametroB = array(0=>10, 1=>100,2=>675,3=>4250,4=>6850,5=>9700);
        $nivelMin = array(0=>10,1=>31,2=>51,3=>71,4=>81,5=>89);
        $nivelMax = array(0=>30,1=>50,2=>70,3=>80,4=>88,5=>97);

        //determinacao da faixa de nivel
        for($i = 0;$i < 6; $i++){
            if($nivel<=$nivelMax[$i]){
                $faixaNivel = $i;
                break;
            }
        }

        //determinacao da faixa de idade
        for($i = 1;$i < 7; $i++){
            if($idade<=$idadeMax[$i]){
                $faixaIdade = $i;
                break;
            }
        }

        //calculo base
        $base = ($parametroA[$faixaNivel] * ($nivel - $nivelMin[$faixaNivel])) + $parametroB[$faixaNivel];

        $passe = $base;

        //cobranca falta
        if($cobrancaFalta > 0){
            $passe = $passe * $bonusCobrancaFalta;
        }

        //idade
        $passe = $passe * $idadeMult[$faixaIdade];

        //posicoes
        $posicoes = str_split($stringPosicoes, 1);
        $polivalencia = array_sum($posicoes);
        if($polivalencia > 7){
          $polivalencia = 7;
        }
        foreach($posicoes as $key=>&$posicao_especifica){
            $posicao_especifica = $posicao_especifica * $ajustePorPosicao[$key];
        }

        unset($posicao_especifica);

        $passe = 1000 * $passe * ((array_sum($posicoes))/($polivalencia)) * $bonusPolivalencia[$polivalencia];

        if($passe < 0){
            $passe = 0;
        }

        return $passe;

    }

    function selecionarElencoTime($id_time,$from_record_num,$records_per_page){

        $id_time = htmlspecialchars(strip_tags($id_time));

        $query = "SELECT j.id as idJogador, j.cobradorFalta, j.Sexo as sexoJogador, j.Nome as nomeJogador, j.Nascimento, c.titularidade, c.posicaoBase, c.capitao, c.cobrancaPenalti, c.ModificadorNivel, c.encerramento, c.tipoContrato, j.valor, j.disponibilidade, p.bandeira as bandeiraPais, p.sigla as siglaPais, j.Nivel, j.StringPosicoes, p.id as idPais, c.titularidade, m.Nome as mentalidade, p.dono as donoJogador, FLOOR((DATEDIFF(CURDATE(), j.Nascimento))/365) as Idade, j.foto 
        FROM contratos_jogador c
        LEFT JOIN jogador j ON c.jogador = j.id
        LEFT JOIN mentalidade m ON j.Mentalidade = m.ID
        LEFT JOIN paises p ON j.Pais = p.id
        WHERE c.clube = ?
        ORDER BY c.titularidade DESC, j.StringPosicoes DESC
                LIMIT
                    {$from_record_num}, {$records_per_page}";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1,$id_time);
        $stmt->execute();

        return $stmt;
        }

        public function countAllSingleTeam($timeId){

            $timeId = htmlspecialchars(strip_tags($timeId));

        $query = "SELECT jogador FROM contratos_jogador WHERE clube = ? ";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1,$timeId);
        $stmt->execute();

        $num = $stmt->rowCount();

        return $num;
        }

        function ultimaTransferencia($id_jogador, $id_time){

            $id_jogador = htmlspecialchars(strip_tags($id_jogador));
            $id_time = htmlspecialchars(strip_tags($id_time));


        $query = "SELECT c.Nome, t.data, c.ID FROM transferencias t
        LEFT JOIN clube c ON t.clubeOrigem = c.ID
        WHERE t.jogador=:jogador AND t.clubeDestino=:clubeDestino ORDER BY t.data DESC LIMIT 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(":clubeDestino",$id_time);
        $stmt->bindParam(":jogador",$id_jogador);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $row['data'] = date("d-m-Y", strtotime($row['data']));
        if($row['Nome']==""){
            $row['Nome']= "Sem clube";
        }

        $results = array("Clube" => $row['Nome'], "Data" => $row['data'], "ID" => $row['ID']);

        return $results;
        }

        function listaPosicoes($stringPosicoes){
            $html = $stringPosicoes;
            $needle = "1";
            $lastPos = 0;
            $positions = array();

            while (($lastPos = strpos($html, $needle, $lastPos))!== false) {
                $positions[] = $lastPos;
                $lastPos = $lastPos + strlen($needle);
            }

            $posicoes = '';
            foreach ($positions as $value) {
                $busca = $value+1;
                $query = "SELECT Sigla FROM posicoes WHERE ID=?";
                $newstmt = $this->conn->prepare( $query );
                $newstmt->bindParam(1, $busca);
                $newstmt->execute();
                $info = $newstmt->fetch(PDO::FETCH_ASSOC);
                extract($info);
                $posicoes .= $Sigla;
                $posicoes .= "-";
            }

            $posicoes = substr($posicoes, 0, -1);

            return $posicoes;
        }

        function posicaoPorSigla($siglaPosicao){
                $query = "SELECT ID FROM posicoes WHERE Sigla=? LIMIT 0,1";
                $newstmt = $this->conn->prepare( $query );
                $newstmt->bindParam(1, $siglaPosicao);
                $newstmt->execute();
                $info = $newstmt->fetch(PDO::FETCH_ASSOC);

                return $info['ID'];
        }

        function posicaoPorCodigo($codigoPosicao){
            $query = "SELECT Sigla FROM posicoes WHERE ID=? LIMIT 0,1";
            $newstmt = $this->conn->prepare( $query );
            $newstmt->bindParam(1, $codigoPosicao);
            $newstmt->execute();
            $info = $newstmt->fetch(PDO::FETCH_ASSOC);

            return $info['Sigla'];
        }

        function nomePosicaoPorCodigo($codigoPosicao){
            $query = "SELECT Nome FROM posicoes WHERE ID=? LIMIT 0,1";
            $newstmt = $this->conn->prepare( $query );
            $newstmt->bindParam(1, $codigoPosicao);
            $newstmt->execute();
            $info = $newstmt->fetch(PDO::FETCH_ASSOC);

            return $info['Nome'];
        }

        function lerPropostasPendentes($idUsuario,$admin, $from_record_num,$records_per_page){
            $idUsuario = htmlspecialchars(strip_tags($idUsuario));
			
			if($admin == 0){
				$admin_query = "";
			} else {
				$admin_query = " 
				UNION 
				SELECT j.Nome as nomeJogador, c.id as idClubeOrigem, d.id as idClubeDestino, c.Nome as clubeOrigem, d.Nome as clubeDestino, t.valor, c.Escudo as escudoOrigem, d.Escudo as escudoDestino, j.id as idJogador, 'inbox' as direcao, t.data as data, j.Nivel as nivelJogador, t.status_execucao as status_execucao, t.id as idTransferencia, (case when t.status_execucao = 0 then 1 when t.status_execucao = 3 then 2 end) as precedencia, emprestimo, encerramento 
				FROM transferencias t
				LEFT JOIN clube c ON t.clubeOrigem = c.id
				LEFT JOIN jogador j ON t.jogador = j.id
				LEFT JOIN paises p ON c.Pais = p.id
				LEFT JOIN clube d ON t.clubeDestino = d.id
				LEFT JOIN paises q ON j.Pais = q.id
				LEFT JOIN paises z ON d.Pais = z.id
				WHERE (p.dono = 0 AND q.dono = 0 AND (t.status_execucao = 0 OR t.status_execucao = 3))";
			
			}

            $query = "SELECT j.Nome as nomeJogador, c.id as idClubeOrigem, d.id as idClubeDestino, c.Nome as clubeOrigem, d.Nome as clubeDestino, t.valor, c.Escudo as escudoOrigem, d.Escudo as escudoDestino, j.id as idJogador, 'inbox' as direcao, t.data as data, j.Nivel as nivelJogador, t.status_execucao as status_execucao, t.id as idTransferencia, (case when t.status_execucao = 0 then 1 when t.status_execucao = 3 then 2 end) as precedencia, emprestimo, encerramento 
            FROM transferencias t
            LEFT JOIN clube c ON t.clubeOrigem = c.id
            LEFT JOIN jogador j ON t.jogador = j.id
            LEFT JOIN paises p ON c.Pais = p.id
            LEFT JOIN clube d ON t.clubeDestino = d.id
            LEFT JOIN paises q ON j.Pais = q.id
            LEFT JOIN paises z ON d.Pais = z.id
            WHERE ((p.dono = ? AND (t.status_execucao = 0 OR t.status_execucao = 3)) OR (c.id = 0 AND z.dono <> ? AND q.dono = ? AND (t.status_execucao = 0 OR t.status_execucao = 3)))
            UNION
            SELECT j.Nome as nomeJogador, c.id as idClubeOrigem, d.id as idClubeDestino, c.Nome as clubeOrigem,  d.Nome as clubeDestino, t.valor, c.Escudo as escudoOrigem, d.Escudo as escudoDestino, j.id as idJogador, 'outbox' as direcao, t.data as data, j.Nivel as nivelJogador, t.status_execucao, t.id as idTransferencia, (case when t.status_execucao = 2 then 1 else 2 end) as precedencia, emprestimo, encerramento 
            FROM transferencias t
            LEFT JOIN clube d ON t.clubeDestino = d.id
            LEFT JOIN jogador j ON t.jogador = j.id
            LEFT JOIN paises p ON d.Pais = p.id
            LEFT JOIN clube c ON t.clubeOrigem = c.id
            LEFT JOIN paises q ON j.Pais = q.id
            WHERE p.dono = ? AND (c.id <> 0 OR q.dono <> ?) ".$admin_query ." 
            ORDER BY precedencia ASC, data DESC
                    LIMIT
                        {$from_record_num}, {$records_per_page}";

            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1,$idUsuario);
            $stmt->bindParam(2,$idUsuario);
            $stmt->bindParam(3,$idUsuario);
            $stmt->bindParam(4,$idUsuario);
            $stmt->bindParam(5,$idUsuario);
            $stmt->execute();

            return $stmt;

        }

        public function contarPropostas($idUsuario, $somenteRecebidas = null){

            $idUsuario = htmlspecialchars(strip_tags($idUsuario));

            if($somenteRecebidas == null){
            $subQuery = "UNION
            SELECT t.id FROM transferencias t
            LEFT JOIN clube d ON t.clubeDestino = d.id
            LEFT JOIN paises p ON d.Pais = p.id
            WHERE p.dono = ? ";
            } else {
                $subQuery = "";
            }

        $query = "SELECT t.id FROM transferencias t
        LEFT JOIN jogador j ON t.jogador = j.id
        LEFT JOIN paises p ON j.Pais = p.id
        LEFT JOIN clube c ON t.clubeOrigem = c.id
        LEFT JOIN paises q ON c.Pais = q.id
        WHERE ((p.dono = ? AND t.clubeOrigem = 0) OR (t.clubeOrigem <> 0 AND q.dono = ?)) AND (t.status_execucao = 0 OR t.status_execucao = 2) ".$subQuery;

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1,$idUsuario);
        $stmt->bindParam(2,$idUsuario);
        if($somenteRecebidas == null){
            $stmt->bindParam(3,$idUsuario);
        }
        $stmt->execute();

        $num = $stmt->rowCount();

        return $num;
        }

        public function avaliarProposta($idTransferencia, $acao, $valor = null){

            $idTransferencia = htmlspecialchars(strip_tags($idTransferencia));
            $acao = htmlspecialchars(strip_tags($acao));

            if($acao == 'recusar'){
                $query = "UPDATE transferencias SET status_execucao = 3 WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1,$idTransferencia);

            }

            if($acao == 'contrapropor'){
                $query = "UPDATE transferencias SET status_execucao = 2, valor = ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1,$valor);
                $stmt->bindParam(2,$idTransferencia);

            }

            if($acao == 'aceitar'){

                $infoquery = "SELECT jogador, clubeOrigem, clubeDestino, valor, encerramento, emprestimo FROM transferencias WHERE id = ?";
                $stmt = $this->conn->prepare($infoquery);
                $stmt->bindParam(1,$idTransferencia);
                if($stmt->execute()){
                } else {
                    return false;
                }
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if($row['valor'] == 0){
                    $passe = $this->calcularPasse($row['jogador']);
                } else {
                    $passe = $row['valor'];
                }


                $novoSalario = $this->calcularSalario($passe);
                
                if($row['emprestimo'] == 1){
                    $clubeVinculado = $row['clubeOrigem'];
                } else {
                    $clubeVinculado = 0;
                }

                if($row['clubeOrigem'] == 0){
                    $prequery = "INSERT INTO contratos_jogador SET
                    clube=:clube, tipoContrato=0, clubeVinculado=:clubeVinculado, salario=:salario, capitao=0, cobrancaPenalti=0, titularidade=-1, jogador=:jogador, encerramento=:encerramento";
                    $stmt = $this->conn->prepare($prequery);
                } else {
                    $prequery = "UPDATE contratos_jogador
                    SET
                        clube=:clube, encerramento=:encerramento, clubeVinculado=:clubeVinculado, tipoContrato=0, salario=:salario, capitao=0, cobrancaPenalti=0, titularidade=-1
                        WHERE jogador=:jogador AND clube=:clubeOrigem";
                    $stmt = $this->conn->prepare($prequery);
                    $stmt->bindParam(':clubeOrigem',$row['clubeOrigem']);
                }
                $stmt->bindParam(':clube',$row['clubeDestino']);
                $stmt->bindParam(':salario',$novoSalario);
                $stmt->bindParam(':jogador',$row['jogador']);
                $stmt->bindParam(':encerramento', $row['encerramento']);
                $stmt->bindParam(':clubeVinculado', $clubeVinculado);

                if($stmt->execute()){
                } else {
                    return false;
                }


                $otherquery = "UPDATE jogador SET valor=:valor WHERE id=:jogador";
                $stmt = $this->conn->prepare($otherquery);
                $stmt->bindParam(':valor',$passe);
                $stmt->bindParam(':jogador',$row['jogador']);
                if($stmt->execute()){
                } else {
                    return false;
                }

                $newquery = "UPDATE transferencias SET status_execucao = 3 WHERE id <> ? AND jogador = ? AND status_execucao <> 1";
                $stmt = $this->conn->prepare($newquery);
                $stmt->bindParam(1,$idTransferencia);
                $stmt->bindParam(2,$row['jogador']);
                //$stmt->bindParam(3,$row['clubeDestino']);
                $stmt->execute();

                $query = "UPDATE transferencias SET status_execucao = 1 WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1,$idTransferencia);


            }

            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
        }


        function exportacao($idPais = null, $idTime = null, $orderBy = null, $idLiga = null){

            $idPais = htmlspecialchars(strip_tags($idPais));
            $idTime = htmlspecialchars(strip_tags($idTime));
			$idLiga = htmlspecialchars(strip_tags($idLiga));

            if($idPais != null){
              $subquery = " b.Pais=:pais";
            } else if($idTime != null){
              $subquery = " b.ID=:clube ";
            } else if($idLiga != null) {
			  $subquery = " b.liga=:liga ";
			}
            
            if($orderBy != null){
                $subquery .= " ORDER BY c.titularidade DESC, c.posicaoBase ASC ";
            }

            $query = "SELECT DISTINCT a.ID as idJogador, a.Nome as nomeJogador, FLOOR((DATEDIFF(CURDATE(), Nascimento))/365) as Idade, o.Sigla as posicao, c.titularidade, c.posicaoBase as codigoPosicaoBase,  Mentalidade, CobradorFalta, StringPosicoes, (Nivel + c.ModificadorNivel) as Nivel, Marcacao, Desarme, VisaoJogo, Movimentacao, Cruzamentos, Cabeceamento, Tecnica, ControleBola, Finalizacao, FaroGol, Velocidade, Forca, Reflexos, Seguranca, Saidas, JogoAereo, Lancamentos, DefesaPenaltis, Determinacao, DeterminacaoOriginal, p.bandeira as Nacionalidade, (Marcacao + Desarme + VisaoJogo + Movimentacao + Cruzamentos + Cabeceamento + Tecnica + ControleBola + Finalizacao + FaroGol + Velocidade + Forca + Reflexos + Seguranca + Saidas + JogoAereo + Lancamentos + DefesaPenaltis + Determinacao) as somaAtributos, Nascimento, b.Nome as Time, b.ID as idTime, CASE WHEN a.sexo = 1 THEN 'F' WHEN a.sexo = 0 THEN 'M' END as sexo
            FROM jogador a
            LEFT JOIN paises p ON a.Pais = p.id
            LEFT JOIN contratos_jogador c ON c.jogador = a.ID
            LEFT JOIN posicoes o ON c.posicaoBase = o.ID 
            LEFT JOIN clube b ON b.ID = c.clube
            WHERE " . $subquery;
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

        function maisValiosos($from_record_num, $records_per_page){
            $query = "SELECT j.id, j.Nome as nomeJogador, s.Nome as posicaoBase, j.StringPosicoes as stringPosicoes, j.Pais as nacionalidade, p.Bandeira as bandeiraJogador, FLOOR(DATEDIFF(NOW(),j.Nascimento)/365) as idade, c.Escudo as escudo, o.clube, j.valor, j.Nivel, j.sexo
            FROM jogador j
            LEFT JOIN paises p ON j.Pais = p.ID
            LEFT JOIN contratos_jogador o ON o.jogador = j.ID
            LEFT JOIN clube c ON o.clube = c.ID

            LEFT JOIN posicoes s ON o.posicaoBase = s.ID
            ORDER BY valor DESC LIMIT {$from_record_num},{$records_per_page}";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        }

        function pesquisaAvancada($nivelMin, $nivelMax, $idadeMin, $idadeMax, $cobrancaFalta, $disponivel, $nome, $nacionalidade, $mentalidade, $stringPosicoes, $seletorPosicoes, $semclube, $valorMin, $valorMax, $sexo, $apenasConfusa, $usuarioLogado){

            $nivelMin = htmlspecialchars(strip_tags($nivelMin));
            $nivelMax = htmlspecialchars(strip_tags($nivelMax));
            $idadeMin = htmlspecialchars(strip_tags($idadeMin));
            $idadeMax = htmlspecialchars(strip_tags($idadeMax));
            $cobrancaFalta = htmlspecialchars(strip_tags($cobrancaFalta));
            $disponivel = htmlspecialchars(strip_tags($disponivel));
            $nome = htmlspecialchars(strip_tags($nome));
            $nacionalidade = htmlspecialchars(strip_tags($nacionalidade));
            $mentalidade = htmlspecialchars(strip_tags($mentalidade));
            $stringPosicoes = htmlspecialchars(strip_tags($stringPosicoes));
            $seletorPosicoes = htmlspecialchars(strip_tags($seletorPosicoes));
            $semclube = htmlspecialchars(strip_tags($semclube));
            $valorMin = htmlspecialchars(strip_tags($valorMin));
            $valorMax = htmlspecialchars(strip_tags($valorMax));
            $sexo = htmlspecialchars(strip_tags($sexo));
            $apenasConfusa = htmlspecialchars(strip_tags($apenasConfusa));

            //converter stringPosicoes para cada posicao
            $splitPosicoes = str_split($stringPosicoes);
            $arrayPosicoes = array();
            foreach($splitPosicoes as $indice => $posicao){
                if($posicao == 1){
                    $arrayPosicoes[] = $this->posicaoPorCodigo($indice + 1);
                }
            }

            $subquery = '';
            if($cobrancaFalta != null){
                $subquery .= ' AND cobrancaFalta <> "-" ';
            }

            if($disponivel != null){
                $subquery .= ' AND disponibilidade = "Sim" ';
            } else {
              $subquery .= ' AND disponibilidade <> "Aposentado" AND disponibilidade <> "Expatriado" ';
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

            if(strcmp($stringPosicoes,"000000000000000") != 0){
                if($seletorPosicoes != null){
                    //contem todas as posicoes
                    foreach($arrayPosicoes as $pos){
                        $subquery .= ' AND posicoes LIKE "%'.$pos.'%" ';
                    }

                } else {

                    //contem qualquer uma das posicoes
                    $subquery .= ' AND (';
                    foreach($arrayPosicoes as $pos){
                        $subquery .= 'posicoes LIKE "%'.$pos.'%" OR ';
                    }
                    $subquery .= ' 1 = 2 )';

                }
            }


            $query = "SELECT t1.*, d.Nome as nomeClube, d.Escudo as escudoClube  FROM (SELECT j.ID as idJogador, j.Nome as nomeJogador, FLOOR(DATEDIFF(NOW(),j.Nascimento)/365) as idadeJogador, m.Nome as mentalidade, r.Nome as cobrancaFalta, j.Sexo as sexoJogador, j.Pais as nacionalidade,
            CONCAT(CASE WHEN SUBSTRING(j.StringPosicoes,1,1) = 0 THEN '' ELSE 'G-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,2,1) = 0 THEN '' ELSE 'LD-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,3,1) = 0 THEN '' ELSE 'LE-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,4,1) = 0 THEN '' ELSE 'Z-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,5,1) = 0 THEN '' ELSE 'AD-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,6,1) = 0 THEN '' ELSE 'AE-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,7,1) = 0 THEN '' ELSE 'V-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,8,1) = 0 THEN '' ELSE 'MD-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,9,1) = 0 THEN '' ELSE 'ME-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,10,1) = 0 THEN '' ELSE 'MC-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,11,1) = 0 THEN '' ELSE 'PD-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,12,1) = 0 THEN '' ELSE 'PE-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,13,1) = 0 THEN '' ELSE 'MA-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,14,1) = 0 THEN '' ELSE 'Am-' END,
            CASE WHEN SUBSTRING(j.StringPosicoes,15,1) = 0 THEN '' ELSE 'Aa-' END) as posicoes, j.StringPosicoes as stringPosicoes,
            j.valor, j.Nivel as nivel, CASE WHEN j.disponibilidade = -1 THEN 'Aposentado' WHEN j.disponibilidade = 0 THEN 'Não' WHEN j.disponibilidade = -2 THEN 'Expatriado' ELSE 'Sim' END as disponibilidade, p.bandeira, q.bandeira as bandeiraClube, q.ID as paisClube, CASE WHEN b.ID is not NULL THEN b.ID ELSE 0 END as idClube, b.liga as idLiga, l.Nome as ligaClube,  CASE WHEN c.posicaoBase <> 0 THEN o.Nome ELSE '' END as posicaoBaseJogador, j.Mentalidade as mentalidadeIndex, p.ranqueavel, CASE WHEN p.dono <> :usuarioLogado THEN 0 ELSE 1 END as donoJogador, c.tipoContrato
            FROM jogador j
            LEFT JOIN paises p ON j.Pais = p.id
            LEFT JOIN contratos_jogador c ON j.ID = c.jogador
            LEFT JOIN clube b ON b.ID = c.clube
            LEFT JOIN paises q ON b.Pais = q.ID
            LEFT JOIN liga l ON l.ID = b.liga
            LEFT JOIN posicoes o ON o.ID = c.posicaoBase
            LEFT JOIN mentalidade m ON m.ID = j.Mentalidade
            LEFT JOIN cobrador r ON r.ID = j.CobradorFalta) t1
            LEFT JOIN clube d ON d.ID = t1.idClube
            WHERE (tipoContrato = 0 OR tipoContrato is NULL) AND nivel >= :nivelMin AND nivel <= :nivelMax AND
                idadeJogador >= :idadeMin AND idadeJogador <= :idadeMax AND valor <= :valorMax AND valor >= :valorMin AND sexoJogador = :sexo " . $subquery;


            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nivelMin',$nivelMin);
            $stmt->bindParam(':nivelMax',$nivelMax);
            $stmt->bindParam(':idadeMin',$idadeMin);
            $stmt->bindParam(':idadeMax',$idadeMax);
            $stmt->bindParam(':valorMin',$valorMin);
            $stmt->bindParam(':valorMax',$valorMax);
            $stmt->bindParam(':usuarioLogado',$usuarioLogado);
            $stmt->bindParam(':sexo',$sexo);
            if($mentalidade != null){
                $stmt->bindParam(':mentalidade',$mentalidade);
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


        function disponibilizar($idJogador){
            $idJogador = htmlspecialchars(strip_tags($idJogador));

            $query = "UPDATE jogador SET disponibilidade = ABS(disponibilidade - 1) WHERE ID = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idJogador);
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
        }

        function aposentar($idJogador,$idClube){
            $idJogador = htmlspecialchars(strip_tags($idJogador));
            $idClube = htmlspecialchars(strip_tags($idClube));

            $error_count = 0;
            $query = "UPDATE jogador SET disponibilidade = -1 WHERE ID = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idJogador);
            if($stmt->execute()){
            } else {
                $error_count++;
            }

            if($this->demitir($idJogador,$idClube)){

            } else {
                $error_count++;
            }

            if($error_count > 0){
                return false;
            } else {
                return true;
            }


        }
		
		function expatriar($idJogador,$idClube){
            $idJogador = htmlspecialchars(strip_tags($idJogador));
            $idClube = htmlspecialchars(strip_tags($idClube));

            $error_count = 0;
            $query = "UPDATE jogador SET disponibilidade = -2 WHERE ID = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idJogador);
            if($stmt->execute()){
            } else {
                $error_count++;
            }

            if($this->demitir($idJogador,$idClube)){

            } else {
                $error_count++;
            }

            if($error_count > 0){
                return false;
            } else {
                return true;
            }


        }



        function demitir($idJogador,$idClube){

        //verificar tipo transferencia
                $query_origem = "SELECT tipoContrato, clubeVinculado FROM contratos_jogador WHERE jogador=:jogador AND clube=:clube";

                $stmt = $this->conn->prepare( $query_origem );
                $stmt->bindParam(":jogador", $idJogador);
                $stmt->bindParam(":clube", $idClube);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if($row == false){
                    $tipoContrato = 0;
                    $clubeVinculado = 0;
                } else {
                    $tipoContrato = $row['tipoContrato'];
                    $clubeVinculado = $row['clubeVinculado'];
                }

                $error_count = 0;
				
		
            if($clubeVinculado == 0){
                $query_contrato = "DELETE FROM
                            contratos_jogador
                        WHERE
                            jogador=:jogador AND clube=:clube";
                $stmt = $this->conn->prepare( $query_contrato );
                $stmt->bindParam(":jogador", $idJogador);
                $stmt->bindParam(":clube", $idClube);
                if($stmt->execute()){
                } else {
                    $error_count++;
                }
            } else {
                $query_contrato = "UPDATE contratos_jogador SET clube = :clubeVinculado, titularidade = -1, capitao = 0, cobrancaPenalti = 0, encerramento = '0000-00-00', clubeVinculado = 0 
                        WHERE
                            jogador=:jogador AND clube=:clube";
                $stmt = $this->conn->prepare( $query_contrato );
                $stmt->bindParam(":jogador", $idJogador);
                $stmt->bindParam(":clube", $idClube);
                $stmt->bindParam(":clubeVinculado", $clubeVinculado);
                if($stmt->execute()){
                } else {
                    $error_count++;
                }
            }



            if($tipoContrato == 0){
                        $query_transferencia = "INSERT INTO transferencias
                        SET
                            jogador=:jogador, clubeOrigem=:clubeOrigem, clubeDestino=:clubeVinculado, valor=0, tipoTransferencia=:tipoTransferencia, status_execucao=1";
        $stmt = $this->conn->prepare( $query_transferencia );
        $stmt->bindParam(":jogador", $idJogador);
        $stmt->bindParam(":tipoTransferencia", $tipoContrato);
        $stmt->bindParam(":clubeOrigem", $idClube);
        $stmt->bindParam(":clubeVinculado", $clubeVinculado);
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

        function selectPosicoes() {

            $query = "SELECT * FROM posicoes WHERE id>1";

            $stmt = $this->conn->prepare($query);

            $stmt->execute();

            return $stmt;
        }

        function verificarDono($idJogador){
            $idJogador = htmlspecialchars(strip_tags($idJogador));

            $query = "SELECT p.dono FROM jogador j LEFT JOIN paises p ON j.Pais = p.id WHERE j.ID = ? LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idJogador);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $idDono = $row['dono'];
            return $idDono;

        }

        function verificarNivelAtual($idJogador){
            $idJogador = htmlspecialchars(strip_tags($idJogador));

            $query = "SELECT Nivel FROM jogador WHERE ID = ? LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idJogador);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nivel = $row['Nivel'];
            return $nivel;

        }

        function verificarStringPosicoesAtual($idJogador){
            $idJogador = htmlspecialchars(strip_tags($idJogador));

            $query = "SELECT StringPosicoes FROM jogador WHERE ID = ? LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idJogador);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $pos = $row['StringPosicoes'];
            return $pos;

        }

        function editar($idJogador,$idTime,$nomeJogador,$nacionalidadeJogador,$nascimentoJogador,$valorJogador,$posicoesJogador,$nivelJogador,$isDono, $atividadeJogador, $mentalidadeJogador = null, $determinacaoJogador = null, $cobrancaFaltaJogador = null, $encerramentoContrato = null, $foto = null, $desdeContrato = null){

            $idJogador = htmlspecialchars(strip_tags($idJogador));
            $idTime = htmlspecialchars(strip_tags($idTime));
            $nomeJogador = htmlspecialchars(strip_tags($nomeJogador));
            $nacionalidadeJogador = htmlspecialchars(strip_tags($nacionalidadeJogador));
            $nascimentoJogador = htmlspecialchars(strip_tags($nascimentoJogador));
            $valorJogador = htmlspecialchars(strip_tags($valorJogador));
            $nivelJogador = htmlspecialchars(strip_tags($nivelJogador));
            $atividadeJogador = htmlspecialchars(strip_tags($atividadeJogador));
            $mentalidadeJogador = htmlspecialchars(strip_tags($mentalidadeJogador));
            $determinacaoJogador = htmlspecialchars(strip_tags($determinacaoJogador));
            $cobrancaFaltaJogador = htmlspecialchars(strip_tags($cobrancaFaltaJogador));
			$encerramentoContrato = htmlspecialchars(strip_tags($encerramentoContrato));
			$foto = htmlspecialchars(strip_tags($foto));
			$desdeContrato = htmlspecialchars(strip_tags($desdeContrato));
			
			if(!is_array($posicoesJogador)){
				$posicoesJogador = explode(",", $posicoesJogador);
			}
			

            if($nivelJogador > 99){
              $nivelJogador = 99;
            }


            if($nivelJogador < 1){
              $nivelJogador = 1;
            }

            $error_count = 0;

            if($mentalidadeJogador != null && $determinacaoJogador != null){

                $nivel = $this->verificarNivelAtual($idJogador);
                $diferenca = $nivel - $nivelJogador;

                if($isDono){
                    $nome = $nomeJogador;
                    $valor = $valorJogador;
                    $nacionalidade = $nacionalidadeJogador;
                    $nascimento = $nascimentoJogador;
                    $atividade = $atividadeJogador;
                    $mentalidade = $mentalidadeJogador;
                    $determinacao = $determinacaoJogador;
                    $cobrancaFalta = $cobrancaFaltaJogador;
					
					

                    if(sizeOf($posicoesJogador)== 0 ){
							$stringPosicoes = $this->verificarStringPosicoesAtual($idJogador);
                        } else {
                        //determinacao string posicoes

                        $stringPosicoes = "";

                        for($i = 0;$i<15;$i++){
                            $codigo = $i+1;
                            if(array_search($codigo,$posicoesJogador) !== false){
                                $stringPosicoes .= "1";
                            } else {
                                $stringPosicoes .= "0";
                            }
                        }
                    }

					if($foto != "" && $foto != null){
						$query_foto = ", foto=:foto";
					} else {
						$query_foto = "";
					}

                    $query = "UPDATE jogador SET Nome=:nome, Nascimento=:nascimento, Pais=:nacionalidade, StringPosicoes=:stringPosicoes, valor=:valor, Nivel=:nivel, Mentalidade=:mentalidade, Determinacao=:determinacao, DeterminacaoOriginal=:determinacaoOriginal, CobradorFalta=:cobradorFalta, disponibilidade =:disponibilidade ".$query_foto." WHERE ID = :id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(":nome", $nome);
                    $stmt->bindParam(":nascimento", $nascimento);
                    $stmt->bindParam(":nacionalidade",$nacionalidade);
                    $stmt->bindParam(":valor",$valor);
                    $stmt->bindParam(":nivel",$nivelJogador);
                    $stmt->bindParam(":stringPosicoes", $stringPosicoes);
                    $stmt->bindParam(":id", $idJogador);
                    $stmt->bindParam(":cobradorFalta", $cobrancaFalta);
                    $stmt->bindParam(":determinacao", $determinacao);
                    $stmt->bindParam(":determinacaoOriginal", $determinacao);
                    $stmt->bindParam(":mentalidade", $mentalidade);
                    $stmt->bindParam(":disponibilidade", $atividade);
					if($foto != "" && $foto != null){
						$stmt->bindParam(":foto", $foto);
					} 
					
                    if($stmt->execute()){

                    } else {
                        $error_count++;
                    }

                    // $queryContrato = "UPDATE contratos_jogador SET ModificadorNivel=ModificadorNivel + :mod WHERE jogador=:jogador";
                    // $stmt = $this->conn->prepare($queryContrato);
                    // $stmt->bindParam(":mod", $diferenca);
                    // $stmt->bindParam(":jogador", $idJogador);
                    // if($stmt->execute()){

                    // } else {
                    //     $error_count++;
                    // }

                } else {

                    if(sizeOf($posicoesJogador)== 0 ){
                        $stringPosicoes = $this->verificarStringPosicoesAtual($idJogador);
                        } else {
                    //determinacao string posicoes

                    $stringPosicoesAtual = $this->verificarStringPosicoesAtual($idJogador);

                    $stringPosicoes = "";

                    for($i = 0;$i<15;$i++){
                        $codigo = $i+1;
                        if(array_search($codigo,$posicoesJogador) !== false){
                            $stringPosicoes .= "1";
                        } else {
                            if(strcmp($stringPosicoesAtual[$i],'1') == 0){
                                $stringPosicoes .= "1";
                            } else {
                                $stringPosicoes .= "0";
                            }
                        }
                    }
                }
				
				$nome = $nomeJogador;
				$nacionalidade = $nacionalidadeJogador;
				
				if($foto != "" && $foto != null){
					$query_foto = ", foto=:foto";
				} else {
					$query_foto = "";
				}

                $query = "UPDATE jogador SET Nome=:nome, StringPosicoes=:stringPosicoes, Nivel=:nivel, Pais=:nacionalidade ".$query_foto." WHERE ID = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":nivel",$nivelJogador);
                $stmt->bindParam(":stringPosicoes", $stringPosicoes);
                $stmt->bindParam(":id", $idJogador);
					if($foto != "" && $foto != null){
						$stmt->bindParam(":foto", $foto);
					} 
				$stmt->bindParam(":nome", $nome);
				$stmt->bindParam(":nacionalidade",$nacionalidade);
                if($stmt->execute()){

                } else {
                    $error_count++;
                }

                $queryContrato = "UPDATE contratos_jogador SET ModificadorNivel=ModificadorNivel + :mod WHERE jogador=:jogador";
                $stmt = $this->conn->prepare($queryContrato);
                $stmt->bindParam(":mod", $diferenca);
                $stmt->bindParam(":jogador", $idJogador);
                if($stmt->execute()){

                } else {
                    $error_count++;
                }

                }

            } else {

            if($isDono){

                $nivel = $this->verificarNivelAtual($idJogador);
                $modificador = $nivelJogador - $nivel;
                $nome = $nomeJogador;
                $valor = $valorJogador;
                $nacionalidade = $nacionalidadeJogador;
                $nascimento = $nascimentoJogador;

                if(sizeOf($posicoesJogador)== 0 ){
                $stringPosicoes = $this->verificarStringPosicoesAtual($idJogador);
                } else {
                //determinacao string posicoes

                $stringPosicoes = "";

                for($i = 0;$i<15;$i++){
                    $codigo = $i+1;
                    if(array_search($codigo,$posicoesJogador) !== false){
                        $stringPosicoes .= "1";
                    } else {
                        $stringPosicoes .= "0";
                    }
                }
            }
				// queryfoto
				if($foto != "" && $foto != null){
					$query_foto = ", foto=:foto";
				} else {
					$query_foto = "";
				}

                $query = "UPDATE jogador SET Nome=:nome, Nascimento=:nascimento, Pais=:nacionalidade, StringPosicoes=:stringPosicoes, valor=:valor, Nivel=:nivel ".$query_foto." WHERE ID = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":nome", $nome);
                $stmt->bindParam(":nascimento", $nascimento);
                $stmt->bindParam(":nacionalidade",$nacionalidade);
                $stmt->bindParam(":valor",$valor);
                $stmt->bindParam(":nivel",$nivelJogador);
                $stmt->bindParam(":stringPosicoes", $stringPosicoes);
                $stmt->bindParam(":id", $idJogador);
				if($foto != "" && $foto != null){
					$stmt->bindParam(":foto", $foto);
				} 
                if($stmt->execute()){

                } else {
                    $error_count++;
                }

                // $queryContrato = "UPDATE contratos_jogador SET ModificadorNivel=:mod WHERE jogador=:jogador AND clube=:clube";
                // $stmt = $this->conn->prepare($queryContrato);
                // $stmt->bindParam(":mod", $modificador);
                // $stmt->bindParam(":clube", $idTime);
                // $stmt->bindParam(":jogador", $idJogador);
                // if($stmt->execute()){

                // } else {
                //     $error_count++;
                // }

            } else {

                $nivel = $this->verificarNivelAtual($idJogador);
                $modificador = $nivelJogador - $nivel;
                $valor = $valorJogador;

                if(sizeOf($posicoesJogador)== 0 ){
                    $stringPosicoes = $this->verificarStringPosicoesAtual($idJogador);
                    } else {
                //determinacao string posicoes

                $stringPosicoesAtual = $this->verificarStringPosicoesAtual($idJogador);

                $stringPosicoes = "";

                for($i = 0;$i<15;$i++){
                    $codigo = $i+1;
                    if(array_search($codigo,$posicoesJogador) !== false){
                        $stringPosicoes .= "1";
                    } else {
                        if(strcmp($stringPosicoesAtual[$i],'1') == 0){
                            $stringPosicoes .= "1";
                        } else {
                            $stringPosicoes .= "0";
                        }
                    }
                }
            }
			
				// queryfoto
				if($foto != "" && $foto != null){
					$query_foto = ", foto=:foto";
				} else {
					$query_foto = "";
				}

                $query = "UPDATE jogador SET StringPosicoes=:stringPosicoes, valor=:valor ".$query_foto." WHERE ID = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":valor",$valor);
                $stmt->bindParam(":stringPosicoes", $stringPosicoes);
                $stmt->bindParam(":id", $idJogador);
				if($foto != "" && $foto != null){
					$stmt->bindParam(":foto", $foto);
				} 
					
					
                if($stmt->execute()){

                } else {
                    $error_count++;
                }

                $queryContrato = "UPDATE contratos_jogador SET ModificadorNivel=:mod WHERE jogador=:jogador AND clube=:clube";
                $stmt = $this->conn->prepare($queryContrato);
                $stmt->bindParam(":mod", $modificador);
                $stmt->bindParam(":clube", $idTime);
                $stmt->bindParam(":jogador", $idJogador);
                if($stmt->execute()){

                } else {
                    $error_count++;
                }


            }

        }
		
		if($atividadeJogador < 0 && $isDono){
			if($this->demitir($idJogador,$idTime)){

            } else {
                $error_count++;
            }
		}
		
		if($isDono && $encerramentoContrato != null){
			if($this->alterarContrato($idJogador,$idTime, $encerramentoContrato)){

            } else {
                $error_count++;
            }
		}
		
		if($isDono && $desdeContrato != null){
			if($this->alterarInicioContrato($idJogador,$idTime, $desdeContrato)){

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

        function readInfo($idJogador){

            $idJogador = htmlspecialchars(strip_tags($idJogador));

            $queryBase = "SELECT j.Nome as nome, j.Pais as idPais, j.Nascimento as nascimento, j.StringPosicoes as stringPosicoes, j.valor, FLOOR((DATEDIFF(CURDATE(), j.Nascimento))/365) as idade, p.bandeira as bandeiraPais, p.nome as Pais, j.Marcacao, j.Desarme, j.VisaoJogo, j.Movimentacao, j.Cruzamentos, j.Cabeceamento, j.Tecnica, j.ControleBola, j.Finalizacao, j.FaroGol, j.Velocidade, j.Forca, j.Reflexos, j.Seguranca, j.Saidas, j.JogoAereo, j.Lancamentos, j.DefesaPenaltis, j.Nivel, j.foto FROM jogador j LEFT JOIN paises p ON j.Pais = p.id WHERE j.ID = ?";
            $stmt = $this->conn->prepare($queryBase);
            $stmt->bindParam(1,$idJogador);
            $stmt->execute();
            $resultBase = $stmt->fetch(PDO::FETCH_ASSOC);

            $queryContrato = "SELECT l.logo as logoLiga, l.tier as tier, l.nome as liga, c.Escudo as escudoTime, j.clube as idTime, c.Nome as time, c.Pais as paisTime, p.nome as nomePaisTime, p.bandeira as bandeiraPaisTime, c.liga as idLiga, j.encerramento as fimContrato, j.salario FROM contratos_jogador j LEFT JOIN clube c ON c.ID = j.clube LEFT JOIN liga l ON c.liga = l.ID LEFT JOIN paises p ON p.id = c.Pais WHERE jogador = ? AND tipoContrato = 0";
            $stmt = $this->conn->prepare($queryContrato);
            $stmt->bindParam(1,$idJogador);
            $stmt->execute();
            $resultContrato = $stmt->fetch(PDO::FETCH_ASSOC);

            $queryTransferencia = "SELECT data as inicioContrato FROM transferencias
            WHERE jogador=? AND clubeDestino=? ORDER BY data DESC LIMIT 0,1";
            $stmt = $this->conn->prepare($queryTransferencia);
            $stmt->bindParam(1,$idJogador);
            $stmt->bindParam(2,$resultContrato['idTime']);
            $stmt->execute();
            $resultTransferencia = $stmt->fetch(PDO::FETCH_ASSOC);

            $queryGolsSelecao = "SELECT count(id_evento) as golsSelecao FROM jogos_eventos
            WHERE id_jogador=? AND tipo = 1";
            $stmt = $this->conn->prepare($queryGolsSelecao);
            $stmt->bindParam(1,$idJogador);
            $stmt->execute();
            $resultGolsSelecao = $stmt->fetch(PDO::FETCH_ASSOC);

            $queryAmarelosSelecao = "SELECT count(id_evento) as amarelosSelecao FROM jogos_eventos
            WHERE id_jogador=? AND tipo = 2";
            $stmt = $this->conn->prepare($queryAmarelosSelecao);
            $stmt->bindParam(1,$idJogador);
            $stmt->execute();
            $resultAmarelosSelecao = $stmt->fetch(PDO::FETCH_ASSOC);

            $queryVermelhosSelecao = "SELECT count(id_evento) as vermelhosSelecao FROM jogos_eventos
            WHERE id_jogador=? AND tipo = 3";
            $stmt = $this->conn->prepare($queryVermelhosSelecao);
            $stmt->bindParam(1,$idJogador);
            $stmt->execute();
            $resultVermelhosSelecao = $stmt->fetch(PDO::FETCH_ASSOC);

            $resultTotal = array_merge($resultBase,$resultContrato,$resultTransferencia,$resultGolsSelecao, $resultAmarelosSelecao, $resultVermelhosSelecao);

            return $resultTotal;

        }
		
		function avaliarPersonalidade($idJogador){
			$idJogador = htmlspecialchars(strip_tags($idJogador));
			
			$query = "SELECT `ID`, `nome`, (reflexos/soma) as reflexos, (seguranca/soma) as seguranca, (saidas/soma) as saidas, (jogoAereo/soma) as jogoAereo, (lancamentos/soma) as lancamentos, (defesaPenaltis/soma) as defesaPenaltis, (marcacao/soma) as marcacao, (visaoJogo/soma) as visaoJogo, (cruzamentos/soma) as cruzamentos, (tecnica/soma) as tecnica,(finalizacao/soma) as finalizacao, (velocidade/soma) as velocidade, (desarme/soma) as desarme, (movimentacao/soma) as movimentacao, (cabeceamento/soma) as cabeceamento, (controleBola/soma) as controleBola, (faroGol/soma) as faroGol, (forca/soma) as forca FROM `perfis` 
			UNION
			SELECT jogador.ID, Nome, (reflexos/soma) as reflexos, (seguranca/soma) as seguranca, (saidas/soma) as saidas, (jogoAereo/soma) as jogoAereo, (lancamentos/soma) as lancamentos, (defesaPenaltis/soma) as defesaPenaltis, (marcacao/soma) as marcacao, (visaoJogo/soma) as visaoJogo, (cruzamentos/soma) as cruzamentos, (tecnica/soma) as tecnica,(finalizacao/soma) as finalizacao, (velocidade/soma) as velocidade, (desarme/soma) as desarme, (movimentacao/soma) as movimentacao, (cabeceamento/soma) as cabeceamento, (controleBola/soma) as controleBola, (faroGol/soma) as faroGol, (forca/soma) as forca FROM (SELECT ID, (reflexos+seguranca+saidas+jogoAereo+lancamentos+defesaPenaltis+marcacao+visaoJogo+cruzamentos+tecnica+finalizacao+velocidade+desarme+movimentacao+cabeceamento+controleBola+faroGol+forca) as soma FROM jogador WHERE ID = ?) t1
			LEFT JOIN jogador ON jogador.ID = t1.ID";
			$stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idJogador);
            $stmt->execute();
			
			$array_personalidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$array_jogador = array_pop($array_personalidades);
			$atributos_jogador = array_slice($array_jogador, 2);
			
			$coletor_perc =array();
			
			foreach($array_personalidades as $personalidade){
				$somatorio_diferenca = 0;
				$atributos_personalidade = array_slice($personalidade,2);
				foreach($atributos_personalidade as $key => $atributo){
					$somatorio_diferenca += abs($atributos_jogador[$key] - $atributo);
				}
				$coletor_perc[$personalidade["nome"]] =  round(1-$somatorio_diferenca,4)*100;
			} 
			
			arsort($coletor_perc);
			
			
			return(array_slice($coletor_perc,0,3));
					
		}

        function readTransferencias($from_record_num,$records_per_page,$idJogador){
            $idJogador = htmlspecialchars(strip_tags($idJogador));

        $query = "SELECT o.Nome as nomeOrigem, o.Escudo as escudoOrigem, o.Pais as paisOrigem, o.ID as idOrigem, d.Nome as nomeDestino, d.Escudo as escudoDestino, d.Pais as paisDestino, d.ID as idDestino, t.data, t.valor, l.Nome as nomeLigaOrigem, l.ID as idLigaOrigem, m.Nome as nomeLigaDestino, m.ID as idLigaDestino, p.bandeira as bandeiraOrigem, q.bandeira as bandeiraDestino
        FROM transferencias t
        LEFT JOIN clube o ON t.clubeOrigem = o.ID
        LEFT JOIN liga l ON o.liga = l.ID
        LEFT JOIN paises p ON o.Pais = p.ID
        LEFT JOIN clube d ON t.clubeDestino = d.ID
        LEFT JOIN liga m ON d.liga = m.ID
        LEFT JOIN paises q ON d.Pais = q.ID
        WHERE t.jogador = ? AND t.status_execucao = 1
        ORDER BY data DESC
        LIMIT {$from_record_num},{$records_per_page}";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idJogador);

            $stmt->execute();

            return $stmt;

        }

        function countAllTransferencias($idJogador){
            $idJogador = htmlspecialchars(strip_tags($idJogador));

            $query = "SELECT count(*) FROM transferencias WHERE jogador = ? AND status_execucao = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idJogador);

            $stmt->execute();
            $result = $stmt->fetchColumn();

            return $result;

        }

        function eliminarTransferenciasNegadas(){
            $query = "DELETE FROM transferencias WHERE status_execucao = 3 AND DATEDIFF(CURDATE(), data) > 15";
            $stmt = $this->conn->prepare($query);
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
        }

        function desconvocarSub21ForaIdade(){
            $query = "DELETE contratos_jogador FROM contratos_jogador  LEFT JOIN jogador  ON contratos_jogador.jogador = jogador.ID WHERE contratos_jogador.tipoContrato = 2 AND FLOOR((DATEDIFF(CURDATE(), jogador.Nascimento))/365) > 21";
            $stmt = $this->conn->prepare($query);
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
        }

        function desconvocarSub20ForaIdade(){
            $query = "DELETE contratos_jogador FROM contratos_jogador  LEFT JOIN jogador  ON contratos_jogador.jogador = jogador.ID WHERE contratos_jogador.tipoContrato = 3 AND FLOOR((DATEDIFF(CURDATE(), jogador.Nascimento))/365) > 20";
            $stmt = $this->conn->prepare($query);
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
        }

        function desconvocarSub18ForaIdade(){
            $query = "DELETE contratos_jogador FROM contratos_jogador  LEFT JOIN jogador  ON contratos_jogador.jogador = jogador.ID WHERE contratos_jogador.tipoContrato = 4 AND FLOOR((DATEDIFF(CURDATE(), jogador.Nascimento))/365) > 18";
            $stmt = $this->conn->prepare($query);
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
        }

        function verificarAposentadoria($idJogador){
            $idJogador = htmlspecialchars(strip_tags($idJogador));

            $query = "SELECT disponibilidade FROM jogador WHERE ID = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idJogador);
            $stmt->execute();
            $result = $stmt->fetchColumn();

            if($result < 0){
                return true;
            } else {
                return false;
            }




        }

        function readExpat($from_record_num, $records_per_page, $user_id){
            $user_id = htmlspecialchars(strip_tags($user_id));

            $query = "SELECT * FROM (
			SELECT tf.ID, tf.Nome, tf.Nascimento, tf.Mentalidade, tf.CobradorFalta, tf.StringPosicoes, tf.valor, tf.Nivel, tf.disponibilidade, tf.idPais, tf.idDonoPais, tf.siglaPais, tf.bandeiraPais, tf.posicaoBase as posicaoBase, tf.titularidade, b.Nome as clubeVinculado, d.Nome as clubeEmprestimo, f.Nome as clubeSelecao, tf.determinacaoOriginal, b.Escudo as escudoClubeVinculado, b.ID as idClubeVinculado, tf.Idade, q.dono as donoPaisClube, tf.modificadorNivel FROM ( SELECT
            a.ID, a.Nome, a.Nascimento, m.Nome as Mentalidade, r.Nome as CobradorFalta, a.StringPosicoes, a.valor, a.Nivel, a.disponibilidade, p.id as idPais, p.dono as idDonoPais, p.sigla as siglaPais, p.bandeira as bandeiraPais, c.clube as clubeVinculado, e.clube as clubeEmprestimo, s.clube as clubeSelecao, c.posicaoBase as posicaoBase, c.titularidade, a.determinacaoOriginal, FLOOR((DATEDIFF(CURDATE(), a.Nascimento))/365) as Idade, c.ModificadorNivel as modificadorNivel
            FROM
                " . $this->table_name . " a
            LEFT JOIN paises p ON a.Pais = p.id
            LEFT JOIN contratos_jogador c ON a.ID = c.jogador AND c.tipoContrato = 0
            LEFT JOIN contratos_jogador e ON a.ID = e.jogador AND e.tipoContrato = 1
            LEFT JOIN contratos_jogador s ON a.ID = s.jogador AND s.tipoContrato = 2
            LEFT JOIN mentalidade m ON a.Mentalidade = m.ID
            LEFT JOIN cobrador r ON a.CobradorFalta = r.ID
            ) tf
            LEFT JOIN clube b ON tf.clubeVinculado = b.id
            LEFT JOIN clube d ON tf.clubeEmprestimo = d.id
            LEFT JOIN clube f ON tf.clubeSelecao = f.id
            LEFT JOIN paises q ON b.Pais = q.id
			) t1 WHERE idDonoPais = ? AND ((donoPaisClube <> idDonoPais AND donoPaisClube <> 0) OR (disponibilidade = -2)) ORDER BY Nome ASC
            LIMIT
                {$from_record_num}, {$records_per_page}";

            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1, $user_id);
            $stmt->execute();

            return $stmt;
        }


        public function countExpat($dono){

            $dono = htmlspecialchars(strip_tags($dono));


            $query =    "SELECT a.id
                        FROM " . $this->table_name . " a
                            LEFT JOIN paises p ON a.pais = p.id
                            LEFT JOIN contratos_jogador c ON c.jogador = a.ID AND c.tipoContrato = 0
                            LEFT JOIN clube b ON c.clube = b.ID
                            LEFT JOIN paises q ON b.Pais = q.id
                            WHERE p.dono = ? AND q.dono <> p.dono AND q.dono <> 0";



            $stmt = $this->conn->prepare( $query );
            $stmt->bindParam(1,$dono);
            $stmt->execute();

            $num = $stmt->rowCount();

            return $num;
           }

           public function testeInatividade($idJogador){
                $idJogador = htmlspecialchars(strip_tags($idJogador));

                $query = "SELECT p.ativo FROM jogador j LEFT JOIN contratos_jogador c ON c.jogador = j.ID AND c.tipoContrato = 0 LEFT JOIN clube b ON c.clube = b.ID LEFT JOIN paises p ON b.Pais = p.id WHERE j.ID = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1,$idJogador);
                $stmt->execute();
                $result = $stmt->fetchColumn();
                if($result == 1){
                    return false;
                } else {
                    return true;
                }
           }

           public function incorporarModificador($idJogador, $novoNivel){
                $idJogador = htmlspecialchars(strip_tags($idJogador));
                $novoNivel = htmlspecialchars(strip_tags($novoNivel));

                $prequery = "SELECT Nivel FROM jogador WHERE ID=:id";
                $stmt = $this->conn->prepare($prequery);
                $stmt->bindParam(":id", $idJogador);
                $stmt->execute();
                $atualNivel = $stmt->fetchColumn();

                $error_count = 0;

                $query = "UPDATE jogador SET Nivel=:nivel WHERE ID=:id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":id", $idJogador);
                $stmt->bindParam(":nivel", $novoNivel);
                if($stmt->execute()){
                } else {
                    $error_count++;
                }

                $difNivel = $atualNivel - $novoNivel;
                $postquery = "UPDATE contratos_jogador SET ModificadorNivel = ModificadorNivel + :diferenca WHERE jogador=:id";
                $stmt = $this->conn->prepare($postquery);
                $stmt->bindParam(":id", $idJogador);
                $stmt->bindParam(":diferenca", $difNivel);
                if($stmt->execute()){
                } else {
                    $error_count++;
                }

                if($error_count > 0){
                    return false;
                } else {
                    return true;
                }


           }


           public function listaMentalidade(){
            $query = "SELECT ID, Nome FROM mentalidade";
            $stmt = $this->conn->prepare($query);

            $stmt->execute();

            return $stmt;
           }
		   

           public function listaCobradorFalta(){
            $query = "SELECT ID, Nome FROM cobrador";
            $stmt = $this->conn->prepare($query);

            $stmt->execute();

            return $stmt;
           }



        function verificarDonoTimeVinculado($idJogador){
            $idJogador = htmlspecialchars(strip_tags($idJogador));

            $query = "SELECT p.dono FROM jogador j LEFT JOIN contratos_jogador c ON c.jogador = j.ID AND c.tipoContrato = 0 LEFT JOIN clube b ON b.ID = c.clube LEFT JOIN paises p ON b.Pais = p.id WHERE j.ID = ? LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1,$idJogador);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $idDono = $row['dono'];

            return $idDono;

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

        function randomPlayer($codigoPosicao, $nacionalidade, $origemNomes, $origemSobrenomes,$idadeMin,$idadeMax,$nivelMin,$nivelMax,$nivelMed,$idadeMed,$ocorrenciaNomeDuplo, $indiceMiscigenacao, $sexo){

            if($idadeMin == 0){
                $idadeMin = 18;
            }

            if($idadeMax == 0){
                $idadeMax = 36;
            }

            if($nivelMin == 0){
                $nivelMin = 30;
            }

            if($nivelMax == 0){
                $nivelMax = 90;
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

            if($nivelMin > $nivelMax){
                $auxNivel = $nivelMax;
                $idadeMax = $idadeMin;
                $idadeMin = $auxNivel;
            }

            if($idadeMin > $idadeMax){

            }

            $idadeMed = round((($idadeMin + $idadeMax)/2),0);

            $this->nivel = $this->sorteia($nivelMin,$nivelMax,$nivelMed);
            $idade = $this->sorteia($idadeMin,$idadeMax,$idadeMed);
            $this->determinacao = $this->sorteia(1,5);
            $this->cobradorFalta = ($this->sorteia(0,3,-1) == 0 ? 0 : $this->sorteia(1,3));
            $this->mentalidade = $this->sorteia(1,8);
            $this->nascimento = $this->aniversario_reverso($idade);

            if($codigoPosicao == null){
                $codigoPosicao = $this->sorteia(1,15);
            }

            if($codigoPosicao >= 2 && $codigoPosicao <= 4){
                //zagueiro

                //outras posicoes - zagueiro, lateral, ala, volante
                $segundaPosicao = ($this->sorteia(0,3,-1) == 0 ? 0 : $this->sorteia(2,7));
                $terceiraPosicao = ($this->sorteia(0,3,-1) == 0 ? 0 : $this->sorteia(2,7));

                $arrayPos = [$codigoPosicao, $segundaPosicao, $terceiraPosicao];
                $arrayPos = array_unique($arrayPos,SORT_NUMERIC);
                $arrayPos = array_filter($arrayPos);

                //atributos - foco em marcacao, desarme, cabeceamento, forca
                $this->marcacao = $this->sorteia(1,7,8);
                $this->desarme = $this->sorteia(1,7,8);
                $this->cabeceamento = $this->sorteia(1,7,7);
                $this->forca = $this->sorteia(1,5,4);
                $this->velocidade = $this->sorteia(1,5,2);
                $this->visaoJogo = $this->sorteia(1,7,2);
                $this->controleBola = $this->sorteia(1,7,3);
                $this->tecnica = $this->sorteia(1,7,1);
                $this->faroGol = $this->sorteia(1,7,2);
                $this->finalizacao = $this->sorteia(1,7,2);
                $this->cruzamentos = $this->sorteia(1,7,1);
                $this->movimentacao = $this->sorteia(1,7,3);
                $this->reflexos = 0;
                $this->seguranca = 0;
                $this->saidas = 0;
                $this->defesaPenaltis = 0;
                $this->lancamentos = 0;
                $this->jogoAereo = 0;

            } else if($codigoPosicao == 13 || $codigoPosicao == 7 || $codigoPosicao == 10){
                //meia
                //outras posicoes - volante, meia central, meia-lateral, meia-atacante
                $segundaPosicao = ($this->sorteia(0,3,-1) == 0 ? 0 : $this->sorteia(7,13));
                $terceiraPosicao = ($this->sorteia(0,3,-1) == 0 ? 0 : $this->sorteia(7,13));

                $arrayPos = [$codigoPosicao, $segundaPosicao, $terceiraPosicao];
                $arrayPos = array_unique($arrayPos,SORT_NUMERIC);
                $arrayPos = array_filter($arrayPos);

                //atributos - foco em visao de jogo, movimentacao, tecnica, controle bola
                $this->marcacao = $this->sorteia(1,7,3);
                $this->desarme = $this->sorteia(1,7,3);
                $this->cabeceamento = $this->sorteia(1,7,2);
                $this->forca = $this->sorteia(1,5,2);
                $this->velocidade = $this->sorteia(1,5,3);
                $this->visaoJogo = $this->sorteia(1,7,7);
                $this->controleBola = $this->sorteia(1,7,7);
                $this->tecnica = $this->sorteia(1,7,6);
                $this->faroGol = $this->sorteia(1,7,3);
                $this->finalizacao = $this->sorteia(1,7,3);
                $this->cruzamentos = $this->sorteia(1,7,4);
                $this->movimentacao = $this->sorteia(1,7,4);
                $this->reflexos = 0;
                $this->seguranca = 0;
                $this->saidas = 0;
                $this->defesaPenaltis = 0;
                $this->lancamentos = 0;
                $this->jogoAereo = 0;

            } else if($codigoPosicao == 1){
                //goleiro
                $arrayPos = $codigoPosicao;

                //atributos - atributos proprios
                $this->marcacao = 0;
                $this->desarme = 0;
                $this->cabeceamento = 0;
                $this->forca = 0;
                $this->velocidade = 0;
                $this->visaoJogo = 0;
                $this->controleBola = 0;
                $this->tecnica = 0;
                $this->faroGol = 0;
                $this->finalizacao = 0;
                $this->cruzamentos = 0;
                $this->movimentacao = 0;
                $this->reflexos = $this->sorteia(1,10,5);
                $this->seguranca = $this->sorteia(1,10,5);
                $this->saidas = $this->sorteia(1,10,5);
                $this->defesaPenaltis = $this->sorteia(1,10,5);
                $this->lancamentos = $this->sorteia(1,10,5);
                $this->jogoAereo = $this->sorteia(1,10,5);

            } else if($codigoPosicao == 5 || $codigoPosicao == 6 || $codigoPosicao == 8 || $codigoPosicao == 9) {
                //lateral
                //outras posicoes - todas que envolvem lateral avançado, mais volante
                $segundaPosicao = ($this->sorteia(0,3,-1) == 0 ? 0 : $this->sorteia(5,9));
                $terceiraPosicao = ($this->sorteia(0,3,-1) == 0 ? 0 : $this->sorteia(5,9));

                $arrayPos = [$codigoPosicao, $segundaPosicao, $terceiraPosicao];
                $arrayPos = array_unique($arrayPos,SORT_NUMERIC);
                $arrayPos = array_filter($arrayPos);

                //atributos - foco em desarme, movimentacao, cruzamento, controle bola, velocidade
                $this->marcacao = $this->sorteia(1,7,3);
                $this->desarme = $this->sorteia(1,7,5);
                $this->cabeceamento = $this->sorteia(1,7,2);
                $this->forca = $this->sorteia(1,5,2);
                $this->velocidade = $this->sorteia(1,5,6);
                $this->visaoJogo = $this->sorteia(1,7,7);
                $this->controleBola = $this->sorteia(1,7,7);
                $this->tecnica = $this->sorteia(1,7,2);
                $this->faroGol = $this->sorteia(1,7,2);
                $this->finalizacao = $this->sorteia(1,7,3);
                $this->cruzamentos = $this->sorteia(1,7,7);
                $this->movimentacao = $this->sorteia(1,7,7);
                $this->reflexos = 0;
                $this->seguranca = 0;
                $this->saidas = 0;
                $this->defesaPenaltis = 0;
                $this->lancamentos = 0;
                $this->jogoAereo = 0;

            } else {
                //atacante
                //outras posicoes - volante, meia central, meia-lateral, meia-atacante
                $segundaPosicao = ($this->sorteia(0,3,-1) == 0 ? 0 : $this->sorteia(11,15));
                $terceiraPosicao = ($this->sorteia(0,3,-1) == 0 ? 0 : $this->sorteia(11,15));

                $arrayPos = [$codigoPosicao, $segundaPosicao, $terceiraPosicao];
                $arrayPos = array_unique($arrayPos,SORT_NUMERIC);
                $arrayPos = array_filter($arrayPos);

                //atributos - foco em finalizacao, faro de gol, tecnica, cabeceamento
                $this->marcacao = $this->sorteia(1,7,1);
                $this->desarme = $this->sorteia(1,7,1);
                $this->cabeceamento = $this->sorteia(1,7,7);
                $this->forca = $this->sorteia(1,5,3);
                $this->velocidade = $this->sorteia(1,5,3);
                $this->visaoJogo = $this->sorteia(1,7,3);
                $this->controleBola = $this->sorteia(1,7,4);
                $this->tecnica = $this->sorteia(1,7,4);
                $this->faroGol = $this->sorteia(1,7,8);
                $this->finalizacao = $this->sorteia(1,7,7);
                $this->cruzamentos = $this->sorteia(1,7,3);
                $this->movimentacao = $this->sorteia(1,7,4);
                $this->reflexos = 0;
                $this->seguranca = 0;
                $this->saidas = 0;
                $this->defesaPenaltis = 0;
                $this->lancamentos = 0;
                $this->jogoAereo = 0;

            }

            if(!is_array($arrayPos)){
                $valuePos = $arrayPos;
                $arrayPos = array();
                $arrayPos[] = $valuePos;
            }

            $stringPosicoes = "";

            for($i = 0;$i<15;$i++){
                $codigo = $i+1;
                if(array_search($codigo,$arrayPos) !== false){
                    $stringPosicoes .= "1";
                } else {
                    $stringPosicoes .= "0";
                }
            }

            $this->stringPosicoes = $stringPosicoes;
            $this->valor = $this->calcularPasse();

            $this->pais = $nacionalidade;

            //nome e sobrenome
            //chance de nome duplo
            $chanceNomeDuplo = mt_rand(1,100);
            if($chanceNomeDuplo <= $ocorrenciaNomeDuplo){
                $nomeDuplo = true;
            } else {
                $nomeDuplo = false;
            }

            //chance de miscigenacao
            $chanceMiscigenacao = mt_rand(1,100);
            if($chanceMiscigenacao <= $indiceMiscigenacao){
                $this->nomeJogador = $this->geranomes($origemNomes, $origemSobrenomes, $nomeDuplo, $sexo);
            } else {
                $this->nomeJogador = $this->geranomes($origemNomes, $origemNomes, $nomeDuplo, $sexo);
            }



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


        function convocar($idJogador, $selecaoDestino,$tipoContrato){
            $idJogador = htmlspecialchars(strip_tags($idJogador));
            $selecaoDestino = htmlspecialchars(strip_tags($selecaoDestino));
            $tipoContrato = htmlspecialchars(strip_tags($tipoContrato));

            if($tipoContrato == 0){
                $tipoContrato = 1;
            }

            $query = "INSERT INTO contratos_jogador (jogador, clube, posicaoBase, titularidade, capitao, cobrancaPenalti, modificadorNivel, encerramento, salario, tipoContrato) VALUES (?, ?, 0, -1, 0, 0, 0, 0, 0, ?) ON DUPLICATE KEY UPDATE jogador = jogador";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $idJogador);
            $stmt->bindParam(2, $selecaoDestino);
            $stmt->bindParam(3, $tipoContrato);
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }

        }


        function modificarPlanilhaImportada($logged_user, $player_index, $player_name, $player_birth, $player_origin, $player_level, $player_ment, $player_fk, $player_det, $player_pos){
          if($player_index == 0){
            return false;
          }

          if($player_index == ""){
            return false;
          }

          if($logged_user == 0){
            return false;
          }

          $player_index = htmlspecialchars(strip_tags($player_index));
          $player_name = htmlspecialchars(strip_tags($player_name));
          $player_birth = htmlspecialchars(strip_tags($player_birth));
          $player_origin = htmlspecialchars(strip_tags($player_origin));
          $player_level = htmlspecialchars(strip_tags($player_level));
          $player_ment = htmlspecialchars(strip_tags($player_ment));
          $player_fk = htmlspecialchars(strip_tags($player_fk));
          $player_det = htmlspecialchars(strip_tags($player_det));
          $player_pos = htmlspecialchars(strip_tags($player_pos));

          if($player_level > 100){
            $player_level = 100;
          }

          if($player_level < 1){
            $player_level = 1;
          }

          if($player_ment > 8){
            $player_ment = 8;
          }

          if($player_ment < 1){
            $player_ment = 1;
          }

          if($player_fk > 3){
            $player_fk = 3;
          }

          if($player_fk < 0){
            $player_fk = 0;
          }

          if($player_det > 5){
            $player_det = 5;
          }

          if($player_det < 1){
            $player_det = 1;
          }

          if($player_pos[0] == 1){
            $player_pos = "100000000000000";
          }

          $queryOrigem = "SELECT id FROM paises WHERE bandeira = :bandeira LIMIT 0, 1";
          $stmt = $this->conn->prepare($queryOrigem);
          $stmt->bindParam(":bandeira", $player_origin);
          $stmt->execute();
          $result = $stmt->fetchColumn();

          $queryCheck = "SELECT CASE WHEN p.dono = :lp AND q.dono = :lp2 THEN 1 WHEN q.dono = :lp3 THEN 0 ELSE -1 END FROM jogador j LEFT JOIN paises p ON p.id = j.Pais LEFT JOIN contratos_jogador c ON c.jogador = j.ID AND c.tipoContrato = 0 LEFT JOIN clube b ON b.ID = c.clube LEFT JOIN paises q ON q.id = b.Pais WHERE j.ID = :id";
          $stmt = $this->conn->prepare($queryCheck);
          $stmt->bindParam(":id", $player_index);
          $stmt->bindParam(":lp", $logged_user);
          $stmt->bindParam(":lp2", $logged_user);
          $stmt->bindParam(":lp3", $logged_user);
          $stmt->execute();
          $checkResult = $stmt->fetchColumn();

          if($checkResult == 1){
            $mainQuery = "UPDATE jogador SET Nome = :nome, Nascimento = :nascimento, CobradorFalta = :cobradorFalta, StringPosicoes = :stringPosicoes, Nivel = :nivel, Mentalidade = :mentalidade, Determinacao = :determinacao, DeterminacaoOriginal = :determinacaoOriginal, Pais = :pais WHERE ID = :id";
            $stmt = $this->conn->prepare($mainQuery);
            $stmt->bindParam(":nome", $player_name);
            $stmt->bindParam(":nascimento", $player_birth);
            $stmt->bindParam(":cobradorFalta", $player_fk);
            $stmt->bindParam(":stringPosicoes", $player_pos);
            $stmt->bindParam(":nivel", $player_level);
            $stmt->bindParam(":mentalidade", $player_ment);
            $stmt->bindParam(":determinacao", $player_det);
            $stmt->bindParam(":determinacaoOriginal", $player_det);
            $stmt->bindParam(":pais", $result);
            $stmt->bindParam(":id", $player_index);
            if($stmt->execute()){
              return true;
            } else {
              return false;
            }

          } else if($checkResult == 0){
            $mainQuery = "UPDATE jogador j LEFT JOIN contratos_jogador c ON c.jogador = j.ID AND c.tipoContrato = 0 SET CobradorFalta = :cobradorFalta, StringPosicoes = :stringPosicoes, c.ModificadorNivel = :nivel - j.Nivel, Mentalidade = :mentalidade, Determinacao = :determinacao, DeterminacaoOriginal = :determinacaoOriginal, Pais = :pais WHERE j.ID = :id";
            $stmt = $this->conn->prepare($mainQuery);
            $stmt->bindParam(":cobradorFalta", $player_fk);
            $stmt->bindParam(":stringPosicoes", $player_pos);
            $stmt->bindParam(":nivel", $player_level);
            $stmt->bindParam(":mentalidade", $player_ment);
            $stmt->bindParam(":determinacao", $player_det);
            $stmt->bindParam(":determinacaoOriginal", $player_det);
            $stmt->bindParam(":pais", $result);
            $stmt->bindParam(":id", $player_index);
            if($stmt->execute()){
              return true;
            } else {
              return false;
            }
          } else {
            return false;
          }


        }


        function randomProgressao(){
          $random_num = mt_rand();
          $progressao = 8;
          if($random_num < 0.04){
            $progressao = 1;
          } else if($random_num <0.08){
            $progressao = 2;
          } else if($random_num <0.12){
            $progressao = 3;
          } else if($random_num <0.16){
            $progressao = 4;
          } else if($random_num <0.18){
            $progressao = 5;
          } else if($random_num < 0.28){
            $progressao = 6;
          } else if($random_num < 0.38){
            $progressao = 7;
          }

          return $progressao;
        }



            function donoJogador($idJogador){
                $idJogador = htmlspecialchars(strip_tags($idJogador));

                    $queryJogador = "SELECT COUNT(p.dono) as checkJogador, p.dono as donoJogador FROM paises p
                    LEFT JOIN jogador j ON j.Pais = p.id
                    WHERE j.ID = ?";
                    $stmtJogador = $this->conn->prepare($queryJogador);
                    $stmtJogador->bindParam(1,$idJogador);
                    $stmtJogador->execute();
                    $rowJogador = $stmtJogador->fetch(PDO::FETCH_ASSOC);
                    $checkJogador = $rowJogador['checkJogador'];

                    if($checkJogador != 0){
                        $idDono = $rowJogador['donoJogador'];
                        return $idDono;
                    } else {
                        return 0;
                    }


            }

            function possivelApagar($idJogador){
                $idJogador = htmlspecialchars(strip_tags($idJogador));

                $query = "SELECT jogador FROM contratos_jogador WHERE jogador = :jogador LIMIT 0,1;";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":jogador", $idJogador);
                $stmt->execute();
                $result = $stmt->fetchColumn();
                if($result == $idJogador){
                  return false;
                } else {

                  $new_query = "SELECT jogador FROM transferencias WHERE jogador = :jogador AND (clubeOrigem * clubeDestino) <> 0 LIMIT 0,1;";
                  $new_stmt = $this->conn->prepare($new_query);
                  $new_stmt->bindParam(":jogador", $idJogador);
                  $new_stmt->execute();
                  $new_result = $new_stmt->fetchColumn();
                  if($new_result == $idJogador){
                    return false;
                  } else {
                    return true;
                  }
                }
            }

            //apagar árbitro
            function apagar($idApagar){
                $idApagar = htmlspecialchars(strip_tags($idApagar));
                $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
                $stmt = $this->conn->prepare( $query );
                $stmt->bindParam(1, $idApagar);
                if($stmt->execute()){
                  $query = "DELETE FROM transferencias WHERE jogador = ?";
                  $stmt = $this->conn->prepare( $query );
                  $stmt->bindParam(1, $idApagar);
                  if($stmt->execute()){
                      return true;
                  } else {
                      return false;
                  }
                } else {
                    return false;
                }

            }


 public function idPorNomePais($nomeJogador, $idPais, $tempId){
   $query = "SELECT nome, id, pais FROM ". $this->table_name . " WHERE id= ?";
   $stmt = $this->conn->prepare($query);
   $stmt->bindParam(1, $tempId);
   $stmt->execute();
   $result = $stmt->fetch(PDO::FETCH_ASSOC);
   similar_text($result['nome'], $nomeJogador, $perc);
   if($result['pais'] == $idPais && $perc > 80){
     return $result['id'];
   } else {
     $newquery = "SELECT nome, id FROM ". $this->table_name . " WHERE pais = ? AND nome LIKE ?";
     $newstmt = $this->conn->prepare($newquery);
     $newstmt->bindParam(1, $idPais);
     $nomeLike = "%". $nomeJogador . "%";
     $newstmt->bindParam(2, $nomeLike);
     $newstmt->execute();
     $highest_perc = 0;
     $likely_id = 0;
     while($newresult = $newstmt->fetch(PDO::FETCH_ASSOC)){
       similar_text($newresult['nome'], $nomeJogador, $temp_perc);
       if($temp_perc > 70 && $temp_perc > $highest_perc){
         $highest_perc = $temp_perc;
         $likely_id = $newresult['id'];
       }
     }
     return $likely_id;
   }
 }

 public function coletarJogadoresTime($idTime){
   $idTime = htmlspecialchars(strip_tags($idTime));

   $query = "SELECT a.ID as idJogador, a.Nome as nomeJogador, FLOOR((DATEDIFF(CURDATE(), Nascimento))/365) as Idade, Mentalidade, CobradorFalta, StringPosicoes, (Nivel + c.ModificadorNivel) as Nivel, Marcacao, Desarme, VisaoJogo, Movimentacao, Cruzamentos, Cabeceamento, Tecnica, ControleBola, Finalizacao, FaroGol, Velocidade, Forca, Reflexos, Seguranca, Saidas, JogoAereo, Lancamentos, DefesaPenaltis, Determinacao, DeterminacaoOriginal, p.bandeira as Nacionalidade, posicoes.Sigla as siglaPosicao, c.capitao, c.cobrancaPenalti FROM jogador a LEFT JOIN paises p ON a.Pais = p.id LEFT JOIN contratos_jogador c ON c.jogador = a.ID LEFT JOIN posicoes ON posicoes.ID = c.posicaoBase WHERE c.clube = ? ORDER BY c.titularidade DESC, c.posicaoBase ASC";
   $stmt = $this->conn->prepare( $query );
   $stmt->bindParam(1, $idTime);
   $stmt->execute();

   return $stmt->fetchAll(PDO::FETCH_ASSOC);

 }

 public function coletarInformacoesJogador($idJogador, $idTime){
   $idJogador = htmlspecialchars(strip_tags($idJogador));
   $idTime = htmlspecialchars(strip_tags($idTime));

   $query = "SELECT a.ID as idJogador, a.Nome as nomeJogador, FLOOR((DATEDIFF(CURDATE(), Nascimento))/365) as Idade, Mentalidade, CobradorFalta, StringPosicoes, (Nivel + c.ModificadorNivel) as Nivel, Marcacao, Desarme, VisaoJogo, Movimentacao, Cruzamentos, Cabeceamento, Tecnica, ControleBola, Finalizacao, FaroGol, Velocidade, Forca, Reflexos, Seguranca, Saidas, JogoAereo, Lancamentos, DefesaPenaltis, Determinacao, DeterminacaoOriginal, p.bandeira as Nacionalidade FROM jogador a LEFT JOIN paises p ON a.Pais = p.id LEFT JOIN contratos_jogador c ON c.jogador = a.ID AND c.clube = ? WHERE c.jogador = ?";
   $stmt = $this->conn->prepare( $query );
   $stmt->bindParam(1, $idTime);
   $stmt->bindParam(2, $idJogador);

   $stmt->execute();

   return $stmt->fetchAll(PDO::FETCH_ASSOC);

 }

 public function enviarEmailProposta($idJogador, $clubeOrigem, $clubeDestino, $idTransferencia){
    $idJogador = htmlspecialchars(strip_tags($idJogador));
    $clubeOrigem = htmlspecialchars(strip_tags($clubeOrigem));
    $clubeDestino = htmlspecialchars(strip_tags($clubeDestino));
    $idTransferencia = htmlspecialchars(strip_tags($idTransferencia));

    if($clubeOrigem){
      $query = "SELECT usuarios.email FROM clube LEFT JOIN paises ON paises.id = clube.pais LEFT JOIN usuarios ON usuarios.id = paises.dono WHERE clube.id = ?";
    } else{
      $query = "SELECT usuarios.email FROM jogador LEFT JOIN paises ON paises.id = jogador.Pais LEFT JOIN usuarios ON usuarios.id = paises.dono WHERE jogador.id = ?";
    }
    $stmt = $this->conn->prepare($query);
    if($clubeOrigem){
      $stmt->bindParam(1,$clubeOrigem);
    } else{
      $stmt->bindParam(1,$idJogador);
    }
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    //return $result;

    $to = $result['email'];
    //$to = "lhsaia@gmail.com";
    $from = "no-reply@confusa.top";

    // informações jogador
        $query = "SELECT Nome FROM jogador WHERE ID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$idJogador);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nomeJogador = $result["Nome"];
    
    // informações clube destino
        $query = "SELECT Nome, Escudo FROM clube WHERE ID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$clubeDestino);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nomeClube = $result["Nome"];
        $escudoClube = $result["Escudo"];
        $extEscudoClube = substr($escudoClube, -3, 3);
        $img = file_get_contents("https://confusa.top/images/escudos/" . urlencode($escudoClube)); 
        $data = base64_encode($img); 
        
    // informações transferência
        $query = "SELECT valor, emprestimo, encerramento FROM transferencias WHERE ID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1,$idTransferencia);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $valor = number_format($result["valor"], 0, ',', ' ');
        $emprestimo = $result["emprestimo"];
        $encerramento = $result["encerramento"];
        
        if($emprestimo){
            $tipoTransferencia = "empréstimo";
        } else {
            $tipoTransferencia = "venda";
        }
        
        if($encerramento == "0000-00-00"){
            $finalPeriodo = " por tempo indeterminado";
        } else {
            $finalPeriodo = " até " . substr($encerramento, -2,2) . "/" . substr($encerramento, -5,2) . "/" . substr($encerramento, 0,4);
        }
 

    $subject_old = "Você recebeu uma proposta de transferência no CONFUSA.TOP ";
    $subject = "[CONFUSA.top] " . $nomeClube . " fez uma proposta por " . $nomeJogador;
    $body_old = "Foi feita uma nova proposta de transferência para um jogador sob seu controle, acesse o portal para negociar.";
    $body = "<div style='text-align:center;' width='100%'><img align='middle' height='60' src='https://confusa.top/images/escudos/" . urlencode($escudoClube). "'/><div><br/>O clube "  . $nomeClube . " fez uma proposta de " . $tipoTransferencia . " por " .$nomeJogador . " no valor de F$" . $valor . $finalPeriodo . ". <br/> Acesse o portal para aceitar, rejeitar ou realizar uma contra proposta." ; 
    
    //<img height='30' width='30' src='data:image/" . $extEscudoClube . ";base64," .$data . "'/>
    
    $html_content = ' 
    <html> 
    <head> 
    </head> 
    <body> 
        <h1 align="center">Proposta de ' . $tipoTransferencia.'</h1> 
        ' .$body .'
    </body> 
    </html>';
    
    // Set content-type header for sending HTML email 
    $headers = "MIME-Version: 1.0" . "\r\n"; 
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n"; 

    $headers .= "From: " . $from . "\r\n";

    if(mail($to, $subject, $html_content, $headers, "-f " . $from)){
      return true;
    } else {
      return false;
    }

 }

// resolver contratos de empréstimo ou tempo determinado
public function resolverContratosTempo(){
                $query = "DELETE FROM contratos_jogador WHERE tipoContrato = 0 AND NOT encerramento = '0000-00-00' AND encerramento < CURDATE() AND clubeVinculado = 0";
            $stmt = $this->conn->prepare($query);
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
}

public function resolverEmprestimos(){
                $query = "UPDATE contratos_jogador SET encerramento = '0000-00-00', clube = clubeVinculado, clubeVinculado = 0, titularidade = -1, capitao = 0, cobrancaPenalti = 0 WHERE tipoContrato = 0 AND NOT encerramento = '0000-00-00' AND encerramento < CURDATE() AND NOT clubeVinculado = 0";
            $stmt = $this->conn->prepare($query);
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
}


    function readAllAjax($item_pesquisado, $dono = null){
		$item_pesquisado = htmlspecialchars(strip_tags($item_pesquisado));
		$dono = htmlspecialchars(strip_tags($dono));

        //ver se é por dono ou geral
        if($dono === null){
            $sub_query_inicio = "SELECT * FROM (";
            $sub_query_fim = ") t1 WHERE (Nome LIKE ?) LIMIT 150";
        } else {
            $sub_query_inicio = "SELECT * FROM (";
            $sub_query_fim = ") t1 WHERE idDonoPais = ? AND (Nome LIKE ?) ORDER BY Nome ASC LIMIT 150";

        } 

    $query = $sub_query_inicio."SELECT tf.ID, tf.Nome, tf.Nascimento, tf.Mentalidade, tf.CobradorFalta, tf.StringPosicoes, tf.valor, tf.Nivel, tf.disponibilidade, tf.idPais, tf.idDonoPais, tf.siglaPais, tf.bandeiraPais, tf.posicaoBase as posicaoBase, tf.titularidade, b.Nome as clubeVinculado, d.Nome as clubeEmprestimo, f.Nome as clubeSelecao, tf.determinacaoOriginal, tf.sexo, b.Escudo as escudoClubeVinculado, b.ID as idClubeVinculado, tf.Idade, q.dono as donoClubeVinculado, tf.foto FROM ( SELECT
            a.ID, a.Nome, a.Nascimento, m.Nome as Mentalidade, r.Nome as CobradorFalta, a.StringPosicoes, a.valor, a.Nivel, a.disponibilidade, p.id as idPais, p.dono as idDonoPais, p.sigla as siglaPais, p.bandeira as bandeiraPais, c.clube as clubeVinculado, e.clube as clubeEmprestimo, s.clube as clubeSelecao, c.posicaoBase as posicaoBase, c.titularidade, a.Sexo as sexo, a.determinacaoOriginal, FLOOR((DATEDIFF(CURDATE(), a.Nascimento))/365) as Idade, foto 
        FROM
            " . $this->table_name . " a
        LEFT JOIN paises p ON a.Pais = p.id
        LEFT JOIN contratos_jogador c ON a.ID = c.jogador AND c.tipoContrato = 0
        LEFT JOIN contratos_jogador e ON a.ID = e.jogador AND e.tipoContrato = 1
        LEFT JOIN contratos_jogador s ON a.ID = s.jogador AND s.tipoContrato = 2
        LEFT JOIN mentalidade m ON a.Mentalidade = m.ID
        LEFT JOIN cobrador r ON a.CobradorFalta = r.ID
          ) tf
        LEFT JOIN clube b ON tf.clubeVinculado = b.id
        LEFT JOIN clube d ON tf.clubeEmprestimo = d.id
        LEFT JOIN clube f ON tf.clubeSelecao = f.id
        LEFT JOIN paises q ON b.Pais = q.id
        ORDER BY
            tf.Nome ASC ".$sub_query_fim;
			
	$stmt = $this->conn->prepare( $query );
	$item_pesquisado = "%" . $item_pesquisado . "%";
		
	if($dono === null){
		$stmt->bindParam(1, $item_pesquisado);
	} else {
		$stmt->bindParam(1, $dono);
		$stmt->bindParam(2, $item_pesquisado);
	} 

	$stmt->execute();

	return $stmt;



}

        function alterarContrato($idJogador,$idClube, $encerramentoNovo){

        //verificar tipo transferencia
                $query_origem = "SELECT tipoContrato, clubeVinculado FROM contratos_jogador WHERE jogador=:jogador AND clube=:clube";

                $stmt = $this->conn->prepare( $query_origem );
                $stmt->bindParam(":jogador", $idJogador);
                $stmt->bindParam(":clube", $idClube);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if($row == false){
                    $tipoContrato = 0;
                    $clubeVinculado = 0;
                } else {
                    $tipoContrato = $row['tipoContrato'];
                    $clubeVinculado = $row['clubeVinculado'];
                }

                $error_count = 0;
				
		
                $query_contrato = "UPDATE contratos_jogador SET encerramento = :encerramento   
                        WHERE
                            jogador=:jogador AND clube=:clube";
                $stmt = $this->conn->prepare( $query_contrato );
				$stmt->bindParam(":encerramento", $encerramentoNovo);
                $stmt->bindParam(":jogador", $idJogador);
                $stmt->bindParam(":clube", $idClube);
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
		
		        function alterarInicioContrato($idJogador,$idClube, $inicioNovo){

                $query_contrato = "UPDATE transferencias SET data = :inicio   
                        WHERE
                            jogador=:jogador AND clubeDestino=:clube AND clubeOrigem=0 AND status_execucao = 1";
                $stmt = $this->conn->prepare( $query_contrato );
				$stmt->bindParam(":inicio", $inicioNovo);
                $stmt->bindParam(":jogador", $idJogador);
                $stmt->bindParam(":clube", $idClube);
                if($stmt->execute()){
					return true;
                } else {
                    return false;
                }
            

        }

}
?>
