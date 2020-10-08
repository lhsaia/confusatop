<!DOCTYPE html>

<?php

/// criar bloqueios para não admin, não logado, não dono e fora de época
/// bloqueios de nível

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "OctaMotor - Pilotos";
//$css_filename = "";
$css_login = 'login';
$aux_css = "driver_info";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/driver.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$octa_database = new OctamotorDatabase();
$odb = $octa_database->getConnection();
$driver = new Driver($odb);

$pais = new Pais($db);

$driver_list = $driver->getDriversList();
$country_list = $pais->read(null, null, null);

if(isset($_GET['driver'])){
  $selected_driver = $_GET['driver'];
} else {
  $selected_driver = 1;
}

?>
<div id='loadingDiv'><img src='/octamotor/images/lights.gif'/></div>
<div id="container-home-octamotor">
  <!-- Driver viewer start -->
  <div id="driver-viewer" class="visible">
    <div class="container-control">
      <?php
      if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
        echo "<a id='create-new-driver' class='editor-button'>Criar</a>";
      }

      echo "<select id='select-driver'>";
      foreach($driver_list as $driver_unit){
        echo "<option data-owner='{$driver_unit['owner']}' value='{$driver_unit['id']}' ". ($driver_unit['id'] == $selected_driver ? "selected" : "").">{$driver_unit['name']}</option>";
      }
      echo "</select>";

      if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
        echo "<a id='edit-driver' class='editor-button'>Editar</a>";
      }

        ?>

    </div>
    <div class="container-driver-main">
      <div id="container-driver-pictures">
        <div id="container-driver-profile-picture" class="image-container">
          <img id="profile-picture-info" class="image" src="" />
        </div>
        <div id="container-driver-car-helmet">
          <div id="container-helmet" class="image-container">
            <img id="helmet-picture-info" class="image" src="" />
          </div>
          <div id="container-car" class="image-container">
            <img id="car-picture-display" class="image" src="" />
          </div>
        </div>
      </div>
      <div id="container-driver-info">
        <span class="driver-info"><span class="driver-info-title">País: </span><span id="driver-country-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Data de Nascimento: </span><span id="driver-birth-date-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Local de Nascimento: </span><span id="driver-birth-place-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Equipe: </span><span id="driver-team-name-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Competição: </span><span id="competition-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Largadas: </span><span id="gps-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Pontos: </span><span id="points-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Melhor posição (corrida): </span><span id="race-position-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Melhor posição (grid): </span></span>
        <span class="driver-info"><span class="driver-info-title">Pódios: </span><span id="podium-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Abandonos: </span><span id="abandon-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Títulos: </span><span id="titles-info"></span></span>
        <span class="driver-info-title">Biografia:</span><p id='driver-bio-info' class="driver-bio"></p>
      </div>
    </div>
  </div>
    <!-- Driver viewer end -->

    <!-- Driver editor start -->
  <div id="driver-editor" class="hidden">
    <div class="container-control">
      <a id="save-driver" class="editor-button">Salvar</a>
      <p id="driver-name-bar"></p>
      <a id="cancel-driver" class="editor-button">Cancelar</a>
    </div>
    <div class="container-driver-main">
      <div id="container-basic-form">
        <form id="driver-basic-form">
          <div hidden id="driver-id"></div>
          <div class='form-group'>
            <label for="driver-name">Nome</label>
            <input type="text" id="driver-name" placeholder="ex. Armin Tamzarian"/>
          </div>
          <div class='form-group'>
            <label for="driver-small-name">Nome abreviado</label>
            <input type="text" id="driver-small-name" placeholder="ex. A. Tamzarian"/>
          </div>
          <div class='form-group'>
            <label for="driver-genre">Sexo</label>
            <select id="driver-genre" required>
              <option value="" selected disable>Selecione o sexo...</option>
              <option value='0'>Masculino</option>
              <option value='1'>Feminino</option>
            </select>
          </div>
          <div class='form-group'>
            <label for="driver-country">País</label>
            <select id="driver-country" required>
              <option value="" selected disable>Selecione o país...</option>
              <?php
              while($result = $country_list->fetch(PDO::FETCH_ASSOC) ){
                echo "<option value='" . $result["id"] . "'>" . $result["nome"] . "</option>";
              }
              ?>
            </select>
          </div>
          <div class='form-group'>
            <label for="driver-birth-date">Data de nascimento</label>
            <input required type="date" id="driver-birth-date"/>
          </div>
          <div class='form-group'>
            <label for="driver-birth-place">Local de nascimento</label>
            <input type="text" id="driver-birth-place" placeholder="ex. Centrotal, Praias"/>
          </div>
          <div class='form-group'>
            <label for="driver-number">Número</label>
            <input type="number" id="driver-number" placeholder="ex. 54"/>
          </div>
          <!-- <div class='form-group'>
            <label for="driver-team">Equipe</label>
            <select id="driver-team" required>
              <option value="" selected disable>Selecione a equipe...</option>
            </select>
          </div>
          <div class='form-group'>
            <label for="driver-competition">Competição</label>
            <select id="driver-competition" required>
              <option value="" selected disable>Selecione a competição...</option>
            </select>
          </div> -->
          <div class='form-group'>
            <label for="driver-bio">Biografia</label>
            <textArea id="driver-bio" placeholder="ex. Nasceu na Cidade de Orion em 1999 e começou a pilotar aos 12 anos no kart..."></textArea>
          </div>
          <div class='form-group'>
            <label for="driver-status">Status</label>
            <select id="driver-status">
              <option selected value='1'>Ativo</option>
              <option value='0'>Aposentado</option>
            </select>
          </div>
        </form>
      </div>
      <div id="container-image-form">
        <form id="driver-image-form">
          <div class='form-group'>
            <label class="picture-label" for="driver-photo"><span id="driver-photo-text">Foto</span><img class="hidden" id="driver-photo-preview" src=""/></label>
            <input type="file" id="driver-photo" onchange="readURL(this, 'photo');"/>
          </div>
          <div class='form-group'>
            <label class='picture-label' for="driver-helmet"><span id="driver-helmet-text">Capacete</span><img class="hidden" id="driver-helmet-preview" src=""/></label>
            <input type="file" id="driver-helmet" onchange="readURL(this, 'helmet');"/>
          </div>
          <div class='form-group'>
              <button class='kit-button' id='kit-capacete'>
                  <i class="fas fa-paint-brush"></i><span> Kit capacete</span>
                  <iframe id='download_helmet' hidden src=""></iframe>
              </button>
          </div>
        </form>
      </div>
      <div id="container-level-form">
        <form id="driver-level-form">
          <div class="form-master-group">
            <div class='form-group vertical'>
              <label for="driver-level">Nível</label>
              <span contenteditable="true" id="current-level"></span>
              <input type="range" min="1" max="100" id="driver-level"/>

            </div>
          </div>
          <div class="form-master-group">
            <div class='form-group'>
              <label>Atributos</label>
            </div>
            <div class='form-group'>
              <label for="driver-hot-lap">Volta lançada</label>
              <input type="range" min="1" max="10" id="driver-hot-lap"/>
            </div>
            <div class='form-group'>
              <label for="driver-pace">Ritmo de corrida</label>
              <input type="range" min="1" max="10" id="driver-pace"/>
            </div>
            <div class='form-group'>
              <label for="driver-aggressiveness">Agressividade</label>
              <input type="range" min="1" max="10" id="driver-aggressiveness"/>
            </div>
            <div class='form-group'>
              <label for="driver-start-skills">Largada</label>
              <input type="range" min="1" max="10" id="driver-start-skills"/>
            </div>
            <div class='form-group'>
              <label for="driver-rain-skills">Chuva</label>
              <input type="range" min="1" max="10" id="driver-rain-skills"/>
            </div>
            <div class='form-group'>
              <label for="driver-car-setup">Acerto do carro</label>
              <input type="range" min="1" max="10" id="driver-car-setup"/>
            </div>
          </div>
        </form>
        <div id="attribute-chart"></div>
      </div>
    </div>
  </div>
