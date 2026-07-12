<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Minhas ligas - ".($_SESSION['nomereal'] ?? '');
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
?>

<style>
    #ranking-container {
        padding: 40px 20px;
        background: #f4f7f6;
        min-height: 100vh;
    }
    
    #quadroTimes {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    h2 {
        font-family: 'Outfit', 'Inter', sans-serif;
        font-weight: 700 !important;
        color: #1a1469 !important;
        margin-bottom: 30px;
        position: relative;
        padding-bottom: 15px;
        letter-spacing: -0.5px;
        text-align: left;
    }
    
    h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 4px;
        background: linear-gradient(90deg, #1a1469, #4c41d1);
        border-radius: 2px;
    }
    
    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        overflow: hidden;
        background: #fff;
        padding: 20px;
    }
    
    .table thead th {
        background: #f8fafc;
        border-bottom: 2px solid #edf2f7;
        color: #718096;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85em;
        letter-spacing: 0.5px;
    }
    
    .table td {
        vertical-align: middle;
        color: #2d3748;
    }
    
    .btn-action {
        padding: 8px 20px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none !important;
    }
    
    .btn-create {
        background: #4c41d1;
        color: #fff !important;
    }
    
    .btn-create:hover {
        background: #3a32a8;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 65, 209, 0.3);
    }
    
    .btn-nav {
        background: #1a1469;
        color: #fff !important;
    }
    
    .btn-nav:hover {
        background: #2a228a;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(26, 20, 105, 0.3);
    }
    
    .genderSign {
        font-size: 0.75em;
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 8px;
        font-weight: 700;
    }
    
    .genderMas { background: #e0f2fe; color: #0369a1; }
    .genderFem { background: #fce7f3; color: #be185d; }
    
    .logoimage {
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .tier-badge {
        background: #f1f5f9;
        color: #475569;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9em;
    }
</style>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>


<div id="ranking-container">
    <div id="quadroTimes">
        <div class="header-actions">
            <h2>Quadro de Ligas</h2>
            <div class="d-flex" style="gap: 10px;">
                <a href="/ligas/gerenciador/gerenciar_competicoes.php" class="btn-action btn-nav"><span class="material-symbols-outlined">trophy</span> Minhas Copas</a>
                <a href="/ligas/criar_liga.php" class="btn-action btn-create"><span class="material-symbols-outlined">add_circle</span> Criar Liga</a>
            </div>
        </div>
        
        <div id='error_box'></div>
        
        <div class="card">


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
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$time = new Time($db);
$pais = new Pais($db);
$liga = new Liga($db);

// query caixa de seleção países desse dono
$stmtPais = $pais->read($_SESSION['user_id']);
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $nome);
    $listaPaises[] = $addArray;
}

//queries de ligas e estadios

//query de ligas
$stmt = $liga->readAll($from_record_num, $records_per_page, $_SESSION['user_id']);

$num = $stmt->rowCount();

// the page where this paging is used
$page_url = "minhasligas.php?";

    // count all products in the database to calculate total pages
    $total_rows = $liga->countAll($_SESSION['user_id']);


    // paging buttons here
    echo "<div class='d-flex justify-content-center my-3'>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");
    echo "</div>";

// display the products if there are any
if($num>0){


    echo "<table id='tabelaPrincipal' class='table'>";
    echo "<thead>";
        echo "<tr>";
           // echo "<th>Id</th>";
            echo "<th width='30%'>Liga</th>";
            echo "<th width='20%'>Logo</th>";
            echo "<th width='10%'>Tier</th>";
            echo "<th width='20%'class='wide'>País</th>";
            echo "<th width='20%' class='wide'>Opções</th>";

        echo "</tr>";
        echo "</thead>";

        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            $logo = ($logo == "0" ? "0.png" : $logo);
            if($sexo == 0){
                $genderCode = "M";
                $genderClass = "genderMas";
            } else {
                $genderCode = "F";
                $genderClass = "genderFem";
            }

            echo "<tr id='".$id."' data-sexo='".$sexo."'>";
                //echo "<td><span id=".$id.">{$id}</span></td>";
                echo "<td><span class='nomeEditavel' id='nom".$id."'><a class='nomeLiga fw-bold' href='../ligas/leaguestatus.php?league=".$id."' style='text-decoration:none; color:#1a1469;'>{$nome}</a></span><span class=' {$genderClass} genderSign'>{$genderCode}</span></td>";
                echo "<td><img class='logoimage' id='log".$id."' src='../images/ligas/".$logo."' height='35px'/><div class='newlogoedit' hidden> <input type='file' id='newlogo".$id."' class=' custom-file-upload' name='file' accept='.jpg,.png,.jpeg'/></div></td>";
                echo "<td><span class='tier-badge nomeEditavel' id='tie".$id."'>{$tier}</span></td>";
                if($idPais != 0){
                    echo "<td class='wide'><img src='/images/bandeiras/{$bandeiraPais}' class='bandeira nomePais' id='ban".$id."'>  <span class='nomePais' id='pai".$id."'>{$siglaPais}</span>";
                } else {
                    echo "<td>";
                }
                echo " <select class='comboPais editavel ' id='{$idPais}' hidden>'  ";
                    //echo "<option>Selecione país...</option>";
                    for($i = 0; $i < count($listaPaises);$i++){
                        echo "<option value='{$listaPaises[$i][0]}'>{$listaPaises[$i][1]}</option>";
                    }
                    echo "</select>";
                    echo "</td>";
                    $optionsString = "<td class='wide'>";

                        $optionsString .= "<a id='edi".$id."' title='Editar' class='clickable editar'><span class='material-symbols-outlined inlineButton'>edit</span></a>";
                        $optionsString .= "<a hidden id='sal".$id."' title='Salvar' class='clickable salvar'><span class='material-symbols-outlined inlineButton positive'>check</span></a>";
                        $optionsString .= "<a hidden id='can".$id."' title='Cancelar' class='clickable cancelar'><span class='material-symbols-outlined inlineButton negative'>close</span></a>";
                        $optionsString .= "<a id='del".$id."' title='Deletar' class='clickable deletar'><span class='material-symbols-outlined inlineButton negative'>delete</span></a>";
                        $optionsString .= "<a id='dra".$id."' title='Criar jogadores para draft' class='clickable draftar'><span class='material-symbols-outlined inlineButton'>pan_tool_alt</span></a>";
                    $optionsString .= "</td>";
                    echo $optionsString;

                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há ligas</div>";
}

        echo "</div>";
    echo "</div>";
