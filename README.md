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

```cmd
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

```cmd
composer run dev
```

This command uses `concurrently` to execute:

- **Server:** `php artisan serve` (Host: 127.0.0.1:8000)
- **Queue:** `php artisan queue:listen` (Background jobs)
- **Vite:** `npm run dev` (Frontend hot-reloading)

### Testing

We use Pest for testing. To run the test suite:

```cmd
composer run test
```

*Uses Pest PHP running against an in-memory SQLite database (`phpunit.xml`).*

### Code Quality

To maintain code style consistency, use Laravel Pint:

```cmd
vendor\bin\pint
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

```cmd
php artisan make:model YourFeature -m
```

This generates both the model in `app/Models/YourFeature.php` and a migration in `database/migrations/`. Edit the migration file to define your schema, then run:

```cmd
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

### Quick Reference Table

| Issue | Root Cause | Quick Solution |
| ------- | ----------- | ----------------- |
| **CSRF token not found** | Meta tag missing in layout or token retrieval fails | Verify `<meta name="csrf-token" content="{{ csrf_token() }}">` in layout's `<head>` and check browser console |
| **Failed to upload image: Unexpected token '<', "<!DOCTYPE"... is not valid JSON** | CSRF token not sent in request headers. Server returning HTML error page instead of JSON | Ensure CSRF token is sent in request headers and API routes are registered. Add `'X-CSRF-TOKEN': this.getCsrfToken()` to request headers |
| **API returns 404** | Route not registered in `routes/web.php` | Check endpoint path in `routes/web.php` and verify route exists |
| **Vite not rebuilding** | Dev server not running or port conflict | Run `composer run dev` to start all services or rebuild with `npm run build` |
| **Database migrations fail** | Wrong credentials or Invalid `.env` credentials or DB doesn't exist | Verify `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` in `.env` match your MySQL instance |
| **"Module not found" in JS** | Import path incorrect | Verify file path and use `./` for relative imports |
| **Styling not applying** | CSS file not imported via Vite | Use `@vite('resources/css/...')` in Blade template |
| **Bootstrap modal not working** | Bootstrap JS not initialized | Ensure `import 'bootstrap';` in `resources/js/app.js` |
| **Can't access `/admin` routes** | Routes point to non-existent views | Create view files or update routes in `web.php` |

---

## Error Deep Dives

### Error: "Failed to upload image: Unexpected token '<', "<!DOCTYPE"... is not valid JSON"

#### Understanding the Error

This error occurs when JavaScript tries to parse a JSON response from the server, but instead receives an **HTML document** (indicated by the `<` character starting with `<!DOCTYPE`). The browser's JSON parser fails because HTML is not valid JSON, triggering a `SyntaxError`.

**Why does this happen?**
When you upload a file and the server returns an error page (like 404, 403, or 500 with HTML), the frontend tries to parse it as JSON and fails.

#### Root Causes & Scenarios

1. **CSRF Token Missing or Not Sent**
   - The `<meta name="csrf-token" content="{{ csrf_token() }}">` tag is missing from the layout
   - The CSRF token is not being included in the `X-CSRF-TOKEN` header
   - Laravel's CSRF middleware rejects the request, returning a 403 HTML error page
   - **Symptom:** Upload button sends request but server responds with HTML error page

2. **User Not Authenticated (Expired Session)**
   - Session token expired or user logged out
   - Route requires authentication but user isn't authenticated
   - Laravel returns 401/403 unauthorized HTML page
   - **Symptom:** Error appears after being logged in for a long time without activity

3. **API Route Not Registered**
   - Endpoint path doesn't match any registered route in `routes/web.php`
   - Typo in API endpoint URL in JavaScript
   - Server returns 404 HTML error page
   - **Symptom:** Check Network tab, see 404 status code

4. **Server Configuration / Database Issue**
   - Database connection fails during file processing
   - File storage permissions incorrect
   - Laravel `.env` configuration missing or wrong
   - **Symptom:** Error occurs after CSRF passes, during file save operation

5. **Application in Debug Mode with Stack Trace**
   - `APP_DEBUG=true` in `.env` with an exception thrown
   - Laravel returns detailed HTML error page instead of JSON
   - **Symptom:** HTML response contains full stack trace in Network tab

#### Prevention: Before Development

✅ **Always verify these before building upload features:**

1. **Meta tag present in layouts** — Check both layout files:

    Customer layout

   ```cmd
   findstr "csrf-token" resources\views\layouts\customer_layout.blade.php
   ```

    Owner layout

   ```cmd
   findstr "csrf-token" resources\views\owner\layouts\owner_layout.blade.php
   ```

   Expected output: `<meta name="csrf-token" content="{{ csrf_token() }}">`

2. **API client initializes CSRF token correctly** — Verify `getCsrfToken()` method in API class:

   ```javascript
   // From resources/js/api/productApi.js (lines 16-21)
   getCsrfToken() {
       return document.querySelector('meta[name="csrf-token"]')
           ?.getAttribute('content');
   }
   ```

3. **Routes are properly registered** — Check `routes/web.php`:

   ```cmd
   findstr "POST.*api/products" routes\web.php
   findstr "POST.*api/home-images" routes\web.php
   findstr "POST.*api/product-samples" routes\web.php
   ```

4. **File permissions correct** — Storage directories writable:

    Grant full permissions to current user on storage directories

   ```cmd
   icacls "storage\app\public" /grant:r "%USERNAME%":(OI)(CI)F /T
   icacls "storage\logs" /grant:r "%USERNAME%":(OI)(CI)F /T
   ```

5. **`.env` properly configured** — Verify key variables exist:

   ```cmd
   findstr "APP_KEY=base64:" .env
   findstr "DB_CONNECTION=mysql" .env
   ```

#### Diagnosis: Step-by-Step Troubleshooting

**Step 1: Capture the API Response**

1. Open browser DevTools: Press `F12` → Click **Network** tab
2. Attempt the upload (try uploading an image)
3. Look for the POST request (usually `POST /api/products` or similar)
4. Click that request to inspect it
5. Click the **Response** tab — **This is what the server actually returned**

**Expected:** JSON like:

```json
{
  "success": false,
  "errors": {"cover_image": ["The image must be a PNG or JPEG file"]},
  "message": "Validation failed"
}
```

**Actual (if error):** HTML starting with:

```html
<!DOCTYPE html>
<html>
  <head><title>403 Forbidden</title></head>
  <body>...
