<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Jobs\ProcessPaymentJob;
use App\Http\Requests\BookProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index()
    {
        return ProductResource::collection(Product::all());
    }

    public function orders(Request $request)
    {
        $orders = $request->user()->orders()->with('product')->latest()->get();
        return OrderResource::collection($orders);
    }

    public function book(BookProductRequest $request, $id)
    {
        // Validation is automatically handled by BookProductRequest
        $quantity = $request->validated('quantity');

        try {
            // DB::transaction automatically wraps the logic in a transaction
            // If an exception is thrown, the transaction is rolled back
            $order = DB::transaction(function () use ($id, $quantity, $request) {
                // Lock the product row for update to prevent concurrent modification
                $product = Product::lockForUpdate()->findOrFail($id);

                if ($product->stock < $quantity) {
                    // Throw a specific exception or return early
                    throw new \Exception('Not enough stock available.', 422);
                }

                // Decrement stock
                $product->stock -= $quantity;
                $product->save();

                // Create pending order
                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'status' => 'pending'
                ]);

                return $order;
            });

            // Dispatch payment job
            ProcessPaymentJob::dispatch($order);

            return response()->json([
                'message' => 'Booking successful. Payment is processing.',
                'order' => new OrderResource($order)
            ], 201);

        } catch (\Exception $e) {
            $code = $e->getCode() == 422 ? 422 : 500;
            return response()->json([
                'message' => 'Booking failed.',
                'error' => $e->getMessage()
            ], $code);
        }
    }
}
