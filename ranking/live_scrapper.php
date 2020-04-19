<?php

function get_web_page( $url ) {
    $res = array();
    $options = array( 
        CURLOPT_RETURNTRANSFER => true,     // return web page 
        CURLOPT_HEADER         => false,    // do not return headers 
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects 
        CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36", // who am i 
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect 
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect 
        CURLOPT_TIMEOUT        => 120,      // timeout on response 
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects 
        CURLOPT_LOCALPORT      => 40000
        //CURLOPT_HTTPHEADER     => Array("GET /CONFUSALive/matches?when=previous HTTP/1.1")
    ); 
    $ch      = curl_init( $url ); 
    curl_setopt_array( $ch, $options ); 
    $content = curl_exec( $ch ); 
    $err     = curl_errno( $ch ); 
    $errmsg  = curl_error( $ch ); 
    $header  = curl_getinfo( $ch ); 
    curl_close( $ch ); 

    $res['error'] = $err;
    $res['errmsg']= $errmsg;
    $res['content'] = $content;     
    $res['url'] = $header['url'];
    return $res; 
}  

$url = "http://52.203.150.214:8080/CONFUSALive/matches";
//$url = "http://portquiz.net";
$html = get_web_page($url); 

echo "ola"; 


$file = 'curltester.txt';
// Open the file to get existing content
$current = file_get_contents($file);
// Append a new person to the file
$current .= print_r($html, true);
// Write the contents back to the file
file_put_contents($file, $current);


?>

<?php 

// Create DOM from URL or file
// //$html = file_get_html('http://52.203.150.214:8080');

// //var_dump($html);

// // // try {
// // //     $ch = curl_init();

// // //     // Check if initialization had gone wrong*    
// // //     if ($ch === false) {
// // //         throw new Exception('failed to initialize');
// // //     }
    
// // //     $agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64)";
// // //     $headers = array();
// // //     $headers[] = "GET /CONFUSALive/matches?when=previous HTTP/1.1";
// // //     // $headers[] = "Content-Type: text/html;charset=UTF-8";
// // //     // $headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3";
// // //     // $headers[] = "Accept-Encoding: gzip, deflate";
// // //     // $headers[] = "Accept-Language: en,pt-BR;q=0.9,pt;q=0.8,fr-CA;q=0.7,fr;q=0.6,en-US;q=0.5,es-CR;q=0.4,es;q=0.3,fr-FR;q=0.2";
    
// // //     curl_setopt($ch, CURLOPT_URL, 'http://confusalive.com');
// // //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// // //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
// // //     curl_setopt($ch, CURLOPT_USERAGENT, $agent);
// // //     curl_setopt($ch, CURLOPT_COOKIEJAR, "cookies.txt");
// // //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// // //     curl_setopt($ch, CURLOPT_REFERER, 'http://52.203.150.214:8080/CONFUSALive/matches');
// // //     curl_setopt($ch, CURLOPT_AUTOREFERER, true);

// // //     $content = curl_exec($ch);

// // //     // Check the return value of curl_exec(), too
// // //     if ($content === false) {
// // //         throw new Exception(curl_error($ch), curl_errno($ch));
// // //     }

// // //     //echo $content;
// // //     /* Process $content here */

// // //     // Close curl handle
// // //     curl_close($ch);
// // // } catch(Exception $e) {

// // //     trigger_error(sprintf(
// // //         'Curl failed with error #%d: %s',
// // //         $e->getCode(), $e->getMessage()),
// // //         E_USER_ERROR);

// // // }



// // // //require('utils/simple_html_dom.php');

// // // // Create DOM from URL or file
// // // //$html = file_get_html('http://52.203.150.214:8080/CONFUSALive/matches?when=previous');
 
// session_start();

// include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// $page_title = "Ranking de Seleções - Masculino";
// $css_filename = "indexRanking";
// $css_login = 'login';
// $css_versao = date('h:i:s');
// include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

// var url = "http://52.203.150.214:8080/CONFUSALive/matches?when=previous";

// function createCORSRequest(method, url) {
//   var xhr = new XMLHttpRequest();
//   if ("withCredentials" in xhr) {

//     // Check if the XMLHttpRequest object has a "withCredentials" property.
//     // "withCredentials" only exists on XMLHTTPRequest2 objects.
//     xhr.open(method, url, true);

//   } else if (typeof XDomainRequest != "undefined") {

//     // Otherwise, check if XDomainRequest.
//     // XDomainRequest only exists in IE, and is IE's way of making CORS requests.
//     xhr = new XDomainRequest();
//     xhr.open(method, url);

//   } else {

//     // Otherwise, CORS is not supported by the browser.
//     xhr = null;

//   }
//   return xhr;
// }

// var xhr = createCORSRequest('GET', url);
// if (!xhr) {
//   throw new Error('CORS not supported');
// }

// xhr.onload = function() {
//  var responseText = xhr.responseText;
//  console.log(responseText);
//  // process the response.
// };

// xhr.onerror = function() {
//   console.log('There was an error!');
// };

// xhr.withCredentials = true;
// xhr.send();
    
    
//     $.ajax({
//     type: 'GET',
//     url: 'http://52.203.150.214:8080/CONFUSALive/matches',
//     crossDomain: true,
//     data: { 
//     when: "previous"
//   },
//     dataType: 'text/html',
//     success: function(responseData, textStatus, jqXHR) {
//         var value = responseData;
//     },
//     error: function (responseData, textStatus, errorThrown) {
//         alert('POST failed.');
//     }
// });

 
//  require_once 'utils/Requests/library/Requests.php';


// Requests::register_autoloader();
 
//  $headers = array('Accept' => 'application/json');
// //$options = array('auth' => array('user', 'pass'));
// $request = Requests::get('http://52.203.150.214:8080/CONFUSALive/matches?when=previous', $headers);

// var_dump($request->status_code);
// // int(200)

// var_dump($request->headers['content-type']);
// // string(31) "application/json; charset=utf-8"

// var_dump($request->body);
// // string(26891) "[...]"

// // require('utils/simple_html_dom.php');