<?php
if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

echo "<html class='no-capture' lang='pt-br' xmlns='http://www.w3.org/1999/xhtml' xml:lang='pt-br'>";
echo "<head>";

echo "<title>" . $page_title . "</title>";

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && isset($_SESSION['impersonated'])) {
    $status_style = "block";
	if($_SESSION['emTestes']){
		$welcometext = $_SESSION['nomereal'] . " [em período de teste]";
	} else {
		$welcometext = $_SESSION['nomereal'];
	}
    
    $onclick_log = 'document.getElementById("id02").style.display="block"';
    $title_log = "Log-out";
    $change_pass = "<a class='nav-item' href='/usuario/alterar_senha.php'>Preferências</a>";
	$my_menu = "<a class='nav-item' href='/usuario/index.php'>Minha área</a>";

    if($_SESSION['admin_status'] == '1' && $_SESSION['impersonated'] == false){
      $admin_btn = "<a class='nav-item' href='/admin/index.php'>Área do Admin</a>";
      $class_conectado = " admin conectado ";
    } else if($_SESSION['impersonated'] == true){
      $class_conectado = " impersonado conectado ";
	  $admin_btn = "";
	} else {
      $admin_btn = "";
      $class_conectado = " user conectado ";
    }

} else {
  $my_menu = "";
  $change_pass = "";
  $status_style = "none";
  $welcometext = "";
  $admin_btn = "";
  $onclick_log = 'document.getElementById("id01").style.display="block"';
  $icone_log = "material-symbols-outlined icon login";
  $title_log = "Log-in";
  $class_conectado = "";
}

// verificação de menus adicionais
$inserir_jogo =  "<a class='nav-item' href='/ranking/criar_jogo.php'>Inserir Jogo</a>";
$importar_jogo = "<a class='nav-item' href='/ranking/importar_jogo.php'>Importar Jogo</a>";
$ver_ranking = "<a class='nav-item' href='/ranking/index.php'>Ranking</a>";
$octamotor_home = "<a class='nav-item' href='/octamotor'>Octamotor home</a>";


$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$currentPage =  explode('/', strtok($request_uri, '?'));

 ?>

<meta charset="utf-8"/>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="/js/prefixfree.js?v5"></script>
<script src="/js/jquery-ui/jquery-ui.min.js?v8"></script>
<script src="/js/Chart.min.js?v1"></script>
<script src="https://cdn.plot.ly/plotly-3.4.0.min.js"></script>
<link rel="shortcut icon" type="image/ico" href="/favicon.ico"/>
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">
<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
<meta name="msapplication-TileColor" content="#da532c">
<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no, viewport-fit=cover, maximum-scale=1.0, user-scalable=0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Gugi" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Share+Tech+Mono" rel="stylesheet">

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,1,0" />
<link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
<link rel="stylesheet" href="/css/soccerfield.min.css" />
<link rel="stylesheet" href="/css/soccerfield.default.min.css" />
<script src="/js/jquery.soccerfield.min.js"></script>

<?php
if (!isset($css_login)) {
  $css_login = 'login';
}
if (!isset($css_versao)) {
  $css_versao = date('h:i:s');
}
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
if(isset($extra_css)){
    echo "<link type='text/css' href='/css/".$extra_css.".css?versao=".$css_versao."' rel='stylesheet'>";
}
?>

<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('/sw.js').then(function(registration) {
      // console.log('ServiceWorker registration successful with scope: ', registration.scope);
      registration.update();
    }, function(err) {
      // console.log('ServiceWorker registration failed: ', err);
    });
  });
}
</script>

