# CONFUSA.top

**CONFUSA.top** is a comprehensive web portal designed for the management of the **Confederação de Futebol Solitário Associado (CONFUSA)**. It serves as a central hub for managing football teams, players, transfers, and rankings, designed to integrate seamlessly with the **Hexacolor YMT** desktop suite.

## 📖 About
Originally developed to provide a centralized ranking system, the project evolved into a full-featured management system. It allows users to:
- Maintain a secure, centralized database of their teams.
- Manage squads with unlimited players (titulars, reserves, substitutes).
- Automate player aging and retirement.
- Negotiate transfers and loans in a global market.
- Generate teams and players using the integrated **Hexagen** algorithm.

## ✨ Principais Funcionalidades / Key Features

### ⚽ Gestão de Futebol & Ligas (Football Management)
- **Ligas e Clubes**: Criação e gestão de ligas (masculinas e femininas), clubes, seleções nacionais e federações (FEASCO, FEMIFUS, COMPACTA).
- **Elencos e Táticas**: Edição detalhada de atributos, posições táticas com campinho visual, histórico de transferências e geração de fatias demográficas por país.
- **Mercado da Bola**: Sistema dinâmico de propostas, empréstimos, janelas de transferências e busca avançada com filtros por estilo e mentalidade.
- **Time Traveler**: Reconstituição histórica de elencos em qualquer data do passado a partir do histórico de transferências, com estatísticas da época e exportação direta em `.ymt`.
- **Competições & Simulador**: Suporte a torneios nacionais e internacionais, chaveamento automático de mata-mata, fase de grupos, sorteios intermediários com slots dinâmicos e simulação integrada via Cron.
- **Central de Desfalques & DM**: Acompanhamento centralizado de suspensões automáticas por cartões e lesões com contagem regressiva de recuperação médica.
- **Súmula & Integração CONFUSA Live**: Envio automático de dados de partidas em tempo real para o painel ao vivo e geração de arquivos `.hyl`.
- **Árbitros & Exportação**: Gerenciamento e importação/exportação de trios de arbitragem (`.tda`) e compatibilidade com a suíte **Hexacolor YMT** / banco SQLite (`.db3`).

### 🎮 Jogos Integrados & Módulos
- **TOPERO**: Simulador de carreira de atleta standalone em PWA (Progressive Web App) Dark Theme, com progressão realista até os 40 anos, métricas de goleiro e exportação de card de carreira.
- **Apresentação Placar (Revista)**: Formato revista visual em estilo figurinha com histórico, títulos e estatísticas do time.
- **Octamotor**: Módulo dedicado à gestão de automobilismo (pilotos, equipes, circuitos e temporadas).

## 🛠️ Tecnologias / Technology Stack
- **Backend**: PHP 8.x (Vanilla / MVC modular)
- **Database**: MySQL / MariaDB, SQLite3 (exportações de dados do simulador)
- **Frontend**: HTML5, CSS3 (Design 2.0 Glassmorphism, responsivo), JavaScript / jQuery
- **Libraries**: SimpleXLSX / SimpleXLSXGen, PHPMailer, Plotly

## 🚀 Instalação & Configuração / Setup
1. **Clonar o repositório**:
   ```bash
   git clone https://github.com/yourusername/confusatop.git
   ```
2. **Configurar o Banco de Dados**:
   - Copie `config/database.php.sample` para `config/database.php` (ou configure os parâmetros de conexão do MySQL).
3. **Requisitos de Servidor**:
   - PHP 8.0+
   - MySQL 5.7+ / MariaDB
   - Servidor Web (Apache com `mod_rewrite` / Nginx)
4. **Permissões de Pastas**:
   - Certifique-se de que os diretórios `export/` e `wp-content/uploads/` tenham permissão de escrita.

## 🤝 Contribuição
Este projeto é desenvolvido para o ecossistema e comunidade da CONFUSA.

## 📜 Licença / License
Distribuído sob a licença **Creative Commons Atribuição-NãoComercial-CompartilhaIgual 4.0 Internacional (CC BY-NC-SA 4.0)**.

- ❌ **Uso Comercial Proibido**: É expressamente proibida a cópia, redistribuição ou uso do código, banco de dados e ativos deste projeto com o intuito de obter lucro, comercializar ou monetizar direta ou indiretamente.
- ✔️ **Uso Não Comercial Permitido**: Permitido para estudos, uso pessoal e contribuições dentro da comunidade, mantendo a atribuição original aos autores e a mesma licença.

Consulte o arquivo [LICENSE](LICENSE) para mais detalhes.

---
Desenvolvido por **Luis Cereda** & Comunidade CONFUSA.  
Baseado e integrado à suíte Hexacolor por **Ronaldo Junior**.