</div>

<script>

var name;
var country;
var country_id;
var photo;
var helmet;
var car_picture;
var birth_date;
var birth_place;
var team_name;
var competition;
var bio;
var number;
var level;
var hotLap;
var pace;
var rainSkills;
var startSkills;
var carSetup;
var aggressiveness;
var id;
var logged_user = {  };
var points;
var gps;
var highest_comp;
var titles;
var podiums;
var best_position;
var best_position_times;
var abandon;
var genre;
var tv_name;

function readURL(input, target_div) {
       if (input.files && input.files[0]) {
           var reader = new FileReader();

           reader.onload = function (e) {
               $('#driver-'+target_div + "-preview")
                   .attr('src', e.target.result).removeClass("hidden");
              $('#driver-' + target_div + '-text').addClass("hidden");
              $('label[for="driver-'+target_div+'"]').addClass("no-padding");
                   // .width(200)
                   // .height(200);
           };

           reader.readAsDataURL(input.files[0]);
       }
   }

function level_distributor(){
  var level = $("#driver-level").val();
  var hotLap = $("#driver-hot-lap").val();
  var pace = $("#driver-pace").val();
  var aggressiveness = $("#driver-aggressiveness").val();
  var startSkills = $("#driver-start-skills").val();
  var rainSkills = $("#driver-rain-skills").val();
  var carSetup = $("#driver-car-setup").val();

  var totalPoints = level * 0.55;
  var totalAttributes = parseInt(hotLap) + parseInt(pace) + parseInt(aggressiveness) + parseInt(startSkills) + parseInt(rainSkills) + parseInt(carSetup);

  hotLap = (hotLap / totalAttributes) * totalPoints ;
  pace = (pace / totalAttributes) * totalPoints;
  aggressiveness = (aggressiveness / totalAttributes) * totalPoints;
  startSkills = (startSkills / totalAttributes) * totalPoints;
  rainSkills = (rainSkills / totalAttributes) * totalPoints;
  carSetup = (carSetup / totalAttributes) * totalPoints;

  var attributeArray = [startSkills,pace, aggressiveness,rainSkills, hotLap, carSetup];

  var remainder;
  var dividend;

  do {
    remainder = 0.0;
    dividend = 0.0;

  attributeArray.forEach(function(element, index) {
    if(element > 10){
      remainder = remainder + element - 10;
      attributeArray[index] = 10;
    } else if(element < 0.5){
      dividend = dividend - (0.5 - element);
      attributeArray[index] = 0.5;
    } else {
      dividend = dividend + 10;
    }
  });

  if(remainder > 0.0){
    var distribution = remainder / dividend;
    attributeArray.forEach(function(element, index) {
      if(element < 10){
        attributeArray[index] = element + distribution * 10 ;
      }
    });
  }



} while (remainder > 0.0);

sum = attributeArray.reduce((pv, cv) => pv + cv, 0);
totalUse = sum/totalPoints;

  return attributeArray;
}

