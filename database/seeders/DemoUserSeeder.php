<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /**
     * Akun demo hanya untuk lokal/bahan praktikum. Jangan dipakai di production.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'owner@ruangstok.test'],
            [
                'name' => 'Budi Santoso',
                'password' => 'ruangstok123', // otomatis di-hash oleh cast 'hashed'
                'role' => User::ROLE_ADMIN,
            ],
        );

        User::updateOrCreate(
            ['email' => 'staff@ruangstok.test'],
            [
                'name' => 'Siti Aminah',
                'password' => 'ruangstok123',
                'role' => User::ROLE_STAFF,
            ],
        );

        User::firstOrCreate(
            ['email' => 'admin2@ruangstok.test'],
            [
                'name' => 'Rina Wijaya',
                'password' => 'ruangstok123',
                'role' => User::ROLE_ADMIN,
            ],
        );
    }
}
