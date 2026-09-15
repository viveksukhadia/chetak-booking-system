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
            [
                'name' => 'Professional Drone X-Pro', 
                'description' => '4K HD camera drone with 3-axis gimbal and obstacle avoidance.',
                'price' => 899.99,
                'image_url' => 'https://images.unsplash.com/photo-1507582020474-9a35b7d455d9?w=600&h=600&fit=crop',
                'stock' => 12
            ],
            [
                'name' => 'Mechanical Keyboard (RGB)', 
                'description' => 'Tactile mechanical switches with customizable per-key RGB backlighting.',
                'price' => 145.50,
                'image_url' => 'https://images.unsplash.com/photo-1595225476474-87563907a212?w=600&h=600&fit=crop',
                'stock' => 50
            ],
            [
                'name' => 'Virtual Reality Headset', 
                'description' => 'Next-gen standalone VR headset with 120Hz display and haptic controllers.',
                'price' => 399.00,
                'image_url' => 'https://images.unsplash.com/photo-1622979135225-d2ba269cf1ac?w=600&h=600&fit=crop',
                'stock' => 8
            ],
            [
                'name' => 'Wireless Noise-Canceling Headphones', 
                'description' => 'Over-ear headphones with active noise cancellation and 40-hour battery life.',
                'price' => 299.99,
                'image_url' => 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?w=600&h=600&fit=crop',
                'stock' => 25
            ],
            [
                'name' => 'Smart Fitness Mirror', 
                'description' => 'Interactive home gym mirror with live classes and AI form correction.',
                'price' => 1495.00,
                'image_url' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&h=600&fit=crop',
                'stock' => 2
            ],
        ]);
    }
}
