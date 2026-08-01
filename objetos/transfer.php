<?php
class Transfer {

    private $conn;

    public string $jogadorNome;
    public string $fotoJogador;
    public string $bandeiraPng;

    public string $origemNome;
    public string $origemEscudo;

    public string $destinoNome;
    public string $destinoEscudo;

    public string $valor;
    public string $tipo;
    public string $data;

    public function __construct($db){
        $this->conn = $db;
    }

    public function carregarPorId(int $id): bool {

        $query = "
            SELECT
                j.Nome AS jogador_nome,
                j.foto AS jogador_foto,
                p.bandeira AS bandeira_png,

                co.Nome AS origem_nome,
                co.Escudo AS origem_escudo,

                cd.Nome AS destino_nome,
                cd.Escudo AS destino_escudo,

                t.valor,
                t.tipoTransferência as tipo,
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

        $this->jogadorNome   = $row['jogador_nome'];
        $this->fotoJogador   = $row['jogador_foto'];
        $this->bandeiraPng   = $row['bandeira_png'];

        $this->origemNome    = $row['origem_nome'];
        $this->origemEscudo  = $row['origem_escudo'];

        $this->destinoNome   = $row['destino_nome'];
        $this->destinoEscudo = $row['destino_escudo'];

        $this->valor         = $row['valor'];
        $this->tipo          = $row['tipo'];
        $this->data          = $row['data_confirmacao'];

        return true;
    }
}
?>
