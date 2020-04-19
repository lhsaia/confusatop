<?php

echo "<!DOCTYPE html>";

echo '<html lang="pt-br" xmlns="http://www.w3.org/1999/xhtml" xml:lang="pt-br">';

echo "<head>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<link href='https://fonts.googleapis.com/css?family=Raleway%7CYrsa' rel='stylesheet'>";
echo "<link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.2.0/css/all.css' integrity='sha384-hWVjflwFxL6sNzntih27bfxkr27PmbbK/iSvJ+a4+0owXq79v+lsFkW54bOGbiDQ' crossorigin='anonymous'>";

//inicialização do CSS
//$numero_versao = random_int(0,1000);
//echo "<link href='".$nome_arquivos.".css?{$numero_versao}' rel='stylesheet'>"; 
?>

<style>

/* determinacao das dimensoes base */
:root{
    --duracao-animacao: 1.3s;
    --altura-simbolo: 400px;
    --largura-simbolo: var(--altura-simbolo);
    --raio: calc(var(--altura-simbolo)*0.65);
    --perc-altura-simbolo: 45.5%;
}


*{
    margin:0;
    padding:0;
    border:0;
}

/* elementos de layout */


#canvas {
    position:absolute;
    /* width:100%;
    height:100%; */
}

#top-bar {
height:40px;
font-family: 'Raleway';
font-size:25px;
z-index:50;
background-color:black;
color:white;  
width:100%;
}

#bottom-bar{
height:40px;
font-family: 'Raleway';
font-size:15px;
background-color:black;
color:white;  
position:absolute;
bottom:0;
width:100%;
}

#bottom-bar a {
    color:gray;
}

#copyright-text{
    color:rgb(131, 131, 131);
    width:100%;
    text-align:center;
    padding-top:10px;
}

#top-menu {
margin:7px 20px 0 0;
float:right;
}

#logo-text {
margin:5px;
float:left;

}

#homeBtn{
    color:white;
    cursor:pointer;
}

#homeBtn:visited{
    color:white;
}

.unclickable{
    pointer-events: none;
    cursor:none;
}

#subtexto{
    font-family: 'Yrsa';
    padding-top:5px;
    font-size:1rem;
    color: gray;
}



p{
    font-size:2.3vw;
    color:gray;
}

h1{
    font-family: 'Raleway';
    font-size:2.9vw;
 }

 h2{
    font-family: 'Raleway';
    font-size:2.3vw;
 }

a{
    text-decoration:none;
    font-weight:bold;
}

a:hover{
    color: black;
}

a:active{
    color:lightgray;
}

a:visited{
    color:darkred;
}

 hr{
    display:block;
    border:none;
    color:white;
    height:4px;
    background-image: linear-gradient(to right, black, white);
 }


 #ajuda{
    font-family: 'Raleway';
    position:absolute;
    top: calc(50% - var(--altura-simbolo)/2);
    left: calc(17% - var(--altura-simbolo)/2);
    width:var(--altura-simbolo);
    text-align:center;
 }

.hoverable:hover, .hoverable:active, .hoverable:focus{
    transform: translate(calc(var(--altura-simbolo)*(-0.075)),calc(var(--altura-simbolo)*(-0.075)));
    filter: drop-shadow(calc(var(--altura-simbolo)*(0.025)) calc(var(--altura-simbolo)*(0.025)) calc(var(--altura-simbolo)*(0.01)) #888888);
 }

.hidden{
    display:none;
}

/* @font-face {
    font-family: 'sports';
    src: url('fonts/high_school_usa_serif-webfont.woff2') format('woff2'),
         url('fonts/high_school_usa_serif-webfont.woff') format('woff');
    font-weight: normal;
    font-style: normal;

} */

/* definicao dos itens animados */
 .itensAnimados {
    background-repeat: no-repeat;
    animation-fill-mode: both;
    position: absolute;
    animation-timing-function: ease;
 }

 #escudo{

    animation-duration: calc(var(--duracao-animacao)*2);
    background-image: url(images/logo.png?1);
    background-size: var(--largura-simbolo);
    height: var(--altura-simbolo);
    width: var(--largura-simbolo);
    top: calc(var(--perc-altura-simbolo) - var(--altura-simbolo)/2);
    left: calc(40% - var(--largura-simbolo)/2);
    z-index:2;
    animation-delay:0.1s;
    animation-direction: backwards;
 }

