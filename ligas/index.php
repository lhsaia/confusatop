
<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $logged_user = $_SESSION['user_id'];
} else {
	$logged_user = "null";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Federações";
$css_filename = "indexRanking";
$aux_css = "ligas";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");


$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);

$stmtPais = $pais->readAll(0,10000,null,1);

$colorDict = array("FEASCO" => "#0e4a9d", "FEMIFUS" => "#fb7c04" , "COMPACTA" => "#43c7cc", "" => "#e8e8e8");

$arrayPaises = [];


	while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
		extract($row_pais);
		if($latitude != null && $longitude != null){
			
			$arrayPaises[] = array("nome" => addslashes($nome), "latitude" => $latitude, "longitude" => $longitude, "fill" => $colorDict[$federacao], "id" => $id, "owner" => $dono, "nc" => $ranqueavel);
			
		}
	}

?>

<img id='feasco' class='logo' src='/images/feasco.png' alt='Logo da FEASCO' hidden/>
<img id='femifus' class='logo' src='/images/femifus.png' alt='Logo da FEMIFUS' hidden/>
<img id='compacta' class='logo' src='/images/compacta.png' alt='Logo da COMPACTA' hidden/>

<link rel="stylesheet" href="/lib/jsvectormap-master/dist/css/jsvectormap.css" />
<script src="/lib/jsvectormap-master/dist/js/jsvectormap2.js"></script>

<div id="map"></div>

<script src="/lib/jsvectormap-master/dist/maps/world.js"></script>

<script>

