<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus jogadores - ".$_SESSION['nomereal'];
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>


<div id="quadro-container">
<div align="center" id="quadroTimes">
<button id='importar_time' onclick="window.location='/jogadores/criar_jogador.php';">Criar jogador</button>
<button id='importar_time' onclick="window.location='/jogadores/importar_jogador.php';">Importar jogador</button>
<h2>Quadro de jogadores - <?php echo $_SESSION['nomereal']?></h2>
<hr>

<?php

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 18;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$time = new Time($db);
$jogador = new Jogador($db);
$pais = new Pais($db);

// query caixa de seleção países desse dono
$stmtPais = $pais->read();
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $sigla);
    $listaPaises[] = $addArray;
}

// query caixa de seleção de posições
$stmtPos = $jogador->selectPosicoes();
$listaPosicoes = array();
while ($row_pos = $stmtPos->fetch(PDO::FETCH_ASSOC)){
    extract($row_pos);
    $addArray = array($ID, $Sigla);
    $listaPosicoes[] = $addArray;
}

//query de jogadores
$stmt = $jogador->readAll($from_record_num, $records_per_page, $_SESSION['user_id']);

$num = $stmt->rowCount();

// the page where this paging is used
$page_url = "meusjogadores.php?";

    // count all products in the database to calculate total pages
    $total_rows = $jogador->countAll($_SESSION['user_id']);


    // paging buttons here
    echo "<div style='clear:both;'></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");

echo "<hr>";
echo "<div id='errorbox'></div>";

