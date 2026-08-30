<?php

require_once isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
    ? $_SERVER['DOCUMENT_ROOT'] . '/config/confusalive.php' 
    : dirname(__DIR__) . '/config/confusalive.php';

class ConfusaLiveUploader {
    /**
     * Envia o arquivo .hyl e os metadados da partida para o CONFUSA Live
     *
     * @param string $hylPath Caminho absoluto para o arquivo .hyl gerado
     * @param string $nomeCompeticao Nome da competição (ex: '2026 - Copa do Mundo')
     * @param string $rodada Nome da rodada ou fase (ex: 'Rodada 1', 'Final', etc.)
     * @param string $dataHora Data e hora da partida (string do BD ou timestamp)
     * @return array ['success' => bool, 'httpCode' => int, 'message' => string]
     */
    public static function enviarPartida($hylPath, $nomeCompeticao, $rodada, $dataHora) {
        try {
            if (!file_exists($hylPath)) {
                return [
                    'success' => false,
                    'httpCode' => 0,
                    'message' => "Arquivo .hyl não encontrado em: {$hylPath}"
                ];
            }

            $url = defined('CONFUSA_LIVE_UPLOAD_URL') && !empty(CONFUSA_LIVE_UPLOAD_URL)
                ? CONFUSA_LIVE_UPLOAD_URL
                : 'http://52.203.150.214:8080/CONFUSALive/uploadMatch';

            $timestamp = strtotime((string)$dataHora) ?: time();
            $dataFormatada = date('d/m/Y', $timestamp);
            $horaFormatada = date('g:ia', $timestamp);

            $fileContents = file_get_contents($hylPath);
            if ($fileContents === false) {
                return [
                    'success' => false,
                    'httpCode' => 0,
                    'message' => "Não foi possível ler o arquivo .hyl: {$hylPath}"
                ];
            }

            $fileName = basename($hylPath);
            $boundary = "---------------------------" . substr(md5((string)rand(0, 32000)), 0, 14);

            // Monta o multipart/form-data na ordem exata do formulário original JSP
            $body = "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$fileName}\"\r\n";
            $body .= "Content-Type: application/octet-stream\r\n\r\n";
            $body .= $fileContents . "\r\n";

            $fields = [
                'competicao' => (string)$nomeCompeticao,
                'rodada' => (string)$rodada,
                'data' => $dataFormatada,
                'hora' => $horaFormatada,
                'upload' => 'Upload'
            ];

            foreach ($fields as $key => $val) {
                $body .= "--{$boundary}\r\n";
                $body .= "Content-Disposition: form-data; name=\"{$key}\"\r\n\r\n";
                $body .= "{$val}\r\n";
            }
            $body .= "--{$boundary}--\r\n";

            $headers = "Content-Type: multipart/form-data; boundary={$boundary}\r\n" .
                       "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n" .
                       "Content-Length: " . strlen($body) . "\r\n";

            $opts = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => $headers,
                    'content' => $body,
                    'timeout' => 20,
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ];

            $ctx = stream_context_create($opts);
            $result = @file_get_contents($url, false, $ctx);

            $httpCode = 200;
            if (isset($http_response_header) && is_array($http_response_header) && !empty($http_response_header)) {
                if (preg_match('#HTTP/[0-9\.]+\s+([0-9]+)#', $http_response_header[0], $matches)) {
                    $httpCode = (int)$matches[1];
                }
            }

            // Se o Live retornar a mensagem de erro no HTML de resposta
            if ($result !== false && stripos($result, 'É preciso selecionar pelo menos uma partida') !== false) {
                return [
                    'success' => false,
                    'httpCode' => $httpCode,
                    'message' => "CONFUSA Live rejeitou o arquivo: 'É preciso selecionar pelo menos uma partida.'"
                ];
            }

            $isSuccess = ($httpCode >= 200 && $httpCode < 400);
            return [
                'success' => $isSuccess,
                'httpCode' => $httpCode,
                'message' => $isSuccess ? "Partida enviada com sucesso ao Live." : "Live respondeu com código HTTP {$httpCode}"
            ];

        } catch (\Throwable $e) {
            error_log("ConfusaLiveUploader [EXCEÇÃO]: " . $e->getMessage());
            return [
                'success' => false,
                'httpCode' => 0,
                'message' => "Exceção no uploader: " . $e->getMessage()
            ];
        }
    }
}