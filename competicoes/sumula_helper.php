<?php
/**
 * Helper para localização e coleta de arquivos de súmulas (.hyl e .hyj)
 * Utilizado para downloads individuais e em pacote (ZIP) pelos donos de competições e administradores.
 */

if (!function_exists('sanitizarNomePastaCompeticao')) {
    function sanitizarNomePastaCompeticao($nomeComposto) {
        $nome = html_entity_decode((string)$nomeComposto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $nome = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $nome);
        $nome = preg_replace('/\s+/', ' ', $nome);
        return trim($nome);
    }
}

if (!function_exists('encontrarArquivoSumula')) {
    /**
     * Localiza o arquivo de súmula de uma partida (.hyl ou .hyj)
     *
     * @param string $nomeComposto Nome composto da competição (ex: "2026 - Taça Nacional")
     * @param string $path Identificador do arquivo da partida (ex: "FLAxFLU - 15-10-2026")
     * @param string $extensao 'hyl' ou 'hyj'
     * @param string $docRoot Caminho raiz do documento (opcional)
     * @return string|null Caminho completo do arquivo ou null se não encontrado
     */
    function encontrarArquivoSumula($nomeComposto, $path, $extensao = 'hyl', $docRoot = '') {
        $ext = strtolower(trim($extensao, '.'));
        if (!in_array($ext, ['hyl', 'hyj', 'png', 'pdf'])) {
            return null;
        }

        if (empty($docRoot)) {
            $docRoot = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
                ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') 
                : dirname(__DIR__);
        }
        $docRoot = str_replace('\\', '/', $docRoot);

        $cleanPath = preg_replace('/\.(hyl|hyj|png|pdf)$/i', '', trim($path));
        if (empty($cleanPath)) {
            return null;
        }

        $searchBases = [
            $docRoot . '/competicoes/hexacolor/Partidas',
            $docRoot . '/full hexa suite/Hexacolor YMT/Partidas'
        ];

        // 1. Tentar diretamente na pasta da competição se o nome composto foi fornecido
        if (!empty($nomeComposto)) {
            $nomesCandidatos = array_unique([$nomeComposto, sanitizarNomePastaCompeticao($nomeComposto)]);
            foreach ($searchBases as $sb) {
                foreach ($nomesCandidatos as $nc) {
                    $compDir = $sb . '/' . $nc;
                    if (is_dir($compDir)) {
                        $matches = glob($compDir . '/*/' . $cleanPath . '.' . $ext);
                        if (!empty($matches) && file_exists($matches[0])) {
                            return str_replace('\\', '/', $matches[0]);
                        }
                        if (file_exists($compDir . '/' . $cleanPath . '.' . $ext)) {
                            return str_replace('\\', '/', $compDir . '/' . $cleanPath . '.' . $ext);
                        }
                    }
                }
            }
        }

        // 2. Tentar glob em subpastas de rodadas em todas as competições
        foreach ($searchBases as $sb) {
            if (!is_dir($sb)) continue;
            $matches = glob($sb . '/*/*/' . $cleanPath . '.' . $ext);
            if (!empty($matches) && file_exists($matches[0])) {
                return str_replace('\\', '/', $matches[0]);
            }
        }

        // 3. Fallback com busca recursiva caso haja variações de diretório
        $targetFile = strtolower($cleanPath . '.' . $ext);
        foreach ($searchBases as $sb) {
            if (!is_dir($sb)) continue;
            try {
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sb, FilesystemIterator::SKIP_DOTS));
                foreach ($it as $file) {
                    if ($file->isFile() && strtolower($file->getFilename()) === $targetFile) {
                        return str_replace('\\', '/', $file->getPathname());
                    }
                }
            } catch (\Throwable $e) {}
        }

        return null;
    }
}

