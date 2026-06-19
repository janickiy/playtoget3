# PlayToGet

PlayToGet is a Laravel-based sports social network for people, teams, groups,
events, playgrounds, shops, and fitness clubs. The application combines a public
frontend, personal profiles, media galleries, messaging, community management,
content pages, feedback, and a Laravel-powered administration panel.

## Main Features

- User registration, login, profile editing, privacy settings, and online status.
- Social authentication with Google, Facebook, X, and LinkedIn.
- Friends, friend requests, personal messages, and push-style notifications.
- Teams and groups with members, administrators, invitations, blacklists, privacy
  rules, photo albums, video albums, feeds, and events.
- Events with participants, invitations, calendar integration, photo albums, and
  video albums.
- Playgrounds, shops, and fitness clubs with owners, covers, avatars, galleries,
  and recommended listings.
- Photo and video galleries with comments, likes, sharing, and modal previews.
- YouTube and Vimeo video link support.
- Site search across teams, groups, events, and sport blocks.
- Announcements, feedback form with reCAPTCHA v3, and content pages by slug.
- Admin panel for users, admins, settings, menus, content, communities, events,
  sport blocks, announcements, feedback, sport types, sport levels, and logs.

## Technology Stack

- PHP 8.4.1 or newer
- Laravel 13
- MySQL 8
- Redis and Memcached
- Nginx
- Vite
- Bootstrap/AdminLTE-based admin interface

The repository includes a Docker Compose setup for local development.

## Requirements

For Docker-based development:

- Docker
- Docker Compose

For local development without Docker:

- PHP 8.4.1+
- Composer
- Node.js and npm
- MySQL 8
- Redis, optional but recommended
- Memcached, optional

## Quick Start with Docker

Copy the environment file:

```bash
cp .env.example .env
```

Update the most important values in `.env`:

```dotenv
APP_NAME=PlayToGet
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=mysql_db
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=root
DB_PASSWORD=root_password

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

Start the containers:

```bash
docker compose up -d --build
```

Install backend dependencies:

```bash
docker compose exec app composer install
```

Install frontend dependencies and build assets:

```bash
docker compose exec app npm install
docker compose exec app npm run build
```

Generate the application key:

```bash
docker compose exec app php artisan key:generate
```

Run database migrations and seed default data:

```bash
docker compose exec app php artisan migrate --seed
```

Create the public storage symlink:

```bash
docker compose exec app php artisan storage:link
```

Clear and warm the framework cache:

```bash
docker compose exec app php artisan optimize:clear
```

The application will be available at:

```text
http://localhost:8080
```

## Optional Docker Tools

phpMyAdmin is included behind the `tools` profile:

```bash
docker compose --profile tools up -d phpmyadmin
```

Then open:

```text
http://localhost:8089
```

## Admin Panel

The admin panel is available at:

```text
http://localhost:8080/cp
```

Default seeded administrator:

```text
Login: admin
Password: 1234567
```

Change these credentials before using the project in production.

## Mail, reCAPTCHA, and Social Login

The default mail driver is `log`, so outgoing messages are written to Laravel
logs during local development. Configure a real mail transport for production:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"
```

Feedback uses Google reCAPTCHA v3. Set these values when captcha protection is
required:

```dotenv
RECAPTCHAV3_SITEKEY=
RECAPTCHAV3_SECRET=
RECAPTCHAV3_LOCALE=en
```

Social authentication is configured through Laravel Socialite. Add provider
credentials when enabling social login:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"

X_CLIENT_ID=
X_CLIENT_SECRET=
X_REDIRECT_URI="${APP_URL}/auth/x/callback"

LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=
LINKEDIN_REDIRECT_URI="${APP_URL}/auth/linkedin/callback"
```

## Useful Artisan Commands

Run migrations:

```bash
php artisan migrate
```

Run seeders:

```bash
php artisan db:seed
```

Clear caches:

```bash
php artisan optimize:clear
```

Run tests:

```bash
php artisan test
```

Format PHP code with Laravel Pint:

```bash
vendor/bin/pint
```

## Frontend Assets

Install Node dependencies:

```bash
npm install
```

Run Vite in development mode:

```bash
npm run dev
```

Build production assets:

```bash
npm run build
```

## Project Structure

```text
app/
  DTO/             Data transfer objects
  Enums/           Application status and label enums
  Helpers/         Shared helper classes
  Http/            Controllers, middleware, and form requests
  Models/          Eloquent models
  Repositories/    Database access and persistence logic
  Service/         Business services

database/
  migrations/      Database schema
  seeders/         Default admin, sport types, and sport levels

resources/
  views/           Blade templates

routes/
  web.php          Frontend routes
  cp.php           Admin panel routes

public/
  frontend/        Public frontend assets
```

## Production Notes

Before deploying to production:

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Use a strong `APP_KEY`.
- Configure a real mail transport.
- Configure trusted OAuth redirect URLs for all enabled social providers.
- Configure reCAPTCHA v3 keys.
- Point the web server document root to `public/`.
- Run `composer install --no-dev --optimize-autoloader`.
- Run `npm ci && npm run build`.
- Run `php artisan migrate --force`.
- Run `php artisan storage:link`.
- Run `php artisan config:cache`, `php artisan route:cache`, and
  `php artisan view:cache`.
- Make sure `storage/` and `bootstrap/cache/` are writable by the web server.

## Author and Copyright

© 2026 PlayToGet. Created by
[Alexander Yanitsky](https://janickiy.com).

## License

This project is proprietary unless a separate license file states otherwise.
