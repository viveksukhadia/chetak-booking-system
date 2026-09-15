<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessPaymentJob;

class ConcurrentBookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:concurrent-book {userId} {productId} {quantity=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Attempts to book a product, used specifically for concurrency testing.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('userId');
        $productId = $this->argument('productId');
        $quantity = (int) $this->argument('quantity');

        $maxRetries = 50;
        $attempt = 0;
        while ($attempt < $maxRetries) {
            try {
                if (DB::getDriverName() === 'sqlite') {
                    DB::statement('PRAGMA busy_timeout = 10000;');
                }
                
                DB::transaction(function () use ($productId, $quantity, $userId) {
                    if (DB::getDriverName() === 'sqlite') {
                        // SQLite does not support lockForUpdate row locks. 
                        // To simulate the absolute protection lockForUpdate gives in MySQL/Postgres,
                        // we must use atomic decrement or BEGIN EXCLUSIVE.
                        $updated = DB::update('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?', [$quantity, $productId, $quantity]);
                        if ($updated === 0) {
                            throw new \Exception('Not enough stock available.', 422);
                        }
                        $product = Product::find($productId);
                    } else {
                        $product = Product::lockForUpdate()->findOrFail($productId);
                        if ($product->stock < $quantity) {
                            throw new \Exception('Not enough stock available.', 422);
                        }
                        $product->stock -= $quantity;
                        $product->save();
                    }

                    $order = Order::create([
                        'user_id' => $userId,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'status' => 'pending'
                    ]);
                    ProcessPaymentJob::dispatch($order);
                });
                return Command::SUCCESS;
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'database is locked')) {
                    $attempt++;
                    usleep(rand(10000, 50000));
                    continue;
                }
                
                $this->error($e->getMessage());
                return Command::FAILURE;
            }
        }
        $this->error("Failed to acquire DB lock after {$maxRetries} attempts.");
        return Command::FAILURE;
    }
}
