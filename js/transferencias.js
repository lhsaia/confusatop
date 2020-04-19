
$('.editar').on("click",function(event){
    var tbl_row = $(this).closest('tr');
    var idPais = tbl_row.attr('id');

    tbl_row.find('span[class*=nomSM]').each(function(){
         $(this).hide();
     });
    tbl_row.find('select[class*=selSM]').each(function(){
        if($(this).parent().hasClass('mercadoAberto')){
            $(this).val(1);
        } else {
            $(this).val(0);
        }
         $(this).show();
     });

     tbl_row.find('.salvar').show();
     tbl_row.find('.cancelar').show();
     $(this).hide();

});

$('.cancelar').on("click",function(event){
    var tbl_row = $(this).closest('tr');
    var idPais = tbl_row.attr('id');

    tbl_row.find('span[class*=nomSM]').each(function(){
         $(this).show();
     });
    tbl_row.find('select[class*=selSM]').each(function(){
         $(this).hide();
     });

     tbl_row.find('.salvar').hide();
     tbl_row.find('.editar').show();
     $(this).hide();

});

$('.salvar').on("click",function(event){
    var tbl_row = $(this).closest('tr');
    var idPais = tbl_row.attr('id');
    var codeArray = [];

    tbl_row.find('span[class*=nomSM]').each(function(){
         $(this).show();

     });
    tbl_row.find('select[class*=selSM]').each(function(){
        codeArray.push($(this).val());
        $(this).hide();
     });

     tbl_row.find('.cancelar').hide();
     tbl_row.find('.editar').show();
     $(this).hide();

     var codeString ="";
    for (var member in codeArray) {
        codeString += codeArray[member];
    }

    var formData = {
        'idPais' : idPais,
        'codeString' : codeString
    };

    $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : 'alterar_janela.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
            encode          : true
        })

            .done(function(data) {

                // log data to the console so we can see
                console.log(data);

                if (data.success) {

                    location.reload();

                } else {

                }

            // here we will handle errors and validation messages
            });

});

    $( function() {
    $( "input[id*='checkbox']" ).checkboxradio({
      icon: false
    });
  } );

  var tipoPagina = $("#tipoBusca").val();
  if (tipoPagina.localeCompare('busca') == 0){
      var nivelMin = 0;
      var nivelMax = 100;
      var valMin = 50;
      var valMax = 90;
  } else if(tipoPagina.localeCompare('buscaTecnico') == 0){
    var nivelMin = 0;
    var nivelMax = 10;
    var valMin = 5;
    var valMax = 9;
  }

  $( function() {

    $( "#range_niveis" ).slider({
      range: true,
      min: nivelMin,
      max: nivelMax,
      values: [ valMin, valMax ],
      slide: function( event, ui ) {
        $( "#mostrador_niveis" ).val(  ui.values[ 0 ] + " - " + ui.values[ 1 ] );
      }
    });
    $( "#mostrador_niveis" ).val( $( "#range_niveis" ).slider( "values", 0 ) +
      " - " + $( "#range_niveis" ).slider( "values", 1 ) );

    $( "#range_idade" ).slider({
        range: true,
        min: 14,
        max: 45,
        values: [ 18, 30 ],
        slide: function( event, ui ) {
            $( "#mostrador_idade" ).val(  ui.values[ 0 ] + " - " + ui.values[ 1 ] );
        }
    });
    $( "#mostrador_idade" ).val( $( "#range_idade" ).slider( "values", 0 ) +
    " - " + $( "#range_idade" ).slider( "values", 1 ) );

    $( "#range_valor" ).slider({
        range: true,
        min: 0,
        max: 50000,
        step: 250,
        values: [ 500, 7000 ],
        slide: function( event, ui ) {
            $( "#mostrador_valor" ).val(  ui.values[ 0 ] + "k - " + ui.values[ 1 ] + "k" );
        }
    });
    $( "#mostrador_valor" ).val( $( "#range_valor" ).slider( "values", 0 ) +
    "k - " + $( "#range_valor" ).slider( "values", 1 ) + "k" );
    } );

    $('input[type=reset]').on("click",function(e){

  var $slider = $("#range_idade");
  $slider.slider("values", [18, 30]);
  var $slider = $("#range_niveis");
  $slider.slider("values",[50, 90]);
  $( "#mostrador_niveis" ).val("50 - 90");
  $( "#mostrador_idade" ).val("18 - 30");
  e.preventDefault();
  $("input[type=checkbox]").each(function(){
      $(this).prop("checked", false).change();
  });
  $("#input_nome").val("");
  $("select").each(function(){
      $(this).val("0");
      $(this).selectmenu("refresh");
  });

$('span', '#toggleButtonLabel').text('Qualquer dentre as posições marcadas');
$('span', '#toggleButtonLabel2').text('Apenas CONFUSA');
$('span', '#toggleButtonLabel3').text('Masculino');

    });

    $('#input_mentalidade').selectmenu();
    $('#input_nacionalidade').selectmenu();
    if($("#input_estilo").length != 0) {
        $('#input_estilo').selectmenu();
      }



    $('#toggleButtonLabel').click(function () {
        var checked = $('input', this).is(':checked');
        $('span', this).text(checked ? 'Todas as posições marcadas' : 'Qualquer dentre as posições marcadas');
    });

    $('#toggleButtonLabel2').click(function () {
        var checked = $('input', this).is(':checked');
        $('span', this).text(checked ? 'Incluir NC-Board e reais' : 'Apenas CONFUSA');
    });

    $('#toggleButtonLabel3').click(function () {
        var checked = $('input', this).is(':checked');
        $('span', this).text(checked ? 'Feminino' : 'Masculino');
    });

