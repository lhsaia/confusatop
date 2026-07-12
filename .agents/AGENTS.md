# Regras do Projeto CONFUSA.top

## Atualização do Histórico de Novidades (updates.json)

Sempre que você realizar alterações de novas funcionalidades, melhorias de layout ou correções solicitadas pelo usuário neste ambiente:
1. Você **deve** atualizar o arquivo `/updates.json` na raiz do projeto.
2. Adicione uma nova entrada no topo do array JSON (mantendo a ordem da mais recente para a mais antiga) com o seguinte formato:
   ```json
   {
     "date": "DD/MM/AAAA",
     "title": "Título Curto e Direto da Melhoria",
     "description": "Uma explicação concisa sobre o que mudou e qual o benefício para o usuário do site."
   }
   ```
3. Certifique-se de que a sintaxe JSON permaneça perfeitamente válida.
