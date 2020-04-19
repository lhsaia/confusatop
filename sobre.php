<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "CONFUSA.top - Sobre";
$css_filename = "mainindex";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>

<nav class='sidenav'>

        <ul>

            <li><a href="#oque">O que é?</a></li>
            <li><a href="#paraquem">Por que e para quem?</a></li>
            <li><a href="#comofoifeito">Como foi feito?</a></li>
            <li><a href="#ultimaatualizacao">Última atualização</a></li>
            <li><a href="#tutorial">Tutorial</a></li>
            <ul class='innerList'>
            <li><a href="#sobreligas">Ligas</a></li>
            <li><a href="#sobremercado">Mercado</a></li>
            <li><a href="#sobreranking">Ranking</a></li>
            <li><a href="#sobrearbitros">Quadro de árbitros</a></li>
            <li><a href="#sobreminhaarea">Minha área</a></li>
            <li><a href="#sobreoutros">Outros links</a></li>
            </ul>
            <li><a href="#novidades">O que vem por aí?</a></li>

        </ul>
    </nav>


<div id="textoSobre">

<div class='anchor' id='oque'></div>
<h1 class='headerSobre' id=''>O que é o CONFUSA.top?</h1>

<p class='paragrafoSobre'>O portal <em>CONFUSA.top</em>, também chamado de <em>portal web</em>, é um site desenvolvido por Luis Cereda cujo objetivo principal é ser usado como gerenciador de equipes e mercado de jogadores para a realidade da Confederação de Futebol Solitário Associado (CONFUSA). Toda a estrutura do site foi criada para ser utilizada em conjunto com a <em>suite</em> <a href='http://ronaldojuniorbr.weebly.com/hexacolor-ymt.html'>Hexacolor</a>, desenvolvida por Ronaldo Junior.</p>

<p class='paragrafoSobre'>O portal pode ser acessado sem login, se o objetivo for apenas visualizar as ligas e equipes existentes no universo da confederação. No entanto, suas principais funcionalidades requerem acesso com senha, que será concedido apenas para membros da CONFUSA através do formulário disponível em <em>Novo Usuário</em> dentro da tela de login (botão com seta no canto superior direito de qualquer página). Para aqueles que desejarem saber mais e fazer parte da confederação, <a href='http://vk.com/futebolsolitario'>visitem-nos em nossa comunidade no VK</a>.</p>

<p class='paragrafoSobre'>De maneira resumida, o usuário pode:</p>
<ul class='featuresListing'>
<li>Importar times em formato .ymt</li>
<li>Importar jogadores em formato .jog</li>
<li>Importar trios de arbitragem em formato .tda</li>
<li>Criar países de confederações paralelas (NC-Board, COPACCHAI, etc.)</li>
<li>Criar ligas, tanto masculinas quanto femininas</li>
<li>Criar times, com opção de geração automática de jogadores</li>
<li>Criar jogadores, com opção de geração automática de informações</li>
<li>Criar técnicos, com opção de geração automática de informações</li>
<li>Criar estádios</li>
<li>Criar climas</li>
<li>Criar trios de arbitragem</li>
<li>Manipular elencos com número ilimtado de jogadores, alternando titulares, reservas e suplentes</li>
<li>Manipular posicionamento tático, cobradores e capitão das equipes</li>
<li>Rebaixar e promover equipes</li>
<li>Promover jogadores aleatórios da base (time) ou para draft (liga)</li>
<li>Editar times, jogadores, técnicos, ligas, países e trios de arbitragem</li>
<li>Criar demografia para países, para a geração de nomes</li>
<li>Criar seleções (principal, sub-21, sub-20, sub-18)</li>
<li>Buscar jogadores e fazer propostas e convocações</li>
<li>Rejeitar, aprovar ou contrapropor propostas recebidas</li>
<li>Ver listagem das maiores transferências e jogadores mais valiosos</li>
<li>Verificar lista de jogadores no exterior, com opção para repatriar em caso de inatividade</li>
<!-- <li>Determinar idades para aposentadoria automática de jogadores</li> -->
<li>Exportar banco de dados completo para uso direto no Hexacolor YMT</li>
</ul>


<p class='paragrafoSobre'>Na seção <a href='#tutorial'>Tutorial</a> podem ser encontrados mais detalhes sobre cada uma das funções.</p>