```

---

**Step 2: Verify CSRF Token in Request**

1. Still in Network tab, check the **Request Headers** section
2. Scroll down and look for a header named `x-csrf-token`
3. Verify it has a value (should be a long random string, not empty)

**If missing:**

- Token not being sent → indicates JavaScript isn't calling `getCsrfToken()` or token retrieval failed
- Move to **Common Fixes** section, Fix #1

**If present:**

- Token was sent → problem is elsewhere, check Step 3

---

**Step 3: Check File Upload Parameters**

1. Still in Network tab, click the **Payload** tab
2. Verify form data includes:
   - File field (e.g., `cover_image`: [File object])
   - Token field (if added via FormData): `_token`: [value]

**If file missing:**

- File input not properly selected in JavaScript
- Check browser Console for errors (press F12 → **Console** tab)

---

**Step 4: Check Browser Console for Errors**

1. Press `F12` → Click **Console** tab
2. Look for error messages in red
3. Exact error should say: `SyntaxError: Unexpected token '<'...`

**If you see this:**

- Confirms server returned HTML
- Check Laravel logs next

---

**Step 5: Check Laravel Application Logs**

Real-time log streaming (requires Laravel Pail)

```cmd
php artisan pail
```

Or view the log file directly (PowerShell command)

```cmd
Get-Content -Path storage\logs\laravel.log -Wait
```

Or check the most recent errors

```cmd
type storage\logs\laravel.log
```

**Look for:**

- `TokenMismatchException` → CSRF token invalid or missing
- `ModelNotFoundException` → Route handler issue
- `FileException` → File storage/permission problem
- Any exception class name and message

---

#### Common Fixes

**Fix #1: Add CSRF Token to Request Headers**

This is the #1 most common cause. Ensure your API client sends the CSRF token in headers:

```javascript
// Correct pattern from resources/js/api/productApi.js (lines 55-70)
async createProduct(formData) {
    const response = await fetch('/api/products', {
        method: 'POST',
        body: formData,  // FormData with files
        headers: {
            'X-CSRF-TOKEN': this.getCsrfToken(),  // ← CRITICAL
            'Accept': 'application/json'
        }
    });
    
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    return await response.json();
}
```

**Critical points:**

- Header name is `X-CSRF-TOKEN` (case-insensitive in HTTP, but convention matters)
- Value must be the result of `this.getCsrfToken()`
- Do **NOT** manually set `Content-Type: multipart/form-data` — let browser handle file boundary
- Always check response status before parsing JSON

---

**Fix #2: Add Token to FormData as Fallback**

Some configurations require the token in FormData as well:

```javascript
// Additional safety layer
async createProduct(formData) {
    if (this.getCsrfToken() && !formData.has('_token')) {
        formData.append('_token', this.getCsrfToken());
    }
    
    const response = await fetch('/api/products', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': this.getCsrfToken()
        }
    });
    
    return await response.json();
}
```

---

**Fix #3: Verify Routes Registered in `routes/web.php`**

Check that your upload endpoint is actually defined:

Check if route exists

```cmd
findstr /N "POST.*api/products" routes\web.php
findstr /N "POST.*api/home-images" routes\web.php
findstr /N "POST.*api/product-samples" routes\web.php
```

If not found, add the route:

```php
// routes/web.php
Route::post('/api/products', [ProductController::class, 'store']);
Route::post('/api/home-images', [HomeImageController::class, 'store']);
Route::post('/api/product-samples', [ProductSampleController::class, 'store']);
```

---

**Fix #4: Ensure Storage Symlink Exists**

Files are stored in `storage/app/public/` but accessed via `/storage/...`. This requires a symlink:

Create the symlink

```cmd
php artisan storage:link
```

Verify it was created

```cmd
dir public\storage
```

Should show the storage directory junction/symlink.
If not created, check file permissions and run as Administrator if needed.

---

**Fix #5: Check File Validation Rules**

The upload fails at validation. Check your controller for file size/type restrictions:

```php
// From app/Http/Controllers/Api/ProductController.php
$validated = $request->validate([
    'cover_image' => 'required|image|mimes:png,jpg,jpeg|max:2048',  // 2MB max
    'price_images.*' => 'required|image|mimes:png,jpg,jpeg|max:5120'  // 5MB max each
]);
```

If your file exceeds size or type constraints:

- Reduce file size in your image editor
- Ensure file format matches `mimes:` list
- Or update validation rule in controller if smaller files fail

**Test with a known-good file:**

Create a test image using PowerShell or download one.
The easiest approach is to use a sample image from your project.
Visit <https://www.pngquant.org/> to create a minimal PNG.
Or simply download any image and save it as test.png
Then try uploading this file to test the upload functionality.

---

#### Expected vs. Actual API Responses

**Successful Upload (Status 201):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Sticker Pack",
    "description": "Amazing stickers",
    "created_at": "2026-03-04T12:34:56.000000Z",
    "updated_at": "2026-03-04T12:34:56.000000Z"
  },
  "message": "Product created successfully"
}
```

