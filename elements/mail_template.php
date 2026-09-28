<?php
/**
 * Helper para renderização de e-mails institucionais padronizados do CONFUSA.top
 */

if (!function_exists('renderConfusaEmail')) {
    /**
     * @param array $params [
     *   'title' => string,         // Título principal do card (ex: "Proposta de Transferência", "Olá, Nome!")
     *   'subtitle' => string|null, // Texto introdutório
     *   'badge' => [ 'text' => string, 'bg' => string, 'color' => string ] | null,
     *   'content_html' => string,  // Conteúdo principal (tabelas, caixas de informação, detalhes)
     *   'btn' => [ 'url' => string, 'text' => string ] | null, // Botão de ação primário
     *   'footer_note' => string|null // Nota de rodapé (default: "Esta é uma mensagem automática...")
     * ]
     * @return string HTML completo pronto para envio
     */
    function renderConfusaEmail(array $params): string {
        $title = $params['title'] ?? 'CONFUSA.top';
        $subtitle = $params['subtitle'] ?? '';
        $badge = $params['badge'] ?? null;
        $contentHtml = $params['content_html'] ?? '';
        $btn = $params['btn'] ?? null;
        $footerNote = $params['footer_note'] ?? 'Esta é uma mensagem automática enviada pelo sistema CONFUSA.top.';

        $badgeHtml = '';
        if (!empty($badge) && !empty($badge['text'])) {
            $badgeBg = $badge['bg'] ?? '#e0f2fe';
            $badgeColor = $badge['color'] ?? '#0369a1';
            $badgeText = htmlspecialchars($badge['text'], ENT_QUOTES, 'UTF-8');
            $badgeHtml = "
            <div style='margin-bottom: 20px;'>
                <span style='display: inline-block; padding: 6px 14px; background: {$badgeBg}; color: {$badgeColor}; font-weight: bold; border-radius: 6px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;'>
                    {$badgeText}
                </span>
            </div>";
        }

        $btnHtml = '';
        if (!empty($btn) && !empty($btn['url']) && !empty($btn['text'])) {
            $btnUrl = htmlspecialchars($btn['url'], ENT_QUOTES, 'UTF-8');
            $btnText = htmlspecialchars($btn['text'], ENT_QUOTES, 'UTF-8');
            $btnHtml = "
            <div style='text-align: center; margin-top: 30px; margin-bottom: 10px;'>
                <a href='{$btnUrl}' style='background: #0284c7; color: #ffffff; text-decoration: none; padding: 13px 28px; font-weight: bold; border-radius: 8px; font-size: 14px; display: inline-block; box-shadow: 0 2px 4px rgba(2, 132, 199, 0.25);'>
                    {$btnText}
                </a>
            </div>";
        }

        $subtitleHtml = '';
        if (!empty($subtitle)) {
            $subtitleHtml = "<p style='color: #475569; font-size: 15px; line-height: 1.6; margin-top: 0; margin-bottom: 20px;'>{$subtitle}</p>";
        }

        return "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$title}</title>
        </head>
        <body style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 30px 15px; color: #0f172a;'>
            <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                <tr>
                    <td align='center'>
                        <table width='100%' border='0' cellspacing='0' cellpadding='0' style='max-width: 600px; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;'>
                            <!-- Cabeçalho Institucional -->
                            <tr>
                                <td style='background: #1A1469; padding: 24px; text-align: center;'>
                                    <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 0.5px;'>CONFUSA<span style='color: #38bdf8;'>.top</span></h1>
                                </td>
                            </tr>
                            
                            <!-- Corpo do Card -->
                            <tr>
                                <td style='padding: 32px 28px;'>
                                    {$badgeHtml}
                                    <h2 style='color: #0f172a; margin-top: 0; margin-bottom: 16px; font-size: 19px; font-weight: 700;'>{$title}</h2>
                                    {$subtitleHtml}
                                    
                                    {$contentHtml}

                                    {$btnHtml}
                                </td>
                            </tr>
                            
                            <!-- Rodapé -->
                            <tr>
                                <td style='background: #f8fafc; padding: 18px 24px; text-align: center; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 12px; line-height: 1.5;'>
                                    {$footerNote}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }
}