<div class='anchor' id='paraquem'></div>
<h1 class='headerSobre' id=''>Por que e para quem?</h1>

<p class='paragrafoSobre'>Desde 2017 tenho buscado desenvolver, e agora aprimorar, meus conhecimentos em programação desktop e <em>web</em>, tanto <em>backend</em> quanto <em>frontend</em>. A primeira ferramenta criada foi o Hexagen em 2017 - que foi criado como <em>desktop application</em> em Java para manter a mesma tecnologia da <em>suite</em> Hexacolor, e cujo objetivo era gerar um time completo de jogadores aleatórios e exportar diretamente para o banco de dados do YMT. Em 2018, o projeto do <em>CONFUSA.top</em> nasceu, na verdade, como uma forma de criar um ranking centralizado de seleções, ao mesmo tempo em que eu criava pela primeira vez um site com o uso de PHP, HTML5, CSS, JS, JQuery e MySQL. Devido à complexidade do ranking, o modelo anterior no Excel ficou inviável computacionalmente, além de ter uma acessibilidade reduzida e dependente de envio de arquivos.</p>
<p class='paragrafoSobre'>Depois de concluir o projeto do ranking em alguns dias, começaram a surgir ideias para aproveitar o grande potencial dessa combinação de ferramentas e resolver alguns desejos históricos da comunidade. O gerenciamento de equipes permite manter um banco de dados centralizado, disponível e seguro (não foram poucos os que perderam seus times por problemas de HD). Além disso, agora existe a possibilidade de ter mais do que 23 jogadores no elenco, além de o controle de idades passar a ser automático.</p>
<p class='paragrafoSobre'>Outro ponto importante é que o usuário tem a opção de importar times ou criar, tendo sido o Hexagen integrado completamente ao portal, com melhoria de algoritmo para criação e escalação de times inteiros, o que permite criar ligas de países em instantes. Com o gerenciador implementado, a ideia do mercado passa a ser trivial: como cada jogador e time agora tem um ID único global, eles podem ser negociados com poucos cliques, e a garantia de que não terminarão duplicados após uma negociação. De forma a garantir a não duplicidade, o usuário é avisado para que atualize seu banco de dados do Hexacolor YMT, o que é feito com apenas um clique.</p>
<p class='paragrafoSobre'>Com isso, a CONFUSA segue aprimorando suas ferramentas (que dez anos atrás eram papel, caneta e dados) e no processo eu sigo aprimorando minhas habilidades com as tecnologias envolvidas.</p>

<div class='anchor' id='comofoifeito'></div>
<h1 class='headerSobre' id=''>Como foi feito?</h1>

<p class='paragrafoSobre'>O portal CONFUSA.top foi desenvolvido em PHP, usando HTML5, CSS e JS (JQuery), além da integração com o banco de dados em MySQL. Devido à criação em etapas do site e ao processo de aprendizado conforme construía, algumas estruturas mais novas são melhores que estruturas criadas semanas antes, embora exista uma lista de itens que serão melhorados aos poucos para aprimorar ainda mais o site.</p>

<p class='paragrafoSobre'>O algoritmo utilizado no ranking é uma adaptação do <a href='https://www.eloratings.net/'>Sistema ELO</a>, e o algoritmo utilizado para a criação de jogadores e times de maneira aleatória é uma melhoria do algoritmo do Hexagen. Este consiste em: um gerador de nomes, que utiliza números pseudoaleatórios e bancos de dados com mais de 80000 nomes e 15000 sobrenomes de mais de 30 diferentes origens; um gerador de idades e níveis, que utiliza uma distribuição própria baseada na gaussiana, utilizando três pontos de entrada (média, mínimo e máximo); um gerador de parâmetros com distribuição uniforme; gerador de posições com atributos aleatórios mas com pesos variáveis conforme a posição; gerador de elencos baseado na escolha de formação tática base, com reservas de acordo; e por fim a escalação automática através da análise dos melhores jogadores disponíveis, também com definição de cobradores e capitão baseadas em características e nível dos jogadores. A parte estes dois algoritmos, o restante dos algoritmos é mais direto, consistindo principalmente de busca/retorno, criação e atualização em banco de dados, na maioria das vezes com o uso de <em>ajax</em>.</p>

