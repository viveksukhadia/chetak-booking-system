<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        \App\Models\Product::insert([
            [
                'name' => 'Limited Edition Sneakers', 
                'description' => 'Premium high-top street sneakers with vibrant contrasting colors.',
                'price' => 250.00,
                'image_url' => '/images/sneakers.jpg',
                'stock' => 5
            ],
            [
                'name' => 'Concert Ticket (VIP)', 
                'description' => 'All-access backstage pass for the Neon Void Festival 2077.',
                'price' => 500.00,
                'image_url' => '/images/vip_ticket.jpg',
                'stock' => 10
            ],
            [
                'name' => 'Exclusive Watch', 
                'description' => 'Luxurious mechanical timepiece with gold and silver detailing.',
                'price' => 1200.00,
                'image_url' => '/images/exclusive_watch.jpg',
                'stock' => 3
            ],
        ]);
    }
}