**Validation Error (Status 422):**

```json
{
  "success": false,
  "errors": {
    "cover_image": [
      "The cover image field is required.",
      "The cover image must be an image.",
      "The cover image must be a file of type: png, jpg, jpeg."
    ]
  },
  "message": "Validation failed"
}
```

**Auth Error (Status 403 - HTML):**

```html
<!DOCTYPE html>
<html>
  <head><title>403 Forbidden</title></head>
  <body>
    <h1>403 Forbidden</h1>
    <p>CSRF token mismatch.</p>
  </body>
</html>
<!-- ← This is the problem! JSON parser fails on < -->
```

**Server Error (Status 500 - in production, JSON):**

```json
{
  "success": false,
  "error": "Failed to upload image",
  "message": "Disk 'public' does not have a configured driver."
}
```

---

### Error: "CSRF token not found"

#### Understanding the Error

This error occurs when JavaScript cannot retrieve the CSRF token from the page's HTML. The application needs the token to send with requests, but the meta tag (which contains the token) is either missing, malformed, or not yet loaded by the time the code runs.

#### Root Causes

1. **Meta Tag Missing from Layout**
   - `<meta name="csrf-token">` not included in layout's `<head>` section
   - Layout file not properly extended by the Blade view
   - **Symptom:** Searching page source (Ctrl+U) shows no `csrf-token` meta tag

