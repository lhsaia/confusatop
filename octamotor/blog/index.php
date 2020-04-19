<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "OctaMotor - Blog da FOCA";
$css_filename = "blog";
$css_login = 'login';
$aux_css = "driver_info";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>
	<!-- container - wraps whole page -->
	<div id="container-home-octamotor">
    <div id='blog-container'>
			<div id='blog-menu'>
				<?php
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && $_SESSION['admin_status'] != 0){
	echo '<div id="blog-lateral-bar">';
		echo "<button id='new-button'>Novo</button>";
		echo "<button id='edit-button'>Editar</button>";
		echo "<button disabled='disabled' id='save-button'>Salvar</button>";
	echo "</div>";

	$height_class = " admin-height ";
} else {
	$height_class = " full-height ";
}
				 ?>
				<label for="blog-post-list">
					Posts
				</label>
				<div id='blog-post-list' class='<?php echo $height_class ?>'>
				</div>
			</div>
      <div id='blog-text-area'>

      </div>
    </div>
  </div>

  <script src="js/medium-editor.js?v=1"></script>
  <link rel="stylesheet" href="css/medium-editor.css">
  <link rel="stylesheet" href="css/themes/default.css">
	<link rel="stylesheet" href="css/medium-editor-embed-button.min.css" type="text/css" media="screen" charset="utf-8">
	<script type="text/javascript" src="js/medium-editor-embed-button.js?v=9"></script>


  <script>

	var localTimeOffset = -new Date().getTimezoneOffset() * 60;
	var editingFileName = "";
	var currentlyEditing = false;
	var logged_user = {  };

	function convertDate(timestamp){
		let usedDate = new Date(((parseInt(timestamp)))*1000);

		let date = usedDate.getDate();
		let month = usedDate.getMonth(); //Be careful! January is 0 not 1
		let year = usedDate.getFullYear();

		//console.log(usedDate);

		return dateString = date + "-" +(month + 1) + "-" + year;
	}

	function setLoggedUser(){

	    Object.defineProperty(logged_user, 'user_id', {
	        value: '<?php echo (isset($_SESSION['user_id'])? $_SESSION['user_id'] : "") ?>',
	        writable : false,
	        enumerable : true,
	        configurable : false
	    });

	    Object.defineProperty(logged_user, 'admin_status', {
	        value: '<?php echo (isset($_SESSION['admin_status'])? $_SESSION['admin_status'] : "") ?>',
	        writable : false,
	        enumerable : true,
	        configurable : false
	    });

	}

	setLoggedUser();

	function loadPostList(){
		$.ajax({
			url: 'load_post_list.php',
			type: 'POST',
			dataType: 'json',
			data: {}
		})
		.done(function(data) {
			//console.log("success");
			let newDiv = document.createElement("button");
			let blogPostList = document.getElementById("blog-post-list");
			blogPostList.innerHTML = "";
			for(item of data.post_list){

				let dateString = convertDate(item.timestamp);

				newDiv.textContent = item.title + " (" + dateString + ")";
				newDiv.setAttribute("id", item.fileName);
				newDiv.setAttribute("data-author", item.author_name);
				newDiv.className = " load-button ";
				blogPostList.appendChild(newDiv.cloneNode(true));
			}

			$(".load-button").click(function(){
				let postCode = $(this).attr("id");
				if(document.querySelector("#save-button")){
					if(document.querySelector(".editable") && $("#save-button").prop("disabled", false)){
						if(confirm("Deseja mesmo sair sem salvar a escrita atual?")){
							$("#save-button").prop("disabled", "disabled");
							if(isAuthor(postCode)){
								$("#edit-button").prop("disabled", false);
							}
							loadPost(postCode);
						}
					} else {
						$("#save-button").prop("disabled", "disabled");
						if(isAuthor(postCode)){
							$("#edit-button").prop("disabled", false);
						}
						loadPost(postCode);
					}
				} else {
					loadPost(postCode);
				}






			});

console.log(editingFileName);
if(editingFileName == ""){
	let first_link = document.querySelector("#blog-post-list button:first-child");
	first_link.click();
} else {
	console.log("aqui");

	let first_link = document.getElementById(editingFileName);
		console.log(first_link);
	first_link.click();
}


		})
		.fail(function() {
			console.log("error");
		});

	}

	$(document).ready(function() {
		loadPostList();
	});

	function isAuthor(postCode){
		let aux_str = postCode.split("U");
		aux_str = aux_str[1].replace(/\D/g,'');

		let user_id = logged_user.user_id;

		if(aux_str === user_id){
			console.log(true);
			return true;
		} else {
			return false;
		}
	}

	function loadPost(postCode){
		$.ajax({
			url: 'load_post.php',
			type: 'POST',
			dataType: 'json',
			data: {postCode: postCode}
		})
		.done(function(data) {
			//console.log("success");
			let loadPostHTML = data.post_data;

			let split = postCode.split("U");
			let timestamp = split[0].replace(/\D/g,'');
			let date = convertDate(timestamp);

			let credit_div = document.createElement("div");
			let main_div = document.createElement("div");
			main_div.innerHTML = loadPostHTML;
			let credit_author = document.createElement("span");
			credit_author.textContent = document.getElementById(postCode).getAttribute("data-author");
			let credit_date = document.createElement("span");
			credit_date.textContent = date;
			credit_date.setAttribute("id", "credit_date");
			credit_author.setAttribute("id", "credit_author");
			credit_div.setAttribute("id", "credit_div");
			credit_div.appendChild(credit_author);
			credit_div.appendChild(credit_date);
			let reference_element = main_div.getElementsByTagName("p")[0];
			main_div.insertBefore(credit_div, reference_element);
			$("#blog-text-area").html("");
			let blog_element = document.getElementById("blog-text-area");
			blog_element.appendChild(main_div);
			editingFileName = postCode;
		})
		.fail(function() {
			console.log("error");
		});

	}


	var editor;

	$("#save-button").click(function(){
		var jsonData = editor.serialize();
		$("#save-button").prop("disabled", "disabled");
		$("#edit-button").prop("disabled", false);
		$(".editable").removeClass("editable");

		$.ajax({
			url: 'save_post.php',
			type: 'POST',
			dataType: 'json',
			data: {jsonData: jsonData,
							editingFileName: editingFileName}
		})
		.done(function() {
			//console.log("success");
			//editingFileName = "";
			loadPostList();
		})
		.fail(function() {
			console.log("error");
		});

	});

	$("#new-button").click(function(){

		if(document.querySelector(".editable")){
			if(confirm("Deseja mesmo sair sem salvar a escrita atual?")){
				createNew();
			}
		} else {
			createNew();
		}


	});

	function createNew(){
		editingFileName = "";
		$("#save-button").prop("disabled", false);
		$("#edit-button").prop("disabled", "disabled");
		let newPostHTML = "<div class='editable'><h1 class='text-title'>Titulo</h1><h4 class='text-subtitle'>Subtitulo</h4></div>";
		$("#blog-text-area").html(newPostHTML);
		editor = new MediumEditor('.editable', {
			buttonLabels: 'fontawesome',
			extensions: {
					embedButton: new EmbedButtonExtension()
			},
			toolbar: {
					buttons: [
							'bold',
							'italic',
							'underline',
							'h2',
							'h3',
							'quote',
							'embedButton'
					]
			}
	});
	}

	$("#edit-button").click(function(){
		$("#blog-text-area > div").addClass("editable");
		$("#edit-button").prop("disabled", "disabled");
		$("#save-button").prop("disabled", false);
		let credit_div = document.getElementById("credit_div");
		console.log(credit_div);
		credit_div.parentNode.removeChild(credit_div);
		editor = new MediumEditor('.editable', {
			buttonLabels: 'fontawesome',
			extensions: {
					embedButton: new EmbedButtonExtension()
			},
			toolbar: {
					buttons: [
							'bold',
							'italic',
							'underline',
							'h2',
							'h3',
							'quote',
							'embedButton'
					]
			}
	});

	});



	// $(function () {
	//     $('.editable').mediumInsert({
	//         editor: editor
	//     });
	// });

	// 	editor = new MediumEditor('.editable', {
	// 		buttonLabels: 'fontawesome',
	// 		extensions: {
	// 				embedButton: new EmbedButtonExtension()
	// 		},
	// 		toolbar: {
	// 				buttons: [
	// 						'bold',
	// 						'italic',
	// 						'underline',
	// 						'h2',
	// 						'h3',
	// 						'quote',
	// 						'embedButton'
	// 				]
	// 		}
	// });


	</script>
    <?php

    include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

    ?>
