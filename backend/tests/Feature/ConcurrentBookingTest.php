<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Process;

class ConcurrentBookingTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_booking_prevents_overselling()
    {
        // 1. Setup Data
        $product = Product::create(['name' => 'Limited Edition Item', 'stock' => 5]);
        $users = User::factory()->count(10)->create();
        
        Queue::fake(); // Prevent mock payment job from restoring stock during the test

        // =========================================================================
        // GENUINE PARALLEL CONCURRENCY TEST 
        // =========================================================================
        // We use Process::pool to spawn 10 separate OS-level PHP processes simultaneously.
        // Each process executes the `app:concurrent-book` artisan command.
        // Since we configured phpunit to use a physical `testing.sqlite` database rather 
        // than an isolated `:memory:` DB, all 10 processes will hit the exact same 
        // physical database file concurrently, triggering real database locks.
        
        // Set environment variable for child processes to inherit
        putenv('DB_DATABASE=' . database_path('testing.sqlite'));

        $processes = [];
        // Start all processes concurrently
        foreach ($users as $user) {
            $processes[] = Process::command("php artisan app:concurrent-book {$user->id} {$product->id} 1")->start();
        }

        $successfulBookings = 0;
        $failedBookings = 0;
        $errors = [];

        // Wait for all processes to finish and collect results
        foreach ($processes as $process) {
            $result = $process->wait();
            if ($result->successful()) {
                $successfulBookings++;
            } else {
                $failedBookings++;
                $errors[] = $result->errorOutput() ?: $result->output();
            }
        }

        // Restore original env
        putenv('DB_DATABASE=:memory:');

        // 3. Assertions
        $this->assertEquals(5, $successfulBookings, 'Only exactly 5 bookings should succeed.');
        $this->assertEquals(5, $failedBookings, 'The remaining 5 requests must fail cleanly.');
        
        // Refresh product from the DB to check final stock
        $finalStock = $product->fresh()->stock;
        $this->assertEquals(0, $finalStock, 'Stock must strictly halt at 0 and never become negative.');
        $this->assertGreaterThanOrEqual(0, $finalStock, 'Stock must never be negative.');
    }
}