function decodeHtml(html) {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
}

function display_driver(updateEditor){
  var driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
  if(!verifyLoggedUser(driver_owner)){
    $("#edit-driver").hide().attr("disabled", "disabled");
  } else {
    $("#edit-driver").show().removeAttr("disabled");
  }
  var id = $("#select-driver").val();

  $.ajax({
    url: 'driver_info_request.php',
    type: 'POST',
    dataType: 'json',
    data: {id: id}
  })
  .done(function(data) {
    genre = data.driver_data.genre;
    tv_name = data.driver_data.tv_name;
    country = data.driver_data.country_name;
    photo = "/octamotor/images/picture/" + data.driver_data.photo;
    helmet = "/octamotor/images/helmet/" + data.driver_data.helmet;
    if(data.driver_data.car_picture){
      car_picture = "/octamotor/images/car/"  + data.driver_data.car_picture;
    } else {
      car_picture = "/octamotor/images/car/default-car2.png";
    }
    birth_date =  data.driver_data.birth_date;
    birth_place = data.driver_data.birth_place;
    if(data.driver_data.team_name){
      team_name = data.driver_data.team_name;
      competition = data.driver_data.competition;
    } else {
      team_name = "Sem equipe";
      competition = data.driver_data.highest_comp;
    }

    bio =  data.driver_data.bio;
    name = data.driver_data.name;
    number = data.driver_data.number;
    country_id = data.driver_data.country_id;
    level = data.driver_data.level;
    hotLap = data.driver_data.speed;
    pace = data.driver_data.pace;
    rainSkills = data.driver_data.rain_skills;
    startSkills = data.driver_data.start_skills;
    carSetup = data.driver_data.technique;
    aggressiveness = data.driver_data.aggressiveness;

    status = data.driver_data.status;
    highest_comp = data.driver_data.highest_comp;

    if(data.driver_data.points){
      points = data.driver_data.points;
    } else {
      points = 0;
    }
    gps = data.driver_data.gps;
    if(data.driver_data.best_position){
      best_position = data.driver_data.best_position;
      best_position_times = data.driver_data.best_position_times;
    } else {
      best_position = 0;
      best_position_times = 0;
    }

    if(data.driver_data.podiums){
      podiums = data.driver_data.podiums;
    } else {
      podiums = 0;
    }
    if(data.driver_data.abandon){
      abandon = data.driver_data.abandon;
    } else {
      abandon = 0;
    }

if(best_position){
  $("#race-position-info").html(best_position + " (" + best_position_times + "x)");
} else {
  $("#race-position-info").html("---");
}

    $("#podium-info").html(podiums);
    $("#abandon-info").html(abandon);

    $("#driver-country-info").html(country);
    $("#driver-birth-date-info").html(birth_date);
    $("#driver-birth-place-info").html(birth_place);
    $("#driver-team-name-info").html(team_name);
    $("#driver-bio-info").html(decodeHtml(bio));

    $("#profile-picture-info").attr("src", photo);
    $("#helmet-picture-info").attr("src", helmet);
    $("#car-picture-display").attr("src", car_picture);
    $("#points-info").html(points);
    $("#gps-info").html(gps);
    if(status < 1){
      $("#container-driver-info").addClass("aposentado");
      $("#container-driver-info").removeClass("sem-contrato");
      $("#competition-info").html(highest_comp);
    } else if(!data.driver_data.team_name){
      $("#container-driver-info").addClass("sem-contrato");
      $("#container-driver-info").removeClass("aposentado");
      $("#competition-info").html(highest_comp);
    } else {
      $("#container-driver-info").removeClass("sem-contrato");
      $("#container-driver-info").removeClass("aposentado");
      $("#competition-info").html(competition);
    }
	let getUrl = window.location;
	let baseUrl = getUrl .protocol + "//" + getUrl.host + "/" + getUrl.pathname.split('/')[1];
	
    window.history.replaceState(null, null, baseUrl + '/driver_info.php?driver=' + $("#select-driver").val());

    if(updateEditor){
      populate_editor(false);
    }
  })
  .fail(function() {
    console.log("error");
  });

}

