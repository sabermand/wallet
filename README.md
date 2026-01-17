# Digital Wallet API (Laravel 12)

A backend-only RESTful API for a fintech-style digital wallet system, designed with correctness, security, and scalability in mind.

This project implements wallet management, money transfers, fraud detection, admin review workflows, and aggregated statistics with Redis caching.

---

##  Features

### Core Functionality
- Token-based authentication using Laravel Sanctum
- Multi-currency wallet support (TRY, USD, EUR)
- Transactions: Deposit, Withdrawal, Transfer, Refund
- Atomic balance updates with database transactions and row-level locking
- Idempotency support for transfer requests
- Fee calculation using Strategy Pattern

### Fraud & Risk Management
- Rule-based fraud detection (Pipeline / Chain of Responsibility)
- Automatic transaction flagging
- Manual review workflow (pending_review)
- Multiple configurable fraud rules

### Admin Capabilities
- Review, approve, reject suspicious transactions
- Aggregated statistics endpoint
- Redis-based caching for heavy queries

### Performance & Infrastructure
- Redis for cache and rate limiting
- Pagination on all list endpoints
- Artisan operational commands

### Testing
- Unit tests for fee logic
- Feature tests for transfer and auth flows

---

##  Tech Stack
- PHP 8.3+
- Laravel 12
- MySQL / MariaDB
- Redis
- PHPUnit

---

##  Requirements
- PHP 8.3+
- Composer
- MySQL or MariaDB
- Redis Server
- Git

---

##  Installation

```bash
git clone https://github.com/sabermand/wallet.git
cd wallet
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

---

##  Redis Setup

.env:
```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

---

##  Run
```bash
php artisan serve
```

---

##  Tests
```bash
php artisan test
```

---

##  Artisan Commands
```bash
php artisan stats:refresh-cache
```

---

## 📡 API Endpoints (v1) — With Request & Response Examples

### Global Headers
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {SANCTUM_TOKEN}
```

Transfer endpoints also require:
```
Idempotency-Key: {unique-key}
```

---

### Auth

#### Register
POST `/api/v1/auth/register`
```json
{
  "name": "Hamid",
  "email": "hamid@example.com",
  "password": "Pass1234!",
  "password_confirmation": "Pass1234!"
}
```

#### Login
POST `/api/v1/auth/login`
```json
{
  "email": "hamid@example.com",
  "password": "Pass1234!"
}
```

---

### Wallets

#### Create Wallet
POST `/api/v1/wallets`
```json
{
  "currency": "TRY"
}
```

#### List Wallets
GET `/api/v1/wallets`

#### Wallet Balance
GET `/api/v1/wallets/{wallet_id}/balance`

---

### Transactions

#### Deposit
POST `/api/v1/transactions/deposit`
```json
{
  "wallet_id": "WALLET_UUID",
  "amount": 500
}
```

#### Withdraw
POST `/api/v1/transactions/withdraw`
```json
{
  "wallet_id": "WALLET_UUID",
  "amount": 200
}
```

#### Transfer (Idempotent)
POST `/api/v1/transactions/transfer`
```json
{
  "source_wallet_id": "WALLET_UUID_1",
  "destination_wallet_id": "WALLET_UUID_2",
  "amount": 100
}
```

---

### Admin

#### Pending Review
GET `/api/v1/admin/transactions/pending-review`

#### Approve Transaction
POST `/api/v1/admin/transactions/{id}/approve`

#### Reject Transaction
POST `/api/v1/admin/transactions/{id}/reject`

#### Statistics (Cached)
GET `/api/v1/admin/statistics`

