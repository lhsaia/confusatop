<?php

class CircuitVectorizer {
    private static $cacheDir = null;

    private static function getCacheDir() {
        if (self::$cacheDir === null) {
            $root = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__, 2);
            self::$cacheDir = $root . '/octamotor/images/track/cache/';
            if (!is_dir(self::$cacheDir)) {
                @mkdir(self::$cacheDir, 0777, true);
            }
        }
        return self::$cacheDir;
    }

    /**
     * Retorna os dados do traçado SVG (com cache automático).
     */
    public static function getTrackSvgData($imageFileName, $trackName = '') {
        if (empty($imageFileName) && empty($trackName)) return null;

        $actualFile = self::resolveActualTrackFile($imageFileName, $trackName);
        if (!$actualFile) return null;

        $cacheFile = self::getCacheDir() . md5($actualFile) . '.json';
        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached) && !empty($cached['svg_path'])) {
                return $cached;
            }
        }

        $root = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__, 2);
        $imagePath = $root . '/octamotor/images/track/' . $actualFile;

        $svgData = self::traceCircuit($imagePath);
        if ($svgData) {
            @file_put_contents($cacheFile, json_encode($svgData));
        }

        return $svgData;
    }

    /**
     * Resolve o arquivo físico real da pista mesmo se houver divergência de sufixo numérico aleatório
     */
    public static function resolveActualTrackFile($requestedFileName, $trackName = '') {
        $root = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__, 2);
        $trackDir = $root . '/octamotor/images/track/';

        if (!empty($requestedFileName) && file_exists($trackDir . $requestedFileName)) {
            return $requestedFileName;
        }

        if (!is_dir($trackDir)) return null;
        $allFiles = scandir($trackDir);
        $validFiles = [];
        foreach ($allFiles as $f) {
            if (preg_match('/\.(png|jpg|webp)$/i', $f)) {
                $validFiles[] = $f;
            }
        }

        $candidates = array_filter([$requestedFileName, $trackName]);
        foreach ($candidates as $candidate) {
            $clean = preg_replace('/^\d+-/', '', $candidate);
            $clean = preg_replace('/\d+\.(png|jpg|webp)$/i', '', $clean);
            $clean = preg_replace('/(International|Circuit|Autodr[oó]me|Aut[oó]dromo|Speedway|Raceway|National|Street|Racetrack)/iu', '', $clean);
            $clean = trim($clean);
            if (mb_strlen($clean) >= 3) {
                foreach ($validFiles as $f) {
                    if (stripos($f, $clean) !== false) {
                        return $f;
                    }
                }
            }
        }

        foreach ($candidates as $candidate) {
            $words = preg_split('/[\s\-_]+/', $candidate);
            foreach ($words as $w) {
                $w = trim($w);
                if (mb_strlen($w) >= 4 && !preg_match('/(International|Circuit|Autodrome|Autodromo|Speedway|Raceway|National|Street|Racetrack)/i', $w)) {
                    foreach ($validFiles as $f) {
                        if (stripos($f, $w) !== false) {
                            return $f;
                        }
                    }
                }
            }
        }

        return !empty($requestedFileName) ? $requestedFileName : null;
    }

    /**
     * Limpa o cache de um traçado
     */
    public static function clearCache($imageFileName) {
        $actualFile = self::resolveActualTrackFile($imageFileName);
        $cacheFile = self::getCacheDir() . md5($actualFile ?: $imageFileName) . '.json';
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }

    /**
     * Extrai o contorno vetorial contínuo e suave a partir de um PNG/JPG/WebP de circuito
     */
    public static function traceCircuit($filePath) {
        if (!file_exists($filePath)) return null;

        $content = @file_get_contents($filePath);
        if (!$content) return null;

        $im = @imagecreatefromstring($content);
        if (!$im) return null;

        $w = imagesx($im);
        $h = imagesy($im);
        if ($w <= 0 || $h <= 0) {
            imagedestroy($im);
            return null;
        }

        // Amostragem robusta de bordas para obter a cor mediana de fundo (branco, preto, transparente ou colorido)
        $borderR = []; $borderG = []; $borderB = []; $borderA = [];
        for ($x = 0; $x < $w; $x += max(1, (int)($w / 50))) {
            $c1 = imagecolorsforindex($im, imagecolorat($im, $x, 0));
            $c2 = imagecolorsforindex($im, imagecolorat($im, $x, $h - 1));
            $borderR[] = $c1['red']; $borderR[] = $c2['red'];
            $borderG[] = $c1['green']; $borderG[] = $c2['green'];
            $borderB[] = $c1['blue']; $borderB[] = $c2['blue'];
            $borderA[] = $c1['alpha']; $borderA[] = $c2['alpha'];
        }
        for ($y = 0; $y < $h; $y += max(1, (int)($h / 50))) {
            $c1 = imagecolorsforindex($im, imagecolorat($im, 0, $y));
            $c2 = imagecolorsforindex($im, imagecolorat($im, $w - 1, $y));
            $borderR[] = $c1['red']; $borderR[] = $c2['red'];
            $borderG[] = $c1['green']; $borderG[] = $c2['green'];
            $borderB[] = $c1['blue']; $borderB[] = $c2['blue'];
            $borderA[] = $c1['alpha']; $borderA[] = $c2['alpha'];
        }

        sort($borderR); sort($borderG); sort($borderB); sort($borderA);
        $mid = (int)(count($borderR) / 2);
        $bgR = $borderR[$mid];
        $bgG = $borderG[$mid];
        $bgB = $borderB[$mid];
        $bgA = $borderA[$mid];

        // Adiciona padding ao redor da imagem para evitar que pistas que tocam a borda do canvas se fundam com os limites da imagem
        $pad = 10;
        $gridW = $w + ($pad * 2);
        $gridH = $h + ($pad * 2);

        $grid = [];
        for ($y = 0; $y < $gridH; $y++) {
            $grid[$y] = array_fill(0, $gridW, 0);
        }

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($im, $x, $y);
                $c = imagecolorsforindex($im, $rgba);
                $r = $c['red']; $g = $c['green']; $b = $c['blue']; $a = $c['alpha'];

                $colorDiff = sqrt(pow($r - $bgR, 2) + pow($g - $bgG, 2) + pow($b - $bgB, 2));
                $alphaDiff = abs($a - $bgA);

                $isTrack = ($colorDiff > 40 || $alphaDiff > 40);

                $grid[$y + $pad][$x + $pad] = $isTrack ? 1 : 0;
            }
        }
        // Detecta pixels vermelhos da linha de largada/chegada
        $redX = []; $redY = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($im, $x, $y);
                $c = imagecolorsforindex($im, $rgba);
                if ($c['red'] > 175 && $c['green'] < 70 && $c['blue'] < 70 && $c['alpha'] < 50) {
                    $redX[] = $x;
                    $redY[] = $y;
                }
            }
        }
        $finishLineCenter = null;
        if (!empty($redX)) {
            $finishLineCenter = [
                'x' => array_sum($redX) / count($redX),
                'y' => array_sum($redY) / count($redY)
            ];
        }

        imagedestroy($im);

        // Encontra todos os contornos fechados e seleciona o circuito principal (maior área cercada)
        $visited = [];
        $bestContour = [];
        $bestArea = 0;
        $dirs = [
            [-1, -1], [0, -1], [1, -1],
            [1, 0],            [1, 1],
            [0, 1],   [-1, 1], [-1, 0]
        ];

        for ($y = 0; $y < $gridH; $y++) {
            for ($x = 0; $x < $gridW; $x++) {
                if ($grid[$y][$x] === 1 && empty($visited["$x,$y"])) {
                    $contour = [];
                    $currX = $x;
                    $currY = $y;
                    $contour[] = ['x' => $currX - $pad, 'y' => $currY - $pad];
                    $visited["$currX,$currY"] = true;

                    $backtrackDir = 0;
                    $maxSteps = $gridW * $gridH;
                    $steps = 0;

                    do {
                        $foundNext = false;
                        for ($i = 0; $i < 8; $i++) {
                            $checkDir = ($backtrackDir + $i) % 8;
                            $nx = $currX + $dirs[$checkDir][0];
                            $ny = $currY + $dirs[$checkDir][1];

                            if ($nx >= 0 && $nx < $gridW && $ny >= 0 && $ny < $gridH && !empty($grid[$ny][$nx])) {
                                $currX = $nx;
                                $currY = $ny;
                                $contour[] = ['x' => $currX - $pad, 'y' => $currY - $pad];
                                $visited["$currX,$currY"] = true;
                                $backtrackDir = ($checkDir + 5) % 8;
                                $foundNext = true;
                                break;
                            }
                        }
                        if (!$foundNext) break;
                        $steps++;
                        if ($currX === $x && $currY === $y && $steps > 15) {
                            break;
                        }
                    } while ($steps < $maxSteps);

                    if (count($contour) >= 20) {
                        // Calcula a área do polígono do contorno
                        $area = 0;
                        $cntCount = count($contour);
                        for ($pi = 0; $pi < $cntCount; $pi++) {
                            $pj = ($pi + 1) % $cntCount;
                            $area += $contour[$pi]['x'] * $contour[$pj]['y'];
                            $area -= $contour[$pj]['x'] * $contour[$pi]['y'];
                        }
                        $area = abs($area) / 2;

                        if ($area > $bestArea) {
                            $bestArea = $area;
                            $bestContour = $contour;
                        }
                    }
                }
            }
        }

        if (empty($bestContour) || count($bestContour) < 15) return null;

        // Se encontrou a linha de largada/chegada vermelha, rotaciona o contorno para começar exatamente nela
        if ($finishLineCenter !== null) {
            $closestIdx = 0;
            $minDist = PHP_FLOAT_MAX;
            foreach ($bestContour as $idx => $pt) {
                $d = pow($pt['x'] - $finishLineCenter['x'], 2) + pow($pt['y'] - $finishLineCenter['y'], 2);
                if ($d < $minDist) {
                    $minDist = $d;
                    $closestIdx = $idx;
                }
            }
            if ($closestIdx > 0) {
                $bestContour = array_merge(
                    array_slice($bestContour, $closestIdx),
                    array_slice($bestContour, 0, $closestIdx)
                );
            }
        }

        // Simplificação / sub-amostragem proporcional para curva suave
        $totalPoints = count($bestContour);
        $targetPoints = min(120, max(35, (int)($totalPoints / 12)));
        $stepSize = max(1, (int)($totalPoints / $targetPoints));

        $simplified = [];
        for ($i = 0; $i < $totalPoints; $i += $stepSize) {
            $simplified[] = $bestContour[$i];
        }
        if (count($simplified) < 8) return null;

        // Gera o path SVG usando splines Catmull-Rom para Bezier Cúbico fechado
        $svgPath = self::pointsToSmoothSvg($simplified);

        return [
            'svg_path' => $svgPath,
            'viewBox' => "0 0 {$w} {$h}",
            'width' => $w,
            'height' => $h,
            'points_count' => count($simplified)
        ];
    }

    private static function pointsToSmoothSvg($points) {
        $n = count($points);
        if ($n < 3) return '';

        $path = "M " . round($points[0]['x'], 1) . " " . round($points[0]['y'], 1);

        for ($i = 0; $i < $n; $i++) {
            $p0 = $points[($i - 1 + $n) % $n];
            $p1 = $points[$i];
            $p2 = $points[($i + 1) % $n];
            $p3 = $points[($i + 2) % $n];

            // Tensão = 0.5 (Catmull-Rom padrão)
            $cp1x = $p1['x'] + ($p2['x'] - $p0['x']) / 6;
            $cp1y = $p1['y'] + ($p2['y'] - $p0['y']) / 6;

            $cp2x = $p2['x'] - ($p3['x'] - $p1['x']) / 6;
            $cp2y = $p2['y'] - ($p3['y'] - $p1['y']) / 6;

            $path .= " C " . round($cp1x, 1) . " " . round($cp1y, 1) . ", "
                           . round($cp2x, 1) . " " . round($cp2y, 1) . ", "
                           . round($p2['x'], 1) . " " . round($p2['y'], 1);
        }

        $path .= " Z";
        return $path;
    }
}