var localData = [];
var asc = true;
var activeSort = '';

$(document).ready(function($){
    
    $('#fimContrato').hide();
    $('label[for="fimContrato"]').hide();
    $('#fimContrato').val('');

//adicionado para ocultar data de encerramento em caso de venda direta
$('#tipoTransacao').change(function(){
    if($(this).val() == 0){
        $('#fimContrato').hide();
        $('label[for="fimContrato"]').hide();
        $('#fimContrato').val('');
    } else {
        $('#fimContrato').show();
        $('label[for="fimContrato"]').show();
    }
});



$('#form_busca_jogador').submit(function(e){
    e.preventDefault();


        var searchForm = $('#form_busca_jogador').serialize();
        //$('#loading').show();  // show loading indicator

        console.log(searchForm);
        $('#loading').show();  // show loading indicator

        $.ajax({
            url:"pesquisa.php",
            method:"POST",
            cache:false,
            data: searchForm,
            success:function(data){
                $('#loading').hide();  // hide loading indicator
                updateTable(JSON.parse(data),1,0,0);
                localData = JSON.parse(data);
                console.log(localData);
            }
        });

});







function updateTable(ajax_data, current_page, highlighted, direction){

    var results_per_page = 17;
    var total_results = ajax_data.length;
    var total_pages = Math.ceil(total_results/results_per_page);

    var treated_page;
    if(current_page == 'final'){
        treated_page = total_pages;
    } else if(current_page == 'inicio'){
        treated_page = 1;
    } else {
        treated_page = current_page;
    }

    var from_result_num = (results_per_page * treated_page) - results_per_page;

    var pgn = pagination(treated_page,total_pages);

    //criar tabela dinamicamente
    var tbl = '';
    tbl += pgn;
    tbl += "<hr>";
    tbl += "<table id='tabelajogos' class='table'>";
        tbl += "<thead id='headings'>";
            tbl += "<tr>";
            if(tipoPagina.localeCompare('busca') == 0){
                tbl += "<th asc='' id='nomeJogador' class='headings'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspJogador</th>";
            }  if(tipoPagina.localeCompare('buscaTecnico') == 0){
                tbl += "<th asc='' id='nomeJogador' class='headings'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspTécnico</th>";
            }
                tbl +=  "<th asc='' id='posicoes' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspPosições</th>";
                tbl +=  "<th asc='' id='idadeJogador' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspIdade</th>";
                tbl +=  "<th asc='' id='bandeira' class='headings'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspNacionalidade</th>";
                tbl +=  "<th asc='' id='nivel' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspNivel</th>";
                tbl +=  "<th asc='' id='mentalidade' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspMentalidade</th>";
                if(tipoPagina.localeCompare('busca') == 0){
                tbl +=  "<th asc='' id='cobrancaFalta' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspCobrança de Falta</th>";
                } else if(tipoPagina.localeCompare('buscaTecnico') == 0){
                    tbl +=  "<th asc='' id='cobrancaFalta' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspEstilo</th>";
                }
                tbl +=  "<th asc='' id='nomeClube' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspClube</th>";
                if(tipoPagina.localeCompare('busca') == 0){
                tbl +=  "<th asc='' id='disponibilidade' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspDisp.</th>";
                tbl +=  "<th asc='' id='valor' class='headings' ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspValor</th>";
                }
                tbl += "<th asc=''  ><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspOpções</td>";
            tbl +=  "</tr>";
        tbl +=  "</thead>";
        tbl +=  "<tbody>";

        // criar linhas
        $.each(ajax_data, function(index, val){

            if(index>=(from_result_num-1) && index<=(from_result_num+results_per_page-2)){

                // if(val['escudoClube'] != null){
                //     var escudo = val['escudoClube'].split(".");
                //     var escudoExt = escudo[1];
                //     var escudoImg = escudo[0];
                // }

                var valor = "F$ " + Math.round(parseFloat(val['valor']/10000))/100 + " M";


            tbl += "<tr id='"+val['idJogador']+"'>";
                if(tipoPagina.localeCompare('busca') == 0){
                    tbl += "<td class='nopadding nomeJogador'><a href='/ligas/playerstatus.php?player="+val['idJogador']+"'>"+val['nomeJogador']+"</a><br><span class='posicao'>"+val['posicaoBaseJogador']+"</span></td>";
                    tbl += "<td class='nopadding'>"+val['posicoes'].slice(0,-1)+"</td>";
                } else  if(tipoPagina.localeCompare('buscaTecnico') == 0){
                    tbl += "<td class='nopadding nomeJogador'>"+val['nomeJogador']+"<br><span class='posicao'>Técnico</span></td>";
                    tbl += "<td class='nopadding'>T</td>";
                }
                tbl += "<td class='nopadding'>"+val['idadeJogador']+"</td>";
                if(val['nacionalidade'] != 0){
                tbl += "<td class='nopadding'><a href='/ligas/paisstatus.php?country="+val['nacionalidade']+"'><img src='/images/bandeiras/"+val['bandeira']+"' class='bandeira nomePais' id='ban"+val['nacionalidade']+"'/></a>";
                } else {
                tbl += "<td>";
                }
                tbl += "</td>";
                tbl +=  "<td class='nopadding'>"+val['nivel']+"</td>";
                tbl +=  "<td class='nopadding'>"+val['mentalidade']+"</td>";
                if(tipoPagina.localeCompare('busca') == 0){
                tbl +=  "<td class='nopadding'>"+val['cobrancaFalta']+"</td>";
                } else if(tipoPagina.localeCompare('buscaTecnico') == 0){
                    tbl +=  "<td class='nopadding'>"+val['estilo']+"</td>";
                }
                tbl += "<td class='nopadding'>";
            if(val['idClube'] != 0){
                tbl += "<a href='/ligas/teamstatus.php?team="+val['idClube']+"'>";
            } else {
                tbl += "<span>";
            }
            tbl += "<img src='/images/escudos/"+val['escudoClube']+"' class='minithumb'/>"+val['nomeClube'];
            if(val['idClube'] != 0){
            tbl += "</a>";
            tbl += "<br/><a class='posicao' href='/ligas/leaguestatus.php?league="+val['idLiga']+"'><img src='/images/bandeiras/"+val['bandeiraClube']+"' class='minithumb' id='ban"+val['paisClube']+"'/>"+val['ligaClube']+"</a>";
            } else {
            tbl += "</span>";
            }
            tbl += "</td>";
            if(tipoPagina.localeCompare('busca') == 0){
                tbl +=  "<td class='nopadding'>"+val['disponibilidade']+"</td>";
                tbl +=  "<td class='nopadding'>"+valor+"</td>";

            }
                tbl +=  "<td class='nopadding'>";


                if(tipoPagina.localeCompare('busca') == 0){
                    tbl += "<a id='pro"+val['idJogador']+"' title='Fazer Proposta' class='clickable proposta'><i class='fas fa-money-bill inlineButton'></i></a>";
                } else if(tipoPagina.localeCompare('buscaTecnico') == 0){
                    tbl += "<a id='pro"+val['idJogador']+"' title='Fazer Proposta' class='clickable propostaTecnico'><i class='fas fa-money-bill inlineButton'></i></a>";
                }


                    if(val['donoJogador'] == 1) {
                        if(tipoPagina.localeCompare('busca') == 0){
                            tbl += "<a id='pro"+val['idJogador']+"' title='Convocar' class='clickable convocar'><i class='fas fa-globe inlineButton'></i></a>";
                        } else if(tipoPagina.localeCompare('buscaTecnico') == 0){
                            tbl += "<a id='pro"+val['idJogador']+"' title='Convocar' class='clickable convocarTecnico'><i class='fas fa-globe inlineButton'></i></a>";
                        }

                    }

                tbl += "</td>";

            tbl +=  "</tr>";
            }
        });

        tbl += '</tbody>';
    tbl += '</table>';

    //mostrar dados da tabela
    $(document).find('.tbl_user_data').html(tbl);
    addFilters();

    $(document).find('#'+highlighted).addClass('highlighted');

    if(direction == 1){
        asc = activeDirection;
    }
    if(asc){
        $(document).find('#'+highlighted).find('.descending').addClass('hidden');
        $(document).find('#'+highlighted).find('.ascending').removeClass('hidden');
    } else {
        $(document).find('#'+highlighted).find('.ascending').addClass('hidden');
        $(document).find('#'+highlighted).find('.descending').removeClass('hidden');
    }

    activeSort = highlighted;
    activeDirection = asc;
}

$(document).on('click', '.pagination_link', function(){
    var page = $(this).attr('id');
    updateTable(localData, page,activeSort, 1);
});


function pagination(current_page, total_pages){
var pgn = '';
pgn += "<ul class='pagination'>";

// button for first page
if(current_page>1){
    pgn +=  "<li><button class='pagination_link' id='inicio' title='Ir para o início'>";
    pgn +=  "Inicio";
    pgn +=  "</button></li>";
}

// range of links to show
const range = 2;

// display links to 'range of pages' around 'current page'
var initial_num = current_page - range;
var condition_limit_num = (+current_page + +range)  + +1;

// teste com While
var x;
if(initial_num > 0){
    x = initial_num;
} else {
    x = 1;
}

while(x <= total_pages && x < condition_limit_num){
    if (x == current_page) {
            pgn += "<li><button class='pagination_link' id='"+x+"' disabled>"+x+"<span class=\"sr-only\">(current)</span></button></li>";
        }
        else {
            pgn += "<li><button class='pagination_link' id='"+x+"'>"+x+"</button></li>";
        }
    x = x+1;
}

// button for last page
if(current_page<total_pages){
    pgn += "<li><button class='pagination_link' id='final' title='Última página é "+total_pages+".'>";
    pgn += "Final";
    pgn += "</button></li>";
}

pgn += "</ul>";

return pgn;
}

function addFilters(){
    $(document).find('.headings').click(function(){
        treatResults(this);
     });
}

$(document).ready(function(){
    addFilters();
});



function treatResults(item){
    var id = $(item).attr('id');

    sortResults(id, asc);

    if(asc){
        asc = false;
    } else {
        asc = true;
    }

}

function sortResults(prop, asc) {

console.log(localData);

if(prop == 'nivelJogador' || prop == "idadeJogador" || prop == "valor"){

    localData = localData.sort(
        function(a,b){
            if (asc) return a[prop] - b[prop];
            if (!asc) return b[prop] - a[prop];
            else return 0;
        }
    );
} else {
    localData = localData.sort(
        function(a, b) {
            if (((a[prop] < b[prop]) && (!asc))||((a[prop] > b[prop]) && (asc))) return 1;
            else if (((a[prop] > b[prop]) && (!asc))||((a[prop] < b[prop]) && (asc))) return -1;
            else return 0;
        }
    );
}


    updateTable(localData, 1,prop,0);

    }

});

