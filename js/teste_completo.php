<?php 

//inicialização das variáveis
//$nome_arquivos = pathinfo((basename($_SERVER['PHP_SELF'])), PATHINFO_FILENAME);
$texto_footer = "&copy;2018 frontpage developed by <a href='https://www.linkedin.com/in/luís-cereda'>Luis Cereda</a>";

//Definir os links a serem mostrados na página
$titulo_pagina = "Vasco de Americana - Home";
$texto_header = "Vasco de Americana";
$link_home = "#";
$link_escudo = "#";
$link_estrela_baixo = "#";
$link_estrela_alto = "#";
$link_estadio = "#";
$link_palmeiras = "#";
$link_americana = "#";
$link_comece_aqui = "#";

//inicialização do cabeçalho
include_once 'header.php'; 

//definição dos elementos da tela
echo "<canvas id='canvas'></canvas>";
echo "<div id='centralAnimacao'>";
echo "<a href='#' id = 'escudo' class = 'itensAnimados fadeInDireita unclickable'><p hidden>Fundação do time</p><p hidden>{$link_escudo}</p></a>";
echo "<a href='#' id = 'estrela1' class = 'itensAnimados estrela estrela1Aparecer unclickable'><p hidden>Títulos do amador</p><p hidden>{$link_estrela_baixo}</p></a>";
echo "<a href='#' id = 'estrela2' class = 'itensAnimados estrela estrela2Aparecer unclickable'><p hidden>Título profissional</p><p hidden>{$link_estrela_alto}</p></a>";
echo "<a href='#' id = 'estrela3' class = 'itensAnimados estrela estrela3Aparecer unclickable'><p hidden>Títulos do amador</p><p hidden>{$link_estrela_baixo}</p></a>";
echo "<a href='#' id = 'estadio' class = 'itensAnimados estadioAparecer unclickable'><p hidden>Estádios</p><p hidden>{$link_estadio}</p></a>";
echo "<a href='#' id = 'palmeiras' class = 'itensAnimados palmeirasAparecer unclickable'><p hidden>Vitória contra o <br>Palmeiras de Leão</p><p hidden>{$link_palmeiras}</p></a>";
echo "<a href='#' id = 'americana' class = 'itensAnimados americanaAparecer unclickable'><p hidden>Mudança de nome para Americana</p><p hidden>{$link_americana}</p></a>";
echo "</div>";
echo "<div id = 'titulo' class = 'itensAnimados tituloAparecer'><h1>VASCO DE AMERICANA</h1> <h2>UM PASSADO DE GLÓRIAS</h2><span id='subtexto'>Clique sobre 
os ícones para saber mais<br>Ou <a href='{$link_comece_aqui}'>comece aqui</a></span></div>";
echo "<p id = 'ajuda' class='hidden'></p>";
 
//inicialização do script JS
//echo "<script src='".$nome_arquivos.".js'></script>";

?>

<script>


//prevenção de geração do menu de contexto em celulares ao pressionar os links
window.oncontextmenu = function(event) {
     event.preventDefault();
     event.stopPropagation();
     return false;
};