function populate_editor(changeBar){
  if(changeBar){
    $("#driver-name-bar").html("Editar piloto").removeClass("request_failure").removeClass("request_success");
  }
  $("#driver-id").html($("#select-driver").val());
  $("#driver-name").val(name);
  $("#driver-small-name").val(tv_name);
  $("#driver-genre").val(genre);
  $("#driver-birth-place").val(birth_place);
  $("#driver-birth-date").val(birth_date);
  $("#driver-bio").val(bio);
  $("#driver-country").val(country_id);
  $("#driver-number").val(number);
  $("#driver-level").val(level);
  $("#driver-hot-lap").val(hotLap);
  $("#driver-pace").val(pace);
  $("#driver-aggressiveness").val(aggressiveness);
  $("#driver-rain-skills").val(rainSkills);
  $("#driver-start-skills").val(startSkills);
  $("#driver-car-setup").val(carSetup);
  $("#driver-photo-preview").attr("src", photo).removeClass("hidden");
  $("#driver-helmet-preview").attr("src", helmet).removeClass("hidden");
  $("#driver-helmet-text").addClass("hidden");
  $("#driver-photo-text").addClass("hidden");
  $(".picture-label").addClass("no-padding");
  $("#driver-photo").val("");
  $("#driver-helmet").val("");
  $("#driver-status").val(status);

    attribute_chart();
}

