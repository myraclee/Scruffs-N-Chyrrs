<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; 

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Creates our specific admin/test user
        User::factory()->create([
            'first_name' => 'Test',          
            'last_name' => 'User',           
            'email' => 'test@example.com',
            'password' => Hash::make('password123'), 
        ]);
    }
}