if (!function_exists('coletarArquivosSumulasCompeticao')) {
    /**
     * Coleta todos os arquivos (.hyl, .hyj) de uma competição para compactação
     *
     * @param string $nomeComposto Nome composto da competição (ex: "2026 - Taça Nacional")
     * @param array $jogosPaths Lista opcional de caminhos de jogos registrados no banco
     * @param string $tipo 'hyl', 'hyj' ou 'todos'
     * @param string $docRoot Caminho raiz do documento (opcional)
     * @return array Mapa no formato [nome_relativo_no_zip => caminho_absoluto_no_disco]
     */
    function coletarArquivosSumulasCompeticao($nomeComposto, $jogosPaths = [], $tipo = 'todos', $docRoot = '') {
        $tipo = strtolower(trim($tipo));
        $extensoes = ($tipo === 'hyl') ? ['hyl'] : (($tipo === 'hyj') ? ['hyj'] : ['hyl', 'hyj']);

        if (empty($docRoot)) {
            $docRoot = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
                ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') 
                : dirname(__DIR__);
        }
        $docRoot = str_replace('\\', '/', $docRoot);

        $arquivosEncontrados = [];

        $searchBases = [
            $docRoot . '/competicoes/hexacolor/Partidas',
            $docRoot . '/full hexa suite/Hexacolor YMT/Partidas'
        ];

        // 1. Varrer a pasta da própria competição se existir
        if (!empty($nomeComposto)) {
            $nomesCandidatos = array_unique([$nomeComposto, sanitizarNomePastaCompeticao($nomeComposto)]);
            foreach ($searchBases as $sb) {
                foreach ($nomesCandidatos as $nc) {
                    $compDir = $sb . '/' . $nc;
                    if (is_dir($compDir)) {
                        try {
                            $it = new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($compDir, FilesystemIterator::SKIP_DOTS)
                            );
                            foreach ($it as $file) {
                                if (!$file->isFile()) continue;
                                $ext = strtolower($file->getExtension());
                                if (in_array($ext, $extensoes)) {
                                    $realPath = str_replace('\\', '/', $file->getPathname());
                                    $relPath = ltrim(str_replace(str_replace('\\', '/', $compDir), '', $realPath), '/');
                                    $arquivosEncontrados[$relPath] = $realPath;
                                }
                            }
                        } catch (\Throwable $e) {}
                    }
                }
            }
        }

        // 2. Para paths específicos de partidas do banco de dados, garantir que sejam adicionados
        if (!empty($jogosPaths) && is_array($jogosPaths)) {
            foreach ($jogosPaths as $jPath) {
                $clean = preg_replace('/\.(hyl|hyj|png|pdf)$/i', '', trim($jPath));
                if (empty($clean)) continue;

                foreach ($extensoes as $ext) {
                    $already = false;
                    foreach ($arquivosEncontrados as $rel => $real) {
                        if (basename($real) === ($clean . '.' . $ext)) {
                            $already = true;
                            break;
                        }
                    }
                    if ($already) continue;

                    $found = encontrarArquivoSumula($nomeComposto, $clean, $ext, $docRoot);
                    if ($found && file_exists($found)) {
                        $relName = basename($found);
                        $parentDir = basename(dirname($found));
                        if (preg_match('/rodada|fase|grupo/i', $parentDir)) {
                            $relName = $parentDir . '/' . $relName;
                        }
                        $arquivosEncontrados[$relName] = $found;
                    }
                }
            }
        }

        return $arquivosEncontrados;
    }
}

