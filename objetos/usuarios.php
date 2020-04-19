<?php
class Usuario{

    // database connection and table name
    private $conn;
    private $table_name = "usuarios";

    // object properties
    public $id;
    public $nomeusuario;
    public $nome;
    public $senha;
    public $email;

    public function __construct($db){
        $this->conn = $db;
    }

    // used by select drop-down list
    function read(){
        //select all data
        $query = "SELECT
                    id, nome
                FROM
                    " . $this->table_name . "
                ORDER BY
                    nome";

        $stmt = $this->conn->prepare( $query );
        $stmt->execute();

        return $stmt;
    }

    function passByName($name){

        $name = htmlspecialchars(strip_tags($name));
        //select all data
        $query = "SELECT
                    senha, nome, admin_status
                FROM
                    " . $this->table_name . "
                WHERE
                 nomeusuario = ? OR email = ?
                LIMIT
                 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1,$name);
        $stmt->bindParam(2,$name);
        $stmt->execute();

        $info_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        //$senha_usuario = $info_usuario['senha'];

        return $info_usuario;
    }

    function setId($id){
      $this->id = $id;
    }

    function getNome(){
      return $this->nome;
    }

    // used to read category name by its ID
    function readName(){

    $query = "SELECT nome FROM " . $this->table_name . " WHERE id = ? limit 0,1";

    $stmt = $this->conn->prepare( $query );
    $stmt->bindParam(1, $this->id);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $this->nome = $row['nome'];
}

    function password(){

    $query = "SELECT senha FROM " . $this->table_name . " WHERE id = ? limit 0,1";

    $stmt = $this->conn->prepare( $query );
    $stmt->bindParam(1, $this->id);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $this->senha = $row['senha'];

    }


    function ID($name){

        $name = htmlspecialchars(strip_tags($name));

    $query = "SELECT id FROM " . $this->table_name . " WHERE nomeusuario = ? OR email = ? limit 0,1";

    $stmt = $this->conn->prepare( $query );
    $stmt->bindParam(1, $name);
    $stmt->bindParam(2, $name);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_usuario = $row['id'];

    return $id_usuario;

    }

    function idByEmail($email){

        $email = htmlspecialchars(strip_tags($email));

        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? limit 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_usuario = $row['id'];

        return $id_usuario;

        }

    function passById($id){

        $id = htmlspecialchars(strip_tags($id));
        //select all data
        $query = "SELECT
                    senha
                FROM
                    " . $this->table_name . "
                WHERE
                 id = ?
                LIMIT
                 0,1";

        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1,$id);
        $stmt->execute();

        $senha_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        //$senha_usuario = $info_usuario['senha'];

        return $senha_usuario;
    }

    function alterarSenha($idInformada, $senhaNovaInformada){

        $idInformada = htmlspecialchars(strip_tags($idInformada));

        $query = "UPDATE " . $this->table_name . " SET senha = ? WHERE id = ?";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(1, $senhaNovaInformada);
        $stmt->bindParam(2, $idInformada);

        if($stmt->execute()){
            return true;
        } else {
            return false;
        }
    }

    function inserir(){

                //write query
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    nomeusuario=:nomeusuario, senha=:senha, email=:email, nome=:nome";

        $stmt = $this->conn->prepare($query);

        // posted values
        $this->nomeusuario=htmlspecialchars(strip_tags($this->nomeusuario));
        $this->senha=htmlspecialchars(strip_tags($this->senha));
        $this->email=htmlspecialchars(strip_tags($this->email));
        $this->nome=htmlspecialchars(strip_tags($this->nome));

        //inserir verificação de usuario aqui
        $query_comparacao = "SELECT id FROM ". $this->table_name . " WHERE nomeusuario = ? OR email = ?";
        $stmt_comparacao = $this->conn->prepare($query_comparacao);
        $stmt_comparacao->bindParam(1, $this->nomeusuario);
        $stmt_comparacao->bindParam(2, $this->email);
        $stmt_comparacao->execute();
        $result_comp = $stmt_comparacao->fetch(PDO::FETCH_ASSOC);

        // bind values
        $stmt->bindParam(":nomeusuario", $this->nomeusuario);
        $stmt->bindParam(":senha", $this->senha);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":nome", $this->nome);

        if($result_comp !== false){
            return false;
        } else {
            if($stmt->execute()){
                return true;
            } else {
                return false;
            }
        }

    }

    function atualizarDownload($idUsuario){

        $idUsuario = htmlspecialchars(strip_tags($idUsuario));

        $query = "UPDATE " . $this->table_name . " SET ultimoDownload = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(1, $idUsuario);

        $stmt->execute();
    }

    function alteracoesPosteriores($idUsuario){

        $idUsuario = htmlspecialchars(strip_tags($idUsuario));

        $query = "SELECT (UNIX_TIMESTAMP(ultimaAlteracao) - UNIX_TIMESTAMP(ultimoDownload)) as tempo FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(1, $idUsuario);

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['tempo'];
    }

    function atualizarAlteracao($idUsuario){

        $idUsuario = htmlspecialchars(strip_tags($idUsuario));

        $query = "UPDATE " . $this->table_name . " SET ultimaAlteracao = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare( $query );

        $stmt->bindParam(1, $idUsuario);

        $stmt->execute();
    }

}
?>
