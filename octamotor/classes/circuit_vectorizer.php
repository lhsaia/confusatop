<?php

class CircuitVectorizer {
    private static $cacheDir = null;

    private static function getCacheDir() {
        if (self::$cacheDir === null) {
            $root = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 2);
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
    public static function getTrackSvgData($imageFileName) {
        if (empty($imageFileName)) return null;

        $cacheFile = self::getCacheDir() . md5($imageFileName) . '.json';
        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached) && !empty($cached['svg_path'])) {
                return $cached;
            }
        }

        $root = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 2);
        $imagePath = $root . '/octamotor/images/track/' . $imageFileName;

        $svgData = self::traceCircuit($imagePath);
        if ($svgData) {
            @file_put_contents($cacheFile, json_encode($svgData));
        }

        return $svgData;
    }

    /**
     * Limpa o cache de um traçado
     */
    public static function clearCache($imageFileName) {
        $cacheFile = self::getCacheDir() . md5($imageFileName) . '.json';
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

        // Criar grade binária
        $grid = [];
        $firstX = -1; $firstY = -1;

        for ($y = 0; $y < $h; $y++) {
            $grid[$y] = [];
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($im, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F; // 0 = opaco, 127 = transparente
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                $brightness = ($r + $g + $b) / 3;

                // Detectar se é linha da pista (opaco e brilhante, ou linha escura em fundo branco)
                $isLine = ($alpha < 90 && $brightness > 50);
                $grid[$y][$x] = $isLine ? 1 : 0;

                if ($isLine && $firstX === -1) {
                    $firstX = $x;
                    $firstY = $y;
                }
            }
        }
        imagedestroy($im);

        if ($firstX === -1) return null;

        // Algoritmo de Moore-Neighbor Tracing para contorno fechado
        $dirs = [
            [0, -1], [1, -1], [1, 0], [1, 1],
            [0, 1], [-1, 1], [-1, 0], [-1, -1]
        ];

        $contour = [];
        $startX = $firstX;
        $startY = $firstY;
        $currX = $startX;
        $currY = $startY;
        $backtrackDir = 6; // Oeste

        $maxSteps = $w * $h;
        $steps = 0;

        $contour[] = ['x' => $startX, 'y' => $startY];

        while ($steps < $maxSteps) {
            $steps++;
            $found = false;
            for ($i = 0; $i < 8; $i++) {
                $dirIdx = ($backtrackDir + 1 + $i) % 8;
                $nx = $currX + $dirs[$dirIdx][0];
                $ny = $currY + $dirs[$dirIdx][1];

                if ($nx >= 0 && $nx < $w && $ny >= 0 && $ny < $h && isset($grid[$ny][$nx]) && $grid[$ny][$nx] === 1) {
                    $currX = $nx;
                    $currY = $ny;
                    $contour[] = ['x' => $currX, 'y' => $currY];
                    $backtrackDir = ($dirIdx + 4) % 8;
                    $found = true;
                    break;
                }
            }

            if (!$found) break;

            if ($currX === $startX && $currY === $startY && count($contour) > 20) {
                break;
            }
        }

        if (count($contour) < 20) return null;

        // Amostragem proporcional para ~60 pontos de controle suaves
        $targetCount = 60;
        $step = count($contour) / $targetCount;
        $sampled = [];
        for ($i = 0; $i < $targetCount; $i++) {
            $idx = (int)floor($i * $step);
            $sampled[] = $contour[$idx];
        }

        // Gera o path SVG usando splines Catmull-Rom para Bezier Cúbico fechado
        $svgPath = self::pointsToSmoothSvg($sampled);

        return [
            'svg_path' => $svgPath,
            'viewBox' => "0 0 {$w} {$h}",
            'width' => $w,
            'height' => $h,
            'points_count' => count($sampled)
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