<p class='paragrafoSobre'>A integração com o Hexacolor YMT é indireta: o portal gera um arquivo database.db3 em SQLite a partir do banco de dados central em MySQL e o usuário faz o download desse arquivo pronto juntamente com as imagens de escudos, uniformes e novas bandeiras, tudo já em um arquivo .zip já na estrutura de pastas do Hexacolor YMT para permitir download, extração e uso direto.</p>

<p class='paragrafoSobre'>Por fim, algumas áreas do site já apresentam compatibilidade com celulares (ranking e árbitros), mas o restante das áreas terá os ajustes de compatibilidade em uma data futura apenas.</p>


<div class='anchor' id='ultimaatualizacao'></div>
<h1 class='headerSobre' id=''>Última Atualização</h1>

<p class='paragrafoSobre'>Na data de 20/12/2018 o site encontra-se em versão beta do primeiro lançamento, com 5 usuários cadastrados. Últimos detalhes da primeira fase estão sendo ajustados, e a segunda fase se iniciará na sequência.</p>

<div class='anchor' id='tutorial'></div>
<h1 class='headerSobre' id=''>Tutorial</h1>

<p class='paragrafoSobre'>Esta seção tem como objetivo apresentar com mais detalhes o que pode ser feito em cada bloco do site. Em breve serão disponibilizados também tutoriais em vídeo com captura de tela. No entanto, o site foi concebido para ser bastante <em>user friendly</em>, fazendo com que a leitura dos tópicos a seguir não seja obrigatória em muitos casos. Em caso de dúvidas ou problemas, fique à vontade para avisar através do formulário de contato (ícone de email no canto superior das páginas).</p>

<div class='anchor' id='sobreligas'></div>
<h2 class='headerSobre' id=''>Ligas</h2>

<p class='paragrafoSobre'>O primeiro bloco do site tem como objetivo ser um painel de visualização de todos os times cadastrados no portal, separados por liga, país e federação. É uma área aberta, ou seja, que pode ser visitada sem login, exceto pelas funcionalidades de edição dos times. Com login, os times podem ser acessados, e então dentro de cada time há três abas, no caso de você ser o dono da equipe, e uma aba no caso de não ser.</p>

<p class='paragrafoSobre'>A aba <strong>Jogadores</strong> permite, para qualquer usuário logado, fazer propostas pelos jogadores, através do botão com uma nota de dinheiro. Ao clicar no botão, um formulário é aberto sobre a página, no qual pode se alterar o valor da proposta, e deve-se selecionar o time que está propondo a oferta. Ao clicar em <em>fazer proposta</em> a proposta é enviada para o dono do jogador, que, no caso de ser o autor da proposta, é aprovada automaticamente.</p>

<p class='paragrafoSobre'>Ainda nessa aba, é possível demitir o jogador (botão com contrato em vermelho), aposentar (botão com óculos) e disponibilizar (botão azul com lista). A diferença entre as três ações é que ao disponibilizar, o jogador segue no plantel mas com o atributo disponível em <em>sim</em> - o que hoje ainda não possui um efeito prático, mas na versão 2 do site poderá permitir contratação imediata; ao demitir o jogador é excluído do plantel e seu contrato é finalizado; e ao aposentar ele além de ser retirado do plantel fica indisponível para contratações.</p>

<!-- dependendo das configurações e do valor da proposta podem fazer com que ela seja aprovada automaticamente -->

<p class='paragrafoSobre'>Por fim, é possível fazer a edição parcial de jogadores através do botão azul com um lápis. Nesta tela, caso seja o dono do país do jogador é possível alterar nome, nascimento, nacionalidade, valor, posições e nível. No caso das posições, se o jogador for goleiro essa escolha é desabilitada. No caso do nível, o nível geral do jogador não será alterado, mas sim será criado um modificador. A intenção disso é permitir que o jogador tenha níveis diferentes no time e na seleção, se assim for o desejo do dono do país. No caso do jogador ser estrangeiro (de outro dono), ficam disponíveis para alteração apenas o valor, o nível (através de modificador) e posições, mas neste caso as posições podem ser apenas aumentadas e não reduzidas. Após cada alteração é necessário aguardar a página recarregar.</p>

