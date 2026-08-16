<?php

$competicao = "Competição de Teste";
$rodada = "Rodada 16";
$data = "02/01/2022";
$hora = "11:45pm";
$upload = "Upload";
$content_length = 67274;



$url = "http://52.203.150.214:8080/CONFUSALive/uploadMatch";

// teste 2
function do_post_request($url, $postdata, $file = null) 
{ 
    $php_errormsg = "";
    $data = ""; 
    $boundary = "---------------------".substr(md5(rand(0,32000)), 0, 10); 

    //Collect Postdata 
    foreach($postdata as $key => $val) 
    { 
        $data .= "--$boundary\n"; 
        $data .= "Content-Disposition: form-data; name=\"".$key."\"\n\n".$val."\n"; 
    } 

    $data .= "--$boundary\n"; 

    //Collect Filedata 
    //foreach($files as $key => $file) 
    //{ 
        $fileContents = file_get_contents($file['tmp_name']); 

        $data .= "Content-Disposition: form-data; name=\"{$key}\"; filename=\"{$file['name']}\"\n"; 
        $data .= "Content-Type: appliction/octet-stream\n"; 
       //$data .= "Content-Transfer-Encoding: binary\n\n"; 
        $data .= $fileContents."\n"; 
        $data .= "--$boundary--\n"; 
    //} 

    $params = array('http' => array( 
           'method' => 'POST', 
           'header' => 'Content-Type: multipart/form-data; boundary='.$boundary,
           'port' => 8080,
           'content' => $data 
        )); 

   $ctx = stream_context_create($params); 
   $fp = fopen($url, 'rb', false, $ctx); 

   if (!$fp) { 
      throw new Exception("Problem with $url, $php_errormsg"); 
   } 


   $response = @stream_get_contents($fp); 
   if ($response === false) { 
      throw new Exception("Problem reading data from $url, $php_errormsg"); 
   } 
   return $response; 
} 

//set data (in this example from post) 

//sample data 
$postdata = array( 
    'competicao' => $competicao, 
    'rodada' => $rodada, 
    'data' => $data, 
    'hora' => $hora, 
    'upload' => $upload
); 

$path = 'AMBxCPS - 12-9-2021.hyl';

$file = [
    'name' => 'file',
    'type' => 'application/octet-stream',
    'tmp_name' => $path,
    'error' => 0,
    'size' => filesize($path),
];


//do_post_request($url, $postdata, $file); 

// teste 1

try {
    
$curl = curl_init($url);

    // Check if initialization had gone wrong*    
    if ($curl === false) {
        throw new Exception('failed to initialize');
    }

curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

$headers = array(
   "Host: 52.203.150.214:8080",
  "Connection: keep-alive",
 // "Content-Length: " . $content_length,
  "Cache-Control: max-age=0",
  "Upgrade-Insecure-Requests: 1",
  "Origin: http://52.203.150.214:8080",
  "Content-Type: multipart/form-data",
  "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.84 Safari/537.36",
  "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
  "Referer: http://52.203.150.214:8080/CONFUSALive/uploadMatch.jsp",
  "Accept-Encoding: gzip, deflate",
  "Accept-Language: en-US,en;q=0.9",
 //  "JSESSIONID=B769A68D9ED6983C7664FDE3614568CE"
);


curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

curl_setopt($curl, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($curl, CURLOPT_STDERR, $verbose); 


$localfile = "AMBxCPS - 12-9-2021.hyl";
//$fp = fopen($localFile, 'r');


$data = array (
                //'file' => new CURLFile ( $localfile, 'application/octet-stream', 'file' ) ,
				'file' => file_get_contents($localfile),
                'competicao' => $competicao,
                'rodada' => $rodada,
                'data' => $data,
                'hora' => $hora,
                'upload' => $upload
        ) ;

curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

//for debug only!
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

//var_dump($data);

$resp = curl_exec($curl);
//var_dump($resp);

    // Check the return value of curl_exec(), too
    if ($resp === false) {
        throw new Exception(curl_error($curl), curl_errno($curl));
    }

    // Check HTTP return code, too; might be something else than 200
    $httpReturnCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    
    rewind($verbose);
$verboseLog = stream_get_contents($verbose);
echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";

echo $verbose;
//echo $httpReturnCode;
    
} catch(Exception $e) {

    trigger_error(sprintf(
        'Curl failed with error #%d: %s',
        $e->getCode(), $e->getMessage()),
        E_USER_ERROR);

} finally {
    // Close curl handle unless it failed to initialize
    if (is_resource($curl)) {
        curl_close($curl);
    }
}







?>