2. **JavaScript Runs Before DOM Loads**
   - Token retrieval code executes before `<head>` is parsed
   - Script tag loaded with `async` attribute causing race condition
   - **Symptom:** Works sometimes, fails other times (timing issue)

3. **Incorrect Selector in `getCsrfToken()` Method**
   - Selector string `'meta[name="csrf-token"]'` is wrong
   - Attribute name or value doesn't match actual HTML
   - **Symptom:** Console shows `null` when running selector manually

4. **Browser Cache or Security Issues**
   - Stale cached page in browser
   - Private/Incognito mode blocking data access
   - Extensions interfering with DOM access
   - **Symptom:** Works in fresh browser, fails after reload

#### Prevention: Before Development

✅ **Ensure proper CSRF token setup:**

1. **Add meta tag to ALL layout files** at the end of `<head>`:

   ```html
   <head>
       <!-- Other meta tags -->
       <meta name="csrf-token" content="{{ csrf_token() }}">
   </head>
   ```

   Verify in both:
   - `resources/views/layouts/customer_layout.blade.php`
   - `resources/views/owner/layouts/owner_layout.blade.php`

2. **Wrap API initialization in DOMContentLoaded:**

   ```javascript
   document.addEventListener('DOMContentLoaded', async () => {
       // Token retrieval and API calls here
       const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
       // Now safe to use token
   });
   ```

3. **Use safe retrieval with optional chaining:**

   ```javascript
   getCsrfToken() {
       // ? prevents error if element doesn't exist
       return document.querySelector('meta[name="csrf-token"]')
           ?.getAttribute('content');
   }
   ```

4. **Test in multiple browser contexts:**
   - Normal window
   - Incognito/Private mode
   - After hard refresh (Ctrl+Shift+R)
   - On actual deployed server (not just localhost)

#### Diagnosis: Step-by-Step Troubleshooting

**Step 1: Verify Meta Tag Exists in Page Source**

1. Go to the page where error occurs
2. Right-click → **View Page Source** (or press Ctrl+U)
3. Press Ctrl+F, search for `csrf-token`
4. Look for this line:

   ```html
   <meta name="csrf-token" content="eyJpdiI6IjRyR...">
   ```

**If found:**

- Meta tag exists → move to Step 2
- Value looks like random string → token is valid

**If NOT found:**

- The layout doesn't include the meta tag
- Add it to the appropriate layout file:

  ```html
  <head>
      ...other meta tags...
      <meta name="csrf-token" content="{{ csrf_token() }}">
  </head>
  ```

---

**Step 2: Verify the Selector Works**

1. Press `F12` → **Console** tab
2. Copy-paste this command and press Enter:

   ```javascript
   document.querySelector('meta[name="csrf-token"]')
   ```