function jogadorEncontrado(jogador) {
    return jogador.idJogador === jogId;
}

$(document).on("click", '.convocar', function(event){
    var propId = $(this).prop("id");
    jogId = propId.replace(/\D/g,'');

    var arrayJogador = localData.find(jogadorEncontrado);

    var nome = arrayJogador.nomeJogador;
    var sexoJogador = arrayJogador.sexoJogador;
    var idadeJogador = arrayJogador.idadeJogador;
    var nacionalidadeJogador = arrayJogador.nacionalidade;

    var counter = 0;
    $("#selecaoDestino option").each(function(){

        if($(this).attr("data-sexo") == sexoJogador && $(this).attr("data-pais") == nacionalidadeJogador){

            if($(this).attr("data-status") == 1){
                $(this).show();
                counter = counter + 1;
                $(this).prop('selected', true);
            } else if($(this).attr("data-status") == 2 && idadeJogador <= 21 ){
                $(this).show();
                counter = counter + 1;
            } else if($(this).attr("data-status") == 3 && idadeJogador <= 20 ){
                $(this).show();
                counter = counter + 1;
            } else if($(this).attr("data-status") == 4 && idadeJogador <= 18 ){
                $(this).show();
                counter = counter + 1;
            } else {
                $(this).hide();
            }

        } else {
            $(this).hide();
            $(this).prop('selected', false);
        }

    });

    if(counter == 0){
        $("#errorbox").html("<div class='alert alert-danger'>Não há seleções disponíveis para esse(a) jogador(a)!</div>");
    } else {
        $('#nomeJogadorConvoca').val(nome);
        $("#idJogadorConvoca").val(jogId);
        $("#idadeJogadorConvoca").val(idadeJogador);
        $("#nacionalidadeJogadorConvoca").val(nacionalidadeJogador);

        $('#modalConvocacao').show();
    }

});