// display the products if there are any
if($num>0){

    echo "<table id='tabelaPrincipal' class='table'>";
    echo "<thead id='tabela".$_SESSION['user_id']."'>";
        echo "<tr>";
           // echo "<th>Id</th>";
            echo "<th>Nome</th>";
            echo "<th>Nascimento (idade)</th>";
            echo "<th>Mentalidade</th>";
            echo "<th>Cobrança de Falta</th>";
            echo "<th>Valor</th>";
            echo "<th>Posições</th>";
            echo "<th>Nivel</th>";
            echo "<th>Determinação</th>";
            echo "<th class='wide'>País</th>";
            echo "<th class='wide'>Clube</th>";
            echo "<th>Status</th>";
            //echo "<th class='wide'>Emp.</th>";
            //echo "<th class='wide'>Sel.</th>";
            echo "<th >Opções</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            //verificar posicoes
            $posicoes = $jogador->listaPosicoes($StringPosicoes);

            $agora = date('Y-m-d');



            $nascimento = date_format(date_create_from_format('Y-m-d', $Nascimento),'d-m-Y');

            $valorDisplay = "F$ " . ($valor/1000) . " k";

            //$escudoVinculado = explode(".",$escudoClubeVinculado);

            if($sexo == 0){
                $genderCode = "M";
                $genderClass = "genderMas";
            } else {
                $genderCode = "F";
                $genderClass = "genderFem";
            }


            echo "<tr id='".$ID."'>";
                //echo "<td><span id=".$id.">{$id}</span></td>";
                echo "<td><a class='linkNome' href='/ligas/playerstatus.php?player={$ID}'><span class='nomeEditavel' id='nom".$ID."'>{$Nome}</span><span class=' {$genderClass} genderSign'>{$genderCode}</span></a></td>";
                echo "<td><span class='nomeNascimento' id='nas".$ID."'>{$nascimento} ({$Idade})</span><input id='selnas".$ID."' class='nascimentoEditavel editavel' type='date' value='{$Nascimento}' hidden/></td>";
                echo "<td><span class='nomeMentalidade' id='men".$ID."'>{$Mentalidade}</span><select class='comboMentalidade editavel'  id='selmen".$ID."' hidden >";
                $listaMentalidade = $jogador->listaMentalidade();
                while($resultMentalidade = $listaMentalidade->fetch(PDO::FETCH_ASSOC)){
                    echo "<option value='{$resultMentalidade['ID']}'>{$resultMentalidade['Nome']}</option>";
                }
                echo "</select></td>";
                echo "<td><span class='nomeCobrador' id='cob".$ID."'>{$CobradorFalta}</span><select class='comboCobrador editavel' id='selcob".$ID."' hidden >";
                $listaCobrador = $jogador->listaCobradorFalta();
                while($resultCobrador = $listaCobrador->fetch(PDO::FETCH_ASSOC)){
                    echo "<option value='{$resultCobrador['ID']}'>{$resultCobrador['Nome']}</option>";
                }
                echo "</select></td>";
                echo "<td><span class='nomeValor' id='val".$ID."'>{$valorDisplay}</span><span class='valorEditavel editavel' contenteditable='true' hidden>{$valor}</span></td>";
                echo "<td><span class='nomePosicao posicoesAtuais' id='pos".$ID."'>{$posicoes}</span>";
                echo " <select multiple class='comboPosicoes editavel ' hidden>'  ";
                //echo "<option>Selecione país...</option>";
                for($i = 0; $i < count($listaPosicoes);$i++){
                    echo "<option value='{$listaPosicoes[$i][0]}'>{$listaPosicoes[$i][1]}</option>";
                }
                echo "</select>";
                echo "</td>";
                echo "<td><span class='nivelEditavel' id='niv".$ID."'>{$Nivel}</span></td>";
                echo "<td><span class='nomeEditavel nomeDeterminacao' id='det".$ID."'>{$determinacaoOriginal}</span><select class='comboDeterminacao editavel' id='seldet".$ID."' hidden ><option value='1'>1</option><option value='2'>2</option><option value='3'>3</option><option value='4'>4</option><option value='5'>5</option></select></td>";
                if($idPais != 0){
                    echo "<td class='wide'><img src='/images/bandeiras/{$bandeiraPais}' class='bandeira nomePais' id='ban".$ID."'>  <span class='nomePais' id='pai".$ID."'>{$siglaPais}</span>";
                } else {
                    echo "<td>";
                }
                echo " <select class='comboPais editavel ' id='{$idPais}' hidden >'  ";
                //echo "<option>Selecione país...</option>";
                for($i = 0; $i < count($listaPaises);$i++){
                    echo "<option value='{$listaPaises[$i][0]}'>{$listaPaises[$i][1]}</option>";
                }
                echo "</select>";
                echo "</td>";
                if($clubeVinculado != null){
                    echo "<td><a href='/ligas/teamstatus.php?team={$idClubeVinculado}' id='dis".$ID."'><img class='minithumb' src='/images/escudos/{$escudoClubeVinculado}'>{$clubeVinculado}</a><span class='donoClubeVinculado' hidden>{$donoClubeVinculado}</span></td>";
                } else {
                    echo "<td>";
                }

                echo "</td>";
				
				switch($disponibilidade){
					case -2:
						$nomeDisponibilidade = "Expatriado";
						break;
					case -1:
						$nomeDisponibilidade = "Aposentado";
						break;
					case 0:
						$nomeDisponibilidade = "Ativo";
						break;
					case 1:
						$nomeDisponibilidade = "Ativo (disponível)";
						break;
				}
				

                echo "<td><span class='nomeAtividade' id='dis".$ID."'>".$nomeDisponibilidade."</span><select data-idTime='{$idClubeVinculado}' class='comboAtividade editavel' id='seldis".$ID."' hidden >
				<option value='1' title='Ativo e disponível para negociar'>Ativo (disponível)</option>
				<option value='0' title='Ativo'>Ativo</option>
				<option value='-1' title='Aposentado, não pode ser contratado'>Aposentado</option>
				<option value='-2' title='Jogando em clubes fora do Portal, não pode ser contratado'>Expatriado</option>
				</select></td>";
                // if($clubeEmprestimo != null){
                //     echo "<td><span class='nomeEditavel' id='dis".$id."'>{$clubeEmprestimo}</span></td>";
                // } else {
                //     echo "<td>";
                // }
                //     echo "</td>";

                // if($clubeSelecao != null){
                //     echo "<td><span class='nomeEditavel' id='dis".$id."'>{$clubeSelecao}</span></td>";
                // } else {
                //     echo "<td>";
                // }
                //     echo "</td>";
                $optionsString = "<td>";
                if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

                    $optionsString .= "<a id='dem".$ID."' title='Editar jogador' class='clickable editar'><i class='fas fa-edit inlineButton azul'></i></a>";
                    $optionsString .= "<a id='apa".$ID."' title='Apagar' class='clickable apagar'><i class='fas fa-trash-alt inlineButton negativo'></i></a>";
                    $optionsString .= "<a hidden id='sal".$ID."' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
                    $optionsString .= "<a hidden id='can".$ID."' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton negativo'></i></a>";
                }

                    $optionsString .= "</td>";
                    echo $optionsString;
                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há jogadores</div>";
}

echo('</div>');
echo('</div>');

?>

<script>

