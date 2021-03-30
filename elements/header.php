<?php

echo "<html class='no-capture' lang='pt-br' xmlns='http://www.w3.org/1999/xhtml' xml:lang='pt-br'>";
echo "<head>";

echo "<title>" . $page_title . "</title>";

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && isset($_SESSION['impersonated'])) {
    $status_style = "block";
    $welcometext = $_SESSION['nomereal'];
    $onclick_log = 'document.getElementById("id02").style.display="block"';
    $title_log = "Log-out";
    $change_pass = "<a class='nav-item' href='/usuario/alterar_senha.php'>Alterar senha</a>";

    if($_SESSION['admin_status'] == '1' && $_SESSION['impersonated'] == false){
      $admin_btn = "<a class='nav-item' href='/admin/criar_usuario.php'>Criar usuário</a>";
      $class_conectado = " admin conectado ";
    } else if($_SESSION['admin_status'] == '1' && $_SESSION['impersonated'] == true){
      $class_conectado = " impersonado conectado ";
	  $admin_btn = "";
	} else {
      $admin_btn = "";
      $class_conectado = " user conectado ";
    }

} else {
  $change_pass = "";
  $status_style = "none";
  $welcometext = "";
  $admin_btn = "";
  $onclick_log = 'document.getElementById("id01").style.display="block"';
  $icone_log = "icon fas fa-sign-in-alt";
  $title_log = "Log-in";
  $class_conectado = "";
}

// verificação de menus adicionais
$inserir_jogo =  "<a class='nav-item' href='/ranking/criar_jogo.php'>Inserir Jogo</a>";
$importar_jogo = "<a class='nav-item' href='/ranking/importar_jogo.php'>Importar Jogo</a>";
$ver_ranking = "<a class='nav-item' href='/ranking/index.php'>Ranking</a>";
$ver_trios = "<a class='nav-item' href='/arbitros/'>Ver Trios de Arbitragem</a>";
$importar_trios = "<a class='nav-item' href='/arbitros/importar_arbitro.php'>Importar Trio</a>";
$criar_trios = "<a class='nav-item' href='/arbitros/inserir_arbitro.php'>Criar Trio</a>";
$octamotor_home = "<a class='nav-item' href='/octamotor'>Octamotor home</a>";


$currentPage =  explode('/',strtok($_SERVER['REQUEST_URI'], '?'));

 ?>

<meta charset="utf-8"/>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="/js/prefixfree.js?v5"></script>
<script src="/js/jquery-ui/jquery-ui.min.js?v8"></script>
<script src="/js/Chart.min.js?v1"></script>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<link rel="shortcut icon" type="image/ico" href="/favicon.ico"/>
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">
<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
<meta name="msapplication-TileColor" content="#da532c">
<meta name="theme-color" content="#ffffff">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css?family=Kanit:400,600,900" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Gugi" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Share+Tech+Mono" rel="stylesheet">
<script src="https://kit.fontawesome.com/376cb796e7.js" crossorigin="anonymous"></script> 
<link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
<link rel="stylesheet" href="/css/soccerfield.min.css" />
<link rel="stylesheet" href="/css/soccerfield.default.min.css" />
<script src="/js/jquery.soccerfield.min.js"></script>

<?php
if(isset($css_filename)){
  echo '<link type="text/css" href="/css/' . $css_filename . '.css?versao=' . $css_versao .'" rel="stylesheet">';
}
 ?>
<link type="text/css" href="/css/<?php echo $css_login . ".css?versao=" . $css_versao ?>" rel="stylesheet">
<link type="text/css" href="/js/jquery-ui/jquery-ui.min.css?v4" rel="stylesheet">
<?php
if(isset($aux_css)){
    echo "<link type='text/css' href='/css/".$aux_css.".css?versao=".$css_versao."' rel='stylesheet'>";
}
?>

</head>

<body class='loggedout no-capture'>
<div id="top-bar" class="elementoFixo no-capture">
  <div id="logo-text">

  CONFUSA<span class="orange">.</span>top <span class='beta'></span>

  </div>

  <div id="toolbar">

<!--- for default <button><i class="fas fa-bomb"></i></button>  for default --->

</div>

<span id="logged-user" class="<?php echo $class_conectado?>">
  <?php echo $welcometext ?>
</span>


<div id="hamburger-menu" class='no-capture'>
  <a id="open-menu" class='menu-toggle-button no-capture'><i class="fas fa-bars no-capture"></i></a>
  <a id="close-menu" class='menu-toggle-button no-capture'><i class="fas fa-times"></i></a>
  <nav class="nav no-capture" id='nav'>
    <a class="nav-item" href="/">Home</a>
    <?php echo "<a class='nav-item' onclick='{$onclick_log}'>{$title_log}</a>" ?>
    <?php echo "<a class='nav-item' href='/sobre.php'>Sobre / Tutorial</a>" ?>
    <?php echo "<a class='nav-item' href='/contato.php'>Contato</a>" ?>
	<?php echo "<a class='nav-item' href='/sugestoes'><i class='fas fa-comment-dots'></i>  Sugestões/Bugs</a>" ?>
    <?php echo $change_pass ?>
    <?php echo $admin_btn ?>
    <?php
    switch ($currentPage[1]) {
      case "ranking":
          echo $ver_ranking;
          if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
            echo $importar_jogo;
          }
          echo $inserir_jogo;
        break;
      case "arbitros":
          echo $ver_trios;
          echo $criar_trios;
          echo $importar_trios;
        break;
      case "octamotor":
          echo $octamotor_home;
        break;
    }

    ?>

  </nav>
