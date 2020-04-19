<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus técnicos - ".$_SESSION['nomereal'];
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>


<div id="quadro-container">
<div align="center" id="quadroTimes">
<button  id='importar_time' onclick="window.location='/ligas/criar_tecnico.php';">Criar técnico</button>
<button id='importar_time' onclick="window.location='/import/importar_tecnico.php';">Importar técnico</button>
<h2>Quadro de técnicos - <?php echo $_SESSION['nomereal']?></h2>
<div id='error_box'></div>

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
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$time = new Time($db);
$pais = new Pais($db);
$tecnico = new Tecnico($db);

// query caixa de seleção países desse dono
$stmtPais = $pais->read($_SESSION['user_id']);
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $nome);
    $listaPaises[] = $addArray;
}

//queries de ligas e estadios

//query de estadios
$stmt = $tecnico->readAll($from_record_num, $records_per_page, $_SESSION['user_id']);

$num = $stmt->rowCount();

// the page where this paging is used
$page_url = "meustecnicos.php?";

    // count all products in the database to calculate total pages
    $total_rows = $tecnico->countAll($_SESSION['user_id']);


    // paging buttons here
    echo "<div style='clear:both;'></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");

echo "<hr>";

// display the products if there are any
if($num>0){


    echo "<table id='tabelaPrincipal' class='table'>";
    echo "<thead id='tabela".$_SESSION['user_id']."'>";
        echo "<tr>";
           // echo "<th>Id</th>";
            echo "<th width='30%'>Nome</th>";
            echo "<th width='20%'>Nascimento</th>";
            echo "<th width='10%'>Nível</th>";
            echo "<th width='10%'>Mentalidade</th>";
            echo "<th width='10%'>Estilo</th>";
            echo "<th width='20%'class='wide'>País</th>";
            echo "<th>Clube</th>";
            echo "<th width='20%' class='wide'>Opções</th>";

        echo "</tr>";
        echo "</thead>";

        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            if($Sexo == 0){
                $genderCode = "M";
                $genderClass = "genderMas";
            } else {
                $genderCode = "F";
                $genderClass = "genderFem";
            }


            echo "<tr id='".$ID."'>";
                //echo "<td><span id=".$id.">{$id}</span></td>";
                echo "<td><span class='nomeEditavel' id='nom".$ID."'>{$Nome}</span><span class=' {$genderClass} genderSign'>{$genderCode}</span></td>";
                echo "<td><span class='nomeNascimento' id='nas".$ID."'>{$Nascimento} ({$idade})</span><input id='selnas".$ID."' class='nascimentoEditavel editavel' type='date' value='{$Nascimento}' hidden/></td>";
                echo "<td><span class='nivelEditavel' id='niv".$ID."'>{$Nivel}</span></td>";

                echo "<td class='wide'>";
                echo " <select disabled class='comboMentalidade transpBack' id='{$Mentalidade}'>'  ";
                    //echo "<option>Selecione país...</option>";

                        echo "<option ".($Mentalidade == 1? "selected":"")." value='1'>Retranca</option>";
                        echo "<option ".($Mentalidade == 2? "selected":"")." value='2'>Defensiva</option>";
                        echo "<option ".($Mentalidade == 3? "selected":"")." value='3'>Balanceada</option>";
                        echo "<option ".($Mentalidade == 4? "selected":"")." value='4'>Ofensiva</option>";
                        echo "<option ".($Mentalidade == 5? "selected":"")." value='5'>Ataque Total</option>";

                    echo "</select>";
                    echo "</td>";

                    echo "<td class='wide'>";
                    echo " <select disabled class='comboEstilo transpBack' id='{$Estilo}'>'  ";
                        //echo "<option>Selecione país...</option>";

                            echo "<option ".($Estilo == 1? "selected":"")." value='1'>Explorar contra-ataques</option>";
                            echo "<option ".($Estilo == 2? "selected":"")." value='2'>Cadenciar o jogo</option>";
                            echo "<option ".($Estilo == 3? "selected":"")." value='3'>Neutro</option>";
                            echo "<option ".($Estilo == 4? "selected":"")." value='4'>Atacar pelas laterais</option>";
                            echo "<option ".($Estilo == 5? "selected":"")." value='5'>Impôr ritmo ofensivo</option>";

                        echo "</select>";
                        echo "</td>";


                echo "<td class='wide'><img src='/images/bandeiras/{$bandeiraPais}' class='bandeira nomePais' id='ban".$ID."'>  <span class='nomePais' id='pai".$ID."'>{$siglaPais}</span>";
                echo " <select class='comboPais editavel ' id='{$idPais}' hidden>'  ";
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
                    $optionsString = "<td class='wide'>";

                        $optionsString .= "<a id='edi".$id."' title='Editar' class='clickable editar'><i class='far fa-edit inlineButton'></i></a>";
                        $optionsString .= "<a hidden id='sal".$id."' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
                        $optionsString .= "<a hidden id='can".$id."' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton negative'></i></a>";
                        //$optionsString .= "<a id='del".$id."' title='Deletar' class='clickable deletar'><i class='far fa-trash-alt inlineButton negative'></i></a>";
                    $optionsString .= "</td>";
                    echo $optionsString;

                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há técnicos</div>";
}

echo('</div>');
echo('</div>');

?>

