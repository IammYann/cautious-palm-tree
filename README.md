# SHOP — Laravel E-Commerce Experience

Welcome to SHOP, a sleek and modern Laravel-based shopping platform built for smooth browsing, secure authentication, and seamless digital payments. Whether you are a shopper discovering products or an admin managing inventory, this project is designed to feel polished, practical, and ready for the next step.

## What This Project Offers

- Beautiful Laravel storefront with a clean, modern experience
- User registration and login flows
- Product browsing and detail pages
- Admin product and user management
- Multiple payment integrations, including:
  - eSewa
  - Khalti
  - FonePay
- SQLite-backed local development setup
- A reliable Laravel 12 foundation with PHP 8.5 support

## Features at a Glance

- Public product catalog for visitors
- Authenticated shopping experience for customers
- Admin dashboard capabilities for content and user management
- Payment callback handling for real-world gateway flows
- Structured Laravel architecture with models, controllers, events, listeners, and mail support

## Tech Stack

- Laravel 12
- PHP 8.5
- SQLite
- Blade templates
- Vite
- Composer
- Node.js / npm

## Installation

1. Clone the repository
   ```bash
   git clone <your-repo-url>
   cd SHOP
   ```

2. Install PHP dependencies
   ```bash
   composer install
   ```

3. Install frontend dependencies
   ```bash
   npm install
   ```

4. Copy the environment file
   ```bash
   cp .env.example .env
   ```

5. Generate the application key
   ```bash
   php artisan key:generate
   ```

6. Run the database migrations
   ```bash
   php artisan migrate
   ```

7. Start the development server
   ```bash
   php artisan serve
   ```

8. In another terminal, build the frontend assets
   ```bash
   npm run dev
   ```

## Verified Environment Notes

If you hit a PDO-related issue during local development, ensure PHP has the required PDO extension enabled. The project was verified to work with:

- PHP 8.5.7
- PDO enabled
- SQLite driver available

## Project Structure

- app/ — Core application logic, controllers, models, and services
- config/ — Laravel configuration files
- database/ — Migrations, seeders, and factories
- resources/ — Blade views, CSS, and JavaScript
- routes/ — Web routes for storefront, auth, payments, and admin pages
- tests/ — Automated tests

## Default Roles

- Admin — can manage products and users
- User — can browse products and complete purchases

## Notes

This project is a great foundation for a modern online shop experience and can be extended with features like:

- inventory management
- order tracking
- coupons and discounts
- reviews and ratings
- shipping integrations

## Contributing

Contributions are welcome. If you have ideas, fixes, or new features, feel free to open a pull request or share your improvements.






























1. Payment idempotency guard (highest value) Your PurchaseController::success() can be called twice by eSewa. Add an atomic Redis check so a transaction_uuid is processed only once:

if (!Redis::set("esewa:{$transactionUuid}", 'processing', 'NX', 'EX', 3600)) {
    return redirect(...)->with('error', 'Already processed');
}
See PurchaseController.php:97.

2. Atomic stock / inventory counter (prevent overselling) Move quantity/stock to a Redis counter with INCRBY/DECRBY instead of risking race conditions on the DB during concurrent purchases.

3. Rate limiting (app/Http/Kernel.php or middleware) Throttle purchase/login attempts per user/IP via Redis (throttle:60,1 already Redis-backed). Cheap abuse protection.

4. Product view counts & "trending" (ProductController::show() app/Http/Controllers/ProductController.php:76) Use Redis::incr("views:product:{$id}") and a sorted set ZINCRBY trending 1 product:$id to show popular products.

5. Real-time admin notifications (you already have the stub) productpurchase event (./app/Events/productpurchase.php) currently broadcasts to log. Switch BROADCAST_DRIVER=redis + Laravel Echo + Predis pub/sub to push "new order" alerts live to an admin dashboard.

6. Cache tags for clean invalidation You manually Cache::forget(...) in 4 places (ProductController.php:56,111,148). Use Cache::tags(['products'])->remember(...) + Cache::tags('products')->flush() for one-line invalidation.

7. Throttle Limitting Using redis

## Work Completed

- **Core models:** Implemented `User`, `Product`, and `Order` models under `app/Models`.
- **Payment integrations:** Config and callback handling added for eSewa, Khalti, and FonePay (see `config/esewa.php`, `config/khalti.php`, `config/fonepay.php`).
- **Events & listeners:** Added the `productpurchase` event and listeners (see `app/Events/productpurchase.php` and `app/Listeners`).
- **Mails:** Notification and transactional mails included: `PurchaseNotificationMail`, `PurchaseThankYouMail`, `AdminDeliveryNotificationMail`, `BuyerDeliveryNotificationMail` (see `app/Mail`).
- **Background jobs:** `ProcessOrderRefundJob` implemented in `app/Jobs`.
- **Observers & policies:** `UserObserver` and `ProductPolicy` are present to handle model lifecycle and authorization.
- **Services:** `SafeCache` service added under `app/Services` for caching helpers.
- **Database:** Migrations and factories for users, products, and orders are included (`database/migrations`, `database/factories`).
- **Testing:** Test scaffolding exists in `tests/` with `TestCase.php`.
- **Frontend tooling:** Vite + npm integration with `vite.config.js` and `package.json`.
- **Dev tools & scripts:** `artisan`, `deploy.sh`, and helper scripts are in the repo root.
- **Developer notes & TODOs:** README includes important operational notes and TODOs (payment idempotency guard, Redis-backed stock counters, cache tagging, rate limits, real-time admin notifications). These are kept near the bottom for quick reference.

If you'd like, I can convert these into a formal changelog section with dates and PR references, or update specific items to reflect exact commit hashes. Tell me which format you prefer.