</div>
</div>
<div style="clear:both;"></div>

<div id='id01' class="modal">

  <form method="POST" class="modal-content animate larger" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']);?>">
    <!-- <div class="imgcontainer">
      <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">&times;</span>
      <img src="/images/default-user.png" alt="Avatar" class="avatar">
    </div> -->

    <div class="container">
      <label for="username"><b>Nome de usuário ou email</b></label>
      <input id="username" type="text" placeholder="Entre com seu nome de usuário ou email..." name="username" required>

      <label for="password"><b>Senha</b></label>
      <input id="password" type="password" placeholder="Entre com sua senha..." name="password" required>

      <button type="submit" name="loginsubmit" class="submitbtn">Fazer login</button>
      <label>
        <input type="checkbox" checked="checked" name="remember" data-role="none"> Lembrar-me
      </label>
    </div>

    <div class="container" style="background-color:#f1f1f1">
        <input type="hidden" name="success" value= '0'>
      <button type="button" onclick="document.getElementById('id01').style.display='none'" class="cancelbtn">Cancelar</button>
      <button type="button" onclick="document.getElementById('id03').style.display='block'"
            class="newbtn">Novo usuário</button>
      <span class="psw">Esqueceu a <a id="esqueceuSenha" href="#">senha?</a></span>
    </div>
  </form>
</div>

    <div id='id02' class="modal">

  <form method="POST" class="modal-content animate smaller" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']);?>">

    <div class="container">
        <p>Você tem certeza?</p>
        <input type=hidden name="logout" value =true>

        <button type="submit" class="submitbtn submitsmall">Sim</button>
        <button type="button" onclick="document.getElementById('id02').style.display='none'" class="cancelbtn">Cancelar</button>
    </div>
  </form>
</div>

<div id="id03" class="modal">

  <form method="POST" class="modal-content animate larger" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']);?>">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id03').style.display='none'" class="close" title="Close Modal">&times;</span>
    </div>

    <div class="container">
      <label for="newname"><b>Nome</b></label>
      <input id="newname"  type="text" placeholder="Digite seu nome e sobrenome..." name="newname" required>

      <label for="newemail"><b>Email</b></label>
      <input id="newemail" type="email" placeholder="Digite seu email..." name="newemail" required>

      <label for="newcountry"><b>Países</b></label>
      <textarea id="newcountry" placeholder="Digite o nome de todos os seus países..." name="newcountry" class="areapais" required></textarea>

      <button type="submit" name="newsubmit" class="submitbtn">Solicitar inscrição</button>
    </div>

    <div class="container" style="background-color:#f1f1f1">
      <button type="button" onclick="document.getElementById('id03').style.display='none'" class="cancelbtn">Cancelar</button>
    </div>
  </form>
</div>

<div id="id04" class="modal">

  <form method="POST" class="modal-content animate larger" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']);?>">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id04').style.display='none'" class="close" title="Close Modal">&times;</span>
    </div>

    <div class="container">
      <label for="newemail"><b>Email</b></label>
      <input type="email" placeholder="Digite seu email..." name="forgetemail" required>

      <button type="submit" name="forgetsubmit" class="submitbtn">Receber nova senha por email</button>
    </div>

    <div class="container" style="background-color:#f1f1f1">
      <button type="button" onclick="document.getElementById('id04').style.display='none'" class="cancelbtn">Cancelar</button>
    </div>
  </form>
</div>



<script>


$('.modal').click(function(e){
    var identifier = e.target.id;
    var checker = identifier.substr(0,3);

    if(checker == 'id0'){
        $("#"+identifier).hide();
    }
});


$('#esqueceuSenha').click(function(event){
    event.preventDefault();
    $("#id04").show();

});

$(document).ready(function() {
    $('#open-menu').click(function() {
        $("#nav").addClass('open');
        $(".nav-item").each(function(){
          $(this).addClass('open');
        });
        $("#open-menu").css("display", "none");
        $("#close-menu").css("display", "block");
    });

    $('#close-menu').click(function() {
        $("#nav").removeClass('open');
        $(".nav-item").each(function(){
          $(this).removeClass('open');
        });
        $("#open-menu").css("display", "block");
        $("#close-menu").css("display", "none");
    });

    jQuery('*:not(.no-capture)').on('click', function(e){
    //  e.stopPropagation();
    //  console.log(this);
    $("#nav").removeClass('open');
    $(".nav-item").each(function(){
      $(this).removeClass('open');
    });
    $("#open-menu").css("display", "block");
    $("#close-menu").css("display", "none");
});


});
</script>

<div style="clear:both;"></div>
    <?php

    if(isset($_POST['success']) && $_POST['success'] == '1'){
        echo "<div class='alert alert-danger'>Não foi possível realizar o login!</div>";
        echo "<div style='clear:both;'></div>";
    }

    if(isset($_POST['newsubmit']) && isset($email_success)){
        if($email_success){
            echo "<div class='alert alert-success'>{$email_msg}</div>";
            echo "<div style='clear:both;'></div>";
        } else {
            echo "<div class='alert alert-danger'>{$email_msg}</div>";
            echo "<div style='clear:both;'></div>";
        }

    }

    if(isset($_POST['forgetsubmit']) && isset($email_success)){
        if($email_success){
            echo "<div class='alert alert-success'>{$email_msg}</div>";
            echo "<div style='clear:both;'></div>";
        } else {
            echo "<div class='alert alert-danger'>{$email_msg}</div>";
            echo "<div style='clear:both;'></div>";
        }
    }

    ?>
