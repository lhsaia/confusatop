<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

$idCompeticao = $_GET['id'];

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/export_torneios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$time = new Time($db);
$torneio = new ExportTorneio($db);
$competicao = new Competicao_clube($db);

$info = $competicao->readInfo($idCompeticao);
$nome_competicao = $info['nome'];
$ano_competicao = $info['ano'];
$sede_competicao = $info['sede'];
$federacao_competicao = $info['federacao'];
$federacao_id = $info['federacaoId'];
$logo_competicao = $info['logo'];
$genero_competicao = $info['genero'];
$dono_competicao = $info['dono'];

$options = $competicao->getOptions($idCompeticao);

// query caixa de seleção países
$stmtPais = $pais->read(null,null,false);
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $nome, $federacao, $dono, $bandeira);
	if($addArray[2] == $federacao_id || $federacao_id == 0){
		$listaPaises[] = $addArray;
	}
}

// query caixa de seleção torneios
$stmtTorneio = $torneio->readAll();
$listaTorneios = array();
while ($row_torneios = $stmtTorneio->fetch(PDO::FETCH_ASSOC)){
    extract($row_torneios);
    $addArray = array($ID, $Nome, $Federacao, $Genero, $NumParticipantes, $Participantes, $Sede);
    $listaTorneios[] = $addArray;
}

// query caixa de seleção times

$stmtTime = $time->read(null,false);
$listaTimes = array();
while ($row_times = $stmtTime->fetch(PDO::FETCH_ASSOC)){
    extract($row_times);
    $addArray = array($id, $nome, $paisTime, $Sexo, $nomePais, $escudo);
	if($addArray[3] == $genero_competicao){
		$listaTimes[] = $addArray;
	}
    
}

// Get information from tournament
$stmtLista = $competicao->carregarListaTimes($idCompeticao);
$listaCompeticao = array();
while ($row_lista = $stmtLista->fetch(PDO::FETCH_ASSOC)){
    extract($row_lista);
    $addArray = array($pais_time);
	$listaCompeticao[$codigo_time] = $addArray;
}


include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Lista de Times";
$css_filename = "home_redesign";
$aux_css = "lista_times_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>
<main class="propostas-container">
    <div id='errorbox'></div>
    <div class="propostas-card">
        <h2 class="propostas-title"><?php echo $page_title; ?></h2>
        <hr>
        
        <?php $random_loader = rand(1,5); ?>
        <div id='loading'><img src='/images/loaders/loader_style<?php echo $random_loader; ?>.gif'/></div>
        
        <div id='quadro_equipes_torneio'>
            <!-- Vagas carregadas via JS -->
        </div>
        <div style="margin-top: 30px;">
            <a href="competitionstatus.php?id=<?php echo $idCompeticao; ?>" style="display: inline-block; padding: 10px 20px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(0, 0, 0, 0.06)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.03)'">
                ← Voltar para a Competição
            </a>
        </div>
    </div>
</main>
<?php
 
 ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js"></script>

<script>

