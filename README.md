# Vigen

Vigen is a Laravel + React monolith for generating video content briefs. Users can register, log in, enter a topic and creative direction, then generate a saved output containing:

- a video script,
- a scene-by-scene storyboard,
- an optional Grok-generated video URL,
- export and download actions,
- searchable private generation history.

If Grok credentials are not configured or the upstream call fails, Vigen still saves a usable script and storyboard fallback.
Generated media assets are stored on Cloudflare R2 by default.

## Tech Stack

- Laravel 12
- Laravel Breeze authentication
- Inertia.js + React 18
- Tailwind CSS
- PostgreSQL by default
- Direct Grok web API integration inspired by the local `grokpi/` reference folder
- Cloudflare R2 (S3-compatible object storage) for generated media

## Requirements

- PHP 8.2+
- Composer
- Node.js 20.19+ or 22.12+
- npm
- PostgreSQL 15+
- PHP `pdo_pgsql` extension

> The app may build with older Node 22 versions, but Vite warns unless Node is `20.19+` or `22.12+`.

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create a PostgreSQL database:

```sql
CREATE DATABASE vigen;
```

If you prefer the PostgreSQL CLI:

```bash
psql -U postgres -h 127.0.0.1 -p 5432 -c "CREATE DATABASE vigen;"
```

Update `.env` with your local PostgreSQL credentials, then run migrations.

Run migrations:

```bash
php artisan migrate
```

Build frontend assets:

```bash
npm run build
```

## Environment Setup

The default `.env.example` uses PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=vigen
DB_USERNAME=postgres
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

### Generated Media Storage (Cloudflare R2)

Set these variables to store generated image/video assets on Cloudflare R2:

```env
VIDEO_GENERATION_ASSET_DISK=r2
VIDEO_GENERATION_ASSET_FALLBACK_DISK=
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_DEFAULT_REGION=auto
R2_BUCKET=
R2_ENDPOINT=https://<accountid>.r2.cloudflarestorage.com
R2_URL=
R2_USE_PATH_STYLE_ENDPOINT=true
```

Notes:

- `VIDEO_GENERATION_ASSET_DISK` should be `r2` for strict R2 storage.
- Keep `VIDEO_GENERATION_ASSET_FALLBACK_DISK` empty to disable fallback storage.
- `R2_URL` is optional (for custom/public URL usage).
- The app still performs FFmpeg merging locally by staging segment files in a temporary workspace before uploading the merged output back to the configured asset disk.

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
- Video download/preview: serves stored media from the configured asset disk (R2 by default), or falls back to upstream URL when needed.

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
- script export,
- merge compatibility for segments stored on non-local disks (for example R2).

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

Confirm the PostgreSQL database exists and your `.env` credentials are correct, then run:

```bash
php artisan migrate
```

### Could not connect to PostgreSQL

Check that PostgreSQL is running and that `.env` matches your local setup:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=vigen
DB_USERNAME=postgres
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

### R2 storage errors (write/read/check existence)

If you see errors like `UnableToCheckFileExistence` or media cannot be downloaded:

1. Verify R2 env values (`R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_ENDPOINT`).
2. Ensure `VIDEO_GENERATION_ASSET_DISK=r2`.
3. Clear cached config:

```bash
php artisan config:clear
```

4. Retry generation and inspect `storage/logs/laravel.log` for asset disk warnings.

### FFmpeg merge cannot find segment files

The merge step requires local file paths. Vigen stages segment files from the configured disk (including R2) into a local temporary merge directory before invoking FFmpeg. If merge still fails:

- confirm FFmpeg is installed and reachable via `FFMPEG_BINARY`,
- verify segment uploads succeeded on the configured asset disk,
- check `storage/logs/laravel.log` for segment load and merge errors.

### Vite cannot find npm or Node version is rejected

Install or upgrade Node.js to `20.19+` or `22.12+`, then rerun:

```bash
npm install
npm run build
```

## License

This project is built on Laravel and inherits the Laravel skeleton's MIT license defaults unless your project owner defines a different license.