$(".editar").on("click", function(){
var tbl_row = $(this).closest("tr");

tbl_row.find('a').each(function(index, val){
    $(this).attr('original_entry', $(this).html());
});

tbl_row.find('span').each(function(index, val){
    $(this).attr('original_entry', $(this).html());
});

tbl_row.find('input').each(function(index, val){
    $(this).attr('data-original-entry', $(this).val());
});

tbl_row.find(".salvar").show();
tbl_row.find(".cancelar").show();
tbl_row.find(".editar").hide();
tbl_row.find(".apagar").hide();

//garantir que o dono do time está logado e que ele é o dono do jogador também (duplo check, JS e PHP)
var donoTime = tbl_row.find(".donoClubeVinculado").html();
var donoJogador = $("#tabelaPrincipal").find('thead').prop("id").replace(/\D/g, "");
//var donoJogador =9;

if (typeof donoTime === 'undefined'){
    donoTime = donoJogador;
}

if(donoTime.localeCompare(donoJogador) == 0){
    var isDono = true;
} else {
    var isDono = false;
}

if(isDono){
    tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
    tbl_row.find('.linkNome').css("cursor","text");
    tbl_row.find('.linkNome').css("pointer-events","none");
    tbl_row.find('.comboCobrador').show();
    tbl_row.find('.comboMentalidade').show();
    tbl_row.find('.comboDeterminacao').show();
    tbl_row.find('.comboAtividade').show();
    tbl_row.find('.nomeCobrador').hide();
    tbl_row.find('.nomeMentalidade').hide();
    tbl_row.find('.nomeDeterminacao').hide();
    tbl_row.find('.nomePais').hide();
    tbl_row.find('.nomeAtividade').hide();
    tbl_row.find('.nomeNascimento').hide();
    tbl_row.find('.nascimentoEditavel').show();
    tbl_row.find('.nomeValor').hide();
    tbl_row.find('.valorEditavel').show();

    var paisId = tbl_row.find('.comboPais').attr('id');
    tbl_row.find('.comboPais').show().val(paisId);
}


tbl_row.find('.nivelEditavel').attr('contenteditable', 'true').addClass('editavel');

tbl_row.find('.comboCobrador option').filter(function() {
    return $(this).text() == tbl_row.find('.nomeCobrador').html();
}).prop("selected", true);

tbl_row.find('.comboMentalidade option').filter(function() {
    return $(this).text() == tbl_row.find('.nomeMentalidade').html();
}).prop("selected", true);

tbl_row.find('.comboDeterminacao').val(tbl_row.find('.nomeDeterminacao').html());

tbl_row.find('.comboAtividade option').filter(function() {
    return $(this).text() == tbl_row.find('.nomeAtividade').html();
}).prop("selected", true);

//verificar se é goleiro
var stringPosicoes = tbl_row.find('.posicoesAtuais').html();
var isGoleiro = stringPosicoes.localeCompare("G");

if(isGoleiro){
    tbl_row.find('.posicoesAtuais').hide();
    tbl_row.find('.comboPosicoes').show();
}

//valor original posicoes
var arrPosicoes = stringPosicoes.split('-');

tbl_row.find('.comboPosicoes option').each(function(){

    if($.inArray($(this).html(), arrPosicoes) !== -1){
        $(this).prop("selected","selected");
    } else {
        $(this).prop("selected", false);
    }
});

});

