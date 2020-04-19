<?php

class SQLiteDatabase{

    // credenciais do banco de dados
    public $conn;
    public $fileName;

    // estabelecer conexão
    public function getConnection(){

        $this->conn = null;

        try{
            $this->conn = new PDO('sqlite:'.$this->fileName);

            //verificar se os dois itens abaixo não vão gerar problemas.
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        }catch(PDOException $exception){
            echo "Erro de conexão: " . $exception->getMessage();
        }

        return $this->conn;
    }



    public function prepareTables(){

        //trio de arbitragem
        $query =
        "CREATE TABLE IF NOT EXISTS `trioarbitragem` (
            `ID`	INTEGER PRIMARY KEY,
            `Arbitro`	varchar ( 45 ) NOT NULL,
            `Auxiliar1`	varchar ( 45 ) NOT NULL,
            `Auxiliar2`	varchar ( 45 ) NOT NULL,
            `Estilo`	int ( 5 ) NOT NULL
        );

        CREATE TABLE IF NOT EXISTS `tecnico` (
            `ID`	INTEGER PRIMARY KEY,
            `Nome`	varchar ( 45 ) NOT NULL,
            `Idade`	int ( 10 ) DEFAULT NULL,
            `Nivel`	int ( 10 ) NOT NULL,
            `Mentalidade`	int ( 5 ) NOT NULL,
            `Estilo`	int ( 5 ) NOT NULL
        );

        CREATE TABLE IF NOT EXISTS `posicaojogador` (
            `Jogador`	int ( 5 ) NOT NULL,
            `G`	tinyint ( 1 ) NOT NULL,
            `LD`	tinyint ( 1 ) NOT NULL,
            `LE`	tinyint ( 1 ) NOT NULL,
            `Z`	tinyint ( 1 ) NOT NULL,
            `AD`	tinyint ( 1 ) NOT NULL,
            `AE`	tinyint ( 1 ) NOT NULL,
            `V`	tinyint ( 1 ) NOT NULL,
            `MD`	tinyint ( 1 ) NOT NULL,
            `ME`	tinyint ( 1 ) NOT NULL,
            `MC`	tinyint ( 1 ) NOT NULL,
            `PD`	tinyint ( 1 ) NOT NULL,
            `PE`	tinyint ( 1 ) NOT NULL,
            `MA`	tinyint ( 1 ) NOT NULL,
            `Am`	tinyint ( 1 ) NOT NULL,
            `Aa`	tinyint ( 1 ) NOT NULL,
            PRIMARY KEY(`Jogador`)
        );

        CREATE TABLE IF NOT EXISTS `perfiljogador` (
            `ID`	INTEGER PRIMARY KEY AUTOINCREMENT,
            `Nome`	varchar ( 45 ) NOT NULL,
            `Marcacao`	int ( 10 ) NOT NULL,
            `Desarme`	int ( 10 ) NOT NULL,
            `VisaoJogo`	int ( 10 ) NOT NULL,
            `Movimentacao`	int ( 10 ) NOT NULL,
            `Cruzamentos`	int ( 10 ) NOT NULL,
            `Cabeceamento`	int ( 10 ) NOT NULL,
            `Tecnica`	int ( 10 ) NOT NULL,
            `ControleBola`	int ( 10 ) NOT NULL,
            `Finalizacao`	int ( 10 ) NOT NULL,
            `FaroGol`	int ( 10 ) NOT NULL,
            `Velocidade`	int ( 10 ) NOT NULL,
            `Forca`	int ( 10 ) NOT NULL
        );

        CREATE TABLE IF NOT EXISTS `perfilgoleiro` (
            `ID`	INTEGER PRIMARY KEY AUTOINCREMENT,
            `Nome`	varchar ( 45 ) NOT NULL,
            `Reflexos`	int ( 10 ) NOT NULL,
            `Seguranca`	int ( 10 ) NOT NULL,
            `Saidas`	int ( 10 ) NOT NULL,
            `JogoAereo`	int ( 10 ) NOT NULL,
            `Lancamentos`	int ( 10 ) NOT NULL,
            `DefesaPenaltis`	int ( 10 ) NOT NULL
        );

