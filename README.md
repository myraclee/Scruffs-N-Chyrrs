# Scruffs-N-Chyrrs: Ordering and Inventory Tracking System

A modern, comprehensive web application for managing merchandise manufacturing services including stickers, prints, and pins. Built with **Laravel 12**, **Vite 7**, **Tailwind CSS 4 + Bootstrap 5**, and **Pest PHP** testing framework.

## Table of Contents

- [Quick Start](#quick-start)
- [Project Overview](#project-overview)
- [Prerequisites](#prerequisites)
- [Getting Started](#getting-started)
  - [Step 1: Clone the Repository](#step-1-clone-the-repository)
  - [Step 2: Run the Setup Script](#step-2-run-the-setup-script)
  - [Step 3: Configuration](#step-3-configuration)
- [Verification After Setup](#verification-after-setup)
- [Running the Application](#running-the-application)
- [Testing](#testing)
- [Code Quality](#code-quality--formatting)
- [Working with Images and File Uploads](#working-with-images-and-file-uploads)
- [Common Commands Reference](#common-commands-reference)
- [Project Structure](#project-structure)
- [Development Workflow](#development-workflow)
- [Environment-Specific Setup](#environment-specific-setup)
  - [Windows Developer Mode Setup](#windows-developer-mode-setup)
  - [macOS Setup](#macos-setup)
  - [Linux Setup](#linux-setup)
- [Database Setup](#database-setup)
- [Key Configuration Files](#key-configuration-files)
- [Infrastructure & Services](#infrastructure--services)
- [Current Architecture Overview](#current-architecture-overview)
- [API Endpoints](#api-endpoints)
- [Project Conventions & Patterns](#project-conventions--patterns)
- [Security & Authentication](#security--authentication)
- [Integration Points & Data Flow](#integration-points--data-flow)
- [Known Constraints & Caveats](#known-constraints--caveats)
- [Code Style & Conventions](#code-style--conventions)
- [Development Best Practices](#development-best-practices)
- [Troubleshooting & FAQ](#troubleshooting--faq)
- [Getting Help](#getting-help)
- [Contributing](#contributing-to-scruffs-n-chyrrs)
- [License](#project-license)

---

## Quick Start

**For experienced developers:**

```bash
composer run setup          # One-command setup (2-5 minutes)
composer run dev            # Start all servers
```

> Visit <http://127.0.0.1:8000/>

**For Windows users:** See [Windows Developer Mode Setup](#windows-developer-mode-setup) before running setup.

---

## Project Overview

**Scruffs-N-Chyrrs** is a monolithic Laravel 12 application following the **MVC (Model-View-Controller)** pattern with a modern hybrid frontend stack. The system manages:

- **Product Catalog Management** — Create and manage merchandise products
- **Pricing & Samples** — Configure pricing tiers and product samples with images
- **Image Upload & Storage** — Full-featured image management system with validation
- **User Authentication** — Multi-role authentication system (Customers, Owners/Admin)
- **Admin Dashboard** — Content management interface for product and image management
- **Email Notifications** — Multi-driver email system (AWS SES, Postmark, Resend)
- **Background Job Processing** — Queue system for asynchronous tasks
- **Responsive Design** — Works seamlessly on desktop, tablet, and mobile devices

### Technology Stack at a Glance

| Layer | Technology | Version |
| ------- | ----------- | --------- |
| **Language** | PHP | 8.2+ |
| **Framework** | Laravel | 12.x |
| **Frontend Build** | Vite | 7.x |
| **CSS Framework** | Tailwind CSS + Bootstrap | 4.0 + 5.x |
| **Node.js** | npm/Node | v18+ |
| **Database** | MySQL/MariaDB (Prod), SQLite (Dev) | 5.7+ |
| **Testing** | Pest PHP | 3.x |
| **Caching** | Redis | Latest |

---

## Prerequisites

Before starting, ensure you have the following installed on your system:

### Required Software

- **PHP 8.2+** — [Download XAMPP Here](https://www.apachefriends.org/download.html) (includes PHP, MySQL, Apache)
- **Database:** MySQL, MariaDB, or SQLite (Should be included with XAMPP)
- **Composer** — [Download Here](https://getcomposer.org/download/)
- **Node.js & NPM v18+** — [Download Here](https://nodejs.org/en/download)
- **Git** — [Download Here](https://git-scm.com/install/windows)

### Setup Verification

Run these commands to verify installations:

```bash
php --version          # Should show 8.2 or higher
composer --version     # Should show version 2.x
node --version         # Should show v18 or higher
git --version          # Should show version 2.x
```

### Post-Installation Configuration (XAMPP Users)

If you installed **XAMPP**, edit the PHP configuration file to increase upload limits for image files:

**Windows:** `C:\xampp\php\php.ini`  
**macOS:** `/Applications/XAMPP/xamppfiles/etc/php.ini`

Find and update these values to larger limits:

```ini
upload_max_filesize = 20M
post_max_size = 25M
```

Then restart Apache from XAMPP Control Panel.

### Windows-Specific Setup Issues?

**⚠️ Important for Windows Users:**

Windows 10/11 may require special setup for creating symbolic links (used for image file access). See [Windows Developer Mode Setup](#windows-developer-mode-setup) section below for detailed instructions on **Developer Mode**, **Administrator Privileges**, and **Troubleshooting**.

---

## Getting Started

### Step 1: Clone the Repository

```bash
git clone https://github.com/your-org/Scruffs-N-Chyrrs.git
cd Scruffs-N-Chyrrs
```

### Step 2: Run the Setup Script

```bash
composer run setup
```

**What this does:**

1. Installs PHP dependencies (Composer packages)
2. Creates `.env` configuration file from `.env.example`
3. Generates application encryption key (`APP_KEY`)
4. Runs database migrations to set up tables
5. Installs Node.js dependencies (npm packages)
6. **Creates symbolic link:** `public/storage` → `storage/app/public` (critical for images)
7. Builds frontend assets (Vite compilation)

**Expected Duration:** 2-5 minutes depending on your internet connection and system speed

**Troubleshooting:** If setup fails on the `storage:link` step on Windows, see [Windows Developer Mode Setup](#windows-developer-mode-setup).

### Step 3: Configuration

The setup automatically creates a `.env` file with sensible defaults. For most local development, no changes are needed. However, you can customize these common settings:

```env
# Database Connection
DB_CONNECTION=sqlite        # Options: sqlite, mysql
DB_DATABASE=scruffsnchyrrs
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=

# Application
APP_NAME="Scruffs & Chyrrs"
APP_ENV=local
APP_DEBUG=true

# Mail (for email notifications)
MAIL_MAILER=log             # Options: log, postmark, resend, ses

# Cloudflare Turnstile (for signup CAPTCHA)
CLOUDFLARE_TURNSTILE_SITEKEY=your-site-key-here
CLOUDFLARE_TURNSTILE_SECRETKEY=your-secret-key-here
```

---

## Verification After Setup

After setup completes, verify everything is working correctly:

### 1. Verify Symbolic Link

The setup creates a **symbolic link** that allows image files to be served to the web. Verify it exists by running these commands in your terminal in the project root directory `\Scruffs-N-Chyrrs>`:

**On Windows (Command Prompt):**

```cmd
dir /AL public
```

**Expected output:** You should see `<SYMLINK>` indicator next to `storage`:

```cmd
Directory of D:\path\to\Scruffs-N-Chyrrs\public

03/15/2026  10:30 AM    <SYMLINK>    storage [storage/app/public]
```

**On Windows (PowerShell):**

```powershell
Get-Item -Path public/storage | Select-Object LinkType, Target
```

**Expected output:**

```powershell
LinkType           Target
--------           ------
SymbolicLink       storage\app\public
```

**On macOS/Linux:**

```bash
ls -la public/ | grep storage
```

**Expected output:** `storage -> storage/app/public` (or similar arrow notation)

**If the symlink doesn't exist:** See [Troubleshooting: Images Don't Display](#troubleshooting-images-dont-display) section.

### 2. Verify Database

```bash
php artisan migrate:status
```

Should show all migrations as `Ran`:

```bash
Migration name ........................... Batch / Status
0001_01_01_000000_create_users_table .... 1 / Ran
0001_01_01_000001_create_cache_table .... 1 / Ran
(etc.)
```

If any show as `Pending`, run: `php artisan migrate`

### 3. Verify Assets (Optional)

```bash
test -f public/build/manifest.json && echo "✓ Assets OK" || echo "✗ Assets missing"
```

Compiled assets should exist at `public/build/manifest.json`.

---

## Running the Application

### Development Server (All Services Together)

Start all servers with one command:

```bash
composer run dev
```

This runs concurrently:

- **Laravel Web Server** — `php artisan serve` (accessible at `http://localhost:8000`)
- **Queue Listener** — `php artisan queue:listen --tries=1` (processes background jobs)
- **Vite Dev Server** — `npm run dev` (hot-reloads frontend assets)

**Then open in your browser:** `http://127.0.0.1:8000/`

To stop all services: Press `Ctrl+C` in the terminal.

### Running Individual Services (Advanced)

If you prefer separate terminal windows for development:

**Terminal 1: Web Server**

```bash
php artisan serve
```

> Server running at <http://127.0.0.1:8000> (you can specify a different port with `--port=8080`)

**Terminal 2: Queue Listener**

```bash
php artisan queue:listen --tries=1
```

> Listens for background jobs

**Terminal 3: Frontend Build Tool**

```bash
npm run dev
```

> Watches for changes and hot-reloads frontend assets

### Production Build

To build assets for production deployment:

```bash
npm run build
```

This creates optimized assets in `public/build/`.

---

## Testing

### Run All Tests

```bash
composer run test
```

Uses **Pest PHP** framework with an in-memory SQLite database. Tests are completely isolated and don't affect your local development database.

### Run Specific Test File

```bash
php artisan test tests/Feature/Auth/LoginTest.php
```

### Run Tests Matching a Pattern

```bash
php artisan test --filter=upload
```

### Generate Test Coverage

```bash
php artisan test --coverage
```

---

## Code Quality & Formatting

### Auto-Format Code (Recommended Before Commit)

```bash
./vendor/bin/pint
```

Automatically fixes PHP code style according to **PSR-12** standards. Modifies files in-place.

### Check Code Style Without Fixing

```bash
./vendor/bin/pint --test
```

Checks if code passes style rules without making changes.

---

## Working with Images and File Uploads

The application uses a **symbolic link** to serve uploaded images. Understanding this is crucial for file upload functionality:

### How Image Upload Works

```txt
User uploads image via admin interface
         ↓
Backend validates file (size, format, dimensions)
         ↓
Saves to: storage/app/public/home_images/ (or other category)
         ↓
Symbolic Link: public/storage → storage/app/public
         ↓
Web accessible at: /storage/home_images/{filename}
         ↓
Browser displays: ✓ Image loads correctly
```

### File Categories

Images are organized by category:

- **`storage/app/public/home_images/`** — Homepage carousel/banner images
- **`storage/app/public/product_samples/`** — Product sample images
- **Other categories** — As defined by your product types

### Testing Image Upload

After setup completes and symlink is verified:

1. Start the development server:

   ```bash
   composer run dev
   ```

2. Navigate to the admin panel → **Content Management**

3. Upload a test image via "Home Page Images" section

4. Verify:
   - ✅ File appears in `storage/app/public/home_images/`
   - ✅ Database record is created
   - ✅ Image displays on the website
   - ✅ URL follows pattern: `http://127.0.0.1:8000/storage/home_images/filename.jpg`

### If Images Don't Display

See [Troubleshooting: Images Don't Display](#troubleshooting-images-dont-display) section below.

---

## Common Commands Reference

| Command | Purpose | Details |
| --------- | --------- | --------- |
| `composer run setup` | Initial project setup | Installs deps, creates .env, runs migrations, builds assets |
| `composer run dev` | Start all dev servers | Runs Laravel, Queue, Vite concurrently |
| `composer run test` | Run test suite | Uses Pest PHP with SQLite |
| `php artisan serve` | Start Laravel server only | Runs at <http://127.0.0.1:8000> |
| `php artisan queue:listen` | Start queue worker | Processes background jobs |
| `npm run dev` | Start Vite dev server | Hot-reloads frontend assets |
| `npm run build` | Build assets for production | Creates optimized files in public/build/ |
| `php artisan migrate` | Run all pending migrations | Creates database tables |
| `php artisan migrate:fresh` | Reset database from scratch | Deletes all data and rebuilds tables |
| `php artisan migrate:status` | Show migration status | Lists which migrations have run |
| `php artisan tinker` | Interactive PHP shell | Test code interactively |
| `php artisan storage:link` | Create storage symlink manually | Useful if automatic setup failed |
| `./vendor/bin/pint` | Format code automatically | Fixes PSR-12 style issues |
| `php artisan optimize:clear` | Clear all caches | Flushes views, routes, config caches |
| `git pull origin main` | Get latest code | Updates local repository |

---

## Project Structure

```txt
Scruffs-N-Chyrrs/
├── app/                              # Application logic
│   ├── Http/
│   │   ├── Controllers/              # Request handlers
│   │   └── Middleware/               # Request middleware layers
│   ├── Models/                       # Eloquent ORM models
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── ProductSample.php
│   │   ├── HomeImage.php
│   │   ├── Material.php
│   │   └── ...
│   ├── Mail/                         # Email classes
│   │   └── PasswordResetCode.php
│   └── Providers/                    # Service providers
│       └── AppServiceProvider.php
├── config/                           # Configuration files
│   ├── app.php                       # Application settings
│   ├── database.php                  # Database connections
│   ├── mail.php                      # Email configuration
│   ├── services.php                  # External service configs (AWS, Postmark, Slack)
│   ├── auth.php                      # Authentication settings
│   ├── cache.php                     # Cache driver configuration
│   └── ...
├── database/                         # Database schema and data
│   ├── migrations/                   # Schema definitions
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 2026_03_02_000000_create_products_table.php
│   │   └── ...
│   ├── factories/                    # Model factories for testing
│   │   └── UserFactory.php
│   └── seeders/                      # Database seeders
│       └── DatabaseSeeder.php
├── resources/                        # Frontend assets
│   ├── css/                          # Stylesheets
│   │   ├── app.css                   # Main CSS (Tailwind imports)
│   │   ├── universal.css             # Global fonts and styles
│   │   ├── signup.css                # Signup page styles
│   │   └── ...
│   ├── js/                           # JavaScript files
│   │   ├── app.js                    # Main JS entry (Bootstrap, app init)
│   │   ├── api/                      # API client classes
│   │   │   ├── productApi.js
│   │   │   ├── homeImageApi.js
│   │   │   └── ...
│   │   ├── pages/                    # Page-specific JavaScript
│   │   └── utils/                    # Utility functions
│   ├── fonts/                        # Custom fonts
│   │   ├── Coolvetica.woff2
│   │   ├── SuperDream.woff2
│   │   └── ...
│   └── views/                        # Blade templates
│       ├── layouts/                  # Layout templates
│       │   ├── customer_layout.blade.php
│       │   └── owner_layout.blade.php
│       ├── customer/                 # Customer-facing pages
│       ├── owner/                    # Owner/admin pages
│       ├── page_parts/               # Reusable components
│       │   ├── header.blade.php
│       │   ├── footer.blade.php
│       │   └── ...
│       └── auth/                     # Authentication pages
├── routes/                           # Route definitions
│   ├── web.php                       # HTTP route definitions
│   └── console.php                   # Artisan command definitions
├── storage/                          # File storage and logs
│   ├── app/
│   │   └── public/                   # Uploaded files (web-accessible via symlink)
│   │       ├── home_images/          # Homepage images
│   │       ├── product_samples/      # Product sample images
│   │       └── ...
│   ├── logs/                         # Application logs
│   │   └── laravel.log
│   └── framework/                    # Framework caches
├── tests/                            # Test files (Pest PHP)
│   ├── Feature/                      # Feature tests (end-to-end)
│   ├── Unit/                         # Unit tests
│   ├── TestCase.php                  # Base test case
│   └── Pest.php                      # Pest configuration
├── public/                           # Web root (served by web server)
│   ├── index.php                     # Laravel entry point
│   ├── storage/                      # Symlink to storage/app/public (CRITICAL FOR IMAGES)
│   ├── build/                        # Compiled assets (Vite output)
│   │   ├── manifest.json
│   │   └── assets/
│   ├── images/                       # Static images
│   │   ├── brand_elements/
│   │   └── website_elements/
│   └── robots.txt
├── bootstrap/                        # Framework bootstrap files
│   ├── app.php
│   ├── providers.php
│   └── cache/
├── vendor/                           # Composer dependencies (auto-generated)
├── node_modules/                     # npm dependencies (auto-generated)
├── .env                              # Environment configuration (auto-created)
├── .env.example                      # Environment template
├── .gitignore                        # Git ignore rules
├── composer.json                     # PHP dependencies and scripts
├── package.json                      # Node.js dependencies
├── vite.config.js                    # Frontend build configuration
├── vite.config.php                   # Laravel Vite plugin config
├── phpunit.xml                       # Test configuration
├── artisan                           # Laravel command line tool
└── README.md                         # This file
```

---

## Development Workflow

### 1. Check for New Changes

```bash
git pull origin main
```

### 2. Install Any New Dependencies

```bash
composer install
npm install
```

### 3. Run Migrations if Database Schema Changed

```bash
php artisan migrate
```

### 4. Start Development Servers

```bash
composer run dev
```

### 5. Make Your Changes

- Edit files in `app/`, `resources/`, etc.
- **Frontend changes auto-reload** (Vite Hot Module Replacement)
- **Backend changes require page refresh** or restart

### 6. Test Your Changes

```bash
composer run test
```

### 7. Format Code Before Commit

```bash
./vendor/bin/pint
```

### 8. Commit and Push

```bash
git add .
git commit -m "Feature: Add product pricing tiers"
git push origin your-branch-name
```

### 9. Create Pull Request

Push to GitHub and create PR for code review.

---

## Environment-Specific Setup

### Windows Developer Mode Setup

Windows 10/11 users may encounter issues creating symbolic links. This section provides complete setup guidance.

#### Prerequisites for Windows

Before running setup, ensure you have:

- **XAMPP** installed.
- **PHP 8.2+ (XAMPP)** installed and accessible in PATH.
- **MySQL/MariaDB (XAMPP)** installed, started, and running for database.
- **Composer** installed globally.
- **Node.js & NPM** installed.
- **Git** installed and configured.
- **Administrator privileges OR Developer Mode enabled** (see below).

#### Option 1: Enable Windows Developer Mode (Recommended)

This allows creating symlinks without administrator privileges:

**Steps:**

1. Open **Settings** → **Update & Security** → **For developers**
2. Under "Developer Mode" section, toggle the switch **ON**
3. Windows will install necessary components (may take 1-2 minutes)
4. **Restart your computer**
5. After restart, you can create symlinks as a regular user

**After enabling:**

```cmd
composer run setup
```

> Done! No special configuration needed.

Should work without requiring administrator privileges.

#### Option 2: Run Setup as Administrator (If Developer Mode Unavailable)

If you can't enable Developer Mode:

1. Right-click **Command Prompt** or **PowerShell**
2. Select **"Run as Administrator"**
3. Navigate to project directory and run:

   ```cmd
   composer run setup
   ```

#### Option 3: Manual Symlink Creation (If Automatic Setup Failed)

If the setup script's symlink creation fails, create it manually:

**Using Command Prompt (requires admin):**

```cmd
rmdir public\storage 2>nul
mklink /D public\storage storage\app\public
```

**Using PowerShell (Windows 10 Build 14393+ with Developer Mode):**

```powershell
New-Item -ItemType SymbolicLink -Path "public\storage" -Target "storage\app\public"
```

#### Verifying Symlink on Windows

After setup, verify the symlink was created correctly:

**Method 1: Command Prompt**

```cmd
dir /AL public
```

**Expected output:** You should see `<SYMLINK>` indicator next to `storage`:

```cmd
Directory of D:\path\to\Scruffs-N-Chyrrs\public

03/15/2026  10:30 AM    <SYMLINK>    storage [storage/app/public]
               0 bytes
```

**Method 2: PowerShell**

```powershell
Get-Item -Path public/storage | Select-Object LinkType, Target
```

**Expected output:**

```powershell
LinkType           Target
--------           ------
SymbolicLink       storage\app\public
```

#### Windows Troubleshooting

##### Problem: "Access Denied" During Setup

**Symptoms:** Setup fails with error message during `storage:link` step.

**Solutions:**

1. **Enable Developer Mode** (see Option 1 above), then rerun:

   ```cmd
   composer run setup
   ```

2. **Run as Administrator** (see Option 2 above)

3. **Check antivirus software** — Some antivirus programs block symlink creation. Temporarily disable or add exception for your project directory.

4. **Create symlink manually** (see Option 3 above)

##### Problem: "public/storage" Already Exists as a Directory

**Symptoms:** Setup fails or symlink wasn't created, but `public/storage` folder exists.

**Cause:** A previous developer or setup attempt may have created `public/storage` as a regular directory instead of a symlink.

**Solution:**

1. Delete the incorrect directory:

   ```cmd
   rmdir public\storage /s /q
   ```

2. Clear npm cache and reinstall (if needed):

   ```cmd
   npm cache clean --force
   npm install
   ```

3. Rerun setup:

   ```cmd
   composer run setup
   ```

4. Verify symlink was created:

   ```cmd
   dir /AL public
   ```

##### Problem: "The system cannot find the file specified" Error

**Symptoms:** Error appears when trying to create symlink or run Laravel commands.

**Solution:**

1. Ensure correct project directory:

   ```cmd
   cd path\to\Scruffs-N-Chyrrs
   ```

   > pwd # or 'cd' in PowerShell to show current directory

2. Verify `storage/app/public` directory exists:

   ```cmd
   dir storage\app\public
   ```

3. If it doesn't exist, run migrations first:

   ```cmd
   php artisan migrate
   ```

4. Then create the symlink:

   ```cmd
   php artisan storage:link
   ```

#### Using Git with Symlinks on Windows

**Important:** The `public/storage` symlink is **NOT** committed to Git (it's in `.gitignore`). This is intentional:

- Each developer's system creates the link appropriately
- Prevents merge conflicts across different systems
- Symlinks work differently on Windows/Mac/Linux
- The `.gitignore` prevents accidental commits

When you switch branches or pull code, the symlink remains valid unless you manually delete it.

---

### macOS Setup

Apple's UNIX-based system handles symlinks natively. Setup should work seamlessly:

```bash
composer run setup
```

> Done! No special configuration needed.

**Verify symlink:**

```bash
ls -la public/ | grep storage
```

Expected: Shows `storage -> storage/app/public`

---

### Linux Setup

Linux handles symlinks natively. Setup should work seamlessly:

```bash
composer run setup
```

> Done! No special configuration needed.

**Verify symlink:**

```bash
ls -la public/ | grep storage
```

Expected: Shows `storage -> storage/app/public`

---

## Database Setup

### Using SQLite (Default for Development)

SQLite requires no installation and works out-of-box:

```bash
php artisan migrate
```

Database file: `database/database.sqlite`

**Advantages:**

- Zero configuration
- No server needed
- Perfect for local development
- Included with PHP

**Disadvantages:**

- Not suitable for production
- Limited concurrency support
- Slower with large datasets

### Switch to MySQL

Edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scruffsnchyrrs
DB_USERNAME=root
DB_PASSWORD=
```

**Create the database** (if not exists):

```bash
mysql -u root -p
CREATE DATABASE scruffsnchyrrs;
EXIT;
```

Then run migrations:

```bash
php artisan migrate
```

### Reset Database

**⚠️ Caution:** This deletes all data!

```bash
php artisan migrate:fresh
```

To reset and seed with test data:

```bash
php artisan migrate:fresh --seed
```

### Check Migration Status

```bash
php artisan migrate:status
```

Shows which migrations have run and which are pending.

---

## Key Configuration Files

| File | Purpose |
| ------ | --------- |
| `config/app.php` | Base application settings (name, timezone, locale, debug mode) |
| `config/database.php` | Database connections (MySQL, SQLite, Redis) and cache settings |
| `config/mail.php` | Email driver configuration (Postmark, Resend, SES, LOG) |
| `config/services.php` | External service credentials (AWS, Postmark, Slack, Cloudflare) |
| `config/auth.php` | Authentication guards and password reset settings |
| `config/cache.php` | Cache driver (Redis, File, Database) and TTL settings |
| `config/queue.php` | Queue driver configuration (Database, SQS, Redis) |
| `config/session.php` | Session driver (File, Database, Redis) and timeout |
| `.env` | Environment-specific variables (created during setup) |
| `vite.config.js` | Frontend build tool configuration |
| `composer.json` | PHP dependencies and custom scripts |
| `package.json` | Node.js dependencies |

---

## Infrastructure & Services

The application integrates with several external systems and services:

### Data Persistence

- **Primary Database:** MySQL/MariaDB (production), SQLite (development)
  - Stores: Users, Products, Samples, Images, Materials, Pricing, etc.
  - Connection configured via `DB_*` variables in `.env`

- **Session & Cache Storage:** Redis
  - Configured via `REDIS_*` variables in `.env`
  - Used by Laravel for session management and cache layer
  - Optional but recommended for production scaling

### Queue System

- **Current (Development):** Database-driven queues
  - Run in development with: `php artisan queue:listen`
  - Processes background jobs synchronously in development

- **Production-Ready Options:**
  - Redis queues (recommended for performance)
  - SQS (Amazon Simple Queue Service)
  - Database queues (simpler but slower)

### Email Services

Multiple drivers available, configured via `MAIL_MAILER` in `.env`:

- **Development:** `log` driver (emails written to logs)
- **Production:** `ses` (AWS SES), `postmark`, or `resend`
  - Requires API keys in `config/services.php`

### External Integrations

- **AWS Services** — S3 (file storage), SES (email), IAM (authentication)
- **Slack** — System alerts and notifications via webhook
- **Cloudflare Turnstile** — CAPTCHA protection on signup form

---

## Current Architecture Overview

### Database Models

The application uses Eloquent ORM with the following primary models:

- **User** — Customer and owner accounts
- **Product** — Merchandise product definitions
- **ProductSample** — Product sample images
- **HomeImage** — Homepage carousel images
- **Material** — Product materials (polymers, metals, etc.)
- **ProductMaterial** — Pivot table for Product ↔ Material relationships
- **ProductPriceImage** — Pricing tier images

### API Endpoints

The application provides JSON APIs for:

| Endpoint | Method | Purpose |
| ---------- | -------- | --------- |
| `/api/products` | GET | List all products |
| `/api/products/{id}` | GET | Get single product |
| `/api/products` | POST | Create new product |
| `/api/products/{id}` | PUT | Update product |
| `/api/products/{id}` | DELETE | Delete product |
| `/api/home-images` | GET | List homepage images |
| `/api/home-images` | POST | Upload homepage image |
| `/api/product-samples` | GET | List product samples |
| `/api/product-samples` | POST | Upload product sample |

All POST/PUT/DELETE endpoints require CSRF token.

---

## Project Conventions & Patterns

### Frontend Strategy: Hybrid CSS Architecture

The application uses a specific frontend strategy combining multiple CSS frameworks:

1. **Tailwind CSS 4** — Initialized in `resources/css/app.css`
   - Main utility-first framework
   - Imported via `@import "tailwindcss";`
   - Configured with `@theme` directive

2. **Bootstrap 5** — JavaScript components imported in `resources/js/app.js`
   - Used for interactive elements (Modals, Dropdowns, Alerts)
   - Imported via: `import 'bootstrap';`

3. **Custom CSS** — Page-specific stylesheets
   - Loaded via Vite: `@vite('resources/css/page_name.css')`
   - Injected in Blade templates via `@section('page_css')`

**⚠️ Warning:** Be careful to avoid CSS class name conflicts between Tailwind and Bootstrap. Use custom class names (e.g., `.nav_container`, `.footer_tophalf`) rather than relying on framework utility classes for critical UI.

### Route & Controller Pattern

Currently, routes are defined as **closures** in `routes/web.php` returning views directly.

**Future Refactoring Target:** As logic grows, migrate closures to:

- Invokable controllers (single-action controllers)
- Resource controllers (full CRUD operations)
- Service classes (business logic extraction)

### Asset Management

**Images:**

- Static images: `public/images/` (categorized: `brand_elements/`, `website_elements/`)
- Uploaded images: `storage/app/public/` (web-accessible via symlink)

**Fonts:**

- Custom fonts: `resources/fonts/` (Coolvetica, SuperDream, etc.)
- Loaded via `@font-face` in `universal.css`

**CSS/JS:**

- Imported via Vite: `@vite('resources/css/...')` and `@vite('resources/js/...')`
- Hot-reloads during development via Vite HMR

---

## Security & Authentication

### CSRF Token Protection

All state-changing requests (POST, PUT, DELETE) require a CSRF token:

**In Blade templates:**

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

Required in the `<head>` of every layout file.

**In JavaScript API calls:**

```javascript
const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
};

async function uploadImage(formData) {
    const response = await fetch('/api/products', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            Accept: 'application/json'
        },
        body: formData
    });
    return response.json();
}
```

### API Client Pattern

API clients provide consistent CSRF token handling:

```javascript
// resources/js/api/productApi.js
class ProductAPI {
    constructor() {
        this.baseUrl = '/api/products';
    }

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');
    }

    async getAll() {
        const response = await fetch(this.baseUrl);
        return response.json();
    }

    async create(formData) {
        const response = await fetch(this.baseUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.getCsrfToken(),
                Accept: 'application/json'
            },
            body: formData
        });
        return response.json();
    }
}

export default new ProductAPI();
```

### Authentication Guards

- **web** guard — Session-based authentication (default)
- Custom middleware for role-based access control (Customer vs. Owner)

---

## Integration Points & Data Flow

### Data Flow Overview

```txt
End User
    ↓
[Scruffs-N-Chyrrs Application]
    ↓
    ├→ [Main Database (MySQL)]
    │   └ Stores: Users, Products, Samples, Images, Materials, etc.
    │
    ├→ [Redis Cache/Sessions]
    │   └ Caches: User sessions, frequently accessed data
    │
    ├→ [Email Service] (Postmark/Resend/AWS SES)
    │   └ Sends: Account confirmations, password resets, notifications
    │
    ├→ [Slack Webhook]
    │   └ Posts: System alerts, error logs, important events
    │
    └→ [File Storage (Local/S3)]
        └ Stores: Uploaded images, files
```

### Configuration for External Services

**AWS SES (Email):**

- Requires: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`
- Configured in: `config/services.php`

**Postmark/Resend (Email):**

- Requires: API key in `POSTMARK_TOKEN` or `RESEND_API_KEY`
- Set `MAIL_MAILER=postmark` or `MAIL_MAILER=resend` in `.env`

**Slack (Notifications):**

- Requires: Webhook URL in `LOG_SLACK_WEBHOOK_URL`
- Configured in: `config/logging.php`

---

## Known Constraints & Caveats

### CSS Class Conflicts

Using Bootstrap JS with Tailwind CSS can cause class name collisions. The project mitigates this by:

- Using custom class names (`.nav_container`, `.footer_tophalf`) instead of framework utility classes
- Maintaining separate CSS files per page
- **Increasing maintenance overhead** — Custom CSS requires more manual management

**Future improvement:** Consider moving to a pure utility-first approach (Tailwind only) or Component-based architecture.

### Database Port Configuration

The `.env` defaults `DB_PORT` to `3307` (not standard 3306), suggesting the development environment may be running MySQL alongside another instance.

**Verify your configuration:**

Test MySQL connection with:

```bash
mysql -h 127.0.0.1 -P 3307 -u root -p
```

If your MySQL is on standard port 3306, update `.env`:

```env
DB_PORT=3306
```

### Mail Driver Default

The default mailer is set to `log` in `config/mail.php` and production should use:

```env
MAIL_MAILER=ses    # or postmark, resend
```

Update `MAIL_MAILER` in `.env` before deployment or email testing.

---

## Code Style & Conventions

### PHP Code Style

The project uses **Laravel Pint** for PSR-12 compliance:

```bash
./vendor/bin/pint
```

**Key conventions:**

- Spaces over tabs (4 spaces per indent)
- Class names: `PascalCase` (e.g., `ProductController`)
- Method names: `camelCase` (e.g., `getProductPrice()`)
- Constants: `UPPER_CASE` (e.g., `MAX_UPLOAD_SIZE`)
- Properties: `camelCase` (e.g., `$userName`)

### JavaScript/TypeScript Code Style

Not currently linted. Recommended conventions:

- **Variable names:** `camelCase` (e.g., `isLoading`, `handleClick`)
- **Constants:** `UPPER_CASE` (e.g., `API_BASE_URL`)
- **Class/Component names:** `PascalCase` (e.g., `ProductForm`)
- **File names:** `kebab-case` (e.g., `product-api.js`) or `camelCase` (e.g., `productApi.js`)

### Blade Template Conventions

- **Layout files:** `layouts/` directory with `*_layout.blade.php` naming
- **Reusable components:** `page_parts/` directory with descriptive names
- **Page templates:** Organized by feature/role (e.g., `customer/pages/`, `owner/pages/`)
- **Spacing:** Consistent indentation with 4-space tabs

### File Naming

| File Type | Convention | Example |
| ----------- | ----------- | --------- |
| Models | PascalCase | `Product.php`, `HomeImage.php` |
| Controllers | PascalCase + "Controller" | `ProductController.php` |
| Migrations | Timestamp + snake_case | `2026_03_02_000000_create_products_table.php` |
| Views | snake_case + `.blade.php` | `product_list.blade.php` |
| CSS files | snake_case | `universal.css`, `signup.css` |
| JS files | camelCase (preferred) or kebab-case | `productApi.js` or `product-api.js` |

---

## Development Best Practices

### Adding New Features (Step-by-Step)

#### 1. Create Database Migration

```bash
php artisan make:migration create_your_features_table
```

Edit the migration in `database/migrations/` and define your schema:

```php
Schema::create('your_features', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->timestamps();
});
```

Run the migration:

```bash
php artisan migrate
```

#### 2. Create Model

```bash
php artisan make:model YourFeature
```

Add relationships and accessors in `app/Models/YourFeature.php`:

```php
class YourFeature extends Model
{
    protected $fillable = ['name', 'description'];
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
```

#### 3. Create API Routes

Edit `routes/web.php`:

```php
Route::get('/api/your-features', function () {
    return response()->json(YourFeature::all());
});

Route::post('/api/your-features', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string'
    ]);
    
    $feature = YourFeature::create($validated);
    return response()->json(['success' => true, 'data' => $feature], 201);
});
```

#### 4. Create API Client

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

    async create(formData) {
        const response = await fetch(this.baseUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.getCsrfToken(),
                Accept: 'application/json'
            },
            body: formData
        });
        return response.json();
    }
}

export default new YourFeatureAPI();
```

#### 5. Create Blade Template

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

#### 6. Create Page JavaScript

Create `resources/js/customer/pages/your_feature.js`:

```javascript
import YourFeatureAPI from '../../api/yourFeatureApi.js';
import { showToast } from '../../utils/toast.js';

async function loadFeatures() {
    try {
        const result = await YourFeatureAPI.getAll();
        if (result.success) {
            console.log(result.data);
        }
    } catch (error) {
        showToast('Error loading features', 'error');
    }
}

document.addEventListener('DOMContentLoaded', loadFeatures);
```

#### 7. Create Styles

Create `resources/css/customer/pages/your_feature.css` for page-specific styling.

### General Best Practices

1. **Always include CSRF token in POST/PUT/DELETE requests** to API endpoints
2. **Use async/await** instead of `.then()` chains for cleaner code
3. **Validate input** on both frontend (UX) and backend (security)
4. **Create migrations** for any database changes (never modify DB directly)
5. **Write tests** for critical business logic (use `composer run test`)
6. **Use meaningful commit messages** when pushing to git
7. **Keep functions small and focused** — single responsibility principle
8. **Document complex logic** with comments, especially in JavaScript APIs

---

## Troubleshooting & FAQ

### Quick Reference Table

| Issue | Root Cause | Quick Solution |
| ------- | ----------- | --------- |
| **Images don't display** | Missing or broken symbolic link | Run `php artisan storage:link` or see Windows setup section |
| **CSRF token not found** | Meta tag missing in layout | Add `<meta name="csrf-token" content="{{ csrf_token() }}">` to `<head>` |
| **Failed to upload image: Unexpected token '<'** | Server returning HTML instead of JSON (CSRF error) | Ensure CSRF token is sent in request headers |
| **API returns 404** | Route not registered in routes/web.php | Check endpoint path and verify route exists |
| **Vite not rebuilding** | Dev server not running | Run `composer run dev` or `npm run dev` |
| **Database migrations fail** | Wrong credentials or DB doesn't exist | Verify DB_* variables in .env match your MySQL instance |
| **"Module not found" in JS** | Import path incorrect | Use `./` for relative imports and verify file exists |
| **Styling not applying** | CSS file not imported via Vite | Use `@vite('resources/css/...')` in Blade template |
| **Bootstrap modal not working** | Bootstrap JS not initialized | Ensure `import 'bootstrap';` in resources/js/app.js |
| **Setup fails on Windows** | Symbolic link creation requires admin | Enable Developer Mode or run as Administrator |

### Troubleshooting: Images Don't Display

#### Understanding the Issue

Images upload successfully to the database and file system, but show as broken on the website. This is typically caused by a missing or broken symbolic link.

#### Verification Steps

1. **Check if symlink exists:**

   **Windows:**

   ```cmd
   dir /AL public
   ```

   Should show `<SYMLINK>` next to storage.

   **macOS/Linux:**

   ```bash
   ls -la public/ | grep storage
   ```

   Should show arrow: `storage -> storage/app/public`

2. **Check if files actually exist:**

   ```bash
   ls storage/app/public/home_images/
   ```

   Should list uploaded image files.

3. **Test image URL in browser:**

   Visit: `http://localhost:8000/storage/home_images/filename.jpg`

   If you see 404, the symlink is broken or missing.

#### Solutions

**Solution 1: Recreate the Symlink**

```bash
php artisan storage:link
```

Then verify:

> Windows

```cmd
dir /AL public
```

> macOS/Linux

```bash
ls -la public/ | grep storage
```

**Solution 2: Manual Symlink Creation**

If above fails, create manually:

**Windows (Command Prompt):**

```cmd
rmdir public\storage 2>nul
mklink /D public\storage storage\app\public
```

**macOS/Linux:**

```bash
rm -f public/storage
ln -s storage/app/public public/storage
```

**Solution 3: Check File Permissions**

Ensure storage directory is writable:

**Windows:**

```cmd
icacls "storage\app\public" /grant:r "%USERNAME%":(OI)(CI)F /T
```

**macOS/Linux:**

```bash
chmod -R 755 storage/app/public
```

**Solution 4: Verify Database Records**

Check that images were actually saved to database:

```bash
php artisan tinker
```

Then:

```php
>>> use App\Models\HomeImage;
>>> HomeImage::all();
=> Collection { ... }
```

---

### Troubleshooting: API Upload Errors

#### Error: "Failed to upload image: Unexpected token '<', "<!DOCTYPE"... is not valid JSON"

This error occurs when JavaScript tries to parse a JSON response but receives HTML instead (usually an error page).

#### Root Causes

1. **CSRF Token Missing in Headers**
   - JavaScript not sending `X-CSRF-TOKEN` header
   - Token retrieval from meta tag failing
   - Solution: Verify `getCsrfToken()` method is returning a value

2. **User Not Authenticated**
   - Session expired or user logged out
   - Route requires authentication
   - Solution: Check session/auth status in browser console

3. **API Route Not Registered**
   - Typo in endpoint URL
   - Route not defined in `routes/web.php`
   - Solution: Verify route exists with `grep "POST.*api/products" routes/web.php`

4. **Server Error (500)**
   - Database connection failed
   - File storage permissions issue
   - Laravel exception thrown
   - Solution: Check `storage/logs/laravel.log`

#### Diagnosis

1. **Check Network Tab (F12 → Network):**
   - Look for failed API request
   - Click to inspect
   - Check **Response** tab for HTML or JSON

2. **Check Request Headers:**
   - Verify `x-csrf-token` header is present
   - Verify header value is not empty

3. **Check Laravel Logs:**

    Real-time logs

   ```bash
   php artisan pail
   ```

    Or tail the log file

   ```bash
   tail -f storage/logs/laravel.log
   ```

#### Solutions

**Solution 1: Ensure CSRF Token in Headers**

```javascript
async createProduct(formData) {
    const response = await fetch('/api/products', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': this.getCsrfToken(),  // ← CRITICAL
            Accept: 'application/json'
        }
    });
    return await response.json();
}
```

**Solution 2: Add Token to FormData as Backup**

```javascript
async createProduct(formData) {
    if (this.getCsrfToken() && !formData.has('_token')) {
        formData.append('_token', this.getCsrfToken());
    }
    
    const response = await fetch('/api/products', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': this.getCsrfToken(),
            Accept: 'application/json'
        }
    });
    return await response.json();
}
```

**Solution 3: Verify Routes Exist**

Check if route is registered in `routes/web.php`

```bash
grep -n "POST.*api/products" routes/web.php
```

If not found, add to `routes/web.php`:

```php
Route::post('/api/products', function (Request $request) {
    // Handle upload
});
```

---

### Troubleshooting: CSRF Token Not Found

#### Understanding the Error

JavaScript cannot retrieve the CSRF token from the page's HTML. The application needs the token for POST requests, but the meta tag is either missing or the JavaScript runs before the DOM loads.

#### Root Causes

1. **Meta Tag Missing from Layout**
   - `<meta name="csrf-token">` not in `<head>` section
   - Layout not properly extended by view

2. **JavaScript Runs Before DOM Loads**
   - Token retrieval code executes before page fully loads
   - Race condition between script execution and DOM parsing

3. **Incorrect Selector**
   - Attribute name or value doesn't match actual HTML

4. **Browser Cache**
   - Stale cached page preventing fresh token generation

#### Verification Steps

1. **Check Page Source for Meta Tag:**

   Right-click page → **View Page Source** (Ctrl+U)
   Search for `csrf-token`

   Expected: `<meta name="csrf-token" content="eyJpdiI6I...`

2. **Test Selector in Browser Console:**

   ```javascript
   document.querySelector('meta[name="csrf-token"]')
       ?.getAttribute('content')
   ```

   Should return a long random string, not `null`.

3. **Check for Console Errors:**

   F12 → **Console** tab for any JavaScript errors.

#### Solutions

**Solution 1: Add Meta Tag to Layouts**

Add to `<head>` section of all layout files:

```html
<!-- resources/views/layouts/customer_layout.blade.php -->
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">  <!-- ← ADD THIS -->
    <title>Scruffs & Chyrrs</title>
    <!-- other meta tags -->
</head>
```

Do this for:

- `resources/views/layouts/customer_layout.blade.php`
- `resources/views/owner/layouts/owner_layout.blade.php`
- Any other layout files

**Solution 2: Wrap Code in DOMContentLoaded**

Ensure token retrieval happens after DOM loads:

```javascript
document.addEventListener('DOMContentLoaded', () => {
    const token = document.querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
    
    if (!token) {
        console.error('CSRF token not found');
        return;
    }
    
    // Safe to use token now
    performUpload(token);
});
```

**Solution 3: Use Safe Retrieval with Optional Chaining**

```javascript
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

**Solution 4: Clear Browser Cache and Test**

- Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)
- Or test in **Incognito/Private mode** (no cache)
- If it works in Incognito, clear normal browser cache

**Solution 5: Restart Laravel Application**

Generate new tokens:

```bash
composer run dev
```

Stop (Ctrl+C) and restart. Forces Laravel to reinitialize and regenerate all CSRF tokens.

---

### Common Issues & Solutions

#### Issue: `composer: command not found`

**Solution:**

Install Composer globally or use the installer:

> Visit <https://getcomposer.org/download/>

```bash
php composer.phar install  # Use local composer
```

#### Issue: Database Connection Error

**Solution:**

Verify `.env` file and database is running:

```bash
php artisan tinker  # Test connection interactively
```

Check that:

- `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` are correct in `.env`
- Database server is running
- Database specified in `DB_DATABASE` exists

#### Issue: npm/node Version Issues

**Solution:**

Update Node.js to v18+:

> Visit <https://nodejs.org/en/download> for installer

```bash
node --version  # Check current version
```

#### Issue: Port 8000 Already in Use

**Solution:**

Use a different port:

```bash
php artisan serve --port=8080
```

#### Issue: Queue Not Processing Jobs

**Solution:**

Queue listener must be running in separate terminal:

```bash
php artisan queue:listen --tries=1
```

The `composer run dev` command starts this automatically.

#### Issue: Assets Not Compiling

**Solution:**

Rebuild with:

```bash
npm run build
```

Or start Vite dev server:

```bash
npm run dev
```

---

### Preventative Best Practices

To avoid common issues during development:

#### 1. CSRF Token Handling

- ✅ Always include `<meta name="csrf-token">` in every layout's `<head>`
- ✅ Use provided API client classes with `getCsrfToken()` method
- ✅ Wrap API calls in `DOMContentLoaded` event
- ✅ Test token presence immediately after page load
- ❌ Never hardcode token values

#### 2. API Route Registration

- ✅ Register upload endpoints in `routes/web.php` before building frontend
- ✅ Use RESTful naming: `POST /api/products`, `PUT /api/products/{id}`, `DELETE /api/products/{id}`
- ✅ Test routes with Postman before integrating with frontend
- ❌ Don't change API paths after frontend is built without updating JavaScript

#### 3. File Upload Handling

- ✅ Always use `FormData` object for file uploads (never JSON with File objects)
- ✅ Don't manually set `Content-Type` header when using FormData
- ✅ Include CSRF token in both headers AND FormData `_token` field
- ✅ Test uploads with various file sizes and formats
- ✅ Set reasonable file size limits in validation (2-5MB)
- ❌ Don't attempt to upload files larger than limit

#### 4. Error Handling & Logging

- ✅ Use try-catch blocks in all async API calls
- ✅ Log network responses to console during development
- ✅ Check Laravel logs immediately when error occurs: `php artisan pail`
- ✅ Use Toast notifications for user-friendly errors
- ✅ Return consistent JSON from all API endpoints
- ❌ Don't rely on console.log alone

#### 5. Testing Workflows

- ✅ Start dev server with `composer run dev` before feature work
- ✅ Test uploads after every backend change
- ✅ Verify API responses in Network tab before debugging JavaScript
- ✅ Clear browser cache (Ctrl+Shift+R) when testing code changes
- ✅ Test in multiple browsers (Chrome, Firefox, Safari)
- ❌ Don't assume JavaScript is wrong until checking server logs

#### 6. File Storage & Permissions

- ✅ Run `php artisan storage:link` after initial setup
- ✅ Ensure `storage/` directory is writable
- ✅ Verify uploaded files appear in `storage/app/public/` after upload
- ✅ Check files are accessible via `/storage/...` URL
- ❌ Don't store files in `public/` directly

#### 7. API Client Classes

- ✅ Always export as singleton: `export default new ProductAPI()`
- ✅ Create separate API client for each resource
- ✅ Implement `getCsrfToken()` consistently across clients
- ✅ Use async/await for all fetch calls
- ✅ Include error handling in every method
- ✅ Return structured responses with `success`, `data`, `errors`, `message` fields
- ❌ Don't make API clients dependent on each other

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
5. **Test API endpoints** directly with Postman or curl

---

## Getting Help

If you encounter issues not covered in this documentation:

1. **Check the Troubleshooting & FAQ section above** — Most common issues are documented
2. **Review Laravel logs:** `storage/logs/laravel.log` or `php artisan pail`
3. **Run `php artisan tinker`** — Interactive PHP shell for testing
4. **Check browser DevTools:**
   - F12 → Network tab for API responses
   - F12 → Console tab for JavaScript errors
5. **Verify prerequisites** — Ensure all software versions match requirements
6. **Post to team communication channel** — Share error messages and steps taken

---

## Contributing To Scruffs-N-Chyrrs

1. Clone the repository.
2. Create a new feature branch (`git checkout -b feature/amazing-feature`).
3. Ensure tests pass before committing: `composer run test`
4. Format code: `./vendor/bin/pint`
5. Open a Pull Request with a clear description of changes.

### Before Committing

**Always run these before pushing code:**

> Format code

```bash
./vendor/bin/pint
```

> Run tests

```bash
composer run test
```

> Check git status

```bash
git status
```

---

## Project License

The Scruffs-N-Chyrrs project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## Laravel Framework

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

### About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing)
- [Powerful dependency injection container](https://laravel.com/docs/container)
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent)
- Database agnostic [schema migrations](https://laravel.com/docs/migrations)
- [Robust background job processing](https://laravel.com/docs/queues)
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting)

Laravel is accessible, powerful, and provides tools required for large, robust applications.

### Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

### Laravel Sponsors

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

### Contributing To Laravel

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

### Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

---

## Useful Resources

- **Laravel Documentation:** [laravel.com/docs](https://laravel.com/docs/)
- **Pest Testing:** [pestphp.com](https://pestphp.com/)
- **Vite Build Tool:** [vitejs.dev](https://vitejs.dev/)
- **Tailwind CSS:** [tailwindcss.com](https://tailwindcss.com/)
- **Bootstrap:** [getbootstrap.com](https://getbootstrap.com/)
- **Composer:** [getcomposer.org](https://getcomposer.org/)
- **Node.js/npm:** [nodejs.org](https://nodejs.org/)
