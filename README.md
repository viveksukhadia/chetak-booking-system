# Concurrent-Safe Booking System

A full-stack booking system built with Laravel and React, demonstrating concurrent-safe stock management.

## Setup Instructions

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+

### Backend (Laravel) Setup
1. Navigate to the backend directory:
   ```bash
   cd backend
   ```
2. Install dependencies:
   ```bash
   composer install
   ```
3. Run database migrations and seed default products (SQLite):
   ```bash
   php artisan migrate:fresh --seed
   ```
4. Start the Laravel development server:
   ```bash
   php artisan serve
   ```
5. In a separate terminal, start the queue worker to process payments:
   ```bash
   php artisan queue:work
   ```

### Frontend (React) Setup
1. Navigate to the frontend directory:
   ```bash
   cd frontend
   ```
2. Install dependencies:
   ```bash
   npm install
   ```
3. Start the Vite development server:
   ```bash
   npm run dev
   ```

## Architecture Decisions & Trade-offs
- **Database**: SQLite was chosen for simplicity of local setup for reviewers. It handles basic local concurrency locking reasonably well, though MySQL/PostgreSQL with proper InnoDB row-level locking would be the production choice.
- **Data Fetching**: Used React Query for its powerful caching, query invalidation, and built-in support for optimistic UI updates.
- **UI Framework**: TailwindCSS was used for quick, modern, and responsive styling.
- **Shortcuts Taken**: For the assignment, no email verification or complex role-based access control was added. Real-world applications would require rate limiting, pagination, and robust input sanitization.

## Preventing Overselling Under Concurrent Load

### How does your solution prevent overselling under concurrent load?
The system utilizes **Pessimistic Locking** (`lockForUpdate()`) wrapped inside a database transaction in the `BookingController`. 

When multiple users try to book the same product simultaneously:
1. The database transaction begins.
2. `Product::lockForUpdate()->findOrFail($id)` asks the database to lock the specific product row for writing.
3. The first request acquires the lock. Subsequent requests for the exact same product wait until the first transaction commits or rolls back.
4. The first request checks the stock. If sufficient, it decrements the stock, creates the order, and commits the transaction. The row lock is released.
5. The waiting requests then acquire the lock one by one and check the *updated* stock. If the stock has run out due to the first request, they fail gracefully and throw an exception, preventing overselling.

If a payment fails later on, the queued `ProcessPaymentJob` also uses a transaction with `lockForUpdate()` to safely restore the product's stock.

### How did you verify it?
- **Automated Verification**: Wrote a PHPUnit Feature test (`tests/Feature/ConcurrentBookingTest.php`) that demonstrates the logical flow of preventing a booking if the requested quantity exceeds stock. While a true concurrent race-condition HTTP test requires parallel processing (e.g., using Laravel's parallel testing package or simulated load via Apache Bench), the locking logic itself fundamentally relies on the database engine's ACID properties.
- **Manual Verification**: You can verify this by opening two separate browsers (or an incognito window), logging in with two different users, and simultaneously attempting to book the last item in stock. The database will process one request fully before completing the other, ensuring stock never dips below zero.