<p class='paragrafoSobre'>A aba <strong>Elenco</strong> permite, para qualquer usuário logado em seus times, modificar quem é titular, quem é reserva e quem é suplente. Deve-se apenas selecionar os jogadores clicando neles e fazer as trocas e movimentações utilizando os botões de seta entre as listas de jogadores. Após cada alteração é necessário aguardar a página recarregar.</p>

<p class='paragrafoSobre'>A aba <strong>Posicionamento</strong> permite, para qualquer usuário logado em seus times, alterar o posicionamento dos jogadores em campo. Para isso, apenas clique sobre um jogador e o arraste para a posição desejada. Se houver alguém no local, haverá troca de posição entre eles. Nesta mesma tela, no canto direito, podem ser trocados os cobradores de pênalti e capitão do time. Para isso, deve-se selecionar os novos nomes e clicar em <em>fazer alterações</em>. Para mudar os jogadores de posição no campo não é necessário apertar o botão.</p>

<div class='anchor' id='sobremercado'></div>
<h2 class='headerSobre' id=''>Mercado</h2>

<p class='paragrafoSobre'>O <em>Mercado</em> possui, em sua tela inicial, cinco botões.</p>

<p class='paragrafoSobre'>Os botões <strong>Últimas transferências</strong>, <strong>Maiores transferências</strong> e <strong>Jogadores mais valiosos</strong> são apenas para consulta, e têm caráter geral, ou seja, irão aparecer informações de toda a CONFUSA. A ideia para essas áreas, assim como a área dos times, surgiu com base no site <a href='http://transfermarkt.com'>Transfermarkt</a>.</p>

<!-- <p class='paragrafoSobre'>O botão <strong>Janelas de transferência</strong> permite ao dono alterar, e a todos consultar, as janelas de transferência dos países. O efeito das janelas variará conforme o que for selecionado nas configurações, variando de nenhum até a total impossibilidade de transferências no período em que a janela está fechada.</p> -->

<p class='paragrafoSobre'>O botão <strong>Janelas de transferência</strong> permite ao dono alterar, e a todos consultar, as janelas de transferência dos países. As janelas ainda não possuem um efeito prático, mas a partir da versão 2 do site serão usadas para controlar negociações.</p>

<p class='paragrafoSobre'>A área de <strong>Busca de jogadores</strong> é uma das mais importantes do portal, pois ela permite fazer uma busca avançada de jogadores, com uma série de opções, e utilizar os jogadores retornados na busca tanto para fazer proposta quanto para convocar para seleções. É uma ferramenta poderosa para facilitar ambos os processos. Para realizar a busca, deve se selecionar, obrigatoriamente:</p>

<ul class='featuresListing'>
<li><strong>Faixa de níveis</strong> - Serão retornados apenas jogadores dentro dessa faixa</li>
<li><strong>Faixa de idades</strong> - Serão retornados apenas jogadores dentro dessa faixa</li>
<li><strong>Faixa de valores</strong> - Serão retornados apenas jogadores dentro dessa faixa</li>
</ul>

<p class='paragrafoSobre'>E de maneira opcional:</p>

<ul class='featuresListing'>
<li><strong>Posições</strong> - Pode ser escolhida uma ou mais de uma. Por padrão, serão retornados jogadores que tenham qualquer uma delas.</li>
<li><strong>Cobrador de Falta</strong> - Se habilitado, selecionará apenas cobradores de falta.</li>
<li><strong>Disponível</strong> - Se habilitado, selecionará apenas jogadores marcados como <em>disponível</em>.</li>
<li><strong>Sem clube</strong> - Se habilitado, selecionará apenas jogadores sem clube.</li>
<li><strong>Nome</strong> - Buscará apenas jogadores que contenham o texto escrito como parte de seu nome/sobrenome.</li>
<li><strong>Nacionalidade</strong> - Buscará apenas jogadores da nacionalidade escolhida.</li>
<li><strong>Mentalidade</strong> - Buscará apenas jogadores com a mentalidade desejada.</li>
</ul>

<p class='paragrafoSobre'>Além disso, existem três caixas de opção que podem ser alteradas:</p>