<script>

    $(document).ready(function() {

        $('.editar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('a').each(function(index, val){
            $(this).attr('original_entry', $(this).html());
        });

        tbl_row.find('span').each(function(index, val){
            $(this).attr('original_entry', $(this).html());
        });

        tbl_row.find('input').each(function(index, val){
            $(this).attr('data-original-entry', $(this).val());
        });


        tbl_row.find('.salvar').show();
        tbl_row.find('.cancelar').show();
        tbl_row.find('.editar').hide();

        //garantir que o dono do time está logado e que ele é o dono do jogador também (duplo check, JS e PHP)
        var donoTime = tbl_row.find(".donoClubeVinculado").html();
        var donoJogador = $("#tabelaPrincipal").find('thead').prop("id").replace(/\D/g, "");
        //var donoTime = "9";

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
          // tbl_row.find('.nomeEditavel').css("cursor","text");
          // tbl_row.find('.nomeEditavel').css("pointer-events","none");
          tbl_row.find('.nomePais').hide();

          var paisId = tbl_row.find('.comboPais').attr('id');
          tbl_row.find('.comboPais').show().val(paisId);

          tbl_row.find('.comboEstilo').removeClass('transpBack');
          tbl_row.find('.comboEstilo').prop('disabled', false);
          tbl_row.find('.comboEstilo').addClass('editavel');

          tbl_row.find('.comboMentalidade').removeClass('transpBack');
          tbl_row.find('.comboMentalidade').prop('disabled', false);
          tbl_row.find('.comboMentalidade').addClass('editavel');

          tbl_row.find('.nomeNascimento').hide();
          tbl_row.find('.nascimentoEditavel').show();


        }

        tbl_row.find('.nivelEditavel').attr('contenteditable', 'true').addClass('editavel');

    });

        $('.cancelar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nivelEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.nomeNascimento').show();
        tbl_row.find('.nascimentoEditavel').hide();
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();

        tbl_row.find('a').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });

        tbl_row.find('span').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });

        tbl_row.find('input').each(function(index, val){
            $(this).val($(this).attr('data-original-entry'));
        });

        var estilo = tbl_row.find('.comboEstilo').attr('id').replace(/\D/g, "");
        var mentalidade = tbl_row.find('.comboMentalidade').attr('id').replace(/\D/g, "");
        tbl_row.find('.comboEstilo').addClass('transpBack');
        tbl_row.find('.comboEstilo').prop('disabled', 'disabled');
        tbl_row.find('.comboEstilo').removeClass('editavel');
        tbl_row.find('.comboEstilo').val(estilo);

        tbl_row.find('.comboMentalidade').addClass('transpBack');
        tbl_row.find('.comboMentalidade').prop('disabled', 'disabled');
        tbl_row.find('.comboMentalidade').removeClass('editavel');
        tbl_row.find('.comboMentalidade').val(mentalidade);

    });

    $('.salvar').click(function(){
      var tbl_row =  $(this).closest('tr');
      tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
      tbl_row.find('.nivelEditavel').attr('contenteditable', 'false').removeClass('editavel');
      tbl_row.find('.nomeNascimento').show();
      tbl_row.find('.nascimentoEditavel').hide();
      tbl_row.find('.comboPais').hide();
      tbl_row.find('.nomePais').show();
      tbl_row.find('.salvar').hide();
      tbl_row.find('.cancelar').hide();
      tbl_row.find('.editar').show();

      tbl_row.find('.comboEstilo').addClass('transpBack');
      tbl_row.find('.comboEstilo').prop('disabled', 'disabled');
      tbl_row.find('.comboEstilo').removeClass('editavel');

      tbl_row.find('.comboMentalidade').addClass('transpBack');
      tbl_row.find('.comboMentalidade').prop('disabled', 'disabled');
      tbl_row.find('.comboMentalidade').removeClass('editavel');

        var id = tbl_row.attr('id');

        //check se é dono do jogador
        //garantir que o dono do time está logado e que ele é o dono do jogador também (duplo check, JS e PHP)
        var donoTime = tbl_row.find(".donoClubeVinculado").html();
        var donoJogador = $("#tabelaPrincipal").find('thead').prop("id").replace(/\D/g, "");
        //var donoTime = "9";

        if (typeof donoTime === 'undefined'){
            donoTime = donoJogador;
        }

        if(donoTime.localeCompare(donoJogador) == 0){

            var isDono = true;
        } else {
            var isDono = false;
        }

        var formData = new FormData();

        if(isDono){
            var nome = tbl_row.find('.nomeEditavel').html();
            var nascimento = tbl_row.find(".nascimentoEditavel").val();
            var pais = tbl_row.find('.comboPais').val();
            var estilo = tbl_row.find('.comboEstilo').val();
            var mentalidade = tbl_row.find('.comboMentalidade').val();

            formData.append('pais', pais);
            formData.append('estilo', estilo);
            formData.append('mentalidade', mentalidade);
            formData.append('nascimento', nascimento);
            formData.append('nome', nome);
        }

        var nivel = tbl_row.find(".nivelEditavel").html();
        var alteracao = 9;

         formData.append('idTecnico', id);
         formData.append('nivel', nivel);
         formData.append('alteracao', alteracao);


    for (var key of formData.entries()) {
         console.log(key[0] + ', ' + key[1]);
     }

        //console.log(formData);
         $.ajax({
             url: '/ligas/editar_tecnico.php',
             processData: false,
            contentType: false,
            cache: false,
            type: "POST",
            dataType: 'json',
             data: formData,
                  success: function(data) {
                      if(data.error != ''){
                        alert(data.error)
                      }
                      location.reload();
                  },
                  error: function(data) {
                      successmessage = 'Error';
                      alert("Erro, o procedimento não foi realizado, tente novamente.");
                      location.reload();
                  }
              });
     });

});



</script>

<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