**If you see:** `<meta name="csrf-token" content="...">` element printout

- Selector is correct, element exists
- Problem might be timing

**If you see:** `null`

- Selector doesn't find the element
- Check spelling: `csrf-token` (not `csrfToken` or `csrf_token`)
- Verify `name` attribute has exact value `csrf-token`

---

**Step 3: Get the Token Value**

Still in Console, run:

```javascript
document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
```

**If you see:** A long random string (e.g., `"eyJpdiI6IjRyR2VhYkplYUxscEFONlp5YkFN..."`)

- Token exists and is retrievable
- Selector pattern is correct

**If you see:** `null` or `undefined`

- The content attribute is missing or empty
- This is a server-side issue (Laravel not generating token)
- Restart application: `composer run dev`

---

**Step 4: Verify Timing with DOMContentLoaded**

In Console, run:

```javascript
console.log('DOM loaded?', document.readyState);
console.log('Token exists?', document.querySelector('meta[name="csrf-token"]') !== null);
```

**If both return `true`/`loaded`:**

- DOM is ready, token is present
- Your API client should work

**If shows `loading`:**

- Script is running before page fully loads
- Wrap your code in `DOMContentLoaded` event

---

**Step 5: Check API Client Initialization**

For product uploads, verify `productApi.js` is properly imported and initialized:

```javascript
// In the page that needs uploads, check that API is imported
import ProductAPI from '...api/productApi.js';

// Verify getCsrfToken() works
console.log(ProductAPI.getCsrfToken());  // Should return token string
```

---

#### Other Common Fixes

**Fix #1: Add Missing Meta Tag to Layout**

Add this line to the `<head>` section of the layout file:

```html
<!-- resources/views/layouts/customer_layout.blade.php -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">  <!-- ← ADD THIS LINE -->
    <title>Scruffs&Chyrrs</title>
    ...rest of head...
</head>
```

Do this for **all layout files:**

- `resources/views/layouts/customer_layout.blade.php`
- `resources/views/owner/layouts/owner_layout.blade.php`

---

**Fix #2: Wrap Code in DOMContentLoaded**

Ensure token retrieval happens after DOM loads:

```javascript
// ❌ BAD - runs before DOM loads
const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// ✅ GOOD - waits for DOM
document.addEventListener('DOMContentLoaded', () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    // Now safe to use token
    performUpload(token);
});
```

---

**Fix #3: Use Optional Chaining Safely**

Prevent errors if element doesn't exist:

```javascript
// From resources/js/api/productApi.js (correct pattern)
getCsrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
    
    if (!token) {
        console.warn('CSRF token not found in meta tag');
        return '';
    }
    
    return token;
}
```

The `?.` operator returns `undefined` (not an error) if element doesn't exist, preventing crashes.

---

**Fix #4: Clear Browser Cache and Test Again**

Stale cache can cause issues:

- Hard refresh in browser: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
- Or Clear cache: Ctrl+Shift+Delete

Then:

1. Test in **Incognito/Private mode** (no cache interference)
2. If it works in Incognito, the issue was browser cache
3. Clear cache from normal browser and try again

---

**Fix #5: Verify Laravel is Generating Tokens**

Tokens are generated by Laravel's CSRF middleware. Restart the application:

```cmd
composer run dev
```

> Stop the dev server (Ctrl+C). Then restart the dev server. This will clear any cached views and regenerate CSRF tokens for all sessions. This is especially important if you recently changed the layout files or updated Laravel.

This forces Laravel to reinitialize and all views to re-render. New CSRF tokens will be generated for all sessions.

---

#### DevTools Verification Checklist

Use this checklist to verify CSRF token setup:

- [ ] Page source (Ctrl+U) contains `<meta name="csrf-token" content=...>`
- [ ] Meta tag is in `<head>` section (not in `<body>`)
- [ ] Content attribute has a non-empty value (long random string)
- [ ] Console command returns token: `document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')`
- [ ] API client imports work: `import ProductAPI from '...api/productApi.js'`
- [ ] `ProductAPI.getCsrfToken()` returns a string (not empty, not null)
- [ ] Network tab shows `x-csrf-token` header in API request
- [ ] Browser console has no errors (F12 → Console tab)

---

### Preventative Best Practices

To avoid the above errors during development, follow these guidelines:

#### 1. **CSRF Token Handling**

- ✅ Always include `<meta name="csrf-token">` in JSON format inside every layout's `<head>`
- ✅ Use the provided API client classes that have `getCsrfToken()` method
- ✅ Wrap API calls in `DOMContentLoaded` event listener
- ✅ Test CSRF token presence immediately after first page load
- ❌ Never hardcode token values; always retrieve from meta tag dynamically

#### 2. **API Route Registration**

- ✅ Register all upload endpoints in `routes/web.php` before building frontend
- ✅ Use RESTful naming: `POST /api/products`, `PUT /api/products/{id}`, `DELETE /api/products/{id}`
- ✅ Test routes with Postman or curl before integrating with frontend
- ✅ Include routes in this order: POST (create), GET (read), PUT (update), DELETE (remove)
- ❌ Don't change API paths after frontend is built without updating JavaScript imports

#### 3. **File Upload Handling**

- ✅ Always use `FormData` object for file uploads (never JSON with File objects)
- ✅ Don't manually set `Content-Type` header when using FormData (browser handles multipart boundary)
- ✅ Include CSRF token in both headers AND FormData `_token` field for maximum compatibility
- ✅ Test file uploads with various file sizes, formats (PNG, JPG, GIF, WebP)
- ✅ Set reasonable file size limits in validation (2-5MB for single files, check controller)
- ❌ Don't attempt to upload files larger than the limit defined in PHP

#### 4. **Error Handling & Logging**

- ✅ Use try-catch blocks in all async API calls
- ✅ Log network responses to browser console during development (`console.log(response)`)
- ✅ Check Laravel logs immediately when error occurs: `php artisan pail` or `Get-Content -Path storage\logs\laravel.log -Wait` (PowerShell)
- ✅ Use Toast notifications to show user-friendly error messages
- ✅ Return consistent JSON response structure from all API endpoints (success, data, errors, message fields)
- ❌ Don't rely on console.log alone; always check Network tab in DevTools

#### 5. **Testing Workflows**

- ✅ Start dev server with `composer run dev` before any feature work (ensures all services running)
- ✅ Test uploads after every backend controller change
- ✅ Verify API responses in Network tab before debugging JavaScript
- ✅ Clear browser cache (Ctrl+Shift+R) when testing code changes
- ✅ Test in multiple browsers (Chrome, Firefox, Safari) for compatibility
- ❌ Don't assume JavaScript is wrong until you've checked server logs and Network responses

#### 6. **File Storage & Permissions**

- ✅ Run `php artisan storage:link` after initial setup and before deployment
- ✅ Ensure `storage/app/public/` directory is writable: `icacls "storage" /grant:r "%USERNAME%":(OI)(CI)F /T`
- ✅ Verify uploaded files appear in `storage/app/public/{category}/` after upload
- ✅ Check that files are accessible via `/storage/...` URL in browser
- ❌ Don't store files in `public/` directly (use storage disk for consistency)

#### 7. **API Client Classes Best Practices**

- ✅ Always export API clients as singleton: `export default new ProductAPI()`
- ✅ Create separate API client class for each resource (Products, Materials, etc.)
- ✅ Implement getCsrfToken() method consistently across all clients
- ✅ Use async/await for all fetch calls (more readable than .then())
- ✅ Include error handling in every API method
- ✅ Return structured responses: `{success: true|false, data: {...}, errors: {...}, message: '...'}`
- ❌ Don't make API clients dependent on each other; keep them standalone and composable

---

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
| ----------- | --------- |
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