$( document ).ready(function(){

	 $("#toolbar").html('<div id="gerar_tabela"><span class="material-symbols-outlined">grid_view</span><span>Gerar Tabela</span></div>');
	 
	 $(document).on('click', '#gerar_tabela', function(){
		if(confirm("Deseja gerar a tabela de jogos? Isso apagará os jogos atuais desta competição!")){
			$('#loading').show();
			$.ajax({
				url: "gerar_tabela.php",
				method: "POST",
				data: {id: '<?php echo $idCompeticao ?>', tipo: '<?php echo isset($options['tipocompeticao']) ? (int)$options['tipocompeticao'] : 0; ?>'},
				success: function(data){
					$('#loading').hide();
					try {
						let res = typeof data === 'object' ? data : JSON.parse(data);
						if(res.success){
							alert("Sucesso! Foram gerados " + (res.total_jogos || 0) + " jogos. Redirecionando para os jogos...");
							window.location.href = 'listajogos.php?id=<?php echo $idCompeticao ?>';
						} else {
							alert("Erro ao gerar tabela: " + res.error);
						}
					} catch(err) {
						alert("Erro no parse do servidor: " + data);
					}
				},
				error: function(xhr, status, error){
					$('#loading').hide();
					alert("Erro de conexão ao gerar tabela: " + error);
				}
			});
		}
	});

	var codigo_competicao = '<?php echo $idCompeticao ?>';
	var numero_times = '<?php echo $options['numero_times'] ?>';
	var lista_paises = <?php echo json_encode($listaPaises) ?>;
	var lista_times = <?php echo json_encode($listaTimes) ?>;
	var dono_competicao = '<?php echo $dono_competicao ?>';
	console.log("Total de times disponíveis carregados:", lista_times.length);
	console.log("O time 817 está na lista?", lista_times.find(t => t[0] == 817));
	
	 var logged ='<?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
		echo "true";
	 } else {
		echo "false";
	 };?>';
	 
  var admin ='<?php if((isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1) || (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $dono_competicao)){
	echo "true";
 } else {
	echo "false";
 };?>';
 
   var user_id ='<?php if(isset($_SESSION['user_id']) ){
	echo $_SESSION['user_id'];
 } else {
	echo $_SESSION['user_id'];
 };?>';
	
	var localData = [];
	load_data();

	
	function load_data(){

		$('#loading').show(); 
		$.ajax({
			url:"refresh_team_list.php",
			method:"POST",
			cache:false,
			data:{codigo_competicao:codigo_competicao},
			success:function(data){
				console.log("Dados carregados:", data);
				$('#loading').hide();  // hide loading indicator
				localData = JSON.parse(data);
				update_table();	
			}
		});
	}

	
	function update_table(){

    //criar tabela dinamicamente
    var tbl = '';
	var status_time = '';
	var cor_status = '';
	var font_status = '';
	
	for (var i = 1; i <= numero_times; i++) {
		var status_time = '';
		var cor_status = '';
		var font_status = '';
		
		let matchData = localData.find(element => parseInt(element.codigo_time) == i);
		if(matchData){
			var checkTeam = (parseInt(matchData.has_team) === 1);
			console.log("Vaga "+i+" | has_team:", matchData.has_team, " | Resultado:", checkTeam);
			if (checkTeam){
				status_time = 'Time pronto';
				cor_status = 'rgba(52, 211, 153, 0.1)';
				font_status = '#059669';
			} else {
				status_time = 'Aguardando time';
				cor_status = 'rgba(251, 191, 36, 0.1)';
				font_status = '#d97706';
			}
		} else {
			status_time = 'Aguardando país';
			cor_status = 'rgba(56, 189, 248, 0.1)';
			font_status = '#0284c7';
		}

		// geração da tabela
		var selectedOwner = 0;
		var selectedCountry = 0;
		let adminStatus = (admin === "true" || dono_competicao === parseInt(user_id)) ? "" : "disabled";
		tbl += "<div class='par_pais_equipe' id='par_pais_equipe"+i+"'>";
			tbl += "<span class='slot-badge'>Vaga #" + i + "</span>";
			tbl += "<select id='selecaoPais"+i+"' name='pais"+i+"' class='smallform selecaoPais' placeholder='País...' "+adminStatus+">";
				tbl += "<option data-flag='flag.png' value=0 >Selecione o país...</option>";
				$.each(lista_paises, function(index, val){
					if(matchData && val[0] == matchData.pais_time){
						var selected = " selected ";
						selectedOwner = val[3];
						selectedCountry = val[0];
					} else {
						var selected = "";
					}
					tbl += "<option data-flag='"+val[4]+"' data-dono='"+val[3]+"' data-federacao='"+val[2]+"' value='"+val[0]+"' "+selected+">"+val[1]+"</option>";
				});
			tbl += "</select>";

			let disabledStatus = (admin === "true" || parseInt(dono_competicao) === parseInt(user_id) || selectedOwner == user_id) ? "" : "disabled";
			tbl += "<select id='selecaoTime"+i+"' name='equipe"+i+"' class='smallform selecaoTime' placeholder='Equipe...' "+disabledStatus+">";
				tbl += "<option data-flag='/images/escudos/0.png' value=0>Selecione o time...</option>";
				$.each(lista_times, function(index, val){
				
					if(val[2] == selectedCountry){
						if(matchData && val[0] == matchData.id_time_portal){
							var selectedTeam = " selected ";
						} else {
							var selectedTeam = "";
						}
						tbl += "<option data-flag='/images/escudos/"+val[5]+"' data-genero='"+val[3]+"' data-pais='"+val[2]+"' value='"+val[0]+"' "+selectedTeam+">"+val[1]+"</option>";
						
					}

				});
				if(matchData && matchData.has_team == 1 && matchData.id_time_portal == null){
					tbl += "<option data-flag='0.png'  value='' selected>Teste</option>";
				}
			tbl += "</select>";
  
			tbl += "<span class='fileUploader ui-button ui-widget'>Importar .ymt temporário</span><input id='import_team"+i+"' type='file' value='Importar .ymt temporário' hidden class='hiddenInput import_team' />";
			tbl += "<input id='update_team"+i+"' type='submit' value='Salvar' class='ui-button ui-widget update_team' disabled/>";
			tbl += "<span class='status_competicao' style='background-color:"+cor_status+" !important; color:"+font_status+" !important; border: 1px solid "+font_status+"20 !important;'>" +status_time + "</span>";
			
		tbl += "</div>";
	}
	
	    //mostrar dados da tabela
    $(document).find('#quadro_equipes_torneio').html(tbl);
    init_page_elements();
}

