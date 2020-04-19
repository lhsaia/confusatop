<!DOCTYPE html>

<?php

/// criar bloqueios para não admin, não dono e fora de época
/// bloqueios de nível

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "OctaMotor - Equipes";
//$css_filename = "";
$css_login = 'login';
$aux_css = "driver_info";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/driver.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/car.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/competition.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$octa_database = new OctamotorDatabase();
$odb = $octa_database->getConnection();
$driver = new Driver($odb);
$car = new Car($odb);
$competition = new Competition($odb);

$pais = new Pais($db);

$team_list = $car->getCarsList();
$country_list = $pais->read(null, null, null);
$driver_list = $driver->getDriversList();
$competition_list = $competition->getCompetitionList();

?>
<div id='loadingDiv'><img src='/octamotor/images/lights.gif'/></div>
<div id="container-home-octamotor">
  <!-- Car viewer start -->
  <div id="driver-viewer" class="visible">
    <div class="container-control">
      <?php
      if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
        echo "<a id='create-new-driver' class='editor-button'>Criar</a>";
      }

      echo "<select id='select-driver'>";
      foreach($team_list as $car_unit){
        if($car_unit['comp_name'] != ""){
          $comp = " (" . $car_unit['comp_name'] . ")";
        } else {
          $comp = "";
        }
        echo "<option data-owner='{$car_unit['owner']}' value='{$car_unit['id']}'>{$car_unit['team_name']}{$comp} </option>";
      }
      echo "</select>";

      if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
        echo "<a id='edit-driver' class='editor-button'>Editar</a>";
      }

        ?>

    </div>
    <div class="container-driver-main">
      <div id="container-driver-pictures">
        <div id='container-drivers-logo-suit'>
          <div id="container-team-drivers">
            <div id="container-driver-profile-1" class="image-container" data-driver-id="">
              <span class='driver-profile-label' data-after="" id="driver-profile-label-1">
                <img id="profile-picture-driver-1" class="image" src="" />
              </span>
            </div>
            <div id="container-driver-profile-2" class="image-container" data-driver-id="">
              <span class='driver-profile-label' data-after=""  id="driver-profile-label-2">
                <img id="profile-picture-driver-2" class="image" src="" />
              </span>
            </div>
          </div>
          <div id='container-logo-suit'>
            <div id="container-logo" class="image-container">
              <img id="car-logo-display" class="image" src="" />
            </div>
            <div id="container-suit" class="image-container">
              <img id="car-suit-display" class="image" src="" />
            </div>
          </div>
        </div>
        <div id="container-car-images">
          <div id="container-car-full" class="image-container">
            <img id="car-picture-display" class="image" src="" />
          </div>
        </div>

      </div>
      <div id="container-driver-info">
        <span class="driver-info"><span class="driver-info-title">País: </span><span id="car-country-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Base: </span><span id="car-base-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Chassi: </span><span id="car-chassis-name-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Motor: </span><span id="car-engine-name-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Chefe de equipe: </span><span id="car-team-chief-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Chefe técnico: </span><span id="car-tech-chief-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Competição: </span><span id="competition-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Primeira corrida: </span></span>
        <span class="driver-info"><span class="driver-info-title">Pontos: </span></span>
        <span class="driver-info"><span class="driver-info-title">Melhor posição (corrida): </span></span>
        <span class="driver-info"><span class="driver-info-title">Melhor posição (grid): </span></span>
        <span class="driver-info"><span class="driver-info-title">Pódios: </span></span>
        <span class="driver-info"><span class="driver-info-title">Títulos: </span></span>
        <span class="driver-info"><span class="driver-info-title">Volta mais rápida: </span></span>
      </div>
    </div>
  </div>
    <!-- Car viewer end -->

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
          <div hidden id="car-id"></div>
          <div class='form-group'>
            <label for="car-name">Nome completo</label>
            <input type="text" id="car-name" placeholder="ex. Aerocar Petrogás"/>
          </div>
          <div class='form-group'>
            <label for="car-small-name">Nome de TV</label>
            <input type="text" id="car-small-name" placeholder="ex. Domarkypha"/>
          </div>
          <div class='form-group'>
            <label for="car-country">País</label>
            <select id="car-country" required>
              <option value="" selected disable>Selecione o país...</option>
              <?php
              while($result = $country_list->fetch(PDO::FETCH_ASSOC) ){
                echo "<option value='" . $result["id"] . "'>" . $result["nome"] . "</option>";
              }
              ?>
            </select>
          </div>
          <div class='form-group'>
            <label for="car-base">Base</label>
            <input type="text" id="car-base" placeholder="ex. Tonomoranster, Estapafúrdia"/>
          </div>
          <div class='form-group'>
            <label for="car-team-chief">Chefe de equipe</label>
            <input type="text" id="car-team-chief" placeholder="ex. Cyril Abiteboul"/>
          </div>
          <div class='form-group'>
            <label for="car-tech-chief">Chefe técnico</label>
            <input type="text" id="car-tech-chief" placeholder="ex. James Allison"/>
          </div>
          <div class='form-group'>
            <label for="car-chassis-name">Chassi</label>
            <input type="text" id="car-chassis-name" placeholder="ex. SF90"/>
          </div>
          <div class='form-group'>
            <label for="car-engine-name">Motor</label>
            <input type="text" id="car-engine-name" placeholder="ex. Honda"/>
          </div>
          <div class='form-group'>
            <label for="car-driver-one">Piloto 1</label>
            <select id="car-driver-one">
              <option data-team="0" value="0" selected>Sem piloto</option>
              <?php
              foreach($driver_list as $driver_unit){
                if($driver_unit['status'] > 0){
                  echo "<option data-team='{$driver_unit['car_id']}' value='" . $driver_unit["id"] . "'>" . $driver_unit["name"] . "</option>";
                }
              }
              ?>
            </select>
          </div>
          <div class='form-group'>
            <label for="car-driver-two">Piloto 2</label>
            <select id="car-driver-two">
              <option data-team="0" value="0" selected>Sem piloto</option>
              <?php
              foreach($driver_list as $driver_unit){
                if($driver_unit['status'] > 0){
                  echo "<option data-team='{$driver_unit['car_id']}' value='" . $driver_unit["id"] . "'>" . $driver_unit["name"] . "</option>";
                }
              }
              ?>
            </select>
          </div>
          <div class='form-group'>
            <label for="car-competition">Competição</label>
            <select id="car-competition" required>
              <option value="" selected disable>Selecione a competição...</option>
              <?php
              foreach($competition_list as $competition_unit){
                echo "<option value='" . $competition_unit["id"] . "'>" . $competition_unit["name"] . "</option>";
              }
              ?>
            </select>
          </div>
        </form>
      </div>
      <div id="container-image-form">
        <form id="driver-image-form">
          <div class='form-group'>
            <label for="car-color">Cor principal</label>
            <input type="color" id="car-color"/>
          </div>
          <div class='form-group'>
            <label class="picture-label" for="car-logo"><span id="car-logo-text">Logo</span><img class="hidden" id="car-logo-preview" src=""/></label>
            <input type="file" id="car-logo" onchange="readURL(this, 'logo');"/>
          </div>
          <div class='form-group'>
            <label class='picture-label' for="car-picture"><span id="car-picture-text">Carro</span><img class="hidden" id="car-picture-preview" src=""/></label>
            <input type="file" id="car-picture" onchange="readURL(this, 'picture');"/>
          </div>
          <div class='form-group'>
            <label class='picture-label' for="car-suit"><span id="car-suit-text">Macacão</span><img class="hidden" id="car-suit-preview" src=""/></label>
            <input type="file" id="car-suit" onchange="readURL(this, 'suit');"/>
          </div>
                    <div class='form-group'>
              <button class='kit-button' id='kit-carro'>
                  <i class="fas fa-paint-brush"></i><span> Kit carro</span>
                  <iframe id='download_car' hidden src=""></iframe>
              </button>
          </div>
                    <div class='form-group'>
              <button class='kit-button' id='kit-macacao'>
                  <i class="fas fa-paint-brush"></i><span> Kit macacão</span>
                  <iframe id='download_suit' hidden src=""></iframe>
              </button>
          </div>
        </form>
      </div>
      <div id="container-level-form">
        <form id="driver-level-form">
          <div class="form-master-group">
            <div class='form-group'>
              <label>Atributos</label>
            </div>
            <div class='form-group'>
              <label for="car-chassis">Chassi</label>
              <input type="range" min="1" max="10" id="car-chassis"/>
            </div>
            <div class='form-group'>
              <label for="car-engine">Motor</label>
              <input type="range" min="1" max="10" id="car-engine"/>
            </div>
            <div class='form-group'>
              <label for="car-reliability">Confiabilidade</label>
              <input type="range" min="1" max="10" id="car-reliability"/>
            </div>
            <div class='form-group'>
              <label for="car-pitstop-skills">Pit Stop</label>
              <input type="range" min="1" max="10" id="car-pitstop-skills"/>
            </div>
            <div class='form-group'>
              <label for="car-strategy">Estratégia</label>
              <input type="range" min="1" max="10" id="car-strategy"/>
            </div>
          </div>
        </form>
        <div id="attribute-chart"></div>
      </div>
    </div>
  </div>