$(document).on("click", '.convocarTecnico', function(event){
    var propId = $(this).prop("id");
    jogId = propId.replace(/\D/g,'');

    var arrayJogador = localData.find(jogadorEncontrado);

    var nome = arrayJogador.nomeJogador;
    var nacionalidadeJogador = arrayJogador.nacionalidade;

    var counter = 0;
    $("#selecaoDestino option").each(function(){

        if($(this).attr("data-pais") == nacionalidadeJogador){

            $(this).show();
            counter = counter + 1;

        } else {
            $(this).hide();
            $(this).prop('selected', false);
        }

    });

    if(counter == 0){
        $("#errorbox").html("<div class='alert alert-danger'>Não há seleções disponíveis para esse(a) técnico(a)!</div>");
    } else {
        $('#nomeJogadorConvoca').val(nome);
        $("#idJogadorConvoca").val(jogId);

        $("#nacionalidadeJogadorConvoca").val(nacionalidadeJogador);

        $('#modalConvocacao').show();
    }

});


$(document).on("click", '.proposta', function(event) {

    var propId = $(this).prop("id");
    jogId = propId.replace(/\D/g,'');


var arrayJogador = localData.find(jogadorEncontrado);

var nome = arrayJogador.nomeJogador;
var valorInicial = arrayJogador.valor;
var clube = arrayJogador.idClube;
var sorvete = $("#maquinaSorvete").html();
var sexoJogador = arrayJogador.sexoJogador;

if(clube != 0){
    $('#valorJogadorTransf').val(valorInicial);
} else {
    $('#valorJogadorTransf').val(0);
}

$("#clubeDestinoTransf option").each(function(){

    if($(this).attr("data-sexo") == sexoJogador){
        if($(this).val() == clube){
            $(this).attr("disabled", "disabled");
            $(this).hide();


        } else {
            $(this).show();
            $(this).removeAttr("disabled");
        }
    } else {
        $(this).attr("disabled", "disabled");
        $(this).hide();
    }

});


$('#nomeJogadorTransf').val(nome);
$("#idJogadorTransf").val(jogId);
$("#clubeOrigemTransf").val(clube);
$('#sorvete').val(sorvete);
$('#modalProposta').show();
});

