<?php
class Transfer {

    private $conn;

    public $origemId;
    public $destinoId;

    public $jogadorNome;
    public $fotoJogador;
    public $bandeiraPng;

    public $origemNome;
    public $origemEscudo;

    public $destinoNome;
    public $destinoEscudo;

    public $valor;
    public $tipo;
    public $data;

    public function __construct($db){
        $this->conn = $db;
    }

    public function carregarPorId(int $id): bool {

        $query = "
            SELECT
                j.Nome AS jogador_nome,
                j.foto AS jogador_foto,
                p.bandeira AS bandeira_png,

                t.clubeOrigem AS clube_origem_id,
                co.Nome AS origem_nome,
                co.Escudo AS origem_escudo,

                t.clubeDestino AS clube_destino_id,
                cd.Nome AS destino_nome,
                cd.Escudo AS destino_escudo,

                t.valor,
                t.tipoTransferencia as tipo,
                t.emprestimo,
                t.dataConclusao as data_confirmacao
            FROM transferencias t
            JOIN jogador j ON j.ID = t.jogador
            JOIN paises p ON p.id = j.Pais
            JOIN clube co ON co.ID = t.clubeOrigem
            JOIN clube cd ON cd.ID = t.clubeDestino
            WHERE t.ID = ?
              AND t.status_execucao = 1
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            return false;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->origemId      = (int)$row['clube_origem_id'];
        $this->destinoId     = (int)$row['clube_destino_id'];

        $this->jogadorNome   = $row['jogador_nome'];
        $this->fotoJogador   = $row['jogador_foto'];
        
        $bandeira = $row['bandeira_png'];
        if ($bandeira && strpos($bandeira, '/images/bandeiras/') !== 0) {
            $bandeira = '/images/bandeiras/' . $bandeira;
        }
        $this->bandeiraPng   = $bandeira;

        $this->origemNome    = $row['origem_nome'];
        $origemEscudo = $row['origem_escudo'];
        if ($origemEscudo && strpos($origemEscudo, '/images/escudos/') !== 0) {
            $origemEscudo = '/images/escudos/' . $origemEscudo;
        }
        $this->origemEscudo  = $origemEscudo;

        $this->destinoNome   = $row['destino_nome'];
        $destinoEscudo = $row['destino_escudo'];
        if ($destinoEscudo && strpos($destinoEscudo, '/images/escudos/') !== 0) {
            $destinoEscudo = '/images/escudos/' . $destinoEscudo;
        }
        $this->destinoEscudo = $destinoEscudo;

        $this->valor         = $row['valor'];
        
        // Mapeamento do tipo de transferência
        $emprestimo = (int)$row['emprestimo'];
        $val = (float)$row['valor'];
        if ($emprestimo == 1 || $emprestimo == 2) {
            $this->tipo = "Empréstimo";
        } elseif ($val == 0 || $emprestimo == 4) {
            $this->tipo = "Sem custo";
        } else {
            $this->tipo = "Permanente";
        }

        $this->data          = $row['data_confirmacao'];

        return true;
    }
}
?>