</div>

<script>

var logged_user = {  };
var name;
var country;
var country_id;
var picture;
var team_name;
var competition;
var engine;
var chassis;
var pit_stop_skills;
var strategy;
var reliability;
var team_chief;
var tech_chief;
var base;
var chassis_name;
var engine_name;
var id;
var driver_one_id;
var driver_one_photo;
var driver_one_name;
var driver_two_id;
var driver_two_photo;
var driver_two_name;
var competition_id;
var logo;
var car_suit;
var tv_name;
var color;


function readURL(input, target_div) {
       if (input.files && input.files[0]) {
           var reader = new FileReader();

           reader.onload = function (e) {
               $('#car-'+target_div + "-preview")
                   .attr('src', e.target.result).removeClass("hidden");
              $('#car-' + target_div + '-text').addClass("hidden");
              $('label[for="car-'+target_div+'"]').addClass("no-padding");
                   // .width(200)
                   // .height(200);
           };

           reader.readAsDataURL(input.files[0]);
       }
   }

function display_car(updateEditor){
  var driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
  if(!verifyLoggedUser(driver_owner)){
    $("#edit-driver").hide().attr("disabled", "disabled");
  } else {
    $("#edit-driver").show().removeAttr("disabled");
  }
  var id = $("#select-driver").val();

  $.ajax({
    url: 'car_info_request.php',
    type: 'POST',
    dataType: 'json',
    data: {id: id}
  })
  .done(function(data) {
    country = data.car_data.country_name;
    color = data.car_data.color;
    tv_name = data.car_data.tv_name;
    if(data.car_data.car_picture){
      car_picture = "/octamotor/images/car/"  + data.car_data.car_picture;
    } else {
      car_picture = "/octamotor/images/car/default-car2.png";
    }
    if(data.car_data.logo){
      logo = "/octamotor/images/car_logo/"  + data.car_data.logo;
    } else {
      logo = "/octamotor/images/car_logo/default-car-logo.png";
    }
    if(data.car_data.car_suit){
      car_suit = "/octamotor/images/suit/"  + data.car_data.car_suit;
    } else {
      car_suit = "/octamotor/images/suit/default-suit.png";
    }
    base =  data.car_data.base;
    team_chief = data.car_data.team_chief;
    tech_chief = data.car_data.tech_chief;
    engine_name = data.car_data.engine_name;
    chassis_name = data.car_data.chassis_name;
    country_id = data.car_data.country_id;
    engine = data.car_data.engine;
    chassis = data.car_data.chassis;
    reliability = data.car_data.reliability;
    pit_stop_skills = data.car_data.pit_stop_skills;
    strategy = data.car_data.strategy;
    competition = data.car_data.competition;
    name = data.car_data.name;
    competition_id = data.car_data.competition_id;

    if(data.driver_data){
      if(data.driver_data.first){
        if(data.driver_data.first.photo){
          driver_one_photo = "/octamotor/images/picture/"  + data.driver_data.first.photo;
        } else {
          driver_one_photo = "/octamotor/images/picture/default-photo.png";
        }

        driver_one_name = data.driver_data.first.name;
        driver_one_id = data.driver_data.first.id;
      } else {
        driver_one_photo = "/octamotor/images/picture/default-photo.png";
        driver_one_name = "";
        driver_one_id = "";
      }

      if(data.driver_data.second){
        if(data.driver_data.second.photo){
          driver_two_photo = "/octamotor/images/picture/"  + data.driver_data.second.photo;
        } else {
          driver_two_photo = "/octamotor/images/picture/default-photo.png";
        }
        driver_two_name = data.driver_data.second.name;
        driver_two_id = data.driver_data.second.id;
      } else {
        driver_two_photo = "/octamotor/images/picture/default-photo.png";
        driver_two_name = "";
        driver_two_id = "";
      }

    } else {
      driver_two_photo = "/octamotor/images/picture/default-photo.png";
      driver_two_name = "";
      driver_two_id = "";
      driver_one_photo = "/octamotor/images/picture/default-photo.png";
      driver_one_name = "";
      driver_one_id = "";
    }



    $("#car-country-info").html(country);
    $("#car-base-info").html(base);
    $("#car-team-chief-info").html(team_chief);
    $("#car-tech-chief-info").html(tech_chief);
    $("#car-engine-name-info").html(engine_name);
    $("#car-chassis-name-info").html(chassis_name);
    $("#competition-info").html(competition);
    $("#car-picture-display").attr("src", car_picture);
    $("#car-logo-display").attr("src", logo);
    $("#car-suit-display").attr("src", car_suit);
    $("#profile-picture-driver-1").attr("src", driver_one_photo);
    $("#profile-picture-driver-2").attr("src", driver_two_photo);
    $("#driver-profile-label-1").attr("data-after", driver_one_name);
    $("#driver-profile-label-2").attr("data-after", driver_two_name);
    $("#container-driver-profile-1").attr("data-driver-id", driver_one_id);
    $("#container-driver-profile-2").attr("data-driver-id", driver_two_id);

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
    $("#driver-name-bar").html("Editar equipe").removeClass("request_failure").removeClass("request_success");
  }
  $("#car-id").html($("#select-driver").val());
  $("#car-name").val(name);
  $("#car-small-name").val(tv_name);
  $("#car-color").val(color);
  $("#car-base").val(base);
  $("#car-country").val(country_id);
  $("#car-team-chief").val(team_chief);
  $("#car-tech-chief").val(tech_chief);
  $("#car-engine-name").val(engine_name);
  $("#car-chassis-name").val(chassis_name);
  $("#car-competition").val(competition_id);
  if(driver_one_id){
    $("#car-driver-one").val(driver_one_id);
  } else {
    $("#car-driver-one").val(0);
  }
  if(driver_two_id){
    $("#car-driver-two").val(driver_two_id);
  } else {
    $("#car-driver-two").val(0);
  }

  $("#car-engine").val(engine);
  $("#car-chassis").val(chassis);
  $("#car-reliability").val(reliability);
  $("#car-pitstop-skills").val(pit_stop_skills);
  $("#car-strategy").val(strategy);
  $("#car-logo-preview").attr("src", logo).removeClass("hidden");
  $("#car-picture-preview").attr("src", car_picture).removeClass("hidden");
  $("#car-suit-preview").attr("src", car_suit).removeClass("hidden");
  $("#car-logo-text").addClass("hidden");
  $("#car-picture-text").addClass("hidden");
  $("#car-suit-text").addClass("hidden");
  $(".picture-label").addClass("no-padding");
  $("#car-logo").val("");
  $("#car-picture").val("");
  $("#car-suit").val("");

  id = $("#car-id").html();
  $("select[id^=car-driver] option").each(function(){
    if($(this).attr("data-team") != 0 && $(this).attr("data-team") != id){
      $(this).hide();
      $(this).attr("disable", "disable");
    } else {
      $(this).show();
      $(this).removeAttr("disable");
    }
  });

    attribute_chart();
}



function empty_editor(reloadBar){
  if(reloadBar){
    $("#driver-name-bar").html("Criar equipe").removeClass("request_failure").removeClass("request_success");
  }
  $("#car-id").html("");
  $("#car-country").val("");
  $("#car-name").val("");
  $("#car-small-name").val("");
  $("#car-color").val("");
  $("#car-base").val("");
  $("#car-tech-chief").val("");
  $("#car-team-chief").val("");
  $("#car-engine-name").val("");
  $("#car-chassis-name").val("");
  $("#car-competition").val("");
  $("#car-driver-one").val(0);
  $("#car-driver-two").val(0);
  $("#car-engine").val("");
  $("#car-chassis").val("");
  $("#car-reliability").val("");
  $("#car-strategy").val("");
  $("#car-pitstop-skills").val("");
  $("#car-logo-preview").attr("src", "").addClass("hidden");
  $("#car-picture-preview").attr("src", "").addClass("hidden");
  $("#car-suit-preview").attr("src", "").addClass("hidden");
  $("#car-logo-text").removeClass("hidden");
  $("#car-picture-text").removeClass("hidden");
  $("#car-suit-text").removeClass("hidden");
  $(".picture-label").removeClass("no-padding");
  $("#car-logo").val("");
  $("#car-picture").val("");
  $("#car-suit").val("");

  $("select[id^=car-driver] option").each(function(){
    if($(this).attr("data-team") != 0){
      $(this).hide();
      $(this).attr("disable", "disable");
    } else {
      $(this).show();
      $(this).removeAttr("disable");
    }
  });

    attribute_chart();
}

function attribute_chart(){

var engine = $("#car-engine").val();
var chassis = $("#car-chassis").val();
var strategy = $("#car-strategy").val();
var pitstopSkills = $("#car-pitstop-skills").val();
var reliability = $("#car-reliability").val();

var results = new Array(engine, chassis, strategy, reliability, pitstopSkills);

//console.log(results);

  data = [{
  type: 'scatterpolar',
    mode: "markers",
  //r: [hotLap, pace, aggressiveness, startSkills, rainSkills, carSetup],
  r: results,
  theta: ['Motor','Chassi','Estratégia','Confiabilidade',  'Pit Stop'],
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
  display_car(false);
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

setLoggedUser();


var $loading = $('#loadingDiv').hide();
$(document)
  .ajaxStart(function () {
    $loading.show();
  })
  .ajaxStop(function () {
    $loading.hide();
  });

$("document").ready(function(){
  display_car(false);
  attribute_chart();

    $("#kit-carro").click(function(e){

      e.preventDefault();

            $.ajax({
        url: 'download_helmet.php',
        type: 'POST',
        cache: false,
        dataType: 'json',
        data: {item: "carro" }
      })
      .done(function(data) {
        $("#download_car").attr("src",decodeURIComponent(data.data) );
      });
  });

    $("#kit-macacao").click(function(e){

      e.preventDefault();

            $.ajax({
        url: 'download_helmet.php',
        type: 'POST',
        cache: false,
        dataType: 'json',
        data: {item: "macacao" }
      })
      .done(function(data) {
        $("#download_suit").attr("src",decodeURIComponent(data.data) );
      });
  });

  $("#driver-level-form :input").change(function() {
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

  $('#car-logo').on('change',function(){
  if($(this).get(0).files.length > 0){ // only if a file is selected
    var fileSize = $(this).get(0).files[0].size;
    //console.log(fileSize);
    if(fileSize > 1024 * 2000){
      $("label[for='car-logo']").addClass("invalid");
    } else {
      $("label[for='car-logo']").removeClass("invalid");
    }
  }
});

$('#car-picture').on('change',function(){
if($(this).get(0).files.length > 0){ // only if a file is selected
  var fileSize = $(this).get(0).files[0].size;
  //console.log(fileSize);
  if(fileSize > 1024 * 2000){
    $("label[for='car-picture']").addClass("invalid");
  } else {
    $("label[for='car-picture']").removeClass("invalid");
  }
}
});

$('#car-suit').on('change',function(){
if($(this).get(0).files.length > 0){ // only if a file is selected
  var fileSize = $(this).get(0).files[0].size;
  //console.log(fileSize);
  if(fileSize > 1024 * 2000){
    $("label[for='car-suit']").addClass("invalid");
  } else {
    $("label[for='car-suit']").removeClass("invalid");
  }
}
});

$("[id^=container-driver-profile]").on("click", function(){
  var driver_id = $(this).attr("data-driver-id");
  if(driver_id){
    window.location = "https://confusa.top/octamotor/driver_info.php?driver=" + driver_id ;
  }

});

  $("#save-driver").on("click", function(){

    console.log($("#car-id").html());
    if($("#car-id").html() != ""){
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

    id = $("#car-id").html();

    var formData = new FormData();

    var previous_picture = "default-car2.png";
    var previous_logo = "default-car-logo.png";
    var previous_suit = "default-suit.png";
    formData.append("name", $("#car-name").val());
    formData.append("tv-name", $("#car-small-name").val());
    formData.append("main-color", $("#car-color").val());
    formData.append("country", $("#car-country").val());
    formData.append("engine", $("#car-engine").val());
    formData.append("chassis", $("#car-chassis").val());
    formData.append("pitstop-skills", $("#car-pitstop-skills").val());
    formData.append("strategy", $("#car-strategy").val());
    formData.append("reliability", $("#car-reliability").val());
    formData.append("competition", $("#car-competition").val());
    formData.append("team-chief", $("#car-team-chief").val());
    formData.append("tech-chief", $("#car-tech-chief").val());
    formData.append("base", $("#car-base").val());
    formData.append("chassis-name", $("#car-chassis-name").val());
    formData.append("engine-name", $("#car-engine-name").val());

      if($("#car-id").html() != ""){
        previous_logo = (logo.substr(logo.lastIndexOf("/") + 1));
      }

      if($("#car-id").html() != ""){
        previous_picture = (car_picture.substr(car_picture.lastIndexOf("/") + 1));
      }

      if($("#car-id").html() != ""){
        previous_suit = (car_suit.substr(car_suit.lastIndexOf("/") + 1));
      }

      // logo
      var inputLogo = $("#car-logo")[0];
      var loadLogo;

      if (inputLogo.files.length > 0) {
        loadLogo = inputLogo.files[0];
      } else {
         loadLogo = null;
      }

      // car
      var inputPicture = $("#car-picture")[0];
      var loadPicture;

      if (inputPicture.files.length > 0) {
        loadPicture = inputPicture.files[0];
      } else {
         loadPicture = null;
      }

      // suit
      var inputSuit = $("#car-suit")[0];
      var loadSuit;

      if (inputSuit.files.length > 0) {
        loadSuit = inputSuit.files[0];
      } else {
         loadSuit = null;
      }

      formData.append("id", id);
      formData.append("previous_logo", previous_logo);
      formData.append("previous_picture", previous_picture);
      formData.append("previous_suit", previous_suit);
      if(loadLogo != null){
        formData.append("logo", loadLogo);
      }
      if(loadPicture != null){
        formData.append("picture", loadPicture);
      }
      if(loadSuit != null){
        formData.append("suit", loadSuit);
      }

      if($("#car-driver-one").val() != ""){
        formData.append("driver1", $("#car-driver-one").val());
      }

      if($("#car-driver-two").val() != ""){
        formData.append("driver2", $("#car-driver-two").val());
      }

      // for (var pair of formData.entries()) {
      //   console.log(pair[0]+ ', ' + pair[1]);
      // }
      // return false;
      $.ajax({
        url: 'modify_car.php',
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

          if(data.new_car){
            empty_editor(false);
          } else {
            display_car(true);
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
