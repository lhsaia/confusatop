<div style="clear:both;">
<div class="container" role="main">

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
			<button type="submit" class="box__button">Carregar</button>
		</div>


		<div class="box__uploading centralize_text">Carregando&hellip;</div>
		<div class="box__success centralize_text">Feito! <a href="" class="box__restart" role="button">Mais arquivos?</a></div>
		<div class="box__error centralize_text">Erro! <span></span>. <a href="" class="box__restart" role="button">Tente novamente!</a></div>
	</form>

</div>

<script type="text/javascript" src="/js/importar.js?version=<?php echo rand()  ?>"></script>
