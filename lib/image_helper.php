<?php

/**
 * Processa e converte uma imagem enviada para o formato WebP, redimensionando proporcionalmente
 * se as dimensões ultrapassarem o valor máximo permitido ($maxDim).
 *
 * @param string $source_path Caminho do arquivo de origem (ex: $_FILES['campo']['tmp_name'])
 * @param string $target_path Caminho de destino completo no servidor (ex: $_SERVER['DOCUMENT_ROOT'] . "/images/escudos/1-time.webp")
 * @param int $maxDim Dimensão máxima em pixels (largura ou altura). Padrão 512.
 * @param int $quality Qualidade da compressão WebP (0-100). Padrão 85.
 * @return bool True em caso de sucesso, False em caso de falha.
 */
function processAndSaveWebPImage($source_path, $target_path, $maxDim = 512, $quality = 85) {
    if (empty($source_path) || !file_exists($source_path)) {
        return false;
    }

    @ini_set('memory_limit', '512M');

    $imageInfo = @getimagesize($source_path);
    if ($imageInfo === false) {
        return false;
    }

    list($width, $height, $type) = $imageInfo;
    if ($width <= 0 || $height <= 0) {
        return false;
    }

    // Calcula dimensões mantendo o aspect ratio
    if ($width > $maxDim || $height > $maxDim) {
        $ratio = $width / $height;
        if ($ratio > 1) {
            $new_width = (int) $maxDim;
            $new_height = (int) round($maxDim / $ratio);
        } else {
            $new_width = (int) round($maxDim * $ratio);
            $new_height = (int) $maxDim;
        }
    } else {
        $new_width = (int) $width;
        $new_height = (int) $height;
    }

    $src = null;

    if ($type == IMAGETYPE_PNG || $type == 'image/png') {
        $src = @imagecreatefrompng($source_path);
    } else if ($type == IMAGETYPE_WEBP || $type == 18 || $type == 'image/webp') {
        $src = @imagecreatefromwebp($source_path);
    } else if ($type == IMAGETYPE_JPEG || $type == 'image/jpeg' || $type == 'image/jpg') {
        $src = @imagecreatefromjpeg($source_path);
    } else if ($type == IMAGETYPE_GIF || $type == 'image/gif') {
        $src = @imagecreatefromgif($source_path);
    }

    if (!$src) {
        $file_data = @file_get_contents($source_path);
        if ($file_data) {
            $src = @imagecreatefromstring($file_data);
        }
    }

    if (!$src) {
        return false;
    }

    // Cria canvas destino preservando total transparência (Alpha channel)
    $dst = imagecreatetruecolor($new_width, $new_height);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $new_width, $new_height, $transparent);

    // Redimensiona mantendo a qualidade e canal alfa
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, (int)$width, (int)$height);
    @imagedestroy($src);

    // Garante que o diretório de destino existe
    $target_dir = dirname($target_path);
    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0755, true);
    }

    // Salva a imagem final em formato WebP
    $saved = @imagewebp($dst, $target_path, $quality);
    @imagedestroy($dst);

    return $saved;
}

/**
 * Função de retrocompatibilidade com códigos legados
 */
if (!function_exists('imageImporterWebP')) {
    function imageImporterWebP($file_name, $target_filename, $maxDim = 512, $quality = 85) {
        return processAndSaveWebPImage($file_name, $target_filename, $maxDim, $quality);
    }
}
