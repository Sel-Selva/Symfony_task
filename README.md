# Syphony Fund Transfer API

A Symfony-based secure fund transfer API built with MySQL and Redis for reliable persistence and caching.

## Features

- `POST /api/accounts` to create accounts with an initial balance
- `GET /api/accounts/{id}` to fetch account details and cached balance
- `POST /api/transfers` to transfer funds between accounts with row locking and transaction integrity
- Redis-backed cache for account balances and Redis lock storage for safe high-load transfers
- Integration tests covering account creation, transfer execution, and insufficient-funds handling

## Requirements

- PHP 8.2
- Composer
- Docker & Docker Compose (recommended for local MySQL + Redis)

## Setup

1. Install dependencies:

```bash
composer install
```

2. Start the infrastructure with Docker:

```bash
docker compose up -d
```

3. Create the MySQL database and schema:

```bash
docker compose exec app php bin/console doctrine:database:create --if-not-exists
```
```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

4. Run the app locally:

```bash
php -S 0.0.0.0:8000 -t public public/index.php
```

## API Endpoints

### Create account

POST `/api/accounts`

Request body:

```json
{
  "currency": "USD",
  "initialBalance": "150.00"
}
```

### Get account

GET `/api/accounts/{id}`

### Transfer funds

POST `/api/transfers`

Request body:

```json
{
  "fromAccountId": 1,
  "toAccountId": 2,
  "amount": "50.00",
  "currency": "USD"
}
```

## Testing

Run integration tests:

```bash
./vendor/bin/phpunit
```

## Notes

- Default local environment values are configured in `.env`
- The API uses transactional row locking and Redis locks to protect transfer integrity under concurrent load

## Time spent

Approximate time spent: ~2.5 hours

## AI tools used

- GitHub Copilot / Raptor mini (Preview)
- VS Code environment tools for file generation and validation