        CREATE TABLE IF NOT EXISTS `parametros` (
            `ID`	INTEGER PRIMARY KEY,
            `Nome`	varChar [45] NOT NULL,
            `Gols`	integer NOT NULL,
            `Faltas`	integer NOT NULL,
            `Impedimentos`	integer NOT NULL,
            `Cartoes`	integer NOT NULL,
            `Chao`	float NOT NULL,
            `Alto`	float NOT NULL,
            `padrao`	integer DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS `paispadrao` (
            `ID_Parametro`	INTEGER,
            `PaisPadrao`	varchar ( 45 ) NOT NULL,
            `ExibirBandeiras`	INTEGER NOT NULL,
            PRIMARY KEY(`ID_Parametro`)
        );

        CREATE TABLE IF NOT EXISTS `opcoes` (
            `parametro`	STRING,
            `valor`	INT,
            `valorLong`	bigInt DEFAULT 0,
            PRIMARY KEY(`parametro`)
        );

        CREATE TABLE IF NOT EXISTS `nacionalidades` (
            `ID_Jogador`	INTEGER,
            `Nacionalidade`	varchar ( 45 ) NOT NULL,
            PRIMARY KEY(`ID_Jogador`)
        );

        CREATE TABLE IF NOT EXISTS `jogador` (
            `ID`	INTEGER PRIMARY KEY,
            `Nome`	varchar ( 45 ) NOT NULL,
            `Idade`	int ( 10 ) DEFAULT NULL,
            `Nivel`	int ( 10 ) NOT NULL,
            `Potencial`	int ( 10 ) DEFAULT NULL,
            `CrescBase`	int ( 10 ) DEFAULT NULL,
            `Mentalidade`	int ( 5 ) NOT NULL,
            `CobradorFalta`	int ( 10 ) NOT NULL
        );

        CREATE TABLE IF NOT EXISTS `estadio` (
            `ID`	INTEGER PRIMARY KEY,
            `Nome`	varchar ( 45 ) NOT NULL,
            `Capacidade`	int ( 10 ) NOT NULL,
            `Clima`	int ( 5 ) NOT NULL,
            `Altitude`	tinyint ( 1 ) NOT NULL,
            `Caldeirao`	tinyint ( 1 ) NOT NULL
        );

        CREATE TABLE IF NOT EXISTS `escalacao` (
            `Clube`	int ( 5 ) NOT NULL,
            `Pos1`	varchar ( 45 ) NOT NULL,
            `Jogador1`	int ( 5 ) NOT NULL,
            `Pos2`	varchar ( 45 ) NOT NULL,
            `Jogador2`	int ( 5 ) NOT NULL,
            `Pos3`	varchar ( 45 ) NOT NULL,
            `Jogador3`	int ( 5 ) NOT NULL,
            `Pos4`	varchar ( 45 ) NOT NULL,
            `Jogador4`	int ( 5 ) NOT NULL,
            `Pos5`	varchar ( 45 ) NOT NULL,
            `Jogador5`	int ( 5 ) NOT NULL,
            `Pos6`	varchar ( 45 ) NOT NULL,
            `Jogador6`	int ( 5 ) NOT NULL,
            `Pos7`	varchar ( 45 ) NOT NULL,
            `Jogador7`	int ( 5 ) NOT NULL,
            `Pos8`	varchar ( 45 ) NOT NULL,
            `Jogador8`	int ( 5 ) NOT NULL,
            `Pos9`	varchar ( 45 ) NOT NULL,
            `Jogador9`	int ( 5 ) NOT NULL,
            `Pos10`	varchar ( 45 ) NOT NULL,
            `Jogador10`	int ( 5 ) NOT NULL,
            `Pos11`	varchar ( 45 ) NOT NULL,
            `Jogador11`	int ( 5 ) NOT NULL,
            `Capitao`	int ( 5 ) NOT NULL,
            `Penalti1`	int ( 5 ) DEFAULT NULL,
            `Penalti2`	int ( 5 ) DEFAULT NULL,
            `Penalti3`	int ( 5 ) DEFAULT NULL,
            PRIMARY KEY(`Clube`)
        );

        CREATE TABLE IF NOT EXISTS `elenco` (
            `Clube`	int ( 5 ) NOT NULL,
            `Jogador1`	int ( 5 ) NOT NULL,
            `Jogador2`	int ( 5 ) NOT NULL,
            `Jogador3`	int ( 5 ) NOT NULL,
            `Jogador4`	int ( 5 ) NOT NULL,
            `Jogador5`	int ( 5 ) NOT NULL,
            `Jogador6`	int ( 5 ) NOT NULL,
            `Jogador7`	int ( 5 ) NOT NULL,
            `Jogador8`	int ( 5 ) NOT NULL,
            `Jogador9`	int ( 5 ) NOT NULL,
            `Jogador10`	int ( 5 ) NOT NULL,
            `Jogador11`	int ( 5 ) NOT NULL,
            `Jogador12`	int ( 5 ) DEFAULT NULL,
            `Jogador13`	int ( 5 ) DEFAULT NULL,
            `Jogador14`	int ( 5 ) DEFAULT NULL,
            `Jogador15`	int ( 5 ) DEFAULT NULL,
            `Jogador16`	int ( 5 ) DEFAULT NULL,
            `Jogador17`	int ( 5 ) DEFAULT NULL,
            `Jogador18`	int ( 5 ) DEFAULT NULL,
            `Jogador19`	int ( 5 ) DEFAULT NULL,
            `Jogador20`	int ( 5 ) DEFAULT NULL,
            `Jogador21`	int ( 5 ) DEFAULT NULL,
            `Jogador22`	int ( 5 ) DEFAULT NULL,
            `Jogador23`	int ( 5 ) DEFAULT NULL,
            `Tecnico`	int ( 5 ) NOT NULL,
            PRIMARY KEY(`Clube`)
        );

        CREATE TABLE IF NOT EXISTS `compatibilidade` (
            `versao`	VARCHAR,
            PRIMARY KEY(`versao`)
        );

        CREATE TABLE IF NOT EXISTS `jogadorpendente` (
            `idJogador`	INT,
            PRIMARY KEY(`idJogador`)
        );

        CREATE TABLE IF NOT EXISTS `clube` (
            `ID`	INTEGER PRIMARY KEY,
            `Nome`	varchar ( 100 ) NOT NULL,
            `TresLetras`	varchar ( 3 ) NOT NULL,
            `Estadio`	int ( 5 ) NOT NULL,
            `Escudo`	varchar ( 500 ) DEFAULT NULL,
            `Uni1Cor1`	varchar ( 9 ) NOT NULL,
            `Uni1Cor2`	varchar ( 9 ) NOT NULL,
            `Uni1Cor3`	varchar ( 9 ) NOT NULL,
            `Uniforme1`	varchar ( 500 ) DEFAULT NULL,
            `Uni2Cor1`	varchar ( 9 ) NOT NULL,
            `Uni2Cor2`	varchar ( 9 ) NOT NULL,
            `Uni2Cor3`	varchar ( 9 ) NOT NULL,
            `Uniforme2`	varchar ( 500 ) DEFAULT NULL,
            `MaxTorcedores`	int ( 10 ) NOT NULL,
            `Fidelidade`	int ( 10 ) NOT NULL
        );

        CREATE TABLE IF NOT EXISTS `clima` (
            `ID`	INTEGER PRIMARY KEY,
            `Nome`	varchar ( 45 ) NOT NULL,
            `TempVerao`	varchar ( 45 ) NOT NULL,
            `EstiloVerao`	varchar ( 45 ) NOT NULL,
            `TempOutono`	varchar ( 45 ) NOT NULL,
            `EstiloOutono`	varchar ( 45 ) NOT NULL,
            `TempInverno`	varchar ( 45 ) NOT NULL,
            `EstiloInverno`	varchar ( 45 ) NOT NULL,
            `TempPrimavera`	varchar ( 45 ) NOT NULL,
            `EstiloPrimavera`	varchar ( 45 ) NOT NULL,
            `Hemisferio`	int ( 10 ) NOT NULL
        );

        CREATE TABLE IF NOT EXISTS `atributosjogador` (
            `Jogador`	int ( 5 ) NOT NULL,
            `Marcacao`	float NOT NULL,
            `Desarme`	float NOT NULL,
            `VisaoJogo`	float NOT NULL,
            `Movimentacao`	float NOT NULL,
            `Cruzamentos`	float NOT NULL,
            `Cabeceamento`	float NOT NULL,
            `Tecnica`	float NOT NULL,
            `ControleBola`	float NOT NULL,
            `Finalizacao`	float NOT NULL,
            `FaroGol`	float NOT NULL,
            `Velocidade`	float NOT NULL,
            `Forca`	float NOT NULL,
            `Determinacao`	float NOT NULL,
            `determinacaoOriginal`	INTEGER,
            PRIMARY KEY(`Jogador`)
        );

        CREATE TABLE IF NOT EXISTS `atributosgoleiro` (
            `Goleiro`	int ( 5 ) NOT NULL,
            `Reflexos`	float NOT NULL,
            `Seguranca`	float NOT NULL,
            `Saidas`	float NOT NULL,
            `JogoAereo`	float NOT NULL,
            `Lancamentos`	float NOT NULL,
            `DefesaPenaltis`	float NOT NULL,
            `Determinacao`	float NOT NULL,
            `determinacaoOriginal`	INTEGER,
            PRIMARY KEY(`Goleiro`)
        );";

        $this->conn->exec($query);

    }