//carregar ao abrir
 window.onload = function() {

    //compensação pelo posicionamento do cabeçalho
    var headerOffset = 40;

    //definição dos principais elementos
    var elm = document.querySelector('#centralAnimacao');
    var c = document.getElementById("canvas");
    var ctx = c.getContext("2d");
    var ajuda = document.getElementById("ajuda");
  
    //transformar elementos em clicáveis e habilitar hover somente após animação
  elm.addEventListener('animationend', function(e) { 
    if(e.target !== e.currentTarget){
        var item = e.target.id;
        
        document.getElementById(item).classList.add("hoverable");
        document.getElementById(item).classList.remove("unclickable");
    }
    e.stopPropagation();
  },false);

  //desenhar linhas ligando elementos ao texto de ajuda
  function lineDrawing(e){
    if(e.target !== e.currentTarget){
        var item = e.target;
        
        ajuda.classList.remove("hidden");
        ajuda.innerHTML = item.firstChild.innerHTML;

        //posicoes elemento alvo
        topPos = (e.target.getBoundingClientRect().top) - headerOffset;
        leftPos = (e.target.getBoundingClientRect().left);
        rightPos = (e.target.getBoundingClientRect().right);
        bottomPos = (e.target.getBoundingClientRect().bottom) - headerOffset;

        heightElm = bottomPos - topPos;
        widthElm = rightPos - leftPos;

        lineIniPosY = topPos + (heightElm)/2;
        lineIniPosX = leftPos + (widthElm)/2;

        //posicoes ajuda
        topPosAjuda = ajuda.getBoundingClientRect().top - headerOffset;
        leftPosAjuda = ajuda.getBoundingClientRect().left;
        rightPosAjuda = ajuda.getBoundingClientRect().right;
        bottomPosAjuda = ajuda.getBoundingClientRect().bottom - headerOffset;

        heightAjuda = bottomPosAjuda - topPosAjuda;
        widthAjuda = rightPosAjuda - leftPosAjuda;

        lineFinPosY = bottomPosAjuda;
        lineFinPosX = leftPosAjuda + (widthAjuda)/2;


        //definições de tamanho da tela (canvas)
        var alturaJanela = window.innerHeight - headerOffset;
        var larguraJanela = window.innerWidth;
        c.setAttribute("width",larguraJanela);
        c.setAttribute("height",alturaJanela);

        //alteração de posicionamento em mobile
        var mq = window.matchMedia("(max-width: 600px)");

        if (mq.matches) {

            c.width = c.width*0.95;
            c.height = c.height*0.95;
         
            ctx.lineWidth = 1;     
            ctx.strokeStyle = "black";
            ctx.beginPath();
            ctx.moveTo(lineIniPosX, lineIniPosY);

            var referenciaLado;
            if(lineIniPosX < (larguraJanela/2)+1){
                referenciaLado = 10;
            } else {
                referenciaLado = c.width - 10;
            }

            ctx.lineTo(referenciaLado,lineIniPosY);
            ctx.lineTo(referenciaLado,lineFinPosY+20);
            ctx.lineTo(lineFinPosX,lineFinPosY+20);
            ctx.lineTo(lineFinPosX, lineFinPosY);
            ctx.moveTo(lineFinPosX+100,lineFinPosY);
            ctx.lineTo(lineFinPosX-100,lineFinPosY);
        } else {

            ctx.lineWidth = 3;              
            ctx.strokeStyle = "black";  
            ctx.beginPath();
            ctx.moveTo(lineIniPosX, lineIniPosY);

            var referenciaAltura;
            var referenciaLateral;
            if(lineIniPosX < (larguraJanela/3)+120){
                if(lineIniPosY < alturaJanela/3){
                    referenciaAltura = -30;
                    referenciaLateral = -200;
                } else {
                    referenciaAltura = 0;
                    referenciaLateral = 0;
                }
            } else {
                if(lineIniPosY < alturaJanela/3){
                    referenciaAltura = -60;
                    referenciaLateral = -200;
                } else {
                    referenciaAltura = 120;
                    referenciaLateral = 0;
                }
            }

            ctx.lineTo(lineIniPosX, lineIniPosY+referenciaAltura);
            ctx.lineTo(lineFinPosX+referenciaLateral,lineIniPosY+referenciaAltura);
            ctx.lineTo(lineFinPosX+referenciaLateral,lineFinPosY+40);
            ctx.lineTo(lineFinPosX,lineFinPosY+40);
            ctx.lineTo(lineFinPosX, lineFinPosY);
            ctx.moveTo(lineFinPosX+150,lineFinPosY);
            ctx.lineTo(lineFinPosX-150,lineFinPosY);
        }
        
        
        ctx.stroke();  
    
    }
    e.stopPropagation();    
    }

    //executar função de desenhar linha ao fazer hover (pc) ou tocar (mobile)
  elm.addEventListener('mouseover', lineDrawing ,false);
  elm.addEventListener('touchstart', lineDrawing ,false);

  //entrar nos links após suspender o toque (mobile)
    elm.addEventListener('touchend', function(e){
      if(e.target !== e.currentTarget){
        var item = e.target.id;
    	window.location = document.getElementById(item).href;
      }
      e.stopPropagation();
  },false);

  //remover texto de ajuda e linhas ao terminar o hover
    elm.addEventListener('mouseout', function(e){
    if(e.target !== e.currentTarget){
        var item = e.target.id;
        document.getElementById("ajuda").classList.add("hidden");
        ajuda.innerHTML = "";

        ctx.clearRect(0, 0, c.width, c.height);
    }
    e.stopPropagation();    
    },false);

}

</script>



<?php

//definição do rodapé
echo "<div id='bottom-bar' class=''>";
echo "<div id='copyright-text'>";
echo "{$texto_footer}";
echo "</div>";
echo "</div>";

echo "</body>";
echo "</html>";

?>