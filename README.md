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

#### Cloudflare Turnstile CAPTCHA Configuration

The signup form uses **Cloudflare Turnstile** for CAPTCHA protection. You must configure the site key in your `.env` file:

```dotenv
CLOUDFLARE_TURNSTILE_SITEKEY=YOUR-SITE-KEY
```

To obtain your Cloudflare Turnstile site key:

1. Go to [Cloudflare Dashboard](https://dash.cloudflare.com/).
2. Navigate to **Turnstile** in the left sidebar.
3. Create a new site or use an existing one.
4. Copy your **Site Key** and paste it into the `CLOUDFLARE_TURNSTILE_SITEKEY` variable in your `.env` file.

> **Note:** Without this configuration, the signup form's CAPTCHA validation will not function, and form submissions will fail.

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

## Current Architecture Overview

### Database Models

Core data models located in `app/Models/`:

- **`User.php`** - Customer & Owner accounts with authentication
- **`Product.php`** - Merchandise products with cover images & pricing options
- **`ProductPriceImage.php`** - Price list images displayed for each product variant
- **`HomeImage.php`** - Images displayed in home page slideshow carousel
- **`ProductSample.php`** - Sample displays showcasing products in real-world applications
- **`ProductSampleImage.php`** - Images associated with product samples
- **`Material.php`** - Material types (e.g., vinyl, paper, plastic)

### API Endpoints

All API endpoints are prefixed with `/api/` and defined in `routes/web.php`:

```
Products
  GET    /api/products              - List all products with pricing
  POST   /api/products              - Create new product
  GET    /api/products/{id}         - Get product details
  PUT    /api/products/{id}         - Update product information
  DELETE /api/products/{id}         - Delete product

Home Images
  GET    /api/home-images           - List home page slideshow images
  POST   /api/home-images           - Upload new home image
  DELETE /api/home-images/{id}      - Remove home image

Product Samples
  GET    /api/product-samples       - List all product samples
  POST   /api/product-samples       - Create new sample
  GET    /api/product-samples/{id}  - Get sample details
  PUT    /api/product-samples/{id}  - Update sample
  DELETE /api/product-samples/{id}  - Delete sample
```

## Project Conventions & Patterns

### Frontend Strategy (The Hybrid Model)

The application uses a specific strategy for frontend assets that requires attention to avoid conflicts:

1. **Tailwind CSS 4:** Initialized in `resources/css/app.css` via `@import "tailwindcss";` and the `@theme` directive.
2. **Bootstrap 5:** JavaScript components are imported in `resources/js/app.js` (`import 'bootstrap';`) and used for interactive elements like Modals (`tnc.js`).
3. **Custom CSS:** Specific pages load raw CSS files via Vite (e.g., `universal.css` for fonts, `signup.css` for form styling).
    - *Convention:* Page-specific CSS is injected via `@section('page_css')` in Blade templates.

#### Frontend Asset Organization

The frontend assets are organized by role and functionality:

```
resources/
├── css/
│   ├── app.css                      ← Tailwind 4 initialization
│   ├── customer/
│   │   └── pages/
│   │       ├── home.css             ← Product samples styling
│   │       ├── home_images.css      ← Slideshow styling
│   │       └── products.css
│   ├── owner/
│   │   └── pages/
│   │       └── content_management/
│   │           ├── home_page_content.css
│   │           ├── products_page_content.css
│   │           └── content_management.css
│   ├── universal_customer.css       ← Customer-wide styles & fonts
│   └── universal_owner.css          ← Owner-wide styles & fonts
├── js/
│   ├── app.js                       ← Bootstrap initialization
│   ├── api/
│   │   ├── productApi.js            ← Product CRUD operations
│   │   ├── productSampleApi.js      ← Sample CRUD operations
│   │   └── homeImageApi.js          ← Home image CRUD operations
│   ├── utils/
│   │   ├── toast.js                 ← Toast notifications
│   │   └── formState.js             ← Form state management
│   ├── customer/
│   │   └── pages/
│   │       ├── home_product_samples.js   ← Load and display samples
│   │       └── home_images_slideshow.js  ← Auto-rotating slideshow
│   └── owner/
│       └── content_page/
│           ├── products_page_content_refactored.js
│           ├── product_sample_modal.js
│           └── edit_home_images_modal.js
└── views/
    ├── layouts/
    │   ├── customer_layout.blade.php
    │   ├── owner_layout.blade.php
    │   └── page_parts/
    ├── customer/
    │   └── pages/
    │       ├── home.blade.php           ← Slideshow + samples
    │       ├── products.blade.php
    │       └── faqs.blade.php
    └── owner/
        └── pages/
            └── content_management.blade.php ← Admin panel
```

## Security & Authentication

### CSRF Token Protection

**All API requests with state changes (POST, PUT, DELETE) require CSRF tokens.** This is critical for preventing cross-site request forgery attacks.

#### Getting the CSRF Token

The CSRF token meta tag is included in the layout files (`owner_layout.blade.php`):

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

#### JavaScript API Utilities Pattern

All JavaScript API clients must include CSRF token handling:

```javascript
class ProductAPI {
    constructor() {
        this.baseUrl = '/api/products';
    }

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    async createProduct(formData) {
        const response = await fetch(this.baseUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'Accept': 'application/json'
            },
            body: formData
        });
        return response.json();
    }

    async updateProduct(id, formData) {
        const response = await fetch(`${this.baseUrl}/${id}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'Accept': 'application/json'
            },
            body: formData
        });
        return response.json();
    }

    async deleteProduct(id) {
        const response = await fetch(`${this.baseUrl}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'Accept': 'application/json'
            }
        });
        return response.json();
    }
}
```

**⚠️ Common Errors:**

- **"CSRF token not found"** → Ensure meta tag is in the layout's `<head>` section
- **"Failed to upload: Unexpected token '<'"** → CSRF token not being sent in request headers; add `'X-CSRF-TOKEN': this.getCsrfToken()` to headers

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

## Code Style & Conventions

### Language-Specific Guidelines

- **PHP:** PSR-12 (enforced by Laravel Pint via `./vendor/bin/pint`)
- **JavaScript:** ES6 modules with class-based API clients
- **CSS:** Mobile-first responsive design using Tailwind utilities + custom classes
- **Blade Templates:** Follow Laravel conventions; use Coolvetica/SuperDream fonts; maintain consistent `#682C7A` purple theme
- **API Responses:** Always return JSON with structure: `{ success: boolean, data: mixed, message: string }`

### File Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| PHP Classes | PascalCase | `ProductController.php`, `HomeImage.php` |
| PHP Methods | camelCase | `getAllProducts()`, `createProduct()` |
| JavaScript Files | snake_case | `productApi.js`, `home_images_slideshow.js` |
| CSS Files | snake_case | `universal_customer.css`, `home_images.css` |
| Blade Views | snake_case | `home.blade.php`, `content_management.blade.php` |
| Database Tables | snake_case, plural | `products`, `product_samples`, `home_images` |
| Database Columns | snake_case | `product_id`, `created_at`, `user_type` |

### Code Organization

**API Client Classes:** Group related API operations in single class files with consistent method patterns:

```javascript
class ProductAPI {
    async getAllProducts() { /* GET /api/products */ }
    async getProduct(id) { /* GET /api/products/:id */ }
    async createProduct(data) { /* POST /api/products */ }
    async updateProduct(id, data) { /* PUT /api/products/:id */ }
    async deleteProduct(id) { /* DELETE /api/products/:id */ }
}
```

**Blade Template Structure:**

```blade
@extends('layouts.customer_layout')

@section('page_css')
@vite('resources/css/customer/pages/example.css')
@endsection

@section('content')
<!-- Page HTML -->
@endsection

@section('page_js')
@vite('resources/js/customer/pages/example.js')
@endsection
```

## Development Best Practices

### Adding a New Feature (Step-by-Step Guide)

#### 1. Create Migration & Model

```bash
php artisan make:model YourFeature -m
```

This generates both the model in `app/Models/YourFeature.php` and a migration in `database/migrations/`. Edit the migration file to define your schema, then run:

```bash
php artisan migrate
```

#### 2. Create API Routes

Add routes to `routes/web.php` following the existing pattern:

```php
// API routes (prefix: /api/)
Route::prefix('api/your-features')->group(function () {
    Route::get('/', [YourFeatureController::class, 'index']);           // List all
    Route::post('/', [YourFeatureController::class, 'store']);          // Create
    Route::get('/{id}', [YourFeatureController::class, 'show']);        // Get single
    Route::put('/{id}', [YourFeatureController::class, 'update']);      // Update
    Route::delete('/{id}', [YourFeatureController::class, 'destroy']);  // Delete
});

// Web routes (for blade templates)
Route::get('/path', function () {
    return view('your_feature.page');
});
```

#### 3. Create Frontend API Utility

Create `resources/js/api/yourFeatureApi.js`:

```javascript
class YourFeatureAPI {
    constructor() {
        this.baseUrl = '/api/your-features';
    }

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    async getAll() {
        const response = await fetch(this.baseUrl);
        return response.json();
    }

    async getById(id) {
        const response = await fetch(`${this.baseUrl}/${id}`);
        return response.json();
    }

    async create(formData) {
        const response = await fetch(this.baseUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'Accept': 'application/json'
            },
            body: formData
        });
        return response.json();
    }

    async update(id, formData) {
        const response = await fetch(`${this.baseUrl}/${id}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'Accept': 'application/json'
            },
            body: formData
        });
        return response.json();
    }

    async delete(id) {
        const response = await fetch(`${this.baseUrl}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'Accept': 'application/json'
            }
        });
        return response.json();
    }
}

export default new YourFeatureAPI();
```

#### 4. Create Blade Template

Create `resources/views/customer/pages/your_feature.blade.php`:

```blade
@extends('layouts.customer_layout')

@section('page_css')
@vite('resources/css/customer/pages/your_feature.css')
@endsection

@section('content')
<div class="container">
    <h1>Your Feature</h1>
    <!-- Content here -->
</div>
@endsection

@section('page_js')
@vite('resources/js/customer/pages/your_feature.js')
@endsection
```

#### 5. Create Page JavaScript

Create `resources/js/customer/pages/your_feature.js`:

```javascript
import YourFeatureAPI from '../../api/yourFeatureApi.js';
import { showToast } from '../../utils/toast.js';

async function loadFeatures() {
    try {
        const result = await YourFeatureAPI.getAll();
        if (result.success) {
            // Render features
            console.log(result.data);
        }
    } catch (error) {
        showToast('Error loading features', 'error');
    }
}

document.addEventListener('DOMContentLoaded', loadFeatures);
```

#### 6. Add Styles

Create `resources/css/customer/pages/your_feature.css` for page-specific styling.

### General Best Practices

1. **Always include CSRF token in POST/PUT/DELETE requests** to API endpoints
2. **Use async/await** instead of `.then()` chains for cleaner code
3. **Validate input** on both frontend (UX) and backend (security)
4. **Create migrations** for any database changes (never modify DB directly)
5. **Write tests** for critical business logic (use `composer run test`)
6. **Use meaningful commit messages** when pushing to git
7. **Keep functions small and focused** - single responsibility principle
8. **Document complex logic** with comments, especially in JavaScript APIs

## Troubleshooting & FAQ

### Common Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| **"CSRF token not found"** | Meta tag missing in layout | Verify `<meta name="csrf-token">` is in layout's `<head>` |
| **"Failed to upload: Unexpected token '<'"** | CSRF token not sent in request | Add `'X-CSRF-TOKEN': this.getCsrfToken()` to request headers |
| **API returns 404** | Route not registered | Check `routes/web.php` and ensure route path matches |
| **Vite not rebuilding** | Dev server not running | Run `composer run dev` or rebuild with `npm run build` |
| **Database migrations fail** | Wrong credentials or DB doesn't exist | Check `.env` database settings match your MySQL instance |
| **"Module not found" in JS** | Import path incorrect | Verify file path and use `./` for relative imports |
| **Styling not applying** | CSS file not imported via Vite | Use `@vite('resources/css/...')` in Blade template |
| **Bootstrap modal not working** | Bootstrap JS not initialized | Ensure `import 'bootstrap';` in `resources/js/app.js` |
| **Can't access `/admin` routes** | Routes point to non-existent views | Create view files or update routes in `web.php` |

### Performance Tips

- **Cache API responses** when appropriate to reduce database hits
- **Use Redis** for sessions and caching in production (configured in `.env`)
- **Minify assets** in production with `npm run build`
- **Use database indexes** for frequently queried columns
- **Lazy-load images** in slideshows and product galleries

### Debugging Techniques

1. **Check Laravel logs:** `storage/logs/laravel.log`
2. **Use Laravel Pail:** `php artisan pail` (real-time log streaming)
3. **Browser DevTools:** F12 → Network/Console tabs for API issues
4. **Add console.log()** in JavaScript to trace execution
5. **Test API endpoints** directly with tools like Postman or curl

## Reference: Key Commands

| Action | Command | Description |
| :--- | :--- | :--- |
| **Setup Project** | `composer run setup` | Full install from scratch. |
| **Start Dev** | `composer run dev` | Starts App, Queue, and Vite. |
| **Run Tests** | `composer run test` | Executes Pest test suite. |
| **Format Code** | `./vendor/bin/pint` | Auto-fixes PHP code style. |
| **Build Assets** | `npm run build` | Compiles assets for production. |
| **Clear Cache** | `php artisan optimize:clear` | Flushes all Laravel caches. |
| **Rebuild Database** | `php artisan migrate:fresh` | Rebuilds database from scratch. |

## File Structure Summary

| Directory | Purpose |
|-----------|---------|
| `app/Models` | Eloquent ORM models (User, Product, ProductSample, etc.) |
| `app/Http/Controllers` | API and web request handlers |
| `config/` | Application configuration files |
| `database/migrations` | Schema definitions and database changes |
| `database/factories` | Model factories for testing |
| `database/seeders` | Database seeders for populating test data |
| `resources/views` | Blade templates organized by domain (customer, owner) |
| `resources/css` | Stylesheets (Tailwind 4 + Custom CSS) organized by role and page |
| `resources/js` | JavaScript modules (API clients, utilities, page logic) |
| `resources/fonts` | Custom fonts (Coolvetica, SuperDream) |
| `routes/` | Route definitions (web.php for HTTP, console.php for Artisan) |
| `tests/` | Test files using Pest PHP framework |
| `public/` | Publicly served files (compiled assets, images) |
| `storage/` | Application storage (logs, file uploads, sessions) |
| `bootstrap/` | Framework bootstrap files |

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
