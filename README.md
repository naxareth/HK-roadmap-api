# HK Roadmap API

REST API backend for the **HK Roadmap** Android app — a scholarship management system for the Hawak Kamay program at PHINMA University of Pangasinan (UPANG). Handles student authentication, document uploads, requirements tracking, and notifications.

Built as a 2nd year, 2nd semester project.

---

## Features

- **Student authentication** — login with student ID, JWT-based session handling
- **Document management** — upload and retrieve scholarship requirement documents
- **Requirements tracking** — manage and update scholar requirement statuses
- **Email notifications** — PHPMailer integration for deadline and announcement emails
- **Profile pictures** — upload and serve student profile images
- **Admin web panel** — browser-based dashboard for administrators to manage scholars, documents, and requirements

---

## Tech Stack

| | |
|---|---|
| Language | PHP 8.2 |
| Architecture | MVC (Controllers / Models / Routes) |
| Database | MySQL / MariaDB |
| Email | PHPMailer |
| Server | Apache (.htaccess routing) |
| Dependencies | Composer |
| Web Frontend | HTML, CSS, JavaScript (Admin Panel) |

---

## Project Structure

```
HK-roadmap-api/
├── Controllers/        # Request handlers
├── Models/             # Database models
├── Routes/             # API route definitions
├── Middleware/         # Auth and request middleware
├── config/             # Database and app configuration
├── PhpMailer/          # Email service
├── Public/             # Public entry point
├── uploads/            # Uploaded files (documents, profile pictures)
├── hkr_database.sql    # Full database schema
├── document.sql        # Document table schema
├── requirements.sql    # Requirements table schema
└── composer.json       # PHP dependencies
```

---

## Getting Started

### Prerequisites

- PHP 8.2+
- MySQL or MariaDB
- Composer
- Apache (with `mod_rewrite` enabled)

### Setup

1. Clone the repository:
```bash
git clone https://github.com/naxareth/HK-roadmap-api.git
cd HK-roadmap-api
```

2. Install dependencies:
```bash
composer install
```

3. Create your database and import the schema:
```bash
mysql -u root -p < hkr_database.sql
```

4. Configure your database connection in `config/`:
```php
// Update host, database name, username, and password
```

5. Configure PHPMailer credentials for email notifications

6. Point your Apache virtual host to the `Public/` directory and ensure `.htaccess` rewriting is enabled

7. Start your server and test the API

---

## Database

The project includes three SQL files:

- `hkr_database.sql` — full schema with all tables
- `document.sql` — document table only
- `requirements.sql` — requirements table only

---

## Related Repository

**Android App:** [naxareth/HK-roadmap](https://github.com/naxareth/HK-roadmap)

---

## Contributors

- **Justin Escano** ([@JustinEscano](https://github.com/JustinEscano))
- **Ace Philip Denulan** ([@naxareth](https://github.com/naxareth))
- **Feniel Barte**
- **Princess Caguioa**

---

## License

For academic use. PHINMA University of Pangasinan — 2nd Year, 2nd Semester.
