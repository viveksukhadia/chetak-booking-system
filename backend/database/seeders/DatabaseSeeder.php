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
            ['name' => 'Limited Edition Sneakers', 'stock' => 5],
            ['name' => 'Concert Ticket (VIP)', 'stock' => 10],
            ['name' => 'Exclusive Watch', 'stock' => 3],
        ]);
    }
}
