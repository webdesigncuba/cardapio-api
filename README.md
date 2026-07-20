# Cardápio API

> **Multi-tenant restaurant management API** — Register, manage restaurants, and build your digital menu platform.

Cardápio is a **RESTful API** built with Laravel 13 that allows restaurant owners to register their business, manage their profile, and prepare for digital menu and order management. The system uses a **multi-tenant architecture** where each restaurant belongs to a tenant, and each owner manages their own restaurants.

---

## Features

### ✅ Implemented

- **Auth**
  - `POST /api/v1/auth/register` — Register owner + tenant + restaurant in one step
  - `POST /api/v1/auth/login` — Authenticate and receive API token
  - `POST /api/v1/auth/logout` — Revoke all active tokens

- **Restaurants (CRUD)**
  - `GET /api/v1/restaurants` — List restaurants from your tenant
  - `POST /api/v1/restaurants` — Create a new restaurant (slug auto-generated)
  - `GET /api/v1/restaurants/{restaurant}` — Show restaurant details
  - `PUT/PATCH /api/v1/restaurants/{restaurant}` — Update restaurant
  - `DELETE /api/v1/restaurants/{restaurant}` — Delete restaurant

### 🔜 Upcoming

- Menu management (categories, products, modifiers)
- Order management (cart, checkout, status tracking)
- Staff roles and permissions
- Tenant/business settings

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Framework** | Laravel 13 |
| **PHP** | 8.3 |
| **Database** | MySQL (production), SQLite (testing) |
| **Auth** | Laravel Sanctum (token-based) |
| **Testing** | PHPUnit 12 |
| **Code Style** | Laravel Pint |

---

## Requirements

- PHP 8.3+
- Composer 2
- MySQL 8+ (or MariaDB 10+)

---

## Installation

```bash
# 1. Clone the repository
git clone <repository-url> cardapio-api
cd cardapio-api

# 2. Install PHP dependencies
composer install

# 3. Create environment file
cp .env.example .env

# 4. Configure database in .env
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=cardapio_api
#    DB_USERNAME=root
#    DB_PASSWORD=

# 5. Generate application key
php artisan key:generate

# 6. Create database and run migrations
php artisan migrate

# 7. Install Sanctum
php artisan install:api

# 8. Start the development server
php artisan serve
```

The API runs at `http://localhost:8000/api/v1/...`

---

## Quick Start

```bash
composer run setup
```

---

## API Reference

### Authentication

All auth endpoints are **public** (no token required), except logout.

#### Register

```http
POST /api/v1/auth/register
Content-Type: application/json

{
    "name": "María García",
    "email": "maria@emporio.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "restaurant_name": "Emporio de María"
}
```

**Response** `201 Created`

```json
{
    "data": {
        "user": {
            "id": 1,
            "name": "María García",
            "email": "maria@emporio.com",
            "role": "owner"
        },
        "restaurant": {
            "id": 1,
            "name": "Emporio de María",
            "slug": "emporio-de-maria"
        },
        "tenant": {
            "id": 1,
            "name": "Emporio de María"
        },
        "token": "1|abc123..."
    },
    "message": "Registro exitoso. Bienvenido a Cardapio."
}
```

#### Login

```http
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "maria@emporio.com",
    "password": "SecurePass123!"
}
```

**Response** `200 OK`

```json
{
    "data": {
        "user": {
            "id": 1,
            "name": "María García",
            "email": "maria@emporio.com",
            "role": "owner"
        },
        "token": "2|xyz789..."
    }
}
```

> Previous tokens are automatically revoked on each login.

#### Logout

```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

**Response** `200 OK`

```json
{
    "message": "Logged out successfully."
}
```

### Restaurants

All restaurant endpoints require **authentication** (`Authorization: Bearer {token}`).

#### List Restaurants

```http
GET /api/v1/restaurants
Authorization: Bearer {token}
```

#### Create Restaurant

```http
POST /api/v1/restaurants
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "La Casa de las Empanadas"
}
```

#### Show / Update / Delete Restaurant

```http
GET    /api/v1/restaurants/{restaurant}
PUT    /api/v1/restaurants/{restaurant}
DELETE /api/v1/restaurants/{restaurant}
```

---

## Architecture

### Directory Structure

```
app/
├── Domains/              ← Business logic (Actions, DTOs)
├── Http/
│   ├── Controllers/
│   │   └── Api/V1/
│   │       ├── Auth/
│   │       └── Restaurants/
│   └── Requests/
│       └── Api/V1/
│           ├── Auth/
│           └── Restaurant/
├── Models/
│   ├── User.php
│   ├── Tenant.php
│   └── Restaurant.php
```

### Multi-tenant Model

```
Tenant (1) → has many Users
           → has many Restaurants
```

Each owner registers and automatically creates their tenant and first restaurant. All subsequent resources are scoped to the owner's tenant.

### API Versioning

Endpoints are versioned via URL prefix: `/api/v1/...`

---

## Development

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=RegisterTest
php artisan test --filter=LoginTest
php artisan test --filter=LogoutTest
php artisan test --filter=RestaurantTest

# Run with compact output
php artisan test --compact
```

Tests use **SQLite in-memory** database — no external database required.

Current test coverage: **32 tests, 155 assertions** ✅

### Code Style

```bash
vendor/bin/pint
```

### Development Server

```bash
composer run dev
```

---

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_NAME` | Cardapio API | Application name |
| `APP_ENV` | local | Environment |
| `APP_URL` | http://localhost | Application URL |
| `DB_CONNECTION` | mysql | Database driver |
| `DB_HOST` | 127.0.0.1 | Database host |
| `DB_PORT` | 3306 | Database port |
| `DB_DATABASE` | cardapio_api | Database name |
| `DB_USERNAME` | root | Database user |
| `DB_PASSWORD` | | Database password |

---

## Contributing

1. Write tests before implementing new features (TDD)
2. Follow Laravel conventions and PSR-12 standards
3. Run `vendor/bin/pint` before committing
4. Ensure all tests pass: `php artisan test`

---

## License

Cardápio API is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