<ul class='featuresListing'>
<li><strong>Apenas CONFUSA / Incluir NC-Board e reais</strong> - Dependendo da seleção, lista apenas jogadores da CONFUSA ou todos.</li>
<li><strong>Masculino / Feminino</strong> -Dependendo da seleção, mostra jogadores ou jogadoras.</li>
<li><strong>Qualquer uma das posições marcadas / Todas as posições marcadas</strong> - Alterna entre buscar jogadores que tenham qualquer uma das posições selecionadas ou jogadores que tenham todas elas.</li>
</ul>

<p class='paragrafoSobre'>As buscas, convocações e propostas ocorrem todas sem recarregar a página, de forma que para uma mesma busca podem ser feitas várias ações. Caso a página seja recarregada pelo usuário, os valores de filtro retornarão para o valor padrão.</p>

<p class='paragrafoSobre'>A área de <strong>Busca de técnicos</strong>, funciona de maneira análoga à de jogadores, com alguns itens a menos para seleção apenas.</p>

<div class='anchor' id='sobreranking'></div>
<h2 class='headerSobre' id=''>Ranking</h2>

<p class='paragrafoSobre'>A área do Ranking foi a primeira a ser criada no site, e é visualmente inspirada no <a href='https://www.fifa.com/fifa-world-ranking/ranking-table/men/'> ranking da FIFA</a> (no momento da criação). No ranking, é possível ver o ranking geral ou separado por federações, e é possível selecionar uma determinada seleção para ver mais informações a respeito dela. Ao clicar em uma seleção, irão aparecer todos os jogos dessa seleção, bem como caixas informativas no topo, que, se clicadas, revelam recordes e estatísticas da seleção. A atividade também aparece nessa página, e é determinada automaticamente pelo site quando determinada seleção ficar mais de 2 anos sem jogar, independentemente do status na CONFUSA em si. Ainda no Ranking, é possível acessar a aba de <strong>Jogos e Recordes</strong> para pesquisar sobre qualquer jogo já ocorrido até hoje e também ver os recordes gerais da CONFUSA ao clicar nos quadros informativos. Por fim, para usuários logados, na faixa laranja de usuário, pode se clicar em <strong>Ranking->Inserir jogo</strong> para inserir jogos que não estejam no ranking.</p>

<div class='anchor' id='sobrearbitros'></div>
<h2 class='headerSobre' id=''>Quadro de árbitros</h2>

<p class='paragrafoSobre'>O quadro de árbitros da confusa permite visualizar todo o quadro da confederação, ou apenas de uma determinada federação, ao clicar na respectiva aba. Na tela inicial, é possível para um usuário logado editar seus trios de arbitragem, através do botão azul com lápis, apagar, através do botão de lixeira vermelho ou exportar o trio no formato (.tda), sendo que esta opção pode ser utilizada por qualquer usuário logado e não apenas o dono. Ainda dentro da área de arbitragem, na barra laranja pode-se entrar em <strong>Arbitragem->Criar trio de arbitragem</strong> para criar um trio do zero, ou <strong>Arbitragem->Importar trio de arbitragem</strong> para importar um trio no formato .tda diretamente. Um ponto importante é que, ao criar pelo portal, não é necessário escrever a sigla do país entre parênteses no nome, apenas selecionar o país. Mas no caso de importação, o árbitro virá sem país a menos que tenha essa informação no nome. No caso de não haver, essa informação pode ser editada depois.</p>

<div class='anchor' id='sobreminhaarea'></div>
<h2 class='headerSobre' id=''>Minha área</h2>
<p class='paragrafoSobre'>Único quadro 100% disponível para usuários logados, o <em>Minha área</em> é o setor mais complexo do site e é dividido em 12 partes.</p>

<p class='paragrafoSobre'>Em <strong>Países</strong>, o usuário pode editar ou criar novos países, sejam da CONFUSA, NC-Board, COPACCHAI ou outras federações. A única diferença é que existe uma caixa de seleção para o <em>ranking</em> que deve ser utilizada apenas para países que são ou já foram membros da CONFUSA. Essa caixa, uma vez editada e marcada, não pode ser desmarcada, por questões de integridade do <em>ranking</em></p>

