<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

if(!isset($_POST['criar'])){
    session_start();
}

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    if(isset($_POST['criar'])){
        $nacionalidade = $_POST['pais'];
    } else {
        $sexo = $_POST['sexo'];
        $nacionalidade = $_POST['nacionalidade'];
        $idadeMin = 0;
        $idadeMax = 0;
        $idadeMed = 0;
        $nivelMin = 0;
        $nivelMax = 0;
        $nivelMed = 0;

        //estabelecer conexão com banco de dados
        include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
        $database = new Database();
        $db = $database->getConnection();
        $tecnico = new Tecnico($db);
        $pais = new Pais($db);
        $liga = new Liga($db);
        $time = new Time($db);
    }



    if(isset($_POST['idadeMin'])){
        $idadeMin = $_POST['idadeMin'];
    }

    if(isset($_POST['idadeMax'])){
        $idadeMax = $_POST['idadeMax'];
    }

    if(isset($_POST['nivelMin'])){
        $nivelMin = $_POST['nivelMin'];
    }

    if(isset($_POST['nivelMax'])){
        $nivelMax = $_POST['nivelMax'];
    }

    if(isset($_POST['nivelMed'])){
        $nivelMed = $_POST['nivelMed'];
    }

    if(isset($_POST['idadeMed'])){
        $idadeMed = $_POST['idadeMed'];
    }

    if($nacionalidade == 0){
        $nacionalidade = $pais->sorteiaNacionalidade($_SESSION['user_id']);
    }


    $errorCounter = 0;
    $error_msg = '';

    //verificar se houve override de origem
    if(isset($_POST['nomenclatura']) && $_POST['nomenclatura'] == 1){

        $listaNomes = $_POST['origemNomes'];

        if(!is_array($listaNomes)){
            $valueNome = $listaNomes;
            $listaNomes = array();
            $listaNomes[] = $valueNome;
        }

        $origemNomes = array_rand($listaNomes);
        $origemNomes = $listaNomes[$origemNomes];

        $listaSobrenomes = $_POST['origemSobrenomes'];

        if(!is_array($listaSobrenomes)){
            $valueSobrenome = $listaSobrenomes;
            $listaSobrenomes = array();
            $listaSobrenomes[] = $valueSobrenome;
        }

        $origemSobrenomes = array_rand($listaSobrenomes);
        $origemSobrenomes = $listaSobrenomes[$origemSobrenomes];
        $indiceMiscigenacao = 100;
    $ocorrenciaNomeDuplo = 0;

    } else {
        $origemNomes = $pais->sorteioDemografico($nacionalidade, 0, $sexo);
        $origemSobrenomes = $pais->sorteioDemografico($nacionalidade,1, $sexo);
        $indiceMiscigenacao = $pais->verificarMiscigenacao($nacionalidade,$origemNomes);
    $ocorrenciaNomeDuplo = $pais->verificarNomeDuplo($nacionalidade,$origemNomes);
    }

    $tecnico->randomTecnico($nacionalidade, $origemNomes, $origemSobrenomes,$idadeMin,$idadeMax,$nivelMin,$nivelMax,$nivelMed,$idadeMed,$ocorrenciaNomeDuplo, $indiceMiscigenacao, $sexo);

    if(isset($_POST['inserir'])){
        $jogador->sexo = $sexo;
        if($tecnico->create(true)){
            $idTecnico = $db->lastInsertId();

            if(isset($_POST['clube'])){
               if($tecnico->transferir($idJogador,$_POST['clube'])){
                    $error_msg .= '';
               } else {
                    $errorCounter++;
                    $error_msg .= 'Técnico foi criado mas não foi possível transferir para o clube';
               }
            } else {
                $error_msg .= '';
            }
        } else {
            $errorCounter++;
            $error_msg .= 'Não foi possível inserir o técnico';
        }

    } else {
        $error_msg .= '';
    }


if($errorCounter >0 ){
    $is_success = false;
    $error_msg .= "Houve " . $errorCounter . " erros durante a execução da inserção de técnico";
} else {
    $is_success = true;
    $error_msg .= "";
}

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}

if(!isset($_POST['criar'])){
    die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg, 'tec_info' => $tecnico]));
}

?>