function empty_editor(reloadBar){
  if(reloadBar){
    $("#driver-name-bar").html("Criar piloto").removeClass("request_failure").removeClass("request_success");
  }
  $("#driver-id").html("");
  $("#driver-name").val("");
  $("#driver-small-name").val("");
  $("#driver-genre").val("");
  $("#driver-birth-place").val("");
  $("#driver-birth-date").val("");
  $("#driver-bio").val("");
  $("#driver-country").val("");
  $("#driver-number").val("");
  $("#driver-level").val("");
  $("#driver-hot-lap").val("");
  $("#driver-pace").val("");
  $("#driver-aggressiveness").val("");
  $("#driver-rain-skills").val("");
  $("#driver-start-skills").val("");
  $("#driver-car-setup").val("");
  $("#driver-photo-preview").attr("src", "").addClass("hidden");
  $("#driver-helmet-preview").attr("src", "").addClass("hidden");
  $("#driver-helmet-text").removeClass("hidden");
  $("#driver-photo-text").removeClass("hidden");
  $(".picture-label").removeClass("no-padding");
  $("#driver-photo").val("");
  $("#driver-helmet").val("");
  $("#driver-status").val(1);

    attribute_chart();
}

function attribute_chart(){

  var currentLevel = $("#driver-level").val();
  $("#current-level").html(currentLevel);

var hotLap = $("#driver-hot-lap").val();
var pace = $("#driver-pace").val();
var aggressiveness = $("#driver-aggressiveness").val();
var startSkills = $("#driver-start-skills").val();
var rainSkills = $("#driver-rain-skills").val();
var carSetup = $("#driver-car-setup").val();

var results = level_distributor();
//console.log(results);

  data = [{
  type: 'scatterpolar',
    mode: "markers",
  //r: [hotLap, pace, aggressiveness, startSkills, rainSkills, carSetup],
  r: results,
  theta: ['Largada','Ritmo de corrida','Agressividade','Chuva',  'Volta Lançada','Acerto do carro'],
  fill: 'toself'
}]

layout = {
  margin: {
   l: 30,
   r: 30,
   b: 30,
   t: 30,
   pad: 10
 },
  polar: {
    radialaxis: {
      visible: true,
      range: [0, 10],
      color:"#ffffff",
      showline: false,
      linewidth: 0,
      ticks: "",
      showticklabels: false
    },
    angularaxis: {
      color:"#ffffff",
      type: "category"
    },
    bgcolor: 'rgba(0,0,0,0)',
  },
  showlegend: false,
  paper_bgcolor: 'rgba(0,0,0,0)',
  plot_bgcolor: 'rgba(0,0,0,0)',
  font: {
    color:"#ffffff"
  },
  gridshape: "linear"


}

Plotly.newPlot("attribute-chart", data, layout, {staticPlot: true},
{displayModeBar: false});

}

$("#select-driver").change(function(){
  display_driver(false);
});

function setLoggedUser(){

    Object.defineProperty(logged_user, 'user_id', {
        value: '<?php echo (isset($_SESSION['user_id'])? $_SESSION['user_id'] : "") ?>',
        writable : false,
        enumerable : true,
        configurable : false
    });

    Object.defineProperty(logged_user, 'admin_status', {
        value: '<?php echo (isset($_SESSION['admin_status'])? $_SESSION['admin_status'] : "") ?>',
        writable : false,
        enumerable : true,
        configurable : false
    });

}

function verifyLoggedUser(user){
  if(logged_user.admin_status > 0){
    return true;
  } else if (logged_user.user_id == user){
    return true;
  } else {
    return false;
  }
}

var $loading = $('#loadingDiv').hide();
$(document)
  .ajaxStart(function () {
    $loading.show();
  })
  .ajaxStop(function () {
    $loading.hide();
  });

setLoggedUser();

