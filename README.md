"php -r '
\$content = <<<\"MD\"
# Cardápio API

> **Multi-tenant restaurant management API** — Register, manage restaurants, categories, products, clients, and orders for your digital menu platform.

Cardápio is a **RESTful API** built with Laravel 13 that allows restaurant owners to register their business, manage their menu, clients, and orders. The system uses a **multi-tenant architecture** where each restaurant belongs to a tenant.

---

## Features — v0.0.1

### Auth

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | /api/v1/auth/register | ❌ | Register owner + tenant + restaurant |
| POST | /api/v1/auth/login | ❌ | Authenticate and receive API token |
| POST | /api/v1/auth/logout | ✅ | Revoke all active tokens |

### Restaurants (CRUD)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/restaurants | List restaurants |
| POST | /api/v1/restaurants | Create a restaurant |
| GET | /api/v1/restaurants/{restaurant} | Show restaurant |
| PUT/PATCH | /api/v1/restaurants/{restaurant} | Update restaurant |
| DELETE | /api/v1/restaurants/{restaurant} | Delete restaurant |

### Categories (CRUD)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/restaurants/{restaurant}/categories | List categories |
| POST | /api/v1/restaurants/{restaurant}/categories | Create a category |
| GET | /api/v1/restaurants/{restaurant}/categories/{category} | Show category |
| PUT/PATCH | /api/v1/restaurants/{restaurant}/categories/{category} | Update category |
| DELETE | /api/v1/restaurants/{restaurant}/categories/{category} | Delete category |

### Products (CRUD)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/restaurants/{restaurant}/products | List products |
| POST | /api/v1/restaurants/{restaurant}/products | Create a product |
| GET | /api/v1/restaurants/{restaurant}/products/{product} | Show product |
| PUT/PATCH | /api/v1/restaurants/{restaurant}/products/{product} | Update product |
| DELETE | /api/v1/restaurants/{restaurant}/products/{product} | Delete product |

### Clients (CRUD)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/restaurants/{restaurant}/clients | List clients |
| POST | /api/v1/restaurants/{restaurant}/clients | Create a client |
| GET | /api/v1/restaurants/{restaurant}/clients/{client} | Show client |
| PUT/PATCH | /api/v1/restaurants/{restaurant}/clients/{client} | Update client |
| DELETE | /api/v1/restaurants/{restaurant}/clients/{client} | Delete client |

### Orders (CRUD + Items + Modifiers)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/restaurants/{restaurant}/orders | List orders |
| POST | /api/v1/restaurants/{restaurant}/orders | Create order with items and modifiers |
| GET | /api/v1/restaurants/{restaurant}/orders/{order} | Show order |
| PUT/PATCH | /api/v1/restaurants/{restaurant}/orders/{order} | Update order |
| DELETE | /api/v1/restaurants/{restaurant}/orders/{order} | Delete order |

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13 |
| PHP | 8.4 |
| Database | MySQL (production), SQLite (testing) |
| Auth | Laravel Sanctum (token-based) |
| Testing | PHPUnit 12 |
| Code Style | Laravel Pint |

---

## Requirements

- PHP 8.3+
- Composer 2
- MySQL 8+ (or MariaDB 10+)
- SQLite 3 (for testing)

---

## Installation

```bash
git clone <repository-url> cardapio-api
cd cardapio-api
composer install
cp .env.example .env
# configure database in .env
php artisan key:generate
php artisan migrate
php artisan install:api
php artisan serve
```

---

## API Reference

### Register

```http
POST /api/v1/auth/register
Content-Type: application/json

{
    \"name\": \"María García\",
    \"email\": \"maria@emporio.com\",
    \"password\": \"SecurePass123!\",
    \"password_confirmation\": \"SecurePass123!\",
    \"restaurant_name\": \"Emporio de María\"
}
```

Response 201:

```json
{
    \"data\": {
        \"user\": {\"id\": 1, \"name\": \"María García\", \"email\": \"maria@emporio.com\", \"role\": \"owner\"},
        \"restaurant\": {\"id\": 1, \"name\": \"Emporio de María\", \"slug\": \"emporio-de-maria\"},
        \"tenant\": {\"id\": 1, \"name\": \"Emporio de María\"},
        \"token\": \"1|abc123...\"
    },
    \"message\": \"Registro exitoso. Bienvenido a Cardapio.\"
}
```

### Login

```http
POST /api/v1/auth/login
Content-Type: application/json

{
    \"email\": \"maria@emporio.com\",
    \"password\": \"SecurePass123!\"
}
```

Response 200:

```json
{
    \"data\": {
        \"user\": {\"id\": 1, \"name\": \"María García\", \"email\": \"maria@emporio.com\", \"role\": \"owner\"},
        \"token\": \"2|xyz789...\"
    }
}
```

### Logout

```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

Response 200:

```json
{\"message\": \"Logged out successfully.\"}
```

---

## Testing

```bash
php artisan test
php artisan test --filter=OrderTest
php artisan test --compact
```

Current coverage: **72 tests, 282 assertions** ✅

### Code Style

```bash
vendor/bin/pint
```

---

## Roadmap

### v0.0.1 ✅ (Current)
- Auth (register, login, logout)
- Restaurants CRUD
- Categories CRUD
- Products CRUD
- Clients CRUD
- Orders CRUD with items and modifiers

### v0.1.0 🔜
- Modifiers CRUD
- Pagination
- Database seeders
- API Resource classes
- Rate limiting

---

## License

Cardapio API is open-sourced software licensed under the MIT license.
MD;

f
