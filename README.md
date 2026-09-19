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

### Concurrency Verification
**Challenge:** Standard PHPUnit tests execute sequentially, making it impossible to genuinely simulate overlapping concurrent requests in a single thread, and testing parallelism against an in-memory database (`:memory:`) isolates connections.
**Solution:**
We engineered a **genuine automated parallel test** (`ConcurrentBookingTest.php`):
1. **Physical Testing Database**: Configured the test environment to use a dedicated physical SQLite file (`testing.sqlite`) so multiple background processes can hit the exact same database simultaneously.
2. **True OS-Level Parallelism**: Used Laravel's `Process` component to spawn **10 independent PHP processes** simultaneously via a dedicated background Artisan command (`app:concurrent-book`).
3. **Atomic Safety**: To guarantee safety across all DB engines (including SQLite which ignores `FOR UPDATE` read locks), the booking logic uses true atomic decrements (`UPDATE products SET stock = stock - 1 WHERE stock > 0`), paired with `lockForUpdate` for robust systems like MySQL/PostgreSQL.
4. **Verification**: The test successfully asserts that exactly 5 out of 10 concurrent processes succeed, the remaining 5 fail cleanly with 422 errors, and the final stock halts strictly at 0, successfully preventing overselling.

---

## Setup Instructions
To run this project locally, ensure you have PHP, Composer, Node.js, and a local SQLite environment set up.

1. **Clone the repository:**
   ```bash
   git clone https://github.com/viveksukhadia/chetak-booking-system.git
   cd chetak-booking-system/backend
   ```
2. **Install Backend Dependencies:**
   ```bash
   composer install
   ```
3. **Set up Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. **Prepare Database:**
   ```bash
   # Make sure database.sqlite exists or is configured
   touch database/database.sqlite
   php artisan migrate --seed
   ```
5. **Install Frontend Dependencies & Build Assets:**
   ```bash
   npm install
   npm run build
   ```
6. **Start the Development Server:**
   ```bash
   # In terminal 1: Start Laravel server
   php artisan serve

   # In terminal 2: Start Queue worker (for the mocked payment job)
   php artisan queue:work
   ```
7. **Access the Application:**
   Open your browser and navigate to `http://localhost:8000`. The Laravel server natively serves the compiled React application.:
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