echo "</div>";

?>

<script>

    $(document).ready(function() {

         $('.editar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('span').each(function(index, val){
            $(this).attr('original_entry', $(this).html());

        });
        tbl_row.find('.nomeEditavel').css("cursor","text");
        tbl_row.find('.nomeLiga').css("cursor","text");
        tbl_row.find('.nomeLiga').css("pointer-events","none");
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
        tbl_row.find('.salvar').show();
        tbl_row.find('.cancelar').show();
        tbl_row.find('.editar').hide();
        tbl_row.find('.deletar').hide();
        tbl_row.find('.nomePais').hide();
        tbl_row.find('.newlogoedit').show();
        tbl_row.find('.logoimage').hide();

        var paisId = tbl_row.find('.comboPais').attr('id');
        tbl_row.find('.comboPais').show().val(paisId);

    });

        $('.cancelar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.nomeLiga').css("pointer-events","auto");
        tbl_row.find('.nomeLiga').css("cursor","auto");
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
        tbl_row.find('.deletar').show();
        tbl_row.find('.newlogoedit').hide();
        tbl_row.find('.logoimage').show();

        tbl_row.find('span').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });
    });

    $('.salvar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.nomeLiga').css("pointer-events","auto");
        tbl_row.find('.nomeLiga').css("cursor","auto");
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
        tbl_row.find('.deletar').show();
        tbl_row.find('.newlogoedit').hide();
        tbl_row.find('.logoimage').show();

        var id = tbl_row.attr('id');
        var nomeLiga = tbl_row.find('#nom'+id).html();
        var tierLiga = tbl_row.find('#tie'+id).html();
        var pais = tbl_row.find('.comboPais').val();

        var input = (tbl_row.find('#newlogo'+id))[0];
        var logo;

        if (input.files.length > 0) {
           logo = input.files[0];
        } else {
           logo = null;
        }

        //var formId = 'form'+id;
        //var form = document.getElementById(formId);
         var formData = new FormData();
         formData.append('id', id);
         formData.append('nomeLiga', nomeLiga);
         formData.append('tierLiga', tierLiga);
         formData.append('pais', pais);
         if(logo != null){
            formData.append('logo', logo);
         }


    // for (var key of formData.entries()) {
    //      console.log(key[0] + ', ' + key[1]);
    //  }

        //console.log(formData);
         $.ajax({
             url: 'alterar_liga.php',
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

$('.deletar').click(function(){
        var ligaId = $(this).attr("id").replace(/\D/g, "");
        var r = confirm("Você tem certeza que deseja apagar essa liga?");
        if (r) {
            $.ajax({
                type: "POST",
                url: '/ligas/apagar_liga.php',
                data: {ligaId:ligaId},
                success: function(data) {
                    successmessage = 'Deu certo'; // modificar depois
                    //$("label#successmessage").text(successmessage);
                    location.reload();
                },
                error: function(data) {
                    successmessage = 'Error';
                    alert("Erro, o procedimento não foi realizado, tente novamente.");
                }
            });
        }


    });

$('.draftar').click(function(){
    var liga = $(this).closest('tr').attr("id");
    var nacionalidade = $(this).closest('tr').find('.comboPais').attr("id");
    var sexo = $(this).closest('tr').attr('data-sexo');

    var formData = {
        'nacionalidade' : nacionalidade,
        'codigoPosicao' : 0,
        'inserir' : true,
        'base' : true,
        'liga' : liga,
        'sexo' : sexo
    }

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/jogadores/hexagen.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
            })

                    .done(function(data) {

            // log data to the console so we can see
            console.log(data);


            if (data.success) {
                $('#error_box').append('<div class="alert alert-success">Os jogadores foram criados com sucesso!</div>');
            } else {
                $('#error_box').append('<div class="alert alert-danger">Não foi possível realizar a inserção, '+data.error+'</div>');
            }

}).fail(function(jqXHR, textStatus, errorThrown ){
            console.log("Erro");
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
            });

});



</script>

<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