if (!function_exists('apagarArquivosPartidaSimulada')) {
    /**
     * Remove fisicamente os arquivos gerados (.hyl, .hyj, .png, .pdf) de uma partida simulada.
     * SEGURANÇA E ISOLAMENTO: Apenas remove arquivos localizados estritamente dentro da pasta
     * da respectiva competição ($nomeComposto).
     *
     * @param string $nomeComposto Nome composto da competição (ex: "2026 - Taça Nacional")
     * @param string $path Identificador/path da partida (ex: "FLAxFLU - 15-10-2026")
     * @param string $docRoot Diretório raiz (opcional)
     * @return int Quantidade de arquivos removidos
     */
    function apagarArquivosPartidaSimulada($nomeComposto, $path, $docRoot = '') {
        $nomeComposto = sanitizarNomePastaCompeticao($nomeComposto ?? '');
        $clean = preg_replace('/\.(hyl|hyj|png|pdf)$/i', '', trim($path ?? ''));
        
        // Bloqueio de segurança: exige obrigatoriamente tanto a pasta da competição quanto o nome da partida
        if (empty($nomeComposto) || empty($clean)) {
            return 0;
        }

        // Previne caracteres de subida de diretório (Directory Traversal)
        if (strpos($nomeComposto, '..') !== false || strpos($clean, '..') !== false) {
            return 0;
        }

        if (empty($docRoot)) {
            $docRoot = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
                ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') 
                : dirname(__DIR__);
        }
        $docRoot = str_replace('\\', '/', $docRoot);

        $searchBases = [
            $docRoot . '/competicoes/hexacolor/Partidas',
            $docRoot . '/full hexa suite/Hexacolor YMT/Partidas'
        ];

        $extensoes = ['hyl', 'hyj', 'png', 'pdf'];
        $removidos = 0;

        foreach ($searchBases as $sb) {
            if (!is_dir($sb)) continue;

            $compDir = $sb . '/' . $nomeComposto;
            if (!is_dir($compDir)) continue;

            // Busca arquivos do jogo estritamente dentro da pasta desta competição (subpastas de rodadas ou raiz da pasta)
            foreach ($extensoes as $ext) {
                $matches = glob($compDir . '/*/' . $clean . '.' . $ext);
                if (!empty($matches)) {
                    foreach ($matches as $m) {
                        if (is_file($m) && @unlink($m)) {
                            $removidos++;
                        }
                    }
                }
                $rootMatch = $compDir . '/' . $clean . '.' . $ext;
                if (is_file($rootMatch)) {
                    if (@unlink($rootMatch)) {
                        $removidos++;
                    }
                }
            }
        }

        return $removidos;
    }
}

if (!function_exists('apagarDiretorioCompeticaoPartidas')) {
    /**
     * Remove recursivamente a pasta de partidas de uma competição inteira.
     * SEGURANÇA E ISOLAMENTO: Valida rigorosamente que o nome da pasta pertence estritamente
     * a uma subpasta sob 'Partidas/', evitando apagar qualquer diretório indevido.
     *
     * @param string $nomeComposto Nome composto da competição (ex: "2026 - Taça Nacional")
     * @param string $docRoot Diretório raiz (opcional)
     * @return bool
     */
    function apagarDiretorioCompeticaoPartidas($nomeComposto, $docRoot = '') {
        $nomeComposto = sanitizarNomePastaCompeticao($nomeComposto ?? '');
        if (empty($nomeComposto)) {
            return false;
        }

        // Prevenções de segurança estritas:
        // 1. Proibido tentar apagar diretórios raiz, '.', '..', caminhos relativos
        if ($nomeComposto === '.' || $nomeComposto === '..' || $nomeComposto === '/' || $nomeComposto === '\\') {
            return false;
        }
        if (strpos($nomeComposto, '..') !== false || strpos($nomeComposto, '/') !== false || strpos($nomeComposto, '\\') !== false) {
            return false;
        }

        if (empty($docRoot)) {
            $docRoot = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
                ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') 
                : dirname(__DIR__);
        }
        $docRoot = str_replace('\\', '/', $docRoot);

        $searchBases = [
            $docRoot . '/competicoes/hexacolor/Partidas',
            $docRoot . '/full hexa suite/Hexacolor YMT/Partidas'
        ];

        $delRdir = function($dir) use (&$delRdir) {
            if (!is_dir($dir)) return;
            $items = @scandir($dir);
            if (!$items) return;
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $path = $dir . '/' . $item;
                if (is_dir($path)) {
                    $delRdir($path);
                } else {
                    @unlink($path);
                }
            }
            @rmdir($dir);
        };

        foreach ($searchBases as $sb) {
            $compDir = $sb . '/' . $nomeComposto;
            // Confirma que a pasta existe e é exatamente um diretório filho sob Partidas
            if (is_dir($compDir) && realpath($compDir) !== false) {
                $realBase = realpath($sb);
                $realComp = realpath($compDir);
                // Validação de fronteira de diretório (Path Containment)
                if ($realBase !== false && strpos($realComp, $realBase) === 0 && strlen($realComp) > strlen($realBase)) {
                    $delRdir($compDir);
                }
            }
        }

        return true;
    }
}
?>
