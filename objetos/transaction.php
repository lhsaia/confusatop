<?php
class Transaction{

    // conexão de banco de dados e nome da tabela
    private $conn;
    private $table_name = "financeiro";

    // object properties
    public $id;
	public $timestamp;
    public $transaction_type;
	public $cash_flow;
	public $value;
	public $comment;
	public $team;
	

    public function __construct($db){
        $this->conn = $db;
    }
	
	public function getOptions(){
		$query = "SELECT id, nome, icone FROM " . $this->table_name . "_opcoes";
		$stmt = $this->conn->prepare( $query );
		$stmt->bindParam(1, $idClube);
		$stmt->execute();
		return $stmt;
	}
	
	public function retrieveTransactions($teamId, $transactionType, $startDate = null, $endDate = null){
		$teamId = htmlspecialchars(strip_tags($teamId));
		$transactionType = htmlspecialchars(strip_tags($transactionType));
		$startDate = htmlspecialchars(strip_tags($startDate));
		$endDate = htmlspecialchars(strip_tags($endDate));
		
		$filters = "";
		$in_filters = "";
		$out_filters = "";
		$tr_filters ="";
		
		if($transactionType != 0){
			$tr_filters .= " WHERE tipo_movimentacao = :movType ";
		} 
		
		if($startDate != null){
			$filters .= " AND data >:startDate ";
			$in_filters .= " AND data > :startDate2 ";
			$out_filters .= " AND data > :startDate3 ";
		} 
		
		if($endDate != null){
			$filters .= " AND data <:endDate ";
			$in_filters .= " AND data < :endDate2 ";
			$out_filters .= " AND data < :endDate3 ";
		} 
		
		$query = "SELECT *, f.nome FROM (SELECT * FROM (SELECT financeiro.id as trans_id, data, tipo_movimentacao, fluxo_caixa, valor, comentario, 'transactions' as tableFrom FROM " . $this->table_name . " WHERE time =:idTime " . $filters .
		" UNION 
		SELECT transferencias.ID as trans_id, data, '1' as tipo_movimentacao, '1' as fluxo_caixa, transferencias.valor  , CONCAT('Transferência de ', jogador.Nome, ' (', clube.Nome, '-', paises.sigla , ')') as comentario, 'transferences_in' as tableFrom FROM transferencias 
		LEFT JOIN clube ON clube.ID = transferencias.clubeDestino 
		LEFT JOIN jogador ON jogador.ID = transferencias.jogador
		LEFT JOIN paises ON paises.ID = clube.pais 
		WHERE clubeDestino <> '0' AND  clubeOrigem = :idTime2 " . $in_filters . 
		" UNION 
		SELECT transferencias.ID as trans_id, data, '1' as tipo_movimentacao, '0' as fluxo_caixa, transferencias.valor * -1, CONCAT('Transferência de ', jogador.Nome, ' (', clube.Nome, '-', paises.sigla , ')') as comentario, 'transferences_out' as tableFrom FROM transferencias 
		LEFT JOIN clube ON clube.ID = transferencias.clubeOrigem
		LEFT JOIN jogador ON jogador.ID = transferencias.jogador
		LEFT JOIN paises ON paises.ID = clube.pais 
		WHERE clubeOrigem <> '0' AND clubeDestino = :idTime3 " . $out_filters .") t1 " . $tr_filters .") t2 LEFT JOIN financeiro_opcoes f ON f.id = t2.tipo_movimentacao ORDER BY data";
		
		$stmt = $this->conn->prepare( $query );
		$stmt->bindParam(":idTime", $teamId);
		$stmt->bindParam(":idTime2", $teamId);
		$stmt->bindParam(":idTime3", $teamId);
		
		if($transactionType != 0){
			$stmt->bindParam(":movType", $transactionType);
		} 
		
		if($startDate != null){
			$stmt->bindParam(":startDate", $startDate);
			$stmt->bindParam(":startDate2", $startDate);
			$stmt->bindParam(":startDate3", $startDate);
		} 
		
		if($endDate != null){
			$stmt->bindParam(":endDate", $endDate);
			$stmt->bindParam(":endDate2", $endDate);
			$stmt->bindParam(":endDate3", $endDate);
		} 
		
		$stmt->execute();
		
		return $stmt;
	}
	
	    function create(){

        //escrever query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    data=:data, tipo_movimentacao=:tipo_movimentacao, fluxo_caixa=:fluxo_caixa, valor=:valor, time=:time, comentario=:comentario";

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->timestamp=htmlspecialchars(strip_tags($this->timestamp));
        $this->transaction_type=htmlspecialchars(strip_tags($this->transaction_type));
        $this->cash_flow=htmlspecialchars(strip_tags($this->cash_flow));
        $this->value=htmlspecialchars(strip_tags($this->value));
        $this->comment=htmlspecialchars(strip_tags($this->comment));
        $this->team=htmlspecialchars(strip_tags($this->team));
        

        // bind values
        $stmt->bindParam(":data", $this->timestamp);
        $stmt->bindParam(":tipo_movimentacao", $this->transaction_type);
        $stmt->bindParam(":fluxo_caixa", $this->cash_flow);
        $stmt->bindParam(":valor", $this->value);
        $stmt->bindParam(":time", $this->team);
        $stmt->bindParam(":comentario", $this->comment);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }

    }
	
	    //apagar transação
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
}
?>