<style>
/* Updates Premium Modal */
.custom-modal-overlay {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background-color: rgba(15, 23, 42, 0.6);
	backdrop-filter: blur(5px);
	-webkit-backdrop-filter: blur(5px);
	display: none;
	justify-content: center;
	align-items: center;
	z-index: 999999;
	opacity: 0;
	transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.custom-modal-overlay.show {
	display: flex;
	opacity: 1;
}
.custom-modal-card {
	background: rgba(30, 41, 59, 0.95);
	border: 1px solid rgba(255, 255, 255, 0.08);
	border-radius: 16px;
	padding: 24px;
	max-width: 500px;
	width: 90%;
	box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
	color: #f1f5f9;
	transform: scale(0.9);
	transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.custom-modal-overlay.show .custom-modal-card {
	transform: scale(1);
}
.updates-card {
	max-width: 550px !important;
}
.custom-modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
	border-bottom: 1px solid rgba(255, 255, 255, 0.06);
	padding-bottom: 12px;
}
.custom-modal-header .header-left {
	display: flex;
	align-items: center;
	gap: 10px;
}
.custom-modal-header .info-icon {
	color: #38bdf8;
	font-size: 24px;
}
.custom-modal-header h3 {
	margin: 0;
	font-size: 1.4rem;
	font-weight: 700;
	color: #f1f5f9;
}
.custom-modal-header .close-icon {
	color: #94a3b8;
	cursor: pointer;
	transition: color 0.2s;
}
.custom-modal-header .close-icon:hover {
	color: #f1f5f9;
}
.custom-modal-body {
	max-height: 400px;
	overflow-y: auto;
	padding-right: 8px;
	text-align: left;
}
.custom-modal-body::-webkit-scrollbar {
	width: 6px;
}
.custom-modal-body::-webkit-scrollbar-track {
	background: transparent;
}
.custom-modal-body::-webkit-scrollbar-thumb {
	background: rgba(255, 255, 255, 0.1);
	border-radius: 4px;
}
.custom-modal-body::-webkit-scrollbar-thumb:hover {
	background: rgba(255, 255, 255, 0.2);
}
.update-item {
	display: flex;
	gap: 15px;
	margin-bottom: 20px;
	align-items: flex-start;
}
.update-badge {
	font-size: 0.75rem;
	font-weight: 700;
	text-transform: uppercase;
	padding: 3px 8px;
	border-radius: 6px;
	white-space: nowrap;
	margin-top: 2px;
}
.update-badge.new {
	background: rgba(16, 185, 129, 0.15);
	color: #34d399;
	border: 1px solid rgba(16, 185, 129, 0.2);
}
.update-badge.design {
	background: rgba(168, 85, 247, 0.15);
	color: #c084fc;
	border: 1px solid rgba(168, 85, 247, 0.2);
}
.update-badge.security {
	background: rgba(239, 68, 68, 0.15);
	color: #f87171;
	border: 1px solid rgba(239, 68, 68, 0.2);
}
.update-badge.bugfix {
	background: rgba(245, 158, 11, 0.15);
	color: #fbbf24;
	border: 1px solid rgba(245, 158, 11, 0.2);
}
.update-content h4 {
	margin: 0 0 6px 0;
	font-size: 1.05rem;
	font-weight: 600;
	color: #f8fafc;
}
.update-content p {
	margin: 0;
	font-size: 0.9rem;
	line-height: 1.45;
	color: #94a3b8;
}
.custom-modal-footer {
	display: flex;
	justify-content: flex-end;
	margin-top: 20px;
	padding-top: 15px;
	border-top: 1px solid rgba(255, 255, 255, 0.06);
}
.custom-modal-btn {
	padding: 10px 20px;
	border-radius: 8px;
	font-weight: 600;
	font-size: 0.95rem;
	border: none;
	cursor: pointer;
	transition: all 0.2s;
}
.custom-modal-btn.btn-ok {
	background: #ffffff;
	color: #0f172a;
}
.custom-modal-btn.btn-ok:hover {
	background: #f1f5f9;
	box-shadow: 0 0 12px rgba(255, 255, 255, 0.35);
}

/* Premium Collapsible Timeline */
.timeline-body {
	position: relative;
}
.timeline-day {
	position: relative;
	margin-bottom: 16px;
	z-index: 2;
}
.timeline-day-header {
	display: flex;
	align-items: center;
	padding: 10px 14px;
	background: rgba(15, 23, 42, 0.3);
	border: 1px solid rgba(255, 255, 255, 0.04);
	border-radius: 8px;
	cursor: pointer;
	transition: all 0.25s;
	user-select: none;
}
.timeline-day-header:hover {
	background: rgba(255, 255, 255, 0.03);
	border-color: rgba(255, 255, 255, 0.1);
}
.timeline-day.active .timeline-day-header {
	background: rgba(56, 189, 248, 0.08);
	border-color: rgba(56, 189, 248, 0.25);
}
.timeline-day-header .expand-icon {
	color: #94a3b8;
	margin-right: 10px;
	font-size: 1.2rem;
	transition: transform 0.25s ease;
}
.timeline-day.active .timeline-day-header .expand-icon {
	transform: rotate(180deg);
	color: #38bdf8;
}
.timeline-day-header .day-date {
	font-weight: 700;
	font-size: 0.95rem;
	color: #e2e8f0;
	flex-grow: 1;
}
.timeline-day.active .timeline-day-header .day-date {
	color: #38bdf8;
}
.timeline-day-content {
	display: none;
	padding: 16px 14px 4px 14px;
	margin-top: 4px;
	margin-left: 28px;
}
.timeline-day.active .timeline-day-content {
	display: block;
}

/* Fallback styles for user avatars and navigation on old layout pages */
<?php 
$is_redesigned = (isset($css_filename) && $css_filename === 'home_redesign') || (isset($aux_css) && $aux_css === 'home_redesign');
if (!$is_redesigned): 
?>
.user-avatar-header,
.user-name-header,
.menu-profile-header {
	display: none !important;
}
.hamburger-icon-fallback {
	display: inline-block !important;
}
<?php else: ?>
.hamburger-icon-fallback {
	display: none !important;
}
<?php endif; ?>
.user-avatar-header {
	width: 28px !important;
	height: 28px !important;
	border-radius: 50% !important;
	object-fit: cover !important;
}
.menu-profile-avatar {
	width: 56px !important;
	height: 56px !important;
	border-radius: 50% !important;
	object-fit: cover !important;
}
</style>
</head>

<body class='loggedout no-capture'>
<div id="top-bar" class="elementoFixo no-capture">
  <div id="logo-text">

  <span style="white-space: nowrap; display: inline-flex; align-items: center;">
    <a href="/" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center;">
      CONFUSA<span class="orange">.</span>top <span class='beta'></span>
    </a>
    <span onclick="showUpdatesModal()" style="display: inline-flex; vertical-align: middle; cursor: pointer; color: #94a3b8; margin-left: 8px; transition: color 0.2s; user-select: none;" onmouseover="this.style.color='#38bdf8'" onmouseout="this.style.color='#94a3b8'" title="Novidades e Atualizações">
      <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">help</span>
    </span>
  </span>

  </div>

  <div id="toolbar">


</div>

<span id="logged-user" class="<?php echo $class_conectado?>">
  <?php if(isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == '1' && empty($_SESSION['impersonated'])): ?>
    <a href="/admin/index.php" style="color: inherit; text-decoration: none; border-bottom: 1px dashed #38bdf8;"><?php echo $welcometext ?></a>
  <?php else: ?>
    <?php echo $welcometext ?>
  <?php endif; ?>
</span>


<div id="hamburger-menu" class='no-capture'>
  <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true): 
    $avatar_to_show = !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : '/images/default-user.png';
  ?>
    <a id="open-menu" class='menu-toggle-button logged-in no-capture'>
      <span class="material-symbols-outlined no-capture hamburger-icon-fallback">menu</span>
      <img src="<?php echo htmlspecialchars($avatar_to_show); ?>" alt="Avatar" class="user-avatar-header" />
      <span class="user-name-header"><?php echo htmlspecialchars($_SESSION['nomereal'] ?? $_SESSION['username'] ?? ''); ?></span>
    </a>
  <?php else: ?>
    <a id="open-menu" class='menu-toggle-button no-capture'><span class="material-symbols-outlined no-capture">menu</span></a>
  <?php endif; ?>
  <a id="close-menu" class='menu-toggle-button no-capture'><span class="material-symbols-outlined">close</span></a>
  <nav class="nav no-capture" id='nav'>
    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true): 
      $avatar_to_show = !empty($_SESSION['avatar']) ? $_SESSION['avatar'] : '/images/default-user.png';
      $is_impersonated = !empty($_SESSION['impersonated']);
      $is_admin = (($_SESSION['admin_status'] ?? '') == '1' && !$is_impersonated);
      $role_label = $is_impersonated ? 'Admin impersonando' : ($is_admin ? 'Administrador' : 'Membro');
    ?>
      <div class="menu-profile-header">
        <img src="<?php echo htmlspecialchars($avatar_to_show); ?>" alt="Avatar" class="menu-profile-avatar" />
        <div class="menu-profile-info">
          <span class="menu-profile-name"><?php echo htmlspecialchars($_SESSION['nomereal'] ?? $_SESSION['username'] ?? ''); ?></span>
          <span class="menu-profile-role <?php echo $is_admin ? 'admin' : ''; ?>"><?php echo $role_label; ?></span>
        </div>
      </div>
    <?php endif; ?>
    <a class="nav-item" href="/">Home</a>
	<?php echo $my_menu ?>
    <?php echo "<a class='nav-item' onclick='{$onclick_log}'>{$title_log}</a>" ?>
    <?php echo "<a class='nav-item' href='/sobre.php'>Sobre / Tutorial</a>" ?>
    <?php echo "<a class='nav-item' href='/contato.php'>Contato</a>" ?>
	<?php echo "<a class='nav-item' href='/sugestoes'><span class='material-symbols-outlined'>chat</span>  Sugestões/Bugs</a>" ?>
    <?php echo $change_pass ?>
    <?php echo $admin_btn ?>
    <?php
    $currentSection = $currentPage[1] ?? '';
    switch ($currentSection) {
      case "arbitros":
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
  <form method="POST" class="modal-content animate larger" action="<?php echo htmlspecialchars($request_uri);?>">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">&times;</span>
    </div>

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

  <form method="POST" class="modal-content animate smaller" action="<?php echo htmlspecialchars($request_uri);?>">

    <div class="container">
        <p>Você tem certeza?</p>
        <input type=hidden name="logout" value =true>

        <button type="submit" class="submitbtn submitsmall">Sim</button>
        <button type="button" onclick="document.getElementById('id02').style.display='none'" class="cancelbtn">Cancelar</button>
    </div>
  </form>
</div>

<div id="id03" class="modal">

  <form method="POST" class="modal-content animate larger" action="<?php echo htmlspecialchars($request_uri);?>">
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

  <form method="POST" class="modal-content animate larger" action="<?php echo htmlspecialchars($request_uri);?>">
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


    $(document).on('change', 'input[type="file"].custom-file-upload', function(e) {
        var file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            var reader = new FileReader();
            var element = $(this);
            reader.onload = function(e) {
                element.css('background-image', 'url(' + e.target.result + ')');
                element.css('background-size', 'contain');
                element.css('background-repeat', 'no-repeat');
                element.css('background-position', 'center');
                element.css('color', 'transparent');
            }
            reader.readAsDataURL(file);
        }
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

<?php
// Load and parse updates.json (relative to root of workspace)
$json_path = $_SERVER['DOCUMENT_ROOT'] . '/updates.json';
$updates_data = [];
if (file_exists($json_path)) {
	$updates_data = json_decode(file_get_contents($json_path), true);
}

$up_title = $updates_data['title'] ?? "Novidades e Ajustes";
$up_btn = $updates_data['btn'] ?? "Legal!";
$badge_translations = $updates_data['badge_translations'] ?? [
	"new" => "Novo",
	"design" => "Design",
	"security" => "Segurança",
	"bugfix" => "Ajuste"
];
$days = $updates_data['days'] ?? [];
?>

<div id="custom-updates-modal" class="custom-modal-overlay">
	<div class="custom-modal-card updates-card">
		<div class="custom-modal-header">
			<div class="header-left">
				<span class="material-symbols-outlined info-icon">auto_awesome</span>
				<h3 class="custom-modal-title"><?php echo htmlspecialchars($up_title); ?></h3>
			</div>
			<span class="material-symbols-outlined close-icon" onclick="closeUpdatesModal()">close</span>
		</div>
		
		<div class="custom-modal-body timeline-body">
			<?php if (empty($days)): ?>
				<p style="text-align: center; color: #94a3b8; padding: 20px;">Nenhuma novidade recente encontrada.</p>
			<?php else: ?>
				<?php foreach ($days as $index => $day): 
					$day_id = htmlspecialchars($day['id'] ?? 'day_' . $index);
					$day_date = htmlspecialchars($day['date'] ?? '');
					$is_active = ($index === 0); // Open first day by default
				?>
					<div class="timeline-day <?php echo $is_active ? 'active' : ''; ?>" data-day="<?php echo $day_id; ?>">
						<div class="timeline-day-header" onclick="toggleTimelineDay('<?php echo $day_id; ?>')">
							<span class="material-symbols-outlined expand-icon">expand_more</span>
							<span class="day-date"><?php echo $day_date; ?></span>
						</div>
						<div class="timeline-day-content" style="<?php echo $is_active ? 'display: block;' : 'display: none;'; ?>">
							<?php 
							$day_title = htmlspecialchars($day['title'] ?? '');
							$day_desc = htmlspecialchars($day['description'] ?? '');
							$items = $day['items'] ?? [];
							
							// Se houver título/descrição raiz E não existirem sub-itens (items), renderiza
							if (empty($items) && (!empty($day_title) || !empty($day_desc))):
							?>
								<div class="update-item">
									<div class="update-badge new">Novo</div>
									<div class="update-content">
										<h4><?php echo $day_title; ?></h4>
										<p><?php echo $day_desc; ?></p>
									</div>
								</div>
							<?php 
							endif;

							// Se houver sub-itens, renderiza-os
							foreach ($items as $item): 
								$badge_key = $item['badge'] ?? 'bugfix';
								$badge_lbl = htmlspecialchars($badge_translations[$badge_key] ?? '');
								$item_title = htmlspecialchars($item['title'] ?? '');
								$item_desc = htmlspecialchars($item['desc'] ?? '');
							?>
								<div class="update-item">
									<div class="update-badge <?php echo htmlspecialchars($badge_key); ?>"><?php echo $badge_lbl; ?></div>
									<div class="update-content">
										<h4><?php echo $item_title; ?></h4>
										<p><?php echo $item_desc; ?></p>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		
		<div class="custom-modal-footer">
			<button class="custom-modal-btn btn-ok" onclick="closeUpdatesModal()"><?php echo htmlspecialchars($up_btn); ?></button>
		</div>
	</div>
</div>

<script>
function showUpdatesModal() {
	openUpdatesModal();
}
function openUpdatesModal() {
	var modal = $("#custom-updates-modal");
	// Reset accordion to show first day open, others closed
	modal.find(".timeline-day").removeClass("active");
	modal.find(".timeline-day-content").hide();
	
	var firstDay = modal.find(".timeline-day").first();
	firstDay.addClass("active");
	firstDay.find(".timeline-day-content").show();
	
	modal.css("display", "flex");
	setTimeout(function() {
		modal.addClass("show");
	}, 10);
}
function closeUpdatesModal() {
	var modal = $("#custom-updates-modal");
	modal.removeClass("show");
	setTimeout(function() {
		modal.css("display", "none");
	}, 300);
}
function toggleTimelineDay(dayId) {
	var targetDay = $(".timeline-day[data-day='" + dayId + "']");
	
	if (targetDay.hasClass("active")) {
		targetDay.find(".timeline-day-content").slideUp(250, function() {
			targetDay.removeClass("active");
		});
	} else {
		// Expand target day and collapse all others
		$(".timeline-day.active").each(function() {
			var activeDay = $(this);
			activeDay.find(".timeline-day-content").slideUp(250, function() {
				activeDay.removeClass("active");
			});
		});
		
		targetDay.find(".timeline-day-content").slideDown(250, function() {
			targetDay.addClass("active");
		});
	}
}

// Fechar se clicar fora do modal
window.addEventListener('click', function(e) {
    const modal = document.getElementById('custom-updates-modal');
    if (e.target === modal) {
        closeUpdatesModal();
    }
});
</script>
