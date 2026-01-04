<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (!User::where('email', 'admin@gmail.com')->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('123456'),
                'role' => 'admin',

                'phone' => '0912345678',
                'location' => 'Hà Nội, Việt Nam',
                'bio' => 'Quản trị viên hệ thống VnDaily. Chịu trách nhiệm vận hành và kiểm duyệt nội dung.',
                'website' => 'https://vndaily.vn',
                'avatar' => null,
                'reputation_score' => 1000,
            ]);
        }
    }
}
