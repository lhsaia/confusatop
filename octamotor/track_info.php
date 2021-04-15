<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "OctaMotor - Circuitos";
//$css_filename = "";
$css_login = 'login';
$aux_css = "driver_info";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/track.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/competition.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$octa_database = new OctamotorDatabase();
$odb = $octa_database->getConnection();
$track = new Track($odb);

$pais = new Pais($db);

$track_list = $track->getTracksList();
$country_list = $pais->read(null, null, null);

?>
<div id='loadingDiv'><img src='/octamotor/images/lights.gif'/></div>
<div id="container-home-octamotor">
  <!-- Track viewer start -->
  <div id="driver-viewer" class="visible">
    <div class="container-control">
      <?php
      if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && !$_SESSION['emTestes']){
        echo "<a id='create-new-driver' class='editor-button'>Criar</a>";
      }

      echo "<select id='select-driver'>";
      foreach($track_list as $track_unit){
        echo "<option data-owner='{$track_unit['owner']}' value='{$track_unit['id']}'>{$track_unit['name']} </option>";
      }
      echo "</select>";

      if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && !$_SESSION['emTestes']){
        echo "<a id='edit-driver' class='editor-button'>Editar</a>";
      }

        ?>

    </div>
    <div class="container-driver-main">
      <div id="container-driver-pictures">
        <div id='container-track-image'>
          <img id="track-image-display" class="image" src="" />
        </div>
      </div>
      <div id="container-driver-info">
        <span class="driver-info"><span class="driver-info-title">País: </span><span id="track-country-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Nome: </span><span id="track-name-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Inaugurado em: </span><span id="track-first-used-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Comprimento: </span><span id="track-length-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Volta mais rápida: </span><span id="track-best-lap-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Principal competição: </span><span id="main-competition-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Sobre: </span><p id="track-about-info"></p></span>
      </div>
    </div>
  </div>
    <!-- Track viewer end -->

    <!-- Track editor start -->
  <div id="driver-editor" class="hidden">
    <div class="container-control">
      <a id="save-driver" class="editor-button">Salvar</a>
      <p id="driver-name-bar"></p>
      <a id="cancel-driver" class="editor-button">Cancelar</a>
    </div>
    <div class="container-driver-main">
      <div id="container-basic-form">
        <form id="driver-basic-form">
          <div hidden id="track-id"></div>
          <div class='form-group'>
            <label for="track-name">Nome</label>
            <input type="text" id="track-name" placeholder="ex. Baku City Circuit"/>
          </div>
          <div class='form-group'>
            <label for="track-country">País</label>
            <select id="track-country" required>
              <option value="" selected disable>Selecione o país...</option>
              <?php
              while($result = $country_list->fetch(PDO::FETCH_ASSOC) ){
                echo "<option value='" . $result["id"] . "'>" . $result["nome"] . "</option>";
              }
              ?>
            </select>
          </div>
          <div class='form-group'>
            <label for="track-first-used">Ano de inauguração</label>
            <input type="number" min='1900' id="track-first-used" placeholder="ex. 1998"/>
          </div>
          <div class='form-group'>
            <label for="track-about">Sobre</label>
            <textArea id="track-about" placeholder="ex. História do circuito, curiosidades, etc."></textArea>
          </div>
        </form>
      </div>
      <div id="container-image-form">
        <form id="driver-image-form">
          <div class='form-group'>
            <label class="picture-label" for="track-image"><span id="track-image-text">Circuito</span><img class="hidden" id="track-image-preview" src=""/></label>
            <input type="file" id="track-image" onchange="readURL(this, 'image');"/>
          </div>
        </form>
      </div>
      <div id="container-level-form">
        <form id="driver-level-form">
          <div class="form-master-group">
            <div class='form-group'>
              <label>Informações técnicas</label>
            </div>
            <div class='form-group'>
              <label for="track-length">Comprimento (m)</label>
              <input type="number" id="track-length"/>
            </div>
            <div class='form-group'>
              <label for="track-curves">Curvas (#)</label>
              <input type="number" min="2" id="track-curves"/>
            </div>
            <div class='form-group'>
              <label for="track-base-time">Tempo base (s)</label>
              <input type="number" id="track-base-time"/>
            </div>
            <div class='form-group'>
              <label for="track-pit-lane" title="Tempo adicional perdido ao passar pela pit lane sem parar">Tempo extra pit lane (s)</label>
              <input type="number" id="track-pit-lane"/>
            </div>
            <div id="moz-track-style" class='form-group special-form-group' data-css="">
              <label for="track-style">Estilo</label>
              <input class='moz-special' data-css="" type="range" min="1" max="5" value="3" id="track-style"/>
            </div>
            <div id="moz-rain-chance" class='form-group special-form-group' data-css="" >
              <label for="track-rain-chance">Chance de chuva (%)</label>
              <input class='moz-special' data-css="" type="range" min="0" max="100" value="20" id="track-rain-chance"/>
            </div>
            <div id="moz-avg-temp" class='form-group special-form-group' data-css="">
              <label for="track-avg-temp">Temperatura média (°C)</label>
              <input class='moz-special' data-css="" type="range" min="-40" max="45" value="25" id="track-avg-temp"/>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>

var logged_user = {  };
var name;
var country;
var country_id;
var track_image;
var name;
var first_used;
var about;
var length;
var curves;
var base_time;
var style;
var rain_chance;
var avg_temp;
var id;
var pit_lane_time;

function readURL(input, target_div) {
       if (input.files && input.files[0]) {
           var reader = new FileReader();

           reader.onload = function (e) {
               $('#track-'+target_div + "-preview")
                   .attr('src', e.target.result).removeClass("hidden");
              $('#track-' + target_div + '-text').addClass("hidden");
              $('label[for="track-'+target_div+'"]').addClass("no-padding");
                   // .width(200)
                   // .height(200);
           };

           reader.readAsDataURL(input.files[0]);
       }
   }

function display_track(updateEditor){
  var driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
  if(!verifyLoggedUser(driver_owner)){
    $("#edit-driver").hide().attr("disabled", "disabled");
  } else {
    $("#edit-driver").show().removeAttr("disabled");
  }
  var id = $("#select-driver").val();

  $.ajax({
    url: 'track_info_request.php',
    type: 'POST',
    dataType: 'json',
    data: {id: id}
  })
  .done(function(data) {
    country = data.track_data.country_name;
    if(data.track_data.image){
      track_image = "/octamotor/images/track/" + data.track_data.image;
    } else {
      track_image = "/octamotor/images/track/default-image.png";
    }
    name =  data.track_data.name;
    country_id = data.track_data.country_id;
    first_used = data.track_data.first_used;
    about = data.track_data.about;
    length = data.track_data.length;
    curves = data.track_data.curves;
    base_time = data.track_data.lap_base_time;
    style = data.track_data.style;
    rain_chance = data.track_data.rain_possibility;
    avg_temp = data.track_data.avg_temp;
    pit_lane_time = data.track_data.pit_lane_time;

    $("#track-country-info").html(country);
    $("#track-name-info").html(name);
    $("#track-first-used-info").html(first_used);
    $("#track-about-info").html(about);
    $("#track-length-info").html(length + "m");


    $("#track-image-display").attr("src", track_image);

    if(updateEditor){
      populate_editor(false);
    }
  })
  .fail(function() {
    console.log("error");
  });

}

function track_correlation(caller){
  var curves_corr = parseFloat($("#track-curves").val());
  var length_corr = parseFloat($("#track-length").val());
  var time_corr = parseFloat($("#track-base-time").val());

  var fac_a = 0.0456898301446877;
  var fac_b = 0.00986378425371254;
  var fac_c = 22.752341462849;

  var estimated_time;
  var estimated_length;

  var oval_circuit;

  if(curves_corr < 4){
    oval_circuit = 0.7;
  } else {
    oval_circuit = 1;
  }

if(!isNaN(curves_corr)){
  if(caller == 0){ //length last
    if(!isNaN(length_corr)){
      estimated_time = ((oval_circuit)*(fac_a * Math.pow(curves_corr, 2) + fac_b * length_corr + fac_c)).toFixed(3);
      if(isNaN(time_corr) || Math.abs(time_corr - estimated_time) > 5.0 ){
        $("#track-base-time").val(estimated_time);
      }
    }
  } else if(caller == 1){ //curves last
    if(!isNaN(length_corr)){
      track_correlation(0);
    } else if(!isNaN(time_corr)){
      track_correlation(2);
    }
  } else { // time last
    if(!isNaN(time_corr)){
      estimated_length = Math.ceil((time_corr - oval_circuit * fac_a * Math.pow(curves_corr, 2) - oval_circuit*fac_c)/(oval_circuit*fac_b));
      if(isNaN(length_corr) || Math.abs(length_corr - estimated_length) > 500.0 ){
        $("#track-length").val(estimated_length);
      }
    }
  }
}

}

function populate_editor(changeBar){
  if(changeBar){
    $("#driver-name-bar").html("Editar circuito").removeClass("request_failure").removeClass("request_success");
  }
  $("#track-id").html($("#select-driver").val());
  $("#track-name").val(name);
  $("#track-image").val("");
  $("#track-country").val(country_id);
  $("#track-first-used").val(first_used);
  $("#track-about").val(about);
  $("#track-length").val(length);
  $("#track-curves").val(curves);
  $("#track-base-time").val(base_time);
  $("#track-style").val(style);
  $("#track-rain-chance").val(rain_chance);
  $("#track-avg-temp").val(avg_temp);
  $("#track-image-preview").attr("src", track_image).removeClass("hidden");
  $("#track-image-text").addClass("hidden");
  $(".picture-label").addClass("no-padding");
  $("#track-rain-chance").attr("data-css", $("#track-rain-chance").val() +"%");
  $("#track-avg-temp").attr("data-css",   $("#track-avg-temp").val() );
  $("#track-style").attr("data-css",convert_style($("#track-style").val()));

  $("#moz-rain-chance").attr("data-css", $("#track-rain-chance").val() +"%");
  $("#moz-avg-temp").attr("data-css",   $("#track-avg-temp").val() );
  $("#moz-track-style").attr("data-css",convert_style($("#track-style").val()));

  $("#track-pit-lane").val(pit_lane_time);

  console.log(style);

}

function convert_style(style_code){

  var style_int = parseInt(style_code);

  switch(style_int) {
    case 1:
      return "Muito aberto";
      break;
    case 2:
      return "Aberto";
      break;
    case 3:
      return "Equilibrado";
      break;
    case 4:
      return "Travado";
      break;
    case 5:
      return "Muito travado";
      break;
    default:
      return false;
  }
}


function empty_editor(reloadBar){
  if(reloadBar){
    $("#driver-name-bar").html("Criar circuito").removeClass("request_failure").removeClass("request_success");
  }
  $("#track-rain-chance").attr("data-css","20%");
  $("#track-avg-temp").attr("data-css", "25");
  $("#track-style").attr("data-css",convert_style(3));

  $("#moz-rain-chance").attr("data-css","20%");
  $("#moz-avg-temp").attr("data-css", "25");
  $("#moz-track-style").attr("data-css",convert_style(3));

  $("#track-name").val("");
  $("#track-first-used").val("");
  $("#track-about").val("");
  $("#track-length").val("");
  $("#track-curves").val("");
  $("#track-base-time").val("");
  $("#track-image-preview").attr("src", track_image).removeClass("hidden");
  $("#track-image-text").addClass("hidden");
  $(".picture-label").addClass("no-padding");
  $("#track-id").html("");
  $("#track-image-preview").attr("src", "").addClass("hidden");
  $("#track-image-text").removeClass("hidden");
  $(".picture-label").removeClass("no-padding");
  $("#track-image").val("");
  $("#track-pit-lane").val("");

}

$("#select-driver").change(function(){
  display_track(false);
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
  display_track(false);

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

  $('#track-image').on('change',function(){
  if($(this).get(0).files.length > 0){ // only if a file is selected
    var fileSize = $(this).get(0).files[0].size;
    //console.log(fileSize);
    if(fileSize > 1024 * 2000){
      $("label[for='track-image']").addClass("invalid");
    } else {
      $("label[for='track-image']").removeClass("invalid");
    }
  }
});

$("#track-style").change(function(){
  $(this).attr("data-css",convert_style($(this).val()));
  $("#moz-track-style").attr("data-css",convert_style($(this).val()));
});

$("#track-rain-chance").change(function(){
  $(this).attr("data-css",$(this).val() + "%");
  $("#moz-rain-chance").attr("data-css",$(this).val() + "%");
});

$("#track-avg-temp").change(function(){
  $(this).attr("data-css",$(this).val());
  $("#moz-avg-temp").attr("data-css",$(this).val());
});

  $("#save-driver").on("click", function(){

if($("#track-id").html() != ""){
  var driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
  if(!verifyLoggedUser(driver_owner)){
    return false;
  }
}


    if($("label").hasClass("invalid")){
      return false;
    }

    id = $("#track-id").html();

    var formData = new FormData();

    var previous_image = "default-image.png";
    formData.append("name", $("#track-name").val());
    formData.append("country", $("#track-country").val());
    formData.append("about", $("#track-about").val());
    formData.append("first-used", $("#track-first-used").val());
    formData.append("length", $("#track-length").val());
    formData.append("curves", $("#track-curves").val());
    formData.append("base-time", $("#track-base-time").val());
    formData.append("style", $("#track-style").val());
    formData.append("rain-chance", $("#track-rain-chance").val());
    formData.append("avg-temp", $("#track-avg-temp").val());
    formData.append("pit-lane-time", $("#track-pit-lane").val());


      if($("#track-id").html() != ""){
        previous_image = (track_image.substr(track_image.lastIndexOf("/") + 1));
      }


      // circuit
      var inputImage = $("#track-image")[0];
      var loadImage;

      if (inputImage.files.length > 0) {
        loadImage = inputImage.files[0];
      } else {
         loadImage = null;
      }

      formData.append("id", id);
      formData.append("previous_image", previous_image);
      if(loadImage != null){
        formData.append("image", loadImage);
      }

      // for (var pair of formData.entries()) {
      //   console.log(pair[0]+ ', ' + pair[1]);
      // }
      // return false;
      $.ajax({
        url: 'modify_track.php',
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
            display_track(true);
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


  $("#track-length").change(function(){
    track_correlation(0);
  });

  $("#track-curves").change(function(){
    track_correlation(1);
  });

  $("#track-base-time").change(function(){
    track_correlation(2);
  });


});

</script>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