function init_page_elements(){
		$(".selecaoPais").select2({
			templateResult: function (country) {
								if (!country.id) return country.text;
								var baseUrl = "/images/bandeiras/";
								return $('<span><img src="' + baseUrl + '/' + country.element.attributes[0].value + '" class="bandeira" /><span  class="opcaoPaisNome"> ' + country.text + '</span></span>');
							},
			templateSelection: function (country) {
								if (!country.id) return country.text;
								var baseUrl = "/images/bandeiras/";
								return $('<span><img src="' + baseUrl + '/' + country.element.attributes[0].value + '" id="bs01" class="bandeiraSelect" /><span> ' + country.text + '</span></span>');
							},
			width:'resolve'
		});
		
		$(".selecaoTime").select2({
			templateResult: function (country) {
								if (!country.id) return country.text;
								return $('<span><img src="' + country.element.attributes[0].value + '" class="bandeira" /><span  class="opcaoPaisNome"> ' + country.text + '</span></span>');
							},
			templateSelection: function (country) {
								if (!country.id) return country.text;
								return $('<span><img src="' + country.element.attributes[0].value + '" id="bs01" class="bandeiraSelect" /><span> ' + country.text + '</span></span>');
							},
			width:'resolve'
		});
        $(".selecaoPais").trigger("change", true);
}

// Event Delegation
$(document).on("change", ".selecaoTime", function(){
    var tbl_row =  $(this).closest('.par_pais_equipe');
    tbl_row.find('.update_team').prop("disabled", false);
});

$(document).on("change", ".selecaoPais", function(e, unlock){
    var tbl_row =  $(this).closest('.par_pais_equipe');
    if(!unlock) tbl_row.find('.update_team').prop("disabled", false);
    
    var donoPais = $(this).find(':selected').data('dono');
    if(admin === "true" || parseInt(dono_competicao) === parseInt(user_id) || parseInt(user_id) === parseInt(donoPais)){
        tbl_row.find('.selecaoTime').prop("disabled", false);
    } else {
        tbl_row.find('.selecaoTime').prop("disabled", true);
    }
    
    
    var codigo_time = parseInt($(this).attr('id').replace( /^\D+/g, ''));
    var pais_selecionado = parseInt($(this).val());
    var opt = "<option data-flag='/images/escudos/0.png' value=0>Selecione o time...</option>";
    $.each(lista_times, function(index, val){
        if(val[2] == pais_selecionado){
            var match = localData.find(element => element.codigo_time == codigo_time);
            var selectedTeam = (match && val[0] == match.id_time_portal) ? " selected " : "";
            opt += "<option data-flag='/images/escudos/"+val[5]+"' data-genero='"+val[3]+"' data-pais='"+val[2]+"' value='"+val[0]+"' "+selectedTeam+">"+val[1]+"</option>";
        }
    });
    
    var currentMatch = localData.find(element => element.codigo_time == codigo_time);
    if(currentMatch && currentMatch.has_team == 1 && currentMatch.id_time_portal == null){
        $.ajax({
            url:"lerTimeExterno.php",
            method:"POST",
            cache:false,
            data:{codigo_time:codigo_time, codigo_competicao:codigo_competicao},
            success:function(data){
                var new_data = JSON.parse(data);
                opt += "<option data-flag='"+new_data.Escudo+"'  value='99999999' selected>"+new_data.Nome+" (externo)</option>";
                $("#selecaoTime" + codigo_time ).html(opt).trigger('change.select2');
            }
        });
    } else {
        $("#selecaoTime" + codigo_time).html(opt).trigger('change.select2');
    }
});
	
