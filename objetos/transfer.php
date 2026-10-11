<?php
class Transfer {

    private $conn;

    public $origemId;
    public $destinoId;
    public $donoOrigem;
    public $donoDestino;

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
                po.dono AS dono_origem,

                t.clubeDestino AS clube_destino_id,
                cd.Nome AS destino_nome,
                cd.Escudo AS destino_escudo,
                pd.dono AS dono_destino,

                t.valor,
                t.tipoTransferencia as tipo,
                t.emprestimo,
                COALESCE(t.dataConclusao, t.data) as data_confirmacao
            FROM transferencias t
            JOIN jogador j ON j.ID = t.jogador
            JOIN paises p ON p.id = j.Pais
            JOIN clube co ON co.ID = t.clubeOrigem
            JOIN paises po ON po.id = co.Pais
            JOIN clube cd ON cd.ID = t.clubeDestino
            JOIN paises pd ON pd.id = cd.Pais
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
        $this->donoOrigem    = (int)($row['dono_origem'] ?? 0);
        $this->donoDestino   = (int)($row['dono_destino'] ?? 0);

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

    /**
     * Envia notificação ao Discord se a transferência for válida e entre donos diferentes.
     * Retorna true se enviou, false caso contrário.
     */
    public function notificarDiscord(): bool {
        // Envia apenas se for transferência de clube para clube
        if ($this->origemId <= 0 || $this->destinoId <= 0) {
            return false;
        }

        // Não notifica se os clubes pertencerem ao mesmo usuário/dono
        if ($this->donoOrigem > 0 && $this->donoOrigem === $this->donoDestino) {
            return false;
        }

        $webhook = getenv('DISCORD_WEBHOOK');
        if (empty($webhook)) {
            return false;
        }

        require_once __DIR__ . '/transferNotifier.php';

        $siteUrl = getenv('SITE_URL') ?: 'https://confusa.top';
        $baseSiteUrl = rtrim($siteUrl, '/');

        $valorInt = (int)$this->valor;
        if ($valorInt >= 1000000) {
            $valorFormatado = "F$ " . number_format($valorInt, 0, ',', '.') . " (" . round($valorInt / 1000000, 1) . " M)";
        } else {
            $valorFormatado = "F$ " . number_format($valorInt, 0, ',', '.');
        }

        $bandeira_png = $this->bandeiraPng ? $baseSiteUrl . $this->bandeiraPng : '';
        $foto = $this->fotoJogador ? $baseSiteUrl . '/images/jogadores/' . $this->fotoJogador : '';
        $origem_escudo_png = $this->origemEscudo ? $baseSiteUrl . $this->origemEscudo : '';
        $destino_escudo_png = $this->destinoEscudo ? $baseSiteUrl . $this->destinoEscudo : '';

        $transferData = [
            'nome' => $this->jogadorNome,
            'bandeira_png' => $bandeira_png,
            'tipo_transferencia' => $this->tipo,
            'foto' => $foto,
            'origem' => $this->origemNome,
            'origem_escudo_png' => $origem_escudo_png,
            'destino' => $this->destinoNome,
            'destino_escudo_png' => $destino_escudo_png,
            'valor' => $valorFormatado,
            'data' => !empty($this->data) ? date('d/m/Y', strtotime($this->data)) : date('d/m/Y')
        ];

        try {
            $notifier = new TransferNotifier($webhook);
            return $notifier->notify($transferData);
        } catch (\Throwable $e) {
            error_log("[Discord Webhook] Erro ao instanciar TransferNotifier: " . $e->getMessage());
            return false;
        }
    }
}
?>
