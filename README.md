# Order & Payment Management API

A RESTful API built with Laravel 12 for managing orders and payments, featuring JWT authentication and support for multiple payment gateways.

---

## Requirements

- PHP 8.2+
- Composer
- MySQL
- Laravel 12

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/fatmaelkassaby2003/Tocaan.git
cd order-payment-api
```

### 2. Install dependencies

```bash
composer install
```

### 3. Set up environment

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

### 4. Configure your database in `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=Tocaan
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Run migrations

```bash
php artisan migrate
```

### 6. Start the server

```bash
php artisan serve
```

---

## API Documentation (Postman)

A complete Postman collection is included at the project root:

```
Tocaan_API.postman_collection.json
```

**To use it:**
1. Open Postman
2. Click **Import** → select the JSON file
3. Set the `base_url` variable to `http://localhost:8000/api`
4. Run **Login** first — the token is auto-saved for all other requests

The collection is organized by:
- **Auth** — Register, Login, Logout, Me
- **Orders** — Create, List, Filter, View, Update, Delete
- **Payments** — Process (3 gateways), List, Filter by Order, View

Each endpoint includes **success and error response examples**.

---

## API Endpoints

### Authentication

| Method | Endpoint           | Description        | Auth Required |
|--------|--------------------|--------------------|---------------|
| POST   | /api/auth/register | Register new user  | No            |
| POST   | /api/auth/login    | Login              | No            |
| POST   | /api/auth/logout   | Logout             | Yes           |
| GET    | /api/auth/me       | Get current user   | Yes           |

### Orders

| Method | Endpoint         | Description                        | Auth Required |
|--------|------------------|------------------------------------|---------------|
| GET    | /api/orders      | List orders (filter by status)     | Yes           |
| POST   | /api/orders      | Create a new order                 | Yes           |
| GET    | /api/orders/{id} | View a specific order              | Yes           |
| PUT    | /api/orders/{id} | Update an order                    | Yes           |
| DELETE | /api/orders/{id} | Delete an order (no payments only) | Yes           |

### Payments

| Method | Endpoint                | Description             | Auth Required |
|--------|-------------------------|-------------------------|---------------|
| GET    | /api/payments           | List all payments       | Yes           |
| POST   | /api/payments/process   | Process a payment       | Yes           |
| GET    | /api/payments/{id}      | View a specific payment | Yes           |

---

## Authentication

All protected routes require a Bearer token in the Authorization header:

```
Authorization: Bearer <your-token>
```

You get the token after registering or logging in.

---

## Business Rules

- Payments can only be processed for orders with **confirmed** status.
- Orders **cannot be deleted** if they have associated payments.
- Users can only view and manage their own orders and payments.

---

## Payment Gateways

Currently supported gateways:

- `credit_card`
- `paypal`
- `stripe`

### Gateway Configuration

Each gateway reads its credentials from `.env` via `config/services.php`:

```env
# Credit Card
CREDIT_CARD_MERCHANT_ID=your-merchant-id
CREDIT_CARD_API_KEY=your-api-key

# PayPal
PAYPAL_CLIENT_ID=your-client-id
PAYPAL_SECRET=your-secret

# Stripe
STRIPE_API_KEY=your-api-key
STRIPE_SECRET=your-secret
```

### Adding a New Payment Gateway

The system uses the **Strategy Pattern**, so adding a new gateway requires only two steps:

**Step 1** — Create a new class in `app/Services/Payment/Gateways/`:

```php
<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGatewayInterface;

class CashGateway implements PaymentGatewayInterface
{
    public function process(array $paymentData): array
    {
        return [
            'success'        => true,
            'transaction_id' => 'CASH-' . uniqid(),
            'message'        => 'Cash payment recorded successfully',
            'gateway'        => $this->getName(),
            'amount'         => $paymentData['amount'],
        ];
    }

    public function getName(): string
    {
        return 'cash';
    }
}
```

**Step 2** — Register it in `app/Services/Payment/PaymentGatewayManager.php`:

```php
use App\Services\Payment\Gateways\CashGateway;

private function registerDefaultGateways(): void
{
    $this->register(new CreditCardGateway());
    $this->register(new PaypalGateway());
    $this->register(new StripeGateway());
    $this->register(new CashGateway()); // just add this
}
```

**Step 3** — Add the new method to the payments migration enum and re-migrate.

That's all. No changes needed anywhere else.

---

## Running Tests

Set up the testing environment:

```bash
cp .env.example .env.testing
php artisan key:generate --env=testing
php artisan jwt:secret --env=testing
```

Update `DB_CONNECTION` in `.env.testing`:

```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

Run the tests:

```bash
php artisan test
```

Expected output: **42 tests, 99 assertions — all passing.**

---

## Project Structure

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           ├── AuthController.php
│           ├── OrderController.php
│           └── PaymentController.php
├── Models/
│   ├── User.php
│   ├── Order.php
│   ├── OrderItem.php
│   └── Payment.php
└── Services/
    └── Payment/
        ├── PaymentGatewayInterface.php
        ├── PaymentGatewayManager.php
        └── Gateways/
            ├── CreditCardGateway.php
            ├── PaypalGateway.php
            └── StripeGateway.php
