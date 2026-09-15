# Concurrent-Safe Booking System

A production-quality, concurrent-safe booking system built with Laravel and React.

This project was built to demonstrate architectural understanding, strict concurrency handling, API design, and modern frontend practices.

## Overview
- **Backend**: Laravel 12 (API) + SQLite
- **Frontend**: React 18 + Vite + TypeScript + Tailwind CSS v4 + React Query
- **Authentication**: Laravel Sanctum (Token-based API auth)
- **Database**: SQLite (for ease of local setup & testing)

## Core Architecture & Booking Flow

The booking flow is designed to be highly responsive on the frontend while guaranteeing absolute consistency and strict limits on the backend.

1. **Optimistic UI (Frontend)**: When a user clicks "Book", React Query immediately (optimistically) decrements the stock on the screen to provide instantaneous feedback.
2. **Strict Validation (Backend)**: The request hits the Laravel API where `BookProductRequest` ensures the payload is valid.
3. **Pessimistic Locking**: Inside `BookingController`, a database transaction is opened and the target product row is exclusively locked using `lockForUpdate()`. 
4. **Stock Decrement**: The backend verifies stock is available, decrements it, creates a `Pending` order, and commits the transaction.
5. **Background Processing**: A `ProcessPaymentJob` is dispatched to the queue to simulate payment processing.
6. **Payment Outcome**: 
   - 80% of the time, the payment succeeds and the order is marked `Confirmed`.
   - 20% of the time, the payment fails. The job safely restores the product's stock and marks the order as `Failed`.
7. **Live Polling (Frontend)**: The "My Orders" dashboard on the frontend polls the backend every 3 seconds to reflect the real-time status changes.

---

## How This Solution Prevents Overselling Under Concurrent Load
When multiple users attempt to book the last available item at the exact same millisecond, race conditions can occur if the stock check and decrement aren't atomic. 

This project solves this strictly via **Pessimistic Locking**:
```php
$order = DB::transaction(function () use ($id, $quantity, $request) {
    $product = Product::lockForUpdate()->findOrFail($id);
    if ($product->stock < $quantity) {
        throw new \Exception('Not enough stock available.', 422);
    }
    $product->stock -= $quantity;
    $product->save();
    // ... create order
});
```
By wrapping the logic in `DB::transaction` and appending `->lockForUpdate()`, the database engine applies a write-lock to the specific product row. If 10 concurrent requests hit the controller, the database forces 9 of them to wait in line until the 1st request finishes its transaction. When the 2nd request is finally allowed to read the row, it sees the freshly updated stock, realizes it's empty, and cleanly fails with a 422 error.

### How Concurrency Was Verified
Because PHPUnit tests utilizing `RefreshDatabase` and SQLite `:memory:` run in isolated database environments, true external HTTP parallelism against the test database is difficult. 
However, concurrency safety was verified via:
1. **Automated Sequential Verification**: `ConcurrentBookingTest.php` strictly verifies the locking boundaries, ensuring that any attempt to exceed stock results in a 422 HTTP exception and stock strictly halts at 0.
2. **Manual Load Testing**: Utilizing concurrent `curl` scripts against the live `php artisan serve` physical database confirmed that overlapping HTTP requests are correctly queued by the database lock, successfully preventing overselling.

---

## Setup Instructions

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+

### 1. Backend Setup
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Run migrations and seed 8 realistic products
php artisan migrate:fresh --seed

# Start the local development server
php artisan serve
```

### 2. Queue Worker Setup (Required for Payment Processing)
In a **new terminal window**, start the queue worker:
```bash
cd backend
php artisan queue:work
```
*(If you do not run this, your orders will remain permanently stuck in the "Processing..." state).*

### 3. Frontend Setup
In a **new terminal window**:
```bash
cd frontend
npm install
npm run dev
```

Visit `http://localhost:5173` in your browser. You can click "Need an account? Sign up" to create a test user.

### 4. Running Automated Tests
```bash
cd backend
php artisan test
```

---

## Features Intentionally Omitted
As per the assignment scope, the following production-level features were intentionally omitted to maintain focus on the core concurrency architecture:
- **Multi-tenancy / RBAC**: No Admin dashboard or Role-Based Access Control exists.
- **Rate Limiting**: No API throttling is implemented.
- **Pagination**: The Product and Order lists fetch all records at once without cursor/offset pagination.
- **Redis / External Queue**: SQLite was used for the database and queues for ease of local evaluation without requiring Docker or Redis installations.
