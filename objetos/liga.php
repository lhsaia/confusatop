<?php
class Liga
{

    // conexao de banco de dados e nome da tabela
    private $conn;
    private $table_name = "liga";

    // object properties
    public $id;
    public $nome;
    public $tier;
    public $limite_idade;
    public $logo;
    public $pais;
    public $sexo;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // criar time
    function inserir()
    {

        //escrever query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    nome=:nome, tier=:tier, limite_idade=:limite_idade, logo=:logo, pais=:pais, Sexo=:sexo, status=0 ";

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->nome = htmlspecialchars(strip_tags((string)($this->nome ?? '')));
        $this->tier = htmlspecialchars(strip_tags((string)($this->tier ?? '')));
        $this->limite_idade = (!empty($this->limite_idade) && intval($this->limite_idade) > 0) ? intval($this->limite_idade) : null;
        $this->logo = htmlspecialchars(strip_tags((string)($this->logo ?? '')));
        $this->pais = htmlspecialchars(strip_tags((string)($this->pais ?? '')));
        $this->sexo = htmlspecialchars(strip_tags((string)($this->sexo ?? '')));

        // bind values
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":tier", $this->tier);
        if ($this->limite_idade !== null) {
            $stmt->bindParam(":limite_idade", $this->limite_idade, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":limite_idade", null, PDO::PARAM_NULL);
        }
        $stmt->bindParam(":logo", $this->logo);
        $stmt->bindParam(":pais", $this->pais);
        $stmt->bindParam(":sexo", $this->sexo);

        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }

    }

    //ler todos os jogadores para o quadro
    function readAll($from_record_num, $records_per_page, $idDonoPais = null, $idFederacao = null, $idPais = null)
    {

        if ($idDonoPais != null) {
            $subquery = "WHERE p.dono = ?";
        } else if ($idFederacao != null) {
            $subquery = "WHERE p.federacao = ?";
        } else if ($idPais != null) {
            $subquery = "WHERE p.id = ?";
        } else {
            $subquery = "";
        }

        $query = "SELECT
                 a.id, a.nome, a.tier, a.limite_idade, a.logo, p.sigla as siglaPais, p.bandeira as bandeiraPais, p.id as idPais, p.dono as idDonoPais, a.Sexo as sexo, p.nome as nomePais 
             FROM
                 " . $this->table_name . " a
             LEFT JOIN paises p ON a.pais = p.id
             " . $subquery . "
             ORDER BY
                 a.tier ASC, siglaPais ASC
             LIMIT
                 {$from_record_num}, {$records_per_page}";

        $stmt = $this->conn->prepare($query);
        if ($idDonoPais != null) {
            $stmt->bindParam(1, $idDonoPais);
        } else if ($idFederacao != null) {
            $stmt->bindParam(1, $idFederacao);
        } else if ($idPais != null) {
            $stmt->bindParam(1, $idPais);
        }
        $stmt->execute();


        return $stmt;
    }

    // used for paging products
    public function countAll($idDonoPais = null, $idFederacao = null, $idPais = null)
    {
        $idDonoPais = !empty($idDonoPais) ? htmlspecialchars(strip_tags($idDonoPais)) : null;
        $idFederacao = !empty($idFederacao) ? htmlspecialchars(strip_tags($idFederacao)) : null;
        $idPais = !empty($idPais) ? htmlspecialchars(strip_tags($idPais)) : null;

        $query = "";
        $params = [];

        if ($idDonoPais === null && $idFederacao === null && $idPais === null) {
            $query = "SELECT id FROM " . $this->table_name;
        } else if ($idFederacao === null && $idPais === null) {
            $query = "SELECT a.id
                     FROM " . $this->table_name . " a
                      LEFT JOIN paises p ON a.pais = p.id
                       WHERE p.dono = :idDonoPais";
            $params[':idDonoPais'] = $idDonoPais;
        } else if ($idDonoPais === null && $idPais === null) {
            $query = "SELECT a.id
        FROM " . $this->table_name . " a
         LEFT JOIN paises p ON a.pais = p.id
          WHERE p.federacao = :idFederacao";
            $params[':idFederacao'] = $idFederacao;
        } else if ($idDonoPais === null && $idFederacao === null) {
            $query = "SELECT a.id
        FROM " . $this->table_name . " a
         LEFT JOIN paises p ON a.pais = p.id
          WHERE p.id = :idPais";
            $params[':idPais'] = $idPais;
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();

        $num = $stmt->rowCount();

        return $num;
    }

    function alterar($idLiga, $nomeLiga, $tierLiga, $pais, $logo = null, $limite_idade = null)
    {

        $idLiga = htmlspecialchars(strip_tags((string)$idLiga));
        $nomeLiga = htmlspecialchars(strip_tags((string)$nomeLiga));
        $tierLiga = htmlspecialchars(strip_tags((string)$tierLiga));
        $pais = htmlspecialchars(strip_tags((string)$pais));
        $logo = ($logo !== null && $logo !== '') ? htmlspecialchars(strip_tags((string)$logo)) : null;
        $limite_idade = (!empty($limite_idade) && intval($limite_idade) > 0) ? intval($limite_idade) : null;

        if ($logo != null) {
            $subquery = ", logo=:logo";
        } else {
            $subquery = "";
        }

        $query = "UPDATE " . $this->table_name . " SET nome=:nome, tier=:tier, limite_idade=:limite_idade, pais=:pais " . $subquery . " WHERE id=:id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nome", $nomeLiga);
        $stmt->bindParam(":tier", $tierLiga);
        if ($limite_idade !== null) {
            $stmt->bindParam(":limite_idade", $limite_idade, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":limite_idade", null, PDO::PARAM_NULL);
        }
        $stmt->bindParam(":pais", $pais);
        if ($logo != null) {
            $stmt->bindParam(":logo", $logo);
        }
        $stmt->bindParam(":id", $idLiga);

        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }

    }

    function readInfo($id)
    {
        $id = intval($id);

        $info1 = [
            'nome' => '',
            'tier' => '',
            'limite_idade' => null,
            'logo' => '',
            'Pais' => '',
            'idPais' => 0,
            'Sexo' => '',
            'idDonoPais' => 0
        ];

        $info2 = [
            'mediaIdade' => 0,
            'mediaNivel' => 0,
            'mediaNivelOnze' => 0,
            'valorTotal' => 0,
            'estrangeiros' => 0,
            'jogadores' => 0
        ];

        if ($id > 0) {
            $query = "SELECT
                    a.nome, a.tier, a.limite_idade, a.logo, COALESCE(p.nome, p.Nome, '') as Pais, a.Pais as idPais, a.Sexo, p.dono as idDonoPais
                FROM
                    " . $this->table_name . " a
                LEFT JOIN
                    paises p ON a.Pais = p.id
                WHERE
                    a.id=:id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $res1 = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res1) {
                $info1 = $res1;
            }

            $query = "SELECT avg(DATEDIFF(NOW(), j.Nascimento)/365) as mediaIdade, avg(j.Nivel) as mediaNivel, avg(case when c.titularidade > 0 then j.Nivel else null end) as mediaNivelOnze, SUM(j.valor) as valorTotal, sum(case when j.Pais != b.Pais then 1 else 0 end) as estrangeiros, count(*) as jogadores 
        FROM contratos_jogador c
        LEFT JOIN jogador j ON c.jogador = j.id
        LEFT JOIN paises p ON j.Pais = p.id
        LEFT JOIN clube b ON c.clube = b.id
        WHERE b.liga = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $res2 = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res2) {
                $info2 = $res2;
            }
        }

        return array_merge((array)$info1, (array)$info2);
    }

    // used by select drop-down list
    function read($dono)
    {

        //select all data
        $query = "SELECT
                    a.id, a.nome, a.Pais, a.Sexo
                FROM
                    " . $this->table_name . " a
                LEFT JOIN
                    paises p ON a.Pais = p.id
                WHERE p.dono = ?
                ORDER BY
                    nome";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $dono);

        $stmt->execute();

        return $stmt;
    }

    function readAdmin()
    {
        $query = "SELECT
                    a.id, a.nome, a.Pais, a.Sexo
                FROM
                    " . $this->table_name . " a
                LEFT JOIN
                    paises p ON a.Pais = p.id
                ORDER BY
                    nome";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    function lerPorPais($pais, $sexo)
    {

        //select all data
        $query = "SELECT
                    a.id, a.nome
                FROM
                    " . $this->table_name . " a
                LEFT JOIN
                    paises p ON a.Pais = p.id
                WHERE p.id = ? AND a.Sexo = ?
                ORDER BY
                    a.tier ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $pais);
        $stmt->bindParam(2, $sexo);

        $stmt->execute();

        return $stmt;
    }

    function mediaNiveis($idLiga)
    {

        $idLiga = htmlspecialchars(strip_tags($idLiga));

        $query = "SELECT (SUM(j.Nivel) + SUM(c.ModificadorNivel))/COUNT(c.jogador) as mediaNiveis FROM contratos_jogador c LEFT JOIN jogador j ON j.ID = c.jogador LEFT JOIN clube b ON b.ID = c.clube WHERE b.liga = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idLiga);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $media = $result['mediaNiveis'];


        return $media;

    }

    function totalTimes($idLiga)
    {

        $idLiga = htmlspecialchars(strip_tags($idLiga));

        $query = "SELECT COUNT(ID) as totalTimes FROM clube WHERE liga = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idLiga);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $media = $result['totalTimes'];


        return $media;

    }

    function logoPadrao()
    {
        $query = "SELECT logo FROM liga WHERE id = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result;
    }

    function apagar($idApagar)
    {
        $idApagar = htmlspecialchars(strip_tags($idApagar));

        $error_count = 0;

        $selectionQuery = "UPDATE clube SET liga=0 WHERE liga=:id";
        $stmt = $this->conn->prepare($selectionQuery);
        $stmt->bindParam(1, $idApagar);
        if ($stmt->execute()) {
        } else {
            $error_count++;
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idApagar);
        if ($stmt->execute()) {
        } else {
            $error_count++;
        }

        if ($error_count == 0) {
            return true;
        } else {
            return false;
        }

    }

    function nomeLiga($codigo_liga)
    {
        $codigo_liga = htmlspecialchars(strip_tags($codigo_liga));

        $query = "SELECT nome FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $codigo_liga);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result;
    }

    function isFromUser($leagueList, $userID)
    {
        if (empty($leagueList) || !is_array($leagueList)) {
            return false;
        }

        $cleanLeagueList = array_values(array_unique(array_filter($leagueList, function($val) {
            return $val !== '' && $val !== null;
        })));

        if (empty($cleanLeagueList)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($cleanLeagueList), '?'));
        $query = "SELECT l.id, p.dono FROM " . $this->table_name . " l LEFT JOIN paises p ON p.id = l.Pais WHERE l.id IN (" . $placeholders . ")";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($cleanLeagueList);

        $foundLeagues = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['dono'] === null || (string)$row['dono'] !== (string)$userID) {
                return false;
            }
            $foundLeagues[] = $row['id'];
        }

        if (count($foundLeagues) !== count($cleanLeagueList)) {
            return false;
        }

        return true;
    }
}
?>