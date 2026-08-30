<?php

/**
 * Configuração de Integração com o CONFUSA Live
 * 
 * Intermediário via Cloudflare Worker para contornar o bloqueio
 * da porta 8080 na hospedagem.
 */

if (!defined('CONFUSA_LIVE_UPLOAD_URL')) {
    define('CONFUSA_LIVE_UPLOAD_URL', 'https://confusalive-proxy.lhsaia.workers.dev/uploadMatch');
}