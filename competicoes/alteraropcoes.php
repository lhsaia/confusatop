<?php

//ini_set( 'display_errors', true );
//error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
    
$error_msg = "";
   
// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$competicao = new Competicao_clube($db);

// if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    $idUsuario = $_SESSION['user_id'];
    $estadios_times = isset($_POST['estadios_times']) ? intval($_POST['estadios_times']) : 0;
    $idCompeticao = intval($_POST['id_competicao']);
    $desempate_grupos = isset($_POST['desempate_grupos']) ? $_POST['desempate_grupos'] : 'SG,GP,VI,CD';
	
	if($competicao->alterarOpcoes($idUsuario, $_POST['numero_times'], $_POST['data_limite'], $_POST['subir_live'], $_POST['sorteio'], $_POST['gol_fora'], $_POST['final_unica'], $_POST['tipo_competicao'], $_POST['criterio_desempate'], $_POST['criterio_desempate_final'], $_POST['criterio_suspensao'], $_POST['zerar_amarelos'], $_POST['permitir_alteracoes'], $_POST['inicio_alteracoes'], $_POST['fim_alteracoes'], $_POST['numero_alteracoes'], $idCompeticao, $estadios_times, $desempate_grupos)){
		$is_success = true;
	} else {
		$is_success = false;
	}

    if ($is_success) {
        // Sincronizar Árbitros e Estádios no SQLite
        require_once $_SERVER['DOCUMENT_ROOT'] . "/config/sqliteDatabase.php";
        $sqliteDb = new SQLiteDatabase();
        $sqliteDb->fileName = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $idCompeticao . "-database.db3";
        $sdb = $sqliteDb->getConnection();

        if ($sdb) {
            // --- 1. Sincronizar Árbitros ---
            $sdb->exec("DELETE FROM trioarbitragem");

            $arbitroIds = isset($_POST['arbitros']) ? array_map('intval', $_POST['arbitros']) : [];
            $arbitroPais = isset($_POST['arbitros_pais']) ? intval($_POST['arbitros_pais']) : 0;
            $arbitroFed = isset($_POST['arbitros_federacao']) ? $_POST['arbitros_federacao'] : '';

            $arbitrosQuery = "SELECT id, nomeArbitro, nomeAuxiliarUm, nomeAuxiliarDois, estilo FROM arbitros WHERE 0";
            if (!empty($arbitroIds)) {
                $arbitrosQuery .= " OR id IN (" . implode(',', $arbitroIds) . ")";
            }
            if ($arbitroPais > 0) {
                $arbitrosQuery .= " OR pais = " . $arbitroPais;
            }
            if (!empty($arbitroFed) && $arbitroFed !== "0") {
                $arbitrosQuery .= " OR pais IN (SELECT id FROM paises WHERE federacao = " . $db->quote($arbitroFed) . ")";
            }

            if ($arbitrosQuery !== "SELECT id, nomeArbitro, nomeAuxiliarUm, nomeAuxiliarDois, estilo FROM arbitros WHERE 0") {
                $stmtArb = $db->query($arbitrosQuery);
                if ($stmtArb) {
                    $stmtInsertArb = $sdb->prepare("INSERT INTO trioarbitragem (ID, Arbitro, Auxiliar1, Auxiliar2, Estilo) VALUES (:id, :nome, :aux1, :aux2, :estilo)");
                    while ($row = $stmtArb->fetch(PDO::FETCH_ASSOC)) {
                        $stmtInsertArb->bindValue(':id', (int)$row['id'], PDO::PARAM_INT);
                        $stmtInsertArb->bindValue(':nome', $row['nomeArbitro']);
                        $stmtInsertArb->bindValue(':aux1', $row['nomeAuxiliarUm']);
                        $stmtInsertArb->bindValue(':aux2', $row['nomeAuxiliarDois']);
                        $stmtInsertArb->bindValue(':estilo', (int)$row['estilo'], PDO::PARAM_INT);
                        $stmtInsertArb->execute();
                    }
                }
            }

            // --- 2. Sincronizar Estádios ---
            $sdb->exec("DELETE FROM estadio");

            $estadioIds = isset($_POST['estadios']) ? array_map('intval', $_POST['estadios']) : [];
            if (!empty($estadioIds)) {
                $estadiosQuery = "SELECT id, Nome, Capacidade, Clima, Altitude, Caldeirao FROM estadio WHERE id IN (" . implode(',', $estadioIds) . ")";
                $stmtEst = $db->query($estadiosQuery);
                if ($stmtEst) {
                    $stmtInsertEst = $sdb->prepare("INSERT INTO estadio (ID, Nome, Capacidade, Clima, Altitude, Caldeirao) VALUES (:id, :nome, :cap, :clima, :alt, :cald)");
                    while ($row = $stmtEst->fetch(PDO::FETCH_ASSOC)) {
                        $stmtInsertEst->bindValue(':id', (int)$row['id'], PDO::PARAM_INT);
                        $stmtInsertEst->bindValue(':nome', $row['Nome']);
                        $stmtInsertEst->bindValue(':cap', (int)$row['Capacidade'], PDO::PARAM_INT);
                        $stmtInsertEst->bindValue(':clima', (int)$row['Clima'], PDO::PARAM_INT);
                        $stmtInsertEst->bindValue(':alt', (int)$row['Altitude'], PDO::PARAM_INT);
                        $stmtInsertEst->bindValue(':cald', (int)$row['Caldeirao'], PDO::PARAM_INT);
                        $stmtInsertEst->execute();
                    }
                }
            }
        }
    }

    die(json_encode([ 'success'=> $is_success, 'error'=> ""]));
} 
die(json_encode([ 'success'=> 'false', 'error'=> "Não foi feito POST request"]));

    } else {
        
        die(json_encode([ 'success'=> false, 'error'=> "Usuário, refaça o login!"]));


}
?>
