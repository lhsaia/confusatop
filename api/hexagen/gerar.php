<?php
/**
 * API Hexagen - Geração de Pessoas
 * /api/hexagen/gerar.php
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Incluir classes necessárias
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();

$apiKey = isset($_GET['apiKey']) ? $_GET['apiKey'] : '';
$usuario = new Usuario($db);
$user_id = $usuario->checkApiKey($apiKey);

if(!$user_id){
    http_response_code(403);
    echo json_encode(['mensagem' => "Not authenticated. Invalid or missing apiKey."]);
    exit();
}

$jogador = new Jogador($db);
$pais = new Pais($db);

// Coletar parâmetros da query string (GET)
$cod_pais = isset($_GET['pais']) ? $_GET['pais'] : null;
$qtde = isset($_GET['quantidade']) ? intval($_GET['quantidade']) : 1;
$genero = isset($_GET['genero']) ? strtolower($_GET['genero']) : 'misto'; // masculino, feminino, misto
$nivel_min = isset($_GET['nivel_min']) ? intval($_GET['nivel_min']) : 1;
$nivel_max = isset($_GET['nivel_max']) ? intval($_GET['nivel_max']) : 99;
$nivel_med = isset($_GET['nivel_med']) ? intval($_GET['nivel_med']) : null;

// Validação básica
if ($qtde < 1 || $qtde > 100) {
    http_response_code(400);
    echo json_encode(array("mensagem" => "Quantidade deve ser entre 1 e 100."));
    exit();
}

if (!in_array($genero, ['masculino', 'feminino', 'misto'])) {
    http_response_code(400);
    echo json_encode(array("mensagem" => "Gênero inválido. Use 'masculino', 'feminino' ou 'misto'."));
    exit();
}

$pessoas_geradas = array();

for ($i = 0; $i < $qtde; $i++) {
    
    // Determinar sexo para esta iteração
    $sexo_atual = null;
    if ($genero === 'misto') {
        $sexo_atual = (rand(0, 1) === 0) ? '0' : '1'; // 0 = Masculino, 1 = Feminino (ajustar conforme lógica db, assumindo 0 paramasc e 1 pra fem no Pais)
    } else if ($genero === 'masculino') {
        $sexo_atual = '0';
    } else if ($genero === 'feminino') {
        $sexo_atual = '1';
    }

    // Determinar nacionalidade (país)
    $nacionalidade_atual = $cod_pais;
    if (empty($nacionalidade_atual)) {
        // Se não fornecido, sorteia uma nacionalidade aleatória.
        // O método sorteiaNacionalidade costuma receber ID de dono para filtrar, mas podemos passar 0 ou null se for geral, vamos ver como a classe se comporta
        // Passando 0 para pegar qualquer país
        $nacionalidade_atual = $pais->sorteiaNacionalidade(0); 
    } else {
        // se a string de código for passada, precisa achar o id pelo país (sigla)
       if(!is_numeric($nacionalidade_atual)){
            $id_pais = $pais->idPorSigla($nacionalidade_atual);
            if(empty($id_pais)){
                http_response_code(404);
                echo json_encode(array("mensagem" => "País não encontrado."));
                exit();
            }
            $nacionalidade_atual = $id_pais;
       }
    }

    // Sorteio demográfico para pegar origem dos nomes baseados na nacionalidade
    $origemNomes = $pais->sorteioDemografico($nacionalidade_atual, 0, $sexo_atual);
    $origemSobrenomes = $pais->sorteioDemografico($nacionalidade_atual, 1, $sexo_atual);
    $indiceMiscigenacao = $pais->verificarMiscigenacao($nacionalidade_atual, $origemNomes);
    $ocorrenciaNomeDuplo = $pais->verificarNomeDuplo($nacionalidade_atual, $origemNomes);

    // Variáveis default para o jogador que não precisamos na API simplificada
    $idadeMin = 16;
    $idadeMax = 35;
    $idadeMed = null;
    $codigoPosicao = null; // Goleiro, Defensor, etc. Vamos deixar randomico na classe jogador passando null

    // Gerar o jogador (pessoa)
    $jogador->randomPlayer($codigoPosicao, $nacionalidade_atual, $origemNomes, $origemSobrenomes, $idadeMin, $idadeMax, $nivel_min, $nivel_max, $nivel_med, $idadeMed, $ocorrenciaNomeDuplo, $indiceMiscigenacao, $sexo_atual);

    $pessoa = array(
        "nome" => $jogador->nomeJogador,
        "genero" => ($sexo_atual == '0' || $sexo_atual == 0) ? "masculino" : "feminino",
        "nivel" => $jogador->nivel,
        "pais_id" => $nacionalidade_atual
    );

    array_push($pessoas_geradas, $pessoa);
}

http_response_code(200);
echo json_encode(array(
    "quantidade_gerada" => count($pessoas_geradas),
    "pessoas" => $pessoas_geradas
));
?>
