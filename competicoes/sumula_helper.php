<?php
/**
 * Helper para localização e coleta de arquivos de súmulas (.hyl e .hyj)
 * Utilizado para downloads individuais e em pacote (ZIP) pelos donos de competições e administradores.
 */

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
        if (!in_array($ext, ['hyl', 'hyj'])) {
            return null;
        }

        if (empty($docRoot)) {
            $docRoot = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
                ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') 
                : dirname(__DIR__);
        }
        $docRoot = str_replace('\\', '/', $docRoot);

        $cleanPath = preg_replace('/\.(hyl|hyj|png)$/i', '', trim($path));
        if (empty($cleanPath)) {
            return null;
        }

        $searchBases = [
            $docRoot . '/competicoes/hexacolor/Partidas',
            $docRoot . '/full hexa suite/Hexacolor YMT/Partidas'
        ];

        // 1. Tentar diretamente na pasta da competição se o nome composto foi fornecido
        if (!empty($nomeComposto)) {
            foreach ($searchBases as $sb) {
                $compDir = $sb . '/' . $nomeComposto;
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
     * Coleta todos os arquivos .hyl e/ou .hyj de uma competição
     *
     * @param string $nomeComposto Nome composto da competição (ex: "2026 - Taça Nacional")
     * @param array $jogosPaths Array com os paths de partidas simuladas
     * @param string $tipo 'todos', 'hyl' ou 'hyj'
     * @param string $docRoot Caminho raiz do documento (opcional)
     * @return array Array associativo [ 'caminho_relativo' => 'caminho_absoluto_real' ]
     */
    function coletarArquivosSumulasCompeticao($nomeComposto, $jogosPaths = [], $tipo = 'todos', $docRoot = '') {
        if (empty($docRoot)) {
            $docRoot = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '' 
                ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') 
                : dirname(__DIR__);
        }
        $docRoot = str_replace('\\', '/', $docRoot);

        $extensoes = [];
        if ($tipo === 'hyl') {
            $extensoes = ['hyl'];
        } elseif ($tipo === 'hyj') {
            $extensoes = ['hyj'];
        } else {
            $extensoes = ['hyl', 'hyj'];
        }

        $arquivosEncontrados = [];
        $searchBases = [
            $docRoot . '/competicoes/hexacolor/Partidas',
            $docRoot . '/full hexa suite/Hexacolor YMT/Partidas'
        ];

        // 1. Varrer a pasta da própria competição se existir
        if (!empty($nomeComposto)) {
            foreach ($searchBases as $sb) {
                $compDir = $sb . '/' . $nomeComposto;
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

        // 2. Para paths específicos de partidas do banco de dados, garantir que sejam adicionados
        if (!empty($jogosPaths) && is_array($jogosPaths)) {
            foreach ($jogosPaths as $jPath) {
                $clean = preg_replace('/\.(hyl|hyj|png)$/i', '', trim($jPath));
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