.estrela{
    background-image: url(images/estrela.png?1);
    background-size:calc(var(--altura-simbolo)/8);
    height:calc(var(--altura-simbolo)/8);
    width:calc(var(--altura-simbolo)/8);
    animation-duration: var(--duracao-animacao);
    top: calc(var(--perc-altura-simbolo) - calc(var(--altura-simbolo)/16));
    left: calc(40% - calc(var(--altura-simbolo)/16));
 }

 #estrela1{
    animation-delay:1s;
 }

  #estrela2{
    animation-delay:1.2s;
 }

  #estrela3{
    animation-delay:1.4s;
 }

  #estadio{
    animation-duration: var(--duracao-animacao);
    background-image: url(images/estadio.png);
    background-size:calc(var(--altura-simbolo)/4);
    height:calc(var(--altura-simbolo)/4);
    width:calc(var(--altura-simbolo)/4);
    top: calc(var(--perc-altura-simbolo) - var(--altura-simbolo)/8);
    left: calc(40% - var(--altura-simbolo)/8);
    animation-delay:2s;
    z-index:11;
 }

 
 #americana{
    animation-duration: var(--duracao-animacao);
    background-image: url(images/americana.png);
    background-size:calc(var(--altura-simbolo)/4);
    height:calc(var(--altura-simbolo)/4);
    width:calc(var(--altura-simbolo)/4);
    top: calc(var(--perc-altura-simbolo) - var(--altura-simbolo)/8);
    left: calc(40% - var(--altura-simbolo)/8);
    animation-delay:2.4s;
    z-index:11;
 }

 
 #palmeiras{
    animation-duration: var(--duracao-animacao);
    background-image: url(images/palmeiras.png);
    background-size:calc(var(--altura-simbolo)/4);
    height:calc(var(--altura-simbolo)/4);
    width:calc(var(--altura-simbolo)/4);
    top: calc(var(--perc-altura-simbolo) - var(--altura-simbolo)/8);
    left: calc(40% - var(--altura-simbolo)/8);
    animation-delay:2.2s;
    z-index:11;
 }

 #titulo{
    color:black;
    position:absolute;
    top: calc(70% - var(--altura-simbolo)/2);
    right: calc(27% - var(--altura-simbolo)/2);
    width: var(--altura-simbolo)*1.1;
    text-align:left;
    animation-delay:2s;
    animation-duration:1s;

 }

 /* inicializacao das animacoes */

 .fadeInDireita {
    animation-name: fadeInDireita;
 }

 .estrela1Aparecer{
    animation-name: estrela1Aparecer;
 }

  .estrela2Aparecer{
    animation-name: estrela2Aparecer;
 }

  .estrela3Aparecer{
    animation-name: estrela3Aparecer;
   }

.estadioAparecer {
    animation-name: estadioAparecer;
 }

 .palmeirasAparecer {
    animation-name: palmeirasAparecer;
 }

 .americanaAparecer {
    animation-name: americanaAparecer;
 }

  .tituloAparecer {
    animation-name: tituloAparecer;
 }

/* definicao dos keyframes */
 
 @keyframes fadeInDireita {
    0% {
       opacity: 0;
       transform: translate(calc(var(--altura-simbolo)/2),0);
    }
    100% {
       opacity: 1;
       transform: translate(0);
    }
 }

 @keyframes estrela1Aparecer {
    0% {
        opacity: 0;
        transform: translate(0);    
    }

    100% {
        opacity: 1;
        transform: translate(calc(var(--altura-simbolo)*(-0.275)), calc(var(--altura-simbolo)*(-0.515)));
    }
 }

  @keyframes estrela2Aparecer {
    0% {
        opacity: 0;
        transform: translateX(0);    
    }

    100% {
        opacity: 1;
        transform: translateY(calc(var(--altura-simbolo)*(-0.59)));
    }
 }

   @keyframes estrela3Aparecer {
    0% {
        opacity: 0;
        transform: translate(0);    
    }

    100% {
        opacity: 1;
        transform: translate(calc(var(--altura-simbolo)*(0.275)), calc(var(--altura-simbolo)*(-0.515)));
    }
 }

    @keyframes tituloAparecer {
    0% {
        opacity: 0; 
    }

    100% {
        opacity: 1;
    }
 }
 
@keyframes estadioAparecer {

    0% {
        opacity: 0;
        transform: rotate(0deg) translateX(var(--raio)) rotate(0deg);
    }

    100% {
        opacity: 1;
        transform: rotate(125deg) translateX(var(--raio)) rotate(-125deg);
    }

}

@keyframes palmeirasAparecer {

0% {
    opacity: 0;
    transform: rotate(0deg) translateX(var(--raio)) rotate(0deg);
}

100% {
    opacity: 1;
    transform: rotate(90deg) translateX(var(--raio)) rotate(-90deg);
}

}

@keyframes americanaAparecer {

0% {
    opacity: 0;
    transform: rotate(0deg) translateX(var(--raio)) rotate(0deg);
}

100% {
    opacity: 1;
    transform: rotate(55deg) translateX(var(--raio)) rotate(-55deg);
}

}

/* media queries para responsividade mobile */
@media only screen and (max-height: 650px) {
    :root{
        --altura-simbolo:350px;
}
}
@media only screen and (max-height: 600px) {
        :root{
            --altura-simbolo:300px;
}
}
@media only screen and (max-height: 550px) {
            :root{
                --altura-simbolo:250px;
}
}

@media only screen and (max-width: 600px) {
    :root{
        --altura-simbolo:200px;
    }

    #titulo {
        top: 60px;
        left: calc(50% - var(--altura-simbolo));
        width: calc(var(--altura-simbolo)*2);

    }

    #ajuda{
        bottom: 80px;
        left: calc(50% - var(--altura-simbolo));
        width: calc(var(--altura-simbolo)*2);
        top: auto;
        
    }


}

</style>




<?php

//definição do título da página
echo "<title>{$titulo_pagina}</title>";
echo "</head>";
echo "<body>";

//definição do cabeçalho
echo "<div id='top-bar' class='elementoFixo'>";
echo "<div id='logo-text'>";
echo "{$texto_header}";
echo "</div>";
echo "<div id='top-menu'>";
echo "<a id='homeBtn' href='{$link_home}' title='Voltar para home' class='icon fas fa-home'></a>";
echo "</div>";
echo "</div>";
echo "<div style='clear:both;'></div>";

?>