<p class='paragrafoSobre'>Ainda em <strong>Países</strong>, o usuário pode acessar a área de <strong>Seleções</strong> ao clicar no botão com o globo. Nessa área, é possível criar novas seleções (principais, olímpicas, sub-20 ou sub-18) ou editar as informações das seleções atuais. Também pode ser acessada a área de <strong>Demografia</strong> através do botão com o simbolo de alfabeto. Nessa área, o usuário pode determinar qual a composição demográfica do país, tanto para nome quanto para sobrenome, o <em>índice de miscigenação</em>, que vai determinar qual a chance de um nome de uma origem ter sobrenome de outra e a <em>ocorrência de nome duplo</em>, que irá determinar qual a chance da pessoa ter dois nomes em determinada origem. Um ponto importante nessa página é que, caso o total da composição não seja 100% da demografia, o sistema ajustará para que a visualização e uso sejam normalizados em 100%, mas os valores arquivados serão o que foi inserido pelo usuário. O usuário pode optar por apagar determinada demografia, mas caso ela seja definida para nome e sobrenome, será apagada de ambos. </p>

<p class='paragrafoSobre'>Na área de <strong>Ligas</strong>, o usuário pode editar suas ligas e inserir novas ligas. O conceito de <em> liga</em> foi definido para que os times fiquem mais organizados no portal, assim como separar corretamente ligas e times masculinos de femininos. Ainda nessa área, com o botão que possui uma mão com o indicador levantado, é possível criar jogadores para <em>draft</em> na liga em questão. Para uma criação correta, a liga deve possuir ao menos um time. Serão criados x * 2 jogadores, sendo x o número de times, e será usado o nível médio da liga como base para a geração dos jogadores, que serão sempre jovens. No caso de uma liga ser inserida sem escudo ou uniformes, serão utilizados padrões.</p>

<p class='paragrafoSobre'>Na área de <strong>Times</strong>, o usuário pode editar seus times e inserir novos times. No caso da importação de time, todos os jogadores, estádio, técnico e clima serão importados automaticamente. A importação pode ser feita de mais de um time ao mesmo tempo, mas recomenda-se não exagerar para não sobrecarregar o sistema, além de respeitar o máximo teórico de 20 arquivos simultâneos. No caso da criação de times, os botões marcados com o símbolo de dois dados são necessários apenas caso o usuário queira os jogadores aleatórios. No caso de utilizar o botão <em>inserir sem jogadores</em> será criado apenas o time, vazio. No caso de <em>inserir com jogadores</em> serão criados o número de jogadores determinado, com as características determinadas e a formação escolhida. O time também será escalado automaticamente e os cobradores e capitão selecionados. O técnico também será criado aleatoriamente. Informações como estádio, escudo, uniformes, torcida, fidelidade e nome nunca serão aleatórias e devem continuar sendo inseridas pelo usuário. Na tela de edição, ainda existe a opção de subir um jovem jogador aleatório da base, com o botão com o dedo erguido. No caso de um time ser inserido sem escudo ou uniformes, serão utilizados padrões.</p>

<p class='paragrafoSobre'>Na área de <strong>Jogadores</strong>, o usuário pode editar seus jogadores e inserir novos jogadores. No caso da importação de jogadores, pode ser feita de mais de um jogador ao mesmo tempo, mas recomenda-se não exagerar para não sobrecarregar o sistema, além de respeitar o máximo teórico de 20 arquivos simultâneos. A importação de jogadores deve ser usada apenas para jogadores sem clube, uma vez que ao importar time os jogadores são importados junto. Na criação de jogadores, o <em>Hexagen</em> funciona de maneira diferente da área de <em>times</em>: ao clicar, são criadas as informações aleatórias para o jogador, mas ele não é inserido automaticamente - podendo o usuário alterar qualquer coisa antes de inserir. Antes de aleatorizar, é possível selecionar um país e uma posição, se assim o usuário desejar, e o <em>Hexagen</em> respeitará apenas esses dois campos. Ainda sobre Jogadores, na tela de edição podem ser alterados quaisquer dados do jogador, exceto se ele estiver no exterior (com outro usuário), sendo que nesse caso as opções ficam bastante restritas. Um ponto importante: qualquer alteração de nível nessa tela será balanceada pelos modificadores nos times, sendo que os jogadores continuarão com o mesmo nível nos times, através dos modificadores.</p>

