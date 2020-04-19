<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Configurações - ".$_SESSION['nomereal'];
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

echo "<div id='quadro-container'>";
echo "<h2>Configurações - ".$_SESSION['nomereal']."</h2>";
echo "<hr>";

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
?>

<div id='errorbox'></div>

<form action="" >
<p class='form-titulo'>Propostas</p>
  <div class='form-control'><input type="checkbox" name="propostaemail" value=""> Receber propostas por email  </div>
  <div class='form-control'><input type="checkbox" name="propostainterna" >Aceitar propostas internas automaticamente </div>
  <div class='form-control'><input type="checkbox" name="indisponivel" >Permitir propostas para jogadores indisponíveis </div>
  <div class='form-control'><input type="checkbox" name="janela" >Permitir propostas fora da janela </div>
  <div class='form-control'><input type="checkbox" name="valorsuperior" >Aceitar propostas automaticamente quando valor for igual ou superior ao do jogador</div>
  <input class='form-control' type="submit" value="Alterar">
</form>

<hr>

<form action="">
  <p class='form-titulo'>Aposentadoria</p>
  <div class='form-control'><input type="checkbox" name="aposentar" >Aposentar jogadores automaticamente após idade</div>
  <div class='form-control'><input type="number" name="aposentarIdade" min="30" max="45" >Com qual idade?</div>
  <div class='form-control'><input type="checkbox" name="alertaAposentar">Alerta pré-aposentadoria</div>
  <div class='form-control'><input type="number" name="alertaIdade" min="28" max="44">Com qual idade?</div>
  <input class='form-control' type="submit" value="Alterar">
</form>

<form action="">
  <p class='form-titulo'>Sistema F+ (salários e valores)</p>
  <p class='table-titulo'>Ajuste por posição</p>
  <table>
    <thead>
        <tr>
            <th>Posição</th>
            <th>Modificador</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>G</td>
            <td><input type="number" name="goleiro" ></td>
        </tr>
        <tr>
            <td>Z</td>
            <td><input type="number" name="zagueiro" ></td>
        </tr>
        <tr>
            <td>LD</td>
            <td><input type="number" name="latdir" ></td>
        </tr>
        <tr>
            <td>LE</td>
            <td><input type="number" name="latesq" ></td>
        </tr>
        <tr>
            <td>AD</td>
            <td><input type="number" name="aladir" ></td>
        </tr>
        <tr>
            <td>AE</td>
            <td><input type="number" name="alaesq" ></td>
        </tr>
        <tr>
            <td>V</td>
            <td><input type="number" name="volante" ></td>
        </tr>
        <tr>
            <td>MC</td>
            <td><input type="number" name="meiacen" ></td>
        </tr>
        <tr>
            <td>MD</td>
            <td><input type="number" name="meiadir" ></td>
        </tr>
        <tr>
            <td>ME</td>
            <td><input type="number" name="meiaesq" ></td>
        </tr>
        <tr>
            <td>MA</td>
            <td><input type="number" name="meiaata" ></td>
        </tr>
        <tr>
            <td>Aa</td>
            <td><input type="number" name="ataarea" ></td>
        </tr>
        <tr>
            <td>Am</td>
            <td><input type="number" name="atamovi" ></td>
        </tr>
        <tr>
            <td>PD</td>
            <td><input type="number" name="pontdir" ></td>
        </tr>
        <tr>
            <td>PE</td>
            <td><input type="number" name="pontesq" ></td>
        </tr>
    </tbody>
</table>

<p class='table-titulo'>Bônus por polivalência</p>
  <table>
    <thead>
        <tr>
            <th>Posições</th>
            <th>Modificador</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td><input type="number" name="um" ></td>
        </tr>
        <tr>
            <td>2</td>
            <td><input type="number" name="dois" ></td>
        </tr>
        <tr>
            <td>3</td>
            <td><input type="number" name="tres" ></td>
        </tr>
        <tr>
            <td>4+</td>
            <td><input type="number" name="quatro" ></td>
        </tr>
    </tbody>
</table>

<div class='longdiv'><input type="number" name="multgeral" ><p class='table-titulo'>Multiplicador geral</p>  </div>
<br>
<div class='longdiv'><input type="number" name="fatorsalario" ><p class='table-titulo'>Fator de salário</p>  </div>


  <input class='form-control' type="submit" value="Alterar">
</form>

<script>

    alert("Essa página ainda não está funcionando.");

</script>

<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