$(document).ready(function(){
	
	$("#toolbar").html('<div id="toggleNC"><span>CONFUSA</span></div>');
	
	var baseCountryLink = "/ligas/paisstatus.php?country=";
	var countryArray =  <?php echo json_encode($arrayPaises); ?>;
	var meusPaises = false;
	var classePaises = 0;
	var loggedUser = <?php echo $logged_user ?>;
	
	if(loggedUser != null){
		$("#toolbar").append("<div id='togglePaises'><span>Todos Países</span></div>");
	}
	
	//console.log(countryArray);
	var markers = [
	
	<?php
foreach ($arrayPaises as $item){
	extract($item);
			echo "{";
			echo "name: '{$nome}',";
			echo "coords: [{$latitude},{$longitude}],";
			echo "style: {";
			echo "fill: '{$fill}'";
			echo "}";
			echo "},";
}
	
	?>
];


var map = new JsVectorMap({
    map: 'world',
	selector: '#map', 
    backgroundColor: 'tranparent',
    draggable: true,
    zoomButtons: true,
    zoomOnScroll: true,
    zoomOnScrollSpeed: 3,
    zoomMax: 15,
    zoomMin: 1,
    zoomAnimate: true,
    showTooltip: true,
    zoomStep: 1.5,
    bindTouchEvents: true,
    focusOn: {}, // focus on regions on page load
    /**
     * Markers options
     */
    markers: markers,
	 labels: {
      markers: {
        render: function (index) {
          return markers[index].name
        }
      }
    },
    markersSelectable: true,
    markersSelectableOne: true,
    markerStyle: {
      // Marker style
      initial: {
        r: 7,
        fill: 'black',
        fillOpacity: 1,
        stroke: 'none',
        strokeWidth: 1,
        strokeOpacity: .65
      },
      hover: {
        fill: '#3cc0ff',
        //stroke: '#5cc0ff',
        cursor: 'pointer',
        strokeWidth: 2
      },
      selected: {
        fill: 'green'
      },
      selectedHover: {}
    },
    // Marker Label style
    markerLabelStyle: {
      initial: {
        fontFamily: 'Verdana',
        fontSize: 12,
        fontWeight: 'bold',
        cursor: 'default',
        fill: 'black'
      },
      hover: {
		  fill: '#3cc0ff',
        cursor: 'pointer'
      },
	        selected: {
        fill: 'green'
      },
    },
    /**
     * Region styles
     */
    // labels: { // add a label for a specific region
      // regions: {
        // render(code) {
          // return ['EG', 'KZ', 'CN'].indexOf(code) > -1 ? 'Hello ' + code : ''
        // }
      // }
    // },
    regionsSelectable: false,
    regionsSelectableOne: false,
    regionStyle: {
      // Region style
      initial: {
        //fill: '#e3eaef',
		fill: '#fb7c04',
        fillOpacity: 0.5,
        stroke: 'none',
        strokeWidth: 0,
        strokeOpacity: 1
      },
      hover: {
        fillOpacity: 1,
        cursor: 'pointer'
      },
      selected: {
        fill: '#000'
      },
      selectedHover: {}
    },
    // Region label style
    regionLabelStyle: {
      initial: {
        fontFamily: 'Verdana',
        fontSize: '12',
        fontWeight: 'bold',
        cursor: 'default',
        fill: '#35373e'
      },
      hover: {
        cursor: 'pointer'
      }
    },

    series: {
      markers: [
        // You can add one or more objects to create series for markers.
          // {
            // attribute: "fill",
            // legend: {
              // title: "Something (marker)",
              // // vertical: true,
            // },
            // scale: {
              // "Criteria one": "#ffd400",
              // "Criteria two": "#4761ff"
            // },
            // values: {
              // 0: "Criteria one",
              // 1: "Criteria two",
              // 2: "Criteria two"
            // }
          // },
      ],
      regions: [
        // You can add one or more objects to create series for regions.
		{
            attribute: 'fill',
            attributes: {
              // EG: 'red'
            },
            legend: {
              // title: 'Federações',
              // vertical: true,
            },
            scale: {
              "FEASCO": "#0e4a9d",
              "FEMIFUS": "#fb7c04",
			  "COMPACTA": "#43c7cc"
            },
            values: {
              GB: "FEMIFUS",
              MX: "FEASCO",
			  US: "FEASCO",
			  CO: "FEASCO",
			  CN: "FEMIFUS",
			  RU: "FEMIFUS",
			  VE: "FEASCO",
			  BR: "FEASCO",
			  CL: "FEASCO",
			  CA: "FEASCO",
			  SU: "FEASCO",
			  UY: "FEASCO",
			  AR: "FEASCO",
			  BO: "FEASCO",
			  GL: "FEASCO",		
			  PR: "FEASCO",		
			  GY: "FEASCO",					  
			  GU: "FEASCO",		
			  GT: "FEASCO",		
			  HN: "FEASCO",	
			  FK: "FEASCO",
			  F2: "FEASCO",
			  BZ: "FEASCO",		
			  SV: "FEASCO",		
			  NI: "FEASCO",			
			  CR: "FEASCO",		
			  PA: "FEASCO",		
			  CU: "FEASCO",		
			  JM: "FEASCO",		
			  HT: "FEASCO",		
			  DO: "FEASCO",	
			  EC: "FEASCO",		
			  PE: "FEASCO",		
			  PY: "FEASCO",				  
			  AU: "COMPACTA",
			  TW: "COMPACTA",
			  PG: "COMPACTA",
			  NZ: "COMPACTA",
			  ID: "COMPACTA",
			  MY: "COMPACTA",
			  TL: "COMPACTA",
			  PH: "COMPACTA",
			  BN: "COMPACTA",	
			  JP: "COMPACTA",
			  BD: "FEMIFUS",
			  BE: "FEMIFUS",
			  BF: "FEMIFUS",			  
			  BG: "FEMIFUS",			  
			  BA: "FEMIFUS",			  
			  BI: "FEMIFUS",			  
			  BJ: "FEMIFUS",			  
			  BT: "FEMIFUS",			  
			  BW: "FEMIFUS",			  
			  BS: "FEASCO",
			  BY: "FEMIFUS",
			  RW: "FEMIFUS",
			  RS: "FEMIFUS",
			  LT: "FEMIFUS",
			  LU: "FEMIFUS",
			  LR: "FEMIFUS",
			  RO: "FEMIFUS",
			  GW: "FEMIFUS",
			  GR: "FEMIFUS",
			  GQ: "FEMIFUS",
			  GE: "FEMIFUS",
			  GA: "FEMIFUS",
			  GN: "FEMIFUS",
			  GM: "FEMIFUS",
			  KW: "FEMIFUS",
			  GH: "FEMIFUS",			  
			  GE: "FEMIFUS",
			  OM: "FEMIFUS",
			  _2: "FEMIFUS",
			  _1: "FEMIFUS",
			  _0: "FEMIFUS",
			  JO: "FEMIFUS",
			  HR: "FEMIFUS",
			  HU: "FEMIFUS",
			  PS: "FEMIFUS",
			  PT: "FEMIFUS",
			  IS: "FEASCO",
			  SR: "FEASCO",
			  TT: "FEASCO",
			  NC:"COMPACTA",
			  FJ:"COMPACTA",
			  SB: "COMPACTA",
			  VU: "COMPACTA"
            },
          },
      ]
    }
});

showCountries();

	paths = document.getElementsByTagName('path');
	
	for (var i = 0; i < paths.length; i++) {
	paths[i].addEventListener("mouseover", function(event){
		//console.log(this);
		
		if(this.getAttribute("fill") == "#0e4a9d"){
			document.getElementById('feasco').style.display = 'block';
			$("path[fill='#0e4a9d']").attr("fill-opacity", 1);
		} else if(this.getAttribute("fill") == "#fb7c04"){
			document.getElementById('femifus').style.display = 'block';
			$("path[fill='#fb7c04']").attr("fill-opacity", 1);
		} else if(this.getAttribute("fill") == "#43c7cc"){
			document.getElementById('compacta').style.display = 'block';
			$("path[fill='#43c7cc']").attr("fill-opacity", 1);
		}
		
        
    });
	
	paths[i].addEventListener("mouseout", function(event){
        	if(this.getAttribute("fill") == "#0e4a9d"){
			document.getElementById('feasco').style.display = 'none';
			$("path[fill='#0e4a9d']").attr("fill-opacity", 0.5);
		} else if(this.getAttribute("fill") == "#fb7c04"){
			document.getElementById('femifus').style.display = 'none';
			$("path[fill='#fb7c04']").attr("fill-opacity", 0.5);
		} else if(this.getAttribute("fill") == "#43c7cc"){
			document.getElementById('compacta').style.display = 'none';
			$("path[fill='#43c7cc']").attr("fill-opacity", 0.5);
		}
    });
	
		paths[i].addEventListener("click", function(event){
        	if(this.getAttribute("fill") == "#0e4a9d"){
				window.location = '/ligas/geral.php?fed=g1';
		} else if(this.getAttribute("fill") == "#fb7c04"){
			window.location = '/ligas/geral.php?fed=g2';
		} else if(this.getAttribute("fill") == "#43c7cc"){
			window.location = '/ligas/geral.php?fed=g3';
		}
    });
	}
	
	$(".jsvmap-marker").each(function(){
		$(this).on("click",function(){
			let idCode = $(this).attr("data-index");
			let countryInfo = countryArray[idCode];
			let link = baseCountryLink + countryInfo["id"];
			window.location = link;
			
		});
	});
	
	$("#togglePaises").on("click", function(){
			meusPaises = !meusPaises;
			if(!meusPaises){
				$("#togglePaises").text("Todos países");
			} else {
				$("#togglePaises").text("Meus países");
			}
			showCountries();
	});
	
	$("#toggleNC").on("click", function(){
		classePaises++;
		if(classePaises > 2){classePaises = 0};
		console.log(classePaises);
		switch (classePaises) {
			case 0:
				$("#toggleNC").text("CONFUSA");
				break;
			case 1:
				$("#toggleNC").text("NC Board");
				break;
			case 2:
				$("#toggleNC").text("Todos");
				break;
			default:
		}
		showCountries();
	});
	
	function showCountries() {
		if(meusPaises && (classePaises == 1)){
			$(".jsvmap-marker").each(function(){
				let dataIndex = $(this).attr("data-index");
				let nc = countryArray[dataIndex]["nc"];
				let owner = countryArray[dataIndex]["owner"];
				if(nc == 1 && owner == loggedUser){
					$(this).show();
				} else {
					$(this).hide();
				}
			});

		} else if (meusPaises && (classePaises == 0)) {
			$(".jsvmap-marker").each(function(){
				let dataIndex = $(this).attr("data-index");
				let nc = countryArray[dataIndex]["nc"];
				let owner = countryArray[dataIndex]["owner"];
				if(nc == 0 && owner == loggedUser){
					$(this).show();
				} else {
					$(this).hide();
				}
			});

		} else if (!meusPaises && (classePaises == 1)) {
			$(".jsvmap-marker").each(function(){
				let dataIndex = $(this).attr("data-index");
				let nc = countryArray[dataIndex]["nc"];
				let owner = countryArray[dataIndex]["owner"];
				if(nc == 1){
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		} else if (!meusPaises && (classePaises == 0)){
			$(".jsvmap-marker").each(function(){
				let dataIndex = $(this).attr("data-index");
				let nc = countryArray[dataIndex]["nc"];
				let owner = countryArray[dataIndex]["owner"];
				if(nc == 0){
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		} else if (!meusPaises && (classePaises == 2)){
			$(".jsvmap-marker").each(function(){

				$(this).show();

			});
		} else {
			$(".jsvmap-marker").each(function(){
				let dataIndex = $(this).attr("data-index");
				let nc = countryArray[dataIndex]["nc"];
				let owner = countryArray[dataIndex]["owner"];
				if(owner == loggedUser){
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		}
	}
// $(document).ready(function(){
  // $('.svg-menu__path__seleccion').on("click", function(e){
      // console.log('oi');
        // var selection = $(this).attr('id');

        // window.location = '/ligas/geral.php?fed='+selection;

  // });
// });

	
});



</script>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
