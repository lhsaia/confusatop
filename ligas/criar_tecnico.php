<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// include database and object files
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

// get database connection
$database = new Database();
$db = $database->getConnection();

// pass connection to objects
$tecnico = new Tecnico($db);
$pais = new Pais($db);
$usuario = new Usuario($db);

$page_title = "Criar Técnico";
$css_filename = "newindex";
$css_login = 'login';
$aux_css = 'area_competicao';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");



if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    $error_msg = '';


// se jogador foi submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar'])){
if(isset($_POST['nome']) && isset($_POST['nascimento']) && $_POST['pais'] != 0){


    // atributos basicos dos jogadores
    $tecnico->nome = $_POST['nome'];
    $tecnico->nascimento = $_POST['nascimento'];
    $tecnico->mentalidade = $_POST['mentalidade'];
    $tecnico->estilo = $_POST['estilo'];
    $tecnico->pais = $_POST['pais'];
    $tecnico->nivel = $_POST['nivel'];
    $tecnico->sexo = $_POST['sexo'];


    // create the product
    if($tecnico->create(true)){
        $usuario->atualizarAlteracao($_SESSION['user_id']);
        echo "<div class='alert alert-success alert-btn'><span class='closebtn'>&times;</span>Técnico inserido com sucesso!</div>";
    }

    // if unable to create the product, tell the user
    else{
        echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Houve um erro ao inserir o técnico!</div>";
    }
}  else {

    echo "<div class='alert alert-danger alert-btn'><span class='closebtn'>&times;</span>Houve um erro ao inserir técnico, campos em branco!</div>";
}
}
?>

<script type="application/javascript">
var close = document.getElementsByClassName("closebtn");
var i;

for (i = 0; i < close.length; i++) {
    close[i].onclick = function(){
        var div = this.parentElement;
        div.style.opacity = "0";
        setTimeout(function(){ div.style.display = "none"; }, 600);
    }
}
</script>


<div class="bg"></div><div class="bg bg2"></div><div class="bg bg3"></div>
<div id='errorbox'></div>
<div>
<div id='inscricao'>

<form method="POST" enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>'>

    

        <label for="nomeTecnico">Nome</label>
<input type='text' name='nome' id='nomeTecnico' class='form-control inputHerdeiro' />

        <label for="nascimentoTecnico">Nascimento</label>
<input type='date' id='nascimentoTecnico' name='nascimento' class='form-control inputHerdeiro' />

        <label for="mentalidade">Mentalidade</label>
<select class='form-control' id='mentalidade' name='mentalidade'>


                        <option value='1'>Retranca</option>
                        <option value='2'>Defensiva</option>
                        <option selected value='3'>Balanceada</option>
                        <option value='4'>Ofensiva</option>
                        <option value='5'>Ataque Total</option>

                    </select>

        <label for="estilo">Estilo</label>
<select class='form-control' id='estilo' name='estilo'>


                        <option value='1'>Explorar contra-ataques</option>
                        <option value='2'>Cadenciar o jogo</option>
                        <option selected value='3'>Neutro</option>
                        <option value='4'>Atacar pelas laterais</option>
                        <option value='5'>Impôr ritmo ofensivo</option>

                    </select>

        <label for="nivel">Nivel</label>
<input type='number' id='nivel' value='6' max='10' min='1' name='nivel' class='form-control inputHerdeiro' />

        <label for="sexo">Masc/Fem</label>
<select class='form-control' id='sexo' name='sexo'>
                <option value='0'>Homem</option>
                <option value='1'>Mulher</option>
                </select>

        <label for="pais">Nacionalidade</label>
<?php
                // ler times do banco de dados
                $stmt = $pais->read();

                // put them in a select drop-down
                echo "<select class='form-control' id='pais' name='pais'>";
                echo "<option value='0'>-</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    echo "<option value='{$id}'>{$nome}</option>";
                }

                echo "</select>";
                ?>

        <div style="margin-top: 15px;">
<button type="submit" name="criar" class="btn">Inserir</button>
                <button type="reset" name="reset" class="btn">Limpar</button>
                <button type='button' id="hexagen" class="btn"><span class="material-symbols-outlined">casino</span>&nbsp Hexagen</button>
</div>

    </form>
</div>
</div>

  <script>

  $(function () {
  $("#nivel").keydown(function () {
    // Save old value.
    if (!$(this).val() || (parseInt($(this).val()) <= 10 && parseInt($(this).val()) >= 1))
    $(this).data("old", $(this).val());
  });
  $("#nivel").keyup(function () {
    // Check correct, else revert back to old value.
    if (!$(this).val() || (parseInt($(this).val()) <= 10 && parseInt($(this).val()) >= 1))
      ;
    else
      $(this).val($(this).data("old"));
  });
});

$("#hexagen").on("click",function(){
    var nacionalidade = $("#pais").val();
    var sexo = $("#sexo").val();

    var formData = {
        'nacionalidade' : nacionalidade,
        'sexo' : sexo
    }

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/ligas/hexagen_tecnico.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
            })

                    .done(function(data) {

            // log data to the console so we can see
            console.log(data);


            if (data.success) {
                //preencher campos
                $("#nomeTecnico").val(data.tec_info.nome);
                $("#nascimentoTecnico").val(data.tec_info.nascimento);
                $("#mentalidade").val(data.tec_info.mentalidade);
                $("#estilo").val(data.tec_info.estilo);
                $("#nivel").val(data.tec_info.nivel);
                $("#pais").val(data.tec_info.pais);

            }

            // here we will handle errors and validation messages
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

    echo "Usuário sem permissão para criar técnicos, por favor faça o login.";
}




include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