$(document).on("click", '.propostaTecnico', function(event) {

    var propId = $(this).prop("id");
    jogId = propId.replace(/\D/g,'');


var arrayJogador = localData.find(jogadorEncontrado);

var nome = arrayJogador.nomeJogador;
var clube = arrayJogador.idClube;
var sorvete = $("#maquinaSorvete").html();
var sexoJogador = arrayJogador.sexoJogador;

$("#clubeDestinoTecnico option").each(function(){


        if($(this).val() == clube){
          $(this).attr("disabled", "disabled");
            $(this).hide();
        } else {
            $(this).show();
            $(this).removeAttr("disabled");
        }


});

$('#nomeTecnicoTransf').val(nome);
$("#idTecnicoTransf").val(jogId);
$("#clubeOrigemTecnico").val(clube);
$('#sorveteTec').val(sorvete);
$('#modalPropostaTecnico').show();
});

$("#formPropostaTecnico").submit(function(event){

    var clubeOrigem = $('input[name=clubeOrigemTecnico]').val();
    var clubeDestino = $('select[name=clubeDestinoTecnico]').val();
    var formData = {
        'idTecnico' : $('input[name=idTecnicoTransf]').val(),
        'clubeOrigem' : clubeOrigem,
        'clubeDestino' : clubeDestino,
        'sorveteTec' : $('input[name=sorveteTec]').val()
    };

    if(clubeOrigem == clubeDestino){
        $('#errorbox').html("<div class='alert alert-danger'>O técnico não pode ir para seu time atual!</div>");
    }

    console.log(formData);

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/ligas/fazer_proposta_tecnico.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
console.log(data);
window.scrollTo(0, 0);

if (! data.success) {
    $('#modalPropostaTecnico').hide();
     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {

$('#modalPropostaTecnico').hide();
     $('#errorbox').append("<div class='alert alert-success'>O pedido foi realizado com sucesso!</div>");

}

// here we will handle errors and validation messages
}).fail(function(jqXHR, textStatus, errorThrown ){
    console.log("Erro");
    console.log(jqXHR);
    console.log(textStatus);
    console.log(errorThrown);
});


    event.preventDefault();
});