$("document").ready(function(){
  display_driver(false);
  attribute_chart();

  $("#kit-capacete").click(function(e){

      e.preventDefault();

            $.ajax({
        url: 'download_helmet.php',
        type: 'POST',
        cache: false,
        dataType: 'json',
        data: {item: "capacete" }
      })
      .done(function(data) {
        $("#download_helmet").attr("src",decodeURIComponent(data.data) );
      });
  });

  $("#driver-level-form :input").change(function() {
    attribute_chart();
    });

    $("#current-level").on("blur", function(){
      $("#driver-level").val($(this).html());
      attribute_chart();
    });

  $("#cancel-driver").on("click", function(){
    $("#driver-viewer").removeClass("hidden").addClass("visible");
    $("#driver-editor").removeClass("visible").addClass("hidden");
  });

  $("#create-new-driver").on("click", function(){
    $("#driver-editor").removeClass("hidden").addClass("visible");
    $("#driver-viewer").removeClass("visible").addClass("hidden");
    empty_editor(true);
  });

  $("#edit-driver").on("click", function(){
    var driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
    if(verifyLoggedUser(driver_owner)){
      $("#driver-editor").removeClass("hidden").addClass("visible");
      $("#driver-viewer").removeClass("visible").addClass("hidden");
      populate_editor(true);
    }
  });

  $('#driver-photo').on('change',function(){
  if($(this).get(0).files.length > 0){ // only if a file is selected
    var fileSize = $(this).get(0).files[0].size;
    //console.log(fileSize);
    if(fileSize > 1024 * 2000){
      $("label[for='driver-photo']").addClass("invalid");
    } else {
      $("label[for='driver-photo']").removeClass("invalid");
    }
  }
});

$('#driver-helmet').on('change',function(){
if($(this).get(0).files.length > 0){ // only if a file is selected
  var fileSize = $(this).get(0).files[0].size;
  //console.log(fileSize);
  if(fileSize > 1024 * 2000){
    $("label[for='driver-helmet']").addClass("invalid");
  } else {
    $("label[for='driver-helmet']").removeClass("invalid");
  }
}
});

  $("#save-driver").on("click", function(){

    if($("#driver-id").html() != ""){
      var driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
      if(!verifyLoggedUser(driver_owner)){
        console.log("Não é dono");
        return false;

      }
    }

    if($("label").hasClass("invalid")){
      console.log("Imagem inválida");
      return false;

    }

    id = $("#driver-id").html();

    var formData = new FormData();

    var previous_photo = "default-photo.png";
    var previous_helmet = "default-helmet2.png";
    formData.append("name", $("#driver-name").val());
    formData.append("tv-name", $("#driver-small-name").val());
    formData.append("genre", $("#driver-genre").val());
    formData.append("bio", $("#driver-bio").val());
    formData.append("level", $("#driver-level").val());
    formData.append("hot-lap", $("#driver-hot-lap").val());
    formData.append("pace", $("#driver-pace").val());
    formData.append("start-skills", $("#driver-start-skills").val());
    formData.append("car-setup", $("#driver-car-setup").val());
    formData.append("rain-skills", $("#driver-rain-skills").val());
    formData.append("aggressiveness", $("#driver-aggressiveness").val());
    formData.append("number", $("#driver-number").val());
    formData.append("country", $("#driver-country").val());
    formData.append("birth-date", $("#driver-birth-date").val());
    formData.append("birth-place", $("#driver-birth-place").val());
    formData.append("status", $("#driver-status").val());

      if($("#driver-id").html() != ""){
        previous_photo = (photo.substr(photo.lastIndexOf("/") + 1));
      }

      if($("#driver-id").html() != ""){
        previous_helmet = (helmet.substr(helmet.lastIndexOf("/") + 1));
      }

      // photo
      var inputPhoto = $("#driver-photo")[0];
      var loadPhoto;

      if (inputPhoto.files.length > 0) {
        loadPhoto = inputPhoto.files[0];
      } else {
         loadPhoto = null;
      }

      // helmet
      var inputHelmet = $("#driver-helmet")[0];
      var loadHelmet;

      if (inputHelmet.files.length > 0) {
        loadHelmet = inputHelmet.files[0];
      } else {
         loadHelmet = null;
      }

      formData.append("id", id);
      formData.append("previous_photo", previous_photo);
      formData.append("previous_helmet", previous_helmet);
      if(loadPhoto != null){
        formData.append("photo", loadPhoto);
      }
      if(loadHelmet != null){
        formData.append("helmet", loadHelmet);
      }

      $.ajax({
        url: 'modify_driver.php',
        type: 'POST',
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json',
        data: formData
      })
      .done(function(data) {
        if(data.success){
          $("#driver-name-bar").html(data.error_msg).addClass("request_success").removeClass("request_failure");

          if(data.new_driver){
            empty_editor(false);
          } else {
            display_driver(true);
          }


        } else {
          $("#driver-name-bar").html(data.error_msg).addClass("request_failure").removeClass("request_success");

        }
      })
      .fail(function() {

        $("#driver-name-bar").html("Erro na solicitação, contacte o admin.").addClass("request_failure").removeClass("request_success");
      });

    //console.log(id);

  });


});

</script>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