    public function initialMainValues(){

        $query =
        "INSERT INTO `perfiljogador` VALUES (1,'Lateral Defensivo',6,7,5,5,7,5,4,5,4,3,4,5);
        INSERT INTO `perfiljogador` VALUES (2,'Lateral Incansável',7,6,4,7,6,4,4,5,4,3,5,5);
        INSERT INTO `perfiljogador` VALUES (3,'Ala Ofensivo',4,5,6,7,7,3,5,5,5,4,5,4);
        INSERT INTO `perfiljogador` VALUES (4,'Beque Central',7,7,5,3,4,7,3,5,6,5,3,5);
        INSERT INTO `perfiljogador` VALUES (5,'Quarto Zagueiro',7,6,4,4,4,7,4,5,5,4,5,5);
        INSERT INTO `perfiljogador` VALUES (6,'Líbero',6,7,7,4,4,6,4,4,5,4,4,5);
        INSERT INTO `perfiljogador` VALUES (7,'Cabeça de Área',7,7,6,4,3,7,4,5,5,3,4,5);
        INSERT INTO `perfiljogador` VALUES (8,'Volante de Raça',7,7,5,5,4,5,4,4,5,4,5,5);
        INSERT INTO `perfiljogador` VALUES (9,'2º Volante',7,6,6,5,4,3,5,7,4,4,5,4);
        INSERT INTO `perfiljogador` VALUES (10,'Carregador de Piano',4,5,7,7,5,4,4,6,5,3,5,5);
        INSERT INTO `perfiljogador` VALUES (11,'Meia Lateral',4,5,7,6,7,3,5,5,5,4,5,4);
        INSERT INTO `perfiljogador` VALUES (12,'Meia Controlador',4,5,6,5,5,4,4,7,6,4,5,5);
        INSERT INTO `perfiljogador` VALUES (13,'Armador de Cadência',3,3,7,4,6,4,7,6,6,6,4,4);
        INSERT INTO `perfiljogador` VALUES (14,'Armador Arisco',3,3,6,6,5,3,7,7,6,5,5,4);
        INSERT INTO `perfiljogador` VALUES (15,'Ponta Fixo',3,3,7,4,6,3,7,7,6,6,5,3);
        INSERT INTO `perfiljogador` VALUES (16,'Atacante de Movimentação',3,3,5,7,5,4,7,6,6,6,5,3);
        INSERT INTO `perfiljogador` VALUES (17,'2º Atacante',3,3,6,5,4,5,4,6,7,7,5,5);
        INSERT INTO `perfiljogador` VALUES (18,'Atacante de Área',3,4,5,4,4,7,5,6,7,7,3,5);
        INSERT INTO `perfiljogador` VALUES (19,'Centroavante Oportunista',3,3,5,6,4,6,5,5,7,7,4,5);

        INSERT INTO `perfilgoleiro` VALUES (1,'Goleiro Ágil',10,6,9,6,7,7);
        INSERT INTO `perfilgoleiro` VALUES (2,'Goleiro Seguro',7,10,8,9,6,5);
        INSERT INTO `perfilgoleiro` VALUES (3,'Goleiro Espalhafatoso',8,3,10,9,7,8);
        INSERT INTO `perfilgoleiro` VALUES (4,'Pegador de Pênaltis',9,8,6,6,6,10);
        INSERT INTO `perfilgoleiro` VALUES (5,'Goleiro de Presença',6,8,8,10,6,7);

        INSERT INTO `compatibilidade` VALUES ('2.8');
        INSERT INTO `compatibilidade` VALUES ('2.9.1');
        INSERT INTO `compatibilidade` VALUES ('2.10');";

        $this->conn->exec($query);
    }

    public function directRun($queryInsercao){
        $queryInsercao = htmlspecialchars(strip_tags($queryInsercao));

        $queryInsercao = html_entity_decode($queryInsercao);

        $this->conn->exec($queryInsercao);
    }

    public function runQuery($queryInsercao,$paramArray = null){

        $queryInsercao = htmlspecialchars(strip_tags($queryInsercao));

       // echo '<pre>Query: ' . var_export($queryInsercao, true) . '</pre>';
       //   echo '<pre>Parametro: ' . var_export($paramArray, true) . '</pre>';

        $stmt = $this->conn->prepare( $queryInsercao );

        $numParametros = 0;
        if($paramArray != null){
            $numParametros = count($paramArray);

            if($numParametros == 1){
                $stmt->bindParam(1,$paramArray);
            } else {
                $j = 1;
                for($i = 0; $i<$numParametros; $i++){
                    $stmt->bindParam($j, $paramArray[$i]);
                    $j++;
                }
            }



        }

    //rodar query
        $stmt->execute();
        //$stmt->debugDumpParams();

    }


    public function setAutoincrement(){


    }
}
?>
