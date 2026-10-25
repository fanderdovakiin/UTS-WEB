<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Jalankan dengan: php artisan migrate --seed
     * Semua seeder bersifat aman dijalankan ulang (tidak menggandakan data
     * dan tidak mengubah stok dua kali).
     */
    public function run(): void
    {
        $this->call([
            DemoUserSeeder::class,
            MasterDataSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
