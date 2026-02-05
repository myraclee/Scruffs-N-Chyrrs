# Scruffs-N-Chyrrs

**Scruffs-N-Chyrrs** is a modern, monolithic web application designed for merchandise manufacturing services (stickers, prints, pins). This project is built on the standard **PHP** ecosystem, utilizing **Laravel 12** as the core framework.

The architecture follows a standard **MVC (Model-View-Controller)** pattern but integrates a hybrid frontend stack combining **Blade templates**, **Bootstrap 5**, and **Tailwind CSS 4** via **Vite 7**. It features robust configuration for enterprise-grade integrations including AWS SES, Redis, and Slack, suggesting a focus on scalability and reliable background processing.

## Core Tech Stack

- **Backend Language:** [PHP 8.2+](https://www.php.net/)
- **Backend Framework:** [Laravel 12](https://laravel.com/docs/12.x)
- **Frontend Build Tool:** [Vite 7](https://vitejs.dev/)
- **Styling Strategy:** Hybrid - [Tailwind CSS 4](https://tailwindcss.com/) + [Bootstrap 5](https://getbootstrap.com/) + Custom Raw CSS
- **Database:** MySQL (Default) / SQLite (Testing)
- **Testing:** [Pest PHP 3](https://pestphp.com/) & PHPUnit
- **Development Tools:** Laravel Boost, Laravel Pail, Laravel Pint
- **Scripting:** npm scripts & Composer scripts

## Prerequisites

Ensure you have the following installed on your local environment:

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- A database engine (MySQL/MariaDB)

## Getting Started

### 1. Pre-Installation

1. Go to the XAMPP folder.
2. Go to the PHP folder inside the XAMPP folder.
3. Open the php.ini file.
4. Press CTRL + F and search ";extension=zip".
5. Remove the ";" to change the text to "extension=zip".
6. Save the file.

### 2. Installation

The project includes a built-in setup script to automate the initial configuration. Run the following command in your terminal:

```bash
composer run setup
```

This script will:

- Install PHP dependencies via Composer.
- Create a `.env` file from `.env.example`.
- Generate the application encryption key.
- Run database migrations.
- Install NPM dependencies.
- Build frontend assets.

### 3. Configuration

Open the `.env` file and configure your database credentials:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scruffsnchyrrs
DB_USERNAME=root
DB_PASSWORD=
```

## Development Workflow

### Running the Application

To start the local development server along with the Vite dev server and queue listener, run:

```bash
composer run dev
```

This command uses `concurrently` to execute:

- **Server:** `php artisan serve` (Host: 127.0.0.1:8000)
- **Queue:** `php artisan queue:listen` (Background jobs)
- **Vite:** `npm run dev` (Frontend hot-reloading)

### Testing

We use Pest for testing. To run the test suite:

```bash
composer run test
```

*Uses Pest PHP running against an in-memory SQLite database (`phpunit.xml`).*

### Code Quality

To maintain code style consistency, use Laravel Pint:

```bash
./vendor/bin/pint
```

*Uses Laravel Pint for PSR-12 code style enforcement.*

## Project Structure

- **`app/`**: Contains core application logic (Models, Controllers, Providers).
  - `Models/`: Eloquent ORM definitions (e.g., `User.php`).
  - `Providers/`: Service bootstrapping (`AppServiceProvider.php`).
- **`config/`**: Source of truth for system behavior.
  - `services.php`: Credentials for AWS, Resend, Postmark, Slack.
  - `database.php`: Definitions for MySQL and Redis connections.
- **`database/`**: Migrations, factories, and seeders.
- **`resources/`**: Frontend assets.
  - `views/`: Blade templates organized by domain (`customer/`, `page_parts/`, `layouts/`).
  - `css/`: Hybrid styling. `app.css` initializes Tailwind 4. Custom CSS files (`universal.css`, `signup.css`) handle specific page styling.
  - `js/`: Application logic (`app.js` initializes Bootstrap).
- **`routes/`**: Application route definitions.
  - `web.php`: Defines synchronous HTTP endpoints.
  - `console.php`: Artisan command definitions.
- **`tests/`**: Feature and Unit Tests.
  - `Pest.php`: Configuration for the Pest testing framework.

### Key Configuration Files

- **`vite.config.js`**: Configures the frontend build pipeline. It utilizes `@tailwindcss/vite` (Tailwind 4) and `laravel-vite-plugin`. It explicitly tracks CSS/JS entry points.
- **`composer.json`**: Defines backend dependencies and custom operational scripts.
- **`package.json`**: Defines frontend dependencies. Note the coexistence of `bootstrap` and `tailwindcss`.

### Infrastructure & Services

The application interacts with the following systems:

- **Data Persistence:**
  - **Primary:** Relational Database (MySQL configured via `DB_CONNECTION`).
  - **Caching/Session:** Redis (via `predis/phpredis`) is the target architecture for production caching and session management (`config/database.php`, `config/cache.php`).
- **Queue System:**
  - Database-driven queues are currently active in the dev workflow (`php artisan queue:listen`).
  - Configuration exists for SQS and Redis queues.
- **External Integrations:**
  - **Email:** Multi-driver support configured for AWS SES, Postmark, and Resend.
  - **Notifications:** Slack webhook integration for critical logging and alerts.
  - **Storage:** AWS S3 configuration is present for cloud file storage.

## Project Conventions & Patterns

### Frontend Strategy (The Hybrid Model)

The application uses a specific strategy for frontend assets that requires attention to avoid conflicts:

1. **Tailwind CSS 4:** Initialized in `resources/css/app.css` via `@import "tailwindcss";` and the `@theme` directive.
2. **Bootstrap 5:** JavaScript components are imported in `resources/js/app.js` (`import 'bootstrap';`) and used for interactive elements like Modals (`tnc.js`).
3. **Custom CSS:** Specific pages load raw CSS files via Vite (e.g., `universal.css` for fonts, `signup.css` for form styling).
    - *Convention:* Page-specific CSS is injected via `@section('page_css')` in Blade templates.

### Route Pattern

- **Routing:** Routes currently use closure-based definitions in `routes/web.php` returning views directly.

### Asset Management

- **Images:** Stored in `public/images/` and categorized into `brand_elements` and `website_elements`.
- **Fonts:** Custom fonts (`Coolvetica`, `SuperDream`) are stored in `resources/fonts/` and loaded via `@font-face` in `universal.css`.

## Integration Points & Data Flow

### Data Flow Diagram Description

1. **End User** interacts with the **Scruffs N Chyrrs Application**.
2. The App reads/writes business data to the **Main Database (MySQL)**.
3. Session state and application cache are stored in **Redis**.
4. Transactional emails are dispatched via one of the configured providers (**Postmark**, **Resend**, or **AWS SES**) based on `.env` configuration.
5. System alerts and logs are pushed to **Slack**.

### External Service Configuration

- **AWS SES:** Requires `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`.
- **Postmark/Resend:** Require specific API keys in `.env`.
- **Slack:** configured via `LOG_SLACK_WEBHOOK_URL` in logging channels.

## Known Constraints & Caveats

1. **CSS Conflict Risk:** Using Bootstrap JS with Tailwind CSS requires ensuring that class names do not collide. The project currently relies on custom CSS classes (e.g., `.nav_container`, `.footer_tophalf`) rather than purely utility-first CSS, mitigating some collision risks but increasing maintenance overhead.
2. **Mail Driver:** The default mailer is set to `log` in `mail.php`. For production or testing email delivery, `MAIL_MAILER` must be updated in `.env`.

## Reference: Key Commands

| Action | Command | Description |
| :--- | :--- | :--- |
| **Setup Project** | `composer run setup` | Full install from scratch. |
| **Start Dev** | `composer run dev` | Starts App, Queue, and Vite. |
| **Run Tests** | `composer run test` | Executes Pest test suite. |
| **Format Code** | `./vendor/bin/pint` | Auto-fixes PHP code style. |
| **Build Assets** | `npm run build` | Compiles assets for production. |
| **Clear Cache** | `php artisan optimize:clear` | Flushes all Laravel caches. |

## Contributing To Scruffs-N-Chyrrs

1. Clone the repository.
2. Create a new feature branch (`git checkout -b feature/amazing-feature`).
3. Ensure tests pass before committing.
4. Open a Pull Request.

## Project License

The Scruffs-N-Chyrrs project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Laravel Framework

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing To Laravel

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## Laravel License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
