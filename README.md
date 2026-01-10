# CONFUSA.top

**CONFUSA.top** is a comprehensive web portal designed for the management of the **Confederação de Futebol Solitário Associado (CONFUSA)**. It serves as a central hub for managing football teams, players, transfers, and rankings, designed to integrate seamlessly with the **Hexacolor YMT** desktop suite.

## 📖 About
Originally developed to provide a centralized ranking system, the project evolved into a full-featured management system. It allows users to:
- Maintain a secure, centralized database of their teams.
- Manage squads with unlimited players (titulars, reserves, substitutes).
- Automate player aging and retirement.
- Negotiate transfers and loans in a global market.
- Generate teams and players using the integrated **Hexagen** algorithm.

## ✨ Key Features

### ⚽ Football Management
- **Leagues & Teams**: Create and manage leagues (masculine/feminine) and teams.
- **Player & Coach Editor**: Detailed editing of attributes, positions, and history.
- **Marketplace**: Search for players/coaches, make transfer proposals, and manage contract negotiations.
- **Ranking**: Automated ELO-based ranking system for national teams (FIFA-style).
- **Referees**: Manage and export referee trios (`.tda` format).
- **Hexacolor Integration**: Export complete databases (`.db3`) and asset packs compatible with Hexacolor YMT.

### 🏎️ Octamotor
A dedicated module for racing management, featuring:
- **Drivers & Teams**: Manage profiles and stats.
- **Circuits**: Database of tracks.
- **Live Info**: Real-time tracking of race events.
- **Competitions**: Season and championship management.

## 🛠️ Technology Stack
- **Backend**: PHP (Vanilla)
- **Database**: MySQL, SQLite (for exports)
- **Frontend**: HTML5, CSS3, JavaScript (jQuery)

## 🚀 Setup & Installation

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/yourusername/confusatop.git
    ```
2.  **Configure Database**:
    - Copy `config/database.php.sample` to `config/database.php` (if available, otherwise create it).
    - Set your MySQL credentials (host, user, password, db name).
3.  **Server Requirements**:
    - PHP 7.4+
    - MySQL 5.7+ / MariaDB
    - Web Server (Apache/Nginx)
4.  **Directory Permissions**:
    - Ensure `wp-content/uploads` (if used) and `export/` directories are writable.

## 🤝 Contributing
This project is developed for the private use of the CONFUSA community.

## 📜 License
Proprietary software developed by **Luis Cereda**.
Based on the Hexacolor suite by **Ronaldo Junior**.