<p class='paragrafoSobre'>Na áreas de <strong>Técnicos</strong>, <strong>Estádios</strong> e <strong>Climas</strong> a lógica é a mesma das telas já mencionadas. Um ponto importante é que o clima tem que ser criado antes do estádio, e o estádio antes do time, no caso de criação dentro do portal. No caso de importação de times, os estádios e climas são importados automaticamente - e se existir um estádio ou clima de mesmo usuário e mesmo nome, ele não é criado em duplicata, mas sim linkado com o já existente.</p>

<p class='paragrafoSobre'>No setor de <strong>Parâmetros HYMT</strong>, o usuário pode alterar suas opções para o Hexacolor YMT, bem como criar e alterar os parâmetros para seus países no Hexacolor YMT. </p>

<p class='paragrafoSobre'>Ao clicar em <strong>Exportar para HYMT</strong>, o sistema irá fazer uma verificação dos times do jogador para ver se há algo pendente, e se houver irá listar e não concluirá a exportação. Pendências possíveis são menos jogadores que o mínimo (tanto titulares quanto gerais), mais reservas que o permitido, número incorreto de goleiros, capitão e batedores escalados. Resolvidas as pendências, o sistema irá gerar um arquivo <em>database.db3</em> para cada país, 100% compatível com o Hexacolor YMT, e em instantes será disponibilizado um pacote em .zip para o usuário baixar. Dentro do .zip, uma pasta para cada país, e dentro de cada pasta, uma estrutura de pastas pronta para extrair direto na pasta do Hexacolor YMT. <strong>Atenção: sempre use um Hexacolor vazio ou tenha um backup do seu database.db3 atual para o caso de algo sair errado!</strong>. O pacote pode ser, em teoria, exportado infinitas vezes na temporada ou fora dela, uma vez que tudo tem ID fixo, ou seja, um jogador, por exemplo, sempre vai ter o mesmo ID. Um símbolo amarelo de exclamação sobre o botão de exportar significa que foram feitas alterações depois da última data em que o usuário exportou um arquivo. <strong>Atenção! Para o cálculo correto dos atributos dos jogadores, o Hexacolor YMT tem que ser utilizado obrigatoriamente na versão 2.10!</strong></p>

<p class='paragrafoSobre'>Em <strong>Propostas de jogadores</strong>, o usuário terá, sobre o botão, a sinalização de quantas propostas pendentes possui. Entrando na área, tem três opções para cada proposta: <em>aceitar</em>, na qual o jogador é automaticamente transferido para o clube que propôs; <em>recusar</em>, na qual o clube que propôs recebe uma negativa e a transferência é cancelada; e <em>contrapropor</em>, na qual o usuário pode propor um novo valor, e a proposta irá retornar para o clube que a fez, para que este possa aprovar ou recusar.</p>

<p class='paragrafoSobre'>Em <strong>Propostas de técnicos</strong>, o usuário terá, sobre o botão, a sinalização de quantas propostas pendentes possui para técnicos. Entrando na área, tem duas opções para cada proposta: <em>aceitar</em>, na qual o técnico é automaticamente transferido para o clube que propôs ou <em>recusar</em>, na qual o clube que propôs recebe uma negativa e a transferência é cancelada.</p>

<p class='paragrafoSobre'>Por fim, em <strong>Jogadores no Exterior</strong>, o usuário pode consultar todos os seus jogadores que atuam fora, acabando com o problema de jogadores perdidos em países inativos. No caso do país no qual o jogador está se tornar (ou já ser) inativo, o botão de <em>repatriar</em> é habilitado e o jogador pode retornar ao seu país de origem sem vínculos contratuais com nenhum clube.</p>

<div class='anchor' id='sobreoutros'></div>
<h2 class='headerSobre' id=''>Outros links</h2>
<p class='paragrafoSobre'>Por uma questão de centralização e comodidade, os links para a comunidade do Futebol Solitário no VK, CONFUSAlive, Confusopédia e Portal COISO são disponibilizados na página inicial.</p>

<div class='anchor' id='novidades'></div>
<h1 class='headerSobre' id=''>O que vem por aí?</h1>
<p class='paragrafoSobre'>Muitas novidades podem surgir no portal, mas no momento a prioridade é correção de bugs e melhorias do código. Em termos de novidades, a principal novidade da fase 2 serão as áreas de federação, com a possibilidade de classificar times para competições continentais com um clique, e a exportação do pacote Hexacolor das competições da federação com mais um clique apenas.</p>

</div>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
