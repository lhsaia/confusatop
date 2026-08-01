<?php
class TransferNotifier {

    private string $webhookUrl;

    public function __construct(string $webhookUrl) {
        $this->webhookUrl = $webhookUrl;
    }

    public function notify(array $data): void {

        $cores = [
            'Empréstimo'      => hexdec('3B82F6'),
            'Permanente' => hexdec('22C55E'),
            'Sem custo'      => hexdec('A855F7')
        ];

        $payload = [
            'embeds' => [[
                'author' => [
                    'name' => $data['nome'],
                    'icon_url' => $data['bandeira_png']
                ],
                'title' => 'Transferência Confirmada',
                'color' => $cores[$data['tipo_transferencia']] ?? hexdec('64748B'),
                'thumbnail' => [
                    'url' => $data['foto']
                ],
                'fields' => [
                    [
                        'name' => '⬅️ De',
                        'value' => "**{$data['origem']}**\n[ ]({$data['origem_escudo_png']})",
                        'inline' => true
                    ],
                    [
                        'name' => '➡️ Para',
                        'value' => "**{$data['destino']}**\n[ ]({$data['destino_escudo_png']})",
                        'inline' => true
                    ],
                    [
                        'name' => '💰 Valor',
                        'value' => $data['valor'],
                        'inline' => true
                    ],
                    [
                        'name' => '🔁 Tipo',
                        'value' => $data['tipo_transferencia'],
                        'inline' => true
                    ],
                    [
                        'name' => '📅 Data',
                        'value' => $data['data'],
                        'inline' => false
                    ]
                ],
                'timestamp' => date('c')
            ]]
        ];

        $ch = curl_init($this->webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_RETURNTRANSFER => true
        ]);

        curl_exec($ch);
        curl_close($ch);
    }
}

?>
