# Maqaad — Booking System

A Laravel 12 booking system using PostgreSQL and Docker, with support for concurrent bookings, wallet transactions, and idempotency.

## Requirements

- Docker
- Docker Compose

## Run the Project

Start the containers:

```bash
docker compose up -d --build
```

Install dependencies:

```bash
docker compose exec app composer install
```

Generate the application key:

```bash
docker compose exec app php artisan key:generate
```

Run migrations and seed the database:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

## Run Tests

The tests use a separate PostgreSQL database named `maqad_test`.

Run all booking tests:

```bash
docker compose exec app php artisan test --testdox tests/Feature/Booking
```

Run each concurrency test separately:

**T1 — Concurrent Booking / Capacity**

```bash
docker compose exec app php artisan test tests/Feature/Booking/T1ConcurrentBookingTest.php
```

**T2 — Overlapping Bookings**

```bash
docker compose exec app php artisan test tests/Feature/Booking/T2OverlappingBookingsTest.php
```

**T3 — Idempotency**

```bash
docker compose exec app php artisan test tests/Feature/Booking/T3IdempotencyTest.php
```

## Test Database

On a fresh Docker database volume, `maqad_test` is created by the PostgreSQL initialization script.

If the database volume already exists and the test database is missing, create it manually:

```bash
docker compose exec db psql -U maqad -d maqad -c "CREATE DATABASE maqad_test;"
```

**Note:** The test suite resets the test database using migrations and seeders. Do not point the test environment at a database containing important data.