$("#formProposta").submit(function(event){

    event.preventDefault();

    var clubeOrigem = $('input[name=clubeOrigemTransf]').val();
    var clubeDestino = $('select[name=clubeDestinoTransf]').val();
    var formData = {
        'idJogador' : $('input[name=idJogadorTransf]').val(),
        'clubeOrigem' : clubeOrigem,
        'clubeDestino' : clubeDestino,
        'valor' : $('input[name=valorJogadorTransf]').val(),
        'sorvete' : $('input[name=sorvete]').val(),
        'tipoTransacao' : $('select[name=tipoTransacao').val(),
        'fimContrato' : $('input[name=fimContrato').val()
    };

    console.log(formData);

    if(clubeOrigem == clubeDestino){
        $('#errorbox').html("<div class='alert alert-danger'>O jogador não pode ir para seu time atual!</div>");

    } else {
        $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/jogadores/fazer_proposta.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
console.log(data);
window.scrollTo(0, 0);

if (! data.success) {
    $('#modalProposta').hide();
     $('#errorbox').html('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


} else {

$('#modalProposta').hide();
     $('#errorbox').html("<div class='alert alert-success'>O pedido foi realizado com sucesso!</div>");

}

// here we will handle errors and validation messages
});
    }





});


$("#formConvocacao").submit(function(event){

    event.preventDefault();

    var selecaoDestino = $('select[name=selecaoDestino]').val();
    var idJogador = $('input[name=idJogadorConvoca]').val();
    var tipoContrato = $('option:selected', $('select[name=selecaoDestino]')).attr('data-status');
    var formData = {
        'selecaoDestino' : selecaoDestino,
        'idJogador' : idJogador,
        'tipoContrato' : tipoContrato
    };

    console.log(formData);

    if(tipoPagina.localeCompare('busca') == 0){
        var url = '/jogadores/convocar.php';
    } else if(tipoPagina.localeCompare('buscaTecnico') == 0){
        var url = '/ligas/convocar_tecnico.php';
    }

        $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : url, // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
console.log(data);
window.scrollTo(0, 0);

if (! data.success) {
    $('#modalConvocacao').hide();
     $('#errorbox').html('<div class="alert alert-danger">Não foi possível fazer a convocação, '+data.error+'</div>');


} else {

$('#modalConvocacao').hide();
     $('#errorbox').html("<div class='alert alert-success'>Convocação realizada com sucesso!</div>");

}

// here we will handle errors and validation messages
});






});