$(document).on("click", ".update_team", function(e){
		e.preventDefault();
		$('#loading').show(); 
		var codigo_time = parseInt($(this).attr('id').replace( /^\D+/g, ''));
		var tbl_row =  $(this).closest('.par_pais_equipe');
		var pais_time = tbl_row.find('.selecaoPais').val();
		if($("#selecaoTime" + codigo_time).val() === '0' && $("#selecaoPais" + codigo_time).val() != 0){
			formData = new FormData();
			formData.append('tipo_alteracao',0);
			formData.append('codigo_time',codigo_time);
			formData.append('codigo_competicao',codigo_competicao);
			formData.append('pais_time',pais_time);
			
			console.log("Enviando TIPO 0 (Apenas País)");
			$.ajax({
                type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
                url         : 'alterar_times_competicao.php', // the url where we want to POST
                data        : formData, // our data object
                dataType    : 'json', // what type of data do we expect back from the server
                processData: false,
                contentType: false,
                cache: false
            }).done(function(new_response) {
				console.log("Resposta TIPO 0:", new_response);
                if(new_response.success){
					load_data();
                } else {
					$('#errorbox').html("<div class='alert alert-danger'>Não foi possível realizar a alteração pelos seguintes motivos:</br>"+new_response.errors+"</div>");
                }
			}).fail(function(new_response) {
                  $('#errorbox').html("<div class='alert alert-danger'>Houve um erro não esperado, por favor contacte o admin.<div>");
                }).always(function(){
							tbl_row.find('.update_team').prop("disabled", "disabled");
						});
		} else if ($("#selecaoTime" + codigo_time).val() != '0' && $("#selecaoPais" + codigo_time).val() != '0') {
			var time_portal = tbl_row.find('.selecaoTime').val();
			formData = new FormData();
			formData.append('tipo_alteracao',1);
			formData.append('codigo_time',codigo_time);
			formData.append('codigo_competicao',codigo_competicao);
			formData.append('pais_time',pais_time);
			formData.append('time_portal',time_portal);
			formData.append('num_equipes',0);
			formData.append('codigo_genero', '<?php echo $genero_competicao ?>');
			formData.append('codigo_sede',pais_time);
			formData.append('codigo_federacao', '<?php echo $federacao_id ?>');
			formData.append('array_times',time_portal);
			
			
			// for (var key of formData.entries()) {
				// console.log(key[0] + ', ' + key[1]);
			// }
			
			console.log("Enviando TIPO 1 (País + Time)");
						$.ajax({
                type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
                url         : '/export/verificar_exportacao.php', // the url where we want to POST
                data        : formData, // our data object
                dataType    : 'json', // what type of data do we expect back from the server
                processData: false,
                contentType: false,
                cache: false
            }).done(function(new_response) {
				console.log("Resposta verificar_exportacao:", new_response);
                if(new_response.success){
					
					$.ajax({
						type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
						url         : 'alterar_times_competicao.php', // the url where we want to POST
						data        : formData, // our data object
						dataType    : 'json', // what type of data do we expect back from the server
						processData: false,
						contentType: false,
						cache: false
					}).done(function(new_response) {
						console.log("Resposta Final alterar_times_competicao:", new_response);
						if(new_response.success){
							
							
							$('#errorbox').html("");
							load_data();
							
						} else {
							$('#loading').hide();
							$('#errorbox').html("<div class='alert alert-danger'>Não foi possível inserir o time pelos seguintes motivos:</br>"+new_response.errors+"</div>");
							
						}
					}).fail(function(new_response) {
						  $('#errorbox').html("<div class='alert alert-danger'>Houve um erro não esperado, por favor contacte o admin.<div>");
						}).always(function(){
							tbl_row.find('.update_team').prop("disabled", "disabled");
						});
					
                } else {
					$('#loading').hide();
					$('#errorbox').html("<div class='alert alert-danger'>Não foi possível inserir o time pelos seguintes motivos:</br>"+new_response.errors+"</div>");
					
                }
			}).fail(function(new_response) {
                  $('#errorbox').html("<div class='alert alert-danger'>Houve um erro não esperado, por favor contacte o admin.<div>");
                });
			
		}
	});
	
	$(".fileUploader").click(function() {
		alert('Ao importar arquivo .ymt e não usar time do portal, as estatísticas do time não serão computadas no histórico geral, apenas na competição.');
		var tbl_row =  $(this).closest('.par_pais_equipe');
		tbl_row.find('.import_team').click();
	});
	
			$(".import_team").on("change", function (e) {

				$('#loading').show(); 
				var codigo_time = parseInt($(this).attr('id').replace( /^\D+/g, ''));
				var tbl_row =  $(this).closest('.par_pais_equipe');
				var pais_time = tbl_row.find('.selecaoPais').val();
				
				var inputFile = $(this)[0];
				var file;

				if (inputFile.files.length > 0) {
				   file = inputFile.files[0];
				} else {
				   file = null;
				}
				

				formData = new FormData();
				formData.append('files',file);
				formData.append('codigo_time',codigo_time);
				formData.append('id_competicao',codigo_competicao);
				formData.append('pais_time',pais_time);
			
			// for (var key of formData.entries()) {
				// console.log(key[0] + ', ' + key[1]);
			// }
			
						$.ajax({
                type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
                url         : 'importar_time_ymt.php', // the url where we want to POST
                data        : formData, // our data object
                dataType    : 'json', // what type of data do we expect back from the server
                processData: false,
                contentType: false,
                cache: false
            }).done(function(new_response) {
                if(new_response.success){
				
							load_data();
							update_table();
							
						} else {
							$('#loading').hide();
							$('#errorbox').html("<div class='alert alert-danger'>Não foi possível inserir o time pelos seguintes motivos:</br>"+new_response.errors+"</div>");
							
						}
					}).fail(function(new_response) {
						  $('#errorbox').html("<div class='alert alert-danger'>Houve um erro não esperado, por favor contacte o admin.<div>");
						});
			
		
	});

});



</script>

<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

// footer
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
