<div style="clear:both;">
<div class="container" role="main">

	<?php
	if (isset($_SESSION['import_message'])) {
		$class = $_SESSION['import_status'] === 'success' ? 'box__success' : 'box__error';
		$display = $_SESSION['import_status'] === 'success' ? 'Feito! ' : 'Erro! ';
		echo "<div style='margin-bottom: 20px; text-align: center;'><div class='{$class}' style='display: block; padding: 15px; border-radius: 8px;'>{$display}" . htmlspecialchars($_SESSION['import_message']) . "</div></div>";
		unset($_SESSION['import_message']);
		unset($_SESSION['import_status']);
	}
	?>

	<form id="importForm" enctype="multipart/form-data" novalidate class="box">

		<div class="box__input centralize_text">
            <input type="file" name="files[]" id="file" class="box__file" data-multiple-caption="{count} arquivos selecionados" multiple />


            <label for="file"><strong>Selecione um arquivo</strong><span class="box__dragndrop"> ou arraste-o aqui</span>.</label>
            <input type="hidden" name="ligaselecionada" id="ligaselecionada" value="" />
            <input type="hidden" name="timeselecionado" id="timeselecionado" value="" />
            <input type="hidden" name="nacionalidade" id="nacionalidade" value=""/>
            <input type="hidden" name="paisligaselecionada" id="paisligaselecionada" value="" />
            <input type="hidden" name="sexo" id="sexo" value="0" />
			<input type="hidden" name="campeonato_jogo_import" id="campeonato_jogo_import" value="10" />
			<input type="hidden" name="fase_jogo_import" id="fase_jogo_import" value="0" />
			<input type="hidden" name="competicao_tipo" id="competicao_tipo" value="1" />
		<button type="submit" class="box__button">Carregar</button>
		</div>


		<div class="box__uploading centralize_text">Carregando&hellip;</div>
		<div class="box__success centralize_text">Feito! <a href="" class="box__restart" role="button">Mais arquivos?</a></div>
		<div class="box__error centralize_text">Erro! <span></span>. <a href="" class="box__restart" role="button">Tente novamente!</a></div>
	</form>

</div><!-- /container -->
</div><!-- /clear:both -->

<script type="text/javascript" src="/js/importar.js?version=<?php echo rand()  ?>"></script>
