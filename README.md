# Vigen

Vigen is a Laravel + React monolith for generating video content briefs. Users can register, log in, enter a topic and creative direction, then generate a saved output containing:

- a video script,
- a scene-by-scene storyboard,
- an optional Grok-generated video URL,
- export and download actions,
- searchable private generation history.

If Grok credentials are not configured or the upstream call fails, Vigen still saves a usable script and storyboard fallback.

## Tech Stack

- Laravel 12
- Laravel Breeze authentication
- Inertia.js + React 18
- Tailwind CSS
- MySQL by default
- Direct Grok web API integration inspired by the local `grokpi/` reference folder

## Requirements

- PHP 8.2+
- Composer
- Node.js 20.19+ or 22.12+
- npm
- MySQL 8+ or MariaDB 10.6+
- PHP `pdo_mysql` extension

> The app may build with older Node 22 versions, but Vite warns unless Node is `20.19+` or `22.12+`.

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database:

```sql
CREATE DATABASE vigen CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

If you prefer the MySQL CLI:

```bash
mysql -u root -p -e "CREATE DATABASE vigen CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Update `.env` with your local MySQL credentials, then run migrations.

Run migrations:

```bash
php artisan migrate
```

Build frontend assets:

```bash
npm run build
```

## Environment Setup

The default `.env.example` uses MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vigen
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

The application uses database-backed sessions, queues, and cache tables, so `php artisan migrate` must run successfully before logging in.

### Grok Configuration

Vigen can run without Grok credentials. In that mode, generations are saved as `script_only`.

To enable real video generation, set:

```env
GROK_SSO_TOKEN=
GROK_CF_CLEARANCE=
GROK_CF_COOKIES=
GROK_PROXY_URL=
GROK_VIDEO_MODEL=grok-imagine-1.0-video
GROK_VIDEO_RESOLUTION=480p
GROK_VIDEO_ASPECT_RATIO=16:9
GROK_VIDEO_PRESET=normal
```

Required:

- `GROK_SSO_TOKEN`: Grok account SSO token.

Optional:

- `GROK_CF_CLEARANCE`: Cloudflare clearance cookie value if your account/session requires it.
- `GROK_CF_COOKIES`: additional Cloudflare cookies.
- `GROK_PROXY_URL`: proxy URL for upstream requests.
- `GROK_VIDEO_RESOLUTION`: `480p` or `720p`.
- `GROK_VIDEO_ASPECT_RATIO`: default is `16:9`.
- `GROK_VIDEO_PRESET`: default is `normal`.

After changing env values, clear config cache if needed:

```bash
php artisan config:clear
```

## Running Locally

Run the Laravel server:

```bash
php artisan serve
```

Run Vite in another terminal for hot reload:

```bash
npm run dev
```

Open:

```text
http://127.0.0.1:8000
```

Register a user, then go to:

```text
/generate
```

## Main Features

- User authentication: register, login, logout, profile management.
- Video generator form: video type, topic, keywords, target audience, tone, duration, and template.
- Templates: marketing video, educational clip, social media reel, product demo, and announcement.
- Duration policy: scripts/storyboards can target 30s or 60s; real Grok video generation is capped at 30s.
- Fallback output: saves script/storyboard when Grok is unavailable.
- History: search, filter, preview, delete.
- Export: download script as `.txt`.
- Video download: downloads/proxies available generated video files, or redirects to the upstream URL when needed.

## Useful Routes

- `GET /generate`: generator page.
- `POST /generations`: create a generation.
- `GET /generations`: history page.
- `GET /generations/{generation}`: preview one generation.
- `DELETE /generations/{generation}`: delete a generation.
- `GET /generations/{generation}/export-script`: export script as `.txt`.
- `GET /generations/{generation}/download-video`: download available video.

All generation routes require authentication. Users can only access their own generations.

## Testing

Run the PHP test suite:

```bash
php artisan test
```

Run a production frontend build:

```bash
npm run build
```

The feature tests cover:

- auth route protection,
- generation validation,
- 60s requests capped to 30s for video,
- Grok success with a mocked service,
- script/storyboard fallback,
- user ownership checks,
- script export.

## Project Structure

Important application files:

- `app/Http/Controllers/VideoGenerationController.php`
- `app/Http/Requests/StoreVideoGenerationRequest.php`
- `app/Models/VideoGeneration.php`
- `app/Services/GrokVideoService.php`
- `app/Services/StoryboardComposer.php`
- `config/grok.php`
- `config/video_templates.php`
- `resources/js/Pages/Generator/Index.jsx`
- `resources/js/Pages/Generations/Show.jsx`
- `resources/js/Pages/History/Index.jsx`
- `database/migrations/*_create_video_generations_table.php`
- `tests/Feature/VideoGenerationTest.php`

The `grokpi/` folder is preserved as a reference implementation for Grok image/video API behavior. It is not required to run Vigen.

## Troubleshooting

### No application encryption key

Run:

```bash
php artisan key:generate
```

### Database table not found

Confirm the MySQL database exists and your `.env` credentials are correct, then run:

```bash
php artisan migrate
```

### Could not connect to MySQL

Check that MySQL is running and that `.env` matches your local setup:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vigen
DB_USERNAME=root
DB_PASSWORD=
```

Then clear cached config:

```bash
php artisan config:clear
```

### Grok output is always script-only

Check that `GROK_SSO_TOKEN` is set in `.env`, then run:

```bash
php artisan config:clear
```

If Grok rejects the request, the app saves the fallback and stores the error message on the generation preview.

### Vite cannot find npm or Node version is rejected

Install or upgrade Node.js to `20.19+` or `22.12+`, then rerun:

```bash
npm install
npm run build
```

## License

This project is built on Laravel and inherits the Laravel skeleton's MIT license defaults unless your project owner defines a different license.
