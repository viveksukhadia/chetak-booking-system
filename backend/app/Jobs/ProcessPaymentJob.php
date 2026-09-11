<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Simulate a ~20% failure rate
        $isSuccess = rand(1, 100) > 20;

        if ($isSuccess) {
            $this->order->update(['status' => 'confirmed']);
            Log::info("Order {$this->order->id} payment confirmed.");
        } else {
            DB::transaction(function () {
                $this->order->update(['status' => 'failed']);

                // Restore stock
                $product = $this->order->product()->lockForUpdate()->first();
                $product->stock += $this->order->quantity;
                $product->save();
            });
            Log::info("Order {$this->order->id} payment failed. Stock restored.");
        }
    }
}
