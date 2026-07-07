# SHOP — Laravel E-Commerce Experience

Welcome to SHOP, a sleek and modern Laravel-based shopping platform built for smooth browsing, secure authentication, and seamless digital payments. Whether you are a shopper discovering products or an admin managing inventory, this project is designed to feel polished, practical, and ready for the next step.

## ✨ What This Project Offers

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

## 🚀 Features at a Glance

- Public product catalog for visitors
- Authenticated shopping experience for customers
- Admin dashboard capabilities for content and user management
- Payment callback handling for real-world gateway flows
- Structured Laravel architecture with models, controllers, events, listeners, and mail support

## 🛠️ Tech Stack

- Laravel 12
- PHP 8.5
- SQLite
- Blade templates
- Vite
- Composer
- Node.js / npm

## 📦 Installation

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

## 🧪 Verified Environment Notes

If you hit a PDO-related issue during local development, ensure PHP has the required PDO extension enabled. The project was verified to work with:

- PHP 8.5.7
- PDO enabled
- SQLite driver available

## 🧭 Project Structure

- app/ — Core application logic, controllers, models, and services
- config/ — Laravel configuration files
- database/ — Migrations, seeders, and factories
- resources/ — Blade views, CSS, and JavaScript
- routes/ — Web routes for storefront, auth, payments, and admin pages
- tests/ — Automated tests

## 👤 Default Roles

- Admin — can manage products and users
- User — can browse products and complete purchases

## 📌 Notes

This project is a great foundation for a modern online shop experience and can be extended with features like:

- inventory management
- order tracking
- coupons and discounts
- reviews and ratings
- shipping integrations

## ❤️ Contributing

Contributions are welcome. If you have ideas, fixes, or new features, feel free to open a pull request or share your improvements.
