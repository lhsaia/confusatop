
	//'use strict';

	;( function ( document, window, index )
	{
		// feature detection for drag&drop upload
		var isAdvancedUpload = function()
			{
				var div = document.createElement( 'div' );
				return ( ( 'draggable' in div ) || ( 'ondragstart' in div && 'ondrop' in div ) ) && 'FormData' in window && 'FileReader' in window;
			}();


		// applying the effect for every form
		var forms = document.querySelectorAll( '.box' );
		Array.prototype.forEach.call( forms, function( form )
		{
			var input		 = form.querySelector( 'input[type="file"]' ),
				label		 = form.querySelector( 'label' ),
				errorMsg	 = form.querySelector( '.box__error span' ),
				restart		 = form.querySelectorAll( '.box__restart' ),
				droppedFiles = false,
				showFiles	 = function( files )
				{
					label.textContent = files.length > 1 ? ( input.getAttribute( 'data-multiple-caption' ) || '' ).replace( '{count}', files.length ) : files[ 0 ].name;
				},
				triggerFormSubmit = function()
				{
					//var event = document.createEvent( 'HTMLEvents' );
                    //event.initEvent( 'submit', true, false );
                    //event.preventDefault();
                    //console.log(event);
                    var event = new Event('acionarAjax');
					form.dispatchEvent( event );
				};

			// letting the server side to know we are going to make an Ajax request
			var ajaxFlag = document.createElement( 'input' );
			ajaxFlag.setAttribute( 'type', 'hidden' );
			ajaxFlag.setAttribute( 'name', 'ajax' );
			ajaxFlag.setAttribute( 'value', 1 );
			form.appendChild( ajaxFlag );

			// automatically submit the form on file select
			input.addEventListener( 'change', function( e )
			{
				showFiles( e.target.files );
				
				triggerFormSubmit();

				
			});
			
			// drag&drop files if the feature is available
			if( isAdvancedUpload )
			{
				form.classList.add( 'has-advanced-upload' ); // letting the CSS part to know drag&drop is supported by the browser

				[ 'drag', 'dragstart', 'dragend', 'dragover', 'dragenter', 'dragleave', 'drop' ].forEach( function( event )
				{
					form.addEventListener( event, function( e )
					{
						// preventing the unwanted behaviours
						e.preventDefault();
                        e.stopPropagation();
                        //event.preventDefault();
					});
				});
				[ 'dragover', 'dragenter' ].forEach( function( event )
				{
					form.addEventListener( event, function()
					{
						form.classList.add( 'is-dragover' );
					});
				});
				[ 'dragleave', 'dragend', 'drop' ].forEach( function( event )
				{
					form.addEventListener( event, function()
					{
						form.classList.remove( 'is-dragover' );
					});
				});
				form.addEventListener( 'drop', function( e )
				{
					droppedFiles = e.dataTransfer.files; // the files that were dropped
					showFiles( droppedFiles );

					
					triggerFormSubmit();

				});
			}


			
            form.addEventListener( 'acionarAjax', function( evt )
            
			{

                evt.preventDefault();

				// preventing the duplicate submissions if the current one is in progress
				if( form.classList.contains( 'is-uploading' ) ) return false;

				form.classList.add( 'is-uploading' );
				form.classList.remove( 'is-error' );

				if( isAdvancedUpload ) // ajax file upload for modern browsers
				{
                    evt.preventDefault();
                    //event.preventDefault();

					// gathering the form data
					var ajaxData = new FormData( form );

					
					
					if( droppedFiles )
					{
						Array.prototype.forEach.call( droppedFiles, function( file )
						{
							ajaxData.append( input.getAttribute( 'name' ), file );
						});
                    }
                    

                    //teste ajax Jquery

                    $.ajax({
                        type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
                        url         : '/elements/import_ajax.php', // the url where we want to POST
                        data        : ajaxData, // our data object
                        cache: false,
                        processData: false,
                        contentType: false,
                        dataType    : 'json', // what type of data do we expect back from the server
                                    encode          : true
                    })
            
                                .done(function(data) {
            
            // log data to the console so we can see
            console.log(data); 
            
                form.classList.add( data.success == true ? 'is-success' : 'is-error' );
                form.classList.remove(data.success == true ? 'is-error' : 'is-success');
                if( !data.success ) errorMsg.textContent = data.error;
            
            // here we will handle errors and validation messages
            }).fail(function(jqXHR, textStatus, errorThrown ){
                console.log("Erro");
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            }).always(function(){
                form.classList.remove( 'is-uploading' );
            });

					// ajax request
					// var ajax = new XMLHttpRequest();
					// ajax.open( form.getAttribute( 'method' ), "https://confusa.top/times/importar_time.php" , true );

					// ajax.onload = function()
					// {
					// 	form.classList.remove( 'is-uploading' );
					// 	if( ajax.status >= 200 && ajax.status < 400 )
					// 	{
					// 		var data = JSON.parse( ajax.responseText );
					// 		form.classList.add( data.success == true ? 'is-success' : 'is-error' );
                    //         form.classList.remove(data.success == true ? 'is-error' : 'is-success');
					// 		if( !data.success ) errorMsg.textContent = data.error;
					// 	}
					// 	else alert( 'Erro. Por favor contacte o admin!');
					// };

					// ajax.onerror = function()
					// {
					// 	form.classList.remove( 'is-uploading' );
					// 	alert( 'Erro. Por favor, tente novamente!' );
					// };

					// ajax.send( ajaxData );
				}
			});

			


			// restart the form if has a state of error/success
			Array.prototype.forEach.call( restart, function( entry )
			{
				entry.addEventListener( 'click', function( e )
				{
					e.preventDefault();
					form.classList.remove( 'is-error', 'is-success' );
					input.click();
				});
			});

			// Firefox focus bug fix for file input
			input.addEventListener( 'focus', function(){ input.classList.add( 'has-focus' ); });
			input.addEventListener( 'blur', function(){ input.classList.remove( 'has-focus' ); });

		});
	}( document, window, 0 ));
function newFunction(event) {
    console.log(event);
}

