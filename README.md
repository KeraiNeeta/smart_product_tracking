# Shop Guard

Shop Guard is a Laravel-based platform for monitoring security shutters and managing the users, devices, alerts, and notifications connected to them.

## Features

- Role-based portals for Super Admins, Company Admins, Dealers, Sub-Dealers, End Customers, and Support Operators.
- Hierarchical account and device visibility for dealer networks.
- Device monitoring, assignment transfers, schedules, alerts, and notification rules.
- Email, SMS, WhatsApp, and push notification provider settings.
- User profile and password management.
- Local demo data for exploring the application.

## Requirements

- PHP 8.2 or later and Composer.
- A database supported by Laravel (PostgreSQL, MySQL, or SQLite).
- Node.js and npm if building frontend assets.

## Local setup

1. Install PHP dependencies:

   ```bash
   composer install
   ```

2. Create the local environment file and application key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   On Windows Command Prompt, use `copy .env.example .env` instead of `cp`.

3. Configure the database connection in `.env`. The included example uses SQLite; create the database file if needed:

   ```bash
   php -r "touch('database/database.sqlite');"
   ```

4. Run migrations and seed the local demo data:

   ```bash
   php artisan migrate --seed
   ```

   The demo seeder only runs in the `local` environment.

5. Start the Laravel development server:

   ```bash
   php artisan serve
   ```

Open the URL shown by Artisan to use Shop Guard. Run the test suite with:

```bash
php artisan test
```