$('.cancelar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find(".salvar").hide();
        tbl_row.find(".cancelar").hide();
        tbl_row.find(".editar").show();
        tbl_row.find(".apagar").show();
        tbl_row.find('.linkNome').css("cursor","pointer");
        tbl_row.find('.linkNome').css("pointer-events","auto");

        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nivelEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.comboCobrador').hide();
        tbl_row.find('.comboMentalidade').hide();
        tbl_row.find('.comboDeterminacao').hide();
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.comboAtividade').hide();
        tbl_row.find('.nomeCobrador').show();
        tbl_row.find('.nomeMentalidade').show();
        tbl_row.find('.nomeDeterminacao').show();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.nomeAtividade').show();
        tbl_row.find('.nomeNascimento').show();
        tbl_row.find('.nascimentoEditavel').hide();
        tbl_row.find('.nomeValor').show();
        tbl_row.find('.valorEditavel').hide();
        tbl_row.find('.nomePosicao').show();
        tbl_row.find('.comboPosicoes').hide();

        tbl_row.find('a').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });

        tbl_row.find('span').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });

        tbl_row.find('input').each(function(index, val){
            $(this).val($(this).attr('data-original-entry'));
        });
    });

    $('.apagar').click(function(){
        var tbl_row =  $(this).closest('tr');
        var jogadorId = tbl_row.prop('id');
        var r = confirm("Você tem certeza que deseja apagar esse jogador? Essa ação não pode ser desfeita!");
        if (r) {
            $.ajax({
                type: "POST",
                url: '/jogadores/apagar_jogador.php',
                data: {jogadorId:jogadorId},
                dataType: 'json',
                success: function(data) {
                  console.log(data.error);
                  if(!data.success){
                    $('#errorbox').append('<div class="alert alert-danger">Não foi possível apagar o jogador. '+ data.error +'</div>');
                  } else {
                    location.reload();
                  }


                },
                error: function(data) {
                    successmessage = 'Error';
                    $('#errorbox').append('<div class="alert alert-danger">Não foi possível apagar o jogador. '+data.error+'</div>');
                }
            });
        }


    });

    $('.salvar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find(".salvar").hide();
        tbl_row.find(".cancelar").hide();
        tbl_row.find(".editar").show();
        tbl_row.find(".apagar").show();
        tbl_row.find('.linkNome').css("cursor","pointer");
        tbl_row.find('.linkNome').css("pointer-events","auto");
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nivelEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.comboCobrador').hide();
        tbl_row.find('.comboMentalidade').hide();
        tbl_row.find('.comboDeterminacao').hide();
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.comboAtividade').hide();
        tbl_row.find('.nomeCobrador').show();
        tbl_row.find('.nomeMentalidade').show();
        tbl_row.find('.nomeDeterminacao').show();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.nomeAtividade').show();
        tbl_row.find('.nomeNascimento').show();
        tbl_row.find('.nascimentoEditavel').hide();
        tbl_row.find('.nomeValor').show();
        tbl_row.find('.valorEditavel').hide();
        tbl_row.find('.nomePosicao').show();
        tbl_row.find('.comboPosicoes').hide();

        //coleta de valores

        //check se é dono do jogador
        //garantir que o dono do time está logado e que ele é o dono do jogador também (duplo check, JS e PHP)
        var donoTime = tbl_row.find(".donoClubeVinculado").html();
        var donoJogador = $("#tabelaPrincipal").find('thead').prop("id").replace(/\D/g, "");
        //var donoJogador =9;

        if (typeof donoTime === 'undefined'){
            donoTime = donoJogador;
        }

        if(donoTime.localeCompare(donoJogador) == 0){

            var isDono = true;
        } else {
            var isDono = false;
        }

        var idJogador = tbl_row.prop('id');

        if(isDono){
            var nome = tbl_row.find('.nomeEditavel').html();
            var nacionalidade = tbl_row.find(".comboPais").val();
            var nascimento = tbl_row.find(".nascimentoEditavel").val();
            var valor = parseInt(tbl_row.find(".valorEditavel").html());
            var determinacao = tbl_row.find(".comboDeterminacao").val();
            var mentalidade = tbl_row.find(".comboMentalidade").val();
            var cobrancaFalta = tbl_row.find(".comboCobrador").val();
            var atividade = tbl_row.find(".comboAtividade").val();
			var timeParaDemissao = tbl_row.find(".comboAtividade").attr("data-idTime");
        }

        var nivel = tbl_row.find(".nivelEditavel").html();
        var posicoes = tbl_row.find(".comboPosicoes").val();
        //var idTime = $('#quadro-container').prop('class');

        var formData = {
            'idJogador' : idJogador,
            'alteracao' : 9,
            'posicoes' : posicoes,
            'nivel' : nivel
        }



if(isDono){
    var moreData = {
            'nome' : nome,
            'nacionalidade' : nacionalidade,
            'nascimento' : nascimento,
            'valor' : valor,
            'determinacao' : determinacao,
            'mentalidade' : mentalidade,
            'cobrancaFalta' : cobrancaFalta,
            'atividade' : atividade,
			'timeParaDemissao' : timeParaDemissao

        }

    $.extend(formData,moreData);
}


console.log(formData);

     ajaxCallJogador(formData);


    });


function ajaxCallJogador(formData){

$.ajax({
        type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
        url         : '/jogadores/editar_jogador.php', // the url where we want to POST
        data        : formData, // our data object
        // processData : false,
        // contentType : false,
        dataType    : 'json', // what type of data do we expect back from the server
                    encode          : true
    })

                .done(function(data) {

        // log data to the console so we can see
        console.log(data);


        if (! data.success) {
            window.scrollTo(0, 0);
            $('#modalProposta').hide();
            $('#errorbox').append('<div class="alert alert-danger">Não foi possível editar o jogador, '+data.error+'</div>');


        } else {

        $('#modalProposta').hide();
            //$('#errorbox').append("<div class='alert alert-success'>A ação foi concluída com sucesso!</div>");

            location.reload();

        }

        // here we will handle errors and validation messages
        }).fail(function(jqXHR, textStatus, errorThrown ){
            console.log("Erro");
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
            $('#modalProposta').hide();
            $('#errorbox').append('<div class="alert alert-danger">Não foi possível editar o jogador, '+errorThrown+'</div>');
        });
}



</script>


<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
