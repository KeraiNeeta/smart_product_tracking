# Shop Guard

Shop Guard is a Laravel-based platform for monitoring security shutters and managing the users, devices, alerts, and notifications connected to them.

## Features

- Role-based portals for Super Admins, Company Admins, Dealers, Sub-Dealers, End Customers, and Support Operators.
- Hierarchical account and device visibility for dealer networks.
- Device monitoring, assignment transfers, schedules, alerts, and notification rules.
- Email, SMS, WhatsApp, and push notification provider settings.
- User profile and password management.
- Public self-registration for End Customers using an available device serial number; each customer can have one device, and other roles are created by authorized admins.
- The first active company is the default company for company admins, dealers, sub-dealers, End Customers, devices, and shops.
- End Customers can queue arm/disarm requests for devices assigned to their account from the portal dashboard.
- Local demo data for exploring the application.

## End User Android API

All mobile endpoints use the `/api/v1/end-user` prefix. Registration and login return a Sanctum bearer token; send it as `Authorization: Bearer {access_token}` on protected requests. Tokens expire after 30 days. Registration requires an available device serial number and creates an End Customer account linked to that device.

Import [the Postman collection](./postman/Shop%20Guard%20End%20User%20API.postman_collection.json) into Postman. Set the `base_url` collection variable to your Laravel server URL. Run Register End User or Login first; the collection saves the returned bearer token for the protected requests.

- `POST /register` — create an End Customer account (`full_name`, `email`, optional `mobile`, `serial_number`, `password`, `password_confirmation`, optional `device_name`).
- `POST /login` — authenticate an End Customer (`email`, `password`, optional `device_name`).
- `POST /logout` — revoke the current token.
- `GET /dashboard` — return the customer profile, assigned device and recent alerts.
- `GET /device` — return the assigned device and monitoring state.
- `POST /device/monitoring-state` — queue `ARMED` or `DISARMED` (`state`); returns HTTP 202 while the device confirmation is pending.
- `GET /notifications` and `POST /notifications/read` — list notifications and mark them read.
- `GET /profile` and `PATCH /profile` — read/update customer details.
- `PUT /profile/password` — change password (`current_password`, `new_password`, `new_password_confirmation`); existing API tokens are revoked.
- `POST /push-token` and `DELETE /push-token` — register/deactivate an Android or iOS push token.

Protected routes accept only active End Customer accounts. Login, registration and state-change requests are rate-limited.

Example requests:

```http
POST /api/v1/end-user/register
Content-Type: application/json

{
  "full_name": "Alex Customer",
  "email": "alex@example.com",
  "mobile": "+15551234567",
  "serial_number": "SG-DEMO-SERIAL-001",
  "password": "ChooseAStrongPassword123",
  "password_confirmation": "ChooseAStrongPassword123",
  "device_name": "android-phone"
}
```

On success, the response contains `data.token`, `data.token_type` (`Bearer`) and `data.user`. Use the token for subsequent calls:

```http
POST /api/v1/end-user/device/monitoring-state
Authorization: Bearer {access_token}
Content-Type: application/json

{"state": "ARMED"}
```

State changes return HTTP 202 while the device command awaits confirmation. Validation errors return HTTP 422 with an `errors` object; missing/invalid authentication returns HTTP 401. An End User can claim an available device by serial number only, so keep unassigned device serials private.

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
